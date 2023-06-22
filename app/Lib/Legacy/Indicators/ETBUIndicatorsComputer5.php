<?php

namespace App\Lib\Legacy\Indicators;

use DB;
use Carbon\Carbon;

use App\Models\Activity;
use App\Models\User;
use Carbon\CarbonInterval;

class ETBUIndicatorsComputer5 extends ProjectIndicatorsComputer
{
    public function compute($request, $customAccess, $new, $datesOnlyQuery, $datesQuery)
    {
        $totalServices = $this->getServicesCountByProfileField(
            '6g6x',
            'HeBk',
            [731],
            $request,
            'notin',
            null,
            false,
            false,
            6,
            false,
            false,
            6,
            false,
            null,
            false,
            true
        );

        $totalServicesNL = $this->getServicesCountByProfileField(
            '6g6x',
            ['HeBk', 'dLBE'],
            [731],
            $request,
            'notin',
            null,
            false,
            false,
            6,
            false,
            false,
            6,
            false,
            null,
            false,
            true
        );

        $totalVulnerableServices = $this->getServicesCountByProfileField(
            null,
            null,
            [731],
            $request,
            '=',
            null,
            2,
            false,
            6,
            false,
            false,
            6,
            false,
            null,
            false,
            true
        );

        $totalVulnerableServicesNL = $this->getServicesCountByProfileField(
            null,
            null,
            [731],
            $request,
            '=',
            null,
            2,
            false,
            6,
            false,
            false,
            6,
            true,
            null,
            false,
            true
        );

        $fullChildrenDatesQuery = $datesOnlyQuery .
            ($request->users && isset($request->users[0]->id) && $request->users[0] != 72
                ? '&users=' . implode('&users=', $request->users) : '');

        $screenedQuery = User::where('roles', 'like', '%client%')
            ->whereHas('projects', function ($query) {
                $query->where('project_id', 6);
            })
            ->whereHas('activities', function ($query) use ($request) {
                $query->where('project_id', 6)
                    ->where(function ($query) {
                        $keyword = 'скрининг';

                        $query->where('title', 'like', "%$keyword%")
                            ->orWhere('description', 'like', "%$keyword%")
                            ->orWhereHas('timings', function ($query) use ($keyword) {
                                $query->where('comment', 'like', "%$keyword%");
                            });
                    });

                if ($request->users) {
                    $query->whereHas('allUsers', function ($query) use ($request) {
                        $query->whereIn('user_id', $request->users);
                    });
                }

                if ($request->from) {
                    $query->where('start_date', '>=', $request->from);
                }

                if ($request->till) {
                    $query->where('start_date', '<=', $request->till);
                }
            });

        $this->addVulnerableClause($screenedQuery);
        $this->addDocumentsJoin($screenedQuery);

        list(
            $screened,
            $screenedMale,
            $screenedFemale,
        ) = $this->getCountsByGender($screenedQuery);

        $screenedNLQuery = clone $screenedQuery;
        static::addNLClause($screenedNLQuery);

        list(
            $screenedNL,
            $screenedMaleNL,
            $screenedFemaleNL,
        ) = $this->getCountsByGender($screenedNLQuery);

        $screenedPrisonerQuery = clone $screenedQuery;
        $screenedPrisonerQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'gG99')
                ->orWhereJsonContains('d.data->6g6x', 'MuuE');
        });

        list(
            $screenedPrisoner,
            $screenedPrisonerMen,
            $screenedPrisonerWomen,
        ) = $this->getCountsByGender($screenedPrisonerQuery);

        $screenedMigrantQuery = clone $screenedQuery;
        $screenedMigrantQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'uQRP')
                ->orWhereJsonContains('d.data->6g6x', 'YNoK');
        });

        list(
            $screenedMigrant,
            $screenedMigrantMen,
            $screenedMigrantWomen,
        ) = $this->getCountsByGender($screenedMigrantQuery);

        $screenedDrugUserQuery = clone $screenedQuery;
        $screenedDrugUserQuery->whereJsonContains('d.data->6g6x', '2AJg');

        list(
            $screenedDrugUser,
            $screenedDrugUserMen,
            $screenedDrugUserWomen,
        ) = $this->getCountsByGender($screenedDrugUserQuery);

        $screenedLimitedQuery = clone $screenedQuery;
        $screenedLimitedQuery->whereJsonContains('d.data->6g6x', 'dLBE');

        list(
            $screenedLimited,
            $screenedLimitedMen,
            $screenedLimitedWomen,
        ) = $this->getCountsByGender($screenedLimitedQuery);

        $redundantScreenedNLQuery = clone $screenedQuery;
        $redundantScreenedNL = $redundantScreenedNLQuery->whereRaw(
            'if(json_contains(d.data, \'"uQRP"\', "$.6g6x") ' .
                'or json_contains(d.data, \'"YNoK"\', "$.6g6x"), 1, 0) + ' .
            'json_contains(d.data, \'"2AJg"\', "$.6g6x") + ' .
            'if(json_contains(d.data, \'"gG99"\', "$.6g6x") ' .
                'or json_contains(d.data, \'"MuuE"\', "$.6g6x"), 1, 0) ' .
            '> 1'
        )->count(DB::raw('distinct users.id'));

        $examinedQuery = User::where('roles', 'like', '%client%')
            ->whereHas('projects', function ($query) {
                $query->where('project_id', 6);
            })
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where(function ($query) {
                        $query->whereNotNull('d.data->Xnsu')
                            ->where('d.data->Xnsu', 'LbKw');
                    })
                    ->where(function ($query) {
                        $query->whereNotNull('d.data->it7x')
                            ->whereDate('d.data->it7x', '>=', '2020-09-01');
                    });
                })
                ->orWhere(function ($query) {
                    $query->where(function ($query) {
                        $query->whereNotNull('d.data->roMc')
                            ->where('d.data->roMc', 'XiCo');
                    })
                    ->where(function ($query) {
                        $query->whereNotNull('d.data->mynW')
                            ->whereDate('d.data->mynW', '>=', '2020-09-01');
                    });
                });
            });

        $this->addDocumentsJoin($examinedQuery);

        if ($request->users) {
            $examinedQuery->whereHas('activities', function ($query) use ($request) {
                $this->addEmployeeRelationClause($query, $request);
            });
        }

        if ($request->from || $request->till) {
            $examinedQuery->where(function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    if ($request->from) {
                        $query->where('d.data->it7x', '>=', $request->from);
                    }

                    if ($request->till) {
                        $query->where('d.data->it7x', '<=', $request->till);
                    }
                })
                ->orWhere(function ($query) use ($request) {
                    if ($request->from) {
                        $query->where('d.data->mynW', '>=', $request->from);
                    }

                    if ($request->till) {
                        $query->where('d.data->mynW', '<=', $request->till);
                    }
                });
            });
        }

        $examinedTotal = $examinedQuery->count(DB::raw('distinct users.id'));

        if ($new) {
            $examinedTotalNewQuery = clone $examinedQuery;
            $this->addNewClientClause($examinedTotalNewQuery, $request);
            $examinedTotalNew = $examinedTotalNewQuery->count(DB::raw('distinct users.id'));
        } else {
            $examinedTotalNew = null;
        }

        $examinedParentsQuery = clone $examinedQuery;
        $examinedParents = $examinedParentsQuery->whereJsonContains('d.data->6g6x', 'aSu3')
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($examinedParentsQuery, $request);
            $examinedParentsNew = $examinedParentsQuery->count(DB::raw('distinct users.id'));
        } else {
            $examinedParentsNew = null;
        }

        $examinedChildrenQuery = clone $examinedQuery;
        $examinedChildren = $examinedChildrenQuery->whereJsonContains('d.data->6g6x', 'iNHa')
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($examinedChildrenQuery, $request);
            $examinedChildrenNew = $examinedChildrenQuery->count(DB::raw('distinct users.id'));
        } else {
            $examinedChildrenNew = null;
        }

        $examinedGeneralQuery = clone $examinedQuery;
        $examinedGeneral = $examinedGeneralQuery->whereJsonContains('d.data->6g6x', 'bEk7')
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($examinedGeneralQuery, $request);
            $examinedGeneralNew = $examinedGeneralQuery->count(DB::raw('distinct users.id'));
        } else {
            $examinedGeneralNew = null;
        }

        $examinedOtherQuery = clone $examinedQuery;
        $examinedOther = $examinedOtherQuery
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->whereRaw('json_length(d.data, "$.6g6x") = 0')
                        ->orWhereNull('d.data->6g6x');
                })
                ->orWhere(function ($query) {
                    $query->whereRaw('json_length(d.data, "$.6g6x") = 1')
                        ->whereJsonContains('d.data->6g6x', 'ab8e');
                });
            })
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($examinedOtherQuery, $request);
            $examinedOtherNew = $examinedOtherQuery->count(DB::raw('distinct users.id'));
        } else {
            $examinedOtherNew = null;
        }

        $this->addVulnerableClause($examinedQuery);

        $examined = $examinedQuery->count(DB::raw('distinct users.id'));

        $examinedNLQuery = clone $examinedQuery;
        static::addNLClause($examinedNLQuery);
        $examinedNL = $examinedNLQuery->count(DB::raw('distinct users.id'));

        if ($new) {
            $examinedNewQuery = clone $examinedQuery;
            $this->addNewClientClause($examinedNewQuery, $request);
            $examinedNew = $examinedNewQuery->count(DB::raw('distinct users.id'));

            static::addNLClause($examinedNewQuery);
            $examinedNLNew = $examinedNewQuery->count(DB::raw('distinct users.id'));
        } else {
            $examinedNew = null;
            $examinedNLNew = null;
        }

        $examinedMaleQuery = clone $examinedQuery;
        $this->addMaleClause($examinedMaleQuery);
        $examinedMale = $examinedMaleQuery->count(DB::raw('distinct users.id'));

        $examinedMaleNLQuery = clone $examinedMaleQuery;
        static::addNLClause($examinedMaleNLQuery);
        $examinedMaleNL = $examinedMaleNLQuery->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($examinedMaleQuery, $request);
            $examinedMaleNew = $examinedMaleQuery->count(DB::raw('distinct users.id'));

            static::addNLClause($examinedMaleQuery);
            $examinedMaleNLNew = $examinedMaleQuery->count(DB::raw('distinct users.id'));
        } else {
            $examinedMaleNew = null;
            $examinedMaleNLNew = null;
        }

        $examinedFemaleQuery = clone $examinedQuery;
        $this->addFemaleClause($examinedFemaleQuery);
        $examinedFemale = $examinedFemaleQuery->count(DB::raw('distinct users.id'));

        $examinedFemaleNLQuery = clone $examinedFemaleQuery;
        static::addNLClause($examinedFemaleNLQuery);
        $examinedFemaleNL = $examinedFemaleNLQuery->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($examinedFemaleQuery, $request);
            $examinedFemaleNew = $examinedFemaleQuery->count(DB::raw('distinct users.id'));

            static::addNLClause($examinedFemaleQuery);
            $examinedFemaleNLNew = $examinedFemaleQuery->count(DB::raw('distinct users.id'));
        } else {
            $examinedFemaleNew = null;
            $examinedFemaleNLNew = null;
        }

        $examinedMigrantsQuery = clone $examinedQuery;
        $examinedMigrants = $examinedMigrantsQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'uQRP')
                ->orWhereJsonContains('d.data->6g6x', 'YNoK');
        })
        ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($examinedMigrantsQuery, $request);
            $examinedMigrantsNew = $examinedMigrantsQuery->count(DB::raw('distinct users.id'));
        } else {
            $examinedMigrantsNew = null;
        }

        $examinedDrugUsersQuery = clone $examinedQuery;
        $examinedDrugUsers = $examinedDrugUsersQuery->whereJsonContains('d.data->6g6x', '2AJg')
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($examinedDrugUsersQuery, $request);
            $examinedDrugUsersNew = $examinedDrugUsersQuery->count(DB::raw('distinct users.id'));
        } else {
            $examinedDrugUsersNew = null;
        }

        $examinedPrisonersQuery = clone $examinedQuery;
        $examinedPrisoners = $examinedPrisonersQuery->where(function ($query) {
                $query->whereJsonContains('d.data->6g6x', 'gG99')
                    ->orWhereJsonContains('d.data->6g6x', 'MuuE');
        })
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($examinedPrisonersQuery, $request);
            $examinedPrisonersNew = $examinedPrisonersQuery->count(DB::raw('distinct users.id'));
        } else {
            $examinedPrisonersNew = null;
        }

        $examinedDifficultQuery = clone $examinedQuery;
        $examinedDifficult = $examinedDifficultQuery->whereJsonContains('d.data->6g6x', 'dLBE')
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($examinedDifficultQuery, $request);
            $examinedDifficultNew = $examinedDifficultQuery->count(DB::raw('distinct users.id'));
        } else {
            $examinedDifficultNew = null;
        }

        $redundantExaminedNLQuery = clone $examinedNLQuery;
        $redundantExaminedNL = $redundantExaminedNLQuery->whereRaw(
            'if(json_contains(d.data, \'"uQRP"\', "$.6g6x") ' .
                'or json_contains(d.data, \'"YNoK"\', "$.6g6x"), 1, 0) + ' .
            'json_contains(d.data, \'"2AJg"\', "$.6g6x") + ' .
            'if(json_contains(d.data, \'"gG99"\', "$.6g6x") ' .
                'or json_contains(d.data, \'"MuuE"\', "$.6g6x"), 1, 0) ' .
            '> 1'
        )->count(DB::raw('distinct users.id'));

        list(
            $vulnerablePatientSchool,
            $vulnerablePatientSchoolMale,
            $vulnerablePatientSchoolFemale
            ) = $this->getCountsByProfileField(
                null,
                null,
                [372, 373, 374, 375, 376, 474, 475, 479, 482],
                $request,
                null,
                null,
                1
            );

        if ($new) {
            list(
                $vulnerablePatientSchoolNew,
                $vulnerablePatientSchoolMaleNew,
                $vulnerablePatientSchoolFemaleNew,
            ) = $this->getCountsByProfileField(
                null,
                null,
                [372, 373, 374, 375, 376, 474, 475, 479, 482],
                $request,
                null,
                null,
                1,
                true
            );
        } else {
            $vulnerablePatientSchoolNew = null;
            $vulnerablePatientSchoolMaleNew = null;
            $vulnerablePatientSchoolFemaleNew = null;
        }

        $vulnerablePatientSchoolServices = $this->getServicesCountByProfileFieldQuery(
            null,
            null,
            [372, 373, 374, 375, 376, 474, 475, 479, 482],
            $request,
            null,
            null,
            true
        )
            ->groupBy('start_date')
            ->get()
            ->count();

        $vulnerablePatientSchoolParticipations = $this->getServicesCountByProfileFieldQuery(
            null,
            null,
            [372, 373, 374, 375, 376, 474, 475, 479, 482],
            $request,
            null,
            null,
            true
        )
            ->withCount(['users' => $this->getServicesCountByProfileFieldQueryUsersClause(
                '=',
                null,
                null,
                [372, 373, 374, 375, 376, 474, 475, 479, 482],
                true,
                false,
                6,
                true
            )])
            ->get()
            ->sum('users_count');

        $vulnerablePatientSchoolNLQuery = $this->getQueryByProfileField(
            null,
            null,
            [372, 373, 374, 375, 376, 474, 475, 479, 482],
            $request,
            null,
            null,
            1
        );

        static::addNLClause($vulnerablePatientSchoolNLQuery);

        list(
            $vulnerablePatientSchoolNL,
            $vulnerablePatientSchoolMaleNL,
            $vulnerablePatientSchoolFemaleNL
        ) = $this->getCountsByGender($vulnerablePatientSchoolNLQuery);

        if ($new) {
            $vulnerablePatientSchoolNewNLQuery = $this->getQueryByProfileField(
                null,
                null,
                [372, 373, 374, 375, 376, 474, 475, 479, 482],
                $request,
                null,
                null,
                1,
                true
            );

            static::addNLClause($vulnerablePatientSchoolNewNLQuery);

            list(
                $vulnerablePatientSchoolNewNL,
                $vulnerablePatientSchoolMaleNewNL,
                $vulnerablePatientSchoolFemaleNewNL,
            ) = $this->getCountsByGender($vulnerablePatientSchoolNewNLQuery);
        } else {
            $vulnerablePatientSchoolNewNL = null;
            $vulnerablePatientSchoolMaleNewNL = null;
            $vulnerablePatientSchoolFemaleNewNL = null;
        }

        $vulnerablePatientSchoolServicesNL = $this->getServicesCountByProfileFieldQuery(
            null,
            null,
            [372, 373, 374, 375, 376, 474, 475, 479, 482],
            $request,
            null,
            null,
            true,
            false,
            6,
            false,
            false,
            6,
            function ($query) {
                $this->getServicesCountByProfileFieldQueryUsersClause(
                    null,
                    null,
                    null,
                    [372, 373, 374, 375, 376, 474, 475, 479, 482],
                    true,
                    false,
                    6,
                )($query);

                static::addNLClause($query);
            }
        )
            ->groupBy('start_date')
            ->get()
            ->count();

        $vulnerablePatientSchoolParticipationsNL = $this->getServicesCountByProfileFieldQuery(
            null,
            null,
            [372, 373, 374, 375, 376, 474, 475, 479, 482],
            $request,
            null,
            null,
            true,
            false,
            6,
            false,
            false,
            6,
            function ($query) {
                $this->getServicesCountByProfileFieldQueryUsersClause(
                    null,
                    null,
                    null,
                    [372, 373, 374, 375, 376, 474, 475, 479, 482],
                    true,
                    false,
                    6
                )($query);

                static::addNLClause($query);
            }
        )
            ->withCount(['users' => function ($query) {
                $this->getServicesCountByProfileFieldQueryUsersClause(
                    null,
                    null,
                    null,
                    [372, 373, 374, 375, 376, 474, 475, 479, 482],
                    true,
                    false,
                    6,
                    true
                )($query);

                static::addNLClause($query);
            }])
            ->get()
            ->sum('users_count');

        list(
            $childrenPatientSchool,
            $childrenPatientSchoolMale,
            $childrenPatientSchoolFemale,
            $childrenPatientSchoolServices
        ) = $this->getCountsByProfileField(
            '6g6x',
            'iNHa',
            [372, 373, 374, 375, 376, 474, 475, 479, 482],
            $request,
            'contains',
            null
        );

        if ($new) {
            list(
                $childrenPatientSchoolNew,
                $childrenPatientSchoolMaleNew,
                $childrenPatientSchoolFemaleNew,
            ) = $this->getCountsByProfileField(
                '6g6x',
                'iNHa',
                [372, 373, 374, 375, 376, 474, 475, 479, 482],
                $request,
                'contains',
                null,
                false,
                true
            );
        } else {
            $childrenPatientSchoolNew = null;
            $childrenPatientSchoolMaleNew = null;
            $childrenPatientSchoolFemaleNew = null;
        }

        $childrenPatientSchoolServices = $this->getServicesCountByProfileFieldQuery(
            '6g6x',
            'iNHa',
            [372, 373, 374, 375, 376, 474, 475, 479, 482],
            $request,
            'contains',
            null
        )
            ->groupBy('start_date')
            ->get()
            ->count();

        $childrenPatientSchoolParticipations = $this->getServicesCountByProfileFieldQuery(
            '6g6x',
            'iNHa',
            [372, 373, 374, 375, 376, 474, 475, 479, 482],
            $request,
            'contains',
            null
        )
            ->withCount(['users' => $this->getServicesCountByProfileFieldQueryUsersClause(
                'contains',
                'd.data->6g6x',
                'iNHa',
                [372, 373, 374, 375, 376, 474, 475, 479, 482],
                false,
                false,
                6,
                true
            )])
            ->get()
            ->sum('users_count');

        list(
            $parentPatientSchool,
            $parentPatientSchoolMale,
            $parentPatientSchoolFemale,
            $parentPatientSchoolServices
        ) = $this->getCountsByProfileField(
            '6g6x',
            'aSu3',
            [372, 373, 374, 375, 376, 474, 475, 479, 482],
            $request,
            'contains',
            null
        );

        if ($new) {
            list(
                $parentPatientSchoolNew,
                $parentPatientSchoolMaleNew,
                $parentPatientSchoolFemaleNew,
            ) = $this->getCountsByProfileField(
                '6g6x',
                'aSu3',
                [372, 373, 374, 375, 376, 474, 475, 479, 482],
                $request,
                'contains',
                null,
                false,
                true
            );
        } else {
            $parentPatientSchoolNew = null;
            $parentPatientSchoolMaleNew = null;
            $parentPatientSchoolFemaleNew = null;
        }

        $parentPatientSchoolServices = $this->getServicesCountByProfileFieldQuery(
            '6g6x',
            'aSu3',
            [372, 373, 374, 375, 376, 474, 475, 479, 482],
            $request,
            'contains',
            null
        )
            ->groupBy('start_date')
            ->get()
            ->count();

        $parentPatientSchoolParticipations = $this->getServicesCountByProfileFieldQuery(
            '6g6x',
            'aSu3',
            [372, 373, 374, 375, 376, 474, 475, 479, 482],
            $request,
            'contains',
            null
        )
            ->withCount(['users' => $this->getServicesCountByProfileFieldQueryUsersClause(
                'contains',
                'd.data->6g6x',
                'aSu3',
                [372, 373, 374, 375, 376, 474, 475, 479, 482],
                false,
                false,
                6,
                true
            )])
            ->get()
            ->sum('users_count');

        $contactedQuery = User::where('roles', 'like', '%client%')
            ->whereJsonContains('d.data->6g6x', 'ab8e')
            ->whereHas('projects', function ($query) {
                $query->where('project_id', 6);
            });

        $this->addDocumentsJoin($contactedQuery);

        $screenedContactedQuery = clone $contactedQuery;

        $this->addScreenedClause($screenedContactedQuery, $request);

        $contacted = $screenedContactedQuery->count(DB::raw('distinct users.id'));

        $contactedQuery->where(function ($query) use ($request) {
            $query->where(function ($query) use ($request) {
                $query->whereNotNull('d.data->Xnsu')
                    ->where('d.data->Xnsu', 'LbKw')
                    ->whereNotNull('d.data->it7x')
                    ->whereDate('d.data->it7x', '>=', '2020-09-01');

                if ($request->from) {
                    $query->where('d.data->it7x', '>=', $request->from);
                }

                if ($request->till) {
                    $query->where('d.data->it7x', '<=', $request->till);
                }
            })
            ->orWhere(function ($query) use ($request) {
                $query->whereNotNull('d.data->roMc')
                    ->where('d.data->roMc', 'XiCo')
                    ->whereNotNull('d.data->mynW')
                    ->whereDate('d.data->mynW', '>=', '2020-09-01');

                if ($request->from) {
                    $query->where('d.data->mynW', '>=', $request->from);
                }

                if ($request->till) {
                    $query->where('d.data->mynW', '<=', $request->till);
                }
            });
        });

        $contactedExamined = $contactedQuery->count(DB::raw('distinct users.id'));

        $contactedQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'iNHa')
                ->orWhereJsonContains('d.data->6g6x', 'aSu3')
                ->orWhereJsonContains('d.data->6g6x', 'HeBk')
                ->orWhereJsonContains('d.data->6g6x', 'bEk7');
        });

        $otherContactedExamined = $contactedQuery->count(DB::raw('distinct users.id'));

        $escortedQuery = User::where('roles', 'like', '%client%')
            ->whereHas('projects', function ($query) {
                $query->where('project_id', 6);
            })
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->whereNotNull('d.data->Xnsu')
                        ->where('d.data->Xnsu', 'LbKw');
                })
                ->orWhere(function ($query) {
                    $query->whereNotNull('d.data->roMc')
                        ->where('d.data->roMc', 'XiCo');
                });
            })
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->whereNotNull('d.data->it7x')
                        ->whereDate('d.data->it7x', '>=', '2020-09-01');
                })
                ->orWhere(function ($query) {
                    $query->whereNotNull('d.data->mynW')
                        ->whereDate('d.data->mynW', '>=', '2020-09-01');
                });
            })
            ->whereHas('activities', function ($query) use ($request) {
                $query->where('project_id', 6)
                    ->whereIn('user_activity.part_id', [362, 363])
                    ->where(function ($query) {
                        $keyword = 'тб';

                        $query->where('title', 'like', "%$keyword%")
                            ->orWhere('description', 'like', "%$keyword%")
                            ->orWhereHas('timings', function ($query) use ($keyword) {
                                $query->where('comment', 'like', "%$keyword%");
                            });
                    });

                if ($request->users) {
                    $query->whereHas('allUsers', function ($query) use ($request) {
                        $query->whereIn('user_id', $request->users);
                    });
                }

                if ($request->from) {
                    $query->where('start_date', '>=', $request->from);
                }

                if ($request->till) {
                    $query->where('start_date', '<=', $request->till);
                }
            });

        $this->addDocumentsJoin($escortedQuery);

        $escorted = $escortedQuery->count(DB::raw('distinct users.id'));

        $escortedNLQuery = clone $escortedQuery;
        static::addNLClause($escortedNLQuery);
        $escortedNL = $escortedNLQuery->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($escortedQuery, $request);
            $escortedNew = $escortedQuery->count(DB::raw('distinct users.id'));
        } else {
            $escortedNew = null;
        }

        if ($new) {
            $this->addNewClientClause($escortedNLQuery, $request);
            $escortedNLNew = $escortedNLQuery->count(DB::raw('distinct users.id'));
        } else {
            $escortedNLNew = null;
        }

        $transportedClientClause = function ($query) {
            $query->where('roles', 'like', '%client%')
                ->whereHas('projects', function ($query) {
                    $query->where('project_id', 6);
                })
                ->where(function ($query) {
                    $query->where(function ($query) {
                        $query->whereNotNull('d.data->Xnsu')
                            ->where('d.data->Xnsu', 'LbKw');
                    })
                    ->orWhere(function ($query) {
                        $query->whereNotNull('d.data->roMc')
                            ->where('d.data->roMc', 'XiCo');
                    });
                })
                ->where(function ($query) {
                    $query->where(function ($query) {
                        $query->whereNotNull('d.data->it7x')
                            ->whereDate('d.data->it7x', '>=', '2020-09-01');
                    })
                    ->orWhere(function ($query) {
                        $query->whereNotNull('d.data->mynW')
                            ->whereDate('d.data->mynW', '>=', '2020-09-01');
                    });
                });
        };

        $transportedClientServiceClause = function ($query) use ($request) {
            $query->where('project_id', 6)
                ->where(function ($query) {
                    $keyword = 'тб';

                    $query->where('title', 'like', "%$keyword%")
                        ->orWhere('description', 'like', "%$keyword%")
                        ->orWhereHas('timings', function ($query) use ($keyword) {
                            $query->where('comment', 'like', "%$keyword%");
                        });
                });

            if ($request->users) {
                $query->whereHas('allUsers', function ($query) use ($request) {
                    $query->whereIn('user_id', $request->users);
                });
            }

            if ($request->from) {
                $query->where('start_date', '>=', $request->from);
            }

            if ($request->till) {
                $query->where('start_date', '<=', $request->till);
            }
        };

        $transportedQuery = User::where($transportedClientClause)
            ->whereHas('activities', function ($query) use ($transportedClientServiceClause) {
                $transportedClientServiceClause($query);
                $query->whereIn('user_activity.part_id', [363]);
            });

        $this->addDocumentsJoin($transportedQuery);

        $transported = $transportedQuery->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($transportedQuery, $request);
            $transportedNew = $transportedQuery->count(DB::raw('distinct users.id'));
        } else {
            $transportedNew = null;
        }

        $transportedNLQuery = clone $transportedQuery;
        static::addNLClause($transportedNLQuery);

        $transportedNL = $transportedNLQuery->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($transportedNLQuery, $request);
            $transportedNLNew = $transportedNLQuery->count(DB::raw('distinct users.id'));
        } else {
            $transportedNLNew = null;
        }

        $transportedServices = Activity::where($transportedClientServiceClause)
            ->whereHas('users', function ($query) use ($transportedClientClause) {
                $transportedClientClause($query);
                $query->whereIn('user_activity.part_id', [363]);
                $this->addDocumentsJoin($query);
            })
            ->count();

        $transportedServicesNL = Activity::where($transportedClientServiceClause)
            ->whereHas('users', function ($query) use ($transportedClientClause) {
                $transportedClientClause($query);
                $query->whereIn('user_activity.part_id', [363]);
                $this->addDocumentsJoin($query);
                static::addNLClause($query);
            })
            ->count();

        $enteredQuery = $this->getQueryByProfileField(null, null, [], $request, null, null, true);

        if ($request->from) {
            $enteredQuery->where('d.data->kB3w', '>=', $request->from);
        }

        if ($request->till) {
            $enteredQuery->where('d.data->kB3w', '<=', $request->till);
        }

        $entered = $enteredQuery->count(DB::raw('distinct users.id'));

        static::addNLClause($enteredQuery);
        $enteredNL = $enteredQuery->count(DB::raw('distinct users.id'));

        $reHospitalizedNLQuery = $this->getQueryByProfileField('eTzT', true, [], $request, '=', null, true);

        static::addNLClause($reHospitalizedNLQuery);

        if ($request->from) {
            $reHospitalizedNLQuery->where('d.data->fofk', '>=', $request->from);
        }

        if ($request->till) {
            $reHospitalizedNLQuery->where('d.data->fofk', '<=', $request->till);
        }

        $reHospitalizedNL = $reHospitalizedNLQuery->count(DB::raw('distinct users.id'));

        list(
            $vulnerable,
            $vulnerableMen,
            $vulnerableWomen,
            $vulnerableServices
        ) = $this->getCountsByProfileField(null, null, [], $request, null, null, true);

        if ($new) {
            list(
                $vulnerableNew,
                $vulnerableMenNew,
                $vulnerableWomenNew,
            ) = $this->getCountsByProfileField(null, null, [], $request, null, null, true, true);
        } else {
            $vulnerableNew = null;
            $vulnerableMenNew = null;
            $vulnerableWomenNew = null;
        }

        $vulnerableNLQuery = $this->getQueryByProfileField(null, null, [], $request, null, null, true);
        static::addNLClause($vulnerableNLQuery);
        $vulnerableNL = $vulnerableNLQuery->count(DB::raw('distinct users.id'));

        if ($new) {
            $vulnerableNLNewQuery = $this->getQueryByProfileField(null, null, [], $request, null, null, true, true);
            static::addNLClause($vulnerableNLNewQuery);
            $vulnerableNLNew = $vulnerableNLNewQuery->count(DB::raw('distinct users.id'));
        } else {
            $vulnerableNLNew = null;
        }

        $vulnerableServicesNLQuery = $this->getServicesCountByProfileFieldQuery(
            null,
            null,
            [731],
            $request,
            null,
            null,
            true,
            false,
            6,
            false,
            false,
            6,
            function ($query) {
                $this->getServicesCountByProfileFieldQueryUsersClause(
                    null,
                    null,
                    null,
                    [731],
                    true,
                    false,
                    6,
                    false,
                    false,
                    true
                )($query);

                static::addNLClause($query);
            },
            false,
            null,
            false,
            true
        );

        $vulnerableServicesNL = $vulnerableServicesNLQuery->count(DB::raw('distinct id'));

        $redundantVulnerableQuery = $this->getQueryByProfileField(null, null, [], $request, null, null, true);
        $redundantVulnerableQuery->whereRaw(
            'if(json_contains(d.data, \'"uQRP"\', "$.6g6x") ' .
                'or json_contains(d.data, \'"YNoK"\', "$.6g6x"), 1, 0) + ' .
            'json_contains(d.data, \'"2AJg"\', "$.6g6x") + ' .
            'if(json_contains(d.data, \'"gG99"\', "$.6g6x") ' .
                'or json_contains(d.data, \'"MuuE"\', "$.6g6x"), 1, 0) + ' .
            'json_contains(d.data, \'"iNHa"\', "$.6g6x") + ' .
            'json_contains(d.data, \'"aSu3"\', "$.6g6x") + ' .
            'json_contains(d.data, \'"dLBE"\', "$.6g6x") ' .
            '> 1'
        );

        $redundantVulnerable = $redundantVulnerableQuery->count(DB::raw('distinct users.id'));

        static::addMDRClause($redundantVulnerableQuery);

        $redundantMDRVulnerable = $redundantVulnerableQuery->count(DB::raw('distinct users.id'));

        $fullVulnerableQuery = $this->getQueryByProfileField(
            null,
            null,
            null,
            null,
            null,
            null,
            true,
            false,
            false,
            6,
            false,
            true
        );

        $fullVulnerableQuery->groupBy('users.id');

        $fullVulnerableQuery->with([
            'activities' => function ($query) use ($request) {
                $query->where('project_id', 6);

                if ($request->from) {
                    $query->where('start_date', '>=', $request->from);
                }

                if ($request->till) {
                    $query->where('start_date', '<=', $request->till);
                }
            }
        ]);


        if ($request->till) {
            $fullVulnerableQuery->where('d.data->kB3w', '<=', $request->till);
        }

        $fullVulnerable = 0;
        $fullVulnerable2 = 0;
        $fullVulnerable2MDR = 0;

        foreach ($fullVulnerableQuery->get() as $item) {
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

            if (count($activites) && count($activites) >= $months) {
                $fullVulnerable++;
            }

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
                $fullVulnerable2++;
            }
        }

        $this->addMDRClause($fullVulnerableQuery);

        foreach ($fullVulnerableQuery->get() as $item) {
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
                $fullVulnerable2MDR++;
            }
        }

        list(
            $vulnerableMDR,
            $vulnerableMDRMen,
            $vulnerableMDRWomen,
            $vulnerableMDRServices
        ) = $this->getCountsByProfileField(null, null, [], $request, null, null, true, false, true);

        if ($new) {
            list(
                $vulnerableMDRNew,
                $vulnerableMDRMenNew,
                $vulnerableMDRWomenNew,
            ) = $this->getCountsByProfileField(null, null, [], $request, null, null, true, true, true);
        } else {
            $vulnerableMDRNew = null;
            $vulnerableMDRMenNew = null;
            $vulnerableMDRWomenNew = null;
        }

        $vulnerableMDRNLQuery = $this->getQueryByProfileField(null, null, [], $request, null, null, true, false, true);
        static::addNLClause($vulnerableMDRNLQuery);
        $vulnerableMDRNL = $vulnerableMDRNLQuery->count(DB::raw('distinct users.id'));

        if ($new) {
            $vulnerableMDRNLNewQuery = $this->getQueryByProfileField(
                null,
                null,
                [],
                $request,
                null,
                null,
                true,
                true,
                true
            );
            static::addNLClause($vulnerableMDRNLNewQuery);
            $vulnerableMDRNLNew = $vulnerableMDRNLNewQuery->count(DB::raw('distinct users.id'));
        } else {
            $vulnerableMDRNLNew = null;
        }

        list(
            $prisoners,
            $prisonerMen,
            $prisonerWomen,
            $prisonerServices
        ) = $this->getCountsByProfileField('6g6x', ['gG99', 'MuuE'], [], $request, 'contains', null, true);

        if ($new) {
            list(
                $prisonersNew,
                $prisonerMenNew,
                $prisonerWomenNew
            ) = $this->getCountsByProfileField('6g6x', ['gG99', 'MuuE'], [], $request, 'contains', null, true, true);
        } else {
            $prisonersNew = null;
            $prisonerMenNew = null;
            $prisonerWomenNew = null;
        }

        list(
            $prisonersMDR,
            $prisonerMDRMen,
            $prisonerMDRWomen,
            $prisonerMDRServices
        ) = $this->getCountsByProfileField('6g6x', ['gG99', 'MuuE'], [], $request, 'contains', null, true, false, true);

        if ($new) {
            list(
                $prisonersMDRNew,
                $prisonerMDRMenNew,
                $prisonerMDRWomenNew
            ) = $this->getCountsByProfileField(
                '6g6x',
                ['gG99', 'MuuE'],
                [],
                $request,
                'contains',
                null,
                true,
                true,
                true
            );
        } else {
            $prisonersMDRNew = null;
            $prisonerMDRMenNew = null;
            $prisonerMDRWomenNew = null;
        }

        list(
            $migrants,
            $migrantMen,
            $migrantWomen,
            $migrantServices
        ) = $this->getCountsByProfileField('6g6x', ['uQRP', 'YNoK'], [], $request, 'contains', null, true);

        if ($new) {
            list(
                $migrantsNew,
                $migrantMenNew,
                $migrantWomenNew
            ) = $this->getCountsByProfileField('6g6x', ['uQRP', 'YNoK'], [], $request, 'contains', null, true, true);
        } else {
            $migrantsNew = null;
            $migrantMenNew = null;
            $migrantWomenNew = null;
        }


        list(
            $migrantsMDR,
            $migrantMDRMen,
            $migrantMDRWomen,
            $migrantMDRServices
        ) = $this->getCountsByProfileField('6g6x', ['uQRP', 'YNoK'], [], $request, 'contains', null, true, false, true);

        if ($new) {
            list(
                $migrantsMDRNew,
                $migrantMDRMenNew,
                $migrantMDRWomenNew
            ) = $this->getCountsByProfileField(
                '6g6x',
                ['uQRP', 'YNoK'],
                [],
                $request,
                'contains',
                null,
                true,
                true,
                true
            );
        } else {
            $migrantsMDRNew = null;
            $migrantMDRMenNew = null;
            $migrantMDRWomenNew = null;
        }

        list(
            $drugUsers,
            $drugUserMen,
            $drugUserWomen,
            $drugUserServices
        ) = $this->getCountsByProfileField('6g6x', '2AJg', [], $request, 'contains', null, true);

        if ($new) {
            list(
                $drugUsersNew,
                $drugUserMenNew,
                $drugUserWomenNew
            ) = $this->getCountsByProfileField('6g6x', '2AJg', [], $request, 'contains', null, true, true);
        } else {
            $drugUsersNew = null;
            $drugUserMenNew = null;
            $drugUserWomenNew = null;
        }

        list(
            $drugUsersMDR,
            $drugUserMDRMen,
            $drugUserMDRWomen,
            $drugUserMDRServices
        ) = $this->getCountsByProfileField('6g6x', '2AJg', [], $request, 'contains', null, true, false, true);

        if ($new) {
            list(
                $drugUsersMDRNew,
                $drugUserMDRMenNew,
                $drugUserMDRWomenNew
            ) = $this->getCountsByProfileField('6g6x', '2AJg', [], $request, 'contains', null, true, true, true);
        } else {
            $drugUsersMDRNew = null;
            $drugUserMDRMenNew = null;
            $drugUserMDRWomenNew = null;
        }

        list(
            $limited,
            $limitedMen,
            $limitedWomen,
            $limitedServices
        ) = $this->getCountsByProfileField('6g6x', 'dLBE', [], $request, 'contains', null, true);

        if ($new) {
            list(
                $limitedNew,
                $limitedMenNew,
                $limitedWomenNew
            ) = $this->getCountsByProfileField('6g6x', 'dLBE', [], $request, 'contains', null, true, true);
        } else {
            $limitedNew = null;
            $limitedMenNew = null;
            $limitedWomenNew = null;
        }

        list(
            $limitedMDR,
            $limitedMDRMen,
            $limitedMDRWomen,
            $limitedMDRServices
        ) = $this->getCountsByProfileField('6g6x', 'dLBE', [], $request, 'contains', null, true, false, true);

        if ($new) {
            list(
                $limitedMDRNew,
                $limitedMDRMenNew,
                $limitedMDRWomenNew
            ) = $this->getCountsByProfileField('6g6x', 'dLBE', [], $request, 'contains', null, true, true, true);
        } else {
            $limitedMDRNew = null;
            $limitedMDRMenNew = null;
            $limitedMDRWomenNew = null;
        }

        list(
            $children,
            $boys,
            $girls,
            $childrenServices
        ) = $this->getCountsByProfileField('6g6x', 'iNHa', [], $request, 'contains');

        if ($new) {
            list(
                $childrenNew,
                $boysNew,
                $girlsNew
            ) = $this->getCountsByProfileField('6g6x', 'iNHa', [], $request, 'contains', null, false, true);
        } else {
            $childrenNew = null;
            $boysNew = null;
            $girlsNew = null;
        }

        list(
            $childrenDS,
            $boysDS,
            $girlsDS,
            $childrenServicesDS
        ) = $this->getCountsByProfileField('6g6x', 'iNHa', [], $request, 'contains', null, false, false, -1);

        if ($new) {
            list(
                $childrenDSNew,
                $boysDSNew,
                $girlsDSNew
            ) = $this->getCountsByProfileField('6g6x', 'iNHa', [], $request, 'contains', null, false, true, -1);
        } else {
            $childrenDSNew = null;
            $boysDSNew = null;
            $girlsDSNew = null;
        }

        list(
            $childrenMDR,
            $boysMDR,
            $girlsMDR,
            $childrenServicesMDR
        ) = $this->getCountsByProfileField('6g6x', 'iNHa', [], $request, 'contains', null, false, false, true);

        if ($new) {
            list(
                $childrenMDRNew,
                $boysMDRNew,
                $girlsMDRNew
            ) = $this->getCountsByProfileField('6g6x', 'iNHa', [], $request, 'contains', null, false, true, true);
        } else {
            $childrenMDRNew = null;
            $boysMDRNew = null;
            $girlsMDRNew = null;
        }

        list(
            $show,
            $showBoys,
            $showGirls,
            $showServices
        ) = $this->getCountsByProfileField('6g6x', 'iNHa', [], $request, 'contains', 'шоу');

        if ($new) {
            list(
                $showNew,
                $showBoysNew,
                $showGirlsNew,
                $showServicesNew
                ) = $this->getCountsByProfileField('6g6x', 'iNHa', [], $request, 'contains', 'шоу', false, true);
        } else {
            $showNew = null;
            $showBoysNew = null;
            $showGirlsNew = null;
            $showServicesNew = null;
        }

        list(
            $art,
            $artBoys,
            $artGirls,
            $artServices
        ) = $this->getCountsByProfileField('6g6x', 'iNHa', [367, 371, 375, 376], $request, 'contains', 'арт');

        if ($new) {
            list(
                $artNew,
                $artBoysNew,
                $artGirlsNew
            ) = $this->getCountsByProfileField(
                '6g6x',
                'iNHa',
                [367, 371, 375, 376],
                $request,
                'contains',
                'арт',
                false,
                true
            );
        } else {
            $artNew = null;
            $artBoysNew = null;
            $artGirlsNew = null;
        }

        list(
            $parents,
            $parentMen,
            $parentWomen,
            $parentServices
        ) = $this->getCountsByProfileField('6g6x', 'aSu3', [], $request, 'contains');

        if ($new) {
            list(
                $parentsNew,
                $parentMenNew,
                $parentWomenNew
            ) = $this->getCountsByProfileField(
                '6g6x',
                'aSu3',
                [],
                $request,
                'contains',
                null,
                false,
                true,
                false,
                6,
                false,
                false,
                null,
                false,
                null,
                false,
                true
            );
        } else {
            $parentsNew = null;
            $parentMenNew = null;
            $parentWomenNew = null;
        }

        $fullChildrenQuery = User::where('users.roles', 'like', '%client%')
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
            }, '>=', 3);

        $this->addDocumentsJoin($fullChildrenQuery);

        if ($request->from) {
            $fullChildrenQuery->where('d.data->GWQS', '>=', $request->from);
        }

        if ($request->till) {
            $fullChildrenQuery->where('d.data->GWQS', '<=', $request->till);
        }

        if ($request->users) {
            $fullChildrenQuery->whereHas('activities', function ($query) use ($request) {
                $query->where('project_id', 6)
                    ->whereHas('allUsers', function ($query) use ($request) {
                        $query->whereIn('user_id', $request->users);
                    });
            });
        }

        $fullChildren = $fullChildrenQuery->count(DB::raw('distinct users.id'));

        $fullChildrenBoysQuery = clone $fullChildrenQuery;
        $fullChildrenBoysQuery->whereNotNull('d.data->f8Bs')
            ->where(function ($query) {
                $query->whereJsonContains('d.data->f8Bs', 'GZkX')
                    ->orWhereJsonContains('d.data->f8Bs', '9Dsr');
            });

        $fullChildrenBoys = $fullChildrenBoysQuery->count(DB::raw('distinct users.id'));
        $fullChildrenBoysNew = 0;

        $fullChildrenGirlsQuery = clone $fullChildrenQuery;
        $fullChildrenGirlsQuery->whereNotNull('d.data->f8Bs')
            ->where(function ($query) {
                $query->whereJsonContains('d.data->f8Bs', 'mtD8')
                    ->orWhereJsonContains('d.data->f8Bs', 'fEG9');
            });

        $fullChildrenGirls = $fullChildrenGirlsQuery->count(DB::raw('distinct users.id'));
        $fullChildrenGirlsNew = 0;

        $fullChildrenServicesQuery = Activity::whereHas('users', function ($query) {
            $query->where('users.roles', 'like', '%client%')
                ->whereHas('projects', function ($query) {
                    $query->where('project_id', 6);
                })
                ->whereJsonDoesntContain('d.data->6g6x', 'ab8e')
                ->whereJsonContains('d.data->6g6x', 'iNHa')
                ->whereNotNull('d.data->kB3w')
                ->whereNotNull('d.data->g459')
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

            $this->addDocumentsJoin($query);
        });

        if ($request->from) {
            $fullChildrenServicesQuery->where('start_date', '>=', $request->from);
        }

        if ($request->till) {
            $fullChildrenServicesQuery->where('start_date', '<=', $request->till);
        }

        $fullChildrenServices = $fullChildrenServicesQuery->count();

        $fullChildrenServicesNew = 0;

        $fullChildrenQuery = User::where('users.roles', 'like', '%client%')
            ->whereHas('projects', function ($query) {
                $query->where('project_id', 6);
            })
            ->whereJsonDoesntContain('d.data->6g6x', 'ab8e')
            ->whereJsonContains('d.data->6g6x', 'iNHa')
            ->whereNotNull('d.data->kB3w')
            ->with([
                'activities' => function ($query) use ($request) {
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
                'activities2' => function ($query) use ($request) {
                    $query->where('project_id', 6)
                        ->whereIn('user_activity.part_id', [367, 371, 375, 376]);
                },
                'relatedUsers.activities' => function ($query) use ($request) {
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
                'relatedUsers.activities2' => function ($query) use ($request) {
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
                'relatedUsers.activities5' => function ($query) use ($request) {
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
                'relatedUsers.activities6' => function ($query) use ($request) {
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

        $this->addDocumentsJoin($fullChildrenQuery);
        $fullChildrenQuery->groupBy('users.id');

        if ($request->users && isset($request->users[0]->id) && $request->users[0] != 72) {
            $fullChildrenQuery->whereHas('activities', function ($query) use ($request) {
                $query->where('project_id', 6)
                    ->whereHas('allUsers', function ($query) use ($request) {
                        $query->whereIn('user_id', $request->users);
                    });
            });
        }

        if ($request->till) {
            $fullChildrenQuery->where('d.data->kB3w', '<=', $request->till);
        }

        $fullChildren2 = 0;
        $fullChildren2Boys = 0;
        $fullChildren2Girls = 0;
        $fullChildrenNew2 = 0;

        $fullChildren3 = 0;
        $fullChildren3Boys = 0;
        $fullChildren3Girls = 0;
        $fullChildrenNew3 = 0;

        $fullChildren4 = 0;
        $fullChildren4Boys = 0;
        $fullChildren4Girls = 0;
        $fullChildrenNew4 = 0;

        $fullChildren5 = 0;
        $fullChildren5_1 = 0;
        $fullChildren5_2 = 0;
        $fullChildren5_3 = 0;
        $fullChildren5_4 = 0;
        $fullChildren6 = 0;
        $fullChildren6_1 = 0;
        $fullChildren6_2 = 0;
        $fullChildren6_3 = 0;
        $fullChildren6_4 = 0;
        $fullChildren7 = 0;
        $fullChildren8 = 0;
        $fullChildren9 = 0;
        $fullChildren10 = 0;
        $fullChildren11 = 0;
        $fullChildren11Boys = 0;
        $fullChildren11Girls = 0;
        $fullChildren11Services = collect();
        $fullChildren11Services1 = collect();
        $fullChildren11Services2 = collect();
        $fullChildren12 = 0;
        $fullChildren12Boys = 0;
        $fullChildren12Girls = 0;
        $fullChildren12Services = collect();
        $fullChildren12Services1 = collect();
        $fullChildren12Services2 = collect();
        $fullChildren13 = 0;
        $fullChildren13Boys = 0;
        $fullChildren13Girls = 0;
        $fullChildren13Services = collect();
        $fullChildren14 = 0;
        $fullChildren14Boys = 0;
        $fullChildren14Girls = 0;
        $fullChildren14Services = collect();
        $fullChildren15 = 0;
        $fullChildren16 = 0;
        $fullChildren16Services = collect();
        $fullChildren16Services1 = collect();
        $fullChildren16Services2 = collect();
        $fullChildren17 = 0;
        $fullChildren17Services = collect();
        $fullChildren17Services1 = collect();
        $fullChildren17Services2 = collect();
        $fullChildren18 = 0;
        $fullChildren18Services = collect();
        $fullChildren18Services1 = collect();
        $fullChildren18Services2 = collect();
        $fullChildren19 = 0;
        $fullChildren19Services = collect();
        $fullChildren19Services1 = collect();
        $fullChildren19Services2 = collect();
        $fullChildren20Services = collect();
        $fullChildren20 = 0;
        $fullChildren21Services = collect();
        $fullChildren21 = 0;
        $fullChildren22Services = collect();
        $fullChildren22 = 0;
        $fullChildren23Services = collect();
        $fullChildren23 = 0;

        foreach ($fullChildrenQuery->get() as $item) {
            if (empty($item->profile[6][0]->data['kB3w'])) {
                continue;
            }

            $from = $request->from ? Carbon::parse($request->from) : null;
            $inProjectFrom = Carbon::parse($item->profile[6][0]->data['kB3w']);
            $treatmentStart = empty($item->profile[6][0]->data['Q5xs']) ? null :
                Carbon::parse($item->profile[6][0]->data['Q5xs']);
            $serviceStart = $treatmentStart && $treatmentStart > $inProjectFrom ? $treatmentStart : $inProjectFrom;

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

            $end->subSecond();

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

            if (!empty($item->profile[6][0]->data['Rchw']) &&
                count($soc) && count($psy) &&
                count($soc) >= $months && count($psy) >= $months
            ) {
                $fullChildren2++;

                if (!empty($item->profile[6][0]->data['f8Bs'])) {
                    if (in_array('GZkX', $item->profile[6][0]->data['f8Bs']) ||
                        in_array('9Dsr', $item->profile[6][0]->data['f8Bs'])
                    ) {
                        $fullChildren2Boys++;
                    }

                    if (in_array('mtD8', $item->profile[6][0]->data['f8Bs']) ||
                        in_array('fEG9', $item->profile[6][0]->data['f8Bs'])
                    ) {
                        $fullChildren2Girls++;
                    }
                }

                if (!empty($item->profile[6][0]->data['nt6j'])) {
                    $fullChildren3++;

                    if (!empty($item->profile[6][0]->data['f8Bs'])) {
                        if (in_array('GZkX', $item->profile[6][0]->data['f8Bs']) ||
                            in_array('9Dsr', $item->profile[6][0]->data['f8Bs'])
                        ) {
                            $fullChildren3Boys++;
                        }

                        if (in_array('mtD8', $item->profile[6][0]->data['f8Bs']) ||
                            in_array('fEG9', $item->profile[6][0]->data['f8Bs'])
                        ) {
                            $fullChildren3Girls++;
                        }
                    }
                }
            }

            $now = $request->till ? Carbon::parse($request->till) : now();
            $now->floorDay();

            if (empty($item->profile[6][0]->data['nt6j']) ||
                ($now <= Carbon::parse($item->profile[6][0]->data['nt6j']) &&
                    $now >= Carbon::parse($item->profile[6][0]->data['kB3w']))
            ) {
                $now = $now->format('Ym');

                if (!empty($item->profile[6][0]->data['g459']) && empty($item->profile[6][0]->data['GWQS'])) {
                    $fullChildren9++;

                    if (!empty($item->profile[6][0]->data['Rchw'])) {
                        if (empty($soc[$now])) {
                            $fullChildren5++;

                            if (!empty($item->profile[6][0]->data['8Src'])) {
                                if ($item->profile[6][0]->data['8Src'] == 'u2Ea') {
                                    $fullChildren5_1++;
                                }

                                if ($item->profile[6][0]->data['8Src'] == '6KMj') {
                                    $fullChildren5_2++;
                                }

                                if ($item->profile[6][0]->data['8Src'] == '98GN') {
                                    $fullChildren5_3++;
                                }

                                if ($item->profile[6][0]->data['8Src'] == 'nRns') {
                                    $fullChildren5_4++;
                                }
                            }
                        }

                        if (empty($psy[$now])) {
                            $fullChildren6++;

                            if (!empty($item->profile[6][0]->data['8Src'])) {
                                if ($item->profile[6][0]->data['8Src'] == 'u2Ea') {
                                    $fullChildren6_1++;
                                }

                                if ($item->profile[6][0]->data['8Src'] == '6KMj') {
                                    $fullChildren6_2++;
                                }

                                if ($item->profile[6][0]->data['8Src'] == '98GN') {
                                    $fullChildren6_3++;
                                }

                                if ($item->profile[6][0]->data['8Src'] == 'nRns') {
                                    $fullChildren6_4++;
                                }
                            }
                        }
                    }
                }

                if (!empty($item->profile[6][0]->data['8Fg3'])) {
                    $fullChildren10++;

                    if (!empty($item->profile[6][0]->data['Rchw'])) {
                        if (empty($soc[$now])) {
                            $fullChildren7++;
                        }

                        if (empty($psy[$now])) {
                            $fullChildren8++;
                        }
                    }
                }
            }

            if (!empty($item->profile[6][0]->data['GWQS'])
                && (!$request->till || $request->till >= $item->profile[6][0]->data['GWQS'])
            ) {
                $psy2 = $item->activities2
                    ->concat($item->relatedUsers->pluck('activities2')->flatten())
                    ->filter(function ($activity) use ($item) {
                        return $item->profile[6][0]->data['kB3w'] <= $activity->start_date &&
                                $item->profile[6][0]->data['GWQS'] >= $activity->start_date;
                    })
                    ->countBy(function ($item) {
                        return date('Ym', strtotime($item->start_date));
                    });

                $soc2 = empty($item->profile[6][0]->data['GWQS']) ? [] : $item->activities
                    ->concat($item->relatedUsers->pluck('activities')->flatten())
                    ->filter(function ($activity) use ($item) {
                        return $item->profile[6][0]->data['kB3w'] <= $activity->start_date &&
                                $item->profile[6][0]->data['GWQS'] >= $activity->start_date;
                    })
                    ->countBy(function ($item) {
                        return date('Ym', strtotime($item->start_date));
                    });

                $end2 = Carbon::parse($item->profile[6][0]->data['GWQS']);

                if ($end2->day < 6) {
                    $end2->floorMonth();
                } else {
                    $end2->ceilMonth();
                }

                $months2 = (int) $start->floatDiffInMonths($end2);

                if (!empty($item->profile[6][0]->data['Rchw']) &&
                    count($soc2) && count($psy2) &&
                    count($soc2) >= $months2 && count($psy2) >= $months2
                ) {
                    $fullChildren4++;

                    if (!empty($item->profile[6][0]->data['f8Bs'])) {
                        if (in_array('GZkX', $item->profile[6][0]->data['f8Bs']) ||
                            in_array('9Dsr', $item->profile[6][0]->data['f8Bs'])
                        ) {
                            $fullChildren4Boys++;
                        }

                        if (in_array('mtD8', $item->profile[6][0]->data['f8Bs']) ||
                            in_array('fEG9', $item->profile[6][0]->data['f8Bs'])
                        ) {
                            $fullChildren4Girls++;
                        }
                    }
                }
            }

            if (!empty($item->profile[6][0]->data['8Fg3'])
                && (!$request->till || $request->till >= $item->profile[6][0]->data['8Fg3'])
                && (!$request->from || empty($item->profile[6][0]->data['nt6j'])
                    || $request->from <= $item->profile[6][0]->data['nt6j'])
            ) {
                $start3 = $request->from && $request->from > $item->profile[6][0]->data['8Fg3']
                    ? $request->from : $item->profile[6][0]->data['8Fg3'];
                $end3 = $request->till && (empty($item->profile[6][0]->data['nt6j'])
                    || $request->till < $item->profile[6][0]->data['nt6j'])
                        ? $request->till : ($item->profile[6][0]->data['nt6j'] ?? null);

                $psy3 = $item->activities2
                    ->concat($item->relatedUsers->pluck('activities2')->flatten())
                    ->filter(function ($activity) use ($start3, $end3) {
                        return $activity->start_date >= $start3 && $activity->start_date <= $end3;
                    });

                $psy3ids = $psy3->pluck('id');

                $psy3ids1 = $item->activities2
                    ->filter(function ($activity) use ($start3, $end3) {
                        return $activity->start_date >= $start3 && $activity->start_date <= $end3;
                    })
                    ->pluck('id');

                $psy3ids2 = $item->relatedUsers->pluck('activities2')->flatten()
                    ->filter(function ($activity) use ($start3, $end3) {
                        return $activity->start_date >= $start3 && $activity->start_date <= $end3;
                    })
                    ->pluck('id');

                $psy3 = $psy3->countBy(function ($item) {
                    return date('Ym', strtotime($item->start_date));
                });

                $soc3 = $item->activities
                    ->concat($item->relatedUsers->pluck('activities')->flatten())
                    ->filter(function ($activity) use ($start3, $end3) {
                        return $activity->start_date >= $start3 && $activity->start_date <= $end3;
                    });

                $soc3ids = $soc3->pluck('id');

                $soc3ids1 = $item->activities
                    ->filter(function ($activity) use ($start3, $end3) {
                        return $activity->start_date >= $start3 && $activity->start_date <= $end3;
                    })
                    ->pluck('id');

                $soc3ids2 = $item->relatedUsers->pluck('activities')->flatten()
                    ->filter(function ($activity) use ($start3, $end3) {
                        return $activity->start_date >= $start3 && $activity->start_date <= $end3;
                    })
                    ->pluck('id');

                $soc3 = $soc3->countBy(function ($item) {
                    return date('Ym', strtotime($item->start_date));
                });

                $soc6 = $item->activities7
                    ->concat($item->relatedUsers->pluck('activities7')->flatten())
                    ->filter(function ($activity) use ($start3, $end3) {
                        return $activity->start_date >= $start3 && $activity->start_date <= $end3;
                    });

                $soc6ids = $soc6->pluck('id');

                $soc6ids1 = $item->activities7
                    ->filter(function ($activity) use ($start3, $end3) {
                        return $activity->start_date >= $start3 && $activity->start_date <= $end3;
                    })
                    ->pluck('id');

                $soc6ids2 = $item->relatedUsers->pluck('activities7')->flatten()
                    ->filter(function ($activity) use ($start3, $end3) {
                        return $activity->start_date >= $start3 && $activity->start_date <= $end3;
                    })
                    ->pluck('id');

                $soc6 = $soc6->countBy(function ($item) {
                    return date('Ym', strtotime($item->start_date));
                });

                $leg3 = $item->activities5
                    ->filter(function ($activity) use ($start3, $end3) {
                        return $activity->start_date >= $start3 && $activity->start_date <= $end3;
                    });

                $leg3ids = $leg3->pluck('id');

                $leg3ids1 = $item->activities5
                    ->filter(function ($activity) use ($start3, $end3) {
                        return $activity->start_date >= $start3 && $activity->start_date <= $end3;
                    })
                    ->pluck('id');

                $leg3ids2 = $item->relatedUsers->pluck('activities5')->flatten()
                    ->filter(function ($activity) use ($start3, $end3) {
                        return $activity->start_date >= $start3 && $activity->start_date <= $end3;
                    })
                    ->pluck('id');

                $phthi3 = $item->activities6
                    ->filter(function ($activity) use ($start3, $end3) {
                        return $activity->start_date >= $start3 && $activity->start_date <= $end3;
                    });

                $phthi3ids = $phthi3->pluck('id');

                $phthi3ids1 = $item->activities6
                    ->filter(function ($activity) use ($start3, $end3) {
                        return $activity->start_date >= $start3 && $activity->start_date <= $end3;
                    })
                    ->pluck('id');

                $phthi3ids2 = $item->relatedUsers->pluck('activities6')->flatten()
                    ->filter(function ($activity) use ($start3, $end3) {
                        return $activity->start_date >= $start3 && $activity->start_date <= $end3;
                    })
                    ->pluck('id');

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

                if (count($soc3) || count($psy3)) {
                    if (!empty($item->profile[6][0]->data['Rchw']) &&
                        count($soc3) >= $months3 && count($psy3) >= $months3
                    ) {
                        $fullChildren13++;

                        $fullChildren13Services = $fullChildren13Services->concat($psy3ids)->concat($soc3ids);

                        if (in_array('GZkX', $item->profile[6][0]->data['f8Bs']) ||
                            in_array('9Dsr', $item->profile[6][0]->data['f8Bs'])
                        ) {
                            $fullChildren13Boys++;
                        }

                        if (in_array('mtD8', $item->profile[6][0]->data['f8Bs']) ||
                            in_array('fEG9', $item->profile[6][0]->data['f8Bs'])
                        ) {
                            $fullChildren13Girls++;
                        }
                    }
                }


                if (count($soc6) || count($psy3) || count($leg3) || count($phthi3)) {
                    $fullChildren11++;

                    $fullChildren11Services = $fullChildren11Services
                        ->concat($soc6ids)
                        ->concat($psy3ids)
                        ->concat($leg3ids)
                        ->concat($phthi3ids);

                    $fullChildren11Services1 = $fullChildren11Services1
                        ->concat($soc6ids1)
                        ->concat($psy3ids1)
                        ->concat($leg3ids1)
                        ->concat($phthi3ids1);

                    $fullChildren11Services2 = $fullChildren11Services2
                        ->concat($soc6ids2)
                        ->concat($psy3ids2)
                        ->concat($leg3ids2)
                        ->concat($phthi3ids2);

                    if (!empty($item->profile[6][0]->data['f8Bs'])) {
                        if (in_array('GZkX', $item->profile[6][0]->data['f8Bs']) ||
                            in_array('9Dsr', $item->profile[6][0]->data['f8Bs'])
                        ) {
                            $fullChildren11Boys++;
                        }

                        if (in_array('mtD8', $item->profile[6][0]->data['f8Bs']) ||
                            in_array('fEG9', $item->profile[6][0]->data['f8Bs'])
                        ) {
                            $fullChildren11Girls++;
                        }
                    }
                }

                if (count($soc6)) {
                    $fullChildren18++;
                    $fullChildren18Services = $fullChildren18Services->concat($soc6ids);
                    $fullChildren18Services1 = $fullChildren18Services1->concat($soc6ids1);
                    $fullChildren18Services2 = $fullChildren18Services2->concat($soc6ids2);
                }

                if (count($psy3)) {
                    $fullChildren19++;
                    $fullChildren19Services = $fullChildren19Services->concat($psy3ids);
                    $fullChildren19Services1 = $fullChildren19Services1->concat($psy3ids1);
                    $fullChildren19Services2 = $fullChildren19Services2->concat($psy3ids2);
                }

                if (count($leg3)) {
                    $fullChildren20++;
                    $fullChildren20Services = $fullChildren20Services->concat($leg3ids);
                }

                if (count($phthi3)) {
                    $fullChildren21++;
                    $fullChildren21Services = $fullChildren21Services->concat($phthi3ids);
                }
            }

            if (!empty($item->profile[6][0]->data['g459'])
                && (!$request->till || $request->till >= $item->profile[6][0]->data['kB3w'])
                && (!$request->from || empty($item->profile[6][0]->data['GWQS'])
                    || $request->from <= $item->profile[6][0]->data['GWQS'])
            ) {
                $start4 = $request->from && $request->from > $item->profile[6][0]->data['kB3w']
                    ? $request->from : $item->profile[6][0]->data['kB3w'];
                $end4 = $request->till && (empty($item->profile[6][0]->data['GWQS'])
                    || $request->till < $item->profile[6][0]->data['GWQS'])
                        ? $request->till : ($item->profile[6][0]->data['GWQS'] ?? null);

                $psy4 = $item->activities4
                    ->concat($item->relatedUsers->pluck('activities4')->flatten())
                    ->filter(function ($activity) use ($start4, $end4) {
                        return $activity->start_date >= $start4 && $activity->start_date <= $end4;
                    });

                $psy4ids = $psy4->pluck('id');

                $psy4ids1 = $item->activities4
                    ->filter(function ($activity) use ($start4, $end4) {
                        return $activity->start_date >= $start4 && $activity->start_date <= $end4;
                    })
                    ->pluck('id');

                $psy4ids2 = $item->relatedUsers->pluck('activities4')->flatten()
                    ->filter(function ($activity) use ($start4, $end4) {
                        return $activity->start_date >= $start4 && $activity->start_date <= $end4;
                    })
                    ->pluck('id');

                $psy4 = $psy4->countBy(function ($item) {
                    return date('Ym', strtotime($item->start_date));
                });

                $soc4 = $item->activities3
                    ->concat($item->relatedUsers->pluck('activities3')->flatten())
                    ->filter(function ($activity) use ($start4, $end4) {
                        return $activity->start_date >= $start4 && $activity->start_date <= $end4;
                    });

                $soc4ids = $soc4->pluck('id');

                $soc4ids1 = $item->activities3
                    ->filter(function ($activity) use ($start4, $end4) {
                        return $activity->start_date >= $start4 && $activity->start_date <= $end4;
                    })
                    ->pluck('id');

                $soc4ids2 = $item->relatedUsers->pluck('activities3')->flatten()
                    ->filter(function ($activity) use ($start4, $end4) {
                        return $activity->start_date >= $start4 && $activity->start_date <= $end4;
                    })
                    ->pluck('id');

                $soc4 = $soc4->countBy(function ($item) {
                    return date('Ym', strtotime($item->start_date));
                });

                $soc5 = $item->activities7
                    ->concat($item->relatedUsers->pluck('activities7')->flatten())
                    ->filter(function ($activity) use ($start4, $end4) {
                        return $activity->start_date >= $start4 && $activity->start_date <= $end4;
                    });

                $soc5ids = $soc5->pluck('id');

                $soc5ids1 = $item->activities7
                    ->filter(function ($activity) use ($start4, $end4) {
                        return $activity->start_date >= $start4 && $activity->start_date <= $end4;
                    })
                    ->pluck('id');

                $soc5ids2 = $item->relatedUsers->pluck('activities7')->flatten()
                    ->filter(function ($activity) use ($start4, $end4) {
                        return $activity->start_date >= $start4 && $activity->start_date <= $end4;
                    })
                    ->pluck('id');

                $soc5 = $soc5->countBy(function ($item) {
                    return date('Ym', strtotime($item->start_date));
                });

                $leg4 = $item->activities5
                    ->filter(function ($activity) use ($start4, $end4) {
                        return $activity->start_date >= $start4 && $activity->start_date <= $end4;
                    });

                $leg4ids = $leg4->pluck('id');

                $leg4ids1 = $item->activities5
                    ->filter(function ($activity) use ($start4, $end4) {
                        return $activity->start_date >= $start4 && $activity->start_date <= $end4;
                    })
                    ->pluck('id');

                $leg4ids2 = $item->relatedUsers->pluck('activities5')->flatten()
                    ->filter(function ($activity) use ($start4, $end4) {
                        return $activity->start_date >= $start4 && $activity->start_date <= $end4;
                    })
                    ->pluck('id');

                $phthi4 = $item->activities6
                    ->filter(function ($activity) use ($start4, $end4) {
                        return $activity->start_date >= $start4 && $activity->start_date <= $end4;
                    });

                $phthi4ids = $phthi4->pluck('id');

                $phthi4ids1 = $item->activities6
                    ->filter(function ($activity) use ($start4, $end4) {
                        return $activity->start_date >= $start4 && $activity->start_date <= $end4;
                    })
                    ->pluck('id');

                $phthi4ids2 = $item->relatedUsers->pluck('activities6')->flatten()
                    ->filter(function ($activity) use ($start4, $end4) {
                        return $activity->start_date >= $start4 && $activity->start_date <= $end4;
                    })
                    ->pluck('id');

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

                if (count($soc4) || count($psy4)) {
                    if (!empty($item->profile[6][0]->data['Rchw'])) {
                        if (count($soc4) >= $months4 && count($psy4) >= $months4) {
                            $fullChildren14++;

                            $fullChildren14Services = $fullChildren14Services->concat($psy4ids)->concat($soc4ids);

                            if (in_array('GZkX', $item->profile[6][0]->data['f8Bs']) ||
                                in_array('9Dsr', $item->profile[6][0]->data['f8Bs'])
                            ) {
                                $fullChildren14Boys++;
                            }

                            if (in_array('mtD8', $item->profile[6][0]->data['f8Bs']) ||
                                in_array('fEG9', $item->profile[6][0]->data['f8Bs'])
                            ) {
                                $fullChildren14Girls++;
                            }
                        }

                        if ($soc4->intersectByKeys($psy4)->count()) {
                            $fullChildren15++;
                        }
                    }
                }

                if (count($soc5) || count($psy4) || count($leg4) || count($phthi4)) {
                    $fullChildren12++;

                    $fullChildren12Services = $fullChildren12Services
                        ->concat($soc5ids)
                        ->concat($psy4ids)
                        ->concat($leg4ids)
                        ->concat($phthi4ids);

                    $fullChildren12Services1 = $fullChildren12Services1
                        ->concat($soc5ids1)
                        ->concat($psy4ids1)
                        ->concat($leg4ids1)
                        ->concat($phthi4ids1);

                    $fullChildren12Services2 = $fullChildren12Services2
                        ->concat($soc5ids2)
                        ->concat($psy4ids2)
                        ->concat($leg4ids2)
                        ->concat($phthi4ids2);

                    if (!empty($item->profile[6][0]->data['f8Bs'])) {
                        if (in_array('GZkX', $item->profile[6][0]->data['f8Bs']) ||
                            in_array('9Dsr', $item->profile[6][0]->data['f8Bs'])
                        ) {
                            $fullChildren12Boys++;
                        }

                        if (in_array('mtD8', $item->profile[6][0]->data['f8Bs']) ||
                            in_array('fEG9', $item->profile[6][0]->data['f8Bs'])
                        ) {
                            $fullChildren12Girls++;
                        }
                    }
                }

                if (count($soc5)) {
                    $fullChildren16++;
                    $fullChildren16Services = $fullChildren16Services->concat($soc5ids);
                    $fullChildren16Services1 = $fullChildren16Services1->concat($soc5ids1);
                    $fullChildren16Services2 = $fullChildren16Services2->concat($soc5ids2);
                }

                if (count($psy4)) {
                    $fullChildren17++;
                    $fullChildren17Services = $fullChildren17Services->concat($psy4ids);
                    $fullChildren17Services1 = $fullChildren17Services1->concat($psy4ids1);
                    $fullChildren17Services2 = $fullChildren17Services2->concat($psy4ids2);
                }

                if (count($leg4)) {
                    $fullChildren22++;
                    $fullChildren22Services = $fullChildren22Services->concat($leg4ids);
                }

                if (count($phthi4)) {
                    $fullChildren23++;
                    $fullChildren23Services = $fullChildren23Services->concat($phthi4ids);
                }
            }
        }

        $fullChildren11Services = $fullChildren11Services->unique()->count();
        $fullChildren11Services1 = $fullChildren11Services1->unique()->count();
        $fullChildren11Services2 = $fullChildren11Services2->unique()->count();
        $fullChildren12Services = $fullChildren12Services->unique()->count();
        $fullChildren12Services1 = $fullChildren12Services1->unique()->count();
        $fullChildren12Services2 = $fullChildren12Services2->unique()->count();
        $fullChildren13Services = $fullChildren13Services->unique()->count();
        $fullChildren14Services = $fullChildren14Services->unique()->count();
        $fullChildren16Services = $fullChildren16Services->unique()->count();
        $fullChildren16Services1 = $fullChildren16Services1->unique()->count();
        $fullChildren16Services2 = $fullChildren16Services2->unique()->count();
        $fullChildren17Services = $fullChildren17Services->unique()->count();
        $fullChildren17Services1 = $fullChildren17Services1->unique()->count();
        $fullChildren17Services2 = $fullChildren17Services2->unique()->count();
        $fullChildren18Services = $fullChildren18Services->unique()->count();
        $fullChildren18Services1 = $fullChildren18Services1->unique()->count();
        $fullChildren18Services2 = $fullChildren18Services2->unique()->count();
        $fullChildren19Services = $fullChildren19Services->unique()->count();
        $fullChildren19Services1 = $fullChildren19Services1->unique()->count();
        $fullChildren19Services2 = $fullChildren19Services2->unique()->count();
        $fullChildren20Services = $fullChildren20Services->unique()->count();
        $fullChildren21Services = $fullChildren21Services->unique()->count();
        $fullChildren22Services = $fullChildren22Services->unique()->count();
        $fullChildren23Services = $fullChildren23Services->unique()->count();

        // $this->addNewClientClause($fullChildrenQuery, $request);
        $fullChildrenNew = 0; //$fullChildrenQuery->count(DB::raw('distinct users.id'));

        list(
            $legal,
            $legalMen,
            $legalWomen,
            $legalServices
        ) = $this->getCountsByProfileField(null, null, [366, 370], $request);

        list(
            $legalNL,
            $legalMenNL,
            $legalWomenNL,
            $totalLegalServicesNL
        ) = $this->getCountsByProfileField(
            null,
            null,
            [366, 370],
            $request,
            null,
            null,
            false,
            false,
            false,
            6,
            false,
            false,
            null,
            true
        );

        if ($new) {
            list(
                $legalNew,
                $legalMenNew,
                $legalWomenNew,
                $legalServicesNew
            ) = $this->getCountsByProfileField(null, null, [366, 370], $request, null, null, false, true);

            list(
                $legalNLNew,
                $legalMenNLNew,
                $legalWomenNLNew,
                $totalLegalServicesNLNew
            ) = $this->getCountsByProfileField(
                null,
                null,
                [366, 370],
                $request,
                null,
                null,
                false,
                true,
                false,
                6,
                false,
                false,
                null,
                true
            );
        } else {
            $legalNew = null;
            $legalNLNew = null;
            $legalServicesNew = null;
        }

        list(
            $legalVulnerable,
            $legalVulnerableMen,
            $legalVulnerableWomen,
            $legalVulnerableServices
        ) = $this->getCountsByProfileField(null, null, [366, 370], $request, null, null, true);

        $legalVulnerableServicesNLQuery = $this->getServicesCountByProfileFieldQuery(
            null,
            null,
            [366, 370],
            $request,
            null,
            null,
            true,
            false,
            6,
            false,
            false,
            6,
            function ($query) {
                $this->getServicesCountByProfileFieldQueryUsersClause(
                    null,
                    null,
                    null,
                    [366, 370],
                    true,
                    false,
                    6
                )($query);

                static::addNLClause($query);
            }
        );

        $legalVulnerableServicesNL = $legalVulnerableServicesNLQuery->count(DB::raw('distinct id'));

        if ($new) {
            list(
                $legalVulnerableNew,
                $legalVulnerableMenNew,
                $legalVulnerableWomenNew,
                $legalVulnerableServicesNew
            ) = $this->getCountsByProfileField(null, null, [366, 370], $request, null, null, true, true);
        } else {
            $legalVulnerableNew = null;
            $legalVulnerableMenNew = null;
            $legalVulnerableWomenNew = null;
            $legalVulnerableServicesNew = null;
        }

        list(
            $legalVulnerableMDR,
            $legalVulnerableMDRMen,
            $legalVulnerableMDRWomen,
            $legalVulnerableMDRServices
        ) = $this->getCountsByProfileField(null, null, [366, 370], $request, null, null, true, false, true);

        if ($new) {
            list(
                $legalVulnerableMDRNew,
                $legalVulnerableMDRMenNew,
                $legalVulnerableMDRWomenNew,
                $legalVulnerableMDRServicesNew
            ) = $this->getCountsByProfileField(null, null, [366, 370], $request, null, null, true, true, true);
        } else {
            $legalVulnerableMDRNew = null;
            $legalVulnerableMDRMenNew = null;
            $legalVulnerableMDRWomenNew = null;
            $legalVulnerableMDRServicesNew = null;
        }

        list(
            $prisonersLegal,
            $prisonersLegalMen,
            $prisonersLegalWomen,
            $prisonersLegalServices,
        ) = $this->getCountsByProfileField('6g6x', ['gG99', 'MuuE'], [366, 370], $request, 'contains', null, true);

        if ($new) {
            list(
                $prisonersLegalNew,
                $prisonersLegalMenNew,
                $prisonersLegalWomenNew,
                $prisonersLegalServicesNew,
            ) = $this->getCountsByProfileField(
                '6g6x',
                ['gG99', 'MuuE'],
                [366, 370],
                $request,
                'contains',
                null,
                true,
                true
            );
        } else {
            $prisonersLegalNew = null;
            $prisonersLegalMenNew = null;
            $prisonersLegalWomenNew = null;
            $prisonersLegalServicesNew = null;
        }

        list(
            $prisonersLegalMDR,
            $prisonersLegalMDRMen,
            $prisonersLegalMDRWomen,
            $prisonersLegalMDRServices,
        ) = $this->getCountsByProfileField(
            '6g6x',
            ['gG99', 'MuuE'],
            [366, 370],
            $request,
            'contains',
            null,
            true,
            false,
            true
        );

        if ($new) {
            list(
                $prisonersLegalMDRNew,
                $prisonersLegalMDRMenNew,
                $prisonersLegalMDRWomenNew,
                $prisonersLegalMDRServicesNew,
            ) = $this->getCountsByProfileField(
                '6g6x',
                ['gG99', 'MuuE'],
                [366, 370],
                $request,
                'contains',
                null,
                true,
                true,
                true
            );
        } else {
            $prisonersLegalMDRNew = null;
            $prisonersLegalMDRMenNew = null;
            $prisonersLegalMDRWomenNew = null;
            $prisonersLegalMDRServicesNew = null;
        }

        list(
            $migrantLegal,
            $migrantLegalMen,
            $migrantLegalWomen,
            $migrantLegalServices,
        ) = $this->getCountsByProfileField('6g6x', ['uQRP', 'YNoK'], [366, 370], $request, 'contains', null, true);

        if ($new) {
            list(
                $migrantLegalNew,
                $migrantLegalMenNew,
                $migrantLegalWomenNew,
                $migrantLegalServicesNew,
            ) = $this->getCountsByProfileField(
                '6g6x',
                ['uQRP', 'YNoK'],
                [366, 370],
                $request,
                'contains',
                null,
                true,
                true
            );
        } else {
            $migrantLegalNew = null;
            $migrantLegalMenNew = null;
            $migrantLegalWomenNew = null;
            $migrantLegalServicesNew = null;
        }

        list(
            $migrantLegalMDR,
            $migrantLegalMDRMen,
            $migrantLegalMDRWomen,
            $migrantLegalMDRServices,
        ) = $this->getCountsByProfileField(
            '6g6x',
            ['uQRP', 'YNoK'],
            [366, 370],
            $request,
            'contains',
            null,
            true,
            false,
            true
        );

        if ($new) {
            list(
                $migrantLegalMDRNew,
                $migrantLegalMDRMenNew,
                $migrantLegalMDRWomenNew,
                $migrantLegalMDRServicesNew,
            ) = $this->getCountsByProfileField(
                '6g6x',
                ['uQRP', 'YNoK'],
                [366, 370],
                $request,
                'contains',
                null,
                true,
                true,
                true
            );
        } else {
            $migrantLegalMDRNew = null;
            $migrantLegalMDRMenNew = null;
            $migrantLegalMDRWomenNew = null;
            $migrantLegalMDRServicesNew = null;
        }

        list(
            $drugUsersLegal,
            $drugUserLegalMen,
            $drugUserLegalWomen,
            $drugUserLegalServices
        ) = $this->getCountsByProfileField('6g6x', '2AJg', [366, 370], $request, 'contains', null, true);

        if ($new) {
            list(
                $drugUsersLegalNew,
                $drugUserLegalMenNew,
                $drugUserLegalWomenNew
            ) = $this->getCountsByProfileField('6g6x', '2AJg', [366, 370], $request, 'contains', null, true, true);
        } else {
            $drugUsersLegalNew = null;
            $drugUserLegalMenNew = null;
            $drugUserLegalWomenNew = null;
        }

        list(
            $drugUsersLegalMDR,
            $drugUserLegalMDRMen,
            $drugUserLegalMDRWomen,
            $drugUserLegalMDRServices
        ) = $this->getCountsByProfileField('6g6x', '2AJg', [366, 370], $request, 'contains', null, true, false, true);

        if ($new) {
            list(
                $drugUsersLegalMDRNew,
                $drugUserLegalMDRMenNew,
                $drugUserLegalMDRWomenNew
            ) = $this->getCountsByProfileField(
                '6g6x',
                '2AJg',
                [366, 370],
                $request,
                'contains',
                null,
                true,
                true,
                true
            );
        } else {
            $drugUsersLegalMDRNew = null;
            $drugUserLegalMDRMenNew = null;
            $drugUserLegalMDRWomenNew = null;
        }

        list(
            $limitedLegal,
            $limitedLegalMen,
            $limitedLegalWomen,
            $limitedLegalServices
        ) = $this->getCountsByProfileField('6g6x', 'dLBE', [366, 370], $request, 'contains', null, true);

        if ($new) {
            list(
                $limitedLegalNew,
                $limitedLegalMenNew,
                $limitedLegalWomenNew
            ) = $this->getCountsByProfileField('6g6x', 'dLBE', [366, 370], $request, 'contains', null, true, true);
        } else {
            $limitedLegalNew = null;
            $limitedLegalMenNew = null;
            $limitedLegalWomenNew = null;
        }

        list(
            $limitedLegalMDR,
            $limitedLegalMDRMen,
            $limitedLegalMDRWomen,
            $limitedLegalMDRServices
        ) = $this->getCountsByProfileField('6g6x', 'dLBE', [366, 370], $request, 'contains', null, true, false, true);

        if ($new) {
            list(
                $limitedLegalMDRNew,
                $limitedLegalMDRMenNew,
                $limitedLegalMDRWomenNew
            ) = $this->getCountsByProfileField(
                '6g6x',
                'dLBE',
                [366, 370],
                $request,
                'contains',
                null,
                true,
                true,
                true
            );
        } else {
            $limitedLegalMDRNew = null;
            $limitedLegalMDRMenNew = null;
            $limitedLegalMDRWomenNew = null;
        }

        list(
            $childrenLegal,
            $boysLegal,
            $girlsLegal,
            $childrenLegalServices
        ) = $this->getCountsByProfileField('6g6x', 'iNHa', [366, 370, 374], $request, 'contains');

        if ($new) {
            list(
                $childrenLegalNew,
                $boysLegalNew,
                $girlsLegalNew,
                $childrenLegalServicesNew
            ) = $this->getCountsByProfileField('6g6x', 'iNHa', [366, 370, 374], $request, 'contains', null, false, true);
        } else {
            $childrenLegalNew = null;
            $boysLegalNew = null;
            $girlsLegalNew = null;
            $childrenLegalServicesNew = null;
        }

        list(
            $childrenPsy,
            $boysPsy,
            $girlsPsy,
            $childrenPsyServices
        ) = $this->getCountsByProfileField('6g6x', 'iNHa', [367, 371, 375, 376], $request, 'contains');

        if ($new) {
            list(
                $childrenPsyNew,
                $boysPsyNew,
                $girlsPsyNew,
                $childrenPsyServicesNew
            ) = $this->getCountsByProfileField('6g6x', 'iNHa', [367, 371, 375, 376], $request, 'contains', null, false, true);
        } else {
            $childrenPsyNew = null;
            $boysPsyNew = null;
            $girlsPsyNew = null;
            $childrenPsyServicesNew = null;
        }

        list(
            $childrenSoc,
            $boysSoc,
            $girlsSoc,
            $childrenSocServices
        ) = $this->getCountsByProfileField(
            '6g6x',
            'iNHa',
            [365, 369, 373, 474, 363, 362, 361, 543, 377, 379, 528, 527, 364, 368, 372],
            $request,
            'contains',
            null,
            false,
            false,
            false,
            6,
            false,
            false,
            null,
            false,
            'ТБ',
            true
        );

        if ($new) {
            list(
                $childrenSocNew,
                $boysSocNew,
                $girlsSocNew,
                $childrenSocServicesNew
            ) = $this->getCountsByProfileField(
                '6g6x',
                'iNHa',
                [365, 369, 373, 474, 363, 362, 361, 543, 377, 379, 528, 527, 364, 368, 372],
                $request,
                'contains',
                null,
                false,
                true,
                false,
                6,
                false,
                false,
                null,
                false,
                'ТБ',
                true
            );
        } else {
            $childrenSocNew = null;
            $boysSocNew = null;
            $girlsSocNew = null;
            $childrenSocServicesNew = null;
        }

        list(
            $childrenPhthi,
            $boysPhthi,
            $girlsPhthi,
            $childrenPhthiServices
        ) = $this->getCountsByProfileField(
            '6g6x',
            'iNHa',
            [364, 368, 372],
            $request,
            'contains',
            null,
            false,
            false,
            false,
            6,
            false,
            false,
            null,
            false,
            'ТБ'
        );

        if ($new) {
            list(
                $childrenPhthiNew,
                $boysPhthiNew,
                $girlsPhthiNew,
                $childrenPhthiServicesNew
            ) = $this->getCountsByProfileField(
                '6g6x',
                'iNHa',
                [364, 368, 372],
                $request,
                'contains',
                null,
                false,
                true,
                false,
                6,
                false,
                false,
                null,
                false,
                'ТБ'
            );
        } else {
            $childrenPhthiNew = null;
            $boysPhthiNew = null;
            $girlsPhthiNew = null;
            $childrenPhthiServicesNew = null;
        }

        list(
            $parentsLegal,
            $parentLegalMen,
            $parentLegalWomen,
            $parentLegalServices
        ) = $this->getCountsByProfileField('6g6x', 'aSu3', [366, 370, 374], $request, 'contains');

        if ($new) {
            list(
                $parentsLegalNew,
                $parentLegalMenNew,
                $parentLegalWomenNew
            ) = $this->getCountsByProfileField('6g6x', 'aSu3', [366, 370, 374], $request, 'contains', null, false, true);
        } else {
            $parentsLegalNew = null;
            $parentLegalMenNew = null;
            $parentLegalWomenNew = null;
        }

        list(
            $parentsPsy,
            $parentPsyMen,
            $parentPsyWomen,
            $parentPsyServices
        ) = $this->getCountsByProfileField('6g6x', 'aSu3', [367, 371, 375, 376], $request, 'contains');

        if ($new) {
            list(
                $parentsPsyNew,
                $parentPsyMenNew,
                $parentPsyWomenNew
            ) = $this->getCountsByProfileField('6g6x', 'aSu3', [367, 371, 375, 376], $request, 'contains', null, false, true);
        } else {
            $parentsPsyNew = null;
            $parentPsyMenNew = null;
            $parentPsyWomenNew = null;
        }

        list(
            $parentsSoc,
            $parentSocMen,
            $parentSocWomen,
            $parentSocServices
        ) = $this->getCountsByProfileField(
            '6g6x',
            'aSu3',
            [365, 369, 373, 474, 363, 362, 361, 543, 377, 379, 528, 527, 364, 368, 372],
            $request,
            'contains',
            null,
            false,
            false,
            false,
            6,
            false,
            false,
            null,
            false,
            'ТБ',
            true
        );

        if ($new) {
            list(
                $parentsSocNew,
                $parentSocMenNew,
                $parentSocWomenNew
            ) = $this->getCountsByProfileField(
                '6g6x',
                'aSu3',
                [365, 369, 373, 474, 363, 362, 361, 543, 377, 379, 528, 527, 364, 368, 372],
                $request,
                'contains',
                null,
                false,
                true,
                false,
                6,
                false,
                false,
                null,
                false,
                'ТБ',
                true
            );
        } else {
            $parentsSocNew = null;
            $parentSocMenNew = null;
            $parentSocWomenNew = null;
        }

        list(
            $parentsPhthi,
            $parentPhthiMen,
            $parentPhthiWomen,
            $parentPhthiServices
        ) = $this->getCountsByProfileField(
            '6g6x',
            'aSu3',
            [364, 368, 372],
            $request,
            'contains',
            null,
            false,
            false,
            false,
            6,
            false,
            false,
            null,
            false,
            'ТБ'
        );

        if ($new) {
            list(
                $parentsPhthiNew,
                $parentPhthiMenNew,
                $parentPhthiWomenNew
            ) = $this->getCountsByProfileField(
                '6g6x',
                'aSu3',
                [364, 368, 372],
                $request,
                'contains',
                null,
                false,
                true,
                false,
                6,
                false,
                false,
                null,
                false,
                'ТБ'
            );
        } else {
            $parentsPhthiNew = null;
            $parentPhthiMenNew = null;
            $parentPhthiWomenNew = null;
        }

        $legalNeededQuery = User::where('roles', 'like', '%client%')
            ->where('d.data->kfbv', true)
            ->whereHas('projects', function ($query) {
                $query->where('project_id', 6);
            });

        $this->addDocumentsJoin($legalNeededQuery);

        $legalNeeded = $legalNeededQuery->count(DB::raw('distinct users.id'));

        $detectedQuery = User::where('roles', 'like', '%client%')
            ->whereHas('projects', function ($query) {
                $query->where('project_id', 6);
            });

        if ($request->from || $request->till) {
            $detectedQuery->where(function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $query->whereNotNull('d.data->d6XS')
                        ->where('d.data->d6XS', '!=', 'vNSz')
                        ->where('d.data->d6XS', '!=', 'MPCj')
                        ->whereNotNull('d.data->Xnsu')
                        ->where('d.data->Xnsu', 'LbKw');

                    if ($request->from) {
                        $query->where('d.data->it7x', '>=', $request->from);
                    }

                    if ($request->till) {
                        $query->where('d.data->it7x', '<=', $request->till);
                    }
                })
                ->orWhere(function ($query) use ($request) {
                    $query->whereNotNull('d.data->eNaZ')
                        ->where('d.data->eNaZ', '!=', 'muv9')
                        ->where('d.data->eNaZ', '!=', 'EohW')
                        ->whereNotNull('d.data->roMc')
                        ->where('d.data->roMc', 'XiCo');

                    if ($request->from) {
                        $query->where('d.data->mynW', '>=', $request->from);
                    }

                    if ($request->till) {
                        $query->where('d.data->mynW', '<=', $request->till);
                    }
                });
            });
        }

        if ($request->users) {
            $detectedQuery->whereHas('activities', function ($query) use ($request) {
                $this->addEmployeeRelationClause($query, $request);
            });
        }

        static::addDetectedClause($detectedQuery);
        $this->addDocumentsJoin($detectedQuery);

        list(
            $detected,
            $detectedMale,
            $detectedFemale,
        ) = $this->getCountsByGender($detectedQuery);

        $detectedMigrantsQuery = clone $detectedQuery;
        $detectedMigrants = $detectedMigrantsQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'uQRP')
                ->orWhereJsonContains('d.data->6g6x', 'YNoK');
        })
        ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($detectedMigrantsQuery, $request);
            $detectedMigrantsNew = $detectedMigrantsQuery->count(DB::raw('distinct users.id'));
        } else {
            $detectedMigrantsNew = null;
        }

        $detectedDrugUsersQuery = clone $detectedQuery;
        $detectedDrugUsers = $detectedDrugUsersQuery->whereJsonContains('d.data->6g6x', '2AJg')
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($detectedDrugUsersQuery, $request);
            $detectedDrugUsersNew = $detectedDrugUsersQuery->count(DB::raw('distinct users.id'));
        } else {
            $detectedDrugUsersNew = null;
        }

        $detectedPrisonersQuery = clone $detectedQuery;
        $detectedPrisoners = $detectedPrisonersQuery->where(function ($query) {
                $query->whereJsonContains('d.data->6g6x', 'gG99')
                    ->orWhereJsonContains('d.data->6g6x', 'MuuE');
        })
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($detectedPrisonersQuery, $request);
            $detectedPrisonersNew = $detectedPrisonersQuery->count(DB::raw('distinct users.id'));
        } else {
            $detectedPrisonersNew = null;
        }

        $detectedChildrenQuery = clone $detectedQuery;
        $detectedChildren = $detectedChildrenQuery->whereJsonContains('d.data->6g6x', 'iNHa')
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($detectedChildrenQuery, $request);
            $detectedChildrenNew = $detectedChildrenQuery->count(DB::raw('distinct users.id'));
        } else {
            $detectedChildrenNew = null;
        }

        $detectedParentsQuery = clone $detectedQuery;
        $detectedParents = $detectedParentsQuery->whereJsonContains('d.data->6g6x', 'aSu3')
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($detectedParentsQuery, $request);
            $detectedParentsNew = $detectedParentsQuery->count(DB::raw('distinct users.id'));
        } else {
            $detectedParentsNew = null;
        }

        $detectedDifficultQuery = clone $detectedQuery;
        $detectedDifficult = $detectedDifficultQuery->whereJsonContains('d.data->6g6x', 'dLBE')
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($detectedDifficultQuery, $request);
            $detectedDifficultNew = $detectedDifficultQuery->count(DB::raw('distinct users.id'));
        } else {
            $detectedDifficultNew = null;
        }

        $detectedMDRQuery = clone $detectedQuery;

        static::addMDRClause($detectedMDRQuery);

        $detectedMDR = $detectedMDRQuery->count(DB::raw('distinct users.id'));

        $detectedMDRNLQuery = clone $detectedMDRQuery;
        static::addNLClause($detectedMDRNLQuery);
        $detectedMDRNL = $detectedMDRNLQuery->count(DB::raw('distinct users.id'));

        if ($new) {
            $detectedMDRNewQuery = clone $detectedMDRQuery;
            $this->addNewClientClause($detectedMDRNewQuery, $request);
            $detectedMDRNew = $detectedMDRNewQuery->count(DB::raw('distinct users.id'));

            static::addNLClause($detectedMDRNewQuery);
            $detectedMDRNLNew = $detectedMDRNewQuery->count(DB::raw('distinct users.id'));
        } else {
            $detectedMDRNew = null;
            $detectedMDRNLNew = null;
        }

        $detectedMDRMaleQuery = clone $detectedMDRQuery;
        $this->addMaleClause($detectedMDRMaleQuery);
        $detectedMDRMale = $detectedMDRMaleQuery->count(DB::raw('distinct users.id'));

        $detectedMDRMaleNLQuery = clone $detectedMDRMaleQuery;
        static::addNLClause($detectedMDRMaleNLQuery);
        $detectedMDRMaleNL = $detectedMDRMaleNLQuery->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($detectedMDRMaleQuery, $request);
            $detectedMDRMaleNew = $detectedMDRMaleQuery->count(DB::raw('distinct users.id'));

            static::addNLClause($detectedMDRMaleQuery);
            $detectedMDRMaleNLNew = $detectedMDRMaleQuery->count(DB::raw('distinct users.id'));
        } else {
            $detectedMDRMaleNew = null;
            $detectedMDRMaleNLNew = null;
        }

        $detectedMDRFemaleQuery = clone $detectedMDRQuery;
        $this->addFemaleClause($detectedMDRFemaleQuery);
        $detectedMDRFemale = $detectedMDRFemaleQuery->count(DB::raw('distinct users.id'));

        $detectedMDRFemaleNLQuery = clone $detectedMDRFemaleQuery;
        static::addNLClause($detectedMDRFemaleNLQuery);
        $detectedMDRFemaleNL = $detectedMDRFemaleNLQuery->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($detectedMDRFemaleQuery, $request);
            $detectedMDRFemaleNew = $detectedMDRFemaleQuery->count(DB::raw('distinct users.id'));

            static::addNLClause($detectedMDRFemaleQuery);
            $detectedMDRFemaleNLNew = $detectedMDRFemaleQuery->count(DB::raw('distinct users.id'));
        } else {
            $detectedMDRFemaleNew = null;
            $detectedMDRFemaleNLNew = null;
        }

        $detectedMDRMigrantsQuery = clone $detectedMDRQuery;
        $detectedMDRMigrants = $detectedMDRMigrantsQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'uQRP')
                ->orWhereJsonContains('d.data->6g6x', 'YNoK');
        })
        ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($detectedMDRMigrantsQuery, $request);
            $detectedMDRMigrantsNew = $detectedMDRMigrantsQuery->count(DB::raw('distinct users.id'));
        } else {
            $detectedMDRMigrantsNew = null;
        }

        $detectedMDRDrugUsersQuery = clone $detectedMDRQuery;
        $detectedMDRDrugUsers = $detectedMDRDrugUsersQuery->whereJsonContains('d.data->6g6x', '2AJg')
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($detectedMDRDrugUsersQuery, $request);
            $detectedMDRDrugUsersNew = $detectedMDRDrugUsersQuery->count(DB::raw('distinct users.id'));
        } else {
            $detectedMDRDrugUsersNew = null;
        }

        $detectedMDRPrisonersQuery = clone $detectedMDRQuery;
        $detectedMDRPrisoners = $detectedMDRPrisonersQuery->where(function ($query) {
                $query->whereJsonContains('d.data->6g6x', 'gG99')
                    ->orWhereJsonContains('d.data->6g6x', 'MuuE');
        })
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($detectedMDRPrisonersQuery, $request);
            $detectedMDRPrisonersNew = $detectedMDRPrisonersQuery->count(DB::raw('distinct users.id'));
        } else {
            $detectedMDRPrisonersNew = null;
        }

        $detectedMDRChildrenQuery = clone $detectedMDRQuery;
        $detectedMDRChildren = $detectedMDRChildrenQuery->whereJsonContains('d.data->6g6x', 'iNHa')
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($detectedMDRChildrenQuery, $request);
            $detectedMDRChildrenNew = $detectedMDRChildrenQuery->count(DB::raw('distinct users.id'));
        } else {
            $detectedMDRChildrenNew = null;
        }

        $detectedMDRParentsQuery = clone $detectedMDRQuery;
        $detectedMDRParents = $detectedMDRParentsQuery->whereJsonContains('d.data->6g6x', 'aSu3')
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($detectedMDRParentsQuery, $request);
            $detectedMDRParentsNew = $detectedMDRParentsQuery->count(DB::raw('distinct users.id'));
        } else {
            $detectedMDRParentsNew = null;
        }

        $detectedMDRDifficultQuery = clone $detectedMDRQuery;
        $detectedMDRDifficult = $detectedMDRDifficultQuery->whereJsonContains('d.data->6g6x', 'dLBE')
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($detectedMDRDifficultQuery, $request);
            $detectedMDRDifficultNew = $detectedMDRDifficultQuery->count(DB::raw('distinct users.id'));
        } else {
            $detectedMDRDifficultNew = null;
        }

        $redundantDetectedQuery = clone $detectedQuery;
        $redundantDetected = $redundantDetectedQuery->whereRaw(
            'if(json_contains(d.data, \'"uQRP"\', "$.6g6x") ' .
                'or json_contains(d.data, \'"YNoK"\', "$.6g6x"), 1, 0) + ' .
            'json_contains(d.data, \'"2AJg"\', "$.6g6x") + ' .
            'if(json_contains(d.data, \'"gG99"\', "$.6g6x") ' .
                'or json_contains(d.data, \'"MuuE"\', "$.6g6x"), 1, 0) + ' .
            'json_contains(d.data, \'"iNHa"\', "$.6g6x") + ' .
            'json_contains(d.data, \'"aSu3"\', "$.6g6x") + ' .
            'json_contains(d.data, \'"dLBE"\', "$.6g6x") ' .
            '> 1'
        )->count(DB::raw('distinct users.id'));

        static::addMDRClause($redundantDetectedQuery);

        $redundantMDRDetected = $redundantDetectedQuery->count(DB::raw('distinct users.id'));

        $detectedNLQuery = clone $detectedQuery;
        static::addNLClause($detectedNLQuery);

        list(
            $detectedNL,
            $detectedMaleNL,
            $detectedFemaleNL
        ) = $this->getCountsByGender($detectedNLQuery);

        $redundantDetectedNLQuery = clone $detectedNLQuery;
        $redundantDetectedNL = $redundantDetectedNLQuery->whereRaw(
            'if(json_contains(d.data, \'"uQRP"\', "$.6g6x") ' .
                'or json_contains(d.data, \'"YNoK"\', "$.6g6x"), 1, 0) + ' .
            'json_contains(d.data, \'"2AJg"\', "$.6g6x") + ' .
            'if(json_contains(d.data, \'"gG99"\', "$.6g6x") ' .
                'or json_contains(d.data, \'"MuuE"\', "$.6g6x"), 1, 0) ' .
            '> 1'
        )->count(DB::raw('distinct users.id'));

        $startedQuery = User::where('roles', 'like', '%client%')
            ->whereHas('projects', function ($query) {
                $query->where('project_id', 6);
            })
            ->whereNotNull('d.data->Q5xs')
            ->where(function ($query) {
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

        $this->addDocumentsJoin($startedQuery);

        if ($request->users) {
            $startedQuery->whereHas('activities', function ($query) use ($request) {
                $this->addEmployeeRelationClause($query, $request);
            });
        }

        if ($request->from) {
            $startedQuery->where('d.data->Q5xs', '>=', $request->from);
        }

        if ($request->till) {
            $startedQuery->where('d.data->Q5xs', '<=', $request->till);
        }

        $started = $startedQuery->count(DB::raw('distinct users.id'));

        if ($new) {
            $startedNewQuery = clone $startedQuery;
            $this->addNewClientClause($startedNewQuery, $request);
            $startedNew = $startedNewQuery->count(DB::raw('distinct users.id'));
        } else {
            $startedNew = null;
        }

        $startedMigrantsQuery = clone $startedQuery;
        $startedMigrants = $startedMigrantsQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'uQRP')
                ->orWhereJsonContains('d.data->6g6x', 'YNoK');
        })
        ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($startedMigrantsQuery, $request);
            $startedMigrantsNew = $startedMigrantsQuery->count(DB::raw('distinct users.id'));
        } else {
            $startedMigrantsNew = null;
        }

        $startedDrugUsersQuery = clone $startedQuery;
        $startedDrugUsers = $startedDrugUsersQuery->whereJsonContains('d.data->6g6x', '2AJg')
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($startedDrugUsersQuery, $request);
            $startedDrugUsersNew = $startedDrugUsersQuery->count(DB::raw('distinct users.id'));
        } else {
            $startedDrugUsersNew = null;
        }

        $startedPrisonersQuery = clone $startedQuery;
        $startedPrisoners = $startedPrisonersQuery->where(function ($query) {
                $query->whereJsonContains('d.data->6g6x', 'gG99')
                    ->orWhereJsonContains('d.data->6g6x', 'MuuE');
        })
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($startedPrisonersQuery, $request);
            $startedPrisonersNew = $startedPrisonersQuery->count(DB::raw('distinct users.id'));
        } else {
            $startedPrisonersNew = null;
        }

        $startedChildrenQuery = clone $startedQuery;
        $startedChildren = $startedChildrenQuery->whereJsonContains('d.data->6g6x', 'iNHa')
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($startedChildrenQuery, $request);
            $startedChildrenNew = $startedChildrenQuery->count(DB::raw('distinct users.id'));
        } else {
            $startedChildrenNew = null;
        }

        $startedParentsQuery = clone $startedQuery;
        $startedParents = $startedParentsQuery->whereJsonContains('d.data->6g6x', 'aSu3')
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($startedParentsQuery, $request);
            $startedParentsNew = $startedParentsQuery->count(DB::raw('distinct users.id'));
        } else {
            $startedParentsNew = null;
        }

        $startedMDRQuery = clone $startedQuery;
        static::addMDRClause($startedMDRQuery);

        list(
            $startedMDR,
            $startedMDRMale,
            $startedMDRFemale,
        ) = $this->getCountsByGender($startedMDRQuery);

        $startedMDRNLQuery = clone $startedMDRQuery;

        static::addNLClause($startedMDRNLQuery);

        list(
            $startedMDRNL,
            $startedMDRMaleNL,
            $startedMDRFemaleNL,
        ) = $this->getCountsByGender($startedMDRNLQuery);

        if ($new) {
            $startedMDRNewQuery = clone $startedMDRQuery;
            $this->addNewClientClause($startedMDRNewQuery, $request);
            $startedMDRNew = $startedMDRNewQuery->count(DB::raw('distinct users.id'));

            list(
                $startedMDRNew,
                $startedMDRMaleNew,
                $startedMDRFemaleNew,
            ) = $this->getCountsByGender($startedMDRNewQuery);

            static::addNLClause($startedMDRNewQuery);

            list(
                $startedMDRNLNew,
                $startedMDRMaleNLNew,
                $startedMDRFemaleNLNew,
            ) = $this->getCountsByGender($startedMDRNewQuery);
        } else {
            $startedMDRNew = null;
            $startedMDRMaleNew = null;
            $startedMDRFemaleNew = null;
            $startedMDRNLNew = null;
            $startedMDRMaleNLNew = null;
            $startedMDRFemaleNLNew = null;
        }

        $startedMDRMigrantsQuery = clone $startedMDRQuery;
        $startedMDRMigrants = $startedMDRMigrantsQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'uQRP')
                ->orWhereJsonContains('d.data->6g6x', 'YNoK');
        })
        ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($startedMDRMigrantsQuery, $request);
            $startedMDRMigrantsNew = $startedMDRMigrantsQuery->count(DB::raw('distinct users.id'));
        } else {
            $startedMDRMigrantsNew = null;
        }

        $startedMDRDrugUsersQuery = clone $startedMDRQuery;
        $startedMDRDrugUsers = $startedMDRDrugUsersQuery->whereJsonContains('d.data->6g6x', '2AJg')
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($startedMDRDrugUsersQuery, $request);
            $startedMDRDrugUsersNew = $startedMDRDrugUsersQuery->count(DB::raw('distinct users.id'));
        } else {
            $startedMDRDrugUsersNew = null;
        }

        $startedMDRPrisonersQuery = clone $startedMDRQuery;
        $startedMDRPrisoners = $startedMDRPrisonersQuery->where(function ($query) {
                $query->whereJsonContains('d.data->6g6x', 'gG99')
                    ->orWhereJsonContains('d.data->6g6x', 'MuuE');
        })
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($startedMDRPrisonersQuery, $request);
            $startedMDRPrisonersNew = $startedMDRPrisonersQuery->count(DB::raw('distinct users.id'));
        } else {
            $startedMDRPrisonersNew = null;
        }

        $startedMDRChildrenQuery = clone $startedMDRQuery;
        $startedMDRChildren = $startedMDRChildrenQuery->whereJsonContains('d.data->6g6x', 'iNHa')
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($startedMDRChildrenQuery, $request);
            $startedMDRChildrenNew = $startedMDRChildrenQuery->count(DB::raw('distinct users.id'));
        } else {
            $startedMDRChildrenNew = null;
        }

        $startedMDRParentsQuery = clone $startedMDRQuery;
        $startedMDRParents = $startedMDRParentsQuery->whereJsonContains('d.data->6g6x', 'aSu3')
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($startedMDRParentsQuery, $request);
            $startedMDRParentsNew = $startedMDRParentsQuery->count(DB::raw('distinct users.id'));
        } else {
            $startedMDRParentsNew = null;
        }

        $startedMDRLimitedQuery = clone $startedMDRQuery;
        $startedMDRLimited = $startedMDRLimitedQuery->whereJsonContains('d.data->6g6x', 'dLBE')
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($startedMDRLimitedQuery, $request);
            $startedMDRLimitedNew = $startedMDRLimitedQuery->count(DB::raw('distinct users.id'));
        } else {
            $startedMDRLimitedNew = null;
        }

        $supportedQuery = clone $startedQuery;
        $supportedQuery->whereHas('activities', function ($query) {
            $keyword = 'скрининг';

            $query->where('project_id', 6)
                // ->whereIn('user_activity.part_id', [364, 365, 368, 369, 373])
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
        });

        $supportedQuery = $this->getQueryByProfileField(null, null, [], $request, null, null, true);

        list(
            $supported,
            $supportedMale,
            $supportedFemale,
        ) = $this->getCountsByGender($supportedQuery);

        $supportedNLQuery = clone $supportedQuery;
        static::addNLClause($supportedNLQuery);

        list(
            $supportedNL,
            $supportedMaleNL,
            $supportedFemaleNL,
        ) = $this->getCountsByGender($supportedNLQuery);

        if ($new) {
            $supportedQueryNew = clone $supportedQuery;
            $this->addNewClientClause($supportedQueryNew, $request);

            list(
                $supportedNew,
                $supportedMaleNew,
                $supportedFemaleNew,
            ) = $this->getCountsByGender($supportedQueryNew);
        } else {
            $supportedNew = null;
            $supportedMaleNew = null;
            $supportedFemaleNew = null;
        }

        $supportedNLQuery->whereRaw(
            'if(json_contains(d.data, \'"uQRP"\', "$.6g6x") ' .
                'or json_contains(d.data, \'"YNoK"\', "$.6g6x"), 1, 0) + ' .
            'json_contains(d.data, \'"2AJg"\', "$.6g6x") + ' .
            'json_contains(d.data, \'"gG99"\', "$.6g6x") ' .
            '> 1'
        );

        $supportedNLRedundant = $supportedNLQuery->count(DB::raw('distinct users.id'));

        $supportedMigrantsQuery = clone $supportedQuery;
        $supportedMigrants = $supportedMigrantsQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'uQRP')
                ->orWhereJsonContains('d.data->6g6x', 'YNoK');
        })
        ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($supportedMigrantsQuery, $request);
            $supportedMigrantsNew = $supportedMigrantsQuery->count(DB::raw('distinct users.id'));
        } else {
            $supportedMigrantsNew = null;
        }

        $supportedDrugUsersQuery = clone $supportedQuery;
        $supportedDrugUsers = $supportedDrugUsersQuery->whereJsonContains('d.data->6g6x', '2AJg')
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($supportedDrugUsersQuery, $request);
            $supportedDrugUsersNew = $supportedDrugUsersQuery->count(DB::raw('distinct users.id'));
        } else {
            $supportedDrugUsersNew = null;
        }

        $supportedPrisonersQuery = clone $supportedQuery;
        $supportedPrisoners = $supportedPrisonersQuery->where(function ($query) {
                $query->whereJsonContains('d.data->6g6x', 'gG99')
                    ->orWhereJsonContains('d.data->6g6x', 'MuuE');
        })
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($supportedPrisonersQuery, $request);
            $supportedPrisonersNew = $supportedPrisonersQuery->count(DB::raw('distinct users.id'));
        } else {
            $supportedPrisonersNew = null;
        }

        $supportedLimitedQuery = clone $supportedQuery;
        $supportedLimited = $supportedLimitedQuery->whereJsonContains('d.data->6g6x', 'dLBE')
            ->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($supportedLimitedQuery, $request);
            $supportedLimitedNew = $supportedLimitedQuery->count(DB::raw('distinct users.id'));
        } else {
            $supportedLimitedNew = null;
        }

        $supportedDetectedQuery = clone $supportedQuery;
        $supportedDetectedQuery->where('d.data->rq3B', 'bHWo')
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

        if ($request->from) {
            $supportedDetectedQuery->where('d.data->Q5xs', '>=', $request->from);
        }

        if ($request->till) {
            $supportedDetectedQuery->where('d.data->Q5xs', '<=', $request->till);
        }

        $supportedDetected = $supportedDetectedQuery->count(DB::raw('distinct users.id'));

        $supportedDetectedNLQuery = clone $supportedDetectedQuery;
        static::addNLClause($supportedDetectedNLQuery);

        $supportedDetectedNL = $supportedDetectedNLQuery->count(DB::raw('distinct users.id'));

        $supportedReferredQuery = clone $supportedQuery;
        $supportedReferredQuery
            ->where(function ($query) use ($request) {
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

        $supportedReferred = $supportedReferredQuery->count(DB::raw('distinct users.id'));

        $supportedReferredNLQuery = clone $supportedReferredQuery;
        static::addNLClause($supportedReferredNLQuery);
        $supportedReferredNL = $supportedReferredNLQuery->count(DB::raw('distinct users.id'));

        $overallStartedQuery = User::where('roles', 'like', '%client%')
            ->whereHas('projects', function ($query) {
                $query->where('project_id', 6);
            })
            ->whereNotNull('d.data->kB3w')
            ->whereJsonDoesntContain('d.data->6g6x', 'iNHa');

        $this->addDocumentsJoin($overallStartedQuery);
        $overallStartedQuery->groupBy('users.id');

        if ($request->till) {
            $overallStartedQuery->where('d.data->kB3w', '<=', $request->till);
        }

        $overallStartedMDR = 0;
        $overallRemainedMDR = 0;

        $overallStartedNL = 0;
        $overallRemainedNL = 0;

        $overallStartedDSNL = 0;
        $overallRemainedDSNL = 0;

        $overallStartedMDRNL = 0;
        $overallRemainedMDRNL = 0;

        foreach ($overallStartedQuery->get() as $client) {
            if (!empty($client->profile['11']) && !empty($client->profile['11'][0]->data['TNF5'])
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
                            $latestCause = $form->data['XcEm'] ??
                                (!empty($form->data['jBQK']) ? 'jBQK' : (!empty($form->data['F4fi']) ? 'F4fi' : null));
                            $latestDeathCause = $form->data['asmx'] ?? null;
                        }
                    }
                }

                $initiated = $client->profile['6'][0]->data['kB3w'];

                if (empty($client->profile['6'][0]->data['2CLW']) || $client->profile['6'][0]->data['2CLW'] != 'wx6s') {
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

                $from = $request->from ? Carbon::parse($request->from) : null;
                $inProjectFrom = Carbon::parse($initiated);

                $start = $from && $from > $inProjectFrom ? $from : $inProjectFrom;

                $till = !$request->till ? now() : Carbon::parse($request->till);
                $latest = Carbon::parse($latestDate);

                $end = !$latest || $latest > $till ? $till : $latest;

                $start->floorMonth();
                $end->ceilMonth();

                $months = (int) $start->floatDiffInMonths($end);

                if (!empty($client->profile['6'][0]->data['nt6j']) && !empty($client->profile['6'][0]->data['fofk'])) {
                    $months -= Carbon::parse($client->profile['6'][0]->data['nt6j'])
                        ->ceilMonth()
                        ->floatDiffInMonths(Carbon::parse($client->profile['6'][0]->data['fofk'])->floorMonth());
                }

                $MDR = (!empty($client->profile[6][0]->data['d6XS']) &&
                        in_array($client->profile[6][0]->data['d6XS'], ['gBZJ','BHpL', 'TrxW', 'aKnw'])) ||
                    (!empty($client->profile[6][0]->data['eNaZ']) &&
                        in_array($client->profile[6][0]->data['eNaZ'], ['3boi','wwHt', 'qXTv', 'uK7c'])) ||
                    (!empty($client->profile[6][0]->data['rjWN']) &&
                        in_array($client->profile[6][0]->data['rjWN'], ['AyZG','CcBo', '2EY9', 'WQoj']));

                if ($MDR) {
                    $overallStartedMDR++;
                }

                if (!empty($client->profile[6][0]->data['6g6x']) &&
                    (in_array('uQRP', $client->profile[6][0]->data['6g6x'])
                    || in_array('gG99', $client->profile[6][0]->data['6g6x'])
                    || in_array('2AJg', $client->profile[6][0]->data['6g6x'])
                    || in_array('YNoK', $client->profile[6][0]->data['6g6x']))
                ) {
                    $overallStartedNL++;

                    if ($MDR) {
                        $overallStartedMDRNL++;
                    } else {
                        $overallStartedDSNL++;
                    }
                }

                if (($latestOutcome == 'nXDJ' ||
                    ($latestOutcome == 'meHa' &&
                        ($latestCause == 'Ze52' ||
                            ($latestCause == 'BtcT' && $latestDeathCause == 'S4s5'))) ||
                    ($latestOutcome == 'A4Z9' && ($latestCause == 'jBQK' || $latestCause == 'F4fi')) ||
                    (($latestOutcome == 'bJjG' || $latestOutcome == 'PnKg') && substr($latestDate, 0, 7) >=
                        (new Carbon($request->till ?: 'last month'))->format('Y-m'))) &&
                    $outcomes >= $months
                ) {
                    if ($MDR) {
                        $overallRemainedMDR++;
                    }

                    if (!empty($client->profile[6][0]->data['6g6x']) &&
                        (in_array('uQRP', $client->profile[6][0]->data['6g6x'])
                        || in_array('gG99', $client->profile[6][0]->data['6g6x'])
                        || in_array('2AJg', $client->profile[6][0]->data['6g6x'])
                        || in_array('YNoK', $client->profile[6][0]->data['6g6x']))
                    ) {
                        $overallRemainedNL++;

                        if ($MDR) {
                            $overallRemainedMDRNL++;
                        } else {
                            $overallRemainedDSNL++;
                        }
                    }
                }
            }
        }

        $childrenOverallStartedQuery = User::where('roles', 'like', '%client%')
            ->whereHas('projects', function ($query) {
                $query->where('project_id', 6);
            })
            ->whereNotNull('d.data')
            ->whereJsonContains('d.data->6g6x', 'iNHa');

        $this->addDocumentsJoin($childrenOverallStartedQuery);
        $childrenOverallStartedQuery->groupBy('users.id');

        if ($request->till) {
            $childrenOverallStartedQuery->whereNotNull('d.data->kB3w')
                ->where('d.data->kB3w', '<=', $request->till);
        }

        $childrenOverallStarted = 0;
        $childrenOverallRemained = 0;

        $childrenOverallStartedMDR = 0;
        $childrenOverallRemainedMDR = 0;

        $childrenOutcomes = 0;
        $childrenOutcomesTreated = 0;
        $childrenOutcomesRecovered = 0;
        $childrenOutcomesLost = 0;
        $childrenOutcomesUnregistered = 0;
        $childrenOutcomesMoved = 0;
        $childrenOutcomesDied = 0;
        $childrenOutcomesDiedTB = 0;
        $childrenOutcomesJailed = 0;
        $childrenOutcomesCured = 0;
        $childrenOutcomesBecameMDR = 0;
        $childrenOutcomesBecameWDR = 0;

        foreach ($childrenOverallStartedQuery->get() as $client) {
            if (!empty($client->profile['11']) && !empty($client->profile['11'][0]->data['TNF5'])
                || (!empty($client->profile[52]) && !empty($client->profile[52][0]->data['TNF5']))
            ) {
                $outcomes = 0;

                $latestDate = null;
                $latestOutcome = null;
                $latestCause = null;
                $latestDeathCause = null;

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
                            $latestCause = $form->data['XcEm'] ??
                                (!empty($form->data['jBQK']) ? 'jBQK' : (!empty($form->data['F4fi']) ? 'F4fi' : null));
                            $latestDeathCause = $form->data['asmx'] ?? null;
                        }
                    }
                }

                if (!$outcomes) {
                    continue;
                }

                $childrenOutcomes++;

                switch ($latestOutcome) {
                    case 'bJjG':
                    case 'PnKg':
                        $childrenOutcomesTreated++;
                        break;

                    case 'nXDJ':
                        $childrenOutcomesRecovered++;
                        break;

                    case 'dAtv':
                        $childrenOutcomesLost++;
                        break;

                    case 'meHa':
                        $childrenOutcomesUnregistered++;

                        switch ($latestCause) {
                            case 'BtcT':
                                $childrenOutcomesDied++;

                                if ($latestDeathCause == 'MppH') {
                                    $childrenOutcomesDiedTB++;
                                }

                                break;

                            case 'Lwch':
                                $childrenOutcomesMoved++;
                                break;

                            case 'KyEj':
                                $childrenOutcomesJailed++;
                                break;

                            case 'Ze52':
                                $childrenOutcomesCured++;
                                break;
                        }

                        break;

                    case 'EMsf':
                        $childrenOutcomesBecameMDR++;
                        break;

                    case 'Ajf5':
                        $childrenOutcomesBecameWDR++;
                        break;
                }

                $initiated = $client->profile['6'][0]->data['kB3w'];

                if (empty($client->profile['6'][0]->data['2CLW']) || $client->profile['6'][0]->data['2CLW'] != 'wx6s') {
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

                $from = $request->from ? Carbon::parse($request->from) : null;
                $inProjectFrom = Carbon::parse($initiated);

                $start = $from && $from > $inProjectFrom ? $from : $inProjectFrom;

                $till = !$request->till ? now() : Carbon::parse($request->till);
                $latest = Carbon::parse($latestDate);

                $end = !$latest || $latest > $till ? $till : $latest;

                $start->floorMonth();
                $end->ceilMonth();

                $months = (int) $start->floatDiffInMonths($end);

                if (!empty($client->profile['6'][0]->data['nt6j']) && !empty($client->profile['6'][0]->data['fofk'])) {
                    $months -= Carbon::parse($client->profile['6'][0]->data['nt6j'])
                        ->ceilMonth()
                        ->floatDiffInMonths(Carbon::parse($client->profile['6'][0]->data['fofk'])->floorMonth());
                }

                $childrenOverallStarted++;

                $MDR = (!empty($client->profile[6][0]->data['d6XS']) &&
                        in_array($client->profile[6][0]->data['d6XS'], ['gBZJ','BHpL', 'TrxW', 'aKnw'])) ||
                    (!empty($client->profile[6][0]->data['eNaZ']) &&
                        in_array($client->profile[6][0]->data['eNaZ'], ['3boi','wwHt', 'qXTv', 'uK7c'])) ||
                    (!empty($client->profile[6][0]->data['rjWN']) &&
                        in_array($client->profile[6][0]->data['rjWN'], ['AyZG','CcBo', '2EY9', 'WQoj']));

                if ($MDR) {
                    $childrenOverallStartedMDR++;
                }

                if (($latestOutcome == 'nXDJ' ||
                    ($latestOutcome == 'meHa' &&
                        ($latestCause == 'Ze52' ||
                            ($latestCause == 'BtcT' && $latestDeathCause == 'S4s5'))) ||
                    ($latestOutcome == 'A4Z9' && ($latestCause == 'jBQK' || $latestCause == 'F4fi')) ||
                    (($latestOutcome == 'bJjG' || $latestOutcome == 'PnKg') && substr($latestDate, 0, 7) >=
                        (new Carbon($request->till ?: 'last month'))->format('Y-m'))) &&
                    $outcomes >= $months
                ) {
                    $childrenOverallRemained++;

                    if ($MDR) {
                        $childrenOverallRemainedMDR++;
                    }
                }
            }
        }

        $restartedQuery = User::where('roles', 'like', '%client%')
            ->whereHas('projects', function ($query) {
                $query->where('project_id', 6);
            })
            ->where('d.data->9Rq9', 'F6SP')
            ->whereNotNull('d.data->Q5xs');

        $this->addDocumentsJoin($restartedQuery);

        if ($request->from) {
            $restartedQuery->where('d.data->Q5xs', '>=', $request->from);
        }

        if ($request->till) {
            $restartedQuery->where('d.data->Q5xs', '<=', $request->till);
        }

        if ($request->users) {
            $restartedQuery->whereHas('activities', function ($query) use ($request) {
                $this->addEmployeeRelationClause($query, $request);
            });
        }

        $restarted = $restartedQuery->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($restartedQuery, $request);
            $restartedNew = $restartedQuery->count(DB::raw('distinct users.id'));
        } else {
            $restartedNew = null;
        }

        $restartedMDRQuery = clone $restartedQuery;
        static::addMDRClause($restartedMDRQuery);

        $restartedMDR = $restartedMDRQuery->count(DB::raw('distinct users.id'));

        if ($new) {
            $this->addNewClientClause($restartedMDRQuery, $request);
            $restartedMDRNew = $restartedMDRQuery->count(DB::raw('distinct users.id'));
        } else {
            $restartedMDRNew = null;
        }

        $stoppedQuery = User::where('roles', 'like', '%client%')
            ->whereHas('projects', function ($query) {
                $query->where('project_id', 6);
            })
            ->whereNotNull('d.data->ZYpR')
            ->whereNotIn('d.data->ZYpR', ['RdBz', 'LLFk']);

        if ($request->users) {
            $stoppedQuery->whereHas('activities', function ($query) use ($request) {
                $this->addEmployeeRelationClause($query, $request);
            });
        }

        $this->addDocumentsJoin($stoppedQuery);

        $stoppedChildrenQuery = clone $stoppedQuery;
        $stoppedChildrenQuery->whereJsonContains('d.data->6g6x', 'iNHa');

        $stoppedQuery->whereJsonDoesntContain('d.data->6g6x', 'iNHa');

        $stopped = $stoppedQuery->count(DB::raw('distinct users.id'));

        $stoppedMDRQuery = clone $stoppedQuery;
        static::addMDRClause($stoppedMDRQuery);
        $stoppedMDR = $stoppedMDRQuery->count(DB::raw('distinct users.id'));

        $stoppedUnregisteredQuery = clone $stoppedQuery;
        $stoppedUnregisteredQuery->where('d.data->ZYpR', 'M6mj');
        $stoppedUnregistered = $stoppedUnregisteredQuery->count(DB::raw('distinct users.id'));
        static::addMDRClause($stoppedUnregisteredQuery);
        $stoppedUnregisteredMDR = $stoppedUnregisteredQuery->count(DB::raw('distinct users.id'));

        $stoppedDeadQuery = clone $stoppedQuery;
        $stoppedDeadQuery->where('d.data->ZYpR', 'KJyh');
        $stoppedDead = $stoppedDeadQuery->count(DB::raw('distinct users.id'));
        static::addMDRClause($stoppedDeadQuery);
        $stoppedDeadMDR = $stoppedDeadQuery->count(DB::raw('distinct users.id'));

        $stoppedLostQuery = clone $stoppedQuery;
        $stoppedLostQuery->where('d.data->ZYpR', 'Nh49');
        $stoppedLost = $stoppedLostQuery->count(DB::raw('distinct users.id'));
        static::addMDRClause($stoppedLostQuery);
        $stoppedLostMDR = $stoppedLostQuery->count(DB::raw('distinct users.id'));

        $stoppedQuitQuery = clone $stoppedQuery;
        $stoppedQuitQuery->where('d.data->ZYpR', 'Jc36');
        $stoppedQuit = $stoppedQuitQuery->count(DB::raw('distinct users.id'));
        static::addMDRClause($stoppedQuitQuery);
        $stoppedQuitMDR = $stoppedQuitQuery->count(DB::raw('distinct users.id'));

        $stoppedFinishedQuery = clone $stoppedQuery;
        $stoppedFinishedQuery->where('d.data->ZYpR', 'vZv4');
        $stoppedFinished = $stoppedFinishedQuery->count(DB::raw('distinct users.id'));
        static::addMDRClause($stoppedFinishedQuery);
        $stoppedFinishedMDR = $stoppedFinishedQuery->count(DB::raw('distinct users.id'));

        $stoppedChildren = $stoppedChildrenQuery->count(DB::raw('distinct users.id'));

        $stoppedMDRChildrenQuery = clone $stoppedChildrenQuery;
        static::addMDRClause($stoppedMDRChildrenQuery);
        $stoppedMDRChildren = $stoppedMDRChildrenQuery->count(DB::raw('distinct users.id'));

        $stoppedUnregisteredChildrenQuery = clone $stoppedChildrenQuery;
        $stoppedUnregisteredChildrenQuery->where('d.data->ZYpR', 'M6mj');
        $stoppedUnregisteredChildren = $stoppedUnregisteredChildrenQuery->count(DB::raw('distinct users.id'));
        static::addMDRClause($stoppedUnregisteredChildrenQuery);
        $stoppedUnregisteredMDRChildren = $stoppedUnregisteredChildrenQuery->count(DB::raw('distinct users.id'));

        $stoppedDeadChildrenQuery = clone $stoppedChildrenQuery;
        $stoppedDeadChildrenQuery->where('d.data->ZYpR', 'KJyh');
        $stoppedDeadChildren = $stoppedDeadChildrenQuery->count(DB::raw('distinct users.id'));
        static::addMDRClause($stoppedDeadChildrenQuery);
        $stoppedDeadMDRChildren = $stoppedDeadChildrenQuery->count(DB::raw('distinct users.id'));

        $stoppedLostChildrenQuery = clone $stoppedChildrenQuery;
        $stoppedLostChildrenQuery->where('d.data->ZYpR', 'Nh49');
        $stoppedLostChildren = $stoppedLostChildrenQuery->count(DB::raw('distinct users.id'));
        static::addMDRClause($stoppedLostChildrenQuery);
        $stoppedLostMDRChildren = $stoppedLostChildrenQuery->count(DB::raw('distinct users.id'));

        $stoppedQuitChildrenQuery = clone $stoppedChildrenQuery;
        $stoppedQuitChildrenQuery->where('d.data->ZYpR', 'Jc36');
        $stoppedQuitChildren = $stoppedQuitChildrenQuery->count(DB::raw('distinct users.id'));
        static::addMDRClause($stoppedQuitChildrenQuery);
        $stoppedQuitMDRChildren = $stoppedQuitChildrenQuery->count(DB::raw('distinct users.id'));

        $stoppedFinishedChildrenQuery = clone $stoppedChildrenQuery;
        $stoppedFinishedChildrenQuery->where('d.data->ZYpR', 'vZv4');
        $stoppedFinishedChildren = $stoppedFinishedChildrenQuery->count(DB::raw('distinct users.id'));
        static::addMDRClause($stoppedFinishedChildrenQuery);
        $stoppedFinishedMDRChildren = $stoppedFinishedChildrenQuery->count(DB::raw('distinct users.id'));

        $outcomesQuery = User::where('roles', 'like', '%client%')
            ->whereHas('projects', function ($query) {
                $query->where('project_id', 6);
            })
            ->whereNotNull('d.data')
            ->whereNotNull('d.data->kB3w')
            ->whereJsonDoesntContain('d.data->6g6x', 'iNHa');

        $this->addDocumentsJoin($outcomesQuery);
        $outcomesQuery->groupBy('users.id');

        if ($request->till) {
            $outcomesQuery->where('d.data->kB3w', '<=', $request->till);
        }

        $outcomes = 0;
        $outcomesTreated = 0;
        $outcomesRecovered = 0;
        $outcomesLost = 0;
        $outcomesUnregistered = 0;
        $outcomesMoved = 0;
        $outcomesDied = 0;
        $outcomesDiedTB = 0;
        $outcomesJailed = 0;
        $outcomesCured = 0;
        $outcomesBecameMDR = 0;
        $outcomesBecameWDR = 0;

        foreach ($outcomesQuery->get() as $client) {
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

                $outcomes++;

                switch ($latestOutcome) {
                    case 'bJjG':
                    case 'PnKg':
                        $outcomesTreated++;
                        break;

                    case 'nXDJ':
                        $outcomesRecovered++;
                        break;

                    case 'dAtv':
                        $outcomesLost++;
                        break;

                    case 'meHa':
                        $outcomesUnregistered++;

                        switch ($latestReason) {
                            case 'BtcT':
                                $outcomesDied++;

                                if ($latestCause == 'MppH') {
                                    $outcomesDiedTB++;
                                }

                                break;

                            case 'Lwch':
                                $outcomesMoved++;
                                break;

                            case 'KyEj':
                                $outcomesJailed++;
                                break;

                            case 'Ze52':
                                $outcomesCured++;
                                break;
                        }

                        break;

                    case 'EMsf':
                        $outcomesBecameMDR++;
                        break;

                    case 'Ajf5':
                        $outcomesBecameWDR++;
                        break;
                }
            }
        }

        $outcomesNLQuery = clone $outcomesQuery;

        static::addNLClause($outcomesNLQuery);

        $outcomesNL = 0;
        $outcomesTreatedNL = 0;
        $outcomesRecoveredNL = 0;
        $outcomesLostNL = 0;
        $outcomesUnregisteredNL = 0;
        $outcomesMovedNL = 0;
        $outcomesDiedNL = 0;
        $outcomesDiedTBNL = 0;
        $outcomesJailedNL = 0;
        $outcomesCuredNL = 0;
        $outcomesBecameMDRNL = 0;
        $outcomesBecameWDRNL = 0;

        $outcomesNLClientIds = [];

        foreach ($outcomesNLQuery->get() as $client) {
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

                $outcomesNL++;
                $outcomesNLClientIds[] = $client->id;

                switch ($latestOutcome) {
                    case 'bJjG':
                    case 'PnKg':
                        $outcomesTreatedNL++;
                        break;

                    case 'A4Z9':
                        if (!$latestReason) {
                            break;
                        }

                        // intended

                    case 'nXDJ':
                        $outcomesRecoveredNL++;
                        break;

                    case 'dAtv':
                        $outcomesLostNL++;
                        break;

                    case 'meHa':
                        $outcomesUnregisteredNL++;

                        switch ($latestReason) {
                            case 'BtcT':
                                $outcomesDiedNL++;

                                if ($latestCause == 'MppH') {
                                    $outcomesDiedTBNL++;
                                }

                                break;

                            case 'Lwch':
                                $outcomesMovedNL++;
                                break;

                            case 'KyEj':
                                $outcomesJailedNL++;
                                break;

                            case 'Ze52':
                                $outcomesCuredNL++;
                                break;
                        }

                        break;

                    case 'EMsf':
                        $outcomesBecameMDRNL++;
                        break;

                    case 'Ajf5':
                        $outcomesBecameWDRNL++;
                        break;
                }
            }
        }

        $outcomesNLServices = $this->getServicesCountByProfileFieldQuery(
            null,
            null,
            [],
            $request,
            null,
            null,
            false,
            false,
            6,
            false,
            false,
            6,
            function ($query) use ($outcomesNLClientIds) {
                $query->whereIn('id', $outcomesNLClientIds);
            }
        )->count(DB::raw('distinct id'));

        $outcomesNLClientsQuery = User::whereIn('users.id', $outcomesNLClientIds)
            ->whereHas('activities', function ($query) use ($request) {
                $query->where('project_id', 6);

                if ($request->from) {
                    $query->where('start_date', '>=', $request->from);
                }

                if ($request->till) {
                    $query->where('start_date', '<=', $request->till);
                }
            });

        $this->addDocumentsJoin($outcomesNLClientsQuery);

        $outcomesNLServiced = $outcomesNLClientsQuery->count(DB::raw('distinct users.id'));

        list(
            $outcomesNLServiced,
            $outcomesNLServicedMen,
            $outcomesNLServicedWomen
        ) = $this->getCountsByGender($outcomesNLClientsQuery);

        $outcomesNLMigrantsQuery = clone $outcomesNLClientsQuery;
        $outcomesNLServicedMigrants = $outcomesNLMigrantsQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'uQRP')
                ->orWhereJsonContains('d.data->6g6x', 'YNoK');
        })->count(DB::raw('distinct users.id'));

        $outcomesNLDrugUsersQuery = clone $outcomesNLClientsQuery;
        $outcomesNLServicedDrugUsers = $outcomesNLDrugUsersQuery
            ->whereJsonContains('d.data->6g6x', '2AJg')
            ->count(DB::raw('distinct users.id'));

        $outcomesNLPrisonersQuery = clone $outcomesNLClientsQuery;
        $outcomesNLServicedPrisoners = $outcomesNLPrisonersQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'gG99')
                ->orWhereJsonContains('d.data->6g6x', 'MuuE');
        })->count(DB::raw('distinct users.id'));

        $noOutcomesNLServicesQuery = $this->getServicesCountByProfileFieldQuery(
            null,
            null,
            [],
            $request,
            null,
            null,
            true,
            false,
            6,
            false,
            false,
            6,
            function ($query) use ($outcomesNLClientIds) {
                $this->getServicesCountByProfileFieldQueryUsersClause(
                    null,
                    null,
                    null,
                    [],
                    true,
                    false,
                    6
                )($query);

                static::addNLClause($query);

                $query->whereNotIn('users.id', $outcomesNLClientIds);
            }
        );

        $noOutcomesNLServices = $noOutcomesNLServicesQuery->count(DB::raw('distinct id'));

        $vulnerableNLQuery->whereNotIn('users.id', $outcomesNLClientIds);

        list(
            $noOutcomesNLServiced,
            $noOutcomesNLServicedMen,
            $noOutcomesNLServicedWomen
        ) = $this->getCountsByGender($vulnerableNLQuery);

        $noOutcomesNLMigrantsQuery = clone $vulnerableNLQuery;
        $noOutcomesNLServicedMigrants = $noOutcomesNLMigrantsQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'uQRP')
                ->orWhereJsonContains('d.data->6g6x', 'YNoK');
        })->count(DB::raw('distinct users.id'));

        $noOutcomesNLDrugUsersQuery = clone $vulnerableNLQuery;
        $noOutcomesNLServicedDrugUsers = $noOutcomesNLDrugUsersQuery
            ->whereJsonContains('d.data->6g6x', '2AJg')
            ->count(DB::raw('distinct users.id'));

        $noOutcomesNLPrisonersQuery = clone $vulnerableNLQuery;
        $noOutcomesNLServicedPrisoners = $noOutcomesNLPrisonersQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'gG99')
                ->orWhereJsonContains('d.data->6g6x', 'MuuE');
        })->count(DB::raw('distinct users.id'));

        static::addMDRClause($outcomesQuery);

        $outcomesMDR = 0;
        $outcomesTreatedMDR = 0;
        $outcomesRecoveredMDR = 0;
        $outcomesLostMDR = 0;
        $outcomesUnregisteredMDR = 0;
        $outcomesMovedMDR = 0;
        $outcomesDiedMDR = 0;
        $outcomesDiedTBMDR = 0;
        $outcomesJailedMDR = 0;
        $outcomesCuredMDR = 0;

        foreach ($outcomesQuery->get() as $client) {
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

                $outcomesMDR++;

                switch ($latestOutcome) {
                    case 'bJjG':
                    case 'PnKg':
                        $outcomesTreatedMDR++;
                        break;

                    case 'nXDJ':
                        $outcomesRecoveredMDR++;
                        break;

                    case 'dAtv':
                        $outcomesLostMDR++;
                        break;

                    case 'meHa':
                        $outcomesUnregisteredMDR++;

                        switch ($latestReason) {
                            case 'BtcT':
                                $outcomesDiedMDR++;

                                if ($latestCause == 'MppH') {
                                    $outcomesDiedTBMDR++;
                                }

                                break;

                            case 'Lwch':
                                $outcomesMovedMDR++;
                                break;

                            case 'KyEj':
                                $outcomesJailedMDR++;
                                break;

                            case 'Ze52':
                                $outcomesCuredMDR++;
                                break;
                        }

                        break;
                }
            }
        }

        static::addNLClause($outcomesQuery);

        $outcomesMDRNL = 0;
        $outcomesTreatedMDRNL = 0;
        $outcomesRecoveredMDRNL = 0;
        $outcomesLostMDRNL = 0;
        $outcomesUnregisteredMDRNL = 0;
        $outcomesMovedMDRNL = 0;
        $outcomesDiedMDRNL = 0;
        $outcomesDiedTBMDRNL = 0;
        $outcomesJailedMDRNL = 0;
        $outcomesCuredMDRNL = 0;

        foreach ($outcomesQuery->get() as $client) {
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

                $outcomesMDRNL++;

                switch ($latestOutcome) {
                    case 'bJjG':
                    case 'PnKg':
                        $outcomesTreatedMDRNL++;
                        break;

                    case 'nXDJ':
                        $outcomesRecoveredMDRNL++;
                        break;

                    case 'dAtv':
                        $outcomesLostMDRNL++;
                        break;

                    case 'meHa':
                        $outcomesUnregisteredMDRNL++;

                        switch ($latestReason) {
                            case 'BtcT':
                                $outcomesDiedMDRNL++;

                                if ($latestCause == 'MppH') {
                                    $outcomesDiedTBMDRNL++;
                                }

                                break;

                            case 'Lwch':
                                $outcomesMovedMDRNL++;
                                break;

                            case 'KyEj':
                                $outcomesJailedMDRNL++;
                                break;

                            case 'Ze52':
                                $outcomesCuredMDRNL++;
                                break;
                        }

                        break;
                }
            }
        }

        $childrenOutcomesQuery = User::where('roles', 'like', '%client%')
            ->whereHas('projects', function ($query) {
                $query->where('project_id', 6);
            })
            ->whereNotNull('d.data')
            ->whereNotNull('d.data->kB3w')
            ->whereJsonContains('d.data->6g6x', 'iNHa');

        $this->addDocumentsJoin($childrenOutcomesQuery);
        $childrenOutcomesQuery->groupBy('users.id');

        if ($request->till) {
            $childrenOutcomesQuery->where('d.data->kB3w', '<=', $request->till);
        }

        list(
            $informed,
            $informedMen,
            $informedWomen
        ) = $this->getCountsByProfileField('Y4N4', ['26bK', 'enqF'], [544, 545], $request, 'otherthan');

        if ($new) {
            list(
                $informedNew,
                $informedMenNew,
                $informedWomenNew
            ) = $this->getCountsByProfileField('Y4N4', ['26bK', 'enqF'], [544, 545], $request, 'otherthan', null, false, true);
        } else {
            $informedNew = null;
            $informedMenNew = null;
            $informedWomenNew = null;
        }

        list(
            $informedMahalla,
            $informedMahallaMale,
            $informedMahallaFemale
        ) = $this->getCountsByProfileField('Y4N4', 'kyLa', [544, 545], $request);

        if ($new) {
            list(
                $informedMahallaNew,
                $informedMahallaMaleNew,
                $informedMahallaFemaleNew,
            ) = $this->getCountsByProfileField('Y4N4', 'kyLa', [544, 545], $request, '=', null, false, true);
        } else {
            $informedMahallaNew = null;
            $informedMahallaMaleNew = null;
            $informedMahallaFemaleNew = null;
        }

        $informedMahallaGroupServices = $this->getServicesCountByProfileFieldQuery('Y4N4', 'kyLa', [545], $request)
            ->groupBy('start_date', 'start_time')
            ->get()
            ->count();

        $informedMahallaServices = $this->getServicesCountByProfileFieldQuery('Y4N4', 'kyLa', [544], $request)
            ->groupBy('start_date', 'start_time')
            ->get()
            ->count();

        $informedMahallasList = $this->getQueryByProfileField('Y4N4', 'kyLa', [544, 545], $request)
            ->groupBy('d.data->9x3m')
            ->get()
            ->map(function ($item) {
                return $item->profile['6'][0]->data['9x3m'];
            });

        list(
            $informed2
        ) = $this->getCountsByProfileField('Y4N4', 'jo8e', [544, 545], $request);

        if ($new) {
            list(
                $informed2New,
            ) = $this->getCountsByProfileField('Y4N4', 'jo8e', [544, 545], $request, '=', null, false, true);
        } else {
            $informed2New = null;
        }

        $informed2Services = $this->getServicesCountByProfileFieldQuery('Y4N4', 'jo8e', [544, 545], $request)
            ->groupBy('start_date', 'start_time')
            ->get()
            ->count();

        list(
            $informed3
        ) = $this->getCountsByProfileField('Y4N4', 'MPbS', [544, 545], $request);

        if ($new) {
            list(
                $informed3New,
            ) = $this->getCountsByProfileField('Y4N4', 'MPbS', [544, 545], $request, '=', null, false, true);
        } else {
            $informed3New = null;
        }

        $informed3Services = $this->getServicesCountByProfileFieldQuery('Y4N4', 'MPbS', [544, 545], $request)
            ->groupBy('start_date', 'start_time')
            ->get()
            ->count();

        list(
            $informed4
        ) = $this->getCountsByProfileField('Y4N4', '9sJk', [544, 545], $request);

        if ($new) {
            list(
                $informed4New,
            ) = $this->getCountsByProfileField('Y4N4', '9sJk', [544, 545], $request, '=', null, false, true);
        } else {
            $informed4New = null;
        }

        $informed4Services = $this->getServicesCountByProfileFieldQuery('Y4N4', '9sJk', [544, 545], $request)
            ->groupBy('start_date', 'start_time')
            ->get()
            ->count();

        $informed4List = $this->getQueryByProfileField('Y4N4', '9sJk', [544, 545], $request)
            ->groupBy('d.data->RCgx')
            ->get()
            ->count();

        list(
            $informed6
        ) = $this->getCountsByProfileField('Y4N4', '26bK', [544, 545], $request);

        if ($new) {
            list(
                $informed6New,
            ) = $this->getCountsByProfileField('Y4N4', '26bK', [544, 545], $request, '=', null, false, true);
        } else {
            $informed6New = null;
        }

        $informed6Services = $this->getServicesCountByProfileFieldQuery('Y4N4', '26bK', [544, 545], $request)
            ->groupBy('start_date', 'start_time')
            ->get()
            ->count();

        list(
            $informed7
        ) = $this->getCountsByProfileField('Y4N4', 'enqF', [544, 545], $request);

        if ($new) {
            list(
                $informed7New,
            ) = $this->getCountsByProfileField('Y4N4', 'enqF', [544, 545], $request, '=', null, false, true);
        } else {
            $informed7New = null;
        }

        $informed7Services = $this->getServicesCountByProfileFieldQuery('Y4N4', 'enqF', [544, 545], $request)
            ->groupBy('start_date', 'start_time')
            ->get()
            ->count();

        list(
            $informed8
        ) = $this->getCountsByProfileField(
            'Y4N4',
            ['6j2x', 'hB4Q', 'daf5', 'HjQ9', 'oCBQ', 'eEhD', 'unka', 'cutm', 'Kwwj', 'xJxu'],
            [545],
            $request,
            'in'
        );

        if ($new) {
            list(
                $informed8New,
            ) = $this->getCountsByProfileField(
                'Y4N4',
                ['6j2x', 'hB4Q', 'daf5', 'HjQ9', 'oCBQ', 'eEhD', 'unka', 'cutm', 'Kwwj', 'xJxu'],
                [545],
                $request,
                'in',
                null,
                false,
                true
            );
        } else {
            $informed8New = null;
        }

        $informed8Services = $this->getServicesCountByProfileFieldQuery(
            'Y4N4',
            ['6j2x', 'hB4Q', 'daf5', 'HjQ9', 'oCBQ', 'eEhD', 'unka', 'cutm', 'Kwwj', 'xJxu'],
            [545],
            $request,
            'in'
        )->groupBy('start_date', 'start_time')
            ->get()
            ->count();

        list(
            $informed5
        ) = $this->getCountsByProfileField('Y4N4', ['kyLa', 'jo8e', 'MPbS', '9sJk'], [544, 545], $request, 'otherthan');

        if ($new) {
            list(
                $informed5New,
            ) = $this->getCountsByProfileField(
                'Y4N4',
                ['kyLa', 'jo8e', 'MPbS', '9sJk'],
                [544, 545],
                $request,
                'otherthan',
                null,
                false,
                true
            );
        } else {
            $informed5New = null;
        }

        $informed5Services = $this->getServicesCountByProfileFieldQuery(
            'Y4N4',
            ['kyLa', 'jo8e', 'MPbS', '9sJk'],
            [544, 545],
            $request,
            'otherthan'
        )
            ->groupBy('start_date', 'start_time')
            ->get()
            ->count();

        $informed5List = $this->getQueryByProfileField(
            'Y4N4',
            ['kyLa', 'jo8e', 'MPbS', '9sJk'],
            [544, 545],
            $request,
            'otherthan'
        )
            ->groupBy(DB::raw(
                'if(json_unquote(json_extract(d.data, \'$."Y4N4"\')) = "YkWg", ' .
                    'json_unquote(json_extract(d.data, \'$."p5yi"\')), ' .
                        'if(json_unquote(json_extract(d.data, \'$."Y4N4"\')) = "uWkv", ' .
                            'json_unquote(json_extract(d.data, \'$."pPwH"\')), ' .
                            'json_unquote(json_extract(d.data, \'$."Y4N4"\'))))'
            ))
            ->get()
            ->count();

        list(
            $trained,
            $trainedMen,
            $trainedWomen
        ) = $this->getCountsByProfileField('Y4N4', null, [546], $request, 'notnull');

        if ($new) {
            list(
                $trainedNew,
                $trainedMenNew,
                $trainedWomenNew
            ) = $this->getCountsByProfileField('Y4N4', null, [546], $request, 'notnull', null, false, true);
        } else {
            $trainedNew = null;
            $trainedMenNew = null;
            $trainedWomenNew = null;
        }

        $vstQuery = User::where('roles', 'like', '%client%')
            ->whereHas('projects', function ($query) {
                $query->where('project_id', 6);
            })
            ->where('d53.data->ui2f', true)
            ->whereNotNull('d53.data->CGPJ');

        $this->addDocumentsJoin($vstQuery);
        $this->addDocumentsJoin($vstQuery, 53, false, true);

        $vstStartedQuery = clone $vstQuery;

        if ($request->from) {
            $vstStartedQuery->where('d53.data->CGPJ', '>=', $request->from);
        }

        if ($request->till) {
            $vstStartedQuery->where('d53.data->CGPJ', '<=', $request->till);
        }

        list(
            $vstStarted,
            $vstStartedMale,
            $vstStartedFemale,
        ) = $this->getCountsByGender($vstStartedQuery);

        $vstStartedMigrantsQuery = clone $vstStartedQuery;
        $vstStartedMigrants = $vstStartedMigrantsQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'uQRP')
                ->orWhereJsonContains('d.data->6g6x', 'YNoK');
        })
        ->count(DB::raw('distinct users.id'));

        $vstStartedPrisonersQuery = clone $vstStartedQuery;
        $vstStartedPrisoners = $vstStartedPrisonersQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'gG99')
                ->orWhereJsonContains('d.data->6g6x', 'MuuE');
        })
        ->count(DB::raw('distinct users.id'));

        $vstStartedDrugUsersQuery = clone $vstStartedQuery;
        $vstStartedDrugUsers = $vstStartedDrugUsersQuery
            ->whereJsonContains('d.data->6g6x', '2AJg')
            ->count(DB::raw('distinct users.id'));

        $vstStartedParentsQuery = clone $vstStartedQuery;
        $vstStartedParents = $vstStartedParentsQuery
            ->whereJsonContains('d.data->6g6x', 'aSu3')
            ->count(DB::raw('distinct users.id'));

        $vstStartedGeneralPopulationQuery = clone $vstStartedQuery;
        $vstStartedGeneralPopulation = $vstStartedGeneralPopulationQuery
            ->whereJsonContains('d.data->6g6x', 'bEk7')
            ->count(DB::raw('distinct users.id'));

        $vstStartedMDRQuery = clone $vstStartedQuery;

        static::addMDRClause($vstStartedMDRQuery);

        $vstStartedMDR = $vstStartedMDRQuery->count(DB::raw('distinct users.id'));

        $vstStartedAmbulatoryQuery = clone $vstStartedQuery;
        $vstStartedAmbulatory = $vstStartedAmbulatoryQuery
            ->whereJsonContains('d53.data->RMCN', '3yph')
            ->count(DB::raw('distinct users.id'));

        $vstStartedInHospitalQuery = clone $vstStartedQuery;
        $vstStartedInHospital = $vstStartedInHospitalQuery
            ->whereJsonContains('d53.data->RMCN', 'oaMX')
            ->count(DB::raw('distinct users.id'));

        $vstAdherenceQuery = clone $vstQuery;
        $this->addDocumentsJoin($vstAdherenceQuery, 52, false, true, function ($query) {
            $query->select('*', DB::raw('ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY json_unquote(json_extract(data, \'$."TNF5"\')) DESC) as rn'));
        });

        if ($request->from) {
            $vstAdherenceQuery->where('d52.data->TNF5', '>=', $request->from);
        }

        if ($request->till) {
            $vstAdherenceQuery->where('d52.data->TNF5', '<=', $request->till);
        }

        $vstHasReferenceQuery = clone $vstAdherenceQuery;
        $vstHasReferenceQuery->whereNotNull('d52.id');

        list(
            $vstHasReference,
            $vstHasReferenceMale,
            $vstHasReferenceFemale
        ) = $this->getCountsByGender($vstHasReferenceQuery);

        $vstHasReferenceMigrantsQuery = clone $vstHasReferenceQuery;
        $vstHasReferenceMigrants = $vstHasReferenceMigrantsQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'uQRP')
                ->orWhereJsonContains('d.data->6g6x', 'YNoK');
        })->count(DB::raw('distinct users.id'));

        $vstHasReferencePrisonersQuery = clone $vstHasReferenceQuery;
        $vstHasReferencePrisoners = $vstHasReferencePrisonersQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'gG99')
                ->orWhereJsonContains('d.data->6g6x', 'MuuE');
        })->count(DB::raw('distinct users.id'));

        $vstHasReferenceDrugUsersQuery = clone $vstHasReferenceQuery;
        $vstHasReferenceDrugUsers = $vstHasReferenceDrugUsersQuery
            ->whereJsonContains('d.data->6g6x', '2AJg')
            ->count(DB::raw('distinct users.id'));

        $vstHasReferenceParentsQuery = clone $vstHasReferenceQuery;
        $vstHasReferenceParents = $vstHasReferenceParentsQuery
            ->whereJsonContains('d.data->6g6x', 'aSu3')
            ->count(DB::raw('distinct users.id'));

        $vstHasReferenceGeneralPopulationQuery = clone $vstHasReferenceQuery;
        $vstHasReferenceGeneralPopulation = $vstHasReferenceGeneralPopulationQuery
            ->whereJsonContains('d.data->6g6x', 'bEk7')
            ->count(DB::raw('distinct users.id'));

        $vstHasReferenceMDRQuery = clone $vstHasReferenceQuery;

        static::addMDRClause($vstHasReferenceMDRQuery);

        $vstHasReferenceMDR = $vstHasReferenceMDRQuery->count(DB::raw('distinct users.id'));

        $vstNotStoppedQuery = clone $vstAdherenceQuery;
        $vstNotStoppedQuery->where('d52.data->JbaL', 'PnKg');
        $vstNotStopped = $vstNotStoppedQuery->count(DB::raw('distinct users.id'));

        $vstStoppedQuery = clone $vstAdherenceQuery;
        $vstStoppedQuery->where('d52.data->JbaL', 'A4Z9');
        $vstStopped = $vstStoppedQuery->count(DB::raw('distinct users.id'));

        $vstStoppedExcludedQuery = clone $vstStoppedQuery;
        $vstStoppedExcludedQuery->where('d52.data->d4Xj', true);
        $vstStoppedExcluded = $vstStoppedExcludedQuery->count(DB::raw('distinct users.id'));

        $vstStoppedFinishedQuery = clone $vstStoppedQuery;
        $vstStoppedFinishedQuery->where('d52.data->jBQK', true);
        $vstStoppedFinished = $vstStoppedFinishedQuery->count(DB::raw('distinct users.id'));

        $vstStoppedLostQuery = clone $vstStoppedQuery;
        $vstStoppedLostQuery->where('d52.data->BPcC', true);
        $vstStoppedLost = $vstStoppedLostQuery->count(DB::raw('distinct users.id'));

        $vstStoppedDiedQuery = clone $vstStoppedQuery;
        $vstStoppedDiedQuery->where('d52.data->7bx5', true);
        $vstStoppedDied = $vstStoppedDiedQuery->count(DB::raw('distinct users.id'));

        $vstStoppedGoneDCTQuery = clone $vstStoppedQuery;
        $vstStoppedGoneDCTQuery->where('d52.data->F4fi', true);
        $vstStoppedGoneDCT = $vstStoppedGoneDCTQuery->count(DB::raw('distinct users.id'));

        $vstStoppedImprisonedQuery = clone $vstStoppedQuery;
        $vstStoppedImprisonedQuery->where('d52.data->5upu', true);
        $vstStoppedImprisoned = $vstStoppedImprisonedQuery->count(DB::raw('distinct users.id'));

        $vstStoppedSuspendedQuery = clone $vstStoppedQuery;
        $vstStoppedSuspendedQuery->where('d52.data->nWKs', true);
        $vstStoppedSuspended = $vstStoppedSuspendedQuery->count(DB::raw('distinct users.id'));

        $vstStoppedSuspendedHospitalizedQuery = clone $vstStoppedSuspendedQuery;
        $vstStoppedSuspendedHospitalizedQuery->where('d52.data->8Rqv', true);
        $vstStoppedSuspendedHospitalized = $vstStoppedSuspendedHospitalizedQuery->count(DB::raw('distinct users.id'));

        $vstStoppedSuspendedLostDeviceQuery = clone $vstStoppedSuspendedQuery;
        $vstStoppedSuspendedLostDeviceQuery->where('d52.data->FxEX', true);
        $vstStoppedSuspendedLostDevice = $vstStoppedSuspendedLostDeviceQuery->count(DB::raw('distinct users.id'));

        $vstStoppedSuspendedGoneAbroadQuery = clone $vstStoppedSuspendedQuery;
        $vstStoppedSuspendedGoneAbroadQuery->where('d52.data->hAKs', true);
        $vstStoppedSuspendedGoneAbroad = $vstStoppedSuspendedGoneAbroadQuery->count(DB::raw('distinct users.id'));

        $vstStoppedSuspendedTechnicalQuery = clone $vstStoppedSuspendedQuery;
        $vstStoppedSuspendedTechnicalQuery->where('d52.data->3EcR', true);
        $vstStoppedSuspendedTechnical = $vstStoppedSuspendedTechnicalQuery->count(DB::raw('distinct users.id'));

        list(
            $vstVideoViewedClients,
            $vstVideoViewedClientsMale,
            $vstVideoViewedClientsFemale,
            $vstVideoViews
        ) = $this->getCountsByProfileField(null, null, [731], $request);

        $migrantsInformedOnlyQuery = User::where('roles', 'like', '%client%')
            ->where(function ($query) {
                $query->whereJsonContains('d.data->6g6x', 'uQRP')
                    ->orWhereJsonContains('d.data->6g6x', 'YNoK');
            })
            ->whereHas('activities', function ($query) {
                $query->where('project_id', 6)
                    ->whereIn('user_activity.part_id', [544, 545]);
            })
            ->whereDoesntHave('activities', function ($query) {
                $query->where('project_id', 6)
                    ->where(function ($query) {
                        $keyword = 'скрининг';

                        $query->where('title', 'like', "%$keyword%")
                            ->orWhere('description', 'like', "%$keyword%")
                            ->orWhereHas('timings', function ($query) use ($keyword) {
                                $query->where('comment', 'like', "%$keyword%");
                            });
                    });
            });

        $this->addDocumentsJoin($migrantsInformedOnlyQuery);

        list(
            $migrantsInformedOnly,
            $migrantsInformedOnlyMale,
            $migrantsInformedOnlyFemale,
        ) = $this->getCountsByGender($migrantsInformedOnlyQuery);

        $socialServicesNL = $this->getServicesCountByProfileField(
            null,
            null,
            [364, 365, 368, 369, 373],
            $request,
            null,
            null,
            true,
            false,
            6,
            false,
            false,
            6,
            true
        );

        $socialServicedNLQuery = $this->getQueryByProfileField(
            null,
            null,
            [364, 365, 368, 369, 373],
            $request,
            null,
            null,
            true,
            false,
            false,
            6,
            false,
            false,
            6,
            true
        );

        list(
            $socialServicedNL,
            $socialServicedNLMale,
            $socialServicedNLFemale,
        ) = $this->getCountsByGender($socialServicedNLQuery);

        $socialServicedNLMigrantsQuery = clone $socialServicedNLQuery;
        $socialServicedNLMigrants = $socialServicedNLMigrantsQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'uQRP')
                ->orWhereJsonContains('d.data->6g6x', 'YNoK');
        })
        ->count(DB::raw('distinct users.id'));

        $socialServicedNLPrisonersQuery = clone $socialServicedNLQuery;
        $socialServicedNLPrisoners = $socialServicedNLPrisonersQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'gG99')
                ->orWhereJsonContains('d.data->6g6x', 'MuuE');
        })
        ->count(DB::raw('distinct users.id'));

        $socialServicedNLDrugUsersQuery = clone $socialServicedNLQuery;
        $socialServicedNLDrugUsers = $socialServicedNLDrugUsersQuery
            ->whereJsonContains('d.data->6g6x', '2AJg')
            ->count(DB::raw('distinct users.id'));

        $psychoServicesNL = $this->getServicesCountByProfileField(
            null,
            null,
            [367, 371, 375, 376],
            $request,
            null,
            null,
            true,
            false,
            6,
            false,
            false,
            6,
            true
        );

        $psychoServicedNLQuery = $this->getQueryByProfileField(
            null,
            null,
            [367, 371, 375, 376],
            $request,
            null,
            null,
            true,
            false,
            false,
            6,
            false,
            false,
            6,
            true
        );

        list(
            $psychoServicedNL,
            $psychoServicedNLMale,
            $psychoServicedNLFemale,
        ) = $this->getCountsByGender($psychoServicedNLQuery);

        $psychoServicedNLMigrantsQuery = clone $psychoServicedNLQuery;
        $psychoServicedNLMigrants = $psychoServicedNLMigrantsQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'uQRP')
                ->orWhereJsonContains('d.data->6g6x', 'YNoK');
        })
        ->count(DB::raw('distinct users.id'));

        $psychoServicedNLPrisonersQuery = clone $psychoServicedNLQuery;
        $psychoServicedNLPrisoners = $psychoServicedNLPrisonersQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'gG99')
                ->orWhereJsonContains('d.data->6g6x', 'MuuE');
        })
        ->count(DB::raw('distinct users.id'));

        $psychoServicedNLDrugUsersQuery = clone $psychoServicedNLQuery;
        $psychoServicedNLDrugUsers = $psychoServicedNLDrugUsersQuery
            ->whereJsonContains('d.data->6g6x', '2AJg')
            ->count(DB::raw('distinct users.id'));

        $legalServicesNL = $this->getServicesCountByProfileField(
            null,
            null,
            [366, 370, 374],
            $request,
            null,
            null,
            true,
            false,
            6,
            false,
            false,
            6,
            true
        );

        $legalServicedNLQuery = $this->getQueryByProfileField(
            null,
            null,
            [366, 370, 374],
            $request,
            null,
            null,
            true,
            false,
            false,
            6,
            false,
            false,
            6,
            true
        );

        list(
            $legalServicedNL,
            $legalServicedNLMale,
            $legalServicedNLFemale,
        ) = $this->getCountsByGender($legalServicedNLQuery);

        $legalServicedNLMigrantsQuery = clone $legalServicedNLQuery;
        $legalServicedNLMigrants = $legalServicedNLMigrantsQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'uQRP')
                ->orWhereJsonContains('d.data->6g6x', 'YNoK');
        })
        ->count(DB::raw('distinct users.id'));

        $legalServicedNLPrisonersQuery = clone $legalServicedNLQuery;
        $legalServicedNLPrisoners = $legalServicedNLPrisonersQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'gG99')
                ->orWhereJsonContains('d.data->6g6x', 'MuuE');
        })
        ->count(DB::raw('distinct users.id'));

        $legalServicedNLDrugUsersQuery = clone $legalServicedNLQuery;
        $legalServicedNLDrugUsers = $legalServicedNLDrugUsersQuery
            ->whereJsonContains('d.data->6g6x', '2AJg')
            ->count(DB::raw('distinct users.id'));

        $socServicesNL = $this->getServicesCountByProfileField(
            null,
            null,
            [365, 369, 373, 474, 363, 362, 361, 543, 377, 379, 528, 527, 364, 368, 372],
            $request,
            null,
            null,
            true,
            false,
            6,
            false,
            false,
            6,
            true,
            'ТБ',
            true
        );

        $socServicedNLQuery = $this->getQueryByProfileField(
            null,
            null,
            [365, 369, 373, 474, 363, 362, 361, 543, 377, 379, 528, 527, 364, 368, 372],
            $request,
            null,
            null,
            true,
            false,
            false,
            6,
            false,
            false,
            6,
            true,
            'ТБ',
            true
        );

        list(
            $socServicedNL,
            $socServicedNLMale,
            $socServicedNLFemale,
        ) = $this->getCountsByGender($socServicedNLQuery);

        $socServicedNLMigrantsQuery = clone $socServicedNLQuery;
        $socServicedNLMigrants = $socServicedNLMigrantsQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'uQRP')
                ->orWhereJsonContains('d.data->6g6x', 'YNoK');
        })
        ->count(DB::raw('distinct users.id'));

        $socServicedNLPrisonersQuery = clone $socServicedNLQuery;
        $socServicedNLPrisoners = $socServicedNLPrisonersQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'gG99')
                ->orWhereJsonContains('d.data->6g6x', 'MuuE');
        })
        ->count(DB::raw('distinct users.id'));

        $socServicedNLDrugUsersQuery = clone $socServicedNLQuery;
        $socServicedNLDrugUsers = $socServicedNLDrugUsersQuery
            ->whereJsonContains('d.data->6g6x', '2AJg')
            ->count(DB::raw('distinct users.id'));

        $phthiServicesNL = $this->getServicesCountByProfileField(
            null,
            null,
            [364, 368, 372],
            $request,
            null,
            null,
            true,
            false,
            6,
            false,
            false,
            6,
            true,
            'ТБ'
        );

        $phthiServicedNLQuery = $this->getQueryByProfileField(
            null,
            null,
            [364, 368, 372],
            $request,
            null,
            null,
            true,
            false,
            false,
            6,
            false,
            false,
            6,
            true,
            'ТБ'
        );

        list(
            $phthiServicedNL,
            $phthiServicedNLMale,
            $phthiServicedNLFemale,
        ) = $this->getCountsByGender($phthiServicedNLQuery);

        $phthiServicedNLMigrantsQuery = clone $phthiServicedNLQuery;
        $phthiServicedNLMigrants = $phthiServicedNLMigrantsQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'uQRP')
                ->orWhereJsonContains('d.data->6g6x', 'YNoK');
        })
        ->count(DB::raw('distinct users.id'));

        $phthiServicedNLPrisonersQuery = clone $phthiServicedNLQuery;
        $phthiServicedNLPrisoners = $phthiServicedNLPrisonersQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'gG99')
                ->orWhereJsonContains('d.data->6g6x', 'MuuE');
        })
        ->count(DB::raw('distinct users.id'));

        $phthiServicedNLDrugUsersQuery = clone $phthiServicedNLQuery;
        $phthiServicedNLDrugUsers = $phthiServicedNLDrugUsersQuery
            ->whereJsonContains('d.data->6g6x', '2AJg')
            ->count(DB::raw('distinct users.id'));

        $socialServicesMDRNL = $this->getServicesCountByProfileField(
            null,
            null,
            [364, 365, 368, 369, 373],
            $request,
            null,
            null,
            true,
            true,
            6,
            false,
            false,
            6,
            true
        );

        $socialServicedMDRNLQuery = $this->getQueryByProfileField(
            null,
            null,
            [364, 365, 368, 369, 373],
            $request,
            null,
            null,
            true,
            false,
            true,
            6,
            false,
            false,
            6,
            true
        );

        list(
            $socialServicedMDRNL,
            $socialServicedMDRNLMale,
            $socialServicedMDRNLFemale,
        ) = $this->getCountsByGender($socialServicedMDRNLQuery);

        $socialServicedMDRNLMigrantsQuery = clone $socialServicedMDRNLQuery;
        $socialServicedMDRNLMigrants = $socialServicedMDRNLMigrantsQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'uQRP')
                ->orWhereJsonContains('d.data->6g6x', 'YNoK');
        })
        ->count(DB::raw('distinct users.id'));

        $socialServicedMDRNLPrisonersQuery = clone $socialServicedMDRNLQuery;
        $socialServicedMDRNLPrisoners = $socialServicedMDRNLPrisonersQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'gG99')
                ->orWhereJsonContains('d.data->6g6x', 'MuuE');
        })
        ->count(DB::raw('distinct users.id'));

        $socialServicedMDRNLDrugUsersQuery = clone $socialServicedMDRNLQuery;
        $socialServicedMDRNLDrugUsers = $socialServicedMDRNLDrugUsersQuery
            ->whereJsonContains('d.data->6g6x', '2AJg')
            ->count(DB::raw('distinct users.id'));

        $psychoServicesMDRNL = $this->getServicesCountByProfileField(
            null,
            null,
            [367, 371, 375, 376],
            $request,
            null,
            null,
            true,
            true,
            6,
            false,
            false,
            6,
            true
        );

        $psychoServicedMDRNLQuery = $this->getQueryByProfileField(
            null,
            null,
            [367, 371, 375, 376],
            $request,
            null,
            null,
            true,
            false,
            true,
            6,
            false,
            false,
            6,
            true
        );

        list(
            $psychoServicedMDRNL,
            $psychoServicedMDRNLMale,
            $psychoServicedMDRNLFemale,
        ) = $this->getCountsByGender($psychoServicedMDRNLQuery);

        $psychoServicedMDRNLMigrantsQuery = clone $psychoServicedMDRNLQuery;
        $psychoServicedMDRNLMigrants = $psychoServicedMDRNLMigrantsQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'uQRP')
                ->orWhereJsonContains('d.data->6g6x', 'YNoK');
        })
        ->count(DB::raw('distinct users.id'));

        $psychoServicedMDRNLPrisonersQuery = clone $psychoServicedMDRNLQuery;
        $psychoServicedMDRNLPrisoners = $psychoServicedMDRNLPrisonersQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'gG99')
                ->orWhereJsonContains('d.data->6g6x', 'MuuE');
        })
        ->count(DB::raw('distinct users.id'));

        $psychoServicedMDRNLDrugUsersQuery = clone $psychoServicedMDRNLQuery;
        $psychoServicedMDRNLDrugUsers = $psychoServicedMDRNLDrugUsersQuery
            ->whereJsonContains('d.data->6g6x', '2AJg')
            ->count(DB::raw('distinct users.id'));

        $legalServicesMDRNL = $this->getServicesCountByProfileField(
            null,
            null,
            [366, 370],
            $request,
            null,
            null,
            true,
            true,
            6,
            false,
            false,
            6,
            true
        );

        $legalServicedMDRNLQuery = $this->getQueryByProfileField(
            null,
            null,
            [366, 370],
            $request,
            null,
            null,
            true,
            false,
            true,
            6,
            false,
            false,
            6,
            true
        );

        list(
            $legalServicedMDRNL,
            $legalServicedMDRNLMale,
            $legalServicedMDRNLFemale,
        ) = $this->getCountsByGender($legalServicedMDRNLQuery);

        $legalServicedMDRNLMigrantsQuery = clone $legalServicedMDRNLQuery;
        $legalServicedMDRNLMigrants = $legalServicedMDRNLMigrantsQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'uQRP')
                ->orWhereJsonContains('d.data->6g6x', 'YNoK');
        })
        ->count(DB::raw('distinct users.id'));

        $legalServicedMDRNLPrisonersQuery = clone $legalServicedMDRNLQuery;
        $legalServicedMDRNLPrisoners = $legalServicedMDRNLPrisonersQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'gG99')
                ->orWhereJsonContains('d.data->6g6x', 'MuuE');
        })
        ->count(DB::raw('distinct users.id'));

        $legalServicedMDRNLDrugUsersQuery = clone $legalServicedMDRNLQuery;
        $legalServicedMDRNLDrugUsers = $legalServicedMDRNLDrugUsersQuery
            ->whereJsonContains('d.data->6g6x', '2AJg')
            ->count(DB::raw('distinct users.id'));

        $trainings = $this->getServicesCountByProfileField(null, null, [546], $request);

        $servicesOutreachNLQuery = Activity::where('activities.project_id', 6);

        static::addOutreachServicesClause($servicesOutreachNLQuery, $request, [731], null, false, true);
        static::addNLClause($servicesOutreachNLQuery);
        $this->addDocumentsJoin($servicesOutreachNLQuery);

        $servicesOutreachNL = $servicesOutreachNLQuery->count(DB::raw('distinct activities.id'));

        $servicedOutreachNLQuery = User::where('roles', 'like', '%client%');

        static::addOutreachClause($servicedOutreachNLQuery, $request);
        static::addNLClause($servicedOutreachNLQuery);
        $this->addDocumentsJoin($servicedOutreachNLQuery);

        list(
            $servicedOutreachNL,
            $servicedOutreachNLMale,
            $servicedOutreachNLFemale,
        ) = $this->getCountsByGender($servicedOutreachNLQuery);

        $servicedOutreachNLMigrantsQuery = clone $servicedOutreachNLQuery;
        $servicedOutreachNLMigrants = $servicedOutreachNLMigrantsQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'uQRP')
                ->orWhereJsonContains('d.data->6g6x', 'YNoK');
        })
        ->count(DB::raw('distinct users.id'));

        $servicedOutreachNLPrisonersQuery = clone $servicedOutreachNLQuery;
        $servicedOutreachNLPrisoners = $servicedOutreachNLPrisonersQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'gG99')
                ->orWhereJsonContains('d.data->6g6x', 'MuuE');
        })
        ->count(DB::raw('distinct users.id'));

        $servicedOutreachNLDrugUsersQuery = clone $servicedOutreachNLQuery;
        $servicedOutreachNLDrugUsers = $servicedOutreachNLDrugUsersQuery
            ->whereJsonContains('d.data->6g6x', '2AJg')
            ->count(DB::raw('distinct users.id'));

        $psychoServicesOutreachNLQuery = Activity::where('activities.project_id', 6);

        static::addOutreachServicesClause($psychoServicesOutreachNLQuery, $request, [367, 371, 375, 376]);
        static::addNLClause($psychoServicesOutreachNLQuery);
        $this->addDocumentsJoin($psychoServicesOutreachNLQuery);

        $psychoServicesOutreachNL = $psychoServicesOutreachNLQuery->count(DB::raw('distinct activities.id'));

        $psychoServicedOutreachNLQuery = User::where('roles', 'like', '%client%');

        static::addOutreachClause($psychoServicedOutreachNLQuery, $request, [367, 371, 375, 376]);
        static::addNLClause($psychoServicedOutreachNLQuery);
        $this->addDocumentsJoin($psychoServicedOutreachNLQuery);

        list(
            $psychoServicedOutreachNL,
            $psychoServicedOutreachNLMale,
            $psychoServicedOutreachNLFemale,
        ) = $this->getCountsByGender($psychoServicedOutreachNLQuery);

        $psychoServicedOutreachNLMigrantsQuery = clone $psychoServicedOutreachNLQuery;
        $psychoServicedOutreachNLMigrants = $psychoServicedOutreachNLMigrantsQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'uQRP')
                ->orWhereJsonContains('d.data->6g6x', 'YNoK');
        })
        ->count(DB::raw('distinct users.id'));

        $psychoServicedOutreachNLPrisonersQuery = clone $psychoServicedOutreachNLQuery;
        $psychoServicedOutreachNLPrisoners = $psychoServicedOutreachNLPrisonersQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'gG99')
                ->orWhereJsonContains('d.data->6g6x', 'MuuE');
        })
        ->count(DB::raw('distinct users.id'));

        $psychoServicedOutreachNLDrugUsersQuery = clone $psychoServicedOutreachNLQuery;
        $psychoServicedOutreachNLDrugUsers = $psychoServicedOutreachNLDrugUsersQuery
            ->whereJsonContains('d.data->6g6x', '2AJg')
            ->count(DB::raw('distinct users.id'));

        $legalServicesOutreachNLQuery = Activity::where('activities.project_id', 6);

        static::addOutreachServicesClause($legalServicesOutreachNLQuery, $request, [366, 370]);
        static::addNLClause($legalServicesOutreachNLQuery);
        $this->addDocumentsJoin($legalServicesOutreachNLQuery);

        $legalServicesOutreachNL = $legalServicesOutreachNLQuery->count(DB::raw('distinct activities.id'));

        $legalServicedOutreachNLQuery = User::where('roles', 'like', '%client%');

        static::addOutreachClause($legalServicedOutreachNLQuery, $request, [366, 370]);
        static::addNLClause($legalServicedOutreachNLQuery);
        $this->addDocumentsJoin($legalServicedOutreachNLQuery);

        list(
            $legalServicedOutreachNL,
            $legalServicedOutreachNLMale,
            $legalServicedOutreachNLFemale,
        ) = $this->getCountsByGender($legalServicedOutreachNLQuery);

        $legalServicedOutreachNLMigrantsQuery = clone $legalServicedOutreachNLQuery;
        $legalServicedOutreachNLMigrants = $legalServicedOutreachNLMigrantsQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'uQRP')
                ->orWhereJsonContains('d.data->6g6x', 'YNoK');
        })
        ->count(DB::raw('distinct users.id'));

        $legalServicedOutreachNLPrisonersQuery = clone $legalServicedOutreachNLQuery;
        $legalServicedOutreachNLPrisoners = $legalServicedOutreachNLPrisonersQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'gG99')
                ->orWhereJsonContains('d.data->6g6x', 'MuuE');
        })
        ->count(DB::raw('distinct users.id'));

        $legalServicedOutreachNLDrugUsersQuery = clone $legalServicedOutreachNLQuery;
        $legalServicedOutreachNLDrugUsers = $legalServicedOutreachNLDrugUsersQuery
            ->whereJsonContains('d.data->6g6x', '2AJg')
            ->count(DB::raw('distinct users.id'));

        $socServicesOutreachNLQuery = Activity::where('activities.project_id', 6);

        static::addOutreachServicesClause(
            $socServicesOutreachNLQuery,
            $request,
            [365, 369, 373, 474, 363, 362, 361, 543, 377, 379, 528, 527, 364, 368, 372],
            'ТБ',
            true
        );
        static::addNLClause($socServicesOutreachNLQuery);
        $this->addDocumentsJoin($socServicesOutreachNLQuery);

        $socServicesOutreachNL = $socServicesOutreachNLQuery->count(DB::raw('distinct activities.id'));

        $socServicedOutreachNLQuery = User::where('roles', 'like', '%client%');

        static::addOutreachClause(
            $socServicedOutreachNLQuery,
            $request,
            [365, 369, 373, 474, 363, 362, 361, 543, 377, 379, 528, 527, 364, 368, 372],
            'ТБ',
            true
        );
        static::addNLClause($socServicedOutreachNLQuery);
        $this->addDocumentsJoin($socServicedOutreachNLQuery);

        list(
            $socServicedOutreachNL,
            $socServicedOutreachNLMale,
            $socServicedOutreachNLFemale,
        ) = $this->getCountsByGender($socServicedOutreachNLQuery);

        $socServicedOutreachNLMigrantsQuery = clone $socServicedOutreachNLQuery;
        $socServicedOutreachNLMigrants = $socServicedOutreachNLMigrantsQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'uQRP')
                ->orWhereJsonContains('d.data->6g6x', 'YNoK');
        })
        ->count(DB::raw('distinct users.id'));

        $socServicedOutreachNLPrisonersQuery = clone $socServicedOutreachNLQuery;
        $socServicedOutreachNLPrisoners = $socServicedOutreachNLPrisonersQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'gG99')
                ->orWhereJsonContains('d.data->6g6x', 'MuuE');
        })
        ->count(DB::raw('distinct users.id'));

        $socServicedOutreachNLDrugUsersQuery = clone $socServicedOutreachNLQuery;
        $socServicedOutreachNLDrugUsers = $socServicedOutreachNLDrugUsersQuery
            ->whereJsonContains('d.data->6g6x', '2AJg')
            ->count(DB::raw('distinct users.id'));

        $phthiServicesOutreachNLQuery = Activity::where('activities.project_id', 6);

        static::addOutreachServicesClause($phthiServicesOutreachNLQuery, $request, [364, 368, 372], 'ТБ');
        static::addNLClause($phthiServicesOutreachNLQuery);
        $this->addDocumentsJoin($phthiServicesOutreachNLQuery);

        $phthiServicesOutreachNL = $phthiServicesOutreachNLQuery->count(DB::raw('distinct activities.id'));

        $phthiServicedOutreachNLQuery = User::where('roles', 'like', '%client%');

        static::addOutreachClause($phthiServicedOutreachNLQuery, $request, [364, 368, 372], 'ТБ');
        static::addNLClause($phthiServicedOutreachNLQuery);
        $this->addDocumentsJoin($phthiServicedOutreachNLQuery);

        list(
            $phthiServicedOutreachNL,
            $phthiServicedOutreachNLMale,
            $phthiServicedOutreachNLFemale,
        ) = $this->getCountsByGender($phthiServicedOutreachNLQuery);

        $phthiServicedOutreachNLMigrantsQuery = clone $phthiServicedOutreachNLQuery;
        $phthiServicedOutreachNLMigrants = $phthiServicedOutreachNLMigrantsQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'uQRP')
                ->orWhereJsonContains('d.data->6g6x', 'YNoK');
        })
        ->count(DB::raw('distinct users.id'));

        $phthiServicedOutreachNLPrisonersQuery = clone $phthiServicedOutreachNLQuery;
        $phthiServicedOutreachNLPrisoners = $phthiServicedOutreachNLPrisonersQuery->where(function ($query) {
            $query->whereJsonContains('d.data->6g6x', 'gG99')
                ->orWhereJsonContains('d.data->6g6x', 'MuuE');
        })
        ->count(DB::raw('distinct users.id'));

        $phthiServicedOutreachNLDrugUsersQuery = clone $phthiServicedOutreachNLQuery;
        $phthiServicedOutreachNLDrugUsers = $phthiServicedOutreachNLDrugUsersQuery
            ->whereJsonContains('d.data->6g6x', '2AJg')
            ->count(DB::raw('distinct users.id'));

        $childrenQuery = $this->getQueryByProfileField('6g6x', 'iNHa', [], $request, 'contains');

        $childrenQueryFacility1 = clone $childrenQuery;
        $childrenQueryFacility1->where('d.data->6uM3', '6Dr8');
        $childrenFacility1 = $childrenQueryFacility1->count(DB::raw('distinct users.id'));

        $childrenQueryFacility2 = clone $childrenQuery;
        $childrenQueryFacility2->where('d.data->6uM3', 'wpBE');
        $childrenFacility2 = $childrenQueryFacility2->count(DB::raw('distinct users.id'));

        $childrenQueryFacility3 = clone $childrenQuery;
        $childrenQueryFacility3->where('d.data->6uM3', 'ZqZj');
        $childrenFacility3 = $childrenQueryFacility3->count(DB::raw('distinct users.id'));

        $childrenQueryFacility4 = clone $childrenQuery;
        $childrenQueryFacility4->where('d.data->6uM3', 'X5Zx');
        $childrenFacility4 = $childrenQueryFacility4->count(DB::raw('distinct users.id'));

        $childrenQueryFacility5 = clone $childrenQuery;
        $childrenQueryFacility5->where('d.data->6uM3', 'ebBT');
        $childrenFacility5 = $childrenQueryFacility5->count(DB::raw('distinct users.id'));

        $childrenQueryFacility6 = clone $childrenQuery;
        $childrenQueryFacility6->where('d.data->6uM3', 'DqoT');
        $childrenFacility6 = $childrenQueryFacility6->count(DB::raw('distinct users.id'));

        $childrenQueryFacility7 = clone $childrenQuery;
        $childrenQueryFacility7->where('d.data->6uM3', 'qPcj');
        $childrenFacility7 = $childrenQueryFacility7->count(DB::raw('distinct users.id'));

        $childrenQueryFacility8 = clone $childrenQuery;
        $childrenQueryFacility8->where('d.data->6uM3', 'mjSk');
        $childrenFacility8 = $childrenQueryFacility8->count(DB::raw('distinct users.id'));

        $childrenQueryFacility9 = clone $childrenQuery;
        $childrenQueryFacility9->where('d.data->6uM3', 'wofi');
        $childrenFacility9 = $childrenQueryFacility9->count(DB::raw('distinct users.id'));

        $childrenQueryFacility10 = clone $childrenQuery;
        $childrenQueryFacility10->where('d.data->6uM3', 'J6h6');
        $childrenFacility10 = $childrenQueryFacility10->count(DB::raw('distinct users.id'));

        $childrenQueryNoFacility = clone $childrenQuery;
        $childrenQueryNoFacility->whereNull('d.data->6uM3');
        $childrenNoFacility = $childrenQueryNoFacility->count(DB::raw('distinct users.id'));

        $childrenTBInfected = $childrenQuery->where(function ($query) {
            $query->where('d.data->rjWN', 'LBj4')
                ->orWhere('d.data->eNaZ', 'ELWf')
                ->orWhere('d.data->d6XS', 'ZkJZ')
                ->orWhere('d.data->byXk', 'uw7C');
        })->count(DB::raw('distinct users.id'));

        $indicators = [
            [
                'title' => 'Показатели ETBU',
                'items' => [
                    [
                        [
                            $totalServices,
                            'всего услуг предоставлено всем категориям клиентов проекта, кроме партнёров',
                            'services?projects=6&profileField=6g6x&profileOp=notin&profileValue=HeBk' .
                            '&parts=731&inverse=1' . $datesQuery
                        ],
                        [
                            $totalVulnerableServices,
                            'услуг предоставлено представителям уязвимых групп (c ТБ и без)',
                            'services?projects=6&vuln=2&parts=731&inverse=1' . $datesQuery
                        ],
                        [
                            $legalServices,
                            'юридических услуг оказано',
                            'services?projects=6&parts=366&parts=370' . $datesQuery,
                            $legalServicesNew
                        ],
                        [
                            $legal,
                            'человек получили юридическую помощь',
                            'clients?project=6&parts=366&parts=370' . $datesQuery,
                            $legalNew
                        ],
                        [
                            $legalNeeded,
                            'человек нуждаются в услугах юриста',
                            'clients?project=6&profileField=kfbv&profileOp=true',
                        ]
                    ],
                    [
                        [
                            $vulnerablePatientSchoolServices,
                            '"школ пациента" проведено для представителей уязвимых групп с ТБ',
                            'services?projects=6' .
                                '&vuln=1&parts=372&parts=372&parts=373&parts=374&parts=375&parts=376&parts=474' .
                                    '&parts=475&parts=479&groupby=start_date' .
                                        ($customAccess ? '&verified=0' : '') . $datesQuery
                        ],
                        [
                            $vulnerablePatientSchool,
                            'представителей уязвимых групп с ТБ приняли участие в "школах пациента"',
                            'clients?project=6' .
                                '&vuln=1&parts=372&parts=372&parts=373&parts=374&parts=375&parts=376&parts=474' .
                                    '&parts=475&parts=479' . $datesQuery,
                            $vulnerablePatientSchoolNew
                        ],
                        [
                            $vulnerablePatientSchoolMale,
                            'мужчин',
                            'clients?project=6' .
                                '&vuln=1&parts=372&parts=372&parts=373&parts=374&parts=375&parts=376&parts=474' .
                                    '&parts=475&parts=479&gender=male' . $datesQuery,
                            $vulnerablePatientSchoolMaleNew
                        ],
                        [
                            $vulnerablePatientSchoolFemale,
                            'женщин',
                            'clients?project=6' .
                                '&vuln=1&parts=372&parts=372&parts=373&parts=374&parts=375&parts=376&parts=474' .
                                    '&parts=475&parts=479&gender=female' . $datesQuery,
                            $vulnerablePatientSchoolFemaleNew,
                        ],
                        [
                            $vulnerablePatientSchoolParticipations,
                            'всего участий в "школах пациента" для представителей уязвимых групп с ТБ',
                        ],
                    ],
                    [
                        [
                            $childrenPatientSchoolServices,
                            '"школ пациента" проведено для детей',
                            'services?projects=6' .
                                '&profileField=6g6x&profileValue=iNHa&profileOp=contains&parts=372&parts=372' .
                                    '&parts=373&parts=374&parts=375&parts=376&parts=474&parts=475&parts=479' .
                                '&groupby=start_date' . ($customAccess ? '&verified=0' : '') . $datesQuery
                        ],
                        [
                            $childrenPatientSchool,
                            'детей приняли участие в "школах пациента"',
                            'clients?project=6' .
                                '&profileField=6g6x&profileValue=iNHa&profileOp=contains' .
                                '&parts=372&parts=372&parts=373&parts=374&parts=375&parts=376&parts=474' .
                                    '&parts=475&parts=479' .
                                $datesQuery,
                            $childrenPatientSchoolNew
                        ],
                        [
                            $childrenPatientSchoolMale,
                            'мальчиков',
                            'clients?project=6' .
                                '&profileField=6g6x&profileValue=iNHa&profileOp=contains' .
                                '&parts=372&parts=372&parts=373&parts=374&parts=375&parts=376&parts=474' .
                                    '&parts=475&parts=479&gender=male' . $datesQuery,
                            $childrenPatientSchoolMaleNew
                        ],
                        [
                            $childrenPatientSchoolFemale,
                            'девочек',
                            'clients?project=6' .
                                '&profileField=6g6x&profileValue=iNHa&profileOp=contains' .
                                '&parts=372&parts=372&parts=373&parts=374&parts=375&parts=376&parts=474&parts=475' .
                                    '&parts=479&gender=female' . $datesQuery,
                            $childrenPatientSchoolFemaleNew
                        ],
                        [
                            $childrenPatientSchoolParticipations,
                            'всего участий в "школах пациента" для детей',
                        ],
                    ],
                    [
                        [
                            $parentPatientSchoolServices,
                            '"школ пациента" проведено для родителей',
                            'services?projects=6' .
                                '&profileField=6g6x&profileValue=aSu3&profileOp=contains&parts=372&parts=372' .
                                    '&parts=373&parts=374&parts=375&parts=376&parts=474&parts=475&parts=479' .
                                '&groupby=start_date' . ($customAccess ? '&verified=0' : '') . $datesQuery
                        ],
                        [
                            $parentPatientSchool,
                            'родителей приняли участие в "школах пациента"',
                            'clients?project=6' .
                                '&profileField=6g6x&profileValue=aSu3&profileOp=contains' .
                                '&parts=372&parts=372&parts=373&parts=374&parts=375&parts=376&parts=474' .
                                    '&parts=475&parts=479' .
                                $datesQuery,
                            $parentPatientSchoolNew
                        ],
                        [
                            $parentPatientSchoolMale,
                            'мужчин',
                            'clients?project=6' .
                                '&profileField=6g6x&profileValue=aSu3&profileOp=contains' .
                                '&parts=372&parts=372&parts=373&parts=374&parts=375&parts=376&parts=474' .
                                    '&parts=475&parts=479&gender=male' . $datesQuery,
                            $parentPatientSchoolMaleNew
                        ],
                        [
                            $parentPatientSchoolFemale,
                            'женщин',
                            'clients?project=6' .
                                '&profileField=6g6x&profileValue=aSu3&profileOp=contains' .
                                '&parts=372&parts=372&parts=373&parts=374&parts=375&parts=376&parts=474&parts=475' .
                                    '&parts=479&gender=female' . $datesQuery,
                            $parentPatientSchoolFemaleNew
                        ],
                        [
                            $parentPatientSchoolParticipations,
                            'всего участий в "школах пациента" для родителей',
                        ],
                    ],
                    [
                        'title' => '1. Аутрич',
                        'level' => 0
                    ],
                    [
                        [
                            $screened,
                            'представителей уязвимых групп (мигранты, освободившиеся, ПН, ' .
                                'люди с ограниченным доступом к медуслугам), прошедших скрининг на ТБ (анкета)',
                            'clients?project=6&searchActivities=скрининг&vuln=3' . $datesQuery
                        ],
                        [
                            $escorted,
                            'представителей уязвимых групп (мигранты, освободившиеся, ПН, ' .
                                'люди с ограниченным доступом к медуслугам) ' .
                                'были сопровождены для тестирования на туберкулез',
                            'clients?project=6&examinedAfter=2020-09-01&searchActivities=тб' .
                                '&parts=362&parts=363&examinedFrom=us' . $datesQuery,
                            $escortedNew
                        ],
                        [
                            $transported,
                            'представителей уязвимых групп (мигранты, освободившиеся, ПН, ' .
                                'люди с ограниченным доступом к медуслугам) ' .
                                'были транспортированы для тестирования на туберкулез',
                            'clients?project=6&examinedAfter=2020-09-01&searchActivities=тб' .
                                '&parts=363&examinedFrom=us' . $datesQuery,
                            $transportedNew
                        ],
                        [
                            $transportedServices,
                            'услуг по транспортировке для тестирования на туберкулез оказано представителем уязвимых ' .
                                'групп (мигранты, освободившиеся, ПН, люди с ограниченным доступом к медуслугам)',
                            'services?projects=6&examinedAfter=2020-09-01&searchActivities=тб&parts=363' .
                                '&examinedFrom=us' . $datesQuery,
                        ],
                    ],
                    [
                        [
                            $examinedTotal,
                            'всего прошедших тестирование на туберкулез',
                            'clients?project=6&examinedAfter=2020-09-01&dateFilter=examined' .
                                '&examinedFrom=us' . $datesQuery,
                            $examinedTotalNew
                        ],
                        [
                            $examined,
                            'представителей уязвимых групп (мигранты, освободившиеся, ПН, ' .
                                'люди с ограниченным доступом к медуслугам)',
                            'clients?project=6&examinedAfter=2020-09-01&dateFilter=examined' .
                                '&examinedFrom=us&vuln=3' . $datesQuery,
                            $examinedNew
                        ],
                        [
                            $examinedParents,
                            'родителей',
                            'clients?project=6&examinedAfter=2020-09-01&dateFilter=examined&examinedFrom=us' .
                                '&profileField3=6g6x&profileValue3=aSu3&profileOp3=contains' . $datesQuery,
                            $examinedParentsNew
                        ],
                        [
                            $examinedChildren,
                            'детей',
                            'clients?project=6&examinedAfter=2020-09-01&dateFilter=examined&examinedFrom=us' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=contains' . $datesQuery,
                            $examinedChildrenNew
                        ],
                        [
                            $examinedGeneral,
                            'представителей общего населения',
                            'clients?project=6&examinedAfter=2020-09-01&dateFilter=examined&examinedFrom=us' .
                                '&profileField3=6g6x&profileValue3=bEk7&profileOp3=contains' . $datesQuery,
                            $examinedGeneralNew
                        ],
                        [
                            $examinedOther,
                            'других',
                            'clients?project=6&examinedAfter=2020-09-01&dateFilter=examined&examinedFrom=us' .
                                '&profileField3=6g6x&profileValue3=ab8e&profileOp3=nulloronly' . $datesQuery,
                            $examinedOtherNew
                        ]
                    ],
                    [
                        [
                            $screenedPrisoner,
                            'освободившихся, прошедших скрининг на ТБ (анкета)',
                            'clients?project=6&searchActivities=скрининг&profileField=6g6x&vuln=3' .
                                '&profileValue=gG99&profileValue=MuuE&profileOp=contains' . $datesQuery
                        ],
                        [
                            $screenedPrisonerMen,
                            'мужчин',
                            'clients?project=6&searchActivities=скрининг&profileField=6g6x&vuln=3' .
                                '&profileValue=gG99&profileValue=MuuE&profileOp=contains&gender=male' . $datesQuery
                        ],
                        [
                            $screenedPrisonerWomen,
                            'женщин',
                            'clients?project=6&searchActivities=скрининг&profileField=6g6x&vuln=3' .
                                '&profileValue=gG99&profileValue=MuuE&profileOp=contains&gender=female' . $datesQuery
                        ],
                    ],
                    [
                        [
                            $screenedMigrant,
                            'мигрантов, прошедших скрининг на ТБ (анкета)',
                            'clients?project=6&searchActivities=скрининг&profileField=6g6x&vuln=3' .
                                '&profileValue=uQRP&profileValue=YNoK&profileOp=contains' . $datesQuery
                        ],
                        [
                            $screenedMigrantMen,
                            'мужчин',
                            'clients?project=6&searchActivities=скрининг&profileField=6g6x&vuln=3' .
                                '&profileValue=uQRP&profileValue=YNoK&profileOp=contains&gender=male' . $datesQuery
                        ],
                        [
                            $screenedMigrantWomen,
                            'женщин',
                            'clients?project=6&searchActivities=скрининг&profileField=6g6x&vuln=3' .
                                '&profileValue=uQRP&profileValue=YNoK&profileOp=contains&gender=female' . $datesQuery
                        ],
                    ],
                    [
                        [
                            $screenedDrugUser,
                            'ПН, прошедших скрининг на ТБ (анкета)',
                            'clients?project=6&searchActivities=скрининг&profileField=6g6x&vuln=3' .
                                '&profileValue=2AJg&profileOp=contains' . $datesQuery
                        ],
                        [
                            $screenedDrugUserMen,
                            'мужчин',
                            'clients?project=6&searchActivities=скрининг&profileField=6g6x&vuln=3' .
                                '&profileValue=2AJg&profileOp=contains&gender=male' . $datesQuery
                        ],
                        [
                            $screenedDrugUserWomen,
                            'женщин',
                            'clients?project=6&searchActivities=скрининг&profileField=6g6x&vuln=3' .
                                '&profileValue=2AJg&profileOp=contains&gender=female' . $datesQuery
                        ],
                    ],
                    [
                        [
                            $screenedLimited,
                            'людей с ограниченным доступом к медуслугам, прошедших скрининг на ТБ (анкета)',
                            'clients?project=6&searchActivities=скрининг&profileField=6g6x&vuln=3' .
                                '&profileValue=dLBE&profileOp=contains' . $datesQuery
                        ],
                        [
                            $screenedLimitedMen,
                            'мужчин',
                            'clients?project=6&searchActivities=скрининг&profileField=6g6x&vuln=3' .
                                '&profileValue=dLBE&profileOp=contains&gender=male' . $datesQuery
                        ],
                        [
                            $screenedLimitedWomen,
                            'женщин',
                            'clients?project=6&searchActivities=скрининг&profileField=6g6x&vuln=3' .
                                '&profileValue=dLBE&profileOp=contains&gender=female' . $datesQuery
                        ],
                    ],
                    [
                        [
                            $contacted,
                            'контактных (из любых групп)',
                            'clients?project=6&searchActivities=скрининг&profileField=6g6x' .
                                '&profileValue=ab8e&profileOp=contains' . $datesQuery,
                        ],
                        [
                            $contactedExamined,
                            'контактных (из любых групп), прошедших тестирование на туберкулез',
                            'clients?project=6&examinedAfter=2020-09-01&dateFilter=examined&examinedFrom=us' .
                                '&profileField3=6g6x&profileValue3=ab8e&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $otherContactedExamined,
                            'контактных (не из уязвимых групп), прошедших тестирование на туберкулез',
                            'clients?project=6&examinedAfter=2020-09-01&dateFilter=examined&examinedFrom=us' .
                                '&profileField3=6g6x&profileValue3=ab8e&profileOp3=contains&nonVuln=1' . $datesQuery
                        ],
                    ],
                    [
                        'title' => '2. МДК',
                        'level' => 0
                    ],
                    [
                        [
                            $vulnerableServices,
                            'услуг оказано представителям уязвимых групп с ТБ',
                            'services?projects=6&vuln=1' . $datesQuery
                        ],
                        [
                            $vulnerable,
                            'представителей уязвимых групп с ТБ получили услуги',
                            'clients?project=6&parts=&vuln=1' . $datesQuery,
                            $vulnerableNew
                        ],
                        [
                            $vulnerableMen,
                            'мужчин',
                            'clients?project=6&parts=&gender=male&vuln=1' . $datesQuery,
                            $vulnerableMenNew
                        ],
                        [
                            $vulnerableWomen,
                            'женщин',
                            'clients?project=6&parts=&gender=female&vuln=1' . $datesQuery,
                            $vulnerableWomenNew
                        ],
                        [
                            $fullVulnerable2,
                            'представителей уязвимых групп должны получать услуги',
                            'clients?project=6&vuln=1&fullvuln=2' . $datesOnlyQuery,
                        ],
                        [
                            $fullVulnerable2MDR,
                            'представителей уязвимых групп с МЛУ ТБ должны получать услуги',
                            'clients?project=6&vuln=1&fullvuln=2&mdr=1' . $datesOnlyQuery,
                        ],
                        [
                            $fullVulnerable,
                            'представителей уязвимых групп с ТБ регулярно получают или получали услуги',
                            'clients?project=6&parts=&vuln=1&fullvuln=1' . $datesOnlyQuery,
                        ],
                        [
                            $entered,
                            'представителей уязвимых групп с ТБ принято в проект',
                            'clients?project=6&parts=&vuln=1&profileDate=kB3w' . $datesQuery,
                        ]
                    ],
                    [
                        [
                            $legalVulnerableServices,
                            'юридических услуг оказано представителям уязвимых групп с ТБ',
                            'services?projects=6&parts=366&parts=370&vuln=1' . $datesQuery,
                            $legalVulnerableServicesNew
                        ],
                        [
                            $legalVulnerable,
                            'представителей уязвимых групп с ТБ получили юридическую помощь',
                            'clients?project=6&parts=366&parts=370&vuln=1' . $datesQuery,
                            $legalVulnerableNew
                        ],
                        [
                            $legalVulnerableMen,
                            'мужчин',
                            'clients?project=6&parts=366&parts=370&gender=male&vuln=1' . $datesQuery,
                            $legalVulnerableMenNew
                        ],
                        [
                            $legalVulnerableWomen,
                            'женщин',
                            'clients?project=6&parts=366&parts=370&gender=female&vuln=1' . $datesQuery,
                            $legalVulnerableWomenNew
                        ],
                    ],
                    [
                        [
                            $vulnerableMDRServices,
                            'услуг оказано представителям уязвимых групп с МЛУ ТБ',
                            'services?projects=6&vuln=1&mdr=1' . $datesQuery
                        ],
                        [
                            $vulnerableMDR,
                            'представителей уязвимых групп с МЛУ ТБ получили услуги',
                            'clients?project=6&parts=&vuln=1&mdr=1' . $datesQuery,
                            $vulnerableMDRNew
                        ],
                        [
                            $vulnerableMDRMen,
                            'мужчин c МЛУ ТБ',
                            'clients?project=6&parts=&gender=male&vuln=1&mdr=1' . $datesQuery,
                            $vulnerableMDRMenNew
                        ],
                        [
                            $vulnerableMDRWomen,
                            'женщин c МЛУ ТБ',
                            'clients?project=6&parts=&gender=female&vuln=1&mdr=1' . $datesQuery,
                            $vulnerableMDRWomenNew
                        ],
                    ],
                    [
                        [
                            $legalVulnerableMDRServices,
                            'юридических услуг оказано представителям уязвимых групп с МЛУ ТБ',
                            'services?projects=6&parts=366&parts=370&vuln=1&mdr=1' . $datesQuery,
                            $legalVulnerableMDRServicesNew
                        ],
                        [
                            $legalVulnerableMDR,
                            'представителей уязвимых групп с МЛУ ТБ получили юридическую помощь',
                            'clients?project=6&parts=366&parts=370&vuln=1&mdr=1' . $datesQuery,
                            $legalVulnerableMDRNew
                        ],
                        [
                            $legalVulnerableMDRMen,
                            'мужчин',
                            'clients?project=6&parts=366&parts=370&gender=male&vuln=1&mdr=1' . $datesQuery,
                            $legalVulnerableMDRMenNew
                        ],
                        [
                            $legalVulnerableMDRWomen,
                            'женщин',
                            'clients?project=6&parts=366&parts=370&gender=female&vuln=1&mdr=1' . $datesQuery,
                            $legalVulnerableMDRWomenNew
                        ],
                    ],
                    [
                        [
                            $redundantVulnerable,
                            'представителей уязвимых групп с ТБ относятся более, чем к одной категории',
                            'clients?project=6&parts=&vuln=1&redundant=1' . $datesQuery
                        ],
                        [
                            $redundantMDRVulnerable,
                            'представителей уязвимых групп с МЛУ ТБ относятся более, чем к одной категории',
                            'clients?project=6&parts=&vuln=1&redundant=1&mdr=1' . $datesQuery
                        ],
                    ],
                    [
                        'title' => '2.1. Люди, вышедшие из мест исполнения наказаний',
                        'level' => 1
                    ],
                    [
                        [
                            $prisonerServices,
                            'услуг предоставлено людям, вышедшим из мест исполнения наказаний',
                            'services?projects=6&profileField=6g6x&profileValue=gG99&profileValue=MuuE' .
                                '&profileOp=contains&vuln=1' . $datesQuery
                        ],
                        [
                            $prisoners,
                            'человек, вышедших из мест исполнения наказаний, получили услуги',
                            'clients?project=6&profileField=6g6x&profileValue=gG99&profileValue=MuuE' .
                                '&profileOp=contains&parts=&vuln=1' . $datesQuery,
                            $prisonersNew
                        ],
                        [
                            $prisonerMen,
                            'мужчин',
                            'clients?project=6&profileField=6g6x&profileValue=gG99&profileValue=MuuE' .
                                '&profileOp=contains&gender=male&parts=&vuln=1' . $datesQuery,
                            $prisonerMenNew
                        ],
                        [
                            $prisonerWomen,
                            'женщин',
                            'clients?project=6&profileField=6g6x&profileValue=gG99&profileValue=MuuE' .
                                '&profileOp=contains&gender=female&parts=&vuln=1' . $datesQuery,
                            $prisonerWomenNew
                        ],
                    ],
                    [
                        [
                            $prisonersLegalServices,
                            'юридических услуг оказано людям, вышедшим из мест исполнения наказаний',
                            'services?projects=6&parts=366&parts=370&profileField=6g6x' .
                                '&profileValue=gG99&profileValue=MuuE&profileOp=contains&vuln=1' . $datesQuery,
                            $prisonersLegalServicesNew
                        ],
                        [
                            $prisonersLegal,
                            'человек, вышедших из мест исполнения наказаний, получили юридическую помощь',
                            'clients?project=6&parts=366&parts=370&profileField=6g6x' .
                                '&profileValue=gG99&profileValue=MuuE&profileOp=contains&vuln=1' . $datesQuery,
                            $prisonersLegalNew
                        ],
                        [
                            $prisonersLegalMen,
                            'мужчин',
                            'clients?project=6&parts=366&parts=370&profileField=6g6x&profileValue=gG99' .
                                '&profileValue=MuuE&profileOp=contains&vuln=1&gender=male' . $datesQuery,
                            $prisonersLegalMenNew
                        ],
                        [
                            $prisonersLegalWomen,
                            'женщин',
                            'clients?project=6&parts=366&parts=370&profileField=6g6x&profileValue=gG99' .
                                '&profileValue=MuuE&profileOp=contains&vuln=1&gender=female' . $datesQuery,
                            $prisonersLegalWomenNew
                        ],
                    ],
                    [
                        [
                            $prisonerMDRServices,
                            'услуг предоставлено людям c МЛУ ТБ, вышедшим из мест исполнения наказаний',
                            'services?projects=6&profileField=6g6x' .
                                '&profileValue=gG99&profileValue=MuuE&profileOp=contains&vuln=1&mdr=1' . $datesQuery
                        ],
                        [
                            $prisonersMDR,
                            'человек c МЛУ ТБ, вышедших из мест исполнения наказаний, получили услуги',
                            'clients?project=6&profileField=6g6x&profileValue=gG99&profileValue=MuuE' .
                                '&profileOp=contains&parts=&vuln=1&mdr=1' . $datesQuery,
                            $prisonersMDRNew
                        ],
                        [
                            $prisonerMDRMen,
                            'мужчин c МЛУ ТБ',
                            'clients?project=6&profileField=6g6x&profileValue=gG99&profileValue=MuuE' .
                                '&profileOp=contains&gender=male&parts=&vuln=1&mdr=1' . $datesQuery,
                            $prisonerMDRMenNew
                        ],
                        [
                            $prisonerMDRWomen,
                            'женщин c МЛУ ТБ',
                            'clients?project=6&profileField=6g6x&profileValue=gG99&profileValue=MuuE' .
                                '&profileOp=contains&gender=female&parts=&vuln=1&mdr=1' . $datesQuery,
                            $prisonerMDRWomenNew
                        ],
                    ],
                    [
                        [
                            $prisonersLegalMDRServices,
                            'юридических услуг оказано людям c МЛУ ТБ, вышедшим из мест исполнения наказаний',
                            'services?projects=6&parts=366&parts=370&profileField=6g6x' .
                                '&profileValue=gG99&profileValue=MuuE&profileOp=contains&vuln=1&mdr=1' . $datesQuery,
                            $prisonersLegalMDRServicesNew
                        ],
                        [
                            $prisonersLegalMDR,
                            'человек c МЛУ ТБ, вышедших из мест исполнения наказаний, получили юридическую помощь',
                            'clients?project=6&parts=366&parts=370&profileField=6g6x' .
                                '&profileValue=gG99&profileValue=MuuE&profileOp=contains&vuln=1&mdr=1' . $datesQuery,
                            $prisonersLegalMDRNew
                        ],
                        [
                            $prisonersLegalMDRMen,
                            'мужчин c МЛУ ТБ',
                            'clients?project=6&parts=366&parts=370&profileField=6g6x&profileValue=gG99' .
                                '&profileValue=MuuE&profileOp=contains&vuln=1&gender=male&mdr=1' . $datesQuery,
                            $prisonersLegalMDRMenNew
                        ],
                        [
                            $prisonersLegalMDRWomen,
                            'женщин c МЛУ ТБ',
                            'clients?project=6&parts=366&parts=370&profileField=6g6x&profileValue=gG99' .
                                '&profileValue=MuuE&profileOp=contains&vuln=1&gender=female&mdr=1' . $datesQuery,
                            $prisonersLegalMDRWomenNew
                        ],
                    ],
                    [
                        'title' => '2.2. Мигранты',
                        'level' => 1
                    ],
                    [
                        [
                            $migrantServices,
                            'услуг оказано мигрантам',
                            'services?projects=6&profileField=6g6x&profileValue=uQRP' .
                                '&profileValue=YNoK&profileOp=contains&vuln=1' . $datesQuery
                        ],
                        [
                            $migrants,
                            'мигрантов получили услуги',
                            'clients?project=6&profileField=6g6x&profileValue=uQRP&profileValue=YNoK' .
                                '&profileOp=contains&parts=&vuln=1' . $datesQuery,
                            $migrantsNew
                        ],
                        [
                            $migrantMen,
                            'мужчин',
                            'clients?project=6&profileField=6g6x&profileValue=uQRP&profileValue=YNoK' .
                                '&profileOp=contains&gender=male&parts=&vuln=1' . $datesQuery,
                            $migrantMenNew
                        ],
                        [
                            $migrantWomen,
                            'женщин',
                            'clients?project=6&profileField=6g6x&profileValue=uQRP&profileValue=YNoK' .
                                '&profileOp=contains&gender=female&parts=&vuln=1' . $datesQuery,
                            $migrantWomenNew
                        ],
                    ],
                    [
                        [
                            $migrantLegalServices,
                            'юридических услуг оказано мигрантам',
                            'services?projects=6&parts=366&parts=370&profileField=6g6x' .
                                '&profileValue=uQRP&profileValue=YNoK&profileOp=contains&vuln=1' . $datesQuery,
                            $migrantLegalServicesNew
                        ],
                        [
                            $migrantLegal,
                            'мигрантов получили юридическую помощь',
                            'clients?project=6&parts=366&parts=370&profileField=6g6x' .
                                '&profileValue=uQRP&profileValue=YNoK&profileOp=contains&vuln=1' . $datesQuery,
                            $migrantLegalNew
                        ],
                        [
                            $migrantLegalMen,
                            'мужчин',
                            'clients?project=6&parts=366&parts=370&profileField=6g6x&profileValue=uQRP' .
                                '&profileValue=YNoK&profileOp=contains&gender=male&vuln=1' . $datesQuery,
                            $migrantLegalMenNew
                        ],
                        [
                            $migrantLegalWomen,
                            'женщин',
                            'clients?project=6&parts=366&parts=370&profileField=6g6x&profileValue=uQRP' .
                                '&profileValue=YNoK&profileOp=contains&gender=female&vuln=1' . $datesQuery,
                            $migrantLegalWomenNew
                        ],
                    ],
                    [
                        [
                            $migrantMDRServices,
                            'услуг оказано мигрантам c МЛУ ТБ',
                            'services?projects=6&profileField=6g6x&profileValue=uQRP&profileValue=YNoK' .
                                '&profileOp=contains&vuln=1&mdr=1' . $datesQuery
                        ],
                        [
                            $migrantsMDR,
                            'мигрантов c МЛУ ТБ получили услуги',
                            'clients?project=6&profileField=6g6x&profileValue=uQRP&profileValue=YNoK' .
                                '&profileOp=contains&parts=&vuln=1&mdr=1' . $datesQuery,
                            $migrantsMDRNew
                        ],
                        [
                            $migrantMDRMen,
                            'мужчин c МЛУ ТБ',
                            'clients?project=6&profileField=6g6x&profileValue=uQRP&profileValue=YNoK' .
                                '&profileOp=contains&gender=male&parts=&vuln=1&mdr=1' . $datesQuery,
                            $migrantMDRMenNew
                        ],
                        [
                            $migrantMDRWomen,
                            'женщин c МЛУ ТБ',
                            'clients?project=6&profileField=6g6x&profileValue=uQRP&profileValue=YNoK' .
                                '&profileOp=contains&gender=female&parts=&vuln=1&mdr=1' . $datesQuery,
                            $migrantMDRWomenNew
                        ],
                    ],
                    [
                        [
                            $migrantLegalMDRServices,
                            'юридических услуг оказано мигрантам c МЛУ ТБ',
                            'services?projects=6&parts=366&parts=370&profileField=6g6x' .
                                '&profileValue=uQRP&profileValue=YNoK&profileOp=contains&vuln=1&mdr=1' . $datesQuery,
                            $migrantLegalMDRServicesNew
                        ],
                        [
                            $migrantLegalMDR,
                            'мигрантов c МЛУ ТБ получили юридическую помощь',
                            'clients?project=6&parts=366&parts=370&profileField=6g6x' .
                                '&profileValue=uQRP&profileValue=YNoK&profileOp=contains&vuln=1&mdr=1' . $datesQuery,
                            $migrantLegalMDRNew
                        ],
                        [
                            $migrantLegalMDRMen,
                            'мужчин c МЛУ ТБ',
                            'clients?project=6&parts=366&parts=370&profileField=6g6x&profileValue=uQRP' .
                                '&profileValue=YNoK&profileOp=contains&gender=male&vuln=1&mdr=1' . $datesQuery,
                            $migrantLegalMDRMenNew
                        ],
                        [
                            $migrantLegalMDRWomen,
                            'женщин c МЛУ ТБ',
                            'clients?project=6&parts=366&parts=370&profileField=6g6x&profileValue=uQRP' .
                                '&profileValue=YNoK&profileOp=contains&gender=female&vuln=1&mdr=1' . $datesQuery,
                            $migrantLegalMDRWomenNew
                        ],
                    ],
                    [
                        'title' => '2.3. ПН',
                        'level' => 1
                    ],
                    [
                        [
                            $drugUserServices,
                            'услуг оказано ПН',
                            'services?projects=6&profileField=6g6x&profileValue=2AJg&profileOp=contains' .
                                '&vuln=1' . $datesQuery
                        ],
                        [
                            $drugUsers,
                            'ПН получили услуги',
                            'clients?project=6&profileField=6g6x&profileValue=2AJg&profileOp=contains' .
                                '&parts=&vuln=1' . $datesQuery,
                            $drugUsersNew
                        ],
                        [
                            $drugUserMen,
                            'мужчин',
                            'clients?project=6&profileField=6g6x&profileValue=2AJg&profileOp=contains' .
                                '&gender=male&parts=&vuln=1' . $datesQuery,
                            $drugUserMenNew
                        ],
                        [
                            $drugUserWomen,
                            'женщин',
                            'clients?project=6&profileField=6g6x&profileValue=2AJg&profileOp=contains' .
                                '&gender=female&parts=&vuln=1' . $datesQuery,
                            $drugUserWomenNew
                        ],
                    ],
                    [
                        [
                            $drugUserLegalServices,
                            'юридических услуг оказано ПН',
                            'services?projects=6&parts=366&parts=370&profileField=6g6x&profileValue=2AJg' .
                                '&profileOp=contains&vuln=1' . $datesQuery
                        ],
                        [
                            $drugUsersLegal,
                            'ПН получили получили юридическую помощь',
                            'clients?project=6&parts=366&parts=370&profileField=6g6x&profileValue=2AJg' .
                                '&profileOp=contains&parts=&vuln=1' . $datesQuery,
                            $drugUsersLegalNew
                        ],
                        [
                            $drugUserLegalMen,
                            'мужчин',
                            'clients?project=6&parts=366&parts=370&profileField=6g6x&profileValue=2AJg' .
                                '&profileOp=contains&gender=male&parts=&vuln=1' . $datesQuery,
                            $drugUserLegalMenNew
                        ],
                        [
                            $drugUserLegalWomen,
                            'женщин',
                            'clients?project=6&parts=366&parts=370&profileField=6g6x&profileValue=2AJg' .
                                '&profileOp=contains&gender=female&parts=&vuln=1' . $datesQuery,
                            $drugUserLegalWomenNew
                        ],
                    ],
                    [
                        [
                            $drugUserMDRServices,
                            'услуг оказано ПН c МЛУ ТБ',
                            'services?projects=6&profileField=6g6x&profileValue=2AJg&profileOp=contains' .
                                '&vuln=1&mdr=1' . $datesQuery
                        ],
                        [
                            $drugUsersMDR,
                            'ПН c МЛУ ТБ получили услуги',
                            'clients?project=6&profileField=6g6x&profileValue=2AJg&profileOp=contains' .
                                '&parts=&vuln=1&mdr=1' . $datesQuery,
                            $drugUsersMDRNew
                        ],
                        [
                            $drugUserMDRMen,
                            'мужчин c МЛУ ТБ',
                            'clients?project=6&profileField=6g6x&profileValue=2AJg&profileOp=contains' .
                                '&gender=male&parts=&vuln=1&mdr=1' . $datesQuery,
                            $drugUserMDRMenNew
                        ],
                        [
                            $drugUserMDRWomen,
                            'женщин c МЛУ ТБ',
                            'clients?project=6&profileField=6g6x&profileValue=2AJg&profileOp=contains' .
                                '&gender=female&parts=&vuln=1&mdr=1' . $datesQuery,
                            $drugUserMDRWomenNew
                        ],
                    ],
                    [
                        [
                            $drugUserLegalMDRServices,
                            'юридических услуг оказано ПН c МЛУ ТБ',
                            'services?projects=6&parts=366&parts=370&profileField=6g6x&profileValue=2AJg' .
                                '&profileOp=contains&vuln=1&mdr=1' . $datesQuery
                        ],
                        [
                            $drugUsersLegalMDR,
                            'ПН c МЛУ ТБ получили получили юридическую помощь',
                            'clients?project=6&parts=366&parts=370&profileField=6g6x&profileValue=2AJg' .
                                '&profileOp=contains&parts=&vuln=1&mdr=1' . $datesQuery,
                            $drugUsersLegalMDRNew
                        ],
                        [
                            $drugUserLegalMDRMen,
                            'мужчин c МЛУ ТБ',
                            'clients?project=6&parts=366&parts=370&profileField=6g6x&profileValue=2AJg' .
                                '&profileOp=contains&gender=male&parts=&vuln=1&mdr=1' . $datesQuery,
                            $drugUserLegalMDRMenNew
                        ],
                        [
                            $drugUserLegalMDRWomen,
                            'женщин c МЛУ ТБ',
                            'clients?project=6&parts=366&parts=370&profileField=6g6x&profileValue=2AJg' .
                                '&profileOp=contains&gender=female&parts=&vuln=1&mdr=1' . $datesQuery,
                            $drugUserLegalMDRWomenNew
                        ],
                    ],
                    [
                        'title' => '2.4. Люди с ограниченным доступом к медицинским услугам',
                        'level' => 1
                    ],
                    [
                        [
                            $limitedServices,
                            'услуг оказано людям с ограниченным доступом к медицинским услугам',
                            'services?projects=6&profileField=6g6x&profileValue=dLBE&profileOp=contains' .
                                '&vuln=1' . $datesQuery
                        ],
                        [
                            $limited,
                            'людей с ограниченным доступом к медицинским услугам получили услуги',
                            'clients?project=6&profileField=6g6x&profileValue=dLBE&profileOp=contains&parts=' .
                                '&vuln=1' . $datesQuery,
                            $limitedNew
                        ],
                        [
                            $limitedMen,
                            'мужчин',
                            'clients?project=6&profileField=6g6x&profileValue=dLBE&profileOp=contains&gender=male' .
                                '&parts=&vuln=1' . $datesQuery,
                            $limitedMenNew
                        ],
                        [
                            $limitedWomen,
                            'женщин',
                            'clients?project=6&profileField=6g6x&profileValue=dLBE&profileOp=contains&gender=female' .
                                '&parts=&vuln=1' . $datesQuery,
                            $limitedWomenNew
                        ],
                    ],
                    [
                        [
                            $limitedLegalServices,
                            'юридических услуг оказано людям с ограниченным доступом к медицинским услугам',
                            'services?projects=6&parts=366&parts=370&profileField=6g6x&profileValue=dLBE' .
                                '&profileOp=contains&vuln=1' . $datesQuery
                        ],
                        [
                            $limitedLegal,
                            'людей с ограниченным доступом к медицинским услугам получили юридическую помощь',
                            'clients?project=6&parts=366&parts=370&profileField=6g6x&profileValue=dLBE' .
                                '&profileOp=contains&vuln=1' . $datesQuery,
                            $limitedLegalNew
                        ],
                        [
                            $limitedLegalMen,
                            'мужчин',
                            'clients?project=6&parts=366&parts=370&profileField=6g6x&profileValue=dLBE' .
                                '&profileOp=contains&gender=male&vuln=1' . $datesQuery,
                            $limitedLegalMenNew
                        ],
                        [
                            $limitedLegalWomen,
                            'женщин',
                            'clients?project=6&parts=366&parts=370&profileField=6g6x&profileValue=dLBE' .
                                '&profileOp=contains&gender=female&vuln=1' . $datesQuery,
                            $limitedLegalWomenNew
                        ],
                    ],
                    [
                        [
                            $limitedMDRServices,
                            'услуг оказано людям с ограниченным доступом к медицинским услугам c МЛУ ТБ',
                            'services?projects=6&profileField=6g6x&profileValue=dLBE&profileOp=contains&vuln=1' .
                                '&mdr=1' . $datesQuery
                        ],
                        [
                            $limitedMDR,
                            'людей с ограниченным доступом к медицинским услугам c МЛУ ТБ получили услуги',
                            'clients?project=6&profileField=6g6x&profileValue=dLBE&profileOp=contains&parts=&vuln=1' .
                                '&mdr=1' . $datesQuery,
                            $limitedMDRNew
                        ],
                        [
                            $limitedMDRMen,
                            'мужчин c МЛУ ТБ',
                            'clients?project=6&profileField=6g6x&profileValue=dLBE&profileOp=contains&gender=male' .
                                '&parts=&vuln=1&mdr=1' . $datesQuery,
                            $limitedMDRMenNew
                        ],
                        [
                            $limitedMDRWomen,
                            'женщин c МЛУ ТБ',
                            'clients?project=6&profileField=6g6x&profileValue=dLBE&profileOp=contains&gender=female' .
                                '&parts=&vuln=1&mdr=1' . $datesQuery,
                            $limitedMDRWomenNew
                        ],
                    ],
                    [
                        [
                            $limitedLegalMDRServices,
                            'юридических услуг оказано людям с ограниченным доступом к медицинским услугам c МЛУ ТБ',
                            'services?projects=6&parts=366&parts=370&profileField=6g6x&profileValue=dLBE' .
                                '&profileOp=contains&vuln=1&mdr=1' . $datesQuery
                        ],
                        [
                            $limitedLegalMDR,
                            'людей с ограниченным доступом к медицинским услугам c МЛУ ТБ получили юридическую помощь',
                            'clients?project=6&parts=366&parts=370&profileField=6g6x&profileValue=dLBE' .
                                '&profileOp=contains&vuln=1&mdr=1' . $datesQuery,
                            $limitedLegalMDRNew
                        ],
                        [
                            $limitedLegalMDRMen,
                            'мужчин',
                            'clients?project=6&parts=366&parts=370&profileField=6g6x&profileValue=dLBE' .
                                '&profileOp=contains&gender=male&vuln=1&mdr=1' . $datesQuery,
                            $limitedLegalMDRMenNew
                        ],
                        [
                            $limitedLegalMDRWomen,
                            'женщин',
                            'clients?project=6&parts=366&parts=370&profileField=6g6x&profileValue=dLBE' .
                                '&profileOp=contains&gender=female&vuln=1&mdr=1' . $datesQuery,
                            $limitedLegalMDRWomenNew
                        ],
                    ],
                    [
                        'title' => '3. Психосоциальная поддержка детям с ТБ и их родителям',
                        'level' => 1
                    ],
                    [
                        'title' => '3.1. Дети',
                        'level' => 2
                    ],
                    [
                        [
                            $childrenServices,
                            'услуг оказано детям',
                            'services?projects=6&profileField=6g6x&profileValue=iNHa&profileOp=contains' . $datesQuery
                        ],
                        [
                            $children,
                            'детей получили услуги',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains' .
                                '&parts=' . $datesQuery,
                            $childrenNew
                        ],
                        [
                            $boys,
                            'мальчиков',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains&gender=male' .
                                '&parts=' . $datesQuery,
                            $boysNew
                        ],
                        [
                            $girls,
                            'девочек',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains&gender=female' .
                                '&parts=' . $datesQuery,
                            $girlsNew
                        ],
                        [
                            $childrenTBInfected,
                            'детей с туб-инфицированием',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains&tbi=1' . $datesQuery
                        ]
                    ], [
                        [
                            $childrenServicesDS,
                            'услуг оказано детям с ЛЧ ТБ',
                            'services?projects=6&profileField=6g6x&profileValue=iNHa&profileOp=contains&mdr=-1' .
                                $datesQuery
                        ],
                        [
                            $childrenDS,
                            'детей с ЛЧ ТБ получили услуги',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains' .
                                '&mdr=-1&parts=' . $datesQuery,
                            $childrenDSNew
                        ],
                        [
                            $boysDS,
                            'мальчиков',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains&gender=male' .
                                '&mdr=-1&parts=' . $datesQuery,
                            $boysDSNew
                        ],
                        [
                            $girlsDS,
                            'девочек',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains&gender=female' .
                                '&mdr=-1&parts=' . $datesQuery,
                            $girlsDSNew
                        ]
                    ], [
                        [
                            $childrenServicesMDR,
                            'услуг оказано детям c ЛУ ТБ',
                            'services?projects=6&profileField=6g6x&profileValue=iNHa&profileOp=contains&mdr=1' .
                                $datesQuery
                        ],
                        [
                            $childrenMDR,
                            'детей c ЛУ ТБ получили услуги',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains' .
                                '&mdr=1&parts=' . $datesQuery,
                            $childrenMDRNew
                        ],
                        [
                            $boysMDR,
                            'мальчиков',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains&gender=male' .
                                '&mdr=1&parts=' . $datesQuery,
                            $boysMDRNew
                        ],
                        [
                            $girlsMDR,
                            'девочек',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains&gender=female' .
                                '&mdr=1&parts=' . $datesQuery,
                            $girlsMDRNew
                        ]
                    ], [
                        [
                            $fullChildren,
                            'детей получили полный цикл психосоциальной поддержки на протяжении всего пребывания ' .
                                'в стационаре от поступления до выписки',
                            'clients?project=6&fullch=1&profileDate=GWQS' . $datesQuery,
                            $fullChildrenNew,
                            route('downloadCases')
                        ],
                        [
                            $fullChildrenBoys,
                            'мальчиков',
                            'clients?project=6&fullch=1&profileDate=GWQS&gender=male' . $datesQuery,
                            $fullChildrenBoysNew
                        ],
                        [
                            $fullChildrenGirls,
                            'девочек',
                            'clients?project=6&fullch=1&profileDate=GWQS&gender=female' . $datesQuery,
                            $fullChildrenGirlsNew
                        ],
                        [
                            $fullChildrenServices,
                            'услуг оказано детям на протяжении всего пребывания в стационаре от поступления до выписки',
                            'services?projects=6&fullch=1&profileDate=GWQS' . $datesQuery,
                            $fullChildrenServicesNew
                        ],
                    ], [
                        [
                            $fullChildren4,
                            'детей получили минимальный пакет в стационаре',
                            'clients?project=6&fullch=4&profileDate=kB3w' . $fullChildrenDatesQuery,
                        ],
                        [
                            $fullChildren4Boys,
                            'мальчиков',
                            'clients?project=6&fullch=4&profileDate=kB3w&gender=male' . $fullChildrenDatesQuery,
                        ],
                        [
                            $fullChildren4Girls,
                            'девочек',
                            'clients?project=6&fullch=4&profileDate=kB3w&gender=female' . $fullChildrenDatesQuery,
                        ],
                    ], [
                        [
                            $fullChildren14,
                            'детей получали минимальный пакет стационаре',
                            'clients?project=6&fullch=14' . $fullChildrenDatesQuery,
                        ],
                        [
                            $fullChildren14Boys,
                            'мальчиков',
                            'clients?project=6&fullch=14&gender=male' . $fullChildrenDatesQuery,
                        ],
                        [
                            $fullChildren14Girls,
                            'девочек',
                            'clients?project=6&fullch=14&gender=female' . $fullChildrenDatesQuery,
                        ],
                        [
                            $fullChildren14Services,
                            'услуг вошло в минимальные пакеты стационаре',
                        ],
                    ], [
                        [
                            $fullChildren15,
                            'детей получали минимальный пакет амбулаторно',
                            'clients?project=6&fullch=15' . $fullChildrenDatesQuery,
                        ],
                        [
                            $fullChildren13,
                            'детей получали минимальный пакет в амбулаторно',
                            'clients?project=6&fullch=13' . $fullChildrenDatesQuery,
                        ],
                        [
                            $fullChildren13Boys,
                            'мальчиков',
                            'clients?project=6&fullch=13&gender=male' . $fullChildrenDatesQuery,
                        ],
                        [
                            $fullChildren13Girls,
                            'девочек',
                            'clients?project=6&fullch=13&gender=female' . $fullChildrenDatesQuery,
                        ],
                        [
                            $fullChildren13Services,
                            'услуг вошло в минимальные пакеты в амбулаторно',
                        ],
                    ], [
                        [
                            $fullChildren2,
                            'детей с ТБ, получивших минимальный пакет психосоциальной поддержки в процессе лечения',
                            'clients?project=6&fullch=2' . $fullChildrenDatesQuery,
                            null,
                            route('downloadCases') . '?minimal' . $fullChildrenDatesQuery
                        ],
                        [
                            $fullChildren2Boys,
                            'мальчиков',
                            'clients?project=6&fullch=2&gender=male' . $fullChildrenDatesQuery,
                        ],
                        [
                            $fullChildren2Girls,
                            'девочек',
                            'clients?project=6&fullch=2&gender=female' . $fullChildrenDatesQuery,
                        ],
                    ], [
                        [
                            $fullChildren3,
                            'детей с ТБ, получивших минимальный пакет психосоциальлной поддержки' .
                                ' на протяжении всего лечения',
                            'clients?project=6&fullch=3' . $fullChildrenDatesQuery,
                        ],
                        [
                            $fullChildren3Boys,
                            'мальчиков',
                            'clients?project=6&fullch=3&gender=male' . $fullChildrenDatesQuery,
                        ],
                        [
                            $fullChildren3Girls,
                            'девочек',
                            'clients?project=6&fullch=3&gender=female' . $fullChildrenDatesQuery,
                        ],
                    ], [
                        [
                            $fullChildren9,
                            'детей получают услуги в стационаре',
                            'clients?project=6&fullch=9' . $fullChildrenDatesQuery
                        ],
                        [
                            $fullChildren5,
                            'детей недополучили услугу соцработника в стационаре в текущем месяце',
                            'clients?project=6&fullch=5' . $fullChildrenDatesQuery
                        ],
                        [
                            $fullChildren5_1,
                            'в ДГФБ',
                            'clients?project=6&fullch=51' . $fullChildrenDatesQuery
                        ],
                        [
                            $fullChildren5_2,
                            'в ГКБФиП',
                            'clients?project=6&fullch=52' . $fullChildrenDatesQuery
                        ],
                        [
                            $fullChildren5_3,
                            'в НИИ Вирусологии',
                            'clients?project=6&fullch=53' . $fullChildrenDatesQuery
                        ],
                        [
                            $fullChildren5_4,
                            'РСНЦФиП',
                            'clients?project=6&fullch=54' . $fullChildrenDatesQuery
                        ],
                        [
                            $fullChildren6,
                            'детей недополучили услугу психолога в стационаре в текущем месяце',
                            'clients?project=6&fullch=6' . $fullChildrenDatesQuery
                        ],
                        [
                            $fullChildren6_1,
                            'в ДГФБ',
                            'clients?project=6&fullch=61' . $fullChildrenDatesQuery
                        ],
                        [
                            $fullChildren6_2,
                            'в ГКБФиП',
                            'clients?project=6&fullch=62' . $fullChildrenDatesQuery
                        ],
                        [
                            $fullChildren6_3,
                            'в НИИ Вирусологии',
                            'clients?project=6&fullch=63' . $fullChildrenDatesQuery
                        ],
                        [
                            $fullChildren6_4,
                            'РСНЦФиП',
                            'clients?project=6&fullch=64' . $fullChildrenDatesQuery
                        ]
                    ], [
                        [
                            $fullChildren12Services .
                                ' (' . $fullChildren12Services1 . ' + ' . $fullChildren12Services2 . ')',
                            'услуг было оказано детям в стационаре',
                        ],
                        [
                            $fullChildren12,
                            'детей получали услуги в стационаре',
                            'clients?project=6&fullch=12' . $fullChildrenDatesQuery
                        ],
                        [
                            $fullChildren12Boys,
                            'мальчиков',
                            'clients?project=6&fullch=12&gender=male' . $fullChildrenDatesQuery
                        ],
                        [
                            $fullChildren12Girls,
                            'девочек',
                            'clients?project=6&fullch=12&gender=female' . $fullChildrenDatesQuery
                        ],
                    ], [
                        [
                            $fullChildren16Services .
                                ' (' . $fullChildren16Services1 . ' + ' . $fullChildren16Services2 . ')',
                            'услуг соцработника было оказано детям в стационаре',
                        ],
                        [
                            $fullChildren16,
                            'детей получали услуги соцработника в стационаре',
                            'clients?project=6&fullch=16' . $fullChildrenDatesQuery
                        ],
                        [
                            $fullChildren17Services .
                                ' (' . $fullChildren17Services1 . ' + ' . $fullChildren17Services2 . ')',
                            'услуг психолога было оказано детям в стационаре',
                        ],
                        [
                            $fullChildren17,
                            'детей получали услуги психолога в стационаре',
                            'clients?project=6&fullch=17' . $fullChildrenDatesQuery
                        ],
                        [
                            $fullChildren22Services,
                            'услуг юриста было оказано детям в стационаре',
                        ],
                        [
                            $fullChildren22,
                            'детей получали услуги юриста в стационаре',
                            'clients?project=6&fullch=22' . $fullChildrenDatesQuery
                        ],
                        [
                            $fullChildren23Services,
                            'услуг фтизиатра было оказано детям в стационаре',
                        ],
                        [
                            $fullChildren23,
                            'детей получали услуги фтизиатра в стационаре',
                            'clients?project=6&fullch=23' . $fullChildrenDatesQuery
                        ],
                    ], [
                        [
                            $fullChildren10,
                            'детей получают услуги амбулаторно',
                            'clients?project=6&fullch=10' . $fullChildrenDatesQuery
                        ],
                        [
                            $fullChildren7,
                            'детей недополучили услугу соцработника амбулаторно в текущем месяце',
                            'clients?project=6&fullch=7' . $fullChildrenDatesQuery
                        ],
                        [
                            $fullChildren8,
                            'детей недополучили услугу психолога амбулаторно в текущем месяце',
                            'clients?project=6&fullch=8' . $fullChildrenDatesQuery
                        ],
                    ], [
                        [
                            $fullChildren11Services .
                                ' (' . $fullChildren11Services1 . ' + ' . $fullChildren11Services2 . ')',
                            'услуг было оказано детям амбулаторно',
                        ],
                        [
                            $fullChildren11,
                            'детей получали услуги амбулаторно',
                            'clients?project=6&fullch=11' . $fullChildrenDatesQuery
                        ],
                        [
                            $fullChildren11Boys,
                            'мальчиков',
                            'clients?project=6&fullch=11&gender=male' . $fullChildrenDatesQuery
                        ],
                        [
                            $fullChildren11Girls,
                            'девочек',
                            'clients?project=6&fullch=11&gender=female' . $fullChildrenDatesQuery
                        ],
                    ], [
                        [
                            $fullChildren18Services .
                                ' (' . $fullChildren18Services1 . ' + ' . $fullChildren18Services2 . ')',
                            'услуг соцработника было оказано детям амбулаторно',
                        ],
                        [
                            $fullChildren18,
                            'детей получали услуги соцработника амбулаторно',
                            'clients?project=6&fullch=18' . $fullChildrenDatesQuery
                        ],
                        [
                            $fullChildren19Services .
                                ' (' . $fullChildren19Services1 . ' + ' . $fullChildren19Services2 . ')',
                            'услуг психолога было оказано детям амбулаторно',
                        ],
                        [
                            $fullChildren19,
                            'детей получали услуги психолога амбулаторно',
                            'clients?project=6&fullch=19' . $fullChildrenDatesQuery
                        ],
                        [
                            $fullChildren20Services,
                            'услуг юриста было оказано детям амбулаторно',
                        ],
                        [
                            $fullChildren20,
                            'детей получали услуги юриста амбулаторно',
                            'clients?project=6&fullch=20' . $fullChildrenDatesQuery
                        ],
                        [
                            $fullChildren21Services,
                            'услуг фтизиатра было оказано детям амбулаторно',
                        ],
                        [
                            $fullChildren21,
                            'детей получали услуги фтизиатра амбулаторно',
                            'clients?project=6&fullch=21' . $fullChildrenDatesQuery
                        ],
                    ], [
                        [
                            $showServices,
                            'анимированных шоу проведено',
                            'services?projects=6&profileField=6g6x&profileValue=iNHa&profileOp=contains' .
                                '&searchActivities=шоу' . $datesQuery,
                            $showServicesNew
                        ],
                        [
                            $show,
                            'детей посетили анимированные шоу',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains' .
                                '&searchActivities=шоу' . $datesQuery,
                            $showNew
                        ],
                        [
                            $showBoys,
                            'мальчиков',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains&gender=male' .
                                '&searchActivities=шоу' . $datesQuery,
                            $showBoysNew
                        ],
                        [
                            $showGirls,
                            'девочек',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains&gender=female' .
                                '&searchActivities=шоу' . $datesQuery,
                            $showGirlsNew
                        ],
                        [0, 'больниц принимали анимированные шоу'],
                    ], [
                        [
                            $artServices,
                            'услуг по арт-терапии оказано детям',
                            'services?projects=6&profileField=6g6x&profileValue=iNHa&profileOp=contains&search=арт' .
                                '&parts=367&parts=371&parts=375&parts=376' . $datesQuery
                        ],
                        [
                            $art,
                            'детей получили услуги по арт-терапии',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains' .
                                '&searchActivities=арт&parts=367&parts=371&parts=375&parts=376' . $datesQuery,
                            $artNew
                        ],
                        [
                            $artBoys,
                            'мальчиков',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains&gender=male' .
                                '&searchActivities=арт&parts=367&parts=371&parts=375&parts=376' . $datesQuery,
                            $artBoysNew
                        ],
                        [
                            $artGirls,
                            'девочек',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains&gender=female' .
                                '&searchActivities=арт&parts=367&parts=371&parts=375&parts=376' . $datesQuery,
                            $artGirlsNew
                        ],
                    ],
                    [
                        [
                            $childrenLegalServices,
                            'юридических услуг оказано детям',
                            'services?projects=6&parts=366&parts=370&parts=374&profileField=6g6x&profileValue=iNHa' .
                                '&profileOp=contains' . $datesQuery,
                            $childrenLegalServicesNew
                        ],
                        [
                            $childrenLegal,
                            'детей получили юридическую помощь',
                            'clients?project=6&parts=366&parts=370&parts=374&profileField=6g6x&profileValue=iNHa' .
                                '&profileOp=contains' . $datesQuery,
                            $childrenLegalNew
                        ],
                        [
                            $boysLegal,
                            'мальчиков',
                            'clients?project=6&parts=366&parts=370&parts=374&profileField=6g6x&profileValue=iNHa' .
                                '&profileOp=contains&gender=male' . $datesQuery,
                            $boysLegalNew
                        ],
                        [
                            $girlsLegal,
                            'девочек',
                            'clients?project=6&parts=366&parts=370&parts=374&profileField=6g6x&profileValue=iNHa' .
                                '&profileOp=contains&gender=female' . $datesQuery,
                            $girlsLegalNew
                        ],
                    ],
                    [
                        [
                            $childrenPsyServices,
                            'услуг психолога оказано детям',
                            'services?projects=6&parts=367&parts=371&parts=375&parts=376&profileField=6g6x&profileValue=iNHa' .
                                '&profileOp=contains' . $datesQuery,
                            $childrenPsyServicesNew
                        ],
                        [
                            $childrenPsy,
                            'детей получили услуги психолога',
                            'clients?project=6&parts=367&parts=371&parts=375&parts=376&profileField=6g6x&profileValue=iNHa' .
                                '&profileOp=contains' . $datesQuery,
                            $childrenPsyNew
                        ],
                        [
                            $boysPsy,
                            'мальчиков',
                            'clients?project=6&parts=367&parts=371&parts=375&parts=376&profileField=6g6x&profileValue=iNHa' .
                                '&profileOp=contains&gender=male' . $datesQuery,
                            $boysPsyNew
                        ],
                        [
                            $girlsPsy,
                            'девочек',
                            'clients?project=6&parts=367&parts=371&parts=375&parts=376&profileField=6g6x&profileValue=iNHa' .
                                '&profileOp=contains&gender=female' . $datesQuery,
                            $girlsPsyNew
                        ],
                    ],
                    [
                        [
                            $childrenSocServices,
                            'услуг соцработника оказано детям',
                            'services?projects=6&parts=365&parts=369&parts=373&parts=474' .
                            '&parts=364&parts=368&parts=372&positionex=тб' .
                            '&parts=363&parts=362&parts=361&parts=543&parts=377&parts=379&parts=528&parts=527' .
                            '&profileField=6g6x&profileValue=iNHa' .
                                '&profileOp=contains' . $datesQuery,
                            $childrenSocServicesNew
                        ],
                        [
                            $childrenSoc,
                            'детей получили услуги соцработника',
                            'clients?project=6&parts=365&parts=369&parts=373&parts=474' .
                            '&parts=364&parts=368&parts=372&positionex=тб' .
                            '&parts=363&parts=362&parts=361&parts=543&parts=377&parts=379&parts=528&parts=527' .
                            '&profileField=6g6x&profileValue=iNHa' .
                                '&profileOp=contains' . $datesQuery,
                            $childrenSocNew
                        ],
                        [
                            $boysSoc,
                            'мальчиков',
                            'clients?project=6&parts=365&parts=369&parts=373&parts=474' .
                            '&parts=364&parts=368&parts=372&positionex=тб' .
                            '&parts=363&parts=362&parts=361&parts=543&parts=377&parts=379&parts=528&parts=527' .
                            '&profileField=6g6x&profileValue=iNHa' .
                                '&profileOp=contains&gender=male' . $datesQuery,
                            $boysSocNew
                        ],
                        [
                            $girlsSoc,
                            'девочек',
                            'clients?project=6&parts=365&parts=369&parts=373&parts=474' .
                            '&parts=364&parts=368&parts=372&positionex=тб' .
                            '&parts=363&parts=362&parts=361&parts=543&parts=377&parts=379&parts=528&parts=527' .
                            '&profileField=6g6x&profileValue=iNHa' .
                                '&profileOp=contains&gender=female' . $datesQuery,
                            $girlsSocNew
                        ],
                    ],
                    [
                        [
                            $childrenPhthiServices,
                            'услуг фтизиатра оказано детям',
                            'services?projects=6&parts=364&parts=368&parts=372&profileField=6g6x&profileValue=iNHa' .
                            '&position=тб' .
                                '&profileOp=contains' . $datesQuery,
                            $childrenPhthiServicesNew
                        ],
                        [
                            $childrenPhthi,
                            'детей получили услуги фтизиатра',
                            'clients?project=6&parts=364&parts=368&parts=372&profileField=6g6x&profileValue=iNHa' .
                            '&position=тб' .
                                '&profileOp=contains' . $datesQuery,
                            $childrenPhthiNew
                        ],
                        [
                            $boysPhthi,
                            'мальчиков',
                            'clients?project=6&parts=364&parts=368&parts=372&profileField=6g6x&profileValue=iNHa' .
                            '&position=тб' .
                                '&profileOp=contains&gender=male' . $datesQuery,
                            $boysPhthiNew
                        ],
                        [
                            $girlsPhthi,
                            'девочек',
                            'clients?project=6&parts=364&parts=368&parts=372&profileField=6g6x&profileValue=iNHa' .
                            '&position=тб' .
                                '&profileOp=contains&gender=female' . $datesQuery,
                            $girlsPhthiNew
                        ],
                    ],
                    [
                        'title' => '3.2. Родители',
                        'level' => 2
                    ],
                    [
                        [
                            $parentServices,
                            'услуг оказано родителям',
                            'services?projects=6&profileField=6g6x&profileValue=aSu3' .
                                '&profileOp=contains' . $datesQuery
                        ],
                        [
                            $parents,
                            'родителей получили услуги',
                            'clients?project=6&profileField=6g6x&profileValue=aSu3&profileOp=contains' .
                                '&parts=' . $datesQuery,
                            $parentsNew
                        ],
                        [
                            $parentMen,
                            'мужчин',
                            'clients?project=6&profileField=6g6x&profileValue=aSu3&profileOp=contains&gender=male' .
                                '&parts=' . $datesQuery,
                            $parentMenNew
                        ],
                        [
                            $parentWomen,
                            'женщин',
                            'clients?project=6&profileField=6g6x&profileValue=aSu3&profileOp=contains&gender=female' .
                                '&parts=' . $datesQuery,
                            $parentWomenNew
                        ],
                    ],
                    [
                        [
                            $parentLegalServices,
                            'юридических услуг оказано родителям',
                            'services?projects=6&parts=366&parts=370&parts=374&profileField=6g6x&profileValue=aSu3' .
                                '&profileOp=contains' . $datesQuery
                        ],
                        [
                            $parentsLegal,
                            'родителей получили юридическую помощь',
                            'clients?project=6&parts=366&parts=370&parts=374&profileField=6g6x&profileValue=aSu3' .
                                '&profileOp=contains' . $datesQuery,
                            $parentsLegalNew
                        ],
                        [
                            $parentLegalMen,
                            'мужчин',
                            'clients?project=6&parts=366&parts=370&parts=374&profileField=6g6x&profileValue=aSu3' .
                                '&profileOp=contains&gender=male' . $datesQuery,
                            $parentLegalMenNew
                        ],
                        [
                            $parentLegalWomen,
                            'женщин',
                            'clients?project=6&parts=366&parts=370&parts=374&profileField=6g6x&profileValue=aSu3' .
                                '&profileOp=contains&gender=female' . $datesQuery,
                            $parentLegalWomenNew
                        ],
                    ],
                    [
                        [
                            $parentPsyServices,
                            'услуг психолога оказано родителям',
                            'services?projects=6&parts=367&parts=371&parts=375&parts=376&profileField=6g6x&profileValue=aSu3' .
                                '&profileOp=contains' . $datesQuery
                        ],
                        [
                            $parentsPsy,
                            'родителей получили услуги психолога',
                            'clients?project=6&parts=367&parts=371&parts=375&parts=376&profileField=6g6x&profileValue=aSu3' .
                                '&profileOp=contains' . $datesQuery,
                            $parentsPsyNew
                        ],
                        [
                            $parentPsyMen,
                            'мужчин',
                            'clients?project=6&parts=367&parts=371&parts=375&parts=376&profileField=6g6x&profileValue=aSu3' .
                                '&profileOp=contains&gender=male' . $datesQuery,
                            $parentPsyMenNew
                        ],
                        [
                            $parentPsyWomen,
                            'женщин',
                            'clients?project=6&parts=367&parts=371&parts=375&parts=376&profileField=6g6x&profileValue=aSu3' .
                                '&profileOp=contains&gender=female' . $datesQuery,
                            $parentPsyWomenNew
                        ],
                    ],
                    [
                        [
                            $parentSocServices,
                            'услуг соцработника оказано родителям',
                            'services?projects=6&parts=365&parts=369&parts=373&parts=474' .
                            '&parts=363&parts=362&parts=361&parts=543&parts=377&parts=379&parts=528&parts=527'.
                            '&profileField=6g6x&profileValue=aSu3' .
                                '&profileOp=contains' . $datesQuery
                        ],
                        [
                            $parentsSoc,
                            'родителей получили услуги соцработника',
                            'clients?project=6&parts=365&parts=369&parts=373&parts=474' .
                            '&parts=364&parts=368&parts=372&positionex=тб' .
                            '&parts=363&parts=362&parts=361&parts=543&parts=377&parts=379&parts=528&parts=527'.
                            '&profileField=6g6x&profileValue=aSu3' .
                                '&profileOp=contains' . $datesQuery,
                            $parentsSocNew
                        ],
                        [
                            $parentSocMen,
                            'мужчин',
                            'clients?project=6&parts=365&parts=369&parts=373&parts=474' .
                            '&parts=364&parts=368&parts=372&positionex=тб' .
                            '&parts=363&parts=362&parts=361&parts=543&parts=377&parts=379&parts=528&parts=527'.
                            '&profileField=6g6x&profileValue=aSu3' .
                                '&profileOp=contains&gender=male' . $datesQuery,
                            $parentSocMenNew
                        ],
                        [
                            $parentSocWomen,
                            'женщин',
                            'clients?project=6&parts=365&parts=369&parts=373&parts=474' .
                            '&parts=364&parts=368&parts=372&positionex=тб' .
                            '&parts=363&parts=362&parts=361&parts=543&parts=377&parts=379&parts=528&parts=527'.
                            '&profileField=6g6x&profileValue=aSu3' .
                                '&profileOp=contains&gender=female' . $datesQuery,
                            $parentSocWomenNew
                        ],
                    ],
                    [
                        [
                            $parentPhthiServices,
                            'услуг фтизиатра оказано родителям',
                            'services?projects=6&parts=364&parts=368&parts=372&profileField=6g6x&profileValue=aSu3' .
                            '&position=тб' .
                                '&profileOp=contains' . $datesQuery
                        ],
                        [
                            $parentsPhthi,
                            'родителей получили услуги фтизиатра',
                            'clients?project=6&parts=364&parts=368&parts=372&profileField=6g6x&profileValue=aSu3' .
                            '&position=тб' .
                                '&profileOp=contains' . $datesQuery,
                            $parentsPhthiNew
                        ],
                        [
                            $parentPhthiMen,
                            'мужчин',
                            'clients?project=6&parts=364&parts=368&parts=372&profileField=6g6x&profileValue=aSu3' .
                            '&position=тб' .
                                '&profileOp=contains&gender=male' . $datesQuery,
                            $parentPhthiMenNew
                        ],
                        [
                            $parentPhthiWomen,
                            'женщин',
                            '&position=тб' .
                            'clients?project=6&parts=364&parts=368&parts=372&profileField=6g6x&profileValue=aSu3' .
                                '&profileOp=contains&gender=female' . $datesQuery,
                            $parentPhthiWomenNew
                        ],
                    ],
                    [
                        'title' => '4. Выявленные случаи ТБ',
                        'level' => 0
                    ],
                    [
                        [
                            $detected,
                            'случаев туберкулеза выявлено',
                            'clients?project=6&examinedAfter=2020-09-01&examined=nonNegative&dateFilter=examined' .
                                '&examinedFrom=us&userParts=361' .
                                '&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                        ],
                        [
                            $detectedMale,
                            'среди мужчин',
                            'clients?project=6&examinedAfter=2020-09-01&examined=nonNegative&dateFilter=examined' .
                                '&examinedFrom=us&userParts=361&gender=male' .
                                '&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                        ],
                        [
                            $detectedFemale,
                            'среди женщин',
                            'clients?project=6&examinedAfter=2020-09-01&examined=nonNegative&dateFilter=examined' .
                                '&examinedFrom=us&userParts=361&gender=female' .
                                '&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                        ],
                        [
                            $redundantDetected,
                            'клиентов, относящихся к более, чем одной категории',
                            'clients?project=6&examinedAfter=2020-09-01&examined=nonNegative&dateFilter=examined' .
                                '&examinedFrom=us&userParts=361&redundant=2' .
                                '&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                        ],
                        [
                            $redundantMDRDetected,
                            'клиентов с МЛУ ТБ, относящихся к более, чем одной категории',
                            'clients?project=6&examinedAfter=2020-09-01&examined=nonNegative&dateFilter=examined' .
                                '&examinedFrom=us&userParts=361&redundant=1&mdr=1' .
                                '&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                        ],
                    ],
                    [
                        [
                            $detectedMigrants,
                            'среди мигрантов',
                            'clients?project=6&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK' .
                                '&profileOp3=contains&examinedAfter=2020-09-01' .
                                '&examined=nonNegative&dateFilter=examined&examinedFrom=us&userParts=361' .
                                '&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                            $detectedMigrantsNew
                        ],
                        [
                            $detectedDrugUsers,
                            'среди ПН',
                            'clients?project=6&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' .
                                '&examinedAfter=2020-09-01&examined=nonNegative&dateFilter=examined&examinedFrom=us' .
                                '&userParts=361&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                            $detectedDrugUsersNew
                        ],
                        [
                            $detectedPrisoners,
                            'среди людей, вышедших из мест исполнения наказаний',
                            'clients?project=6&profileField3=6g6x' .
                                '&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                                '&examinedAfter=2020-09-01&examined=nonNegative&dateFilter=examined&examinedFrom=us' .
                                '&userParts=361&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                            $detectedPrisonersNew
                        ],
                        [
                            $detectedDifficult,
                            'среди людей с ограниченным доступом к медицинским услугам',
                            'clients?project=6&profileField3=6g6x&profileValue3=dLBE&profileOp3=contains' .
                                '&examinedAfter=2020-09-01&examined=nonNegative&dateFilter=examined&examinedFrom=us' .
                                '&userParts=361&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                            $detectedDifficultNew
                        ],
                    ],
                    [
                        [
                            $detectedChildren,
                            'среди детей',
                            'clients?project=6&profileField3=6g6x&profileValue3=iNHa&profileOp3=contains' .
                                '&examinedAfter=2020-09-01&examined=nonNegative&dateFilter=examined&examinedFrom=us' .
                                '&userParts=361&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                            $detectedChildrenNew
                        ],
                        [
                            $detectedParents,
                            'среди родителей',
                            'clients?project=6&profileField3=6g6x&profileValue3=aSu3&profileOp3=contains' .
                                '&examinedAfter=2020-09-01&examined=nonNegative&dateFilter=examined&examinedFrom=us' .
                                '&userParts=361&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                            $detectedParentsNew
                        ],
                    ],
                    [
                        [
                            $detectedMDR,
                            'случаев МЛУ туберкулеза выявлено',
                            'clients?project=6&examinedAfter=2020-09-01&examined=nonNegative' .
                                '&dateFilter=examined&examinedFrom=us&userParts=361&mdr=1' .
                                '&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                            $detectedMDRNew
                        ],
                        [
                            $detectedMDRMigrants,
                            'среди мигрантов (МЛУ ТБ)',
                            'clients?project=6' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                                '&examinedAfter=2020-09-01&examined=nonNegative&dateFilter=examined&examinedFrom=us' .
                                '&userParts=361&mdr=1&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                            $detectedMDRMigrantsNew
                        ],
                        [
                            $detectedMDRDrugUsers,
                            'среди ПН (МЛУ ТБ)',
                            'clients?project=6&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' .
                                '&examinedAfter=2020-09-01&examined=nonNegative&dateFilter=examined&examinedFrom=us' .
                                '&userParts=361&mdr=1&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                            $detectedMDRDrugUsersNew
                        ],
                        [
                            $detectedMDRPrisoners,
                            'среди людей, вышедших из мест исполнения наказаний (МЛУ ТБ)',
                            'clients?project=6' .
                                '&profileField3=6g6x&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                                '&examinedAfter=2020-09-01&examined=nonNegative&dateFilter=examined&examinedFrom=us' .
                                '&userParts=361&mdr=1&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                            $detectedMDRPrisonersNew
                        ],
                        [
                            $detectedMDRDifficult,
                            'среди людей с ограниченным доступом к медицинским услугам (МЛУ ТБ)',
                            'clients?project=6' .
                                '&profileField3=6g6x&profileValue3=dLBE&profileValue3=MuuE&profileOp3=contains' .
                                '&examinedAfter=2020-09-01&examined=nonNegative&dateFilter=examined&examinedFrom=us' .
                                '&userParts=361&mdr=1&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                            $detectedMDRDifficultNew
                        ],
                    ],
                    [
                        [
                            $detectedMDRChildren,
                            'среди детей (МЛУ ТБ)',
                            'clients?project=6' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=contains' .
                                '&examinedAfter=2020-09-01&examined=nonNegative&dateFilter=examined&examinedFrom=us' .
                                '&userParts=361&mdr=1&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                            $detectedMDRChildrenNew
                        ],
                        [
                            $detectedMDRParents,
                            'среди родителей (МЛУ ТБ)',
                            'clients?project=6' .
                                '&profileField3=6g6x&profileValue3=aSu3&profileOp3=contains' .
                                '&examinedAfter=2020-09-01&examined=nonNegative&dateFilter=examined&examinedFrom=us' .
                                '&userParts=361&mdr=1&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                            $detectedMDRParentsNew
                        ],
                    ],
                    [
                        'title' => '5. Больные, начавшие лечение',
                        'level' => 0
                    ],
                    [
                        [
                            $started,
                            'больных туберкулезом, начавших лечение',
                            'clients?project=6&profileField=Q5xs&profileOp=notNull&profileDate=Q5xs' .
                                '&started=1' . $datesQuery,
                            $startedNew
                        ],
                        [
                            $startedMigrants,
                            'мигрантов',
                            'clients?project=6&profileField=Q5xs&profileOp=notNull' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                                '&profileDate=Q5xs&started=1' . $datesQuery,
                            $startedMigrantsNew
                        ],
                        [
                            $startedDrugUsers,
                            'ПН',
                            'clients?project=6&profileField=Q5xs&profileOp=notNull' .
                                '&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains&profileDate=Q5xs' .
                                '&started=1' . $datesQuery,
                            $startedDrugUsersNew
                        ],
                        [
                            $startedPrisoners,
                            'человек, вышедших из мест исполнения наказаний',
                            'clients?project=6&profileField=Q5xs&profileOp=notNull' .
                                '&profileField3=6g6x&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                                '&profileDate=Q5xs&started=1' . $datesQuery,
                            $startedPrisonersNew
                        ],
                    ], [
                        [
                            $startedChildren,
                            'детей',
                            'clients?project=6&profileField=Q5xs&profileOp=notNull' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=contains&profileDate=Q5xs' .
                                '&started=1' . $datesQuery,
                            $startedChildrenNew
                        ],
                        [
                            $startedParents,
                            'родителей',
                            'clients?project=6&profileField=Q5xs&profileOp=notNull' .
                                '&profileField3=6g6x&profileValue3=aSu3&profileOp3=contains' .
                                '&profileDate=Q5xs&started=1' . $datesQuery,
                            $startedParentsNew
                        ],
                    ],
                    [
                        [
                            $startedMDR,
                            'представителей уязвимых групп больных МЛУ туберкулезом, начавших лечение',
                            'clients?project=6&profileField=Q5xs&profileOp=notNull&profileDate=Q5xs&started=1' .
                                '&mdr=1' . $datesQuery,
                            $startedMDRNew
                        ],
                        [
                            $overallStartedMDR,
                            'больных МЛУ туберкулезом',
                            'clients?project=6&profileField=kB3w&profileOp=notNull&mdr=1&remained=1' . $datesQuery,
                            null,
                            route('downloadMDR') . '?' . $datesQuery
                        ],
                        [
                            $overallRemainedMDR,
                            'больных МЛУ туберкулезом, продолжающих либо окончивших лечение',
                            'clients?project=6&profileField=kB3w&profileOp=notNull&profileDate=kB3w&mdr=1' .
                                '&remained=2' . $datesQuery
                        ],
                        [
                            $startedMDRMigrants,
                            'мигрантов с МЛУ ТБ',
                            'clients?project=6&profileField=Q5xs&profileOp=notNull' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                                '&profileDate=Q5xs&started=1&mdr=1' . $datesQuery,
                            $startedMDRMigrantsNew
                        ],
                        [
                            $startedMDRDrugUsers,
                            'ПН с МЛУ ТБ',
                            'clients?project=6&profileField=Q5xs&profileOp=notNull' .
                                '&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains&profileDate=Q5xs' .
                                '&started=1&mdr=1' . $datesQuery,
                            $startedMDRDrugUsersNew
                        ],
                        [
                            $startedMDRPrisoners,
                            'человек с МЛУ ТБ, вышедших из мест исполнения наказаний',
                            'clients?project=6&profileField=Q5xs&profileOp=notNull' .
                                '&profileField3=6g6x&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                                '&profileDate=Q5xs&started=1&mdr=1' . $datesQuery,
                            $startedMDRPrisonersNew
                        ],
                    ], [
                        [
                            $startedMDRChildren,
                            'детей с МЛУ ТБ',
                            'clients?project=6&profileField=Q5xs&profileOp=notNull' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=contains&profileDate=Q5xs' .
                                '&started=1&mdr=1' . $datesQuery,
                            $startedMDRChildrenNew
                        ],
                        [
                            $startedMDRParents,
                            'родителей с МЛУ ТБ',
                            'clients?project=6&profileField=Q5xs&profileOp=notNull' .
                                '&profileField3=6g6x&profileValue3=aSu3&profileOp3=contains&profileDate=Q5xs' .
                                '&started=1&mdr=1' . $datesQuery,
                            $startedMDRParentsNew
                        ],
                    ],
                    [
                        'title' => '6. Больные, возобновившие лечение',
                        'level' => 0
                    ],
                    [
                        [
                            $restarted,
                            'больных туберкулезом, возобновивших лечение',
                            'clients?project=6&profileField=Q5xs&profileOp=notNull' .
                                '&profileField3=9Rq9&profileValue3=F6SP&profileDate=Q5xs' . $datesQuery,
                            $restartedNew
                        ],
                        [0, 'мигрантов'],
                        [0, 'ПН'],
                        [0, 'бывших заключенных'],
                        [0, 'детей'],
                        [0, 'родителей'],
                        [0, 'получили вознаграждение за выявленные случай ТБ'],
                        [
                            $restartedMDR,
                            'больных МЛУ туберкулезом, возобновивших лечение',
                            'clients?project=6&profileField=Q5xs&profileOp=notNull' .
                                '&profileField3=9Rq9&profileValue3=F6SP&profileDate=Q5xs&mdr=1' . $datesQuery,
                            $restartedMDRNew
                        ],
                        [0, 'мигрантов'],
                        [0, 'ПН'],
                        [0, 'бывших заключенных'],
                        [0, 'детей'],
                        [0, 'родителей'],
                        [0, 'получили вознаграждение за выявленные случай ТБ'],
                    ],
                    [
                        'title' => '7. Исходы заболевания',
                        'level' => 0
                    ],
                    [
                        [
                            $outcomes,
                            'человек, получавших справки о приверженности',
                            'clients?project=6&outcomes=1' . $datesQuery,
                        ],
                        [
                            $outcomesTreated,
                            'продолжают медикаментозное лечение ПТП',
                            'clients?project=6&outcomes=bJjG&outcomes=PnKg' . $datesQuery,
                        ],
                        [
                            $outcomesRecovered,
                            'успешно завершили медикаментозное лечение ПТП',
                            'clients?project=6&outcomes=nXDJ' . $datesQuery,
                        ],
                        [
                            $outcomesLost,
                            'потеряны для последующего наблюдения',
                            'clients?project=6&outcomes=dAtv' . $datesQuery,
                        ],
                        [
                            $outcomesBecameMDR,
                            'ЛЧТБ перешел в МЛУТБ',
                            'clients?project=6&outcomes=EMsf' . $datesQuery,
                        ],
                        [
                            $outcomesBecameWDR,
                            'МЛУТБ перешел в ШЛУТБ',
                            'clients?project=6&outcomes=Ajf5' . $datesQuery,
                        ]
                    ],
                    [
                        [
                            $outcomesUnregistered,
                            'сняты с диспансерного учета',
                            'clients?project=6&outcomes=meHa' . $datesQuery,
                        ],
                        [
                            $outcomesMoved,
                            'уехали',
                            'clients?project=6&outcomes=meHa&reason=Lwch' . $datesQuery,
                        ],
                        [
                            $outcomesDied,
                            'умерли',
                            'clients?project=6&outcomes=meHa&reason=BtcT' . $datesQuery,
                        ],
                        [
                            $outcomesDiedTB,
                            'умерли от ТБ',
                            'clients?project=6&outcomes=meHa&reason=BtcT&cause=MppH' . $datesQuery,
                        ],
                        [
                            $outcomesJailed,
                            'попали в места исполнения наказания',
                            'clients?project=6&outcomes=meHa&reason=KyEj' . $datesQuery,
                        ],
                        [
                            $outcomesCured,
                            'излечены',
                            'clients?project=6&outcomes=meHa&reason=Ze52' . $datesQuery,
                        ]
                    ],
                    [
                        'title' => 'МЛУ',
                        'level' => 1
                    ],
                    [
                        [
                            $outcomesMDR,
                            'человек, получавших справки о приверженности',
                            'clients?project=6&outcomes=1&mdr=1' . $datesQuery,
                        ],
                        [
                            $outcomesTreatedMDR,
                            'продолжают медикаментозное лечение ПТП',
                            'clients?project=6&outcomes=bJjG&outcomes=PnKg&mdr=1' . $datesQuery,
                        ],
                        [
                            $outcomesRecoveredMDR,
                            'успешно завершили медикаментозное лечение ПТП',
                            'clients?project=6&outcomes=nXDJ&mdr=1' . $datesQuery,
                        ],
                        [
                            $outcomesLostMDR,
                            'потеряны для последующего наблюдения',
                            'clients?project=6&outcomes=dAtv&mdr=1' . $datesQuery,
                        ],
                    ],
                    [
                        [
                            $outcomesUnregisteredMDR,
                            'сняты с диспансерного учета',
                            'clients?project=6&outcomes=meHa&mdr=1' . $datesQuery,
                        ],
                        [
                            $outcomesMovedMDR,
                            'уехали',
                            'clients?project=6&outcomes=meHa&reason=Lwch&mdr=1' . $datesQuery,
                        ],
                        [
                            $outcomesDiedMDR,
                            'умерли',
                            'clients?project=6&outcomes=meHa&reason=BtcT&mdr=1' . $datesQuery,
                        ],
                        [
                            $outcomesDiedTBMDR,
                            'умерли от ТБ',
                            'clients?project=6&outcomes=meHa&reason=BtcT&cause=MppH&mdr=1' . $datesQuery,
                        ],
                        [
                            $outcomesJailedMDR,
                            'попали в места исполнения наказания',
                            'clients?project=6&outcomes=meHa&reason=KyEj&mdr=1' . $datesQuery,
                        ],
                        [
                            $outcomesCuredMDR,
                            'излечены',
                            'clients?project=6&outcomes=meHa&reason=Ze52&mdr=1' . $datesQuery,
                        ]
                    ],
                    [
                        'title' => 'Предыдущая версия',
                        'level' => 1
                    ],
                    [
                        [
                            $stopped,
                            'больных туберкулезом, прекративших лечение',
                            'clients?project=6&profileField=ZYpR&profileOp=notNull' .
                                '&profileField2=ZYpR&profileValue2=RdBz&profileValue2=LLFk&profileOp2=notin' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=notin' . $datesQuery,
                        ],
                        [
                            $stoppedUnregistered,
                            'cняты с диспансерного учета',
                            'clients?project=6&profileField=ZYpR&profileOp=notNull' .
                                '&profileField2=ZYpR&profileValue2=M6mj' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=notin' . $datesQuery,
                        ],
                        [
                            $stoppedDead,
                            'умерли',
                            'clients?project=6&profileField=ZYpR&profileOp=notNull' .
                                '&profileField2=ZYpR&profileValue2=KJyh' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=notin' . $datesQuery,
                        ],
                        [
                            $stoppedLost,
                            'не выходят на связь',
                            'clients?project=6&profileField=ZYpR&profileOp=notNull' .
                                '&profileField2=ZYpR&profileValue2=Nh49' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=notin' . $datesQuery,
                        ],
                        [
                            $stoppedQuit,
                            'бросили лечение',
                            'clients?project=6&profileField=ZYpR&profileOp=notNull' .
                                '&profileField2=ZYpR&profileValue2=Jc36' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=notin' . $datesQuery,
                        ],
                        [
                            $stoppedFinished,
                            'успешно завершили медикаментозное лечение',
                            'clients?project=6&profileField=ZYpR&profileOp=notNull' .
                                '&profileField2=ZYpR&profileValue2=vZv4' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=notin' . $datesQuery,
                        ],
                    ],
                    [
                        [
                            $stoppedMDR,
                            'больных МЛУ туберкулезом, прекративших лечение',
                            'clients?project=6&profileField=ZYpR&profileOp=notNull' .
                                '&profileField2=ZYpR&profileValue2=RdBz&profileValue2=LLFk&profileOp2=notin' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=notin&mdr=1' . $datesQuery,
                        ],
                        [
                            $stoppedUnregisteredMDR,
                            'cняты с диспансерного учета',
                            'clients?project=6&profileField=ZYpR&profileOp=notNull' .
                                '&profileField2=ZYpR&profileValue2=M6mj' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=notin&mdr=1' . $datesQuery,
                        ],
                        [
                            $stoppedDeadMDR,
                            'умерли',
                            'clients?project=6&profileField=ZYpR&profileOp=notNull' .
                                '&profileField2=ZYpR&profileValue2=KJyh' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=notin&mdr=1' . $datesQuery,
                        ],
                        [
                            $stoppedLostMDR,
                            'не выходят на связь',
                            'clients?project=6&profileField=ZYpR&profileOp=notNull' .
                                '&profileField2=ZYpR&profileValue2=Nh49' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=notin&mdr=1' . $datesQuery,
                        ],
                        [
                            $stoppedQuitMDR,
                            'бросили лечение',
                            'clients?project=6&profileField=ZYpR&profileOp=notNull' .
                                '&profileField2=ZYpR&profileValue2=Jc36' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=notin&mdr=1' . $datesQuery,
                        ],
                        [
                            $stoppedFinishedMDR,
                            'успешно завершили медикаментозное лечение',
                            'clients?project=6&profileField=ZYpR&profileOp=notNull' .
                                '&profileField2=ZYpR&profileValue2=vZv4' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=notin&mdr=1' . $datesQuery,
                        ],
                    ],
                    [
                        [
                            $stoppedChildren,
                            'детей, больных туберкулезом, прекративших лечение',
                            'clients?project=6&profileField=ZYpR&profileOp=notNull' .
                                '&profileField2=ZYpR&profileValue2=RdBz&profileValue2=LLFk&profileOp2=notin' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $stoppedUnregisteredChildren,
                            'cняты с диспансерного учета',
                            'clients?project=6&profileField=ZYpR&profileOp=notNull' .
                                '&profileField2=ZYpR&profileValue2=M6mj' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $stoppedDeadChildren,
                            'умерли',
                            'clients?project=6&profileField=ZYpR&profileOp=notNull' .
                                '&profileField2=ZYpR&profileValue2=KJyh' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $stoppedLostChildren,
                            'не выходят на связь',
                            'clients?project=6&profileField=ZYpR&profileOp=notNull' .
                                '&profileField2=ZYpR&profileValue2=Nh49' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $stoppedQuitChildren,
                            'бросили лечение',
                            'clients?project=6&profileField=ZYpR&profileOp=notNull' .
                                '&profileField2=ZYpR&profileValue2=Jc36' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $stoppedFinishedChildren,
                            'успешно завершили медикаментозное лечение',
                            'clients?project=6&profileField=ZYpR&profileOp=notNull' .
                                '&profileField2=ZYpR&profileValue2=vZv4' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=contains' . $datesQuery,
                        ],
                    ],
                    [
                        [
                            $stoppedMDRChildren,
                            'детей, больных МЛУ туберкулезом, прекративших лечение',
                            'clients?project=6&profileField=ZYpR&profileOp=notNull' .
                                '&profileField2=ZYpR&profileValue2=RdBz&profileValue2=LLFk&profileOp2=notin' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=notin&mdr=1' . $datesQuery,
                        ],
                        [
                            $stoppedUnregisteredMDRChildren,
                            'cняты с диспансерного учета',
                            'clients?project=6&profileField=ZYpR&profileOp=notNull' .
                                '&profileField2=ZYpR&profileValue2=M6mj' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=notin&mdr=1' . $datesQuery,
                        ],
                        [
                            $stoppedDeadMDRChildren,
                            'умерли',
                            'clients?project=6&profileField=ZYpR&profileOp=notNull' .
                                '&profileField2=ZYpR&profileValue2=KJyh' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=notin&mdr=1' . $datesQuery,
                        ],
                        [
                            $stoppedLostMDRChildren,
                            'не выходят на связь',
                            'clients?project=6&profileField=ZYpR&profileOp=notNull' .
                                '&profileField2=ZYpR&profileValue2=Nh49' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=notin&mdr=1' . $datesQuery,
                        ],
                        [
                            $stoppedQuitMDRChildren,
                            'бросили лечение',
                            'clients?project=6&profileField=ZYpR&profileOp=notNull' .
                                '&profileField2=ZYpR&profileValue2=Jc36' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=notin&mdr=1' . $datesQuery,
                        ],
                        [
                            $stoppedFinishedMDRChildren,
                            'успешно завершили медикаментозное лечение',
                            'clients?project=6&profileField=ZYpR&profileOp=notNull' .
                                '&profileField2=ZYpR&profileValue2=vZv4' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=notin&mdr=1' . $datesQuery,
                        ],
                    ],
                    [
                        'title' => '8. Партнеры',
                        'level' => 0
                    ],
                    [
                        [
                            $informed,
                            'стэйкхолдеров (партнеров) проинформировано о проекте',
                            'clients?project=6&profileField=6g6x&profileValue=HeBk&profileOp=contains' .
                                '&profileField3=Y4N4&profileValue3=26bK&profileValue3=enqF&profileOp3=otherthan' .
                                '&parts=544&parts=545&partner=1' . $datesQuery,
                            $informedNew
                        ],
                        [
                            $informedMen,
                            'мужчин',
                            'clients?project=6&profileField=6g6x&profileValue=HeBk&profileOp=contains' .
                                '&profileField3=Y4N4&profileValue3=26bK&profileValue3=enqF&profileOp3=otherthan' .
                                '&gender=male&&parts=544&parts=545&partner=1' . $datesQuery,
                            $informedMenNew
                        ],
                        [
                            $informedWomen,
                            'женщин',
                            'clients?project=6&profileField=6g6x&profileValue=HeBk&profileOp=contains' .
                                '&profileField3=Y4N4&profileValue3=26bK&profileValue3=enqF&profileOp3=otherthan' .
                                '&gender=female&&parts=544&parts=545&partner=1' . $datesQuery,
                            $informedWomenNew
                        ]
                    ],
                    [
                        [
                            $informedMahalla,
                            'сотрудников махаллей',
                            'clients?project=6&profileField=Y4N4&profileValue=kyLa' .
                                '&parts=544&parts=545&partner=1' . $datesQuery,
                            $informedMahallaNew
                        ],
                        [
                            $informedMahallaGroupServices,
                            'групповых информирований в махаллях',
                            'services?projects=6&profileField=Y4N4&profileValue=kyLa' .
                                '&parts=545&partner=1&groupby=2' . $datesQuery,
                        ],
                        [
                            $informedMahallaServices,
                            'индивидуальных информирований в махаллях',
                            'services?projects=6&profileField=Y4N4&profileValue=kyLa' .
                                '&parts=544&partner=1&groupby=2' . $datesQuery,
                        ],
                    ],
                    [
                        [
                            $informedMahallasList->count(),
                            'махаллей: ' . $informedMahallasList->join(', '),
                        ],
                    ],
                    [
                        [
                            $informed2,
                            'сотрудников кабинетов доверия',
                            'clients?project=6&profileField=Y4N4&profileValue=jo8e' .
                                '&parts=544&parts=545&partner=1' . $datesQuery,
                            $informed2New
                        ],
                        [
                            $informed2Services,
                            'информирований в кабинетах доверия',
                            'services?projects=6&profileField=Y4N4&profileValue=jo8e' .
                                '&parts=544&parts=545&partner=1&groupby=2' . $datesQuery,
                        ],
                        [
                            $informed3,
                            'сотрудников центров по борьбе со СПИДом',
                            'clients?project=6&profileField=Y4N4&profileValue=MPbS' .
                                '&parts=544&parts=545&partner=1' . $datesQuery,
                            $informed3New
                        ],
                        [
                            $informed3Services,
                            'информирований в центрах по борьбе со СПИДом',
                            'services?projects=6&profileField=Y4N4&profileValue=MPbS' .
                                '&parts=544&parts=545&partner=1&groupby=2' . $datesQuery,
                        ],
                        [
                            $informed4,
                            'сотрудников центров адаптации и социальной помощи при Хокимияте',
                            'clients?project=6&profileField=Y4N4&profileValue=9sJk' .
                                '&parts=544&parts=545&partner=1' . $datesQuery,
                            $informed4New
                        ],
                        [
                            $informed4Services,
                            'информирований в центрах адаптации и социальной помощи при Хокимияте',
                            'services?projects=6&profileField=Y4N4&profileValue=9sJk' .
                                '&parts=544&parts=545&partner=1&groupby=2' . $datesQuery,
                        ],
                        [
                            $informed4List,
                            'центров адаптации'
                        ],
                    ],
                    [
                        [
                            $informed6,
                            'партнеров на стройках',
                            'clients?project=6&profileField=Y4N4&profileValue=26bK' .
                                '&parts=544&parts=545&partner=1' . $datesQuery,
                            $informed6New
                        ],
                        [
                            $informed6Services,
                            'информирований на стройках',
                            'services?projects=6&profileField=Y4N4&profileValue=26bK' .
                                '&parts=544&parts=545&partner=1&groupby=2' . $datesQuery,
                        ],
                        [
                            $informed7,
                            'партнеров в военных частях',
                            'clients?project=6&profileField=Y4N4&profileValue=enqF' .
                                '&parts=544&parts=545&partner=1' . $datesQuery,
                            $informed7New
                        ],
                        [
                            $informed7Services,
                            'информирований в военных частях',
                            'services?projects=6&profileField=Y4N4&profileValue=enqF' .
                                '&parts=544&parts=545&partner=1&groupby=2' . $datesQuery,
                        ],
                        [
                            $informed8,
                            'сотрудников фтизиатрической службы',
                            'clients?project=6&profileField=Y4N4&profileOp=or&profileValue=6j2x&profileValue=hB4Q' .
                                '&profileValue=daf5&profileValue=HjQ9&profileValue=oCBQ&profileValue=eEhD' .
                                '&profileValue=unka&profileValue=cutm&profileValue=Kwwj&profileValue=xJxu' .
                                '&parts=545&partner=1' . $datesQuery,
                            $informed7New
                        ],
                        [
                            $informed8Services,
                            'информирований для сотрудников фтизиатрической службы',
                            'services?projects=6&profileField=Y4N4&profileOp=or&profileValue=6j2x&profileValue=hB4Q' .
                                '&profileValue=daf5&profileValue=HjQ9&profileValue=oCBQ&profileValue=eEhD' .
                                '&profileValue=unka&profileValue=cutm&profileValue=Kwwj&profileValue=xJxu' .
                                '&parts=545&partner=1' . $datesQuery,
                        ],
                    ],
                    [
                        [
                            $informed5,
                            'сотрудников других организаций',
                            'clients?project=6' .
                                '&profileField3=Y4N4&profileValue3=kyLa&profileValue3=jo8e&profileValue3=MPbS' .
                                    '&profileValue3=9sJk&profileOp3=notin&parts=544&parts=545&partner=1' . $datesQuery,
                            $informed5New
                        ],
                        [
                            $informed5Services,
                            'информирований в других организациях',
                            'services?projects=6' .
                                '&profileField=Y4N4&profileValue=kyLa&profileValue=jo8e&profileValue=MPbS' .
                                    '&profileValue=9sJk&profileOp=notin&parts=544' .
                                '&parts=545&partner=1&groupby=2' . $datesQuery,
                        ],
                        [
                            $informed5List,
                            'других организаций'
                        ],
                        [
                            $trainings,
                            'тренинга',
                            'services?projects=6&parts=546' . $datesQuery,
                        ]
                    ],
                    [
                        [
                            $migrantsInformedOnly,
                            'мигрантов проинформировано (без скрининга)',
                            'clients?project=6' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                            '&searchActivities=скрининг&inverse=2&parts=544&parts=545' . $datesQuery,
                        ],
                        [
                            $migrantsInformedOnlyMale,
                            'мужчин',
                            'clients?project=6&gender=male' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                            '&searchActivities=скрининг&inverse=2&parts=544&parts=545' . $datesQuery,
                        ],
                        [
                            $migrantsInformedOnlyFemale,
                            'женщин',
                            'clients?project=6&gender=female' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                            '&searchActivities=скрининг&inverse=2&parts=544&parts=545' . $datesQuery,
                        ]
                    ],
                ]
            ], [
                'title' => 'Показатели ETBU\'',
                'items' => [
                    [
                        [
                            $totalServicesNL,
                            'всего услуг предоставлено всем категориям клиентов проекта, кроме партнёров '.
                                'и клиентов с ограниченным доступом к медицинским услугам',
                            'services?projects=6&profileField=6g6x&profileOp=notin' .
                                '&profileValue=HeBk&profileValue=dLBE' .
                            '&parts=731&inverse=1' . $datesQuery
                        ],
                        [
                            $totalVulnerableServicesNL,
                            'услуг предоставлено представителям уязвимых групп (c ТБ и без)',
                            'services?projects=6&vuln=2' .
                            '&profileField=6g6x&profileValue=uQRP&profileValue=gG99' .
                            '&profileValue=2AJg&profileValue=YNoK&profileOp=contains' .
                            '&parts=731&inverse=1' . $datesQuery
                        ],
                        [
                            $totalLegalServicesNL,
                            'юридических услуг оказано',
                            'services?projects=6&parts=366&parts=370' .
                            '&profileField=6g6x&profileValue=uQRP&profileValue=gG99' .
                            '&profileValue=2AJg&profileValue=YNoK&profileOp=contains' .
                            $datesQuery
                        ],
                        [
                            $legalNL,
                            'человек получили юридическую помощь',
                            'clients?project=6&parts=366&parts=370' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                            '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' .
                            $datesQuery,
                            $legalNLNew
                        ]
                    ],
                    [
                        [
                            $vulnerablePatientSchoolServicesNL,
                            '"школ пациента" проведено для представителей уязвимых групп с ТБ',
                            'services?projects=6' .
                                '&profileField=6g6x&profileValue=uQRP&profileValue=gG99' .
                                '&profileValue=2AJg&profileValue=YNoK&profileOp=contains' .
                                '&vuln=1&parts=372&parts=372&parts=373&parts=374&parts=375&parts=376&parts=474' .
                                    '&parts=475&parts=479&groupby=start_date' .
                                        ($customAccess ? '&verified=0' : '') . $datesQuery
                        ],
                        [
                            $vulnerablePatientSchoolNL,
                            'представителей уязвимых групп с ТБ приняли участие в "школах пациента"',
                            'clients?project=6' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' .
                                '&vuln=1&parts=372&parts=372&parts=373&parts=374&parts=375&parts=376&parts=474' .
                                    '&parts=475&parts=479' . $datesQuery,
                            $vulnerablePatientSchoolNewNL
                        ],
                        [
                            $vulnerablePatientSchoolMaleNL,
                            'мужчин',
                            'clients?project=6' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' .
                                '&vuln=1&parts=372&parts=372&parts=373&parts=374&parts=375&parts=376&parts=474' .
                                    '&parts=475&parts=479&gender=male' . $datesQuery,
                            $vulnerablePatientSchoolMaleNewNL
                        ],
                        [
                            $vulnerablePatientSchoolFemaleNL,
                            'женщин',
                            'clients?project=6' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' .
                                '&vuln=1&parts=372&parts=372&parts=373&parts=374&parts=375&parts=376&parts=474' .
                                    '&parts=475&parts=479&gender=female' . $datesQuery,
                            $vulnerablePatientSchoolFemaleNewNL,
                        ],
                        [
                            $vulnerablePatientSchoolParticipationsNL,
                            'всего участий в "школах пациента" для представителей уязвимых групп с ТБ',
                        ]
                    ],
                    [
                        'title' => 'Аутрич',
                        'level' => 0
                    ],
                    [
                        [
                            $escortedNL,
                            'представителей уязвимых групп (мигранты, освободившиеся, ПН, ' .
                                'люди с ограниченным доступом к медуслугам) ' .
                                'были сопровождены для тестирования на туберкулез',
                            'clients?project=6&examinedAfter=2020-09-01&searchActivities=тб' .
                                '&parts=362&parts=363&examinedFrom=us' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                            $escortedNLNew
                        ],
                        [
                            $transportedNL,
                            'представителей уязвимых групп (мигранты, освободившиеся, ПН, ' .
                                'люди с ограниченным доступом к медуслугам) ' .
                                'были транспортированы для тестирования на туберкулез',
                            'clients?project=6&examinedAfter=2020-09-01&searchActivities=тб' .
                                '&parts=363&examinedFrom=us' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                            $transportedNLNew
                        ],
                        [
                            $transportedServicesNL,
                            'услуг по транспортировке для тестирования на туберкулез оказано представителем уязвимых ' .
                                'групп (мигранты, освободившиеся, ПН, люди с ограниченным доступом к медуслугам)',
                            'services?projects=6&examinedAfter=2020-09-01&searchActivities=тб&parts=363' .
                                '&examinedFrom=us' .
                                '&profileField=6g6x&profileValue=uQRP&profileValue=gG99' .
                                    '&profileValue=2AJg&profileValue=YNoK&profileOp=contains' . $datesQuery,
                        ],
                    ],
                    [
                        [
                            $servicesOutreachNL,
                            'услуг оказано на этапе аутрич',
                            'services?projects=6&outreach=1' .
                            '&parts=731&inverse=1' .
                            '&profileField=6g6x&profileValue=uQRP&profileValue=gG99' .
                                '&profileValue=2AJg&profileValue=YNoK&profileOp=contains' . $datesQuery
                        ],
                        [
                            $servicedOutreachNL,
                            'представителей уязвимых групп получили услуги на этапе аутрич',
                            'clients?project=6&outreach=1&parts=' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $servicedOutreachNLMale,
                            'мужчин',
                            'clients?project=6&outreach=1&parts=&gender=male' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $servicedOutreachNLFemale,
                            'женщин',
                            'clients?project=6&outreach=1&parts=&gender=female' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $servicedOutreachNLMigrants,
                            'мигрантов',
                            'clients?project=6&outreach=1&parts=' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                            $datesQuery
                        ],
                        [
                            $servicedOutreachNLPrisoners,
                            'вышедших из мест исполнения наказаний',
                            'clients?project=6&outreach=1&parts=' .
                            '&profileField3=6g6x&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                            $datesQuery
                        ],
                        [
                            $servicedOutreachNLDrugUsers,
                            'ПН',
                            'clients?project=6&outreach=1&parts=' .
                            '&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' .
                            $datesQuery
                        ],
                    ],
                    [
                        [
                            $psychoServicesOutreachNL,
                            'услуг психолога оказано на этапе аутрич',
                            'services?projects=6&parts=367&parts=371&parts=375&parts=376&outreach=1' .
                            '&profileField=6g6x&profileValue=uQRP&profileValue=gG99' .
                                '&profileValue=2AJg&profileValue=YNoK&profileOp=contains' . $datesQuery
                        ],
                        [
                            $psychoServicedOutreachNL,
                            'представителей уязвимых групп получили услуги психолога на этапе аутрич',
                            'clients?project=6&parts=367&parts=371&parts=375&parts=376&outreach=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $psychoServicedOutreachNLMale,
                            'мужчин',
                            'clients?project=6&parts=367&parts=371&parts=375&parts=376&outreach=1&gender=male' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $psychoServicedOutreachNLFemale,
                            'женщин',
                            'clients?project=6&parts=367&parts=371&parts=375&parts=376&outreach=1&gender=female' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $psychoServicedOutreachNLMigrants,
                            'мигрантов',
                            'clients?project=6&parts=367&parts=371&parts=375&parts=376&outreach=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                            $datesQuery
                        ],
                        [
                            $psychoServicedOutreachNLPrisoners,
                            'вышедших из мест исполнения наказаний',
                            'clients?project=6&parts=367&parts=371&parts=375&parts=376&outreach=1' .
                            '&profileField3=6g6x&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                            $datesQuery
                        ],
                        [
                            $psychoServicedOutreachNLDrugUsers,
                            'ПН',
                            'clients?project=6&parts=367&parts=371&parts=375&parts=376&outreach=1' .
                            '&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' .
                            $datesQuery
                        ],
                    ],
                    [
                        [
                            $legalServicesOutreachNL,
                            'юридических услуг оказано на этапе аутрич',
                            'services?projects=6&parts=366&parts=370&parts=374&outreach=1' .
                            '&profileField=6g6x&profileValue=uQRP&profileValue=gG99' .
                                '&profileValue=2AJg&profileValue=YNoK&profileOp=contains' . $datesQuery
                        ],
                        [
                            $legalServicedOutreachNL,
                            'представителей уязвимых групп получили юридические услуги на этапе аутрич',
                            'clients?project=6&parts=366&parts=370&parts=374&outreach=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $legalServicedOutreachNLMale,
                            'мужчин',
                            'clients?project=6&parts=366&parts=370&parts=374&outreach=1&gender=male' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $legalServicedOutreachNLFemale,
                            'женщин',
                            'clients?project=6&parts=366&parts=370&parts=374&outreach=1&gender=female' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $legalServicedOutreachNLMigrants,
                            'мигрантов',
                            'clients?project=6&parts=366&parts=370&parts=374&outreach=1&' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                            $datesQuery
                        ],
                        [
                            $legalServicedOutreachNLPrisoners,
                            'вышедших из мест исполнения наказаний',
                            'clients?project=6&parts=366&parts=370&parts=374&outreach=1' .
                            '&profileField3=6g6x&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                            $datesQuery
                        ],
                        [
                            $legalServicedOutreachNLDrugUsers,
                            'ПН',
                            'clients?project=6&parts=366&parts=370&parts=374&outreach=1' .
                            '&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' .
                            $datesQuery
                        ],
                    ],
                    [
                        [
                            $socServicesOutreachNL,
                            'услуг соцработника оказано на этапе аутрич',
                            'services?projects=6&parts=365&parts=369&parts=373&parts=474' .
                            '&parts=364&parts=368&parts=372&positionex=тб' .
                            '&parts=363&parts=362&parts=361&parts=543&parts=377&parts=379&parts=528&parts=527'.
                            '&outreach=1&profileField=6g6x&profileValue=uQRP&profileValue=gG99' .
                                '&profileValue=2AJg&profileValue=YNoK&profileOp=contains' . $datesQuery
                        ],
                        [
                            $socServicedOutreachNL,
                            'представителей уязвимых групп получили услуги соцработника на этапе аутрич',
                            'clients?project=6&parts=365&parts=369&parts=373&parts=474' .
                            '&parts=364&parts=368&parts=372&positionex=тб' .
                            '&parts=363&parts=362&parts=361&parts=543&parts=377&parts=379&parts=528&parts=527'.
                            '&outreach=1&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $socServicedOutreachNLMale,
                            'мужчин',
                            'clients?project=6&parts=365&parts=369&parts=373&parts=474' .
                            '&parts=364&parts=368&parts=372&positionex=тб' .
                            '&parts=363&parts=362&parts=361&parts=543&parts=377&parts=379&parts=528&parts=527'.
                            '&outreach=1&gender=male&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $socServicedOutreachNLFemale,
                            'женщин',
                            'clients?project=6&parts=365&parts=369&parts=373&parts=474' .
                            '&parts=364&parts=368&parts=372&positionex=тб' .
                            '&parts=363&parts=362&parts=361&parts=543&parts=377&parts=379&parts=528&parts=527'.
                            '&outreach=1&gender=female&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $socServicedOutreachNLMigrants,
                            'мигрантов',
                            'clients?project=6&parts=365&parts=369&parts=373&parts=474' .
                            '&parts=364&parts=368&parts=372&positionex=тб' .
                            '&parts=363&parts=362&parts=361&parts=543&parts=377&parts=379&parts=528&parts=527'.
                            '&outreach=1&&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains'.
                            $datesQuery
                        ],
                        [
                            $socServicedOutreachNLPrisoners,
                            'вышедших из мест исполнения наказаний',
                            'clients?project=6&parts=365&parts=369&parts=373&parts=474' .
                            '&parts=364&parts=368&parts=372&positionex=тб' .
                            '&parts=363&parts=362&parts=361&parts=543&parts=377&parts=379&parts=528&parts=527'.
                            '&outreach=1&profileField3=6g6x&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                            $datesQuery
                        ],
                        [
                            $socServicedOutreachNLDrugUsers,
                            'ПН',
                            'clients?project=6&parts=365&parts=369&parts=373&parts=474' .
                            '&parts=364&parts=368&parts=372&positionex=тб' .
                            '&parts=363&parts=362&parts=361&parts=543&parts=377&parts=379&parts=528&parts=527'.
                            '&outreach=1&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' .
                            $datesQuery
                        ],
                    ],
                    [
                        [
                            $phthiServicesOutreachNL,
                            'услуг фтизиатра оказано на этапе аутрич',
                            'services?projects=6&parts=364&parts=368&parts=372&outreach=1' .
                            '&position=тб' .
                            '&profileField=6g6x&profileValue=uQRP&profileValue=gG99' .
                                '&profileValue=2AJg&profileValue=YNoK&profileOp=contains' . $datesQuery
                        ],
                        [
                            $phthiServicedOutreachNL,
                            'представителей уязвимых групп получили услуги фтизиатра на этапе аутрич',
                            'clients?project=6&parts=364&parts=368&parts=372&outreach=1' .
                            '&position=тб' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $phthiServicedOutreachNLMale,
                            'мужчин',
                            'clients?project=6&parts=364&parts=368&parts=372&outreach=1&gender=male' .
                            '&position=тб' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $phthiServicedOutreachNLFemale,
                            'женщин',
                            'clients?project=6&parts=364&parts=368&parts=372&outreach=1&gender=female' .
                            '&position=тб' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $phthiServicedOutreachNLMigrants,
                            'мигрантов',
                            'clients?project=6&parts=364&parts=368&parts=372&outreach=1&' .
                            '&position=тб' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                            $datesQuery
                        ],
                        [
                            $phthiServicedOutreachNLPrisoners,
                            'вышедших из мест исполнения наказаний',
                            'clients?project=6&parts=364&parts=368&parts=372&outreach=1' .
                            '&position=тб' .
                            '&profileField3=6g6x&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                            $datesQuery
                        ],
                        [
                            $phthiServicedOutreachNLDrugUsers,
                            'ПН',
                            'clients?project=6&parts=364&parts=368&parts=372&outreach=1' .
                            '&position=тб' .
                            '&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' .
                            $datesQuery
                        ],
                    ],
                    [
                        [
                            $screenedNL,
                            'VPs screened for TB through Program outreach',
                            'clients?project=6&searchActivities=скрининг&vuln=3' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $screenedMaleNL,
                            'male',
                            'clients?project=6&searchActivities=скрининг&vuln=3&gender=male' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $screenedFemaleNL,
                            'female',
                            'clients?project=6&searchActivities=скрининг&vuln=3&gender=female' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $screenedPrisoner,
                            'ex-prisoners',
                            'clients?project=6&searchActivities=скрининг&vuln=3' .
                                '&profileField3=6g6x&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                                $datesQuery,
                        ],
                        [
                            $screenedMigrant,
                            'migrants',
                            'clients?project=6&searchActivities=скрининг&vuln=3' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                                $datesQuery,
                        ],
                        [
                            $screenedDrugUser,
                            'drug users',
                            'clients?project=6&searchActivities=скрининг&vuln=3' .
                                '&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $redundantScreenedNL,
                            'VPs from multiple categories screened for TB through Program outreach',
                            'clients?project=6&searchActivities=скрининг&vuln=3&redundant=2' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                    ],
                    [
                        [
                            $examinedNL,
                            'VPs tested for TB through Program referral',
                            'clients?project=6&examinedAfter=2020-09-01&dateFilter=examined&examinedFrom=us' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' .
                                '&vuln=3' . $datesQuery,
                            $examinedNLNew
                        ],
                        [
                            $examinedMaleNL,
                            'male',
                            'clients?project=6&examinedAfter=2020-09-01&dateFilter=examined&examinedFrom=us' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' .
                                '&vuln=3&gender=male' . $datesQuery,
                            $examinedMaleNLNew
                        ],
                        [
                            $examinedFemaleNL,
                            'female',
                            'clients?project=6&examinedAfter=2020-09-01&dateFilter=examined&examinedFrom=us' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' .
                                '&vuln=3&gender=female' . $datesQuery,
                            $examinedFemaleNLNew
                        ],
                        [
                            $examinedPrisoners,
                            'ex-prisoners',
                            'clients?project=6&examinedAfter=2020-09-01&dateFilter=examined&examinedFrom=us' .
                                '&vuln=3&profileField3=6g6x&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                                $datesQuery,
                            $examinedPrisonersNew
                        ],
                        [
                            $examinedMigrants,
                            'migrants',
                            'clients?project=6&examinedAfter=2020-09-01&dateFilter=examined&examinedFrom=us' .
                                '&vuln=3&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                                $datesQuery,
                            $examinedMigrantsNew
                        ],
                        [
                            $examinedDrugUsers,
                            'drug users',
                            'clients?project=6&examinedAfter=2020-09-01&dateFilter=examined&examinedFrom=us' .
                                '&vuln=3&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' . $datesQuery,
                            $examinedDrugUsersNew
                        ],
                        [
                            $redundantExaminedNL,
                            'VPs from multiple categories tested for TB through Program referral',
                            'clients?project=6&examinedAfter=2020-09-01&dateFilter=examined&examinedFrom=us' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains&redundant=2' .
                                '&vuln=3' . $datesQuery,
                        ]
                    ],
                    [
                        'title' => 'МДК',
                        'level' => 0
                    ],
                    [
                        [
                            $overallStartedNL
                                ? round($overallRemainedNL / $overallStartedNL * 1000) / 10 . '%'
                                : 0,
                            'представителей уязвимых групп с ТБ успешно завершили или продолжают лечение',
                        ],
                        [
                            $overallStartedMDRNL
                                ? round($overallRemainedMDRNL / $overallStartedMDRNL * 1000) / 10 . '%'
                                : 0,
                            'представителей уязвимых групп с ЛУ ТБ успешно завершили или продолжают лечение',
                        ]
                    ],
                    [
                        [
                            $vulnerableServicesNL,
                            'услуг оказано представителям уязвимых групп с ТБ',
                            'services?projects=6&vuln=1' .
                            '&parts=731&inverse=1' .
                            '&profileField=6g6x&profileValue=uQRP&profileValue=gG99' .
                                '&profileValue=2AJg&profileValue=YNoK&profileOp=contains' . $datesQuery
                        ],
                        [
                            $enteredNL,
                            'представителей уязвимых групп с ТБ принято в проект',
                            'clients?project=6&parts=&vuln=1&profileDate=kB3w' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $reHospitalizedNL,
                            'представителей уязвимых групп с ТБ госпитализировано повторно',
                            'clients?project=6&parts=&vuln=1&profileDate=fofk&profileField=eTzT&profileOp=true' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $legalVulnerableServicesNL,
                            'юридических услуг оказано представителям уязвимых групп с ТБ',
                            'services?projects=6&parts=366&parts=370&vuln=1' .
                            '&profileField=6g6x&profileValue=uQRP&profileValue=gG99' .
                                '&profileValue=2AJg&profileValue=YNoK&profileOp=contains' . $datesQuery,
                        ]
                    ],
                    [
                        [
                            $vulnerableNL,
                            'представителей уязвимых групп с ТБ получили услуги',
                            'clients?project=6&parts=&vuln=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                            $vulnerableNLNew
                        ],
                        [
                            $vulnerableMDRNL,
                            'представителей уязвимых групп с МЛУ ТБ получили услуги',
                            'clients?project=6&parts=&vuln=1&mdr=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                            $vulnerableMDRNLNew
                        ],
                        [
                            $supportedNLRedundant,
                            'VPs who started TB treatment and received adherence support through Program services ' .
                                '(multiple categories)',
                            'clients?project=6&vuln=1&parts=&redundant=2&' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                    ],
                    [
                        [
                            $outcomesNL,
                            'человек, получавших справки о приверженности',
                            'clients?project=6&outcomes=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $outcomesTreatedNL,
                            'продолжают медикаментозное лечение ПТП',
                            'clients?project=6&outcomes=bJjG&outcomes=PnKg' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $outcomesRecoveredNL,
                            'успешно завершили медикаментозное лечение ПТП',
                            'clients?project=6&outcomes=nXDJ' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $outcomesLostNL,
                            'потеряны для последующего наблюдения',
                            'clients?project=6&outcomes=dAtv' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                    ],
                    [
                        [
                            $outcomesUnregisteredNL,
                            'сняты с диспансерного учета',
                            'clients?project=6&outcomes=meHa' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $outcomesMovedNL,
                            'уехали',
                            'clients?project=6&outcomes=meHa&reason=Lwch' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $outcomesDiedNL,
                            'умерли',
                            'clients?project=6&outcomes=meHa&reason=BtcT' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $outcomesDiedTBNL,
                            'умерли от ТБ',
                            'clients?project=6&outcomes=meHa&reason=BtcT&cause=MppH' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $outcomesJailedNL,
                            'попали в места исполнения наказания',
                            'clients?project=6&outcomes=meHa&reason=KyEj' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $outcomesCuredNL,
                            'излечены',
                            'clients?project=6&outcomes=meHa&reason=Ze52' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $outcomesBecameMDRNL,
                            'ЛЧТБ перешел в МЛУТБ',
                            'clients?project=6&outcomes=EMsf' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $outcomesBecameWDRNL,
                            'МЛУТБ перешел в ШЛУТБ',
                            'clients?project=6&outcomes=Ajf5' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ]
                    ],
                    [
                        [
                            $outcomesNLServices,
                            'услуг оказано представителям уязвимых групп, получавших справки о приверженности',
                        ],
                        [
                            $outcomesNLServiced,
                            'представителей уязвимых групп, получавших справки о приверженности, получали услуги',
                            'clients?project=6&outcomes=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains&parts=' . $datesQuery,
                        ],
                        [
                            $outcomesNLServicedMen,
                            'мужчин',
                            'clients?project=6&outcomes=1&gender=male' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains&parts=' . $datesQuery,
                        ],
                        [
                            $outcomesNLServicedWomen,
                            'женщин',
                            'clients?project=6&outcomes=1&gender=female' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains&parts=' . $datesQuery,
                        ],
                        [
                            $outcomesNLServicedMigrants,
                            'мигрантов',
                            'clients?project=6&outcomes=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                            '&parts=' . $datesQuery,
                        ],
                        [
                            $outcomesNLServicedDrugUsers,
                            'ПН',
                            'clients?project=6&outcomes=1' .
                            '&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains&parts=' . $datesQuery,
                        ],
                        [
                            $outcomesNLServicedPrisoners,
                            'вышедших из мест исполнения наказаний',
                            'clients?project=6&outcomes=1' .
                            '&profileField3=6g6x&profileValue3=gG99&profileOp3=contains&parts=' . $datesQuery,
                        ]
                    ],
                    [
                        [
                            $noOutcomesNLServices,
                            'услуг оказано представителям уязвимых групп, не получавших справки о приверженности',
                        ],
                        [
                            $noOutcomesNLServiced,
                            'представителей уязвимых групп, не получавших справки о приверженности, получали услуги',
                            'clients?project=6&parts=&vuln=1&outcomes=-1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $noOutcomesNLServicedMen,
                            'мужчин',
                            'clients?project=6&parts=&vuln=1&outcomes=-1&gender=male' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $noOutcomesNLServicedWomen,
                            'женщин',
                            'clients?project=6&parts=&vuln=1&outcomes=-1&gender=female' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $noOutcomesNLServicedMigrants,
                            'мигрантов',
                            'clients?project=6&vuln=1&outcomes=-1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                            '&parts=' . $datesQuery,
                        ],
                        [
                            $noOutcomesNLServicedDrugUsers,
                            'ПН',
                            'clients?project=6&vuln=1&outcomes=-1' .
                            '&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains&parts=' . $datesQuery,
                        ],
                        [
                            $noOutcomesNLServicedPrisoners,
                            'вышедших из мест исполнения наказаний',
                            'clients?project=6&vuln=1&outcomes=-1' .
                            '&profileField3=6g6x&profileValue3=gG99&profileOp3=contains&parts=' . $datesQuery,
                        ]
                    ],
                    [
                        [
                            $socialServicesNL,
                            'услуг соцработника/фтизиатра оказано представителям уязвимых групп',
                            'services?projects=6&parts=364&parts=365&parts=368&parts=369&parts=373&vuln=1' .
                            '&profileField=6g6x&profileValue=uQRP&profileValue=gG99' .
                                '&profileValue=2AJg&profileValue=YNoK&profileOp=contains' . $datesQuery
                        ],
                        [
                            $socialServicedNL,
                            'представителей уязвимых групп получили услуги соцработника/фтизиатра',
                            'clients?project=6&&parts=364&parts=365&parts=368&parts=369&parts=373&vuln=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $socialServicedNLMale,
                            'мужчин',
                            'clients?project=6&parts=364&parts=365&parts=368&parts=369&parts=373&vuln=1&gender=male' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $socialServicedNLFemale,
                            'женщин',
                            'clients?project=6&parts=364&parts=365&parts=368&parts=369&parts=373&vuln=1&gender=female' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $socialServicedNLMigrants,
                            'мигрантов',
                            'clients?project=6&parts=364&parts=365&parts=368&parts=369&parts=373&vuln=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                            $datesQuery
                        ],
                        [
                            $socialServicedNLPrisoners,
                            'вышедших из мест исполнения наказаний',
                            'clients?project=6&parts=364&parts=365&parts=368&parts=369&parts=373&vuln=1' .
                            '&profileField3=6g6x&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                            $datesQuery
                        ],
                        [
                            $socialServicedNLDrugUsers,
                            'ПН',
                            'clients?project=6&parts=364&parts=365&parts=368&parts=369&parts=373&vuln=1' .
                            '&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' .
                            $datesQuery
                        ],
                    ],
                    [
                        [
                            $psychoServicesNL,
                            'услуг психолога оказано представителям уязвимых групп',
                            'services?projects=6&parts=367&parts=371&parts=375&parts=376&vuln=1' .
                            '&profileField=6g6x&profileValue=uQRP&profileValue=gG99' .
                                '&profileValue=2AJg&profileValue=YNoK&profileOp=contains' . $datesQuery
                        ],
                        [
                            $psychoServicedNL,
                            'представителей уязвимых групп получили услуги психолога',
                            'clients?project=6&parts=367&parts=371&parts=375&parts=376&vuln=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $psychoServicedNLMale,
                            'мужчин',
                            'clients?project=6&parts=367&parts=371&parts=375&parts=376&vuln=1&gender=male' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $psychoServicedNLFemale,
                            'женщин',
                            'clients?project=6&parts=367&parts=371&parts=375&parts=376&vuln=1&gender=female' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $psychoServicedNLMigrants,
                            'мигрантов',
                            'clients?project=6&parts=367&parts=371&parts=375&parts=376&vuln=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                            $datesQuery
                        ],
                        [
                            $psychoServicedNLPrisoners,
                            'вышедших из мест исполнения наказаний',
                            'clients?project=6&parts=367&parts=371&parts=375&parts=376&vuln=1' .
                            '&profileField3=6g6x&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                            $datesQuery
                        ],
                        [
                            $psychoServicedNLDrugUsers,
                            'ПН',
                            'clients?project=6&parts=367&parts=371&parts=375&parts=376&vuln=1' .
                            '&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' .
                            $datesQuery
                        ],
                    ],
                    [
                        [
                            $legalServicesNL,
                            'юридических услуг оказано представителям уязвимых групп',
                            'services?projects=6&parts=366&parts=370&parts=374&vuln=1' .
                            '&profileField=6g6x&profileValue=uQRP&profileValue=gG99' .
                                '&profileValue=2AJg&profileValue=YNoK&profileOp=contains' . $datesQuery
                        ],
                        [
                            $legalServicedNL,
                            'представителей уязвимых групп получили юридические услуги',
                            'clients?project=6&parts=366&parts=370&parts=374&vuln=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $legalServicedNLMale,
                            'мужчин',
                            'clients?project=6&parts=366&parts=370&parts=374&vuln=1&gender=male' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $legalServicedNLFemale,
                            'женщин',
                            'clients?project=6&parts=366&parts=370&parts=374&vuln=1&gender=female' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $legalServicedNLMigrants,
                            'мигрантов',
                            'clients?project=6&parts=366&parts=370&parts=374&vuln=1&' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                            $datesQuery
                        ],
                        [
                            $legalServicedNLPrisoners,
                            'вышедших из мест исполнения наказаний',
                            'clients?project=6&parts=366&parts=370&parts=374&vuln=1' .
                            '&profileField3=6g6x&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                            $datesQuery
                        ],
                        [
                            $legalServicedNLDrugUsers,
                            'ПН',
                            'clients?project=6&parts=366&parts=370&parts=374&vuln=1' .
                            '&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' .
                            $datesQuery
                        ],
                    ],
                    [
                        [
                            $socServicesNL,
                            'услуг соцработника оказано представителям уязвимых групп',
                            'services?projects=6&parts=365&parts=369&parts=373&parts=474' .
                            '&parts=364&parts=368&parts=372&positionex=тб' .
                            '&parts=363&parts=362&parts=361&parts=543&parts=377&parts=379&parts=528&parts=527'.
                            '&vuln=1&profileField=6g6x&profileValue=uQRP&profileValue=gG99' .
                                '&profileValue=2AJg&profileValue=YNoK&profileOp=contains' . $datesQuery
                        ],
                        [
                            $socServicedNL,
                            'представителей уязвимых групп получили услуги соцработника',
                            'clients?project=6&parts=365&parts=369&parts=373&parts=474' .
                            '&parts=364&parts=368&parts=372&positionex=тб' .
                            '&parts=363&parts=362&parts=361&parts=543&parts=377&parts=379&parts=528&parts=527' .
                            '&vuln=1&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $socServicedNLMale,
                            'мужчин',
                            'clients?project=6&parts=365&parts=369&parts=373&parts=474' .
                            '&parts=364&parts=368&parts=372&positionex=тб' .
                            '&parts=363&parts=362&parts=361&parts=543&parts=377&parts=379&parts=528&parts=527' .
                            '&vuln=1&gender=male&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $socServicedNLFemale,
                            'женщин',
                            'clients?project=6&parts=365&parts=369&parts=373&parts=474' .
                            '&parts=364&parts=368&parts=372&positionex=тб' .
                            '&parts=363&parts=362&parts=361&parts=543&parts=377&parts=379&parts=528&parts=527' .
                            '&vuln=1&gender=female&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $socServicedNLMigrants,
                            'мигрантов',
                            'clients?project=6&parts=365&parts=369&parts=373&parts=474' .
                            '&parts=364&parts=368&parts=372&positionex=тб' .
                            '&parts=363&parts=362&parts=361&parts=543&parts=377&parts=379&parts=528&parts=527' .
                            '&vuln=1&&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                            $datesQuery
                        ],
                        [
                            $socServicedNLPrisoners,
                            'вышедших из мест исполнения наказаний',
                            'clients?project=6&parts=365&parts=369&parts=373&parts=474' .
                            '&parts=364&parts=368&parts=372&positionex=тб' .
                            '&parts=363&parts=362&parts=361&parts=543&parts=377&parts=379&parts=528&parts=527' .
                            '&vuln=1&profileField3=6g6x&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                            $datesQuery
                        ],
                        [
                            $socServicedNLDrugUsers,
                            'ПН',
                            'clients?project=6&parts=365&parts=369&parts=373&parts=474' .
                            '&parts=364&parts=368&parts=372&positionex=тб' .
                            '&parts=363&parts=362&parts=361&parts=543&parts=377&parts=379&parts=528&parts=527' .
                            '&vuln=1&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' .
                            $datesQuery
                        ],
                    ],
                    [
                        [
                            $phthiServicesNL,
                            'услуг фтизиатра оказано представителям уязвимых групп',
                            'services?projects=6&parts=364&parts=368&parts=372&vuln=1' .
                            '&position=тб' .
                            '&profileField=6g6x&profileValue=uQRP&profileValue=gG99' .
                                '&profileValue=2AJg&profileValue=YNoK&profileOp=contains' . $datesQuery
                        ],
                        [
                            $phthiServicedNL,
                            'представителей уязвимых групп получили услуги фтизиатра',
                            'clients?project=6&parts=364&parts=368&parts=372&vuln=1' .
                            '&position=тб' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $phthiServicedNLMale,
                            'мужчин',
                            'clients?project=6&parts=364&parts=368&parts=372&vuln=1&gender=male' .
                            '&position=тб' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $phthiServicedNLFemale,
                            'женщин',
                            'clients?project=6&parts=364&parts=368&parts=372&vuln=1&gender=female' .
                            '&position=тб' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $phthiServicedNLMigrants,
                            'мигрантов',
                            'clients?project=6&parts=364&parts=368&parts=372&vuln=1&' .
                            '&position=тб' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                            $datesQuery
                        ],
                        [
                            $phthiServicedNLPrisoners,
                            'вышедших из мест исполнения наказаний',
                            'clients?project=6&parts=364&parts=368&parts=372&vuln=1' .
                            '&position=тб' .
                            '&profileField3=6g6x&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                            $datesQuery
                        ],
                        [
                            $phthiServicedNLDrugUsers,
                            'ПН',
                            'clients?project=6&parts=364&parts=368&parts=372&vuln=1' .
                            '&position=тб' .
                            '&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' .
                            $datesQuery
                        ],
                    ],
                    [
                        'title' => 'МЛУ',
                        'level' => 1
                    ],
                    [
                        [
                            $outcomesMDRNL,
                            'человек, получавших справки о приверженности',
                            'clients?project=6&outcomes=1&mdr=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $outcomesTreatedMDRNL,
                            'продолжают медикаментозное лечение ПТП',
                            'clients?project=6&outcomes=bJjG&outcomes=PnKg&mdr=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $outcomesRecoveredMDRNL,
                            'успешно завершили медикаментозное лечение ПТП',
                            'clients?project=6&outcomes=nXDJ&mdr=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $outcomesLostMDRNL,
                            'потеряны для последующего наблюдения',
                            'clients?project=6&outcomes=dAtv&mdr=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                    ],
                    [
                        [
                            $outcomesUnregisteredMDRNL,
                            'сняты с диспансерного учета',
                            'clients?project=6&outcomes=meHa&mdr=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $outcomesMovedMDRNL,
                            'уехали',
                            'clients?project=6&outcomes=meHa&reason=Lwch&mdr=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $outcomesDiedMDRNL,
                            'умерли',
                            'clients?project=6&outcomes=meHa&reason=BtcT&mdr=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $outcomesDiedTBMDRNL,
                            'умерли от ТБ',
                            'clients?project=6&outcomes=meHa&reason=BtcT&cause=MppH&mdr=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $outcomesJailedMDRNL,
                            'попали в места исполнения наказания',
                            'clients?project=6&outcomes=meHa&reason=KyEj&mdr=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $outcomesCuredMDRNL,
                            'излечены',
                            'clients?project=6&outcomes=meHa&reason=Ze52&mdr=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ]
                    ],
                    [
                        [
                            $socialServicesMDRNL,
                            'услуг соцработника оказано представителям уязвимых групп с МЛУ ТБ',
                            'services?projects=6&parts=364&parts=365&parts=368&parts=369&parts=373' .
                            '&vuln=1&mdr=1' .
                            '&profileField=6g6x&profileValue=uQRP&profileValue=gG99' .
                                '&profileValue=2AJg&profileValue=YNoK&profileOp=contains' . $datesQuery
                        ],
                        [
                            $socialServicedMDRNL,
                            'представителей уязвимых групп с МЛУ ТБ получили услуги соцработника',
                            'clients?project=6&&parts=364&parts=365&parts=368&parts=369&parts=373' .
                            '&vuln=1&mdr=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $socialServicedMDRNLMale,
                            'мужчин',
                            'clients?project=6&parts=364&parts=365&parts=368&parts=369&parts=373' .
                            '&vuln=1&mdr=1&gender=male' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $socialServicedMDRNLFemale,
                            'женщин',
                            'clients?project=6&parts=364&parts=365&parts=368&parts=369&parts=373' .
                            '&vuln=1&mdr=1&gender=female' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $socialServicedMDRNLMigrants,
                            'мигрантов',
                            'clients?project=6&parts=364&parts=365&parts=368&parts=369&parts=373' .
                            '&vuln=1&mdr=1&' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                            $datesQuery
                        ],
                        [
                            $socialServicedMDRNLPrisoners,
                            'вышедших из мест исполнения наказаний',
                            'clients?project=6&parts=364&parts=365&parts=368&parts=369&parts=373' .
                            '&vuln=1&mdr=1&' .
                            '&profileField3=6g6x&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                            $datesQuery
                        ],
                        [
                            $socialServicedMDRNLDrugUsers,
                            'ПН',
                            'clients?project=6&parts=364&parts=365&parts=368&parts=369&parts=373' .
                            '&vuln=1&mdr=1&' .
                            '&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' .
                            $datesQuery
                        ],
                    ],
                    [
                        [
                            $psychoServicesMDRNL,
                            'услуг психолога оказано представителям уязвимых групп с МЛУ ТБ',
                            'services?projects=6&parts=367&parts=371&parts=375&parts=376' .
                            '&vuln=1&mdr=1' .
                            '&profileField=6g6x&profileValue=uQRP&profileValue=gG99' .
                                '&profileValue=2AJg&profileValue=YNoK&profileOp=contains' . $datesQuery
                        ],
                        [
                            $psychoServicedMDRNL,
                            'представителей уязвимых групп с МЛУ ТБ получили услуги психолога',
                            'clients?project=6&parts=367&parts=371&parts=375&parts=376' .
                            '&vuln=1&mdr=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $psychoServicedMDRNLMale,
                            'мужчин',
                            'clients?project=6&parts=367&parts=371&parts=375&parts=376' .
                            '&vuln=1&mdr=1&gender=male' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $psychoServicedMDRNLFemale,
                            'женщин',
                            'clients?project=6&parts=367&parts=371&parts=375&parts=376' .
                            '&vuln=1&mdr=1&gender=female' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $psychoServicedMDRNLMigrants,
                            'мигрантов',
                            'clients?project=6&parts=367&parts=371&parts=375&parts=376' .
                            '&vuln=1&mdr=1&' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                            $datesQuery
                        ],
                        [
                            $psychoServicedMDRNLPrisoners,
                            'вышедших из мест исполнения наказаний',
                            'clients?project=6&parts=367&parts=371&parts=375&parts=376' .
                            '&vuln=1&mdr=1&' .
                            '&profileField3=6g6x&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                            $datesQuery
                        ],
                        [
                            $psychoServicedMDRNLDrugUsers,
                            'ПН',
                            'clients?project=6&parts=367&parts=371&parts=375&parts=376' .
                            '&vuln=1&mdr=1&' .
                            '&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' .
                            $datesQuery
                        ],
                    ],
                    [
                        [
                            $legalServicesMDRNL,
                            'юридических услуг оказано представителям уязвимых групп с МЛУ ТБ',
                            'services?projects=6&parts=366&parts=370' .
                            '&vuln=1&mdr=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $legalServicedMDRNL,
                            'представителей уязвимых групп с МЛУ ТБ получили юридические услуги',
                            'clients?project=6&parts=366&parts=370' .
                            '&vuln=1&mdr=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $legalServicedMDRNLMale,
                            'мужчин',
                            'clients?project=6&parts=366&parts=370' .
                            '&vuln=1&mdr=1&gender=male' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $legalServicedMDRNLFemale,
                            'женщин',
                            'clients?project=6&parts=366&parts=370' .
                            '&vuln=1&mdr=1&gender=female' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $legalServicedMDRNLMigrants,
                            'мигрантов',
                            'clients?project=6&parts=366&parts=370' .
                            '&vuln=1&mdr=1&' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                            $datesQuery
                        ],
                        [
                            $legalServicedMDRNLPrisoners,
                            'вышедших из мест исполнения наказаний',
                            'clients?project=6&parts=366&parts=370' .
                            '&vuln=1&mdr=1&' .
                            '&profileField3=6g6x&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                            $datesQuery
                        ],
                        [
                            $legalServicedMDRNLDrugUsers,
                            'ПН',
                            'clients?project=6&parts=366&parts=370' .
                            '&vuln=1&mdr=1&' .
                            '&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' .
                            $datesQuery
                        ],
                    ],
                    [
                        [
                            $overallStartedMDRNL,
                            'больных МЛУ туберкулезом',
                            'clients?project=6&profileField=kB3w&profileOp=notNull&mdr=1&remained=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                            null,
                            route('downloadMDR') . '?nl&' . $datesQuery
                        ],
                        [
                            $overallRemainedMDRNL,
                            'больных МЛУ туберкулезом, продолжающих либо окончивших лечение',
                            'clients?project=6&profileField=kB3w&profileOp=notNull&mdr=1' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' .
                            '&remained=2' . $datesQuery
                        ]
                    ],
                    [
                        'title' => 'Выявленные случаи ТБ',
                        'level' => 0
                    ],
                    [
                        [
                            $detectedNL,
                            'случаев туберкулеза выявлено',
                            'clients?project=6&examinedAfter=2020-09-01&examined=nonNegative&dateFilter=examined' .
                                '&examinedFrom=us&userParts=361' .
                                '&profileField=2CLW&profileValue=wx6s&profileOp=neq' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $detectedMaleNL,
                            'среди мужчин',
                            'clients?project=6&examinedAfter=2020-09-01&examined=nonNegative&dateFilter=examined' .
                                '&examinedFrom=us&userParts=361&gender=male' .
                                '&profileField=2CLW&profileValue=wx6s&profileOp=neq' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $detectedFemaleNL,
                            'среди женщин',
                            'clients?project=6&examinedAfter=2020-09-01&examined=nonNegative&dateFilter=examined' .
                                '&examinedFrom=us&userParts=361&gender=female' .
                                '&profileField=2CLW&profileValue=wx6s&profileOp=neq' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $redundantDetectedNL,
                            'клиентов, относящихся к более, чем одной категории',
                            'clients?project=6&examinedAfter=2020-09-01&examined=nonNegative&dateFilter=examined' .
                                '&examinedFrom=us&userParts=361&redundant=2' .
                                '&profileField=2CLW&profileValue=wx6s&profileOp=neq' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                    ],
                    [
                        [
                            $detectedMigrants,
                            'среди мигрантов',
                            'clients?project=6&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK' .
                                '&profileOp3=contains&examinedAfter=2020-09-01' .
                                '&examined=nonNegative&dateFilter=examined&examinedFrom=us&userParts=361' .
                                '&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                            $detectedMigrantsNew
                        ],
                        [
                            $detectedDrugUsers,
                            'среди ПН',
                            'clients?project=6&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' .
                                '&examinedAfter=2020-09-01&examined=nonNegative&dateFilter=examined&examinedFrom=us' .
                                '&userParts=361&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                            $detectedDrugUsersNew
                        ],
                        [
                            $detectedPrisoners,
                            'среди людей, вышедших из мест исполнения наказаний',
                            'clients?project=6&profileField3=6g6x' .
                                '&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                                '&examinedAfter=2020-09-01&examined=nonNegative&dateFilter=examined&examinedFrom=us' .
                                '&userParts=361&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                            $detectedPrisonersNew
                        ]
                    ],
                    [
                        'title' => 'Дети',
                        'level' => 0
                    ],
                    [
                        [
                            $childrenFacility1,
                            'аутрич',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains&parts=' .
                                '&profileField2=6uM3&profileValue2=6Dr8' . $datesQuery
                        ],
                        [
                            $childrenFacility2,
                            'ТГЦФиП',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains&parts=' .
                                '&profileField2=6uM3&profileValue2=wpBE' . $datesQuery
                        ],
                        [
                            $childrenFacility3,
                            'МФД-1',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains&parts=' .
                                '&profileField2=6uM3&profileValue2=ZqZj' . $datesQuery
                        ],
                        [
                            $childrenFacility4,
                            'МФД-2',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains&parts=' .
                                '&profileField2=6uM3&profileValue2=X5Zx' . $datesQuery
                        ],
                        [
                            $childrenFacility5,
                            'МФД-3',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains&parts=' .
                                '&profileField2=6uM3&profileValue2=ebBT' . $datesQuery
                        ],
                        [
                            $childrenFacility6,
                            'МФД-4',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains&parts=' .
                                '&profileField2=6uM3&profileValue2=DqoT' . $datesQuery
                        ],
                        [
                            $childrenFacility7,
                            'МФД-5',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains&parts=' .
                                '&profileField2=6uM3&profileValue2=qPcj' . $datesQuery
                        ],
                        [
                            $childrenFacility8,
                            'РСНПЦФиП',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains&parts=' .
                                '&profileField2=6uM3&profileValue2=mjSk' . $datesQuery
                        ],
                        [
                            $childrenFacility9,
                            'ДГФБ',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains&parts=' .
                                '&profileField2=6uM3&profileValue2=wofi' . $datesQuery
                        ],
                        [
                            $childrenFacility10,
                            'ГКБФиП',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains&parts=' .
                                '&profileField2=6uM3&profileValue2=J6h6' . $datesQuery
                        ],
                        [
                            $childrenNoFacility,
                            'ГКБФиП',
                            'clients?project=6&profileField3=6g6x&profileValue3=iNHa&profileOp3=contains&parts=' .
                                '&profileField=6uM3&profileOp=null' . $datesQuery
                        ]
                    ],
                    [
                        'title' => 'ВСЛ',
                        'level' => 0
                    ],
                    [
                        [
                            $vstHasReference,
                            'получали ВСЛ',
                            'clients?project=6&adherence=52&hasreference=52' . $datesQuery
                        ],
                        [
                            $vstHasReferenceMale,
                            'мужчин',
                            'clients?project=6&adherence=52&hasreference=52&gender=male' . $datesQuery
                        ],
                        [
                            $vstHasReferenceFemale,
                            'женщин',
                            'clients?project=6&adherence=52&hasreference=52&gender=female' . $datesQuery
                        ],
                        [
                            $vstHasReferenceMigrants,
                            'мигрантов',
                            'clients?project=6&adherence=52&hasreference=52' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                    ],
                    [
                        [
                            $vstHasReferencePrisoners,
                            'освободившихся из мест исполнения наказаний',
                            'clients?project=6&adherence=52&hasreference=52' .
                            '&profileField3=6g6x&profileValue3=gG99&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $vstHasReferenceDrugUsers,
                            'ПН',
                            'clients?project=6&adherence=52&hasreference=52' .
                            '&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $vstHasReferenceParents,
                            'родителей',
                            'clients?project=6&adherence=52&hasreference=52' .
                            '&profileField3=6g6x&profileValue3=aSu3&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $vstHasReferenceGeneralPopulation ?: '-',
                            'общее население',
                            $vstHasReferenceGeneralPopulation ?
                                'clients?project=6&adherence=52&hasreference=52' .
                                '&profileField3=6g6x&profileValue3=bEk7&profileOp3=contains' . $datesQuery
                                : null
                        ],
                        [
                            $vstHasReferenceMDR,
                            'с МЛУ ТБ',
                            'clients?project=6&adherence=52&hasreference=52&mdr=1' . $datesQuery
                        ]
                    ],
                    [
                        [
                            $vstVideoViews ?: '-',
                            'видео просмотрено',
                            'services?project=6&parts=731' . $datesQuery
                        ],
                        [
                            $vstVideoViewedClients ?: '-',
                            'клиентов с просмотренными видео',
                            'clients?project=6&parts=731' . $datesQuery
                        ],
                    ],
                    [
                        'title' => 'Приняты',
                        'level' => 1
                    ],
                    [
                        [
                            $vstStarted,
                            'клиентов принято на ВСЛ',
                            'clients?project=6&profileDate=53.CGPJ' .
                            '&profileField=53.ui2f&profileOp=true&profileField2=53.CGPJ&profileOp2=notNull' . $datesQuery,
                        ],
                        [
                            $vstStartedMale,
                            'мужчин',
                            'clients?project=6&profileDate=53.CGPJ&gender=male' .
                            '&profileField=53.ui2f&profileOp=true&profileField2=53.CGPJ&profileOp2=notNull' . $datesQuery,
                        ],
                        [
                            $vstStartedFemale,
                            'женщин',
                            'clients?project=6&profileDate=53.CGPJ&gender=female' .
                            '&profileField=53.ui2f&profileOp=true&profileField2=53.CGPJ&profileOp2=notNull' . $datesQuery,
                        ],
                        [
                            $vstStartedMigrants,
                            'мигрантов',
                            'clients?project=6&profileDate=53.CGPJ' .
                            '&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                            '&profileField=53.ui2f&profileOp=true&profileField2=53.CGPJ&profileOp2=notNull' . $datesQuery,
                        ],
                    ],
                    [
                        [
                            $vstStartedPrisoners,
                            'освободившихся из мест исполнения наказаний',
                            'clients?project=6&profileDate=53.CGPJ' .
                            '&profileField3=6g6x&profileValue3=gG99&profileOp3=contains' .
                            '&profileField=53.ui2f&profileOp=true&profileField2=53.CGPJ&profileOp2=notNull' . $datesQuery,
                        ],
                        [
                            $vstStartedDrugUsers,
                            'ПН',
                            'clients?project=6&profileDate=53.CGPJ' .
                            '&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' .
                            '&profileField=53.ui2f&profileOp=true&profileField2=53.CGPJ&profileOp2=notNull' . $datesQuery,
                        ],
                        [
                            $vstStartedParents,
                            'родителей',
                            'clients?project=6&profileDate=53.CGPJ' .
                            '&profileField3=6g6x&profileValue3=aSu3&profileOp3=contains' .
                            '&profileField=53.ui2f&profileOp=true&profileField2=53.CGPJ&profileOp2=notNull' . $datesQuery,
                        ],
                        [
                            $vstStartedGeneralPopulation ?: '-',
                            'общее население',
                            $vstStartedGeneralPopulation ?
                                'clients?project=6&profileDate=53.CGPJ' .
                                '&profileField3=6g6x&profileValue3=bEk7&profileOp3=contains' .
                                '&profileField=53.ui2f&profileOp=true&profileField2=53.CGPJ&profileOp2=notNull' . $datesQuery
                                : null
                        ],
                    ],
                    [
                        [
                            $vstStartedMDR,
                            'с МЛУ ТБ',
                            'clients?project=6&profileDate=53.CGPJ&mdr=1' .
                            '&profileField=53.ui2f&profileOp=true&profileField2=53.CGPJ&profileOp2=notNull' . $datesQuery,
                        ],
                        [
                            $vstStartedAmbulatory,
                            'на амбулаторном лечении',
                            'clients?project=6&profileDate=53.CGPJ' .
                            '&profileField3=53.RMCN&profileValue3=3yph' .
                            '&profileField=53.ui2f&profileOp=true&profileField2=53.CGPJ&profileOp2=notNull' . $datesQuery,
                        ],
                        [
                            $vstStartedInHospital,
                            'на стационарном лечении',
                            'clients?project=6&profileDate=53.CGPJ' .
                            '&profileField3=53.RMCN&profileValue3=oaMX' .
                            '&profileField=53.ui2f&profileOp=true&profileField2=53.CGPJ&profileOp2=notNull' . $datesQuery,
                        ]
                    ],
                    [
                        'title' => 'Исходы',
                        'level' => 1
                    ],
                    [
                        [
                            $vstHasReference ? round(($vstNotStopped + $vstStoppedFinished + $vstStoppedGoneDCT) /
                                $vstHasReference * 1000) / 10 . '%' : 0,
                            'клиентов успешно завершили либо продолжают ВСЛ'
                        ],
                        [
                            $vstNotStopped ?: '-',
                            'продолжают получать ВСЛ',
                            'clients?project=6&profileDate=52.TNF5&adherence=52' .
                            '&profileField3=53.ui2f&profileOp3=true' .
                            '&profileField2=52.JbaL&profileValue2=PnKg' . $datesQuery,
                        ],
                        [
                            $vstStoppedFinished ?: '-',
                            'успешно завершили медикаментозное лечение',
                            'clients?project=6&profileDate=52.TNF5&adherence=52' .
                            '&profileField2=52.JbaL&profileValue2=A4Z9' .
                            '&profileField3=53.ui2f&profileOp3=true' .
                            '&profileField=52.jBQK&profileOp=true' . $datesQuery
                        ],
                    ],
                    [
                        [
                            $vstStopped ?: '-',
                            'прекратили получать ВСЛ',
                            'clients?project=6&profileDate=52.TNF5&adherence=52' .
                            '&profileField3=53.ui2f&profileOp3=true' .
                            '&profileField2=52.JbaL&profileValue2=A4Z9' . $datesQuery,
                        ],
                        [
                            $vstStoppedExcluded ?: '-',
                            'исключены из программы',
                            'clients?project=6&profileDate=52.TNF5&adherence=52' .
                            '&profileField2=52.JbaL&profileValue2=A4Z9' .
                            '&profileField3=53.ui2f&profileOp3=true' .
                            '&profileField=52.d4Xj&profileOp=true' . $datesQuery
                        ],
                        [
                            $vstStoppedLost ?: '-',
                            'не отправляли видеозаписи и не отвечали на телефонные звонки ',
                            'clients?project=6&profileDate=52.TNF5&adherence=52' .
                            '&profileField2=52.JbaL&profileValue2=A4Z9' .
                            '&profileField3=53.ui2f&profileOp3=true' .
                            '&profileField=52.BPcC&profileOp=true' . $datesQuery
                        ],
                        [
                            $vstStoppedDied ?: '-',
                            'умерли',
                            'clients?project=6&profileDate=52.TNF5&adherence=52' .
                            '&profileField2=52.JbaL&profileValue2=A4Z9' .
                            '&profileField3=53.ui2f&profileOp3=true' .
                            '&profileField=52.7bx5&profileOp=true' . $datesQuery
                        ],
                        [
                            $vstStoppedGoneDCT ?: '-',
                            'переведены на НКЛ',
                            'clients?project=6&profileDate=52.TNF5&adherence=52' .
                            '&profileField2=52.JbaL&profileValue2=A4Z9' .
                            '&profileField3=53.ui2f&profileOp3=true' .
                            '&profileField=52.F4fi&profileOp=true' . $datesQuery
                        ],
                    ],
                    [
                        [
                            $vstStoppedImprisoned ?: '-',
                            'помещены под стражу',
                            'clients?project=6&profileDate=52.TNF5&adherence=52' .
                            '&profileField2=52.JbaL&profileValue2=A4Z9' .
                            '&profileField3=53.ui2f&profileOp3=true' .
                            '&profileField=52.5upu&profileOp=true' . $datesQuery
                        ],
                        [
                            $vstStoppedSuspended ?: '-',
                            'временно не участвовали в ВСЛ',
                            'clients?project=6&profileDate=52.TNF5&adherence=52' .
                            '&profileField2=52.JbaL&profileValue2=A4Z9' .
                            '&profileField3=53.ui2f&profileOp3=true' .
                            '&profileField=52.nWKs&profileOp=true' . $datesQuery
                        ],
                        [
                            $vstStoppedSuspendedHospitalized ?: '-',
                            'госпитализированы',
                            'clients?project=6&profileDate=52.TNF5&adherence=52' .
                            '&profileField2=52.JbaL&profileValue2=A4Z9' .
                            '&profileField3=53.ui2f&profileOp3=true' .
                            '&profileField=52.nWKs&profileOp=true&profileField3=52.8Rqv&profileOp3=true' . $datesQuery
                        ],
                    ],
                    [
                        [
                            $vstStoppedSuspendedLostDevice ?: '-',
                            'имели проблемы со смартфоном',
                            'clients?project=6&profileDate=52.TNF5&adherence=52' .
                            '&profileField2=52.JbaL&profileValue2=A4Z9' .
                            '&profileField3=53.ui2f&profileOp3=true' .
                            '&profileField=52.nWKs&profileOp=true&profileField3=52.FxEX&profileOp3=true' . $datesQuery
                        ],
                        [
                            $vstStoppedSuspendedGoneAbroad ?: '-',
                            'выехали за границу',
                            'clients?project=6&profileDate=52.TNF5&adherence=52' .
                            '&profileField2=52.JbaL&profileValue2=A4Z9' .
                            '&profileField3=53.ui2f&profileOp3=true' .
                            '&profileField=52.nWKs&profileOp=true&profileField3=52.hAKs&profileOp3=true' . $datesQuery
                        ],
                        [
                            $vstStoppedSuspendedTechnical ?: '-',
                            'в связи с техническими неисправностями',
                            'clients?project=6&profileDate=52.TNF5&adherence=52' .
                            '&profileField2=52.JbaL&profileValue2=A4Z9' .
                            '&profileField3=53.ui2f&profileOp3=true' .
                            '&profileField=52.nWKs&profileOp=true&profileField3=52.3EcR&profileOp3=true' . $datesQuery
                        ]
                    ]
                ]
            ], [
                'title' => 'ETBU',
                'items' => [
                    [
                        'title' => 'GOAL: To increase access to TB diagnostics and treatment for vulnerable groups ' .
                                'in Tashkent city during 3 years.',
                        'level' => 0
                    ],
                    [
                        [
                            $overallStartedMDR ? round($overallRemainedMDR / $overallStartedMDR * 1000) / 10 . '%' : 0,
                            'of VPs diagnosed with MDR-TB who have initiated and remained on second-line treatment',
                        ],
                    ],
                    [
                        'title' => 'Number of vulnerable persons benefiting ' .
                                'from USG-supported social services (ES.4-1)',
                        'level' => 1
                    ],
                    [
                        [
                            $children + $parents + $screened,
                            'children affected by TB received any psychological and social support ' .
                                'through Program services (ES.4-1c: Age 0-17), parents of children affected by TB ' .
                                'received support through Program services (ES.4-1), VPs who started TB treatment ' .
                                'and received adherence support through Program services or screened for TB ' .
                                'through Program outreach',
                        ],
                        [
                            $boys + $parentMen + $screenedMale,
                            'male',
                        ],
                        [
                            $girls + $parentWomen + $screenedFemale,
                            'female',
                        ]
                    ],
                    [
                        'title' => 'Objective 1: To ensure access to comprehensive and appropriate TB diagnostics ' .
                                'and treatment services for 500 representatives of vulnerable groups - ex-prisoners, ' .
                                'migrants and drug users.',
                        'level' => 1
                    ],
                    [
                        'title' => 'Sub-Objective 1 A: To ensure access to early TB diagnostics, ' .
                                'including drug susceptibility testing, for vulnerable populations ' .
                                'through Program outreach and referral.',
                        'level' => 2
                    ],
                    [
                        [
                            $detectedMDR,
                            'MDR-TB cases detected among VPs through Program referral (HL.2.4.-1)',
                            'clients?project=6&examinedAfter=2020-09-01&examined=nonNegative' .
                                '&dateFilter=examined&examinedFrom=us&userParts=361&mdr=1' .
                                '&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                            $detectedMDRNew
                        ],
                        [
                            $detectedMDRMale,
                            'male',
                            'clients?project=6&examinedAfter=2020-09-01&examined=nonNegative' .
                                '&dateFilter=examined&examinedFrom=us&userParts=361&mdr=1&gender=male' .
                                '&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                            $detectedMDRMaleNew
                        ],
                        [
                            $detectedMDRFemale,
                            'female',
                            'clients?project=6&examinedAfter=2020-09-01&examined=nonNegative' .
                                '&dateFilter=examined&examinedFrom=us&userParts=361&mdr=1&gender=female' .
                                '&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                            $detectedMDRFemaleNew
                        ],
                        [
                            $detectedMDRPrisoners,
                            'ex-prisoners',
                            'clients?project=6&examinedAfter=2020-09-01&examined=nonNegative' .
                                '&dateFilter=examined&examinedFrom=us&userParts=361&mdr=1' .
                                '&profileField3=6g6x&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                                '&profileField=2CLW&profileValue=wx6s&profileOp=neq' .
                                $datesQuery,
                            $detectedMDRPrisonersNew
                        ],
                        [
                            $detectedMDRMigrants,
                            'migrants',
                            'clients?project=6&examinedAfter=2020-09-01&examined=nonNegative' .
                                '&dateFilter=examined&examinedFrom=us&userParts=361&mdr=1' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                                '&profileField=2CLW&profileValue=wx6s&profileOp=neq' .
                                $datesQuery,
                            $detectedMDRMigrantsNew
                        ],
                        [
                            $detectedMDRDrugUsers,
                            'drug users',
                            'clients?project=6&examinedAfter=2020-09-01&examined=nonNegative' .
                                '&dateFilter=examined&examinedFrom=us&userParts=361&mdr=1' .
                                '&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' .
                                '&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                            $detectedMDRDrugUsersNew
                        ],
                        [
                            $detectedMDRDifficult,
                            'limited access',
                            'clients?project=6&examinedAfter=2020-09-01&examined=nonNegative' .
                                '&dateFilter=examined&examinedFrom=us&userParts=361&mdr=1' .
                                '&profileField3=6g6x&profileValue3=dLBE&profileValue3=MuuE&profileOp3=contains' .
                                '&profileField=2CLW&profileValue=wx6s&profileOp=neq' .
                                $datesQuery,
                            $detectedMDRDifficultNew
                        ],
                        [
                            $detectedMDRChildren,
                            'children',
                            'clients?project=6&examinedAfter=2020-09-01&examined=nonNegative' .
                                '&dateFilter=examined&examinedFrom=us&userParts=361&mdr=1' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=contains' .
                                '&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                            $detectedMDRChildrenNew
                        ],
                        [
                            $detectedMDRParents,
                            'parents',
                            'clients?project=6&examinedAfter=2020-09-01&examined=nonNegative' .
                                '&dateFilter=examined&examinedFrom=us&userParts=361&mdr=1' .
                                '&profileField3=6g6x&profileValue3=aSu3&profileOp3=contains' .
                                '&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                            $detectedMDRParentsNew
                        ]
                    ],
                    [
                        [
                            $examined,
                            'VPs tested for TB through Program referral',
                            'clients?project=6&examinedAfter=2020-09-01&dateFilter=examined&examinedFrom=us' .
                                '&vuln=3' . $datesQuery,
                            $examinedNew
                        ],
                        [
                            $examinedMale,
                            'male',
                            'clients?project=6&examinedAfter=2020-09-01&dateFilter=examined&examinedFrom=us' .
                                '&vuln=3&gender=male' . $datesQuery,
                            $examinedMaleNew
                        ],
                        [
                            $examinedFemale,
                            'female',
                            'clients?project=6&examinedAfter=2020-09-01&dateFilter=examined&examinedFrom=us' .
                                '&vuln=3&gender=female' . $datesQuery,
                            $examinedFemaleNew
                        ],
                        [
                            $examinedPrisoners,
                            'ex-prisoners',
                            'clients?project=6&examinedAfter=2020-09-01&dateFilter=examined&examinedFrom=us' .
                                '&vuln=3&profileField3=6g6x&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                                $datesQuery,
                            $examinedPrisonersNew
                        ],
                        [
                            $examinedMigrants,
                            'migrants',
                            'clients?project=6&examinedAfter=2020-09-01&dateFilter=examined&examinedFrom=us' .
                                '&vuln=3&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                                $datesQuery,
                            $examinedMigrantsNew
                        ],
                        [
                            $examinedDrugUsers,
                            'drug users',
                            'clients?project=6&examinedAfter=2020-09-01&dateFilter=examined&examinedFrom=us' .
                                '&vuln=3&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' . $datesQuery,
                            $examinedDrugUsersNew
                        ],
                        [
                            $examinedDifficult,
                            'limited access',
                            'clients?project=6&examinedAfter=2020-09-01&dateFilter=examined&examinedFrom=us' .
                                '&vuln=3&profileField3=6g6x&profileValue3=dLBE&profileValue3=MuuE&profileOp3=contains' .
                                $datesQuery,
                            $examinedDifficultNew
                        ]
                    ],
                    [
                        [
                            $screened,
                            'VPs screened for TB through Program outreach',
                            'clients?project=6&searchActivities=скрининг&vuln=3' . $datesQuery
                        ],
                        [
                            $screenedMale,
                            'male',
                            'clients?project=6&searchActivities=скрининг&vuln=3&gender=male' . $datesQuery,
                        ],
                        [
                            $screenedFemale,
                            'female',
                            'clients?project=6&searchActivities=скрининг&vuln=3&gender=female' . $datesQuery,
                        ],
                        [
                            $screenedPrisoner,
                            'ex-prisoners',
                            'clients?project=6&searchActivities=скрининг&vuln=3' .
                                '&profileField3=6g6x&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                                $datesQuery,
                        ],
                        [
                            $screenedMigrant,
                            'migrants',
                            'clients?project=6&searchActivities=скрининг&vuln=3' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                                $datesQuery,
                        ],
                        [
                            $screenedDrugUser,
                            'drug users',
                            'clients?project=6&searchActivities=скрининг&vuln=3' .
                                '&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $screenedLimited,
                            'limited access',
                            'clients?project=6&searchActivities=скрининг&vuln=3' .
                                '&profileField3=6g6x&profileValue3=dLBE&profileValue3=MuuE&profileOp3=contains' .
                                $datesQuery,
                        ]
                    ],
                    [
                        'title' => 'Sub-Objective 1B: To ensure access to second-line treatment ' .
                                'and adherence support services for vulnerable populations with high risk ' .
                                'of lost-to-follow up though a multidisciplinary team approach.',
                        'level' => 2
                    ],
                    [
                        [
                            $startedMDR,
                            'MDR-TB cases notified among VPs through Program referral ' .
                                'that have initiated second-line treatment (HL.2.4.-2)',
                            'clients?project=6&profileField=Q5xs&profileOp=notNull&profileDate=Q5xs&started=1' .
                                '&mdr=1' . $datesQuery,
                            $startedMDRNew
                        ],
                        [
                            $startedMDRMale,
                            'male',
                            'clients?project=6&profileField=Q5xs&profileOp=notNull&profileDate=Q5xs&started=1' .
                                '&mdr=1&gender=male' . $datesQuery,
                            $startedMDRMaleNew
                        ],
                        [
                            $startedMDRFemale,
                            'female',
                            'clients?project=6&profileField=Q5xs&profileOp=notNull&profileDate=Q5xs&started=1' .
                                '&mdr=1&gender=female' . $datesQuery,
                            $startedMDRFemaleNew
                        ],
                        [
                            $startedMDRPrisoners,
                            'ex-prisoners',
                            'clients?project=6&profileField=Q5xs&profileOp=notNull&profileDate=Q5xs&started=1' .
                                '&mdr=1&profileField3=6g6x&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                                $datesQuery,
                            $startedMDRPrisonersNew
                        ],
                        [
                            $startedMDRMigrants,
                            'migrants',
                            'clients?project=6&profileField=Q5xs&profileOp=notNull&profileDate=Q5xs&started=1&mdr=1' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                                $datesQuery,
                            $startedMDRMigrantsNew
                        ],
                        [
                            $startedMDRDrugUsers,
                            'drug users',
                            'clients?project=6&profileField=Q5xs&profileOp=notNull&profileDate=Q5xs&started=1&mdr=1' .
                                '&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' . $datesQuery,
                            $startedMDRDrugUsersNew
                        ],
                        [
                            $startedMDRLimited,
                            'limited access',
                            'clients?project=6&profileField=Q5xs&profileOp=notNull&profileDate=Q5xs&started=1&mdr=1' .
                                '&profileField3=6g6x&profileValue3=dLBE&profileValue3=MuuE&profileOp3=contains' .
                                $datesQuery,
                            $startedMDRLimitedNew
                        ]
                    ],
                    [
                        [
                            $supported,
                            'VPs who started TB treatment and received adherence support through Program services ' .
                                '(incl. those who started second-line treatment (ES.4-1) & ' .
                                'those who referred by the TB services)',
                            'clients?project=6&vuln=1&parts=' . $datesQuery,
                        ],
                        [
                            $supportedDetected,
                            'patients from vulnerable groups diagnosed with TB through the Outreach Program ' .
                                '(screening, referral, and diagnostics) plus TB patients who were found ' .
                                'and brought back to the treatment Program thanks to the Program efforts',
                            'clients?project=6&vuln=1&supported=2&parts=&profileDate=Q5xs' . $datesQuery,
                        ],
                        [
                            $supportedReferred,
                            'patients from vulnerable groups already diagnosed with TB who were referred to ' .
                            'the Program to prevent loss for observation during treatment',
                            'clients?project=6&vuln=1&supported=3&parts=' . $datesQuery,
                        ],
                    ],
                    [
                        [
                            $supportedMale,
                            'male',
                            'clients?project=6&vuln=1&parts=&gender=male' . $datesQuery,
                        ],
                        [
                            $supportedFemale,
                            'female',
                            'clients?project=6&vuln=1&parts=&gender=female' . $datesQuery,
                        ],
                        [
                            $supportedPrisoners,
                            'ex-prisoners',
                            'clients?project=6&vuln=1&parts=&' .
                                'profileField3=6g6x&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                                $datesQuery,
                        ],
                        [
                            $supportedMigrants,
                            'migrants',
                            'clients?project=6&vuln=1&parts=&' .
                                'profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                                $datesQuery,
                        ],
                        [
                            $supportedDrugUsers,
                            'drug users',
                            'clients?project=6&vuln=1&parts=' .
                                '&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' .
                                $datesQuery,
                        ],
                        [
                            $supportedLimited,
                            'limited access',
                            'clients?project=6&vuln=1&parts=' .
                                '&profileField3=6g6x&profileValue3=dLBE&profileValue3=MuuE&profileOp3=contains' .
                                $datesQuery,
                        ]
                    ],
                    [
                        'title' => 'Objective 2: To provide 200 children receiving TB treatment (TBT) ' .
                            'in Tashkent city and their parents with the services of psychological support and ' .
                            'social assistance.',
                        'level' => 1
                    ],
                    [
                        [
                            $fullChildren2,
                            'children affected by TB who received a minimum package of social and ' .
                            'psychological support during the course of treatment',
                            'clients?project=6&fullch=2' . $datesQuery,
                        ],
                        [
                            $fullChildren2Boys,
                            'male',
                            'clients?project=6&fullch=2&gender=male' . $datesQuery,
                        ],
                        [
                            $fullChildren2Girls,
                            'female',
                            'clients?project=6&fullch=2&gender=female' . $datesQuery,
                        ],
                    ],
                    [
                        [
                            $children,
                            'children affected by TB received any psychological and social support ' .
                                'through Program services (ES.4-1c: Age 0-17)',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains&parts=' .
                                $datesQuery,
                            $childrenNew
                        ],
                        [
                            $boys,
                            'male',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains' .
                                '&gender=male&parts=' . $datesQuery,
                            $boysNew
                        ],
                        [
                            $girls,
                            'female',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains' .
                                '&gender=female&parts=' . $datesQuery,
                            $girlsNew
                        ],
                    ],
                    [
                        [
                            $parents,
                            'parents of children affected by TB received support through Program services (ES.4-1)',
                            'clients?project=6&profileField=6g6x&profileValue=aSu3&profileOp=contains&parts=' .
                            $datesQuery,
                            $parentsNew
                        ],
                        [
                            $parentMen,
                            'male',
                            'clients?project=6&profileField=6g6x&profileValue=aSu3&profileOp=contains&gender=male' .
                                '&parts=' . $datesQuery,
                            $parentMenNew
                        ],
                        [
                            $parentWomen,
                            'female',
                            'clients?project=6&profileField=6g6x&profileValue=aSu3&profileOp=contains&gender=female' .
                                '&parts=' . $datesQuery,
                            $parentWomenNew
                        ]
                    ],
                    [
                        'title' => 'Objective 3: To enable further development and coordination of the national ' .
                                'TB partnership network representatives.',
                        'level' => 1
                    ],
                    [
                        [
                            $informed,
                            'service providers informed by Program who serve VPs',
                            'clients?project=6&parts=544&parts=545' .
                                '&profileField3=Y4N4&profileValue3=26bK&profileValue3=enqF&profileOp3=otherthan' .
                                '&partner=1' . $datesQuery,
                            $informedNew
                        ],
                        [
                            $informedMahalla,
                            'mahalla employees',
                            'clients?project=6&profileField=Y4N4&profileValue=kyLa&parts=544&parts=545' .
                                '&partner=1' . $datesQuery,
                            $informedMahallaNew
                        ],
                        [
                            $informedMahallaMale,
                            'male',
                            'clients?project=6&profileField=Y4N4&profileValue=kyLa&parts=544&parts=545' .
                                '&partner=1&gender=male' . $datesQuery,
                            $informedMahallaMaleNew
                        ],
                        [
                            $informedMahallaFemale,
                            'female',
                            'clients?project=6&profileField=Y4N4&profileValue=kyLa&parts=544&parts=545' .
                                '&partner=1&gender=male' . $datesQuery,
                            $informedMahallaFemaleNew
                        ]
                    ],
                    [
                        'title' => 'Objective 4: To provide informational and educational support ' .
                                'for the project realization.',
                        'level' => 1
                    ],
                    [
                        [
                            'n/a',
                            'IEC materials distributed'
                        ],
                    ],
                    [
                        [
                            $trained,
                            'service providers trained by Program who serve VPs (ES.4-2)',
                            'clients?project=6&profileField=Y4N4&profileOp=notNull&parts=546' . $datesQuery,
                            $trainedNew
                        ],
                        [
                            $trainedMen,
                            'male',
                            'clients?project=6&profileField=Y4N4&profileOp=notNull&parts=546&gender=male' . $datesQuery,
                            $trainedMenNew
                        ],
                        [
                            $trainedWomen,
                            'female',
                            'clients?project=6&profileField=Y4N4&profileOp=notNull&parts=546' .
                                '&gender=female' . $datesQuery,
                            $trainedWomenNew
                        ],
                    ]
                ]
            ], [
                'title' => 'ETBU\'',
                'items' => [
                    [
                        'title' => 'GOAL: To increase access to TB diagnostics and treatment for vulnerable groups ' .
                                'in Tashkent city during 3 years.',
                        'level' => 0
                    ],
                    [
                        [
                            $overallStartedMDRNL
                                ? round($overallRemainedMDRNL / $overallStartedMDRNL * 1000) / 10 . '%'
                                : 0,
                            'of VPs diagnosed with MDR-TB who have initiated and remained on second-line treatment',
                        ],
                    ],
                    [
                        'title' => 'Number of vulnerable persons benefiting ' .
                                'from USG-supported social services (ES.4-1)',
                        'level' => 1
                    ],
                    [
                        [
                            $children + $parents + $screenedNL,
                            'children affected by TB received any psychological and social support ' .
                                'through Program services (ES.4-1c: Age 0-17), parents of children affected by TB ' .
                                'received support through Program services (ES.4-1), VPs who started TB treatment ' .
                                'and received adherence support through Program services or screened for TB ' .
                                'through Program outreach',
                        ],
                        [
                            $boys + $parentMen + $screenedMaleNL,
                            'male',
                        ],
                        [
                            $girls + $parentWomen + $screenedFemaleNL,
                            'female',
                        ]
                    ],
                    [
                        'title' => 'Objective 1: To ensure access to comprehensive and appropriate TB diagnostics ' .
                                'and treatment services for 500 representatives of vulnerable groups - ex-prisoners, ' .
                                'migrants and drug users.',
                        'level' => 1
                    ],
                    [
                        'title' => 'Sub-Objective 1 A: To ensure access to early TB diagnostics, ' .
                                'including drug susceptibility testing, for vulnerable populations ' .
                                'through Program outreach and referral.',
                        'level' => 2
                    ],
                    [
                        [
                            $detectedMDRNL,
                            'MDR-TB cases detected among VPs through Program referral (HL.2.4.-1)',
                            'clients?project=6&examinedAfter=2020-09-01&examined=nonNegative' .
                                '&dateFilter=examined&examinedFrom=us&userParts=361&mdr=1' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' .
                                '&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                            $detectedMDRNLNew
                        ],
                        [
                            $detectedMDRMaleNL,
                            'male',
                            'clients?project=6&examinedAfter=2020-09-01&examined=nonNegative' .
                                '&dateFilter=examined&examinedFrom=us&userParts=361&mdr=1&gender=male' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' .
                                '&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                            $detectedMDRMaleNLNew
                        ],
                        [
                            $detectedMDRFemaleNL,
                            'female',
                            'clients?project=6&examinedAfter=2020-09-01&examined=nonNegative' .
                                '&dateFilter=examined&examinedFrom=us&userParts=361&mdr=1&gender=female' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' .
                                '&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                            $detectedMDRFemaleNLNew
                        ],
                        [
                            $detectedMDRPrisoners,
                            'ex-prisoners',
                            'clients?project=6&examinedAfter=2020-09-01&examined=nonNegative' .
                                '&dateFilter=examined&examinedFrom=us&userParts=361&mdr=1' .
                                '&profileField3=6g6x&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                                '&profileField=2CLW&profileValue=wx6s&profileOp=neq' .
                                $datesQuery,
                            $detectedMDRPrisonersNew
                        ],
                        [
                            $detectedMDRMigrants,
                            'migrants',
                            'clients?project=6&examinedAfter=2020-09-01&examined=nonNegative' .
                                '&dateFilter=examined&examinedFrom=us&userParts=361&mdr=1' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                                '&profileField=2CLW&profileValue=wx6s&profileOp=neq' .
                                $datesQuery,
                            $detectedMDRMigrantsNew
                        ],
                        [
                            $detectedMDRDrugUsers,
                            'drug users',
                            'clients?project=6&examinedAfter=2020-09-01&examined=nonNegative' .
                                '&dateFilter=examined&examinedFrom=us&userParts=361&mdr=1' .
                                '&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' .
                                '&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                            $detectedMDRDrugUsersNew
                        ],
                        [
                            $detectedMDRChildren,
                            'children',
                            'clients?project=6&examinedAfter=2020-09-01&examined=nonNegative' .
                                '&dateFilter=examined&examinedFrom=us&userParts=361&mdr=1' .
                                '&profileField3=6g6x&profileValue3=iNHa&profileOp3=contains' .
                                '&profileField=2CLW&profileValue=wx6s&profileOp=neq' . $datesQuery,
                            $detectedMDRChildrenNew
                        ]
                    ],
                    [
                        [
                            $examinedNL,
                            'VPs tested for TB through Program referral',
                            'clients?project=6&examinedAfter=2020-09-01&dateFilter=examined&examinedFrom=us' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' .
                                '&vuln=3' . $datesQuery,
                            $examinedNLNew
                        ],
                        [
                            $examinedMaleNL,
                            'male',
                            'clients?project=6&examinedAfter=2020-09-01&dateFilter=examined&examinedFrom=us' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' .
                                '&vuln=3&gender=male' . $datesQuery,
                            $examinedMaleNLNew
                        ],
                        [
                            $examinedFemaleNL,
                            'female',
                            'clients?project=6&examinedAfter=2020-09-01&dateFilter=examined&examinedFrom=us' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' .
                                '&vuln=3&gender=female' . $datesQuery,
                            $examinedFemaleNLNew
                        ],
                        [
                            $examinedPrisoners,
                            'ex-prisoners',
                            'clients?project=6&examinedAfter=2020-09-01&dateFilter=examined&examinedFrom=us' .
                                '&vuln=3&profileField3=6g6x&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                                $datesQuery,
                            $examinedPrisonersNew
                        ],
                        [
                            $examinedMigrants,
                            'migrants',
                            'clients?project=6&examinedAfter=2020-09-01&dateFilter=examined&examinedFrom=us' .
                                '&vuln=3&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                                $datesQuery,
                            $examinedMigrantsNew
                        ],
                        [
                            $examinedDrugUsers,
                            'drug users',
                            'clients?project=6&examinedAfter=2020-09-01&dateFilter=examined&examinedFrom=us' .
                                '&vuln=3&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' . $datesQuery,
                            $examinedDrugUsersNew
                        ]
                    ],
                    [
                        [
                            $screenedNL,
                            'VPs screened for TB through Program outreach',
                            'clients?project=6&searchActivities=скрининг&vuln=3' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery
                        ],
                        [
                            $screenedMaleNL,
                            'male',
                            'clients?project=6&searchActivities=скрининг&vuln=3&gender=male' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $screenedFemaleNL,
                            'female',
                            'clients?project=6&searchActivities=скрининг&vuln=3&gender=female' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $screenedPrisoner,
                            'ex-prisoners',
                            'clients?project=6&searchActivities=скрининг&vuln=3' .
                                '&profileField3=6g6x&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                                $datesQuery,
                        ],
                        [
                            $screenedMigrant,
                            'migrants',
                            'clients?project=6&searchActivities=скрининг&vuln=3' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                                $datesQuery,
                        ],
                        [
                            $screenedDrugUser,
                            'drug users',
                            'clients?project=6&searchActivities=скрининг&vuln=3' .
                                '&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' . $datesQuery,
                        ]
                    ],
                    [
                        'title' => 'Sub-Objective 1B: To ensure access to second-line treatment ' .
                                'and adherence support services for vulnerable populations with high risk ' .
                                'of lost-to-follow up though a multidisciplinary team approach.',
                        'level' => 2
                    ],
                    [
                        [
                            $startedMDRNL,
                            'MDR-TB cases notified among VPs through Program referral ' .
                                'that have initiated second-line treatment (HL.2.4.-2)',
                            'clients?project=6&profileField=Q5xs&profileOp=notNull&profileDate=Q5xs&started=1' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' .
                                '&mdr=1' . $datesQuery,
                            $startedMDRNLNew
                        ],
                        [
                            $startedMDRMaleNL,
                            'male',
                            'clients?project=6&profileField=Q5xs&profileOp=notNull&profileDate=Q5xs&started=1' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' .
                                '&mdr=1&gender=male' . $datesQuery,
                            $startedMDRMaleNLNew
                        ],
                        [
                            $startedMDRFemaleNL,
                            'female',
                            'clients?project=6&profileField=Q5xs&profileOp=notNull&profileDate=Q5xs&started=1' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' .
                                '&mdr=1&gender=female' . $datesQuery,
                            $startedMDRFemaleNLNew
                        ],
                        [
                            $startedMDRPrisoners,
                            'ex-prisoners',
                            'clients?project=6&profileField=Q5xs&profileOp=notNull&profileDate=Q5xs&started=1' .
                                '&mdr=1&profileField3=6g6x&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                                $datesQuery,
                            $startedMDRPrisonersNew
                        ],
                        [
                            $startedMDRMigrants,
                            'migrants',
                            'clients?project=6&profileField=Q5xs&profileOp=notNull&profileDate=Q5xs&started=1&mdr=1' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                                $datesQuery,
                            $startedMDRMigrantsNew
                        ],
                        [
                            $startedMDRDrugUsers,
                            'drug users',
                            'clients?project=6&profileField=Q5xs&profileOp=notNull&profileDate=Q5xs&started=1&mdr=1' .
                                '&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' . $datesQuery,
                            $startedMDRDrugUsersNew
                        ],
                    ],
                    [
                        [
                            $supportedNL,
                            'VPs who started TB treatment and received adherence support through Program services ' .
                                '(incl. those who started second-line treatment (ES.4-1) & ' .
                                'those who referred by the TB services)',
                            'clients?project=6&vuln=1&parts=' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $supportedDetectedNL,
                            'patients from vulnerable groups diagnosed with TB through the Outreach Program ' .
                                '(screening, referral, and diagnostics) plus TB patients who were found ' .
                                'and brought back to the treatment Program thanks to the Program efforts',
                            'clients?project=6&vuln=1&supported=2&parts=&profileDate=Q5xs' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $supportedReferredNL,
                            'patients from vulnerable groups already diagnosed with TB who were referred to ' .
                            'the Program to prevent loss for observation during treatment',
                            'clients?project=6&vuln=1&supported=3&parts=' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                    ],
                    [
                        [
                            $supportedMaleNL,
                            'male',
                            'clients?project=6&vuln=1&parts=&gender=male' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $supportedFemaleNL,
                            'female',
                            'clients?project=6&vuln=1&parts=&gender=female' .
                                '&profileField3=6g6x&profileValue3=uQRP&profileValue3=gG99' .
                                    '&profileValue3=2AJg&profileValue3=YNoK&profileOp3=contains' . $datesQuery,
                        ],
                        [
                            $supportedPrisoners,
                            'ex-prisoners',
                            'clients?project=6&vuln=1&parts=&' .
                                'profileField3=6g6x&profileValue3=gG99&profileValue3=MuuE&profileOp3=contains' .
                                $datesQuery,
                        ],
                        [
                            $supportedMigrants,
                            'migrants',
                            'clients?project=6&vuln=1&parts=&' .
                                'profileField3=6g6x&profileValue3=uQRP&profileValue3=YNoK&profileOp3=contains' .
                                $datesQuery,
                        ],
                        [
                            $supportedDrugUsers,
                            'drug users',
                            'clients?project=6&vuln=1&parts=' .
                                '&profileField3=6g6x&profileValue3=2AJg&profileOp3=contains' .
                                $datesQuery,
                        ]
                    ],
                    [
                        'title' => 'Objective 2: To provide 200 children receiving TB treatment (TBT) ' .
                            'in Tashkent city and their parents with the services of psychological support and ' .
                            'social assistance.',
                        'level' => 1
                    ],
                    [
                        [
                            $fullChildren2,
                            'children affected by TB who received a minimum package of social and ' .
                            'psychological support during the course of treatment',
                            'clients?project=6&fullch=2' . $datesQuery,
                        ],
                        [
                            $fullChildren2Boys,
                            'male',
                            'clients?project=6&fullch=2&gender=male' . $datesQuery,
                        ],
                        [
                            $fullChildren2Girls,
                            'female',
                            'clients?project=6&fullch=2&gender=female' . $datesQuery,
                        ],
                    ],
                    [
                        [
                            $children,
                            'children affected by TB received any psychological and social support ' .
                                'through Program services (ES.4-1c: Age 0-17)',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains&parts=' .
                                $datesQuery,
                            $childrenNew
                        ],
                        [
                            $boys,
                            'male',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains' .
                                '&gender=male&parts=' . $datesQuery,
                            $boysNew
                        ],
                        [
                            $girls,
                            'female',
                            'clients?project=6&profileField=6g6x&profileValue=iNHa&profileOp=contains' .
                                '&gender=female&parts=' . $datesQuery,
                            $girlsNew
                        ],
                    ],
                    [
                        [
                            $parents,
                            'parents of children affected by TB received support through Program services (ES.4-1)',
                            'clients?project=6&profileField=6g6x&profileValue=aSu3&profileOp=contains&parts=' .
                            $datesQuery,
                            $parentsNew
                        ],
                        [
                            $parentMen,
                            'male',
                            'clients?project=6&profileField=6g6x&profileValue=aSu3&profileOp=contains&gender=male' .
                                '&parts=' . $datesQuery,
                            $parentMenNew
                        ],
                        [
                            $parentWomen,
                            'female',
                            'clients?project=6&profileField=6g6x&profileValue=aSu3&profileOp=contains&gender=female' .
                                '&parts=' . $datesQuery,
                            $parentWomenNew
                        ]
                    ],
                    [
                        'title' => 'Objective 3: To enable further development and coordination of the national ' .
                                'TB partnership network representatives.',
                        'level' => 1
                    ],
                    [
                        [
                            $informed,
                            'service providers informed by Program who serve VPs',
                            'clients?project=6&parts=544&parts=545' .
                                '&profileField3=Y4N4&profileValue3=26bK&profileValue3=enqF&profileOp3=otherthan' .
                                '&partner=1' . $datesQuery,
                            $informedNew
                        ],
                        [
                            $informedMahalla,
                            'mahalla employees',
                            'clients?project=6&profileField=Y4N4&profileValue=kyLa&parts=544&parts=545' .
                                '&partner=1' . $datesQuery,
                            $informedMahallaNew
                        ],
                        [
                            $informedMahallaMale,
                            'male',
                            'clients?project=6&profileField=Y4N4&profileValue=kyLa&parts=544&parts=545' .
                                '&partner=1&gender=male' . $datesQuery,
                            $informedMahallaMaleNew
                        ],
                        [
                            $informedMahallaFemale,
                            'female',
                            'clients?project=6&profileField=Y4N4&profileValue=kyLa&parts=544&parts=545' .
                                '&partner=1&gender=male' . $datesQuery,
                            $informedMahallaFemaleNew
                        ]
                    ],
                    [
                        'title' => 'Objective 4: To provide informational and educational support ' .
                                'for the project realization.',
                        'level' => 1
                    ],
                    [
                        [
                            'n/a',
                            'IEC materials distributed'
                        ],
                    ],
                    [
                        [
                            $trained,
                            'service providers trained by Program who serve VPs (ES.4-2)',
                            'clients?project=6&profileField=Y4N4&profileOp=notNull&parts=546' . $datesQuery,
                            $trainedNew
                        ],
                        [
                            $trainedMen,
                            'male',
                            'clients?project=6&profileField=Y4N4&profileOp=notNull&parts=546&gender=male' . $datesQuery,
                            $trainedMenNew
                        ],
                        [
                            $trainedWomen,
                            'female',
                            'clients?project=6&profileField=Y4N4&profileOp=notNull&parts=546' .
                                '&gender=female' . $datesQuery,
                            $trainedWomenNew
                        ],
                    ]
                ]
            ], [
                'title' => 'Исходы (дети)',
                'items' => [
                    [
                        'title' => '7. Исходы заболевания',
                        'level' => 0
                    ],
                    [
                        [
                            $childrenOverallStarted
                                ? round($childrenOverallRemained / $childrenOverallStarted * 1000) / 10 . '%'
                                : 0,
                            'детей успешно завершили или продолжают лечение',
                        ],
                        [
                            $childrenOutcomes,
                            'детей, получавших справки о приверженности',
                            'clients?project=6&outcomes=1' .
                            '&profileField=6g6x&profileValue=iNHa&profileOp=contains' . $datesQuery,
                        ],
                        [
                            $childrenOutcomesTreated,
                            'продолжают медикаментозное лечение ПТП',
                            'clients?project=6&outcomes=bJjG&outcomes=PnKg' .
                            '&profileField=6g6x&profileValue=iNHa&profileOp=contains' . $datesQuery,
                        ],
                        [
                            $childrenOutcomesRecovered,
                            'успешно завершили медикаментозное лечение ПТП',
                            'clients?project=6&outcomes=nXDJ' .
                            '&profileField=6g6x&profileValue=iNHa&profileOp=contains' . $datesQuery,
                        ],
                        [
                            $childrenOutcomesLost,
                            'потеряны для последующего наблюдения',
                            'clients?project=6&outcomes=dAtv' .
                            '&profileField=6g6x&profileValue=iNHa&profileOp=contains' . $datesQuery,
                        ],
                        [
                            $childrenOutcomesBecameMDR,
                            'ЛЧТБ перешел в МЛУТБ',
                            'clients?project=6&outcomes=EMsf' .
                            '&profileField=6g6x&profileValue=iNHa&profileOp=contains' . $datesQuery,
                        ],
                        [
                            $childrenOutcomesBecameWDR,
                            'МЛУТБ перешел в ШЛУТБ',
                            'clients?project=6&outcomes=Ajf5' .
                            '&profileField=6g6x&profileValue=iNHa&profileOp=contains' . $datesQuery,
                        ]
                    ],
                    [
                        [
                            $childrenOutcomesUnregistered,
                            'сняты с диспансерного учета',
                            'clients?project=6&outcomes=meHa' .
                            '&profileField=6g6x&profileValue=iNHa&profileOp=contains' . $datesQuery,
                        ],
                        [
                            $childrenOutcomesMoved,
                            'уехали',
                            'clients?project=6&outcomes=meHa&reason=Lwch' .
                            '&profileField=6g6x&profileValue=iNHa&profileOp=contains' . $datesQuery,
                        ],
                        [
                            $childrenOutcomesDied,
                            'умерли',
                            'clients?project=6&outcomes=meHa&reason=BtcT' .
                            '&profileField=6g6x&profileValue=iNHa&profileOp=contains' . $datesQuery,
                        ],
                        [
                            $childrenOutcomesDiedTB,
                            'умерли от ТБ',
                            'clients?project=6&outcomes=meHa&reason=BtcT&cause=MppH' .
                            '&profileField=6g6x&profileValue=iNHa&profileOp=contains' . $datesQuery,
                        ],
                        [
                            $childrenOutcomesJailed,
                            'попали в места исполнения наказания',
                            'clients?project=6&outcomes=meHa&reason=KyEj' .
                            '&profileField=6g6x&profileValue=iNHa&profileOp=contains' . $datesQuery,
                        ],
                        [
                            $childrenOutcomesCured,
                            'излечены',
                            'clients?project=6&outcomes=meHa&reason=Ze52' .
                            '&profileField=6g6x&profileValue=iNHa&profileOp=contains' . $datesQuery,
                        ]
                    ]
                ]
            ]
        ];

        if (empty($request->users) || $customAccess) {
            CarbonInterval::setCascadeFactors([
                'minute' => [60, 'seconds'],
                'hour' => [60, 'minutes']
            ]);

            $etbuEmployees = User::whereHas('projectsUsers', function ($query) {
                $query->where('project_id', 6);
            })
            ->with([
                'projectsUsers' => function ($query) use ($request) {
                    $query->whereHas('userActivities.activity', function ($query) use ($request) {
                        $query->where('project_id', 6)
                            ->where('role', '!=', 'client')
                            ->whereHas('clients');

                        if ($request->from) {
                            $query->where('start_date', '>=', $request->from);
                        }

                        if ($request->till) {
                            $query->where('start_date', '<=', $request->till);
                        }
                    })
                    ->groupBy('user_id', 'position')
                    ->orderBy('id', 'desc');
                }
            ])
            ->withCount([
                'activities as services_count' => function ($query) use ($request) {
                    $query->where('project_id', 6)
                        ->where('role', '!=', 'client')
                        ->whereHas('clients');

                    if ($request->from) {
                        $query->where('start_date', '>=', $request->from);
                    }

                    if ($request->till) {
                        $query->where('start_date', '<=', $request->till);
                    }
                },
                'projectUserTimings as services_timing' => function ($query) use ($request) {
                    $query->select(DB::raw('SUM(timing)'))
                        ->where('verified', 1)
                        ->where('volunteering', 0)
                        ->where('project_id', 6)
                        ->whereHas('activity', function ($query) use ($request) {
                            $query->whereHas('clients');
                        });

                    if ($request->from) {
                        $query->whereDate('began_at', '>=', $request->from);
                    }

                    if ($request->till) {
                        $query->whereDate('ended_at', '<=', $request->till);
                    }
                }
            ])
            ->get()
            ->filter(function ($employee) {
                return !!$employee->services_count
                    && preg_match('/(психолог|социальн|консультант|юрист)/ui', $employee->projectsUsers[0]->position);
            })
            ->sortBy(function ($employee) {
                foreach (['психолог', 'социальн', 'консультант', 'юрист'] as $i => $position) {
                    if (preg_match("/$position/ui", $employee->projectsUsers[0]->position)) {
                        return $i;
                    }
                }
            })
            ->map(function ($employee) use ($request, $datesOnlyQuery) {
                $clients = User::whereHas('activities', function ($query) use ($request, $employee) {
                    $query->where('role', 'client')
                        ->where('project_id', 6)
                        ->whereHas('allUsers', function ($query) use ($employee) {
                            $query->where('user_id', $employee->id);
                        });

                    if ($request->from) {
                        $query->where('start_date', '>=', $request->from);
                    }

                    if ($request->till) {
                        $query->where('start_date', '<=', $request->till);
                    }
                })->count();

                $totalTimingQuery = $employee->projectUserTimings()
                    ->where('verified', 1)
                    ->where('volunteering', 0)
                    ->where('project_id', 6);

                if ($request->from) {
                    $totalTimingQuery->whereDate('began_at', '>=', $request->from);
                }

                if ($request->till) {
                    $totalTimingQuery->whereDate('ended_at', '<=', $request->till);
                }

                $totalTiming = $totalTimingQuery->sum('timing');

                $meetingTimingQuery = clone $totalTimingQuery;
                $meetingTimingQuery->whereIn('part_id', [11, 22, 98, 596, 612]);
                $meetingTiming = $meetingTimingQuery->sum('timing');

                $reportTimingQuery = clone $totalTimingQuery;
                $reportTimingQuery->whereIn('part_id', [37, 102, 185, 342, 384, 444, 547, 557, 677, 683, 710]);
                $reportTiming = $reportTimingQuery->sum('timing');

                $documentationTimingQuery = clone $totalTimingQuery;
                $documentationTimingQuery->whereIn('part_id', [3, 6, 18, 28, 64, 106, 182, 321, 342, 377, 379, 384]);
                $documentationTiming = $documentationTimingQuery->sum('timing');

                $eisTimingQuery = clone $totalTimingQuery;
                $eisTimingQuery->whereIn('part_id', [2, 109, 139]);
                $eisTiming = $eisTimingQuery->sum('timing');

                $rideTimingQuery = clone $totalTimingQuery;
                $rideTimingQuery->whereIn('part_id', [4]);
                $rideTiming = $rideTimingQuery->sum('timing');

                $remainingTiming = $totalTiming - $employee->services_timing - $meetingTiming - $reportTiming
                    - $documentationTiming - $eisTiming - $rideTiming;

                return [
                    [
                        'title' => $employee->name,
                        'level' => 2
                    ],
                    [
                        [
                            $clients,
                            'всего клиентов получили услуги',
                            'clients?project=6&parts=&users=' . $employee->id . $datesOnlyQuery
                        ],
                        [
                            $employee->services_count,
                            'всего услуг предоставлено',
                            'services?projects=6&users=' . $employee->id . $datesOnlyQuery
                        ],
                        [
                            $this->formatTime($totalTiming),
                            'общая выработка'
                        ],
                        [
                            $this->formatTime($employee->services_timing),
                            'оказание услуг'
                        ],
                        [
                            $this->formatTime($meetingTiming),
                            'участие в собраниях'
                        ],
                        [
                            $this->formatTime($reportTiming),
                            'подготовка учетно-отчетной документации'
                        ],
                        [
                            $this->formatTime($documentationTiming),
                            'работа с документами'
                        ],
                        [
                            $this->formatTime($eisTiming),
                            'внесение данных в ЭИС'
                        ],
                        [
                            $this->formatTime($rideTiming),
                            'служебные поездки'
                        ],
                        [
                            $this->formatTime($remainingTiming),
                            'другое'
                        ]
                    ]
                ];
            })
            ->flatten(1);

            $indicators[] = [
                'title' => 'Индивидуальная сводка',
                'items' => $etbuEmployees
            ];
        }

        return $indicators;
    }

    protected static function addDetectedClause($query)
    {
        $query->where(function ($query) {
            $query->whereNull('d.data->2CLW')
                ->orWhere('d.data->2CLW', '!=', 'wx6s');
        })
        ->where(function ($query) {
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
    }

    public static function addOutreachClause(
        $query,
        $request,
        $parts = null,
        $position = null,
        $positionNe = false
    ) {
        $query->where(function ($query) use ($request, $parts, $position, $positionNe) {
            $query->where(function ($query) use ($request, $parts, $position, $positionNe) {
                static::addDetectedClause($query);

                $query->whereHas('activities', function ($query) use ($request, $parts, $position, $positionNe) {
                    $query->where('project_id', 6);

                    if ($parts) {
                        $query->whereIn('user_activity.part_id', $parts);
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
                        $query->where('start_date', '<=', $request->till);
                    }

                    $query->where(function ($query) {
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

                    $query->whereColumn('start_date', '<', DB::raw('if(
                        (
                            (
                                json_extract(`d`.`data`, \'$."Xnsu"\') is not null
                                AND json_type(json_extract(`d`.`data`, \'$."Xnsu"\')) != \'NULL\'
                            )
                            and json_unquote(json_extract(`d`.`data`, \'$."Xnsu"\')) = \'LbKw\'
                        )
                        and (
                            (
                                json_extract(`d`.`data`, \'$."it7x"\') is not null
                                AND json_type(json_extract(`d`.`data`, \'$."it7x"\')) != \'NULL\'
                            )
                            and date(
                                json_unquote(json_extract(`d`.`data`, \'$."it7x"\'))
                            ) >= \'2020-09-01\'
                        )
                        and (
                            json_extract(`d`.`data`, \'$."d6XS"\') is not null
                            AND json_type(json_extract(`d`.`data`, \'$."d6XS"\')) != \'NULL\'
                        )
                        and json_unquote(json_extract(`d`.`data`, \'$."d6XS"\')) != \'vNSz\'
                        and json_unquote(json_extract(`d`.`data`, \'$."d6XS"\')) != \'MPCj\',
                        json_unquote(json_extract(`d`.`data`, \'$."it7x"\')),
                        json_unquote(json_extract(`d`.`data`, \'$."mynW"\'))
                    )'));

                    if ($position) {
                        $query->whereHas('allUsers.projectUser', function ($query) use ($position, $positionNe) {
                            $query->where('position', $positionNe ? 'not like' : 'like', "%$position%");
                        });
                    }
                });
            })
            ->orWhere(function ($query) use ($request, $parts, $position, $positionNe) {
                $query->where(function ($query) {
                    $query->whereNull('d.data->2CLW')
                        ->orWhere('d.data->2CLW', '!=', 'wx6s');
                })
                ->where(function ($query) {
                    $query->whereNull('d.data->Xnsu')
                        ->orWhere('d.data->Xnsu', 'BfXs')
                        ->orWhereNull('d.data->it7x')
                        ->orWhereDate('d.data->it7x', '<', '2020-09-01')
                        ->orWhereNull('d.data->d6XS')
                        ->orWhere('d.data->d6XS', 'vNSz')
                        ->orWhere('d.data->d6XS', 'MPCj');
                })
                ->where(function ($query) {
                    $query->whereNull('d.data->roMc')
                        ->orWhere('d.data->roMc', 'jX7P')
                        ->orWhereNull('d.data->mynW')
                        ->orWhereDate('d.data->mynW', '<', '2020-09-01')
                        ->orWhereNull('d.data->eNaZ')
                        ->orWhere('d.data->eNaZ', 'muv9')
                        ->orWhere('d.data->eNaZ', 'EohW');
                })
                ->whereHas('activities', function ($query) {
                    $query->where('project_id', 6)
                        ->where(function ($query) {
                            $keyword = 'скрининг';

                            $query->where('title', 'like', "%$keyword%")
                                ->orWhere('description', 'like', "%$keyword%")
                                ->orWhereHas('timings', function ($query) use ($keyword) {
                                    $query->where('comment', 'like', "%$keyword%");
                                });
                        });
                })
                ->whereHas('activities', function ($query) use ($request, $parts, $position, $positionNe) {
                    $query->where('project_id', 6);

                    if ($parts) {
                        $query->whereIn('user_activity.part_id', $parts);
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
                        $query->where('start_date', '<=', $request->till);
                    }

                    if ($position) {
                        $query->whereHas('allUsers.projectUser', function ($query) use ($position, $positionNe) {
                            $query->where('position', $positionNe ? 'not like' : 'like', "%$position%");
                        });
                    }
                });
            });
        });
    }

    public static function addOutreachServicesClause(
        $query,
        $request,
        $parts = null,
        $position = null,
        $positionNe = false,
        $inverseParts = false
    ) {
        if ($parts) {
            if (!$inverseParts) {
                $query->whereIn('user_activity.part_id', $parts);
            } else {
                $query->whereNotIn('user_activity.part_id', $parts);
            }
        }

        $query->where(function ($query) use ($request) {
            if ($request->users) {
                $query->whereHas('allUsers', function ($query) use ($request) {
                    $query->whereIn('user_id', $request->users);
                });
            }

            if ($request->from) {
                $query->where('activities.start_date', '>=', $request->from);
            }

            if ($request->till) {
                $query->where('activities.start_date', '<=', $request->till);
            }
        })
        ->join('user_activity', 'user_activity.activity_id', '=', 'activities.id')
        ->join('users', 'users.id', '=', 'user_activity.user_id')
        ->where(function ($query) {
            $query->where(function ($query) {
                static::addDetectedClause($query);

                $query->where(function ($query) {
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

                $query->whereColumn('activities.start_date', '<', DB::raw('if(
                    (
                        (
                            json_extract(`d`.`data`, \'$."Xnsu"\') is not null
                            AND json_type(json_extract(`d`.`data`, \'$."Xnsu"\')) != \'NULL\'
                        )
                        and json_unquote(json_extract(`d`.`data`, \'$."Xnsu"\')) = \'LbKw\'
                    )
                    and (
                        (
                            json_extract(`d`.`data`, \'$."it7x"\') is not null
                            AND json_type(json_extract(`d`.`data`, \'$."it7x"\')) != \'NULL\'
                        )
                        and date(
                            json_unquote(json_extract(`d`.`data`, \'$."it7x"\'))
                        ) >= \'2020-09-01\'
                    )
                    and (
                        json_extract(`d`.`data`, \'$."d6XS"\') is not null
                        AND json_type(json_extract(`d`.`data`, \'$."d6XS"\')) != \'NULL\'
                    )
                    and json_unquote(json_extract(`d`.`data`, \'$."d6XS"\')) != \'vNSz\'
                    and json_unquote(json_extract(`d`.`data`, \'$."d6XS"\')) != \'MPCj\',
                    json_unquote(json_extract(`d`.`data`, \'$."it7x"\')),
                    json_unquote(json_extract(`d`.`data`, \'$."mynW"\'))
                )'));
            })
            ->orWhere(function ($query) {
                $query->where(function ($query) {
                    $query->whereNull('d.data->2CLW')
                        ->orWhere('d.data->2CLW', '!=', 'wx6s');
                })
                ->where(function ($query) {
                    $query->whereNull('d.data->Xnsu')
                        ->orWhere('d.data->Xnsu', 'BfXs')
                        ->orWhereNull('d.data->it7x')
                        ->orWhereDate('d.data->it7x', '<', '2020-09-01')
                        ->orWhereNull('d.data->d6XS')
                        ->orWhere('d.data->d6XS', 'vNSz')
                        ->orWhere('d.data->d6XS', 'MPCj');
                })
                ->where(function ($query) {
                    $query->whereNull('d.data->roMc')
                        ->orWhere('d.data->roMc', 'jX7P')
                        ->orWhereNull('d.data->mynW')
                        ->orWhereDate('d.data->mynW', '<', '2020-09-01')
                        ->orWhereNull('d.data->eNaZ')
                        ->orWhere('d.data->eNaZ', 'muv9')
                        ->orWhere('d.data->eNaZ', 'EohW');
                })
                ->where(function ($query) {
                    $keyword = 'скрининг';

                    $query->where('screening_activity.title', 'like', "%$keyword%")
                        ->orWhere('screening_activity.description', 'like', "%$keyword%")
                        ->orWhere('comment', 'like', "%$keyword%");
                });
            });
        })
        ->leftJoin('user_activity as screening_user_activity', 'screening_user_activity.user_id', '=', 'users.id')
        ->leftJoin('activities as screening_activity', function ($query) {
            $query->whereColumn('screening_activity.id', 'screening_user_activity.activity_id')
                ->where('screening_activity.project_id', 6);
        })
        ->leftJoin('timings', 'timings.activity_id', '=', 'screening_activity.id');

        if ($position) {
            $query->whereHas('allUsers.projectUser', function ($query) use ($position, $positionNe) {
                $query->where('position', $positionNe ? 'not like' : 'like', "%$position%");
            });
        }
    }
}
