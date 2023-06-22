<?php

namespace App\Lib\Legacy\Indicators;

use App\Models\User;

class DVVIndicatorsComputer extends ProjectIndicatorsComputer
{
    public function compute($request, $customAccess, $new, $datesOnlyQuery, $datesQuery)
    {
        list(
            $dvvClients,
            $dvvClientsMen,
            $dvvClientsWomen,
        ) = $this->getCountsByProfileField(null, null, null, $request, '=', null, false, false, false, 7, true, true);

        list(
            $dvvServicedClients,
            $dvvServicedClientsMen,
            $dvvServicedClientsWomen,
            $dvvServices
        ) = $this->getCountsByProfileField(null, null, [], $request, '=', null, false, false, false, 7, true, true);

        list(
            $dvvPartners,
            $dvvPartnersMen,
            $dvvPartnersWomen
        ) = $this->getCountsByProfileField(
            'cusF',
            'pSDq',
            null,
            $request,
            '=',
            null,
            false,
            false,
            false,
            7,
            true,
            true
        );

        list(
            $dvvLegal,
            $dvvLegalMen,
            $dvvLegalWomen,
            $dvvLegalServices
        ) = $this->getCountsByProfileField(
            null,
            null,
            [366, 370, 379],
            $request,
            '=',
            null,
            false,
            false,
            false,
            7,
            true,
            true
        );

        list(
            $dvvPsy,
            $dvvPsyMen,
            $dvvPsyWomen,
            $dvvPsyServices
        ) = $this->getCountsByProfileField(
            null,
            null,
            [367, 371, 375, 376],
            $request,
            '=',
            null,
            false,
            false,
            false,
            7,
            true,
            true
        );

        list(
            $dvvSoc,
            $dvvSocMen,
            $dvvSocWomen,
            $dvvSocServices
        ) = $this->getCountsByProfileField(
            null,
            null,
            [364, 365, 368, 369, 373, 361, 362, 363],
            $request,
            '=',
            null,
            false,
            false,
            false,
            7,
            true,
            true
        );

        $trainingQuery = User::where('roles', 'like', '%client%')
            ->whereHas('projects', function ($query) {
                $query->where('project_id', 7);
            });

        $this->addDocumentsJoin($trainingQuery, 9);

        $this->addDocumentsJoin($trainingQuery, 51, false, true, function ($query) {
            $query->where('data->tdak', 'gZk8');
        });

        $trainingQuery->whereNotNull('d51.data->7Amw');

        $doingTrainingQuery = clone $trainingQuery;

        $doingTrainingQuery->whereNull('d51.data->792u->file');

        if ($request->from) {
            $doingTrainingQuery->where('d51.data->7Amw', '>=', $request->from);
        }

        if ($request->till) {
            $doingTrainingQuery->where('d51.data->7Amw', '<=', $request->till);
        }

        list(
            $training,
            $trainingMen,
            $trainingWomen
        ) = $this->getCountsByGender($doingTrainingQuery, 9);

        $quitTrainingQuery = clone $trainingQuery;
        $quitTrainingQuery->where('d51.data->9Dbe', true);

        list(
            $quitTraining,
            $quitTrainingMen,
            $quitTrainingWomen
        ) = $this->getCountsByGender($quitTrainingQuery, 9);

        $finishedTrainingQuery = clone $trainingQuery;

        $finishedTrainingQuery->whereNotNull('d51.data->iXCk');

        if ($request->from) {
            $finishedTrainingQuery->where('d51.data->iXCk', '>=', $request->from);
        }

        if ($request->till) {
            $finishedTrainingQuery->where('d51.data->iXCk', '<=', $request->till);
        }

        $finishedTrainingQuery->whereNotNull('d51.data->792u->file');

        list(
            $finishedTraining,
            $finishedTrainingMen,
            $finishedTrainingWomen
        ) = $this->getCountsByGender($finishedTrainingQuery, 9);

        return [
            [
                'title' => 'DVV',
                'items' => [
                    [
                        [
                            $dvvClients,
                            'всего клиентов',
                            'clients?project=7&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvClientsMen,
                            'мужчин',
                            'clients?project=7&gender=male&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvClientsWomen,
                            'женщин',
                            'clients?project=7&gender=female&verified=1' . $datesOnlyQuery,
                        ],
                    ],
                    [
                        [
                            $dvvServices,
                            'услуг оказано',
                            'services?projects=7&parts=&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvServicedClients,
                            'клиентов получили услуги',
                            'clients?project=7&parts=&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvServicedClientsMen,
                            'мужчин',
                            'clients?project=7&parts=&gender=male&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvServicedClientsWomen,
                            'женщин',
                            'clients?project=7&parts=&gender=female&verified=1' . $datesOnlyQuery,
                        ],
                    ],
                    [
                        [
                            $dvvPartners,
                            'всего партнеров',
                            'clients?project=7&profileField=cusF&profileValue=pSDq&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvPartnersMen,
                            'мужчин',
                            'clients?project=7&profileField=cusF&profileValue=pSDq&gender=male&verified=1' .
                                $datesOnlyQuery,
                        ],
                        [
                            $dvvPartnersWomen,
                            'женщин',
                            'clients?project=7&profileField=cusF&profileValue=pSDq&gender=female&verified=1' .
                                $datesOnlyQuery,
                        ],
                    ],
                    [
                        [
                            $dvvLegalServices,
                            'юридических услуг оказано',
                            'services?projects=7&parts=366&parts=370&parts=379&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvLegal,
                            'клиентов получили юридические услуги',
                            'clients?project=7&parts=366&parts=370&parts=379&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvLegalMen,
                            'мужчин',
                            'clients?project=7&parts=366&parts=370&parts=379&gender=male&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvLegalWomen,
                            'женщин',
                            'clients?project=7&parts=366&parts=370&parts=379&gender=female' .
                                '&verified=1' . $datesOnlyQuery,
                        ],
                    ],
                    [
                        [
                            $dvvPsyServices,
                            'услуг психолога оказано',
                            'services?projects=7&parts=367&parts=371&parts=375&parts=376&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvPsy,
                            'клиентов получили услуги психолога',
                            'clients?project=7&parts=367&parts=371&parts=375&parts=376&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvPsyMen,
                            'мужчин',
                            'clients?project=7&parts=367&parts=371&parts=375&parts=376&gender=male' .
                                '&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvPsyWomen,
                            'женщин',
                            'clients?project=7&parts=367&parts=371&parts=375&parts=376&gender=female' .
                                '&verified=1' . $datesOnlyQuery,
                        ],
                    ],
                    [
                        [
                            $dvvSocServices,
                            'услуг социального работника оказано',
                            'services?projects=7&parts=364&parts=365&parts=368&parts=369&parts=373&parts=361&parts=362&parts=363' .
                                '&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvSoc,
                            'клиентов получили услуги социального работника',
                            'clients?project=7&parts=364&parts=365&parts=368&parts=369&parts=373&parts=361&parts=362&parts=363' .
                                '&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvSocMen,
                            'мужчин',
                            'clients?project=7&parts=364&parts=365&parts=368&parts=369&parts=373&parts=361&parts=362&parts=363&gender=male' .
                                '&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvSocWomen,
                            'женщин',
                            'clients?project=7&parts=364&parts=365&parts=368&parts=369&parts=373&parts=361&parts=362&parts=363&gender=female' .
                                '&verified=1' . $datesOnlyQuery,
                        ],
                    ],
                    [
                        'title' => 'Обучение',
                        'level' => 1
                    ],
                    [
                        [
                            $training,
                            'всего обучаются',
                            'clients?project=7&profileField2=51.7Amw&profileOp2=notNull&profileDate=51.7Amw' .
                            '&profileField=51.792u->file&profileOp=null' .
                            '&dscField=51.tdak&dscValue=gZk8' .
                            '&verified=1' . $datesOnlyQuery,
                            null,
                            route('downloadTraining') . '?role=client&project=7&profileField2=51.7Amw' .
                                '&profileOp2=notNull&profileDate=51.7Amw&dscField=51.tdak&dscValue=gZk8' .
                                '&profileField=51.792u->file&profileOp=null' .
                                '&verified=1' . $datesOnlyQuery
                        ],
                        [
                            $trainingMen,
                            'мужчин',
                            'clients?project=7&profileField2=51.7Amw&profileOp2=notNull&profileDate=51.7Amw' .
                            '&profileField=51.792u->file&profileOp=null' .
                            '&dscField=51.tdak&dscValue=gZk8' .
                            '&gender=male&verified=1' . $datesOnlyQuery
                        ],
                        [
                            $trainingWomen,
                            'женщин',
                            'clients?project=7&profileField2=51.7Amw&profileOp2=notNull&profileDate=51.7Amw' .
                            '&profileField=51.792u->file&profileOp=null' .
                            '&dscField=51.tdak&dscValue=gZk8' .
                            '&gender=female&verified=1' . $datesOnlyQuery
                        ]
                    ],
                    [
                        [
                            $quitTraining,
                            'всего бросили обучение',
                            'clients?project=7&profileField2=51.7Amw&profileOp2=notNull&profileDate=51.7Amw' .
                            '&profileField=51.9Dbe&profileOp=true&dscField=51.tdak&dscValue=gZk8' .
                            '&verified=1' . $datesOnlyQuery
                        ],
                        [
                            $quitTrainingMen,
                            'мужчин',
                            'clients?project=7&profileField2=51.7Amw&profileOp2=notNull&profileDate=51.7Amw' .
                            '&profileField=51.9Dbe&profileOp=true&dscField=51.tdak&dscValue=gZk8' .
                            '&gender=male&verified=1' . $datesOnlyQuery
                        ],
                        [
                            $quitTrainingWomen,
                            'женщин',
                            'clients?project=7&profileField2=51.7Amw&profileOp2=notNull&profileDate=51.7Amw' .
                            '&profileField=51.9Dbe&profileOp=true&dscField=51.tdak&dscValue=gZk8' .
                            '&gender=female&verified=1' . $datesOnlyQuery
                        ]
                    ],
                    [
                        [
                            $finishedTraining,
                            'всего завершили обучение',
                            'clients?project=7&profileField=51.7Amw&profileOp=notNull&profileDate=51.iXCk' .
                            '&profileField2=51.iXCk&profileOp2=notNull' .
                            '&profileField3=51.792u->file&profileOp3=notNull' .
                            '&dscField=51.tdak&dscValue=gZk8' .
                            '&verified=1' . $datesOnlyQuery,
                            null,
                            route('downloadTraining') . '?role=client&project=7&profileField2=51.7Amw' .
                                '&profileOp2=notNull&profileDate=51.iXCk' .
                                '&profileField2=51.iXCk&profileOp2=notNull' .
                                '&profileField3=51.792u->file&profileOp3=notNull' .
                                '&dscField=51.tdak&dscValue=gZk8' .
                                '&verified=1' . $datesOnlyQuery
                        ],
                        [
                            $finishedTrainingMen,
                            'мужчин',
                            'clients?project=7&profileField=51.7Amw&profileOp=notNull&profileDate=51.iXCk' .
                            '&profileField2=51.iXCk&profileOp2=notNull' .
                            '&profileField3=51.792u->file&profileOp3=notNull' .
                            '&dscField=51.tdak&dscValue=gZk8' .
                            '&gender=male&verified=1' . $datesOnlyQuery
                        ],
                        [
                            $finishedTrainingWomen,
                            'женщин',
                            'clients?project=7&profileField=51.7Amw&profileOp=notNull&profileDate=51.iXCk' .
                            '&profileField2=51.iXCk&profileOp2=notNull' .
                            '&profileField3=51.792u->file&profileOp3=notNull' .
                            '&dscField=51.tdak&dscValue=gZk8' .
                            '&gender=female&verified=1' . $datesOnlyQuery
                        ]
                    ]
                ]
            ]
        ];
    }
}
