<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Excel;
use App\Models\Activity;
use App\Exports\Services as ServicesExport;
use App\Exports\Cases as CasesExport;
use App\Exports\MinimalCases;
use App\Exports\MDR;
use App\Lib\Etc;
use App\Lib\Legacy\Indicators\ETBUIndicatorsComputer5;

class ServicesController extends Controller
{
    protected function addDocumentsJoin($query, $projectIds = [6])
    {
        $joins = $query->getQuery()->joins;

        if ($joins != null) {
            foreach ($joins as $join) {
                if (($join->table instanceof \Illuminate\Database\Query\Expression)
                    && strpos($join->table->getValue(), '`d`') !== false
                ) {
                    return;
                }
            }
        }

        $form_id = [6 => 6, 7 => 9, 11 => 10][$projectIds[0]];

        $query->select('users.*');

        $documentsSubquery = DB::table('documents')
            ->select(
                'documents.*',
                'user_id',
                DB::raw('ROW_NUMBER() OVER (PARTITION BY original_id ORDER BY updated_at DESC) as rn')
            )
            ->leftJoin('document_user', 'document_id', '=', 'id')
            ->where('form_id', $form_id);

        if ($till = request()->till) {
            $documentsSubquery->whereDate('updated_at', '<=', $till);
        }

        $query->leftJoinSub($documentsSubquery, 'd', function ($query) {
            $query->whereColumn('d.user_id', 'users.id')
                ->where('d.rn', 1);
        });
    }

    protected function getQuery($request)
    {
        $from = $request->from;
        $till = $request->till;

        $users = $request->users;

        if (!$request->has('verified')
            && !in_array('superuser', $request->user()->roles)
            && !in_array($request->user()->id, array_keys(Etc::$employeesListedByManager))
        ) {
            $users = [$request->user()->id];
        }

        $items = Activity::with([
            'user',
            'project',
            'allUsers',
            'allUsers.part:id,description',
            'allUsers.user:id,name',
            'allUsers.projectUser.location:id,code',
            'timings' => function ($query) use ($users) {
                if (!empty($users)) {
                    $query->whereHas('projectUser', function ($query) use ($users) {
                        $query->whereIn('user_id', $users);
                    });
                }
            }
        ]);

        if ($request->outreach) {
            ETBUIndicatorsComputer5::addOutreachServicesClause($items, $request, $request->parts, null, false, $request->inverse == 1);

            $items->where(function ($query) {
                $query->whereJsonContains('d.data->6g6x', 'uQRP')
                    ->orWhereJsonContains('d.data->6g6x', 'gG99')
                    ->orWhereJsonContains('d.data->6g6x', '2AJg')
                    ->orWhereJsonContains('d.data->6g6x', 'YNoK');
            });

            $this->addDocumentsJoin($items, $request->projects);

            $items->select('activities.*')
                ->groupBy('activities.id');

            $from = $till = $request->parts = null;
        }

        if ($from) {
            $items->where('start_date', '>=', $from);
        }

        if ($till) {
            $items->where('start_date', '<=', $till);
        }

        if ($search = $request->search) {
            $items->where(function ($query) use ($search, $users) {
                $query->where('title', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%")
                    ->orWhereHas('timings', function ($query) use ($search, $users) {
                        $query->where('comment', 'like', "%$search%");

                        if (!empty($users)) {
                            $query->whereHas('projectUser', function ($query) use ($users) {
                                $query->whereIn('user_id', $users);
                            });
                        }
                    })
                    ->orWhereHas('allUsers', function ($query) use ($search) {
                        $query->where('role', 'client')
                        ->whereHas('user', function ($query) use ($search) {
                            $query->where('name', 'like', "%$search%");
                        });
                    });
            });
        }

        if ($searchActivities = $request->searchActivities) {
            $items->where(function ($query) use ($searchActivities, $users) {
                $query->where('title', 'like', "%$searchActivities%")
                    ->orWhere('description', 'like', "%$searchActivities%")
                    ->orWhereHas('timings', function ($query) use ($searchActivities, $users) {
                        $query->where('comment', 'like', "%$searchActivities%");

                        if (!empty($users)) {
                            $query->whereHas('projectUser', function ($query) use ($users) {
                                $query->whereIn('user_id', $users);
                            });
                        }
                    });
            });
        }

        if ($request->projects) {
            $items->whereIn('activities.project_id', $request->projects);
        }

        if ($request->verified) {
            $items->whereHas('timings', function ($query) {
                $query->where('verified', true);
            });
        }

        if ($users) {
            $items->whereHas('allUsers', function ($query) use ($users, $request) {
                $query->whereIn('user_id', $users);

                if (!$request->has('verified')
                    && !in_array('superuser', $request->user()->roles)
                    && !in_array($request->user()->id, array_keys(Etc::$employeesListedByManager))
                ) {
                    $query->whereHas('projectUser', function ($query) use ($request) {
                        $query->whereNull('terminated_at')
                            ->orWhere('terminated_at', '>', now());
                    });
                }
            });
        }

        if ($request->parts
            || $request->locations
            || $request->profileField
            || $request->vuln
            || $request->mdr
            || $request->partner
            || $request->examinedAfter
            || $request->examined
            || $request->examinedFrom
        ) {
            $items->whereHas('users', function ($query) use ($request) {
                $query->where('role', 'client');

                if ($request->parts) {
                    if ($request->inverse == 1) {
                        $query->whereNotIn('user_activity.part_id', $request->parts);
                    } else {
                        $query->whereIn('user_activity.part_id', $request->parts);
                    }
                }

                if ($request->locations) {
                    $query->whereHas('projectsUsers', function ($query) use ($request) {
                        $query->whereColumn('project_user.id', 'user_activity.project_user_id')
                            ->whereIn('location_id', $request->locations);
                    });
                }

                if ($request->examinedAfter
                    || $request->examined == 'nonNegative'
                    || $request->examinedFrom == 'us'
                ) {
                    $this->addDocumentsJoin($query, $request->projects);

                    $query->where(function ($query) use ($request) {
                        $query->where(function ($query) use ($request) {
                            if ($request->examinedAfter) {
                                $query->where(function ($query) use ($request) {
                                    $query->whereNotNull('d.data->it7x')
                                        ->whereDate('d.data->it7x', '>=', $request->examinedAfter);
                                });
                            }

                            if ($request->examined == 'nonNegative') {
                                $query->where(function ($query) {
                                    $query->whereNotNull('d.data->d6XS')
                                        ->where('d.data->d6XS', '!=', 'vNSz')
                                        ->where('d.data->d6XS', '!=', 'MPCj');
                                });
                            }

                            if ($request->examinedFrom == 'us') {
                                $query->where(function ($query) {
                                    $query->whereNotNull('d.data->Xnsu')
                                        ->where('d.data->Xnsu', 'LbKw');
                                });
                            }
                        })
                        ->orWhere(function ($query) use ($request) {
                            if ($request->examinedAfter) {
                                $query->where(function ($query) use ($request) {
                                    $query->whereNotNull('d.data->mynW')
                                        ->whereDate('d.data->mynW', '>=', $request->examinedAfter);
                                });
                            }

                            if ($request->examined == 'nonNegative') {
                                $query->where(function ($query) {
                                    $query->whereNotNull('d.data->eNaZ')
                                        ->where('d.data->eNaZ', '!=', 'muv9')
                                        ->where('d.data->eNaZ', '!=', 'EohW');
                                });
                            }

                            if ($request->examinedFrom == 'us') {
                                $query->where(function ($query) {
                                    $query->whereNotNull('d.data->roMc')
                                        ->where('d.data->roMc', 'XiCo');
                                });
                            }
                        });
                    });
                }

                if ($request->profileField) {
                    $this->addDocumentsJoin($query, $request->projects);

                    $profileField = "d.data->{$request->profileField}";

                    if ($request->profileOp == 'contains') {
                        if (!is_array($request->profileValue)) {
                            $query->whereJsonContains($profileField, $request->profileValue);
                        } else {
                            $query->where(function ($query) use ($request, $profileField) {
                                foreach ($request->profileValue as $value) {
                                    $query->orWhereJsonContains($profileField, $value);
                                }
                            });
                        }
                    } elseif ($request->profileOp == 'notNull') {
                        $query->whereNotNull($profileField);
                    } elseif ($request->profileOp == 'notin') {
                        if (!is_array($request->profileValue)) {
                            $query->whereJsonDoesntContain($profileField, $request->profileValue);
                        } else {
                            $query->where(function ($query) use ($request, $profileField) {
                                foreach ($request->profileValue as $value) {
                                    $query->whereJsonDoesntContain($profileField, $value);
                                }
                            });
                        }
                    } elseif ($request->profileOp == 'or') {
                        $query->whereIn($profileField, $request->profileValue);
                    } else {
                        $query->whereNotNull($profileField)
                            ->where($profileField, $request->input('profileOp', '='), $request->profileValue);
                    }
                }

                if ($request->profileField2) {
                    $this->addDocumentsJoin($query, $request->projects);

                    $profileField2 = "d.data->{$request->profileField2}";

                    if ($request->profileOp2 == 'null') {
                        $query->whereNull($profileField2);
                    } else {
                        $query->whereNotNull($profileField2)
                            ->where($profileField2, $request->input('profileOp2', '='), $request->profileValue2);
                    }
                }

                if ($request->vuln) {
                    $this->addDocumentsJoin($query, $request->projects);

                    if ($request->vuln != 2) {
                        $query->where(function ($query) {
                            $query->where(function ($query) {
                                $query->where(function ($query) {
                                    $query->whereNotNull('d.data->d6XS')
                                        ->where('d.data->d6XS', '!=', 'vNSz')
                                        ->where('d.data->d6XS', '!=', 'MPCj');
                                })
                                ->orWhere(function ($query) {
                                    $query->whereNotNull('d.data->eNaZ')
                                        ->where('d.data->eNaZ', '!=', 'muv9')
                                        ->where('d.data->eNaZ', '!=', 'EohW');
                                });
                            })
                            ->orWhere('d.data->9Rq9', 'F6SP')
                            ->orWhere('d.data->q5Mw', true);
                        });
                    }

                    $query->where(function ($query) {
                        $query->whereJsonContains('d.data->6g6x', 'uQRP')
                            ->orWhereJsonContains('d.data->6g6x', 'gG99')
                            ->orWhereJsonContains('d.data->6g6x', '2AJg')
                            ->orWhereJsonContains('d.data->6g6x', 'YNoK')
                            ->orWhereJsonContains('d.data->6g6x', 'dLBE');
                    });
                }

                if ($request->mdr) {
                    $this->addDocumentsJoin($query, $request->projects);

                    if ($request->mdr == -1) {
                        $query->whereJsonDoesntContain('d.data->d6XS', 'gBZJ')
                            ->whereJsonDoesntContain('d.data->d6XS', 'BHpL')
                            ->whereJsonDoesntContain('d.data->d6XS', 'TrxW')
                            ->whereJsonDoesntContain('d.data->d6XS', 'aKnw')
                            ->whereJsonDoesntContain('d.data->eNaZ', 'qXTv')
                            ->whereJsonDoesntContain('d.data->eNaZ', 'wwHt')
                            ->whereJsonDoesntContain('d.data->eNaZ', 'qXTv')
                            ->whereJsonDoesntContain('d.data->eNaZ', 'uK7c')
                            ->whereJsonDoesntContain('d.data->rjWN', 'AyZG')
                            ->whereJsonDoesntContain('d.data->rjWN', 'CcBo')
                            ->whereJsonDoesntContain('d.data->rjWN', '2EY9')
                            ->whereJsonDoesntContain('d.data->rjWN', 'WQoj');
                    } else {
                        $query->where(function ($query) {
                            $query->whereJsonContains('d.data->d6XS', 'gBZJ')
                                ->orWhereJsonContains('d.data->d6XS', 'BHpL')
                                ->orWhereJsonContains('d.data->d6XS', 'TrxW')
                                ->orWhereJsonContains('d.data->d6XS', 'aKnw')
                                ->orWhereJsonContains('d.data->eNaZ', 'qXTv')
                                ->orWhereJsonContains('d.data->eNaZ', 'wwHt')
                                ->orWhereJsonContains('d.data->eNaZ', 'qXTv')
                                ->orWhereJsonContains('d.data->eNaZ', 'uK7c')
                                ->orWhereJsonContains('d.data->rjWN', 'AyZG')
                                ->orWhereJsonContains('d.data->rjWN', 'CcBo')
                                ->orWhereJsonContains('d.data->rjWN', '2EY9')
                                ->orWhereJsonContains('d.data->rjWN', 'WQoj');
                        });
                    }
                }

                if ($request->partner) {
                    $this->addDocumentsJoin($query, $request->projects);

                    $query->whereNotNull('d.data->Y4N4');
                }
            });
        } else {
            $items->whereHas('parts', function ($query) {
                $query->where('role', 'client');
            });
        }

        if ($request->fullch) {
            $items->whereHas('users', function ($query) use ($request) {
                $this->addDocumentsJoin($query, $request->projects);

                $query->where('users.roles', 'like', '%client%')
                    ->whereHas('projects', function ($query) {
                        $query->where('project_id', 6);
                    })
                    ->whereJsonDoesntContain('d.data->6g6x', 'ab8e')
                    ->whereJsonContains('d.data->6g6x', 'iNHa')
                    ->whereNotNull('d.data->kB3w')
                    ->whereNotNull('d.data->GWQS')
                    ->leftJoin('user_user', 'user_user.user_id', '=', 'users.id')
                    ->where(function ($query) {
                        $keyword = 'шоу';

                        $query->selectRaw('count(*)')
                            ->from('activities')
                            ->join('user_activity', 'user_activity.activity_id', '=', 'activities.id')
                            ->leftJoin('timings', 'timings.activity_id', '=', 'activities.id')
                            ->where(function ($query) {
                                $query->where('user_activity.user_id', DB::raw('users.id'))
                                    ->orWhere('user_activity.user_id', DB::raw('user_user.related_user_id'));
                            })
                            ->whereIn('user_activity.part_id', [364, 365, 368, 369, 373])
                            ->where('project_id', 6)
                            ->whereDate('d.data->kB3w', '<=', DB::raw('start_date'))
                            ->whereDate('d.data->GWQS', '>=', DB::raw('start_date'))
                            ->where(function ($query) use ($keyword) {
                                $query->whereNull('title')
                                    ->orWhere('title', 'not like', "%$keyword%");
                            })
                            ->where(function ($query) use ($keyword) {
                                $query->whereNull('description')
                                    ->orWhere('description', 'not like', "%$keyword%");
                            })
                            ->where(function ($query) use ($keyword) {
                                $query->whereNull('timings.comment')
                                    ->orWhere('timings.comment', 'not like', "%$keyword%");
                            });
                    }, '>=', 2)
                    ->where(function ($query) {
                        $keyword = 'диагностик';

                        $query->selectRaw('count(*)')
                            ->from('activities')
                            ->join('user_activity', 'user_activity.activity_id', '=', 'activities.id')
                            ->leftJoin('timings', 'timings.activity_id', '=', 'activities.id')
                            ->where(function ($query) {
                                $query->where('user_activity.user_id', DB::raw('users.id'))
                                    ->orWhere('user_activity.user_id', DB::raw('user_user.related_user_id'));
                            })
                            ->whereIn('user_activity.part_id', [367, 371, 375, 376])
                            ->where('project_id', 6)
                            ->whereDate('d.data->kB3w', '<=', DB::raw('start_date'))
                            ->whereDate('d.data->GWQS', '>=', DB::raw('start_date'))
                            ->where(function ($query) use ($keyword) {
                                $query->where('title', 'like', "%$keyword%")
                                    ->orWhere('description', 'like', "%$keyword%")
                                    ->orWhere('timings.comment', 'like', "%$keyword%");
                            });
                    }, '>=', 1)
                    ->where(function ($query) {
                        $keyword = 'шоу';

                        $query->selectRaw('count(*)')
                            ->from('activities')
                            ->join('user_activity', 'user_activity.activity_id', '=', 'activities.id')
                            ->leftJoin('timings', 'timings.activity_id', '=', 'activities.id')
                            ->where(function ($query) {
                                $query->where('user_activity.user_id', DB::raw('users.id'))
                                    ->orWhere('user_activity.user_id', DB::raw('user_user.related_user_id'));
                            })
                            ->whereIn('user_activity.part_id', [367, 371, 375, 376])
                            ->where('project_id', 6)
                            ->whereDate('d.data->kB3w', '<=', DB::raw('start_date'))
                            ->whereDate('d.data->GWQS', '>=', DB::raw('start_date'))
                            ->where(function ($query) use ($keyword) {
                                $query->whereNull('title')
                                    ->orWhere('title', 'not like', "%$keyword%");
                            })
                            ->where(function ($query) use ($keyword) {
                                $query->whereNull('description')
                                    ->orWhere('description', 'not like', "%$keyword%");
                            })
                            ->where(function ($query) use ($keyword) {
                                $query->whereNull('timings.comment')
                                    ->orWhere('timings.comment', 'not like', "%$keyword%");
                            });
                    }, '>=', 3)
                    ->whereDate('d.data->kB3w', '<=', DB::raw('start_date'))
                    ->whereDate('d.data->GWQS', '>=', DB::raw('start_date'));
            });
        }

        if ($request->position || $request->positionex) {
            $items->whereHas('allUsers.projectUser', function ($query) use ($request) {
                $position = $request->positionex ?: $request->position;
                $query->where('position', $request->positionex ? 'not like' : 'like', "%$position%");
            });
        }

        if ($request->groupby) {
            if ($request->groupby == 2) {
                $items->groupBy('start_date', 'start_time');
            } else {
                $items->groupBy($request->groupby);
            }
        }

        foreach ($request->input('sortBy', ['activities.start_date']) as $index => $order) {
            $items->orderBy($order, isset($request->input('sortDesc', [false])[$index]) &&
                $request->input('sortDesc', ['false'])[$index] == 'true' ? 'desc' : 'asc');
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

        $items = $this->getQuery($request)
            ->with([
                // 'verifiedTiming',
                'frozenTiming'
            ]);

        return $items->paginate($per_page != -1 ? $per_page : $items->count());
    }

    public function download(Request $request)
    {
        $query = $this->getQuery($request);

        return Excel::download(new ServicesExport($query), 'Услуги ' . date('Y-m-d His') . '.xlsx');
    }

    public function downloadCases(Request $request)
    {
        return Excel::download(
            $request->has('minimal') ? new MinimalCases($request, $request->has('hospital')) : new CasesExport,
            'Клиенты ' . date('Y-m-d Hi') . '.xlsx'
        );
    }

    public function downloadMDR(Request $request)
    {
        return Excel::download(
            new MDR($request),
            'Клиенты ' . date('Y-m-d Hi') . '.xlsx'
        );
    }
}
