<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Timing;

use App\Http\Requests\Activities\StoreActivity;
use App\Http\Requests\Activities\UpdateActivity;
use App\Lib\Etc;
use App\Lib\TimingsFreezer;
use App\Lib\Util;
use App\Models\Activity;
use App\Models\Part;
use App\Models\Freeze;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use App\Models\Verification;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use DB;
use PhpOffice\PhpWord\TemplateProcessor;
use \PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Storage;
use App\Lib\Indicators;

class TimingsController extends Controller
{
    protected function getQuery(Request $request)
    {
        $items = Timing::with([
            'user',
            'activity',
            'activity.project',
            'activity.clients',
            'activity.clients.user',
            'activity.clients.part',
            'activity.clients.projectUser',
            'projectUser.location:id,code',
            'projectUser.user',
            'projectUser.part'
        ])
        ->whereNotNull('timing');

        $users = $request->users;

        $customAccessUserId = in_array($request->user()->id, array_keys(Etc::$employeesByManager))
            ? $request->user()->id : null;

        if (!in_array('superuser', $request->user()->roles) && !$customAccessUserId) {
            $users = [$request->user()->id];
        }

        if ($users || $request->projects || $request->locations || $request->positions || $customAccessUserId) {
            $items->whereHas('projectUser', function ($query) use ($users, $request, $customAccessUserId) {
                if ($request->projects) {
                    $query->whereIn('project_id', $request->projects);
                }

                if ($users) {
                    $query->whereIn('user_id', $users);

                    if (!in_array('superuser', $request->user()->roles) && !$customAccessUserId) {
                        $query->where(function ($query) {
                            $query->whereNull('terminated_at')
                                ->orWhere('terminated_at', '>', now());
                        });
                    }
                }

                if ($customAccessUserId) {
                    $query->where(function ($query) use ($customAccessUserId) {
                        $query->where(function ($query) use ($customAccessUserId) {
                            $usersPerProjects = Etc::$employeesByManager[$customAccessUserId];

                            foreach ($usersPerProjects as $projectId => $userIds) {
                                $query->orWhere(function ($query) use ($projectId, $userIds) {
                                    $query->where('project_id', $projectId)
                                        ->whereHas('user', function ($query) {
                                            $query->where('roles', 'not like', '%superuser%');
                                        });

                                    if (count($userIds)) {
                                        $query->whereIn('user_id', $userIds);
                                    }
                                });
                            }
                        })
                        ->orWhere(function ($query) use ($customAccessUserId) {
                            $query->where('user_id', $customAccessUserId);
                        });
                    });
                }

                if ($request->locations) {
                    $query->whereIn('location_id', $request->locations);
                }

                if ($request->positions) {
                    $query->whereIn('position', $request->positions);
                }
            });
        }

        if ($from = $request->from) {
            $items->whereDate('began_at', '>=', $from);
        }

        if ($till = $request->till) {
            $items->whereDate('ended_at', '<=', $till);
        }

        if ($search = $request->search) {
            $items->where(function ($query) use ($search, $users) {
                $query->where('id', $search)
                    ->orWhere('comment', 'like', "%$search%")
                    ->orWhereHas('activity', function ($query) use ($search) {
                        $query->where('title', 'like', "%$search%");
                    })
                    ->orWhereHas('projectUser', function ($query) use ($search, $users) {
                        $query->whereHas('part', function ($query) use ($search) {
                            $query->where('description', 'like', "%$search%");
                        });

                        if ($users) {
                            $query->whereIn('user_id', $users);
                        }
                    });
            });
        }

        if ($request->mode == 4) {
            $items->whereHas('projectUser', function ($query) {
                $query->where('part_id', 4);
            });
        }

        if ($request->requests) {
            $items->whereHas('requests', function ($query) {
                $query->whereStatus('requested');
            })->with([
                'requests',
                'requests.user'
            ]);
        }

        return $items;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $per_page = (int) $request->input('itemsPerPage');

        $items = $this->getQuery($request);

        $items->with([
            'authorTiming',
            'verifiers'
        ]);

        foreach ($request->input('sortBy', ['began_at']) as $index => $order) {
            $items->orderBy($order, isset($request->input('sortDesc', [false])[$index]) &&
                $request->input('sortDesc', ['false'])[$index] == 'true' ? 'desc' : 'asc');
        }

        return $items->paginate($per_page != -1 ? $per_page : $items->count());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreActivity  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();

        $request->validate(
            $this->makePeriodValidators(
                $data,
                Activity::find($data['activity_id'])->project_id,
                $request->user()->id
            )
        );

        $began_at = new Carbon($data['start_date'] . ' ' . $data['start_time']);
        $ended_at = new Carbon($data['end_date'] . ' ' . $data['end_time']);

        $timing = [
            'user_id' => $request->user()->id,
            'activity_id' => $data['activity_id'],
            'comment' => $data['comment'],
            'volunteering' => $data['volunteering'],
            'began_at' => $began_at,
            'ended_at' => $ended_at,
            'timing' => $began_at->diffInSeconds($ended_at)
        ];

        $activity = Activity::find($timing['activity_id']);
        $participants = $activity->allUsers()
            ->where('role', '!=', 'client')
            ->get();

        $doesAuthorParticipate = !!$participants->firstWhere('user_id', $timing['user_id']);

        foreach ($participants as $participant) {
            $timing['project_user_id'] = $participant->project_user_id;
            $timing['comment'] = !$doesAuthorParticipate || $participant->user_id == $timing['user_id']
                ? $data['comment'] : null;

            Timing::create($timing);
        }

        return [];
    }

    /**
     * Display the specified resource.
     *
     * @param  Timing  $timing
     * @return \Illuminate\Http\Response
     */
    public function show(Timing $timing)
    {
        return $timing;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateActivity  $request
     * @param  Activity  $activity
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Timing $timing)
    {
        $data = $request->all();
        $userId = $request->user()->id;

        if (isset($data['verified'])) {
            if ($timing->projectUser &&
                $timing->projectUser->user &&
                (in_array('superuser', $timing->projectUser->user->roles) !== false ||
                    in_array($timing->projectUser->user->id, array_keys(Etc::$employeesByManager))) &&
                $userId != 2 &&
                !$request->user()->verifiedUsers()->where('verified_user_id', $timing->projectUser->user->id)->first()
            ) {
                abort(403);
            }

            $verified = !!$data['verified'];

            $verification = !!$timing->verifiers()->where('id', $userId)->count();

            if (!$verified && $verification) {
                $timing->verifiers()->detach($userId);
            }

            if ($verified && !$verification) {
                $timing->verifiers()->attach($userId);
            }

            $verified = $timing->verifiers->count() > (
                in_array(
                    $timing->projectUser->user_id,
                    Etc::$multiverifiedEmployees[$timing->projectUser->project_id] ?? []
                ) ? 1 : 0);

            if ($timing->verified !== $verified) {
                $timing->verified = $verified;

                if ($verified) {
                    $timing->flagged = false;
                }

                $timing->timestamps = false;
                $timing->save();

                Indicators::invalidate($timing->projectUser->project_id);
                Indicators::compute();
            }
        } elseif (isset($data['flagged'])) {
            $flagged = !!$data['flagged'];
            if ($timing->flagged !== $flagged) {
                $timing->flagged = $flagged;

                if ($flagged) {
                    $timing->flagged_at = now();
                    $timing->flagged_by = $userId;
                }

                $timing->timestamps = false;
                $timing->save();
            }
        } elseif (isset($data['note'])) {
            $timing->note = $data['note'];
            $timing->flagged_at = now();
            $timing->timestamps = false;
            $timing->save();
        } else {
            if (isset($data['volunteering'])) {
                $volunteering = !!$data['volunteering'];
                if ($timing->volunteering !== $volunteering) {
                    $timing->volunteering = $volunteering;
                    $timing->save();
                }
            }

            $request->validate(
                $this->makePeriodValidators(
                    $data,
                    $timing->activity->project_id,
                    $timing->projectUser->user_id,
                    $timing->id
                )
            );

            $update = [];

            if (isset($data['start_date']) && isset($data['start_time'])) {
                $update['began_at'] = new Carbon($data['start_date'] . ' ' . $data['start_time']);
            }

            if (isset($data['end_date']) && isset($data['end_time'])) {
                $update['ended_at'] = new Carbon($data['end_date'] . ' ' . $data['end_time']);
            }

            if (isset($update['began_at']) && isset($update['ended_at'])) {
                $update['timing'] = $update['began_at']->diffInSeconds($update['ended_at']);
            }

            if (isset($data['comment'])) {
                $update['comment'] = $data['comment'];
            }

            $timing->update($update);
        }

        $timing->load([
            'projectUser.user',
            'projectUser.location',
            'projectUser.part',
            'activity.project'
        ]);

        return $timing;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Timing  $timing
     * @return \Illuminate\Http\Response
     */
    public function destroy(Timing $timing)
    {
        $timing->delete();
    }

    /**
     * Display timing aggregate.
     *
     * @return \Illuminate\Http\Response
     */
    public function total(Request $request)
    {
        $items = Timing::whereNotNull('timing');

        if (!in_array('superuser', $request->user()->roles)) {
            $items->whereHas('projectUser', function ($query) use ($request) {
                $userId = $request->user()->id;

                if (in_array($userId, array_keys(Etc::$employeesByManager))) {
                    $query->where(function ($query) use ($userId) {
                        $query->where(function ($query) use ($userId) {
                            $usersPerProjects = Etc::$employeesByManager[$userId];

                            foreach ($usersPerProjects as $projectId => $userIds) {
                                $query->orWhere(function ($query) use ($projectId, $userIds) {
                                    $query->where('project_id', $projectId)
                                        ->whereHas('user', function ($query) {
                                            $query->where('roles', 'not like', '%superuser%');
                                        });

                                    if (count($userIds)) {
                                        $query->whereIn('user_id', $userIds);
                                    }
                                });
                            }
                        })
                        ->orWhere(function ($query) use ($userId) {
                            $query->where('user_id', $userId);
                        });
                    });
                } else {
                    $query->where('user_id', $request->user()->id);
                }
            });
        }

        if ($projects = $request->projects) {
            $items->whereHas('activity', function ($query) use ($projects) {
                $query->whereIn('project_id', $projects);
            });
        }

        if ($users = $request->users) {
            $items->whereHas('projectUser.user', function ($query) use ($users) {
                $query->whereIn('user_id', $users);
            });
        }

        if ($positions = $request->positions) {
            $items->whereHas('projectUser', function ($query) use ($positions) {
                $query->whereIn('position', $positions);
            });
        }

        if ($from = $request->from) {
            $items->whereDate('began_at', '>=', $from);
        }

        if ($till = $request->till) {
            $items->whereDate('ended_at', '<=', $till);
        }

        if ($search = $request->search) {
            $items->where(function ($query) use ($search) {
                $query->where('comment', 'like', "%$search%")
                    ->orWhereHas('activity', function ($query) use ($search) {
                        $query->where('title', 'like', "%$search%");
                    });
            });
        }

        $taxiItems = clone $items;

        $total = $items->sum('timing');

        $items->where('volunteering', 0)
            ->whereDoesntHave('projectUser', function ($query) {
                $query->whereIn('part_id', [19, 23]);
            });

        $timing = $items->sum('timing');
        $items->where('verified', true);
        $verified = $items->sum('timing');

        if ($request->mode != 4) {
            return [
                'timing' => $total - $verified,
                'verified' => $verified,
                'total' => $total
            ];
        } else {
            $taxiItems->whereHas('projectUser', function ($query) {
                $query->where('part_id', 4);
            })
            ->join('activities', 'activities.id', '=', 'activity_id');

            $cost_total = $taxiItems->sum('activities.forms->29->5FsZ');

            $taxiItems->where('volunteering', 0)
                ->whereDoesntHave('projectUser', function ($query) {
                    $query->whereIn('part_id', [19, 23]);
                });

            $cost = $taxiItems->sum('activities.forms->29->5FsZ');
            $taxiItems->where('verified', true);
            $cost_verified = $taxiItems->sum('activities.forms->29->5FsZ');

            return [
                'timing' => $timing - $verified,
                'verified' => $verified,
                'total' => $total,
                'cost' => number_format($cost - $cost_verified, 2, '.', ' ') . ' сум',
                'cost_verified' => number_format($cost_verified, 2, '.', ' ') . ' сум',
                'cost_total' => number_format($cost_total, 2, '.', ' ') . ' сум'
            ];
        }
    }

    protected function aggregatedParts(Request $request, $sum = true)
    {
        return Part::withCount([
            'timings as timing' => function ($query) use ($request, $sum) {
                if ($sum) {
                    $query->select(DB::raw('SUM(timing)'));
                }

                $query->where('timings.activity_id', DB::raw('user_activity.activity_id'));

                $query->whereNotNull('timing')
                    ->where('volunteering', 0)
                    ->whereDoesntHave('projectUser', function ($query) {
                        $query->whereIn('part_id', [19, 23]);
                    });

                if ($request->mode == 3 || !$sum) {
                    $query->where('verified', true);
                }

                if ($projects = $request->projects) {
                    $query->whereHas('activity', function ($query) use ($projects) {
                        $query->whereIn('project_id', $projects);
                    });
                }

                $query->whereHas('projectUser', function ($query) use ($request) {
                    if (!in_array('superuser', $request->user()->roles)) {
                        $userId = $request->user()->id;

                        if (in_array($userId, array_keys(Etc::$employeesByManager))) {
                            $query->where(function ($query) use ($userId) {
                                $query->where(function ($query) use ($userId) {
                                    $usersPerProjects = Etc::$employeesByManager[$userId];

                                    foreach ($usersPerProjects as $projectId => $userIds) {
                                        $query->orWhere(function ($query) use ($projectId, $userIds) {
                                            $query->where('project_id', $projectId)
                                                ->whereHas('user', function ($query) {
                                                    $query->where('roles', 'not like', '%superuser%');
                                                });

                                            if (count($userIds)) {
                                                $query->whereIn('user_id', $userIds);
                                            }
                                        });
                                    }
                                })
                                ->orWhere(function ($query) use ($userId) {
                                    $query->where('user_id', $userId);
                                });
                            });
                        } else {
                            $query->where('user_id', $request->user()->id);
                        }
                    }

                    if ($users = $request->users) {
                        $query->whereIn('user_id', $users);
                    }

                    if ($positions = $request->positions) {
                        $query->whereIn('position', $positions);
                    }
                });

                if ($from = $request->from) {
                    $query->whereDate('began_at', '>=', $from);
                }

                if ($till = $request->till) {
                    $query->whereDate('ended_at', '<=', $till);
                }

                if ($search = $request->search) {
                    $query->where('comment', 'like', "%$search%");
                }
            }
        ]);
    }

    /**
     * Display aggregated timing.
     *
     * @return \Illuminate\Http\Response
     */
    public function aggregated(Request $request)
    {
        $items = $this->aggregatedParts($request)
            ->whereHas('timings', function ($query) use ($request) {
                $query->whereNotNull('timing')
                    ->where('volunteering', 0)
                    ->whereDoesntHave('projectUser', function ($query) {
                        $query->whereIn('part_id', [19, 23]);
                    });

                if ($request->mode == 3) {
                    $query->where('verified', true);
                }

                if (!in_array('superuser', $request->user()->roles)) {
                    $query->whereHas('projectUser', function ($query) use ($request) {
                        $userId = $request->user()->id;

                        if (in_array($userId, array_keys(Etc::$employeesByManager))) {
                            $query->where(function ($query) use ($userId) {
                                $query->where(function ($query) use ($userId) {
                                    $usersPerProjects = Etc::$employeesByManager[$userId];

                                    foreach ($usersPerProjects as $projectId => $userIds) {
                                        $query->orWhere(function ($query) use ($projectId, $userIds) {
                                            $query->where('project_id', $projectId)
                                                ->whereHas('user', function ($query) {
                                                    $query->where('roles', 'not like', '%superuser%');
                                                });

                                            if (count($userIds)) {
                                                $query->whereIn('user_id', $userIds);
                                            }
                                        });
                                    }
                                })
                                ->orWhere(function ($query) use ($userId) {
                                    $query->where('user_id', $userId);
                                });
                            });
                        } else {
                            $query->where('user_id', $request->user()->id);
                        }
                    });
                }

                if ($projects = $request->projects) {
                    $query->whereHas('activity', function ($query) use ($projects) {
                        $query->whereIn('project_id', $projects);
                    });
                }

                if ($users = $request->users) {
                    $query->whereHas('projectUser.user', function ($query) use ($users) {
                        $query->whereIn('user_id', $users);
                    });
                }

                if ($positions = $request->positions) {
                    $query->whereHas('projectUser', function ($query) use ($positions) {
                        $query->whereIn('position', $positions);
                    });
                }

                if ($from = $request->from) {
                    $query->whereDate('began_at', '>=', $from);
                }

                if ($till = $request->till) {
                    $query->whereDate('ended_at', '<=', $till);
                }

                if ($search = $request->search) {
                    $query->where('comment', 'like', "%$search%");
                }
            })
            ->orderBy('type')
            ->get()
            ->groupBy('type')
            ->map(function ($group, $key) {
                return [
                    'title' => [
                        'Услуга',
                        'Профильная деятельность',
                        'Административная деятельность',
                        'Методическая деятельность',
                        'Хозяйственная деятельность',
                        'Волонтерство'
                    ][$key],
                    'timing' => $group->sum('timing'),
                    'items' => $group
                ];
            });

        $dailyQuery = Timing::where(function ($query) use ($request) {
            $query->whereNotNull('timing')
                ->where('volunteering', 0)
                ->whereDoesntHave('projectUser', function ($query) {
                    $query->whereIn('part_id', [19, 23]);
                });

            if ($request->mode == 3) {
                $query->where('verified', true);
            }

            if (!in_array('superuser', $request->user()->roles)) {
                $query->whereHas('projectUser', function ($query) use ($request) {
                    $query->where('user_id', $request->user()->id);
                });
            }

            if ($projects = $request->projects) {
                $query->whereHas('activity', function ($query) use ($projects) {
                    $query->whereIn('project_id', $projects);
                });
            }

            if ($users = $request->users) {
                $query->whereHas('projectUser.user', function ($query) use ($users) {
                    $query->whereIn('user_id', $users);
                });
            }

            if ($positions = $request->positions) {
                $query->whereHas('projectUser', function ($query) use ($positions) {
                    $query->whereIn('position', $positions);
                });
            }

            if ($from = $request->from) {
                $query->whereDate('began_at', '>=', $from);
            }

            if ($till = $request->till) {
                $query->whereDate('ended_at', '<=', $till);
            }

            if ($search = $request->search) {
                $query->where('comment', 'like', "%$search%");
            }
        })
        ->orderBy('began_at')
        ->groupBy(DB::raw('DATE(began_at)'));

        $daily = $dailyQuery->get([
            DB::raw('DATE(began_at) as date'),
            DB::raw('SUM(timing) as timing')
        ])
        ->mapWithKeys(function ($item) {
            return [$item->date => $item->timing];
        })
        ->toArray();

        $dailyQuery->where(function ($query) use ($request) {
            $query->where('verified', true);
        });

        $dailyVerified = $dailyQuery->get([
            DB::raw('DATE(began_at) as date'),
            DB::raw('SUM(timing) as timing')
        ])
        ->mapWithKeys(function ($item) {
            return [$item->date => $item->timing];
        })
        ->toArray();

        $date = $request->from;

        if (!$date) {
            $date = array_key_first($daily);
        }

        $date = new Carbon($date);

        $till = $request->till;

        if (!$till) {
            $till = array_key_last($daily);
        }

        $till = new Carbon($till);

        $days = [];

        while ($date->lessThanOrEqualTo($till)) {
            $days[] = [
                'date' => $date->format('d.m'),
                'timing' => (int) ($daily[$date->format('Y-m-d')] ?? 0),
                'verified_timing' => (int) ($dailyVerified[$date->format('Y-m-d')] ?? 0),
                'weekend' => $date->format('N') > 5
            ];
            $date->addDay();
        }

        $volunteering = Timing::whereNotNull('timing');

        if (!in_array('superuser', $request->user()->roles)) {
            $volunteering->whereHas('projectUser', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            });
        }

        if ($projects = $request->projects) {
            $volunteering->whereHas('activity', function ($query) use ($projects) {
                $query->whereIn('project_id', $projects);
            });
        }

        if ($users = $request->users) {
            $volunteering->whereHas('projectUser.user', function ($query) use ($users) {
                $query->whereIn('user_id', $users);
            });
        }

        if ($positions = $request->positions) {
            $volunteering->whereHas('projectUser', function ($query) use ($positions) {
                $query->whereIn('position', $positions);
            });
        }

        if ($from = $request->from) {
            $volunteering->whereDate('began_at', '>=', $from);
        }

        if ($till = $request->till) {
            $volunteering->whereDate('ended_at', '<=', $till);
        }

        if ($search = $request->search) {
            $volunteering->where(function ($query) use ($search) {
                $query->where('comment', 'like', "%$search%")
                    ->orWhereHas('activity', function ($query) use ($search) {
                        $query->where('title', 'like', "%$search%");
                    });
            });
        }

        $volunteering->where(function ($query) {
            $query->where('volunteering', 1)
                ->orWhereHas('projectUser', function ($query) {
                    $query->whereIn('part_id', [19, 23]);
                });
        });

        if ($request->mode == 3) {
            $volunteering->where('verified', true);
        }

        $items[] = [
            'title' => 'Волонтерство',
            'timing' => $volunteering->sum('timing')
        ];

        return [
            'days' => $days,
            'parts' => $items,
            'timings' => $this->total($request)
        ];
    }

    public function duplicate(Timing $timing)
    {
        $timing = $timing->replicate();
        $timing->verified = false;
        $timing->frozen = false;

        $changeDate = false;

        foreach (collect(
            $this->makePeriodValidators(
                [
                    'start_date' => $timing->began_at->format('Y-m-d'),
                    'start_time' => $timing->began_at->format('H:i:s'),
                    'end_date' => $timing->ended_at->format('Y-m-d'),
                    'end_time' => $timing->ended_at->format('H:i:s'),
                ],
                $timing->projectUser->project_id,
                $timing->projectUser->user_id
            )
        )->flatten() as $validator) {
            $validator(null, null, function () use (&$changeDate) {
                $changeDate = true;
            });

            if ($changeDate) {
                break;
            }
        }

        if ($changeDate) {
            $now = now();

            $timing->began_at = $timing->began_at
                ->setYear($now->year)
                ->setMonth($now->month)
                ->setDay($now->day);

            $timing->ended_at = $timing->ended_at
                ->setYear($now->year)
                ->setMonth($now->month)
                ->setDay($now->day);
        }

        $timing->save();

        return $timing;
    }

    public function batchVerify(Request $request)
    {
        $userId = $request->user()->id;

        if (!in_array($userId, [1, 2, 5, 43, 65, 6721])) {
            abort(403);
        }

        $query = $this->getQuery($request);

        if (!in_array($request->user()->id, [1, 2, 5])) {
            $query->whereDoesnthave('projectUser.user', function ($query) use ($request) {
                $query->where(function ($query) {
                    $query->where('roles', 'like', '%superuser%')
                        ->orWhereIn('id', array_keys(Etc::$employeesByManager));
                })
                ->whereDoesnthave('verifyingUsers', function ($query) use ($request) {
                    $query->where('user_id', $request->user()->id);
                });
            });
        }

        $items = $query->get();

        foreach ($items as $timing) {
            $verification = !!$timing->verifiers()->where('id', $userId)->count();

            if (!$verification) {
                $timing->verifiers()->attach($userId);
            }

            $verified = $timing->verifiers->count() > (
                in_array(
                    $timing->projectUser->user_id,
                    Etc::$multiverifiedEmployees[$timing->projectUser->project_id] ?? []
                ) ? 1 : 0);

            if ($timing->verified !== $verified) {
                $timing->verified = $verified;

                if ($verified) {
                    $timing->flagged = false;
                }

                $timing->save();
            }
        }
    }

    public function freeze(Request $request)
    {
        $user = $request->user();

        if (!in_array('superuser', $user->roles) || !($user->id == 2146 || $user->id == 5 || $user->id == 2)) {
            abort(403);
        }

        TimingsFreezer::freeze(
            $request->projects[0],
            $request->users,
            Carbon::parse($request->from . ' 00:00:00'),
            Carbon::parse($request->till . ' 23:59:59')
        );
    }

    private function makePeriodValidators($data, $projectId, $userId, $excludeTimingId = null)
    {
        if (!empty($data['start_date']) &&
            !empty($data['start_time']) &&
            !empty($data['end_date']) &&
            !empty($data['end_time'])
        ) {
            $began_at = new Carbon($data['start_date'] . ' ' . $data['start_time']);
            $ended_at = new Carbon($data['end_date'] . ' ' . $data['end_time']);

            $validate_start = function ($attribute, $value, $fail) use ($projectId, $userId, $began_at, $ended_at) {
                $freeze = Freeze::where('project_id', $projectId)
                    ->where(function ($query) use ($userId) {
                        $query->whereNull('user_id')
                            ->orWhere('user_id', $userId);
                    })
                    ->where(function ($query) use ($began_at, $ended_at) {
                        $query->where(function ($query) use ($began_at) {
                            $query->where('start_at', '<=', $began_at)
                                ->where('end_at', '>=', $began_at);
                        })
                        ->orWhere(function ($query) use ($began_at, $ended_at) {
                            $query->where('start_at', '>=', $began_at)
                                ->where('end_at', '<=', $ended_at);
                        });
                    })
                    ->first();

                if ($freeze) {
                    $fail('Период заморожен');
                }
            };

            $validate_end = function ($attribute, $value, $fail) use ($projectId, $userId, $began_at, $ended_at) {
                $freeze = Freeze::where('project_id', $projectId)
                    ->where(function ($query) use ($userId) {
                        $query->whereNull('user_id')
                            ->orWhere('user_id', $userId);
                    })
                    ->where(function ($query) use ($began_at, $ended_at) {
                        $query->where(function ($query) use ($ended_at) {
                            $query->where('start_at', '<=', $ended_at)
                                ->where('end_at', '>=', $ended_at);
                        })
                        ->orWhere(function ($query) use ($began_at, $ended_at) {
                            $query->where('start_at', '>=', $began_at)
                                ->where('end_at', '<=', $ended_at);
                        });
                    })->first();

                if ($freeze) {
                    $fail('Период заморожен');
                }
            };

            $validateOverlapStart = function ($attribute, $value, $fail) use ($excludeTimingId, $projectId, $userId, $began_at, $ended_at) {
                $overlappingTiming = Timing::where('id', '!=', $excludeTimingId)
                    ->whereHas('projectUser', function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    })
                    ->where(function ($query) use ($began_at, $ended_at) {
                        $query->where(function ($query) use ($began_at) {
                            $query->where('began_at', '<', $began_at)
                                ->where('ended_at', '>', $began_at);
                        })
                        ->orWhere(function ($query) use ($began_at, $ended_at) {
                            $query->where('began_at', '>', $began_at)
                                ->where('ended_at', '<', $ended_at);
                        });
                    })->first();

                if ($overlappingTiming) {
                    $fail('Время с ' . $overlappingTiming->began_at->format('H:i') . ' до ' .
                        $overlappingTiming->ended_at->format('H:i') .
                        ($overlappingTiming->projectUser->project_id == $projectId ?
                            ' занято деятельностью #' . $overlappingTiming->id :
                            ' занято деятельностью #' . $overlappingTiming->id . ' в ' .
                                Project::find($overlappingTiming->projectUser->project_id)->name));
                }
            };

            $validateOverlapEnd = function ($attribute, $value, $fail) use ($excludeTimingId, $projectId, $userId, $began_at, $ended_at) {
                $overlappingTiming = Timing::where('id', '!=', $excludeTimingId)
                    ->whereHas('projectUser', function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    })
                    ->where(function ($query) use ($began_at, $ended_at) {
                        $query->where(function ($query) use ($ended_at) {
                            $query->where('began_at', '<', $ended_at)
                                ->where('ended_at', '>', $ended_at);
                        })
                        ->orWhere(function ($query) use ($began_at, $ended_at) {
                            $query->where('began_at', '>', $began_at)
                                ->where('ended_at', '<', $ended_at);
                        });
                    })->first();

                if ($overlappingTiming) {
                    $fail('Время с ' . $overlappingTiming->began_at->format('H:i') . ' до ' .
                        $overlappingTiming->ended_at->format('H:i') .
                        ($overlappingTiming->projectUser->project_id == $projectId ?
                        ' занято деятельностью #' . $overlappingTiming->id :
                        ' занято деятельностью #' . $overlappingTiming->id . ' в ' .
                            Project::find($overlappingTiming->projectUser->project_id)->name));
                }
            };

            return [
                'start_date' => $validate_start,
                'start_time' => [$validate_start, $validateOverlapStart],
                'end_date' => $validate_end,
                'end_time' => [$validate_end, $validateOverlapEnd],
            ];
        }

        return [];
    }

    public function downloadDocuments(Request $request)
    {
        $user2user = [
            1 => [
                'Абдурахимова' => [
                    'Девятова Е. В.',
                    'Нац. Менеджер проекта'
                ],
                'Камалов' => [
                    'Девятова Е. В.',
                    'Нац. Менеджер проекта'
                ],
                'Данилов' => [
                    'Абдурахимова З. К.',
                    'Нац. Координатор'
                ],
                'Абу Шихада' => [
                    'Абдурахимова З. К.',
                    'Нац. Координатор'
                ],
                'Саматов' => [
                    'Абдурахимова З. К.',
                    'Нац. Координатор'
                ],
                'Махмудова' => [
                    'Абдурахимова З. К.',
                    'Нац. Координатор'
                ],
                'Курбанов' => [
                    'Абдурахимова З. К.',
                    'Нац. Координатор'
                ],
                'Бахринов' => [
                    'Абдурахимова З. К.',
                    'Нац. Координатор'
                ],
                'Атабаева' => [
                    'Абдурахимова З. К.',
                    'Нац. Координатор'
                ],
                'Хошимов' => [
                    'Абдурахимова З. К.',
                    'Нац. Координатор'
                ],
                'Одилова' => [
                    'Абдурахимова З. К.',
                    'Нац. Координатор'
                ],
                'Турдиев' => [
                    'Абдурахимова З. К.',
                    'Нац. Координатор'
                ],
                'Фатхуллин' => [
                    'Абдурахимова З. К.',
                    'Нац. Координатор'
                ],
                'Мамараимов' => [
                    'Абдурахимова З. К.',
                    'Нац. Координатор'
                ],
                'Палванов' => [
                    'Абдурахимова З. К.',
                    'Нац. Координатор'
                ],
                'Бердимуратова' => [
                    'Абдурахимова З. К.',
                    'Нац. Координатор'
                ],
                'Абдиганиев' => [
                    'Саматов Д. А.',
                    'Рег. Координатор в Андижанской области'
                ],
                'Хакбердиева' => [
                    'Махмудова М. М.',
                    'Рег. Координатор в Бухарской области '
                ],
                'Абдуваитова' => [
                    'Курбанов У. К.',
                    'Рег. Координатор в Джиззакской области'
                ],
                'Юлдашев' => [
                    'Бахринов О. Х.',
                    'Рег. Координатор в Кашкадарьинской области'
                ],
                'Абрурахимова' => [
                    'Атабаева Х. Т.',
                    'Рег. Координатор в Наваийской области'
                ],
                'Назаров' => [
                    'Хошимов Ш. М.',
                    'Рег. Координатор в Наманганской области'
                ],
                'Нарзикулова' => [
                    'Одилова Ш. С.',
                    'Рег. Координатор в Самаркандской области'
                ],
                'Хасанов' => [
                    'Турдиев О. Ч.',
                    'Рег. Координатор в Сурхандарьинской области'
                ],
                'Бобожонова' => [
                    'Фатхуллин Т. Ф.',
                    'Рег. Координатор в Сырдарьинской области'
                ],
                'Кодиров' => [
                    'Мамараимов Ж. Ю.',
                    'Рег. Координатор в Ферганской области'
                ],
                'Хусаинов' => [
                    'Палванов М. М.',
                    'Рег. Координатор в Харезмской области'
                ],
                'Пиржанов' => [
                    'Бердимуратова С. П.',
                    'Рег. Координатор в Республике Каракалпакстан'
                ],
                'Тураханова' => [
                    'Камалов Б. А.',
                    'Нац. АФА'
                ]
            ],
            4 => [],
            6 => [
                'Subotin' => [
                    'Nikitina T. S.'
                ],
                'Norboyeva' => [
                    'Subotin D. Yu.',
                    'Программный менеджер'
                ],
                'Godunova' => [
                    'Subotin D. Yu.',
                    'Программный менеджер'
                ],
                'Devyatova' => [
                    'Subotin D. Yu.',
                    'Программный менеджер'
                ],
                'Marasulova' => [
                    'Subotin D. Yu.',
                    'Программный менеджер'
                ],
                'Turdaliyev' => [
                    'Subotin D. Yu.',
                    'Программный менеджер'
                ],
                'Abduserikova' => [
                    'Godunova Ye. B.',
                    'PR координатор'
                ],
                'Drogomirova' => [
                    'Devyatova Ye. V.',
                    'Координатор МиО'
                ],
                'Sharmetova' => [
                    'Marasulova R. S.',
                    'Координатор ВСЛ и детского ТБ'
                ],
                'Yakubdjanova' => [
                    'Marasulova R. S.',
                    'Координатор ВСЛ и детского ТБ'
                ],
                'Saidova' => [
                    'Marasulova R. S.',
                    'Координатор ВСЛ и детского ТБ'
                ],
                'Kattaxodjayeva' => [
                    'Marasulova R. S.',
                    'Координатор ВСЛ и детского ТБ'
                ],
                'Yurechko' => [
                    'Marasulova R. S.',
                    'Координатор ВСЛ и детского ТБ'
                ],
                'Zoirova' => [
                    'Marasulova R. S.',
                    'Координатор ВСЛ и детского ТБ'
                ],
                'Vaxabova' => [
                    'Marasulova R. S.',
                    'Координатор ВСЛ и детского ТБ'
                ],
                'Obidova' => [
                    'Marasulova R. S.',
                    'Координатор ВСЛ и детского ТБ'
                ],
                'Gafurova' => [
                    'Turdaliyev B. B.',
                    'Координатор аутрич и МДК'
                ],
                'Fedotova' => [
                    'Turdaliyev B. B.',
                    'Координатор аутрич и МДК'
                ],
                'Kalandarova' => [
                    'Turdaliyev B. B.',
                    'Координатор аутрич и МДК'
                ],
                'Koxodze' => [
                    'Turdaliyev B. B.',
                    'Координатор аутрич и МДК'
                ],
                'Bazarov' => [
                    'Turdaliyev B. B.',
                    'Координатор аутрич и МДК'
                ],
                'Xayrulina' => [
                    'Norboyeva R. S.',
                    'Финансовый менеджер'
                ],
                'Melnikova' => [
                    'Norboyeva R. S.',
                    'Финансовый менеджер'
                ],
                'Danilov' => [
                    'Norboyeva R. S.',
                    'Финансовый менеджер'
                ],
                'Vereshagin' => [
                    'Norboyeva R. S.',
                    'Финансовый менеджер'
                ],
                'Yusupova' => [
                    'Norboyeva R. S.',
                    'Финансовый менеджер'
                ],
                'Xabibullina' => [
                    'Xayrulina Ye. A.',
                    'Координатор по кадрам'
                ],
                'Abasova' => [
                    'Norboyeva R. S.',
                    'Финансовый менеджер'
                ],
            ],
            7 => [
                'Li' => [
                    'Nikitina T. S.',
                    'координатор'
                ],
                'Marasulova' => [
                    'Nikitina T. S.',
                    'координатор'
                ],
                'Abu' => [
                    'Marasulova R. S.',
                    'Помощник координатора'
                ],
                'Abduserikova' => [
                    'Marasulova R. S.',
                    'Помощник координатора'
                ],
                'Garayev' => [
                    'Marasulova R. S.',
                    'Помощник координатора'
                ],
                'Fazlyakbarova' => [
                    'Marasulova R. S.',
                    'Помощник координатора'
                ]
            ],
            9 => [
                'Ли' => [
                    'Nikitina T. S.',
                    'нац. руководитель'
                ],
                'Marasulova' => [
                    'Nikitina T. S.',
                    'нац. руководитель'
                ],
                'Мадмарова' => [
                    'Marasulova R. S.',
                    'Нац. Координатор'
                ],
                'Верещагин' => [
                    'Marasulova R. S.',
                    'Нац. Координатор'
                ],
                'Kalandarova' => [
                    'Marasulova R. S.',
                    'Нац. Координатор'
                ],
                'Хафизова' => [
                    'Marasulova R. S.',
                    'Нац. Координатор'
                ],
                'Хидирова' => [
                    'Marasulova R. S.',
                    'Нац. Координатор'
                ],
                'Файзиева' => [
                    'Хафизова К. Х.',
                    'Рег. Координатор в Бухарской области'
                ],
                'Хусенова' => [
                    'Хафизова К. Х.',
                    'Рег. Координатор в Бухарской области'
                ],
                'Кенжаева' => [
                    'Хафизова К. Х.',
                    'Рег. Координатор в Бухарской области'
                ],
                'Неков' => [
                    'Хафизова К. Х.',
                    'Рег. Координатор в Бухарской области'
                ],
                'Муминов' => [
                    'Хидирова Ф. Р.',
                    'Рег. Координатор в Наваийской области'
                ],
                'Санакулова' => [
                    'Хидирова Ф. Р.',
                    'Рег. Координатор в Наваийской области'
                ],
                'Бафаева' => [
                    'Хидирова Ф. Р.',
                    'Рег. Координатор в Наваийской области'
                ],
                'Тоирова' => [
                    'Хидирова Ф. Р.',
                    'Рег. Координатор в Наваийской области'
                ]
            ],
            10 => [
                'Marasulova' => [
                    'Nikitina T. S.'
                ],
                'Norboyeva' => [
                    'Nikitina T. S.'
                ],
                'Abdusamatova' => [
                    'Marasulova R. S.',
                    'Координатор'
                ],
                'Kalandarova' => [
                    'Marasulova R. S.',
                    'Координатор'
                ],
                'Vaxabova' => [
                    'Marasulova R. S.',
                    'Координатор'
                ],
                'Turdaliyev' => [
                    'Marasulova R. S.',
                    'Координатор'
                ],
                'Melnikova' => [
                    'Norboyeva R. S.',
                    'Финансовый менеджер'
                ],
                'Axmedjanova' => [
                    'Norboyeva R. S.',
                    'Финансовый менеджер'
                ],
                'Danilov' => [
                    'Norboyeva R. S.',
                    'Финансовый менеджер'
                ],
            ],
            11 => [
                'Abu' => [
                    'Nikitina T. S.'
                ],
                'Мадмарова' => [
                    'Хабибуллаева С. Д.',
                    'Координатор'
                ],
                'Камалов' => [
                    'Хабибуллаева С. Д.',
                    'Координатор'
                ],
                'Тахирова' => [
                    'Мадмарова Ф. И.',
                    'Ассистент по обучению'
                ],
                'Саидова' => [
                    'Мадмарова Ф. И.',
                    'Ассистент по обучению'
                ],
                'Саёхат (фамилия?)' => [
                    'Мадмарова Ф. И.',
                    'Ассистент по обучению'
                ],
                'Файзибекова' => [
                    'Мадмарова Ф. И.',
                    'Ассистент по обучению'
                ],
            ],
            12 => [
                'Devyatova' => [
                    'Nikitina T. S.'
                ],
            ],
            15 => [
                'Bazarov' => [
                    'Nikitina T. S.'
                ],
                'Norboyeva' => [
                    'Nikitina T. S.'
                ],
                'Xayrulina' => [
                    'Norboyeva R. S.',
                    'Финансовый менеджер'
                ],
                'Melnikova' => [
                    'Norboyeva R. S.',
                    'Финансовый менеджер'
                ],
                'Danilov' => [
                    'Norboyeva R. S.',
                    'Финансовый менеджер'
                ]
            ]
        ];

        $defaultUser2user = [
            6 => [
                'Turdaliyev B. B.',
                'Координатор аутрич и МДК'
            ],
            7 => [
                'Marasulova R. S.',
                'Помощник координатора'
            ],
            11 => [
                'Abu Shikhada Yu. O.',
                'Координатор'
            ],
            12 => [
                'Devyatova Y.V.	',
                'Национальный менеджер субпроекта ННО РИОЦ «INTILISH «Оказание содействия в проведении профилактических программ по ВИЧ среди ключевых групп населения» филиала ННО РИОЦ «INTILISH  в   Ферганском  вилояте'
            ],
            15 => [
                'Bazarov F. B.',
                'Координатор'
            ]
        ];

        $project2form = [
            1 => 13,
            2 => 14,
            3 => 15,
            4 => 56,
            5 => 16,
            6 => 17,
            7 => 18,
            8 => 19,
            9 => 20,
            10 => 21,
            11 => 22,
            12 => 23,
            15 => 54
        ];

        set_time_limit(0);

        $project = Project::findOrFail($request->projects[0]);

        if (!isset($user2user[$project->id])) {
            return 'Отсутствуют данные о структуре проекта.';
        }

        $user = User::find($request->users ? $request->users[0] : $request->user()->id);
        $userProjectInfo = $user->profile[$project2form[$project->id]];

        if (empty($userProjectInfo) ||
            ((empty($userProjectInfo[0]->data['i9qR']) || $userProjectInfo[0]->data['i9qR'] != 'bTTv')
                && (empty($userProjectInfo[0]->data['Dtc7']) ||
                    ($userProjectInfo[0]->data['Dtc7'] == 'WvKY' && empty($userProjectInfo[0]->data['puoj'])) ||
                    empty($userProjectInfo[0]->data['8ake']) ||
                    ($userProjectInfo[0]->data['Dtc7'] != 'WvKY' &&
                        (empty($userProjectInfo[0]->data['LGLM']) ||
                            !is_numeric(str_replace(' ', '', $userProjectInfo[0]->data['LGLM'])))
                    )
                )
        )) {
            return 'В профиле сотрудника отсутствуют необходимые данные.';
        }

        $name = explode(' ', $user->name);
        $name_short = $name[0] . ' ' . mb_substr($name[1], 0, 1) . '.' . (isset($name[2]) ? ' ' . mb_substr($name[2], 0, 1) . '.' : '');

        $managers = [];

        foreach ($request->projects as $projectId) {
            $projectManagers = [];
            $manager = $name[0];
            while (isset($user2user[$projectId][$manager]) || (empty($projectManagers) && isset($defaultUser2user[$projectId]))) {
                $manager = $user2user[$projectId][$manager] ?? $defaultUser2user[$projectId];

                if ($name_short != 'Xayrulina Y. A.'
                    && (
                        ($manager[0] == 'Nikitina T. S.' && in_array($projectId, [15]))
                        || ($manager[0] == 'Subotin D. Yu.' && in_array($projectId, [6, 10]))
                    )
                ) {
                    $projectManagers[] = [
                        'manager_name' => 'Xayrulina Ye. A.',
                        'manager_position' => 'Координатор по кадрам',
                        'manager_project' => "по проекту «{$project->description}»"
                    ];
                }

                $projectManagers[] = [
                    'manager_name' => $manager[0],
                    'manager_position' => ($manager[0] == 'Nikitina T. S.' ? 'Директор' .
                                                (!empty($manager[1]) ? ' и ' : '') : '') .
                                            ($manager[1] ?? ''),
                    'manager_project' => !empty($manager[1]) ? 'по проекту «' . Project::find($projectId)->description . '»' : ''
                ];

                $manager = explode(' ', $manager[0]);
                $manager = $manager[0];
            }

            foreach ($projectManagers as $projectManager) {
                $insert = null;
                foreach ($managers as $index => $manager) {
                    if ($manager['manager_name'] == $projectManager['manager_name']) {
                        if ($manager['manager_position'] != $projectManager['manager_position'] ||
                            $manager['manager_project'] != $projectManager['manager_project']
                        ) {
                            // $projectManager['same'] = true;
                            $insert = true;
                        } else {
                            $insert = false;
                        }

                        break;
                    }
                }

                if ($insert === true) {
                    array_splice($managers, $index, 1, [$projectManager]);
                }

                if ($insert === null) {
                    $managers[] = $projectManager;
                }
            }
        }

        setlocale(LC_TIME, 'ru_RU.utf8');

        CarbonInterval::setCascadeFactors([
            'minute' => [60, 'seconds'],
            'hour' => [60, 'minutes']
        ]);

        $from = Carbon::parse($request->from);

        $request->mode = 3;
        $hourly_rate = str_replace(' ', '', $user->profile[$project2form[$project->id]][0]->data['LGLM'] ?? 0);

        if (count($request->projects) > 1
            || (!empty($userProjectInfo[0]->data['Dtc7'])
                && $userProjectInfo[0]->data['Dtc7'] == 'ei6C')
        ) {
            $from = $from->floorMonth();
            $request['from'] = $from;
            $request['till'] = (clone $from)->lastOfMonth();

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(resource_path('doc/timing-table.xlsx'));

            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('T4', now()->formatLocalized('"%-e" %B %Y г.'));
            $sheet->setCellValue('E7', "учета рабочего времени сотрудника $name_short за " . $from->formatLocalized('%B месяц %Y года'));

            $rowsAdded = -1;

            $days_start_index = Coordinate::columnIndexFromString('F');

            $requestPositions = $request->positions;

            foreach ($request->projects as $projectId) {
                $project = Project::findOrFail($projectId);

                $positions = ProjectUser::where('user_id', $user->id)
                    ->where('project_id', $projectId)
                    ->whereIn('position', $requestPositions)
                    ->groupBy('position')
                    ->get()
                    ->pluck('position');

                foreach ($positions as $position) {
                    $rowsAdded++;

                    $row_to = 13 + $rowsAdded;

                    if ($rowsAdded) {
                        Util::clonePhpSpreadsheetRow($sheet, 13, $row_to);
                        $sheet->mergeCells("C$row_to:D$row_to");
                    }

                    $sheet->setCellValue("B$row_to", $rowsAdded + 1);
                    $sheet->setCellValue("C$row_to", $project->description);
                    $sheet->setCellValue("E$row_to", $position);
                    // $sheet->getRowDimension($row_to)->setRowHeight(-1);

                    $request->projects = [$projectId];
                    $request->positions = [$position];
                    $aggregated = $this->aggregated($request);

                    $days = 0;
                    $timing = 0;

                    foreach (array_slice($aggregated['days'], 0, 15) as $index => $day) {
                        $column = Coordinate::stringFromColumnIndex($days_start_index + $index);

                        $sheet->setCellValue($column . $row_to, CarbonInterval::seconds($day['verified_timing'])->cascade()->format('%H:%I'));

                        if ($day['verified_timing']) {
                            $days++;
                            $timing += $day['verified_timing'];
                        }

                        if ($day['weekend']) {
                            $sheet->getStyle("{$column}10:$column" . (18 + $rowsAdded))
                                ->getFill()
                                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                ->getStartColor()
                                ->setARGB('FF00B0F0');
                        }
                    }

                    $sheet->setCellValue(
                        'V' . $row_to,
                        CarbonInterval::seconds(round($timing / 3600) * 3600)
                            ->cascade()->format('%H:%I')
                    );
                    $sheet->setCellValue('W' . $row_to, $days);

                    $row_to = 24 + $rowsAdded * 2;

                    if ($rowsAdded) {
                        Util::clonePhpSpreadsheetRow($sheet, 24 + $rowsAdded * 2, $row_to);
                        $sheet->mergeCells("C$row_to:D$row_to");
                    }

                    $sheet->setCellValue("B$row_to", $rowsAdded + 1);
                    $sheet->setCellValue("C$row_to", $project->description);
                    $sheet->setCellValue("E$row_to", $position);
                    // $sheet->getRowDimension($row_to)->setRowHeight(-1);

                    foreach (array_slice($aggregated['days'], 15) as $index => $day) {
                        $column = Coordinate::stringFromColumnIndex($days_start_index + $index);

                        $sheet->setCellValue($column . $row_to, CarbonInterval::seconds($day['verified_timing'])->cascade()->format('%H:%I'));

                        if ($day['verified_timing']) {
                            $days++;
                            $timing += $day['verified_timing'];
                        }

                        if ($day['weekend']) {
                            $sheet->getStyle($column . (21 + $rowsAdded * 2) . ':' . $column . (29 + $rowsAdded * 2))
                                ->getFill()
                                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                ->getStartColor()
                                ->setARGB('FF00B0F0');
                        }
                    }

                    $sheet->setCellValue(
                        'V' . $row_to,
                        CarbonInterval::seconds(round($timing / 3600) * 3600)
                            ->cascade()->format('%H:%I')
                    );
                    $sheet->setCellValue('W' . $row_to, $days);
                }
            }

            $sheet->setCellValue('B' . (14 + $rowsAdded), $rowsAdded + 2);
            $sheet->setCellValue('B' . (15 + $rowsAdded), $rowsAdded + 3);
            $sheet->setCellValue('B' . (16 + $rowsAdded), $rowsAdded + 4);
            $sheet->setCellValue('B' . (25 + $rowsAdded * 2), $rowsAdded + 2);
            $sheet->setCellValue('B' . (26 + $rowsAdded * 2), $rowsAdded + 3);
            $sheet->setCellValue('B' . (27 + $rowsAdded * 2), $rowsAdded + 4);
            $sheet->setCellValue('B' . (31 + $rowsAdded * 2), 'Подготовил(а) ' . $name_short);

            array_pop($managers);

            foreach ($managers as $index => $manager) {
                $row_to = 31 + $rowsAdded * 2 + $index;

                if ($index) {
                    $row_from = 31 + $rowsAdded * 2;

                    Util::clonePhpSpreadsheetRow($sheet, $row_from, $row_to);

                    $sheet->setCellValue("B$row_to", null);
                    $sheet->mergeCells("K$row_to:N$row_to");
                    $sheet->mergeCells("P$row_to:S$row_to");
                    $sheet->mergeCells("U$row_to:X$row_to");
                }

                $sheet->setCellValue('K' . $row_to, $manager['manager_name']);
                $sheet->setCellValue('P' . $row_to, $manager['manager_position']);
                $sheet->setCellValue('U' . $row_to, $manager['manager_project']);

                if (!empty($manager['same'])) {
                    $sheet->setCellValue('F' . $row_to, null);
                }
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $filename = $user->name . '.xlsx';
            $writer->save(storage_path('app/public/' . $filename));
        } elseif (!empty($userProjectInfo[0]->data['Dtc7']) && $userProjectInfo[0]->data['Dtc7'] == 'WvKY') {
            $aggregated = $this->aggregated($request);

            $timing = round($aggregated['timings']['verified'] / 3600) * 3600;

            $parts = array_slice($aggregated['parts']->toArray(), 0, -1);

            $contract = $user->profile[$project2form[$project->id]][0]->data['8ake'];

            $date = Carbon::parse(substr($contract, -10))->formatLocalized('%-e %B %Y');
            $from = $from->formatLocalized('«%-e» %B %Y');
            $till = Carbon::parse($request->till)->formatLocalized('«%-e» %B %Y');

            $template = new TemplateProcessor(resource_path('doc/timing-act.docx'));

            $amount = round(($timing / 3600) * $hourly_rate * 100) / 100;
            $fraction = ($amount * 100) % 100;

            $formatter = new \NumberFormatter('ru', \NumberFormatter::SPELLOUT);

            $template->setValues([
                'contract' => $contract,
                'date' => $date,
                'name' => $user->name,
                'from' => $from,
                'till' => $till,
                'project' => $project->description,
                'position' => $request->positions[0],
                'name_short' => $name_short,
                'act_number' => $user->profile[$project2form[$project->id]][0]->data['puoj'],
                'total_timing' => CarbonInterval::seconds($timing)->cascade()->format('%H:%I')
            ]);

            if ($amount) {
                $template->cloneBlock('amount_info', 1, true, false, [[
                    'amount' => number_format($amount, $fraction ? 2 : 0, '.', ' '),
                    'words' => $formatter->format(floor($amount)) . ' сум'
                        . ($fraction ? ' ' . $formatter->format($fraction) . ' тийн' : '')
                ]]);
            } else {
                $template->deleteBlock('amount_info');
            }

            $template->cloneBlock(
                'subtotal',
                count($parts),
                true,
                true
            );

            if (count($managers)) {
                $template->cloneBlock(
                    'manager',
                    0,
                    true,
                    false,
                    $managers
                );
            } else {
                $template->cloneBlock(
                    'manager',
                    0,
                    true
                );
            }

            foreach ($parts as $i => $item) {
                $i += 1;

                $template->setValues([
                    "title#$i" => $item['title'],
                    "subtotal_timing#$i" => CarbonInterval::seconds($item['timing'])->cascade()->format('%H:%I:%S')
                ]);

                $items = $item['items']->toArray();

                $template->cloneRowAndSetValues("index#$i", array_map(function ($part, $k) use ($i) {
                    return [
                        "index#$i" => $k + 1,
                        "part#$i" => $part['description_past'] ?: $part['description'],
                        "part_timing#$i" => CarbonInterval::seconds($part['timing'])->cascade()->format('%H:%I:%S'),
                    ];
                }, $items, array_keys($items)));
            }

            $filename = $user->name . '.docx';
            $template->saveAs(storage_path('app/public/' . $filename));
        } elseif (!empty($userProjectInfo[0]->data['i9qR']) && $userProjectInfo[0]->data['i9qR'] == 'bTTv') {
            $total = 0;

            $projectUserClause = function ($query) use ($project, $user, $request) {
                $query->where('project_id', $project->id)
                    ->where('user_id', $user->id)
                    ->where('position', $request->positions[0]);
            };

            $parts = $this->aggregatedParts($request, false)
                ->where('type', 1)
                ->whereHas('projectUsers', function ($query) use ($request) {
                    $query->whereNotNull('plan')
                        ->whereIn('user_id', $request->users);
                })
                ->whereHas('projectUsers', $projectUserClause)
                ->with(['projectUsers' => $projectUserClause])
                ->get()
                ->map(function ($part, $index) use (&$total) {
                    $cost = $part->projectUsers[0]->cost;
                    $plan = $part->projectUsers[0]->plan ?? 1;
                    $subtotal = round($part->timing / $plan * $cost);
                    $total += $subtotal;

                    return [
                        'index' => $index + 1,
                        'description' => $part->description,
                        'unit' => $part->unit,
                        'cost' => number_format($cost, 0, '.', ' '),
                        'plan' => $part->plan,
                        'result' => sprintf($part->result_description, $part->timing),
                        'subtotal' => number_format($subtotal, 0, '.', ' ')
                    ];
                })
                ->toArray();

            $contract = $userProjectInfo[0]->data['8ake'];

            $date = Carbon::parse($userProjectInfo[0]->data['date'])->formatLocalized('%-e %B %Y');
            $from = $from->formatLocalized('«%-e» %B %Y');
            $till = Carbon::parse($request->till)->formatLocalized('«%-e» %B %Y');

            $template = new TemplateProcessor(resource_path('doc/amount-act.docx'));

            $formatter = new \NumberFormatter('ru', \NumberFormatter::SPELLOUT);

            $template->setValues([
                'contract' => $contract,
                'date' => $date,
                'name' => $user->name,
                'from' => $from,
                'till' => $till,
                'project' => $project->description,
                'amount' => number_format($total, 0, '.', ' '),
                'words' => $formatter->format($total),
                'position' => $request->positions[0],
                'name_short' => $name_short,
                'act_number' => $user->profile[$project2form[$project->id]][0]->data['puoj'] ?? 1,
                'total' => $total
            ]);

            $template->cloneBlock('part', 0, true, false, $parts);

            if (count($managers)) {
                $template->cloneBlock(
                    'manager',
                    0,
                    true,
                    false,
                    $managers
                );
            } else {
                $template->cloneBlock(
                    'manager',
                    0,
                    true
                );
            }

            $filename = $user->name . '.docx';
            $template->saveAs(storage_path('app/public/' . $filename));
        }

        return Storage::download($filename, $filename);
    }

    public function downloadRides(Request $request)
    {
        setlocale(LC_TIME, 'ru_RU.utf8');

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(resource_path('doc/rides.xlsx'));

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('C2', 'Driving log / Путевой лист за ' .
            Carbon::parse($request->from)->formatLocalized('%B %Y г.'));

        $request->desc = [false];
        $items = $this->getQuery($request)->get();

        foreach ($items as $index => $item) {
            $row = 7 + $index;

            if ($index) {
                Util::clonePhpSpreadsheetRow($sheet, 7, $row);
            }

            $sheet->setCellValue("A$row", $item->began_at->format('Y-m-d'));
            $sheet->setCellValue("B$row", $item->activity->forms[29][0]['data']['DQqq'] ?? null);
            $sheet->setCellValue("C$row", $item->activity->forms[29][0]['data']['iah6'] ?? null);
            $sheet->setCellValue("D$row", $item->comment);

            $name = explode(' ', $item->user->name);
            $name_short = $name[0] . ' ' . mb_substr($name[1], 0, 1) . '.' . (isset($name[2]) ? ' ' . mb_substr($name[2], 0, 1) . '.' : '');

            $sheet->setCellValue("E$row", $name_short);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'Путевой лист от ' . date('Y-m-d Hi') . '.xlsx';
        $writer->save(storage_path('app/public/' . $filename));

        return Storage::download($filename, $filename);
    }
}
