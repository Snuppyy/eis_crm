<?php

namespace App\Lib\Legacy\Indicators;

use DB;

use App\Models\Activity;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterval;

class ProjectIndicatorsComputer
{
    public $request;

    protected function addDocumentsJoin(
        $query,
        $form_id = 6,
        $count = false,
        $alias = false,
        $documentsSubqueryClause = null
    ) {
        static::addDocumentsJoinWithRequest($query, $this->request, $form_id, $count, $alias, $documentsSubqueryClause);
    }

    public static function addDocumentsJoinWithRequest(
        $query,
        $request = null,
        $form_id = 6,
        $count = false,
        $alias = false,
        $documentsSubqueryClause = null
    ) {
        $joins = $query->getQuery()->joins;

        $postfix = $alias ? $form_id : '';

        if (!$alias && $joins != null) {
            foreach ($joins as $join) {
                if (($join->table instanceof \Illuminate\Database\Query\Expression)
                    && strpos($join->table->getValue(), "`d$postfix`") !== false
                ) {
                    return;
                }
            }
        }

        if (!$count) {
            $query->select('users.*');
        }

        $documentsSubquery = DB::table('documents')
            ->select(
                'documents.*',
                'user_id',
                DB::raw('ROW_NUMBER() OVER (PARTITION BY original_id ORDER BY updated_at DESC) as rn')
            )
            ->leftJoin('document_user', 'document_id', '=', 'id')
            ->where('form_id', $form_id);

        if (is_callable($documentsSubqueryClause)) {
            $documentsSubqueryClause($documentsSubquery);
        }

        if ($request && $till = $request->till) {
            $documentsSubquery->whereDate(
                'updated_at',
                '<=',
                in_array($form_id, [11, 52]) ? Carbon::parse($till)->addDays(6) : $till
            );
        }

        $query->leftJoinSub($documentsSubquery, "d$postfix", function ($query) use ($postfix) {
            $query->whereColumn("d$postfix.user_id", 'users.id')
                ->where("d$postfix.rn", 1);
        });
    }

    protected function addMaleClause($query, $formId = 6)
    {
        $this->addDocumentsJoin($query, $formId);

        if ($formId == 6) {
            $query->where(function ($query) {
                $query->whereJsonContains('d.data->f8Bs', 'GZkX')
                    ->orWhereJsonContains('d.data->f8Bs', '9Dsr');
            });
        } else {
            $this->addDocumentsJoin($query, 6, false, true);

            $otherFormId = $formId == 9 ? 10 : 9;
            $this->addDocumentsJoin($query, $otherFormId, false, true);

            $query->where(function ($query) use ($otherFormId) {
                $query->where('d.data->WuTe', 'Wr8D')
                    ->orWhere("d$otherFormId.data->WuTe", 'Wr8D')
                    ->orWhereJsonContains('d6.data->f8Bs', 'GZkX')
                    ->orWhereJsonContains('d6.data->f8Bs', '9Dsr');
            });
        }
    }

    protected function addFemaleClause($query, $formId = 6)
    {
        $this->addDocumentsJoin($query, $formId, false, true);

        if ($formId == 6) {
            $query->where(function ($query) {
                $query->whereJsonContains('d.data->f8Bs', 'mtD8')
                    ->orWhereJsonContains('d.data->f8Bs', 'fEG9');
            });
        } else {
            $this->addDocumentsJoin($query, 6, false, true);

            $otherFormId = $formId == 9 ? 10 : 9;
            $this->addDocumentsJoin($query, $otherFormId, false, true);

            $query->where(function ($query) use ($otherFormId) {
                $query->where('d.data->WuTe', 'PE5b')
                    ->orWhere("d$otherFormId.data->WuTe", 'PE5b')
                    ->orWhereJsonContains('d6.data->f8Bs', 'mtD8')
                    ->orWhereJsonContains('d6.data->f8Bs', 'fEG9');
            });
        }
    }

    protected function getCountsByGender($query, $form_id = 6)
    {
        $men = clone $query;
        $this->addMaleClause($men, $form_id);

        $women = clone $query;
        $this->addFemaleClause($women, $form_id);

        return [
            $query->count(DB::raw('distinct users.id')),
            $men->count(DB::raw('distinct users.id')),
            $women->count(DB::raw('distinct users.id'))
        ];
    }

    protected function getServicesCountByProfileFieldQueryUsersClause(
        $op,
        $field,
        $value,
        $parts,
        $vuln,
        $MDR,
        $form_id = 6,
        $count = false,
        $nl = false,
        $inverseParts = false
    ) {
        return function ($query) use ($op, $field, $value, $parts, $vuln, $MDR, $form_id, $count, $nl, $inverseParts) {
            $query->where('user_activity.role', 'client');

            if ($field || $vuln || $nl) {
                $this->addDocumentsJoin($query, $form_id, $count);
            }

            if (!empty($parts)) {
                if (!$inverseParts) {
                    $query->whereIn('user_activity.part_id', $parts);
                } else {
                    $query->whereNotIn('user_activity.part_id', $parts);
                }
            }

            if ($op == 'contains') {
                if (!is_array($value)) {
                    $query->whereJsonContains($field, $value);
                } else {
                    $query->where(function ($query) use ($field, $value) {
                        foreach ($value as $val) {
                            $query->orWhereJsonContains($field, $val);
                        }
                    });
                }
            } elseif ($op == 'notin' || $op == 'otherthan') {
                if (!is_array($value)) {
                    $query->whereJsonDoesntContain($field, $value);
                } else {
                    foreach ($value as $val) {
                        $query->whereJsonDoesntContain($field, $val);
                    }
                }

                if ($op == 'otherthan') {
                    $query->whereNotNull($field);
                }
            } elseif ($op == 'in') {
                $query->whereIn($field, $value);
            } elseif ($field) {
                $query->whereNotNull($field)
                    ->where($field, $op, $value);
            }

            if ($vuln) {
                if ($vuln !== 2) {
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

                $this->addVulnerableClause($query);
            }

            if ($nl) {
                static::addNLClause($query);
            }

            if ($MDR === -1) {
                static::addDSClause($query);
            } elseif ($MDR) {
                static::addMDRClause($query);
            }
        };
    }

    protected function getServicesCountByProfileFieldQuery(
        $field,
        $value,
        $parts,
        $request,
        $op = '=',
        $search = null,
        $vuln = false,
        $MDR = false,
        $project = 6,
        $verified = false,
        $ignoreUsersFilter = false,
        $form_id = 6,
        $usersQuery = null,
        $nl = false,
        $position = null,
        $positionNe = false,
        $inverseParts = false
    ) {
        if ($field) {
            $field = "d.data->$field";
        }

        $services = Activity::where('project_id', $project)
            ->whereHas('users', $usersQuery ?: $this->getServicesCountByProfileFieldQueryUsersClause(
                $op,
                $field,
                $value,
                $parts,
                $vuln,
                $MDR,
                $form_id,
                false,
                $nl,
                $inverseParts
            ));

        if ($search) {
            $services->where(function ($query) use ($search) {
                $query->where('title', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%")
                    ->orWhereHas('timings', function ($query) use ($search) {
                        $query->where('comment', 'like', "%$search%");
                    });
            });
        }

        if ($verified) {
            $services->whereHas('timings', function ($query) {
                $query->where('verified', true);
            });
        }

        if ($request->users && !$ignoreUsersFilter) {
            $services->whereHas('users', function ($query) use ($request) {
                $query->whereIn('user_id', $request->users);
            });
        }

        if ($request->from) {
            $services->where('start_date', '>=', $request->from);
        }

        if ($request->till) {
            $services->where('start_date', '<=', $request->till);
        }

        if ($position) {
            $services->whereHas('allUsers.projectUser', function ($query) use ($position, $positionNe) {
                $query->where('position', $positionNe ? 'not like' : 'like', "%$position%");
            });
        }

        return $services;
    }

    protected function getServicesCountByProfileField(
        $field,
        $value,
        $parts,
        $request,
        $op = '=',
        $search = null,
        $vuln = false,
        $MDR = false,
        $project = 6,
        $verified = false,
        $ignoreUsersFilter = false,
        $form_id = 6,
        $nl = false,
        $position = null,
        $positionNe = false,
        $inverseParts = false
    ) {
        $services = $this->getServicesCountByProfileFieldQuery(
            $field,
            $value,
            $parts,
            $request,
            $op,
            $search,
            $vuln,
            $MDR,
            $project,
            $verified,
            $ignoreUsersFilter,
            $form_id,
            null,
            $nl,
            $position,
            $positionNe,
            $inverseParts
        );
        return $services->count(DB::raw('distinct id'));
    }

    protected function getQueryByProfileField(
        $field,
        $value,
        $parts,
        $request,
        $op = '=',
        $search = null,
        $vuln = false,
        $new = false,
        $MDR = false,
        $project = 6,
        $verified = false,
        $ignoreUsersFilter = false,
        $form_id = 6,
        $nl = false,
        $position = null,
        $positionNe = false,
        $parentsByDate = false
    ) {
        $query = User::where('roles', 'like', '%client%')
            ->whereHas('projects', function ($query) use ($project) {
                $query->where('project_id', $project);
            });

        if ($field) {
            $field = "d.data->$field";
        }

        $this->addDocumentsJoin($query, $form_id);

        if (is_array($parts)) {
            $query->whereHas('activities', function ($query) use (
                $request,
                $parts,
                $search,
                $vuln,
                $project,
                $verified,
                $ignoreUsersFilter,
                $position,
                $positionNe
            ) {
                $query->where('project_id', $project);

                if (!empty($parts)) {
                    $query->whereIn('user_activity.part_id', $parts);
                }

                if ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('title', 'like', "%$search%")
                            ->orWhere('description', 'like', "%$search%")
                            ->orWhereHas('timings', function ($query) use ($search) {
                                $query->where('comment', 'like', "%$search%");
                            });
                    });
                }

                if ($request->users && !$ignoreUsersFilter) {
                    $query->whereHas('allUsers', function ($query) use ($request) {
                        $query->whereIn('user_id', $request->users);
                    });
                }

                if ($request->from) {
                    $query->where('start_date', '>=', $request->from);
                }

                if ($request->till) {
                    if (!$vuln || $vuln === 1) {
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

                if ($verified) {
                    $query->whereHas('timings', function ($query) {
                        $query->where('verified', true);
                    });
                }

                if ($position) {
                    $query->whereHas('allUsers.projectUser', function ($query) use ($position, $positionNe) {
                        $query->where('position', $positionNe ? 'not like' : 'like', "%$position%");
                    });
                }
            });
        } elseif ($ignoreUsersFilter) {
            if ($request) {
                if ($request->from) {
                    $query->where('users.created_at', '>=', $request->from);
                }

                if ($request->till) {
                    $query->where('users.created_at', '<=', $request->till);
                }
            }
        } elseif ($request->users) {
            $query->whereHas('activities', function ($query) use ($request, $project) {
                $query->where('project_id', $project)
                    ->whereHas('allUsers', function ($query) use ($request) {
                        $query->whereIn('user_id', $request->users);
                    });
            });
        }

        if ($op == 'contains') {
            if (!is_array($value)) {
                $query->whereJsonContains($field, $value);
            } else {
                $query->where(function ($query) use ($field, $value) {
                    foreach ($value as $val) {
                        $query->orWhereJsonContains($field, $val);
                    }
                });
            }
        } elseif ($op == 'notin' || $op == 'otherthan') {
            if (!is_array($value)) {
                $query->whereJsonDoesntContain($field, $value);
            } else {
                foreach ($value as $val) {
                    $query->whereJsonDoesntContain($field, $val);
                }
            }

            if ($op == 'otherthan') {
                $query->whereNotNull($field);
            }
        } elseif ($op == 'notnull') {
            $query->whereNotNull($field);
        } elseif ($op == 'isnull') {
            $query->whereNull($field);
        } elseif ($op == 'in') {
            $query->whereIn($field, $value);
        } elseif ($field) {
            $query->whereNotNull($field)
                ->where($field, $op, $value);
        }

        if ($vuln) {
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

            $this->addVulnerableClause($query);
        }

        if ($nl) {
            static::addNLClause($query);
        }

        if ($new) {
            if (!$vuln) {
                $this->addNewClientClause($query, $request, $parentsByDate);
            } else {
                $query->where(function ($query) use ($request) {
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

        if ($MDR === -1) {
            static::addDSClause($query);
        } elseif ($MDR) {
            static::addMDRClause($query);
        }

        return $query;
    }

    protected function getCountsByProfileField(
        $field,
        $value,
        $parts,
        $request,
        $op = '=',
        $search = null,
        $vuln = false,
        $new = false,
        $MDR = false,
        $project = 6,
        $verified = false,
        $ignoreUsersFilter = false,
        $form_id = null,
        $nl = false,
        $position = null,
        $positionNe = false,
        $parentsByDate = false
    ) {
        $form_id = $form_id ? $form_id : [6 => 6, 7 => 9, 11 => 10][$project];

        $query = $this->getQueryByProfileField(
            $field,
            $value,
            $parts,
            $request,
            $op,
            $search,
            $vuln,
            $new,
            $MDR,
            $project,
            $verified,
            $ignoreUsersFilter,
            $form_id,
            $nl,
            $position,
            $positionNe,
            $parentsByDate
        );

        $services = 0;

        if (is_array($parts)) {
            $services = $this->getServicesCountByProfileField(
                $field,
                $value,
                $parts,
                $request,
                $op,
                $search,
                $vuln,
                $MDR,
                $project,
                $verified,
                $ignoreUsersFilter,
                $form_id,
                $nl,
                $position,
                $positionNe
            );
        }

        return $this->getCountsByGender($query, $form_id) + [3 => $services];
    }

    protected function addScreenedClause($query, $request)
    {
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
        });
    }

    protected function addNewClientClause($query, $request, $parentsByDate = false)
    {
        $query->where(function ($query) use ($request, $parentsByDate) {
            $this->addScreenedClause($query, $request);

            $query->orWhere(function ($query) use ($request, $parentsByDate) {
                $query->where(function ($query) use ($parentsByDate) {
                    $query->whereJsonContains('d.data->6g6x', 'iNHa');

                    if ($parentsByDate) {
                        $query->orWhereJsonContains('d.data->6g6x', 'aSu3');
                    }
                });

                if ($request->from) {
                    $query->where('d.data->kB3w', '>=', $request->from);
                }

                if ($request->till) {
                    $query->where('d.data->kB3w', '<=', $request->till);
                }
            });
        });
    }

    protected function addEmployeeRelationClause($query, $request, $parts = [361, 362, 363])
    {
        $query->where('project_id', 6)
            ->whereHas('allUsers', function ($query) use ($request) {
                $query->whereIn('user_id', $request->users);
            })
            ->whereIn('part_id', $parts)
            ->where(function ($query) {
                $keyword = 'тб';

                $query->where('title', 'like', "%$keyword%")
                    ->orWhere('description', 'like', "%$keyword%")
                    ->orWhereHas('timings', function ($query) use ($keyword) {
                        $query->where('comment', 'like', "%$keyword%");
                    });
            });
    }

    protected function addVulnerableClause($query)
    {
        $query->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'uQRP')
                ->orWhereJsonContains('d.data->6g6x', 'gG99')
                ->orWhereJsonContains('d.data->6g6x', '2AJg')
                ->orWhereJsonContains('d.data->6g6x', 'YNoK')
                ->orWhereJsonContains('d.data->6g6x', 'dLBE');
        });
    }

    public static function addNLClause($query)
    {
        $query->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'uQRP')
                ->orWhereJsonContains('d.data->6g6x', 'gG99')
                ->orWhereJsonContains('d.data->6g6x', '2AJg')
                ->orWhereJsonContains('d.data->6g6x', 'YNoK');
        });
    }

    public static function addMDRClause($query)
    {
        $query->where(function ($query) {
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

    public static function addDSClause($query)
    {
        $query->whereJsonDoesntContain('d.data->d6XS', 'gBZJ')
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
    }

    protected function formatTime($seconds)
    {
        $carbon = CarbonInterval::seconds($seconds)->cascade();

        return implode(' ', array_filter([$carbon->format('%h ч.'), $carbon->format('%i м.')], 'intval'));
    }
}
