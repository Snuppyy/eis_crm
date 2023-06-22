<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use DB;
use Storage;
use Excel;
use Carbon\Carbon;

use App\Http\Requests\Users\StoreUser;
use App\Http\Requests\Users\UpdateUser;

use App\Models\User;
use App\Models\Part;
use App\Models\Project;
use App\Models\ProjectUser;

use App\Exports\Users as UsersExport;
use App\Exports\Training as TrainingExport;
use App\Exports\Factors as FactorsExport;
use App\Lib\DocumentResults;
use App\Lib\Etc;
use App\Lib\Indicators;
use App\Lib\Legacy\Indicators\ETBUIndicatorsComputer3;
use App\Lib\UserMerger;
use App\Lib\Util;
use App\Models\Document;
use App\Models\Form;

class UsersController extends Controller
{
    protected function addDocumentsJoin($query, $request, $alias = false, $form_id = null)
    {
        if (empty($form_id)) {
            $projectId = $request->project;
            $form_id = [6 => 6, 7 => 9, 11 => 10][$projectId];
        }

        $postfix = $alias ? $form_id : '';

        $joins = $query->getQuery()->joins;

        if ($joins != null) {
            foreach ($joins as $join) {
                if (($join->table instanceof \Illuminate\Database\Query\Expression)
                    && strpos($join->table->getValue(), "`d$postfix`") !== false
                ) {
                    return;
                }
            }
        }

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
            $documentsSubquery->whereDate(
                'updated_at',
                '<=',
                in_array($form_id, [11, 52]) ? Carbon::parse($till)->addDays(6) : $till
            );
        }

        if ($dscField = request()->dscField) {
            $dscField = explode('.', $dscField);

            if ($dscField[0] == $form_id) {
                $documentsSubquery->where("data->{$dscField[1]}", request()->dscValue);
            }
        }

        if (($adherence = request()->adherence) && $adherence == $form_id) {
            $documentsSubquery->select('*', DB::raw('ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY json_unquote(json_extract(data, \'$."TNF5"\')) DESC) as rn'));
        }

        $query->leftJoinSub($documentsSubquery, "d$postfix", function ($query) use ($postfix) {
            $query->whereColumn("d$postfix.user_id", 'users.id')
                ->where("d$postfix.rn", 1);
        });
    }

    protected function getColumnForField($field, $query, $request)
    {
        $field = explode('.', $field);

        $form = null;

        if (isset($field[1])) {
            $form = array_shift($field);

            $this->addDocumentsJoin($query, $request, true, $form);
        } else {
            $this->addDocumentsJoin($query, $request);
        }

        return "d{$form}.data->{$field[0]}";
    }

    protected function getQuery(Request $request)
    {
        $per_page = (int) $request->input('itemsPerPage');
        $project = (int) $request->project;

        $items = User::when(
            $request->search,
            function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $search = $request->search;

                    $query->where('users.name', 'like', "%$search%")
                        ->orWhere('users.email', 'like', "%$search%")
                        ->orWhere('users.phone', 'like', "%$search%");
                });
            }
        )
        ->when(
            $role = $request->role,
            function ($query) use ($role, $request) {
                $query->where('users.roles', 'like', "%$role%")
                    ->with([
                        'activities' => function ($query) use ($request) {
                            $query->select(['id', 'activities.user_id'])
                                ->orderBy('start_date')
                                ->orderBy('start_time')
                                ->limit(1);

                            if ($request->project) {
                                $query->where('project_id', $request->project);
                            }
                        },
                        'activities.user:id,name'
                    ]);
            }
        )
        ->when(
            !$project && !in_array('superuser', $request->user()->roles)
                && !in_array($request->user()->id, array_keys(Etc::$employeesListedByManager)),
            function ($query) use ($request) {
                if ($request->has('withPositions')) {
                    $query->where('id', $request->user()->id);
                } else {
                    $projects = Project::whereHas('projectUsers', function ($query) use ($request) {
                        $query->where('user_id', $request->user()->id)
                            ->where(function ($query) {
                                $query->whereNull('terminated_at')
                                    ->orWhere('terminated_at', '>', now());
                            });
                    })->get()->pluck('id');

                    $query->where('roles', 'like', '%client%')
                        ->where(function ($query) use ($projects) {
                            $query->whereHas('projectsUsers', function ($query) use ($projects) {
                                $query->whereIn('project_id', $projects);
                            })
                            ->orWhereDoesntHave('projectsUsers');
                        });
                }
            }
        )
        ->when(
            (!$project || $per_page != -1)
                && !$request->has('withPositions')
                && in_array('superuser', $request->user()->roles)
                && !$role,
            function ($query) {
                $query->where('roles', 'not like', '%client%');
            }
        )
        ->when(
            $project,
            function ($query) use ($project) {
                $query->whereHas('projects', function ($query) use ($project) {
                    $query->where('project_id', $project);
                });
            }
        );

        if ($request->has('withPositions')) {
            $items->where('roles', 'not like', '%client%');

            $userId = $request->user()->id;

            if ($request->projects || in_array($userId, array_keys(Etc::$employeesListedByManager))) {
                $items->whereHas('projectsUsers', function ($query) use ($request, $userId) {
                    if ($request->projects) {
                        $query->whereIn('project_id', $request->projects);
                    }

                    if (!in_array('superuser', $request->user()->roles) &&
                        in_array($userId, array_keys(Etc::$employeesListedByManager))
                    ) {
                        $query->where(function ($query) use ($userId) {
                            $query->where(function ($query) use ($userId) {
                                $query->whereHas('user', function ($query) {
                                    $query->where('roles', 'not like', '%superuser%');
                                });

                                $usersPerProjects = Etc::$employeesListedByManager[$userId];

                                $query->where(function ($query) use ($usersPerProjects) {
                                    foreach ($usersPerProjects as $projectId => $userIds) {
                                        $query->orWhere(function ($query) use ($projectId, $userIds) {
                                            $query->where('project_id', $projectId);

                                            if (count($userIds)) {
                                                $query->whereIn('user_id', $userIds);
                                            }
                                        });
                                    }
                                });
                            })
                            ->orWhere(function ($query) use ($userId) {
                                $query->where('project_id', '!=', 6)
                                    ->where('user_id', $userId);
                            });
                        });
                    }
                });
            }
        }

        if ($request->profileField) {
            $profileField = $this->getColumnForField($request->profileField, $items, $request);

            if ($request->profileOp == 'contains') {
                if (!is_array($request->profileValue)) {
                    $items->whereJsonContains($profileField, $request->profileValue);
                } else {
                    $items->where(function ($query) use ($request, $profileField) {
                        foreach ($request->profileValue as $value) {
                            $query->orWhereJsonContains($profileField, $value);
                        }
                    });
                }
            } elseif ($request->profileOp == 'true') {
                $items->where($profileField, true);
            } elseif ($request->profileOp == 'notNull') {
                $items->whereNotNull($profileField);
            } elseif ($request->profileOp == 'null') {
                $items->whereNull($profileField);
            } elseif ($request->profileOp == 'dateEqMore') {
                $items->whereNotNull($profileField)
                    ->whereDate($profileField, '>=', $request->profileValue);
            } elseif ($request->profileOp == 'neq') {
                $items->where(function ($query) use ($request, $profileField) {
                    $query->whereNull($profileField)
                        ->orWhere($profileField, '!=', $request->profileValue);
                });
            } else {
                $items->where(function ($query) use ($request, $profileField) {
                    if ($request->profileOp == 'or') {
                        $query->where(function ($query) use ($request, $profileField) {
                            foreach ($request->profileValue as $value) {
                                $query->orWhere($profileField, $value);
                            }
                        });
                    } else {
                        $query->where($profileField, $request->input('profileOp', '='), $request->profileValue);

                        if ($request->profileOp == '<>') {
                            $query->orWhereNull($profileField);
                        } else {
                            $query->whereNotNull($profileField);
                        }
                    }
                });
            }
        }

        if ($request->profileField2) {
            $profileField2 = $this->getColumnForField($request->profileField2, $items, $request);

            $items->whereNotNull($profileField2);

            if ($request->profileOp2 == 'notin') {
                $items->whereNotIn($profileField2, $request->profileValue2);
            } elseif ($request->profileOp2 == 'notNull') {
                $items->whereNotNull($profileField2);
            } elseif ($request->profileOp2 == 'null') {
                $items->whereNull($profileField2);
            } else {
                $items->where(
                    $profileField2,
                    $request->input('profileOp2', '='),
                    $request->profileValue2
                );
            }
        }

        if ($request->profileField4) {
            $profileField4 = $this->getColumnForField($request->profileField4, $items, $request);

            $items->whereNotNull($profileField4)
                ->where(
                    $profileField4,
                    $request->input('profileOp4', '='),
                    $request->profileValue4
                );
        }

        if ($request->profileField3) {
            $profileField3 = $this->getColumnForField($request->profileField3, $items, $request);

            if ($request->profileOp3 == 'contains') {
                if (!is_array($request->profileValue3)) {
                    $items->whereJsonContains($profileField3, $request->profileValue3);
                } else {
                    $items->where(function ($query) use ($request, $profileField3) {
                        foreach ($request->profileValue3 as $value) {
                            $query->orWhereJsonContains($profileField3, $value);
                        }
                    });
                }
            } elseif ($request->profileOp3 == 'notin' || $request->profileOp3 == 'otherthan') {
                if (!is_array($request->profileValue3)) {
                    $items->whereJsonDoesntContain($profileField3, $request->profileValue3);
                } else {
                    $items->where(function ($query) use ($request, $profileField3) {
                        foreach ($request->profileValue3 as $value) {
                            $query->whereJsonDoesntContain($profileField3, $value);
                        }
                    });
                }

                if ($request->profileOp3 == 'otherthan') {
                    $items->whereNotNull($profileField3);
                }
            } elseif ($request->profileOp3 == 'nulloronly') {
                $items->where(function ($query) use ($request, $profileField3) {
                    $query->where(function ($query) use ($profileField3) {
                        $query->whereRaw('json_length(' . str_replace('->', ', "$.', $profileField3) . '") = 0')
                            ->orWhereNull($profileField3);
                    })
                    ->orWhere(function ($query) use ($request, $profileField3) {
                        $query->whereRaw('json_length(' . str_replace('->', ', "$.', $profileField3) . '") = 1')
                            ->whereJsonContains($profileField3, $request->profileValue3);
                    });
                });
            } elseif ($request->profileOp3 == 'notNull') {
                $items->whereNotNull($profileField3);
            } elseif ($request->profileOp == 'true') {
                $items->where($profileField3, true);
            } else {
                $items->whereNotNull($profileField3)
                    ->where(
                        $profileField3,
                        $request->input('profileOp3', '='),
                        $request->profileValue3
                    );
            }
        }

        if ($request->examinedAfter
            || $request->examined == 'nonNegative'
            || $request->examinedFrom == 'us'
        ) {
            $this->addDocumentsJoin($items, $request);
        }

        $items->where(function ($query) use ($request) {
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

        if ($request->vuln) {
            $this->addDocumentsJoin($items, $request);

            if ($request->vuln != 3) {
                $items->where(function ($query) {
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

            if ($request->vuln != 2) {
                $items->where(function ($query) {
                    $query->whereJsonContains('d.data->6g6x', 'uQRP')
                        ->orWhereJsonContains('d.data->6g6x', 'gG99')
                        ->orWhereJsonContains('d.data->6g6x', '2AJg')
                        ->orWhereJsonContains('d.data->6g6x', 'YNoK')
                        ->orWhereJsonContains('d.data->6g6x', 'dLBE');
                });
            }
        }

        if ($request->nonVuln) {
            $this->addDocumentsJoin($items, $request);

            $items->where(function ($query) {
                $query->whereJsonContains('d.data->6g6x', 'iNHa')
                    ->orWhereJsonContains('d.data->6g6x', 'aSu3')
                    ->orWhereJsonContains('d.data->6g6x', 'HeBk')
                    ->orWhereJsonContains('d.data->6g6x', 'bEk7');
            });
        }

        if ($request->redundant) {
            $this->addDocumentsJoin($items, $request);

            if ($request->redundant == 2) {
                $items->whereRaw(
                    'if(json_contains(d.data, \'"uQRP"\', "$.6g6x") ' .
                        'or json_contains(d.data, \'"YNoK"\', "$.6g6x"), 1, 0) + ' .
                    'json_contains(d.data, \'"2AJg"\', "$.6g6x") + ' .
                    'json_contains(d.data, \'"gG99"\', "$.6g6x") ' .
                    '> 1'
                );
            } else {
                $items->whereRaw(
                    'if(json_contains(d.data, \'"uQRP"\', "$.6g6x") ' .
                        'or json_contains(d.data, \'"YNoK"\', "$.6g6x"), 1, 0) + ' .
                    'json_contains(d.data, \'"2AJg"\', "$.6g6x") + ' .
                    'json_contains(d.data, \'"gG99"\', "$.6g6x") + ' .
                    'if(json_contains(d.data, \'"gG99"\', "$.6g6x") ' .
                        'or json_contains(d.data, \'"MuuE"\', "$.6g6x"), 1, 0) + ' .
                    'json_contains(d.data, \'"iNHa"\', "$.6g6x") + ' .
                    'json_contains(d.data, \'"aSu3"\', "$.6g6x") + ' .
                    'json_contains(d.data, \'"dLBE"\', "$.6g6x") ' .
                    '> 1'
                );
            }
        }

        if ($gender = $request->gender) {
            $this->addDocumentsJoin($items, $request);

            if ($request->project != 6) {
                $this->addDocumentsJoin($items, null, true, 6);

                $otherFormId = $request->project == 7 ? 10 : 9;
                $this->addDocumentsJoin($items, null, true, $otherFormId);
            }

            if ($gender == 'male') {
                if ($request->project == 6) {
                    $items->where(function ($query) {
                        $query->whereJsonContains('d.data->f8Bs', 'GZkX')
                            ->orWhereJsonContains('d.data->f8Bs', '9Dsr');
                    });
                } else {
                    $items->where(function ($query) use ($otherFormId) {
                        $query->where('d.data->WuTe', 'Wr8D')
                            ->orWhere("d$otherFormId.data->WuTe", 'Wr8D')
                            ->orWhereJsonContains('d6.data->f8Bs', 'GZkX')
                            ->orWhereJsonContains('d6.data->f8Bs', '9Dsr');
                    });
                }
            } else {
                if ($request->project == 6) {
                    $items->where(function ($query) {
                        $query->whereJsonContains('d.data->f8Bs', 'mtD8')
                            ->orWhereJsonContains('d.data->f8Bs', 'fEG9');
                    });
                } else {
                    $items->where(function ($query) use ($otherFormId) {
                        $query->where('d.data->WuTe', 'PE5b')
                            ->orWhere("d$otherFormId.data->WuTe", 'PE5b')
                            ->orWhereJsonContains('d6.data->f8Bs', 'mtD8')
                            ->orWhereJsonContains('d6.data->f8Bs', 'fEG9');
                    });
                }
            }
        }

        if ($request->profileDate) {
            $profileDate = $this->getColumnForField($request->profileDate, $items, $request);

            if ($request->from) {
                $items->where($profileDate, '>=', $request->from);
            }

            if ($request->till && (!$request->fullch || $request->fullch == 1)) {
                if ($request->profileDate2) {
                    $items->where($profileDate, '<=', $request->till);
                } else {
                    $items->where($profileDate, '<=', $request->till);
                }
            }
        }

        if ($request->supported == 2) {
            $this->addDocumentsJoin($items, $request);

            $items->where('d.data->rq3B', 'bHWo')
                ->where('d.data->wrKd', true)
                ->where(function ($query) {
                    $query->where('d.data->9Rq9', 'F6SP')
                        ->orWhere(function ($query) {
                            $query->where(function ($query) {
                                $query->where(function ($query) {
                                    $query->whereNotNull('d.data->Xnsu')
                                        ->where('d.data->Xnsu', 'LbKw');
                                })
                                ->where(function ($query) {
                                    $query->whereNotNull('d.data->it7x')
                                        ->whereDate('d.data->it7x', '>=', '2020-09-01');
                                })
                                ->whereNotNull('d.data->d6XS')
                                ->where('d.data->d6XS', '!=', 'vNSz')
                                ->where('d.data->d6XS', '!=', 'MPCj');
                            })
                            ->orWhere(function ($query) {
                                $query->where(function ($query) {
                                    $query->whereNotNull('d.data->roMc')
                                        ->where('d.data->roMc', 'XiCo');
                                })
                                ->where(function ($query) {
                                    $query->whereNotNull('d.data->mynW')
                                        ->whereDate('d.data->mynW', '>=', '2020-09-01');
                                })
                                ->whereNotNull('d.data->eNaZ')
                                ->where('d.data->eNaZ', '!=', 'muv9')
                                ->where('d.data->eNaZ', '!=', 'EohW');
                            });
                        });
                });
        }

        if ($request->supported == 3) {
            $this->addDocumentsJoin($items, $request);

            $items->where(function ($query) use ($request) {
                $query->whereNull('d.data->rq3B')
                    ->orWhere('d.data->rq3B', '!=', 'bHWo')
                    ->orWhereNull('d.data->wrKd')
                    ->orWhere('d.data->wrKd', '!=', true)
                    ->orWhere(function ($query) {
                        $query->where(function ($query) {
                            $query->whereNull('d.data->9Rq9')
                                ->orWhere('d.data->9Rq9', '!=', 'F6SP');
                        })
                        ->where(function ($query) {
                            $query->where(function ($query) {
                                $query->whereNull('d.data->Xnsu')
                                    ->orWhere('d.data->Xnsu', '!=', 'LbKw');
                            })
                            ->orWhere(function ($query) {
                                $query->whereNull('d.data->it7x')
                                    ->orWhereDate('d.data->it7x', '<=', '2020-09-01');
                            })
                            ->orWhereNull('d.data->d6XS')
                            ->orWhere('d.data->d6XS', 'vNSz')
                            ->orWhere('d.data->d6XS', 'MPCj');
                        })
                        ->where(function ($query) {
                            $query->where(function ($query) {
                                $query->whereNull('d.data->roMc')
                                    ->orWhere('d.data->roMc', '!=', 'XiCo');
                            })
                            ->orWhere(function ($query) {
                                $query->whereNull('d.data->mynW')
                                    ->orWhereDate('d.data->mynW', '<=', '2020-09-01');
                            })
                            ->orWhereNull('d.data->eNaZ')
                            ->orWhere('d.data->eNaZ', 'muv9')
                            ->orWhere('d.data->eNaZ', 'EohW');
                        });
                    });

                if ($request->from) {
                    $query->orWhere('d.data->Q5xs', '<', $request->from);
                }

                if ($request->till) {
                    $query->orWhere('d.data->Q5xs', '>', $request->till);
                }
            });
        }

        if ($request->dateFilter == 'examined') {
            $this->addDocumentsJoin($items, $request);

            if ($request->from || $request->till) {
                $items->where(function ($query) use ($request) {
                    $query->where(function ($query) use ($request) {
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

                        if ($request->from) {
                            $query->where('d.data->it7x', '>=', $request->from);
                        }

                        if ($request->till) {
                            $query->where('d.data->it7x', '<=', $request->till);
                        }
                    })
                    ->orWhere(function ($query) use ($request) {
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

                        if ($request->from) {
                            $query->where('d.data->mynW', '>=', $request->from);
                        }

                        if ($request->till) {
                            $query->where('d.data->mynW', '<=', $request->till);
                        }
                    });
                });
            }
        }

        if ($request->searchActivities) {
            if (!$request->inverse) {
                $items->whereHas('activities', function ($query) use ($request) {
                    if (!$request->searchNot) {
                        $query->where('project_id', $request->project)
                            ->where(function ($query) use ($request) {
                                $search = $request->searchActivities;

                                $query->where('title', 'like', "%$search%")
                                    ->orWhere('description', 'like', "%$search%")
                                    ->orWhereHas('timings', function ($query) use ($search) {
                                        $query->where('comment', 'like', "%$search%");
                                    });
                            });

                        if ($request->users) {
                            $query->whereHas('allUsers', function ($query) use ($request) {
                                $query->whereIn('user_id', $request->users);
                            });
                        }

                        if ($parts = $request->parts) {
                            $query->whereIn(
                                'user_activity.part_id',
                                is_array($parts) ? $parts : [$parts]
                            );
                        }

                        if ($request->from) {
                            $query->where('start_date', '>=', $request->from);
                        }

                        if ($request->till) {
                            $query->where('start_date', '<=', $request->till);
                        }
                    } else {
                        $query->where('project_id', 6)
                            // ->whereIn('user_activity.part_id', [364, 365, 368, 369, 373])
                            ->where(function ($query) use ($request) {
                                $search = $request->searchActivities;

                                $query->where(function ($query) use ($search) {
                                    $query->whereNull('title')
                                        ->orWhere('title', 'not like', "%$search%");
                                })
                                ->where(function ($query) use ($search) {
                                    $query->whereNull('description')
                                        ->orWhere('description', 'not like', "%$search%");
                                })
                                ->whereDoesntHave('timings', function ($query) use ($search) {
                                    $query->where('comment', 'like', "%$search%");
                                });
                            });
                    }
                }, '>=', $request->has('duplicate') ? 2 : 1);
            } else {
                $items->whereDoesntHave('activities', function ($query) use ($request) {
                    $query->where('project_id', $request->project)
                        ->where(function ($query) use ($request) {
                            $search = $request->searchActivities;

                            $query->where('title', 'like', "%$search%")
                                ->orWhere('description', 'like', "%$search%")
                                ->orWhereHas('timings', function ($query) use ($search) {
                                    $query->where('comment', 'like', "%$search%");
                                });
                        });

                    if ($request->users) {
                        $query->whereHas('allUsers', function ($query) use ($request) {
                            $query->whereIn('user_id', $request->users);
                        });
                    }

                    if ($request->inverse != 2 && $parts = $request->parts) {
                        $query->whereIn(
                            'user_activity.part_id',
                            is_array($parts) ? $parts : [$parts]
                        );
                    }

                    if ($request->from) {
                        $query->where('start_date', '>=', $request->from);
                    }

                    if ($request->till) {
                        $query->where('start_date', '<=', $request->till);
                    }
                });
            }
        } elseif ($request->has('parts') && $request->inverse != 1) {
            $items->whereHas('activities', function ($query) use ($request) {
                $parts = $request->parts;

                $query->where('project_id', $request->project);

                if (!empty($parts)) {
                    $query->whereIn(
                        'user_activity.part_id',
                        is_array($parts) ? $parts : [$parts]
                    );
                }

                if ($request->users) {
                    $query->whereHas('allUsers', function ($query) use ($request) {
                        $query->whereIn('user_id', $request->users);
                    });
                }

                if ($request->from) {
                    $query->where('start_date', '>=', $request->from);
                }

                if ($request->till) {
                    if ($request->vuln != 1) {
                        $query->where('start_date', '<=', $request->till);
                    } else {
                        $query->where(function ($query) use ($request) {
                            $query->where(function ($query) use ($request) {
                                $query->where('d.data->2vq3', 'krnX')
                                    ->whereNotNull('d.data->d6XS')
                                    ->where('d.data->d6XS', '!=', 'vNSz')
                                    ->where('d.data->d6XS', '!=', 'MPCj')
                                    ->where('d.data->Xnsu', 'LbKw')
                                    ->whereNotNull('d.data->it7x')
                                    ->where('d.data->it7x', '<=', $request->till);
                            })
                            ->orWhere(function ($query) use ($request) {
                                $query->where('d.data->jnoa', true)
                                    ->whereNotNull('d.data->eNaZ')
                                    ->where('d.data->eNaZ', '!=', 'muv9')
                                    ->where('d.data->eNaZ', '!=', 'EohW')
                                    ->where('d.data->roMc', 'XiCo')
                                    ->whereNotNull('d.data->mynW')
                                    ->where('d.data->mynW', '<=', $request->till);
                            })
                            ->orWhere(function ($query) use ($request) {
                                $query->where(function ($query) {
                                    $query->whereNull('d.data->2vq3')
                                        ->orWhere('d.data->2vq3', '!=', 'krnX')
                                        ->orWhereNull('d.data->d6XS')
                                        ->orWhere('d.data->d6XS', 'vNSz')
                                        ->orWhere('d.data->d6XS', 'MPCj')
                                        ->orWhere('d.data->Xnsu', '!=', 'LbKw')
                                        ->orWhereNull('d.data->it7x');
                                })
                                ->where(function ($query) {
                                    $query->whereNull('d.data->jnoa')
                                        ->orWhere('d.data->jnoa', '!=', true)
                                        ->orWhereNull('d.data->eNaZ')
                                        ->orWhere('d.data->eNaZ', 'muv9')
                                        ->orWhere('d.data->eNaZ', 'EohW')
                                        ->orWhere('d.data->roMc', '!=', 'XiCo')
                                        ->orWhereNull('d.data->mynW');
                                })
                                ->where('d.data->kB3w', '<=', $request->till);
                            });
                        });
                    }
                }

                if ($request->verified) {
                    $query->where(function ($query) use ($request) {
                        $query->whereHas('timings', function ($query) {
                            $query->where('verified', true);
                        });
                    });
                }

                if ($request->position || $request->positionex) {
                    $query->whereHas('allUsers.projectUser', function ($query) use ($request) {
                        $position = $request->positionex ?: $request->position;
                        $query->where('position', $request->positionex ? 'not like' : 'like', "%$position%");
                    });
                }
            });
        } elseif ($request->users) {
            $items->whereHas('activities', function ($query) use ($request) {
                $query->where('project_id', 6)
                    ->whereHas('allUsers', function ($query) use ($request) {
                        $query->whereIn('user_id', $request->users);
                    });

                if (!$request->has('fullch')) {
                    if ($parts = $request->userParts) {
                        $query->whereIn(
                            'user_activity.part_id',
                            is_array($parts) ? $parts : [$parts]
                        );
                    } else {
                        $query->whereIn('part_id', [361, 362, 363])
                            ->where(function ($query) {
                                $keyword = 'тб';

                                $query->where('title', 'like', "%$keyword%")
                                    ->orWhere('description', 'like', "%$keyword%")
                                    ->orWhereHas('timings', function ($query) use ($keyword) {
                                        $query->where('comment', 'like', "%$keyword%");
                                    });
                            });
                    }
                }
            });
        } elseif ($request->verified) {
            if ($request->from) {
                $items->where('users.created_at', '>=', $request->from);
            }

            if ($request->till) {
                $items->where('users.created_at', '<=', $request->till);
            }
        }

        if ($request->has('new')) {
            if ($request->vuln != 1) {
                $this->addDocumentsJoin($items, $request);

                $items->where(function ($query) use ($request) {
                    $query->whereHas('activities', function ($query) use ($request) {
                        $query->where('project_id', 6)
                            ->where(function ($query) {
                                $keyword = 'скрининг';

                                $query->where('title', 'like', "%$keyword%")
                                    ->orWhere('description', 'like', "%$keyword%")
                                    ->orWhereHas('timings', function ($query) use ($keyword) {
                                        $query->where('comment', 'like', "%$keyword%");
                                    });
                            });

                        if ($request->from) {
                            $query->where('start_date', '>=', $request->from);
                        }

                        if ($request->till) {
                            $query->where('start_date', '<=', $request->till);
                        }
                    })
                    ->orWhere(function ($query) use ($request) {
                        $query->whereJsonContains('d.data->6g6x', 'iNHa')
                            ->orWhereJsonContains('d.data->6g6x', 'aSu3');

                        if ($request->from) {
                            $query->where('d.data->kB3w', '>=', $request->from);
                        }

                        if ($request->till) {
                            $query->where('d.data->kB3w', '<=', $request->till);
                        }
                    });
                });
            } else {
                $this->addDocumentsJoin($items, $request);

                $items->where(function ($query) use ($request) {
                    $query->where(function ($query) use ($request) {
                        $query->where('d.data->2vq3', 'krnX')
                            ->whereNotNull('d.data->d6XS')
                            ->where('d.data->d6XS', '!=', 'vNSz')
                            ->where('d.data->d6XS', '!=', 'MPCj')
                            ->whereNotNull('d.data->it7x');

                        if ($request->from) {
                            $query->where('d.data->it7x', '>=', $request->from);
                        }

                        if ($request->till) {
                            $query->where('d.data->it7x', '<=', $request->till);
                        }
                    })
                    ->orWhere(function ($query) use ($request) {
                        $query->where('d.data->jnoa', true)
                            ->whereNotNull('d.data->eNaZ')
                            ->where('d.data->eNaZ', '!=', 'muv9')
                            ->where('d.data->eNaZ', '!=', 'EohW')
                            ->whereNotNull('d.data->mynW');

                        if ($request->from) {
                            $query->where('d.data->mynW', '>=', $request->from);
                        }

                        if ($request->till) {
                            $query->where('d.data->mynW', '<=', $request->till);
                        }
                    })
                    ->orWhere(function ($query) use ($request) {
                        $query->whereNull('d.data->it7x')
                            ->whereNull('d.data->mynW');

                        if ($request->from) {
                            $query->where('d.data->kB3w', '>=', $request->from);
                        }

                        if ($request->till) {
                            $query->where('d.data->kB3w', '<=', $request->till);
                        }
                    });
                });
            }
        }

        if ($request->has('mdr')) {
            $this->addDocumentsJoin($items, $request);

            if ($request->mdr == -1) {
                $items->whereJsonDoesntContain('d.data->d6XS', 'gBZJ')
                    ->whereJsonDoesntContain('d.data->d6XS', 'BHpL')
                    ->whereJsonDoesntContain('d.data->d6XS', 'TrxW')
                    ->whereJsonDoesntContain('d.data->d6XS', 'aKnw')
                    ->whereJsonDoesntContain('d.data->eNaZ', '3boi')
                    ->whereJsonDoesntContain('d.data->eNaZ', 'wwHt')
                    ->whereJsonDoesntContain('d.data->eNaZ', 'qXTv')
                    ->whereJsonDoesntContain('d.data->eNaZ', 'uK7c')
                    ->whereJsonDoesntContain('d.data->rjWN', 'AyZG')
                    ->whereJsonDoesntContain('d.data->rjWN', 'CcBo')
                    ->whereJsonDoesntContain('d.data->rjWN', '2EY9')
                    ->whereJsonDoesntContain('d.data->rjWN', 'WQoj');
            } else {
                $items->where(function ($query) {
                    $query->whereJsonContains('d.data->d6XS', 'gBZJ')
                        ->orWhereJsonContains('d.data->d6XS', 'BHpL')
                        ->orWhereJsonContains('d.data->d6XS', 'TrxW')
                        ->orWhereJsonContains('d.data->d6XS', 'aKnw')
                        ->orWhereJsonContains('d.data->eNaZ', '3boi')
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

        if ($request->fullch == 1) {
            $this->addDocumentsJoin($items, $request);

            $items->whereJsonDoesntContain('d.data->6g6x', 'ab8e')
                ->whereJsonContains('d.data->6g6x', 'iNHa')
                ->whereNotNull('d.data->kB3w')
                ->whereNotNull('d.data->GWQS')
                ->leftJoin('user_user', 'user_user.user_id', '=', 'users.id')
                ->where(function ($query) use ($request) {
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

                    $this->addDocumentsJoin($query, $request);
                }, '>=', 2)
                ->where(function ($query) use ($request) {
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

                    $this->addDocumentsJoin($query, $request);
                }, '>=', 1)
                ->where(function ($query) use ($request) {
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

                    $this->addDocumentsJoin($query, $request);
                }, '>=', 3);
        }

        if ($request->fullch > 1) {
            $query = clone $items;

            $this->addDocumentsJoin($query, $request);

            $query->whereJsonDoesntContain('d.data->6g6x', 'ab8e')
                ->whereJsonContains('d.data->6g6x', 'iNHa')
                ->whereNotNull('d.data->kB3w')
                ->with([
                    'activities' => function ($query) {
                        $keyword = 'шоу';

                        $query->where('project_id', 6)
                            ->whereIn('user_activity.part_id', [364, 365, 368, 369, 373])
                            ->where(function ($query) use ($keyword) {
                                $query->whereNull('title')
                                    ->orWhere('title', 'not like', "%$keyword%");
                            })
                            ->where(function ($query) use ($keyword) {
                                $query->whereNull('description')
                                    ->orWhere('description', 'not like', "%$keyword%");
                            })
                            ->whereDoesntHave('timings', function ($query) use ($keyword) {
                                $query->where('comment', 'like', "%$keyword%");
                            });
                    },
                    'activities2' => function ($query) {
                        $query->where('project_id', 6)
                            ->whereIn('user_activity.part_id', [367, 371, 375, 376]);
                    },
                    'relatedUsers.activities' => function ($query) {
                        $keyword = 'шоу';

                        $query->where('project_id', 6)
                            ->whereIn('user_activity.part_id', [364, 365, 368, 369, 373])
                            ->where(function ($query) use ($keyword) {
                                $query->whereNull('title')
                                    ->orWhere('title', 'not like', "%$keyword%");
                            })
                            ->where(function ($query) use ($keyword) {
                                $query->whereNull('description')
                                    ->orWhere('description', 'not like', "%$keyword%");
                            })
                            ->whereDoesntHave('timings', function ($query) use ($keyword) {
                                $query->where('comment', 'like', "%$keyword%");
                            });
                    },
                    'relatedUsers.activities2' => function ($query) {
                        $query->where('project_id', 6)
                            ->whereIn('user_activity.part_id', [367, 371, 375, 376]);
                    },
                    'activities3' => function ($query) use ($request) {
                        $query->where('project_id', 6)
                            ->whereIn('user_activity.part_id', [364, 365, 368, 369, 373]);
                    },
                    'activities4' => function ($query) use ($request) {
                        $query->where('project_id', 6)
                            ->whereIn('user_activity.part_id', [367, 371, 375, 376]);
                    },
                    'relatedUsers.activities3' => function ($query) use ($request) {
                        $query->where('project_id', 6)
                            ->whereIn('user_activity.part_id', [364, 365, 368, 369, 373]);
                    },
                    'relatedUsers.activities4' => function ($query) use ($request) {
                        $query->where('project_id', 6)
                            ->whereIn('user_activity.part_id', [367, 371, 375, 376]);
                    },
                    'activities5' => function ($query) use ($request) {
                        $query->where('project_id', 6)
                            ->whereIn('user_activity.part_id', [366, 370, 374]);
                    },
                    'activities6' => function ($query) use ($request) {
                        $query->where('project_id', 6)
                            ->whereIn('user_activity.part_id', [364, 368, 372])
                            ->whereHas('allUsers.projectUser', function ($query) {
                                $query->where('position', 'like', '%тб%');
                            });
                    },
                    'activities7' => function ($query) use ($request) {
                        $keyword = 'шоу';

                        $query->where('project_id', 6)
                            ->whereIn(
                                'user_activity.part_id',
                                [365, 369, 373, 474, 363, 362, 361, 543, 377, 379, 528, 527, 364, 368, 372]
                            )
                            ->where(function ($query) use ($keyword) {
                                $query->whereNull('title')
                                    ->orWhere('title', 'not like', "%$keyword%");
                            })
                            ->where(function ($query) use ($keyword) {
                                $query->whereNull('description')
                                    ->orWhere('description', 'not like', "%$keyword%");
                            })
                            ->whereDoesntHave('timings', function ($query) use ($keyword) {
                                $query->where('comment', 'like', "%$keyword%");
                            })
                            ->whereHas('allUsers.projectUser', function ($query) {
                                $query->where('position', 'not like', '%тб%');
                            });
                    }
                ]);

            if (!$request->till || $request->till > '2022-09-30') {
                if (in_array($request->fullch, [2, 3, 4, 5, 51, 52, 53, 54, 6, 61, 62, 63, 64, 13, 14, 15])) {
                    $query->where('d.data->Rchw', true);
                }
            } else {
                $query->whereNotNull('d.data->g459');
            }

            if ($request->till) {
                $query->where('d.data->kB3w', '<=', $request->till);
            }

            $ids = [];

            foreach ($query->get() as $item) {
                if (empty($item->profile[6][0]->data['kB3w'])) {
                    continue;
                }

                $from = $request->from ? Carbon::parse($request->from) : null;
                $inProjectFrom = Carbon::parse($item->profile[6][0]->data['kB3w']);
                $treatmentStart = empty($item->profile[6][0]->data['Q5xs']) ? null :
                    Carbon::parse($item->profile[6][0]->data['Q5xs']);
                $serviceStart = (!$request->till || $request->till > '2022-09-30') &&
                    $treatmentStart && $treatmentStart > $inProjectFrom ? $treatmentStart : $inProjectFrom;

                $start = $from && $from > $serviceStart ? $from : $serviceStart;

                $till = !$request->till ? now() : Carbon::parse($request->till);
                $treatmentEnd = !empty($item->profile[6][0]->data['nt6j'])
                    ? Carbon::parse($item->profile[6][0]->data['nt6j']) : null;

                $end = !$treatmentEnd || $treatmentEnd > $till ? $till : $treatmentEnd;

                if ($start->day > 24) {
                    $start->ceilMonth();
                } else {
                    $start->floorMonth();
                }

                if ($end->day < 6) {
                    $end->floorMonth();
                } else {
                    $end->ceilMonth();
                }

                $months = (int) $start->floatDiffInMonths($end);

                if (!$request->till || $request->till > '2022-06-30') {
                    $end->subSecond();
                }

                $soc = $item->activities
                    ->concat($item->relatedUsers->pluck('activities')->flatten())
                    ->filter(function ($activity) use ($item, $start, $end) {
                        return $start <= Carbon::parse($activity->start_date) &&
                            $end >= Carbon::parse($activity->start_date);
                    })
                    ->countBy(function ($item) {
                        return date('Ym', strtotime($item->start_date));
                    });

                $psy = $item->activities2
                    ->concat($item->relatedUsers->pluck('activities2')->flatten())
                    ->filter(function ($activity) use ($item, $start, $end) {
                        return $start <= Carbon::parse($activity->start_date) &&
                            $end >= Carbon::parse($activity->start_date);
                    })
                    ->countBy(function ($item) {
                        return date('Ym', strtotime($item->start_date));
                    });

                if ($request->fullch == 4) {
                    if (!empty($item->profile[6][0]->data['GWQS'])
                        && (!$request->till || $request->till >= $item->profile[6][0]->data['GWQS'])
                    ) {
                        $end2 = Carbon::parse($item->profile[6][0]->data['GWQS']);

                        if ($end2->day < 6) {
                            $end2->floorMonth();
                        } else {
                            $end2->ceilMonth();
                        }

                        $months2 = (int) $start->floatDiffInMonths($end2);

                        if (!$request->till || $request->till > '2022-06-30') {
                            $end2->subSecond();
                        }

                        $soc2 = $item->activities
                            ->concat($item->relatedUsers->pluck('activities')->flatten())
                            ->filter(function ($activity) use ($item, $start, $end2) {
                                return $start <= Carbon::parse($activity->start_date) &&
                                    $end2 >= Carbon::parse($activity->start_date);
                            })
                            ->countBy(function ($item) {
                                return date('Ym', strtotime($item->start_date));
                            });

                        $psy2 = $item->activities2
                            ->concat($item->relatedUsers->pluck('activities2')->flatten())
                            ->filter(function ($activity) use ($item, $start, $end2) {
                                return $start <= Carbon::parse($activity->start_date) &&
                                    $end2 >= Carbon::parse($activity->start_date);
                            })
                            ->countBy(function ($item) {
                                return date('Ym', strtotime($item->start_date));
                            });

                        if (count($soc2) && count($psy2) && count($soc2) >= $months2 && count($psy2) >= $months2) {
                            $ids[] = $item->id;
                        }
                    }
                } elseif ($request->fullch < 4) {
                    if (count($soc) && count($psy) && count($soc) >= $months && count($psy) >= $months) {
                        if ($request->fullch == 2 || !empty($item->profile[6][0]->data['nt6j'])) {
                            $ids[] = $item->id;
                        }
                    }
                } elseif (in_array($request->fullch, [11, 13, 18, 19, 20, 21])) {
                    if (!empty($item->profile[6][0]->data['8Fg3'])
                        && (!$request->till || $request->till >= $item->profile[6][0]->data['8Fg3'])
                        && (!$request->from || empty($item->profile[6][0]->data['nt6j'])
                            || $request->from <= $item->profile[6][0]->data['nt6j'])
                    ) {
                        $start3 = $request->from && $request->from > $item->profile[6][0]->data['8Fg3']
                                    ? $request->from : $item->profile[6][0]->data['8Fg3'];
                        $end3 = $request->till && (
                                empty($item->profile[6][0]->data['nt6j'])
                                || $request->till < $item->profile[6][0]->data['nt6j']
                            ) ? $request->till : ($item->profile[6][0]->data['nt6j'] ?? null);

                        $psy3 = $item->activities2
                            ->concat($item->relatedUsers->pluck('activities2')->flatten())
                            ->filter(function ($activity) use ($start3, $end3) {
                                return $activity->start_date >= $start3 && $activity->start_date <= $end3;
                            })
                            ->countBy(function ($item) {
                                return date('Ym', strtotime($item->start_date));
                            });

                        $soc3 = $item->activities
                            ->concat($item->relatedUsers->pluck('activities')->flatten())
                            ->filter(function ($activity) use ($start3, $end3) {
                                return $activity->start_date >= $start3 && $activity->start_date <= $end3;
                            })
                            ->countBy(function ($item) {
                                return date('Ym', strtotime($item->start_date));
                            });

                        $soc6 = $item->activities7
                            ->concat($item->relatedUsers->pluck('activities7')->flatten())
                            ->filter(function ($activity) use ($start3, $end3) {
                                return $activity->start_date >= $start3 && $activity->start_date <= $end3;
                            })
                            ->countBy(function ($item) {
                                return date('Ym', strtotime($item->start_date));
                            });

                        $leg3 = $item->activities5
                            ->filter(function ($activity) use ($start3, $end3) {
                                return $activity->start_date >= $start3 && $activity->start_date <= $end3;
                            });

                        $phthi3 = $item->activities6
                            ->filter(function ($activity) use ($start3, $end3) {
                                return $activity->start_date >= $start3 && $activity->start_date <= $end3;
                            });

                        if (count($soc3) || count($psy3)) {
                            if ($request->fullch == 11) {
                                $ids[] = $item->id;
                            }

                            if ($request->fullch == 13) {
                                $start3 = Carbon::parse($start3);
                                $end3 = Carbon::parse($end3);

                                if ($start3->day > 24) {
                                    $start3->ceilMonth();
                                } else {
                                    $start3->floorMonth();
                                }

                                if ($end3->day < 6) {
                                    $end3->floorMonth();
                                } else {
                                    $end3->ceilMonth();
                                }

                                $months3 = (int) $start3->floatDiffInMonths($end3);

                                if (count($soc3) >= $months3 && count($psy3) >= $months3) {
                                    $ids[] = $item->id;
                                }
                            }
                        }

                        if (count($soc6) && $request->fullch == 18) {
                            $ids[] = $item->id;
                        }

                        if (count($psy3) && $request->fullch == 19) {
                            $ids[] = $item->id;
                        }

                        if (count($leg3) && $request->fullch == 20) {
                            $ids[] = $item->id;
                        }

                        if (count($phthi3) && $request->fullch == 21) {
                            $ids[] = $item->id;
                        }
                    }
                } elseif (in_array($request->fullch, [12, 14, 15, 16, 17, 22, 23])) {
                    if ((($request->till && $request->till <= '2022-09-30')
                            || !empty($item->profile[6][0]->data['g459']))
                        && (!$request->till || $request->till >= $item->profile[6][0]->data['kB3w'])
                        && (!$request->from || empty($item->profile[6][0]->data['GWQS'])
                            || $request->from <= $item->profile[6][0]->data['GWQS'])
                    ) {
                        $start4 = $request->from && $request->from > $item->profile[6][0]->data['kB3w']
                                    ? $request->from : $item->profile[6][0]->data['kB3w'];
                        $end4 = $request->till && (
                                empty($item->profile[6][0]->data['GWQS'])
                                || $request->till < $item->profile[6][0]->data['GWQS']
                            ) ? $request->till : ($item->profile[6][0]->data['GWQS'] ?? null);

                        $psy4 = $item->activities4
                            ->concat($item->relatedUsers->pluck('activities4')->flatten())
                            ->filter(function ($activity) use ($start4, $end4) {
                                return $activity->start_date >= $start4 && $activity->start_date <= $end4;
                            })
                            ->countBy(function ($item) {
                                return date('Ym', strtotime($item->start_date));
                            });

                        $soc4 = $item->activities3
                            ->concat($item->relatedUsers->pluck('activities3')->flatten())
                            ->filter(function ($activity) use ($start4, $end4) {
                                return $activity->start_date >= $start4 && $activity->start_date <= $end4;
                            })
                            ->countBy(function ($item) {
                                return date('Ym', strtotime($item->start_date));
                            });

                        $soc5 = $item->activities7
                            ->concat($item->relatedUsers->pluck('activities7')->flatten())
                            ->filter(function ($activity) use ($start4, $end4) {
                                return $activity->start_date >= $start4 && $activity->start_date <= $end4;
                            })
                            ->countBy(function ($item) {
                                return date('Ym', strtotime($item->start_date));
                            });

                        $leg4 = $item->activities5
                            ->filter(function ($activity) use ($start4, $end4) {
                                return $activity->start_date >= $start4 && $activity->start_date <= $end4;
                            });

                        $phthi4 = $item->activities6
                            ->filter(function ($activity) use ($start4, $end4) {
                                return $activity->start_date >= $start4 && $activity->start_date <= $end4;
                            });

                        if (count($soc4) || count($psy4)) {
                            if ($request->fullch == 12) {
                                $ids[] = $item->id;
                            } elseif ($request->fullch == 14) {
                                $start4 = Carbon::parse($start4);
                                $end4 = Carbon::parse($end4);

                                if ($start4->day > 24) {
                                    $start4->ceilMonth();
                                } else {
                                    $start4->floorMonth();
                                }

                                if ($end4->day < 6) {
                                    $end4->floorMonth();
                                } else {
                                    $end4->ceilMonth();
                                }

                                $months4 = (int) $start4->floatDiffInMonths($end4);

                                if (count($soc4) >= $months4 && count($psy4) >= $months4) {
                                    $ids[] = $item->id;
                                }
                            } elseif ($request->fullch == 15) {
                                if ($soc4->intersectByKeys($psy4)->count()) {
                                    $ids[] = $item->id;
                                }
                            } elseif ($request->fullch == 17) {
                                if (count($psy4)) {
                                    $ids[] = $item->id;
                                }
                            }
                        }

                        if (count($soc5) && $request->fullch == 16) {
                            $ids[] = $item->id;
                        }

                        if (count($leg4) && $request->fullch == 22) {
                            $ids[] = $item->id;
                        }

                        if (count($phthi4) && $request->fullch == 23) {
                            $ids[] = $item->id;
                        }
                    }
                } else {
                    $now = $request->till ? Carbon::parse($request->till) : now();
                    $now->floorDay();

                    if (empty($item->profile[6][0]->data['nt6j']) ||
                        ($now <= Carbon::parse($item->profile[6][0]->data['nt6j']) &&
                            $now >= Carbon::parse($item->profile[6][0]->data['kB3w']))
                    ) {
                        $now = $now->format('Ym');

                        if ((($request->till && $request->till <= '2022-09-30')
                                || !empty($item->profile[6][0]->data['g459']))
                            && empty($item->profile[6][0]->data['GWQS'])
                        ) {
                            if ($request->fullch == 9) {
                                $ids[] = $item->id;
                            }

                            if (empty($soc[$now])
                                && ($request->fullch == 5 || (!empty($item->profile[6][0]->data['8Src']) && (
                                ($request->fullch == 51 && $item->profile[6][0]->data['8Src'] == 'u2Ea')
                                    || ($request->fullch == 52 && $item->profile[6][0]->data['8Src'] == '6KMj')
                                    || ($request->fullch == 53 && $item->profile[6][0]->data['8Src'] == '98GN')
                                    || ($request->fullch == 54 && $item->profile[6][0]->data['8Src'] == 'nRns')
                                )))
                            ) {
                                $ids[] = $item->id;
                            }

                            if (empty($psy[$now])
                                && ($request->fullch == 6 || (!empty($item->profile[6][0]->data['8Src']) && (
                                ($request->fullch == 61 && $item->profile[6][0]->data['8Src'] == 'u2Ea')
                                    || ($request->fullch == 62 && $item->profile[6][0]->data['8Src'] == '6KMj')
                                    || ($request->fullch == 63 && $item->profile[6][0]->data['8Src'] == '98GN')
                                    || ($request->fullch == 64 && $item->profile[6][0]->data['8Src'] == 'nRns')
                                )))
                            ) {
                                $ids[] = $item->id;
                            }
                        }

                        if (!empty($item->profile[6][0]->data['8Fg3'])) {
                            if ($request->fullch == 10) {
                                $ids[] = $item->id;
                            }

                            if (!empty($item->profile[6][0]->data['Rchw'])) {
                                if (empty($soc[$now]) && $request->fullch == 7) {
                                    $ids[] = $item->id;
                                }

                                if (empty($psy[$now]) && $request->fullch == 8) {
                                    $ids[] = $item->id;
                                }
                            }
                        }
                    }
                }
            }

            $items->whereIn('users.id', $ids);
        }

        if ($request->location) {
            $items->whereHas('projectsUsers', function ($query) use ($request) {
                $query->where('location_id', $request->location);
            });
        } elseif ($request->locations) {
            $items->whereHas('projectsUsers', function ($query) use ($request) {
                $query->whereIn('location_id', $request->locations);
            });
        } elseif (!in_array('superuser', $request->user()->roles)) {
            $projectsUsersQuery = $request->user()->projectsUsers()
                ->groupBy('project_id')
                ->groupBy('location_id');

            if ($request->project) {
                $projectsUsersQuery->where('project_id', $request->project);
            }

            $projectsUsers = $projectsUsersQuery->get()->groupBy('project_id');

            $items->whereHas('projectsUsers', function ($query) use ($projectsUsers) {
                $query->where(function ($query) use ($projectsUsers) {
                    foreach ($projectsUsers as $projectId => $locations) {
                        $query->orWhere(function ($query) use ($projectId, $locations) {
                            $query->where('project_id', $projectId);

                            $locations = $locations->pluck('location_id');

                            if (!$locations->contains(1)) {
                                $locations->push(1);
                                $query->whereIn('location_id', $locations);
                            }
                        });
                    }
                });
            });
        }

        if ($request->role == 'client') {
            $items->with(['screenings' => function ($query) use ($request) {
                if ($request->project) {
                    $query->where('project_id', $request->project);
                }
            }]);
        }

        if (!$request->searchActivities &&
            !$request->profileDate &&
            !$request->dateFilter &&
            ($request->from || $request->till) &&
            !$request->has('partner') &&
            !$request->has('parts') &&
            !$request->has('fullch') &&
            !$request->has('fullvuln') &&
            !$request->has('verified') &&
            !$request->has('remained') &&
            !$request->has('outcomes') &&
            !$request->has('hasreference')
        ) {
            $items->whereHas('screenings', function ($query) use ($request) {
                if ($request->from) {
                    $query->where('start_date', '>=', $request->from);
                }

                if ($request->till) {
                    $query->where('start_date', '<=', $request->till);
                }
            });
        }

        if ($project && $request->input('itemsPerPage') != -1) {
            $items->with('location', function ($query) use ($project) {
                $query->where('project_user.project_id', $project);
            });
        }

        if ($request->ids) {
            $items->whereIn('users.id', $request->ids);
        }

        if ($request->partner) {
            $this->addDocumentsJoin($items, $request);

            $items->whereNotNull('d.data->Y4N4');
        }

        if ($request->started) {
            $this->addDocumentsJoin($items, $request);

            $items->where(function ($query) {
                $query->where('d.data->wrKd', true)
                    ->orWhere(function ($query) {
                        $query->whereNotNull('d.data->eNaZ')
                            ->where('d.data->eNaZ', '!=', 'muv9')
                            ->where('d.data->eNaZ', '!=', 'EohW')
                            ->whereNotNull('d.data->roMc')
                            ->where('d.data->roMc', 'XiCo');
                    })
                    ->orWhere(function ($query) {
                        $query->whereNotNull('d.data->d6XS')
                            ->where('d.data->d6XS', '!=', 'vNSz')
                            ->where('d.data->d6XS', '!=', 'MPCj')
                            ->whereNotNull('d.data->Xnsu')
                            ->where('d.data->Xnsu', 'LbKw');
                    });
            });
        }

        if ($request->remained) {
            $this->addDocumentsJoin($items, $request);

            $items->whereNotNull('d.data->kB3w');

            if ($request->till) {
                $items->where('d.data->kB3w', '<=', $request->till);
            }

            $ids = [];

            foreach ($items->get() as $client) {
                if ((!empty($client->profile['11']) && !empty($client->profile['11'][0]->data['TNF5']))
                    || (!empty($client->profile[52]) && !empty($client->profile[52][0]->data['TNF5']))
                ) {
                    $latestDate = null;
                    $latestOutcome = null;
                    $latestCause = null;
                    $latestDeathCause = null;

                    $outcomes = 0;

                    $forms = $client->profile['11'] ?? collect();

                    if (!empty($client->profile[52])) {
                        $forms = $forms->concat($client->profile[52])->sortByDesc('data.TNF5');
                    }

                    foreach ($forms as $form) {
                        if (!empty($form->data['TNF5'])
                            && (!$request->till || $request->till >= $form->data['TNF5'])
                            && (!$request->from || $request->from <= $form->data['TNF5'])
                        ) {
                            $outcomes++;

                            if ($form->data['TNF5'] > $latestDate) {
                                $latestDate = $form->data['TNF5'];
                                $latestOutcome = $form->data['JbaL'];
                                $latestCause = $form->data['XcEm'] ?? ($form->data['jBQK'] ?? null);
                                $latestDeathCause = $form->data['asmx'] ?? null;
                            }
                        }
                    }

                    $initiated = $client->profile['6'][0]->data['kB3w'];

                    if (empty($client->profile['6'][0]->data['2CLW'])
                        || $client->profile['6'][0]->data['2CLW'] != 'wx6s'
                    ) {
                        if (!empty($client->profile['6'][0]->data['d6XS'])
                            && $client->profile['6'][0]->data['d6XS'] != 'vNSz'
                            && $client->profile['6'][0]->data['d6XS'] != 'MPCj'
                            && !empty($client->profile['6'][0]->data['it7x'])
                            && $client->profile['6'][0]->data['it7x'] > $initiated
                        ) {
                            $initiated = $client->profile['6'][0]->data['it7x'];
                        } elseif (!empty($client->profile['6'][0]->data['eNaZ'])
                            && $client->profile['6'][0]->data['eNaZ'] != 'muv9'
                            && $client->profile['6'][0]->data['eNaZ'] != 'EohW'
                            && !empty($client->profile['6'][0]->data['mynW'])
                            && $client->profile['6'][0]->data['mynW'] > $initiated
                        ) {
                            $initiated = $client->profile['6'][0]->data['mynW'];
                        }
                    }

                    if (!empty($client->profile['6'][0]->data['RB67'])
                        && $client->profile['6'][0]->data['RB67'] > $initiated
                    ) {
                        $initiated = $client->profile['6'][0]->data['RB67'];
                    }

                    if (($latestOutcome == 'meHa' && ($latestCause == 'Lwch' || $latestCause == 'KyEj'))
                        || (!$outcomes
                            && (($request->from && $initiated < $request->from)
                                || ($request->till && $initiated > $request->till)))
                    ) {
                        continue;
                    }

                    if ($request->remained == 1) {
                        $ids[] = $client->id;
                    }

                    if ($request->remained == 2) {
                        $from = $request->from ? Carbon::parse($request->from) : null;
                        $inProjectFrom = Carbon::parse($initiated);
                        $start = $from && $from > $inProjectFrom ? $from : $inProjectFrom;

                        $till = !$request->till ? now() : Carbon::parse($request->till);
                        $latest = Carbon::parse($latestDate);

                        $end = !$latest || $latest > $till ? $till : $latest;

                        $start->floorMonth();
                        $end->floorMonth();

                        $months = (int) $start->floatDiffInMonths($end);

                        if (($latestOutcome == 'nXDJ' ||
                            ($latestOutcome == 'meHa' &&
                                ($latestCause == 'Ze52' ||
                                    ($latestCause == 'BtcT' && $latestDeathCause == 'S4s5'))) ||
                            ($latestOutcome == 'A4Z9' && $latestCause) ||
                            (($latestOutcome == 'bJjG' || $latestOutcome == 'PnKg') && substr($latestDate, 0, 7) >=
                                (new Carbon($request->till ?: 'last month'))->format('Y-m'))) &&
                            $outcomes >= $months
                        ) {
                            $ids[] = $client->id;
                        }
                    }
                }
            }

            $items->whereIn('users.id', $ids);
        }

        if ($request->outcomes) {
            $this->addDocumentsJoin($items, $request);

            $items->whereNotNull('d.data->kB3w');

            if ($request->till) {
                $items->where('d.data->kB3w', '<=', $request->till);
            }

            $ids = [];

            foreach ($items->get() as $client) {
                if (!empty($client->profile['11']) && !empty($client->profile['11'][0]->data['TNF5'])
                    || (!empty($client->profile[52]) && !empty($client->profile[52][0]->data['TNF5']))
                ) {
                    $latestDate = null;
                    $latestOutcome = null;
                    $latestReason = null;
                    $latestCause = null;

                    $forms = $client->profile['11'] ?? collect();

                    if (!empty($client->profile[52])) {
                        $forms = $forms->concat($client->profile[52])->sortByDesc('data.TNF5');
                    }

                    foreach ($forms as $form) {
                        if (!empty($form->data['TNF5']) && $form->data['TNF5'] > $latestDate
                            && (!$request->till || $request->till >= $form->data['TNF5'])
                            && (!$request->from || $request->from <= $form->data['TNF5'])
                        ) {
                            $latestDate = $form->data['TNF5'];
                            $latestOutcome = $form->data['JbaL'];
                            $latestReason = $form->data['XcEm'] ?? ($form->data['jBQK'] ?? null);
                            $latestCause = $form->data['asmx'] ?? null;
                        }
                    }

                    if (!$latestDate) {
                        continue;
                    }

                    if ($request->outcomes == 1 || $request->outcomes == -1) {
                        $ids[] = $client->id;
                    } else {
                        if ($latestOutcome == 'A4Z9') {
                            if ($request->outcomes == 'nXDJ' && $latestReason) {
                                $ids[] = $client->id;
                            }
                        } elseif ((is_array($request->outcomes)
                            ? in_array($latestOutcome, $request->outcomes)
                            : $latestOutcome == $request->outcomes
                        )
                            && (!$request->reason || $request->reason == $latestReason)
                            && (!$request->cause || $request->cause == $latestCause)
                        ) {
                            $ids[] = $client->id;
                        }
                    }
                }
            }

            if ($request->outcomes == -1) {
                $items->whereNotIn('users.id', $ids);
            } else {
                $items->whereIn('users.id', $ids);
            }
        }

        if ($request->fullvuln) {
            $query = clone $items;

            $query->with([
                'activities' => function ($query) use ($request) {
                    $query->where('project_id', $request->project);

                    if ($request->from) {
                        $query->where('start_date', '>=', $request->from);
                    }

                    if ($request->till) {
                        $query->where('start_date', '<=', $request->till);
                    }
                }
            ]);

            if ($request->till) {
                $this->addDocumentsJoin($items, $request);

                $query->where('d.data->kB3w', '<=', $request->till);
            }

            $ids = [];

            foreach ($query->get() as $item) {
                $start = $request->from ?: (empty($item->profile[6][0]->data['kB3w']) ?
                    $item->created_at->format('Y-m-d') : $item->profile[6][0]->data['kB3w']);
                $end = $request->till ?: (empty($item->profile[6][0]->data['nt6j']) ?
                    now()->format('Y-m-d') : $item->profile[6][0]->data['nt6j']);

                $activites = $item->activities
                    ->filter(function ($activity) use ($start, $end) {
                        return $start <= $activity->start_date && $end >= $activity->start_date;
                    })
                    ->countBy(function ($item) {
                        return date('Ym', strtotime($item->start_date));
                    });

                $start = Carbon::parse($start);
                $end = Carbon::parse($end);

                if ($start->day > 24) {
                    $start->ceilMonth();
                } else {
                    $start->floorMonth();
                }

                if ($end->day < 6) {
                    $end->floorMonth();
                } else {
                    $end->ceilMonth();
                }

                $months = (int) $start->floatDiffInMonths($end);

                if ($request->fullvuln == 1 && count($activites) && count($activites) >= $months) {
                    $ids[] = $item->id;
                }

                if ($request->fullvuln == 2) {
                    $latestDate = null;
                    $latestOutcome = null;

                    if (!empty($item->profile['11']) && !empty($item->profile['11'][0]->data['TNF5'])
                        || (!empty($item->profile[52]) && !empty($item->profile[52][0]->data['TNF5']))
                    ) {
                        $forms = $item->profile['11'] ?? collect();

                        if (!empty($item->profile[52])) {
                            $forms = $forms->concat($item->profile[52])->sortByDesc('data.TNF5');
                        }

                        foreach ($forms as $form) {
                            if (!empty($form->data['TNF5'])
                                && (!$request->till || $request->till >= $form->data['TNF5'])
                                && (!$request->from || $request->from >= $form->data['TNF5'])
                            ) {
                                if ($form->data['TNF5'] > $latestDate) {
                                    $latestDate = $form->data['TNF5'];
                                    $latestOutcome = $form->data['JbaL'];
                                }
                            }
                        }
                    }

                    if (!$latestOutcome || $latestOutcome == 'bJjG' || $latestOutcome == 'PnKg') {
                        $ids[] = $item->id;
                    }
                }
            }

            $items->whereIn('users.id', $ids);
        }

        if ($request->hasreference) {
            $this->addDocumentsJoin($items, $request, true, 52);

            if ($request->from) {
                $items->where('d52.data->TNF5', '>=', $request->from);
            }

            if ($request->till) {
                $items->where('d52.data->TNF5', '<=', $request->till);
            }

            $items->whereNotNull("d{$request->hasreference}.id");
        }

        if ($request->tbi) {
            $items->where(function ($query) {
                $query->where('d.data->rjWN', 'LBj4')
                    ->orWhere('d.data->eNaZ', 'ELWf')
                    ->orWhere('d.data->d6XS', 'ZkJZ')
                    ->orWhere('d.data->byXk', 'uw7C');
            });
        }

        if ($request->outreach) {
            ETBUIndicatorsComputer3::addOutreachClause($items, $request, $request->parts);
        }

        foreach ($request->input('sortBy', ['users.name']) as $index => $order) {
            $items->orderBy($order, isset($request->input('sortDesc', ['false'])[$index]) &&
                $request->input('sortDesc', ['false'])[$index] != 'false' ? 'desc' : 'asc');
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

        if ($per_page == -1) {
            $per_page = $items->count(DB::raw('distinct users.id'));
        } else {
            $items->with('relatedUsers');
        }

        $items->groupBy('users.id');

        $items->where('users.id', '!=', 6293);

        return $items->paginate($per_page);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreUser  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreUser $request)
    {
        $data = $request->validated();

        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        $data['name'] = implode(
            ' ',
            array_filter([
                $data['profile'][''][0]['data']['last_name'] ?? null,
                $data['profile'][''][0]['data']['first_name'] ?? null,
                $data['profile'][''][0]['data']['middle_name'] ?? null
            ])
        );

        $user = User::create($data);

        foreach ($data['profile'] as $form_id => $docs) {
            foreach ($docs as &$doc) {
                foreach ($doc['data'] ?? [] as $key => $value) {
                    $trimmedValue = is_string($value) ? trim($value) : $value;

                    if ($trimmedValue !== 0 && $trimmedValue !== '0' && empty($trimmedValue)) {
                        unset($doc['data'][$key]);
                    }
                }

                if (empty($doc['data'])) {
                    continue;
                }

                $document = new Document();
                $document->created_by = $request->user()->id;

                if ($form_id) {
                    $document->form_id = $form_id;
                }

                $document->approved_at = !empty($doc['approved_at']) ? Carbon::parse($doc['approved_at']) : now();
                $document->data = $doc['data'];
                $document->save();

                $document->original_id = $document->id;
                $document->save();

                if ($document->form_id && $document->form->projects->count()) {
                    $document->projects()->attach($document->form->projects[0]->id);
                }

                $document->users()->attach($user->id);
            }
        }

        if (!empty($data['roles']) && in_array('client', $data['roles'])) {
            foreach ($data['projects'] as $projectId) {
                $parts = Part::where('type', 0)
                    ->whereHas('projectUsers', function ($query) use ($projectId) {
                        $query->where('project_id', $projectId);
                    })
                    ->get();

                foreach ($parts as $part) {
                    $user->parts()->attach($part->id, ['project_id' => $projectId]);
                }
            }

            if (isset($data['location'])) {
                ProjectUser::where('user_id', $user->id)
                    ->update(['location_id' => $data['location']]);
            }
        }

        $related = $user->profile[6][0]->data['fiu4'] ?? [];
        $user->users()->sync($related);

        return ['id' => $user->id];
    }

    /**
     * Display the specified resource.
     *
     * @param  User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $user = User::where('id', $id)->with([
                'projects:id',
                'location:locations.id'
            ])
            ->first();

        if (in_array('employee', $user->roles)) {
            $user->with('currentActivity');
        }

        if (in_array('client', $user->roles)) {
            $user->load([
                'activities' => function ($query) use ($request) {
                    if (!in_array('superuser', $request->user()->roles)) {
                        $query->whereIn('project_id', $request->user()->projects->pluck('id'));
                    }

                    $query->orderBy('start_date', 'desc')
                        ->orderBy('start_time', 'desc');
                },
                'activities.allUsers',
                'activities.allUsers.part:id,description',
                'activities.allUsers.user:id,name',
                'activities.project:id,name',
                'activities.timings:id,activity_id,comment',
                'activities.frozenTiming',
            ]);
        }

        $user->append('profile');

        return $user;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateUser  $request
     * @param  User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateUser $request, User $user)
    {
        $data = $request->validated();

        if ($request->isMethod('put')) {
            if (!empty($data['password'])) {
                $data['password'] = bcrypt($data['password']);
            } else {
                unset($data['password']);
            }

            $files = [];

            foreach ($user->profile as $docs) {
                foreach ($docs as $doc) {
                    if (!empty($doc->data['images'])) {
                        $files[] = $doc->data['images'][0];
                    }
                }
            }

            foreach ($data['profile'] as $docs) {
                foreach ($docs as $doc) {
                    if (!empty($doc['data']['images']) && !isset($doc['data']['upload']) &&
                        ($i = array_search($doc['data']['images'][0], $files)) !== false
                    ) {
                        unset($files[$i]);
                    }
                }
            }

            foreach ($files as $file) {
                Storage::delete($file);
            }

            foreach ($data['profile'] as $form_id => $docs) {
                $form = $form_id ? Form::find($form_id) : null;

                foreach ($docs as &$doc) {
                    foreach ($doc['data'] ?? [] as $key => $value) {
                        $trimmedValue = is_string($value) ? trim($value) : $value;

                        if ($trimmedValue !== 0 && $trimmedValue !== '0' && empty($trimmedValue)) {
                            unset($doc['data'][$key]);
                        }
                    }

                    if (empty($doc['data']) && empty($doc['id'])) {
                        continue;
                    }

                    if (!empty($doc['id'])) {
                        $existingDocument = Document::find($doc['id']);

                        if (Util::arraysEqualRecursive($existingDocument->data, $doc['data'])
                            //&& $existingDocument->approved_at == $doc['approved_at']
                        ) {
                            continue;
                        }

                        $updateAllowanceMap = [
                            6 => [2449, 5],
                            11 => [2449, 5],
                            9 => [65],
                            10 => [65],
                            9 => [65]
                        ];

                        if (!isset($updateAllowanceMap[$form_id])
                            || in_array($request->user()->id, $updateAllowanceMap[$form_id])
                        ) {
                            if ($form && !empty($form->schema['multiple']) && !$existingDocument->frozen_at) {
                                $existingDocument->data = $doc['data'];
                                $existingDocument->approved_at = !empty($doc['approved_at'])
                                    ? Carbon::parse($doc['approved_at'], 0) : null;
                                $existingDocument->save();
                            } else {
                                $newDocument = new Document();
                                $newDocument->created_by = $request->user()->id;
                                $newDocument->original_id = $existingDocument->original_id;
                                $newDocument->approved_at = $existingDocument->approved_at;

                                if ($form_id) {
                                    $newDocument->form_id = $form_id;
                                }

                                $results = DocumentResults::compute($form_id, $doc['data']);

                                if ($results) {
                                    $doc['data']['results'] = $results;
                                }

                                $newDocument->data = $doc['data'];
                                $newDocument->save();

                                if ($newDocument->form_id && $newDocument->form->projects->count()) {
                                    $newDocument->projects()->attach($newDocument->form->projects[0]->id);
                                }

                                $newDocument->users()->attach($user->id);

                                if (!$form || empty($form->schema['multiple'])) {
                                    break;
                                }
                            }
                        }
                    } else {
                        $document = new Document();
                        $document->approved_at = !empty($doc['approved_at'])
                            ? Carbon::parse($doc['approved_at']) : now();
                        $document->created_by = $request->user()->id;

                        if ($form_id) {
                            $document->form_id = $form_id;
                        }

                        $results = DocumentResults::compute($form_id, $doc['data']);

                        if ($results) {
                            $doc['data']['results'] = $results;
                        }

                        $document->data = $doc['data'];
                        $document->save();

                        $document->original_id = $document->id;
                        $document->save();

                        if ($document->form_id && $document->form->projects->count()) {
                            $document->projects()->attach($document->form->projects[0]->id);
                        }

                        $document->users()->attach($user->id);
                    }
                }
            }

            $user->load('documents');

            $data['name'] = implode(
                ' ',
                array_filter([
                    $user->profile[null][0]->data['last_name'] ?? null,
                    $user->profile[null][0]->data['first_name'] ?? null,
                    $user->profile[null][0]->data['middle_name'] ?? null
                ])
            );

            $user->update($data);

            $related = $user->profile[6][0]->data['fiu4'] ?? [];
            $user->users()->sync($related);
        } else {
            $profile = $user->profile[null][0];
            $profileData = $profile->data;

            if (!empty($data['photo'])) {
                if (!empty($profileData['photo'])) {
                    Storage::delete($profileData['photo']);
                }

                $profileData['photo'] = $data['photo']->store("users/$user->id");
                $user->photo = $profileData['photo'];
                $user->save();

                if (!empty($profileData['originalPhoto'])) {
                    Storage::delete($profileData['originalPhoto']);
                }

                $profileData['originalPhoto'] = $data['originalPhoto']->store("users/$user->id");
            } elseif (isset($data['removePhoto'])) {
                if (!empty($profileData['photo'])) {
                    Storage::delete($profileData['photo']);
                    unset($profileData['photo']);
                    $user->photo = null;
                    $user->save();
                }

                if (!empty($profileData['originalPhoto'])) {
                    Storage::delete($profileData['originalPhoto']);
                    unset($profileData['originalPhoto']);
                }
            }

            $profile->data = $profileData;
            $profile->save();

            foreach ($user->profile as &$docs) {
                foreach ($docs as &$doc) {
                    $docData = $doc->data;

                    if (isset($docData['upload'])) {
                        if (!empty($data['images'][$docData['upload']])) {
                            $docData['images'] = [$data['images'][$docData['upload']]->store("users/$user->id/docs")];
                        }

                        unset($docData['upload']);
                    }

                    foreach ($docData as &$value) {
                        if (is_array($value)) {
                            if (isset($value['upload'])) {
                                if (!empty($data['files'][$value['upload']])) {
                                    $upload = $data['files'][$value['upload']];
                                    $value['file'] = $upload->storeAs("users/$user->id/docs", $upload->getClientOriginalName());
                                    unset($value['upload']);
                                }
                            } else {
                                foreach ($value as &$repeated) {
                                    if (isset($repeated['upload']) && !empty($data['files'][$repeated['upload']])) {
                                        $upload = $data['files'][$repeated['upload']];
                                        $repeated['file'] = $upload->storeAs("users/$user->id/docs", $upload->getClientOriginalName());
                                        unset($repeated['upload']);
                                    }
                                }
                            }
                        }
                    }

                    $doc->data = $docData;
                    $doc->save();
                }
            }
        }

        if (!empty($data['roles']) && in_array('client', $data['roles'])) {
            foreach ($data['projects'] as $projectId) {
                $parts = Part::where('type', 0)
                    ->whereHas('projectUsers', function ($query) use ($projectId) {
                        $query->where('project_id', $projectId);
                    })
                    ->whereDoesntHave('projectUsers', function ($query) use ($projectId, $user) {
                        $query->where('project_id', $projectId)
                            ->where('user_id', $user->id);
                    })
                    ->get();

                foreach ($parts as $part) {
                    $user->parts()->attach($part->id, ['project_id' => $projectId]);
                }

                Indicators::invalidate($projectId);
            }

            Indicators::compute();

            ProjectUser::where('user_id', $user->id)
                ->whereNotIn('project_id', $data['projects'])
                ->whereDoesntHave('userActivities')
                ->delete();

            if (isset($data['location'])) {
                ProjectUser::where('user_id', $user->id)
                    ->update(['location_id' => $data['location']]);
            }
        }

        $user->append('profile');

        return $user;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->delete();
    }

    public function download(Request $request)
    {
        $query = $this->getQuery($request);

        $query->groupBy('users.id');

        return Excel::download(new UsersExport($query), 'Клиенты ' . date('Y-m-d His') . '.xlsx');
    }

    public function downloadTraining(Request $request)
    {
        $query = $this->getQuery($request);

        $query->groupBy('users.id');

        return Excel::download(new TrainingExport($query, $request->project), 'Обучение ' . date('Y-m-d His') . '.xlsx');
    }

    public function downloadFactors()
    {
        return Excel::download(new FactorsExport(), 'Факторы ' . date('Y-m-d His') . '.xlsx');
    }

    public function merge(Request $request)
    {
        $destination = User::findOrFail($request->destination);

        foreach ($request->users as $userId) {
            $user = User::findOrFail($userId);

            UserMerger::mergeUser($user, $destination);
        }
    }

    public function clientStats(Request $request, User $user)
    {
        $stats = User::select(['id', 'name'])
            ->where('roles', 'not like', '%client%')
            ->whereHas('activities', function ($query) use ($user) {
                $query->where('project_id', 6)
                    ->whereHas('clients', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    });
            })
            ->withCount([
                'timings as timingsCount' => function ($query) use ($request, $user) {
                    $query->whereHas('activity', function ($query) use ($user) {
                        $query->where('project_id', 6)
                            ->whereHas('clients', function ($query) use ($user) {
                                $query->where('user_id', $user->id)
                                    ->where('part_id', '<>', 543);
                            });
                    });
                },
                'timings as timing' => function ($query) use ($request, $user) {
                    $query->select(DB::raw('SUM(timing)'))
                        ->whereHas('activity', function ($query) use ($user) {
                            $query->where('project_id', 6)
                                ->whereHas('clients', function ($query) use ($user) {
                                    $query->where('user_id', $user->id)
                                        ->where('part_id', '<>', 543);
                                });
                        });
                },
                'timings as timingOther' => function ($query) use ($request, $user) {
                    $query->select(DB::raw('SUM(timing)'))
                        ->whereHas('activity', function ($query) use ($user) {
                            $query->where('project_id', 6)
                                ->whereHas('clients', function ($query) use ($user) {
                                    $query->where('user_id', $user->id)
                                        ->where('part_id', 543);
                                });
                        });
                }
            ])
            ->get();

        $stats->push([
            'name' => '',
            'timingsCount' => $stats->sum('timingsCount'),
            'timing' => $stats->sum('timing'),
            'timingOther' => $stats->sum('timingOther'),
        ]);

        return $stats;
    }
}
