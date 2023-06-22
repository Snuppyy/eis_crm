<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Activity;
use App\Models\Timing;

use App\Http\Requests\Activities\StoreActivity;
use App\Http\Requests\Activities\UpdateActivity;
use App\Lib\Indicators;
use App\Models\Freeze;
use App\Models\ProjectUser;
use Carbon\Carbon;
use Storage;

class ActivitiesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $per_page = (int) $request->input('itemsPerPage');

        $items = Activity::with([
                'user',
                'timings' => function ($query) {
                    $query->limit(1);
                }
            ])
            ->when(
                $search = $request->search,
                function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('title', 'like', "%$search%")
                            ->orWhere('description', 'like', "%$search%")
                            ->orWhereHas('user', function($query) use ($search) {
                                $query->where('name', 'like', "%$search%");
                            });
                    });
                }
            );

        if ($request->project) {
            $items = Activity::scoped(['tree' => 1, 'project_id' => $request->project])
                ->withDepth()
                ->get();

            if ($items->count()) {
                $items = $items->toFlatTree();

                $depths = [];
                $prevDepth = 1;

                foreach ($items as &$item) {
                    if (!isset($depths[$item->depth])) {
                        $depths[$item->depth] = 0;
                    }

                    if ($item->depth > $prevDepth) {
                        $depths[$item->depth] = 1;
                    } else {
                        $depths[$item->depth]++;
                    }

                    $prevDepth = $item->depth;

                    $item->hierarchical_title = implode('. ', array_slice($depths, 0, $item->depth + 1)) . '. ' . $item->title;
                }

                return $items;
            }

            return [[
                'id' => 0,
                'hierarchical_title' => '(Корень)'
            ]];
        } else {
            $items->with(['project:id,name']);

            if (!$request->has('location')) {
                $items->whereHas('project', function ($query) use ($request) {
                    $query->whereHas('projectUsers', function ($query) use ($request) {
                        $query->where('user_id', $request->user()->id)
                            ->where(function ($query) {
                                $query->whereNull('terminated_at')
                                    ->orWhere('terminated_at', '>', now());
                            });
                    });
                })
                ->where(function ($query) use ($request) {
                    $query->where('user_id', $request->user()->id)
                        ->orWhereHas('allUsers', function ($query) use ($request) {
                            $query->where('user_id', $request->user()->id);
                        })
                        ->orWhere(function ($query) use ($request) {
                            $query->where('tree', 1)
                                ->whereIn('project_id', $request->user()->projects->pluck('id'));
                        });
                });
            }

            if ($request->projects) {
                $items->whereIn('project_id', $request->projects);
            }
        }

        if (!$request->has('location')) {
            $items->whereNull('location_id');
        } else {
            if ($location = $request->location) {
                $items->where('location_id', $location);
            } else {
                $items->whereNotNull('location_id');
            }

            if (!in_array('superuser', $request->user()->roles)) {
                $items->whereHas('project.users', function ($query) use ($request) {
                    $query->where('users.id', $request->user()->id);
                });
            }

            $items->with('verifiers');

            if (!in_array($request->user()->id, [5, 6721])) {
                $visibleUsers = $request->user()->verifiedActivityUsers->pluck('id');
                $visibleUsers[] = $request->user()->id;
                $items->whereIn('user_id', $visibleUsers);
            }

            if ($users = $request->users) {
                $items->whereIn('user_id', $users);
            }

            if ($request->from) {
                $items->where(function ($query) use ($request) {
                    $query->whereNull('start_date')
                        ->orWhere('start_date', '>=', $request->from);
                });
            }

            if ($request->till) {
                $items->where(function ($query) use ($request) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '<=', $request->till);
                });
            }
        }

        $sortDesc = $request->input('sortDesc', [true, true]);

        foreach ($request->input('sortBy', ['start_date', 'start_time']) as $index => $order) {
            $items->orderBy($order, isset($sortDesc[$index]) &&
                $sortDesc[$index] == 'true' ? 'desc' : 'asc');
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

        $users = collect($data)
            ->only(['users', 'clients'])
            ->flatten(1)
            ->mapWithKeys(function ($item) {
                unset($item['position']);
                return [$item['user_id'] => $item];
            })
            ->all();

        if (!$request->has('location_id') && !$request->has('users')) {
            $data['project_id'] = 4;

            $userId = $request->user()->id;

            $projectUser = ProjectUser::where('user_id', $userId)
                ->where('project_id', 4)
                ->where('part_id', 23)
                ->first();

            if ($projectUser) {
                $users = [
                    $userId => [
                        'role' => 'implementer',
                        'part_id' => 23,
                        'project_user_id' => $projectUser->id
                    ]
                ];
            }
        }

        $request->validate($this->makePeriodValidators($data, array_keys($users), $request->createTiming));

        $data['user_id'] = $request->user()->id;

        if (isset($data['parent_id'])) {
            if (in_array($request->user()->id, [1, 2, 5])) {
                $data['tree'] = 1;
                $parent_id = $data['parent_id'];
            }
            unset($data['parent_id']);
        }

        $this->cleanForms($data);

        $activity = Activity::create($data);

        if (isset($parent_id)) {
            $activity->parent_id = $parent_id;
            $activity->save();
        }

        $activity->users()->sync($users);

        if ($request->createTiming &&
            !empty($data['start_date']) &&
            !empty($data['start_time']) &&
            !empty($data['end_date']) &&
            !empty($data['end_time'])
        ) {
            $began_at = new Carbon($data['start_date'] . ' ' . $data['start_time']);
            $ended_at = new Carbon($data['end_date'] . ' ' . $data['end_time']);

            $timing = [
                'user_id' => $request->user()->id,
                'activity_id' => $activity->id,
                'began_at' => $began_at,
                'ended_at' => $ended_at,
                'timing' => $began_at->diffInSeconds($ended_at)
            ];

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
        }

        if (!empty($data['clients'])) {
            Indicators::invalidate($activity->project_id);
            Indicators::compute();
        }

        return ['id' => $activity->id];
    }

    /**
     * Display the specified resource.
     *
     * @param  Request   $request
     * @param  Activity  $activity
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Activity $activity)
    {
        $activity->load([
            'allUsers',
            'timings' => function ($query) use ($request) {
                if (!in_array('superuser', $request->user()->roles)) {
                    $query->whereHas('projectUser', function ($query) use ($request) {
                        $query->where('user_id', $request->user()->id);
                    });
                }
            },
            // 'verifiedTiming',
            'frozenTiming'
        ]);

        $activity->append('editable');

        return $activity;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateActivity  $request
     * @param  Activity  $activity
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Activity $activity)
    {
        $user = $request->user();

        $data = $request->all();

        if (($activity->verifiedTiming && $user->id != 5)
            || (!in_array($user->id, [5, 6721])
                && ($activity->verifiers()->count()
                    || (!empty($data['verified'])
                        && !$user->verifiedActivityUsers
                            ->where('pivot.project_id', $activity->project_id)
                            ->where('id', $activity->user_id)->count())))
        ) {
            abort(403);
        }

        $users = collect($data)
            ->only(['users', 'clients'])
            ->flatten(1)
            ->mapWithKeys(function ($item) {
                unset($item['position']);
                return [$item['user_id'] => $item];
            })
            ->all();

        $request->validate($this->makePeriodValidators($data, array_keys($users), $request->createTiming));

        if ($request->isMethod('patch')) {
            $forms = $activity->forms;

            if (!empty($data['file'])) {
                if (!empty($forms[29]['file'])) {
                    Storage::delete($forms[29]['file']);
                }

                $forms[29]['file'] = $data['file']->storeAs("activities/$activity->id", $data['file']->getClientOriginalName());
            }

            if (!empty($data['files'])) {
                foreach ($forms['files'] as &$file) {
                    if (isset($file['upload'])) {
                        $upload = $data['files'][$file['upload']];
                        $file['path'] = $upload->storeAs("activities/$activity->id", $upload->getClientOriginalName());
                        unset($file['upload']);
                        unset($file['file']);
                    }
                }
            }

            $activity->update(['forms' => $forms]);

            return ['forms' => $forms];
        }

        if (!empty($activity->forms['files'])) {
            $files = [];

            foreach ($activity->forms['files'] as $file) {
                if (!empty($file['path'])) {
                    $files[] = $file['path'];
                }
            }

            foreach ($data['forms']['files'] as $file) {
                if (!empty($file['path']) && ($i = array_search($file['path'], $files)) !== false) {
                    unset($files[$i]);
                }
            }

            foreach ($files as $file) {
                Storage::delete($file);
            }
        }

        $this->cleanForms($data);

        if (isset($data['parent_id']) && !in_array($request->user()->id, [1, 2, 5])) {
            unset($data['parent_id']);
        }

        if (!empty($data['verified'])) {
            unset($data['verified']);
            if (!$activity->verifiers()->where('id', $user->id)->count()) {
                $activity->verifiers()->attach($user->id);
            }
        }

        $activity->update($data);

        $activity->load(['allUsers', 'verifiers']);

        $activity->users()->sync($users);

        if ($request->createTiming &&
            !empty($data['start_date']) &&
            !empty($data['start_time']) &&
            !empty($data['end_date']) &&
            !empty($data['end_time'])
        ) {
            $began_at = new Carbon($data['start_date'] . ' ' . $data['start_time']);
            $ended_at = new Carbon($data['end_date'] . ' ' . $data['end_time']);

            $timing = [
                'user_id' => $request->user()->id,
                'activity_id' => $activity->id,
                'began_at' => $began_at,
                'ended_at' => $ended_at,
                'timing' => $began_at->diffInSeconds($ended_at)
            ];

            $participants = $activity->allUsers()
                ->where('role', '!=', 'client')
                ->get();

            foreach ($participants as $participant) {
                $timing['project_user_id'] = $participant->project_user_id;
                $timing['comment'] = $participant->user_id == $timing['user_id'] ? $data['comment'] : null;

                Timing::create($timing);
            }
        } else {
            $participants = [];

            foreach ($activity->allUsers as $participant) {
                $participants[$participant->user_id] = $participant;
            }

            $activity->load('allUsers');

            foreach ($activity->allUsers as $participant) {
                if (isset($participants[$participant->user_id])) {
                    $project_user_id = $participants[$participant->user_id]->project_user_id;

                    if ($project_user_id != $participant->project_user_id) {
                        foreach ($activity->timings as $timing) {
                            if ($project_user_id == $timing->project_user_id) {
                                $timing->project_user_id = $participant->project_user_id;
                                $timing->save();
                            }
                        }
                    }
                }
            }
        }

        if (!empty($data['clients'])) {
            Indicators::invalidate($activity->project_id);
            Indicators::compute();
        }

        return $activity;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Activity  $activity
     * @return \Illuminate\Http\Response
     */
    public function destroy(Activity $activity, Request $request)
    {
        if ($activity->verifiers()->count()) {
            abort(403);
        }

        if (($activity->frozenTiming || $activity->verifiedTiming) && $request->user()->id != 37) {
            abort(403);
        }

        if ($activity->start_date < '2020-10-01') {
            abort(402);
        }

        if ($activity->clients()->count()) {
            Indicators::invalidate($activity->project_id);
            Indicators::compute();
        }

        $activity->delete();
    }

    /**
     * Start activity timing.
     *
     * @param  Request  $request
     * @param  Activity  $activity
     * @return \Illuminate\Http\Response
     */
    public function start(Request $request, Activity $activity)
    {
        $userActivity = $activity->allUsers()
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$activity->tree && $userActivity) {
            $timing = Timing::create([
                'user_id' => $request->user()->id,
                'activity_id' => $activity->id,
                'project_user_id' => $userActivity->project_user_id,
                'began_at' => now()
            ]);

            if (empty($activity->start_date) && empty($activity->start_time)) {
                $activity->start_date = date('Y-m-d');
                $activity->start_time = date('H:i:s');
            }

            $activity->ongoing = 1;
            $activity->save();

            return $timing;
        }
    }

    /**
     * Stop activity timing.
     *
     * @param  Request  $request
     * @param  Activity  $activity
     * @return \Illuminate\Http\Response
     */
    public function stop(Request $request, Activity $activity)
    {
        $timing = Timing::where('activity_id', $activity->id)->latest()->first();
        $timing->comment = $request->comment;
        $timing->ended_at = now();
        $timing->timing = $timing->began_at->diffInSeconds($timing->ended_at);
        $timing->save();

        if (empty($activity->end_date) && empty($activity->end_time)) {
            $activity->end_date = date('Y-m-d');
            $activity->end_time = date('H:i:s');
        }

        $activity->ongoing = 0;
        $activity->save();

        return $timing;
    }

    private function makePeriodValidators($data, $userIds, $checkOverlap = false)
    {
        if (!empty($data['start_date']) &&
            !empty($data['start_time']) &&
            !empty($data['end_date']) &&
            !empty($data['end_time'])
        ) {
            $began_at = new Carbon($data['start_date'] . ' ' . $data['start_time']);
            $ended_at = new Carbon($data['end_date'] . ' ' . $data['end_time']);

            $validate_start = function ($attribute, $value, $fail) use ($data, $userIds, $began_at, $ended_at) {
                $freeze = Freeze::where('project_id', $data['project_id'])
                    ->where(function ($query) use ($userIds) {
                        $query->whereNull('user_id')
                            ->orWhereIn('user_id', $userIds);
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

            $validate_end = function ($attribute, $value, $fail) use ($data, $userIds, $began_at, $ended_at) {
                $freeze = Freeze::where('project_id', $data['project_id'])
                    ->where(function ($query) use ($userIds) {
                        $query->whereNull('user_id')
                            ->orWhereIn('user_id', $userIds);
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

            $validators = [
                'start_date' => $validate_start,
                'start_time' => [$validate_start],
                'end_date' => $validate_end,
                'end_time' => [$validate_end],
                'users.*.user_id' => []
            ];

            if ($checkOverlap) {
                $addUsersMessages = false;

                $query = Timing::whereHas('projectUser', function ($query) use ($data, $userIds) {
                    $query->whereIn('user_id', $userIds);
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
                });

                if ($query->count()) {
                    $query->join('project_user', 'project_user_id', '=', 'project_user.id')
                        ->select('timings.*');

                    if ($query->distinct()->count('project_user.user_id') > 1) {
                        $error = 'Время занято другой деятельностью';
                        $addUsersMessages = true;
                    } else {
                        $overlappingTiming = $query->first();

                        $error = $overlappingTiming->projectUser->user->name . ' уже имеет деятельность с ' .
                            $overlappingTiming->began_at->format('H:i') . ' до ' .
                            $overlappingTiming->ended_at->format('H:i') .
                            ($overlappingTiming->projectUser->project_id == $data['project_id'] ?
                                '' : ' в ' . $overlappingTiming->projectUser->project->name) .
                            ' (#' . $overlappingTiming->id . ')';
                    }

                    $validators['start_time'][] = function ($attribute, $value, $fail) use ($error) {
                        $fail($error);
                    };
                }

                $query = Timing::whereHas('projectUser', function ($query) use ($data, $userIds) {
                    $query->whereIn('user_id', $userIds);
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
                });

                if ($query->count()) {
                    $query->join('project_user', 'project_user_id', '=', 'project_user.id')
                        ->select('timings.*');

                    if ($query->distinct()->count('project_user.user_id') > 1) {
                        $error = 'Время занято другой деятельностью';
                        $addUsersMessages = true;
                    } else {
                        $overlappingTiming = $query->first();

                        $error = $overlappingTiming->projectUser->user->name . ' уже имеет деятельность с ' .
                            $overlappingTiming->began_at->format('H:i') . ' до ' .
                            $overlappingTiming->ended_at->format('H:i') .
                            ($overlappingTiming->projectUser->project_id == $data['project_id'] ?
                                '' : ' в ' . $overlappingTiming->projectUser->project->name) .
                            ' (#' . $overlappingTiming->id . ')';
                    }

                    $validators['end_time'][] = function ($attribute, $value, $fail) use ($error) {
                        $fail($error);
                    };
                }

                if ($addUsersMessages) {
                    $overlappingTimings = Timing::whereHas('projectUser', function ($query) use ($data, $userIds) {
                        $query->whereIn('user_id', $userIds);
                    })
                    ->where(function ($query) use ($began_at, $ended_at) {
                        $query->where(function ($query) use ($began_at, $ended_at) {
                            $query->where(function ($query) use ($began_at) {
                                $query->where('began_at', '<', $began_at)
                                    ->where('ended_at', '>', $began_at);
                            })
                            ->orWhere(function ($query) use ($ended_at) {
                                $query->where('began_at', '<', $ended_at)
                                    ->where('ended_at', '>', $ended_at);
                            });
                        })
                        ->orWhere(function ($query) use ($began_at, $ended_at) {
                            $query->where('began_at', '>', $began_at)
                                ->where('ended_at', '<', $ended_at);
                        });
                    })
                    ->join('project_user', 'project_user_id', '=', 'project_user.id')
                    ->with('projectUser')
                    ->groupBy('project_user.user_id')
                    ->get()
                    ->keyBy('projectUser.user_id');

                    $validators['users.*.user_id'][] =
                        function ($attribute, $value, $fail) use ($overlappingTimings, $data) {
                            if (isset($overlappingTimings[$value])) {
                                $timing = $overlappingTimings[$value];

                                $fail('Время с ' . $timing->began_at->format('H:i') . ' до ' .
                                    $timing->ended_at->format('H:i') .
                                    ($timing->projectUser->project_id == $data['project_id'] ?
                                        ' занято другой деятельностью' : ' занято деятельностью в ' .
                                        $timing->projectUser->project->name)) .
                                    ' (#' . $timing->id . ')';
                            }
                        };
                }
            }

            return $validators;
        }

        return [];
    }

    protected function cleanForms(&$data)
    {
        foreach ($data['forms'] ?? [] as $formId => $form) {
            if ($formId != 'files') {
                foreach ($form[0]['data'] ?? [] as $key => $value) {
                    $trimmedValue = is_string($value) ? trim($value) : $value;

                    if ($trimmedValue !== 0 && $trimmedValue !== '0' && empty($trimmedValue)) {
                        unset($form['data'][$key]);
                    }
                }

                if (empty($form[0]['data'])) {
                    unset($data['forms'][$formId]);
                }
            }
        }
    }
}
