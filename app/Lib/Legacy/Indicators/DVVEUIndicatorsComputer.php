<?php

namespace App\Lib\Legacy\Indicators;

use App\Models\User;

class DVVEUIndicatorsComputer extends ProjectIndicatorsComputer
{
    public function compute($request, $customAccess, $new, $datesOnlyQuery, $datesQuery)
    {
        list(
            $dvvEuClients,
            $dvvEuClientsMen,
            $dvvEuClientsWomen
        ) = $this->getCountsByProfileField(null, null, null, $request, '=', null, false, false, false, 11, true, true);

        list(
            $dvvEuPrisonerClients,
            $dvvEuPrisonerClientsMen,
            $dvvEuPrisonerClientsWomen
        ) = $this->getCountsByProfileField(
            'Awwe',
            'xhKq',
            null,
            $request,
            '=',
            null,
            false,
            false,
            false,
            11,
            true,
            true
        );

        list(
            $dvvEuPartners,
            $dvvEuPartnersMen,
            $dvvEuPartnersWomen
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
            11,
            true,
            true
        );

        list(
            $dvvEuLegal,
            $dvvEuLegalMen,
            $dvvEuLegalWomen,
            $dvvEuLegalServices
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
            11,
            true,
            true
        );

        list(
            $dvvEuPrisonerLegal,
            $dvvEuPrisonerLegalMen,
            $dvvEuPrisonerLegalWomen,
            $dvvEuPrisonerLegalServices
        ) = $this->getCountsByProfileField(
            'Awwe',
            'xhKq',
            [366, 370, 379],
            $request,
            '=',
            null,
            false,
            false,
            false,
            11,
            true,
            true
        );

        list(
            $dvvEuPsy,
            $dvvEuPsyMen,
            $dvvEuPsyWomen,
            $dvvEuPsyServices
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
            11,
            true,
            true
        );

        list(
            $dvvEuPrisonerPsy,
            $dvvEuPrisonerPsyMen,
            $dvvEuPrisonerPsyWomen,
            $dvvEuPrisonerPsyServices
        ) = $this->getCountsByProfileField(
            'Awwe',
            'xhKq',
            [367, 371, 375, 376],
            $request,
            '=',
            null,
            false,
            false,
            false,
            11,
            true,
            true
        );

        list(
            $dvvEuSoc,
            $dvvEuSocMen,
            $dvvEuSocWomen,
            $dvvEuSocServices
        ) = $this->getCountsByProfileField(
            null,
            null,
            [364, 365, 368, 369, 373],
            $request,
            '=',
            null,
            false,
            false,
            false,
            11,
            true,
            true
        );

        list(
            $dvvEuPrisonerSoc,
            $dvvEuPrisonerSocMen,
            $dvvEuPrisonerSocWomen,
            $dvvEuPrisonerSocServices
        ) = $this->getCountsByProfileField(
            'Awwe',
            'xhKq',
            [364, 365, 368, 369, 373],
            $request,
            '=',
            null,
            false,
            false,
            false,
            11,
            true,
            true
        );

        $trainingQuery = User::where('roles', 'like', '%client%')
            ->whereHas('projects', function ($query) {
                $query->where('project_id', 11);
            });

        $this->addDocumentsJoin($trainingQuery, 10);

        $this->addDocumentsJoin($trainingQuery, 51, false, true, function ($query) {
            $query->where('data->tdak', '7ij9');
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
        ) = $this->getCountsByGender($quitTrainingQuery, 10);

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
        ) = $this->getCountsByGender($finishedTrainingQuery, 10);

        return [
            [
                'title' => 'DVV EU',
                'items' => [
                    [
                        [
                            $dvvEuClients,
                            'всего клиентов',
                            'clients?project=11&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvEuClientsMen,
                            'мужчин',
                            'clients?project=11&gender=male&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvEuClientsWomen,
                            'женщин',
                            'clients?project=11&gender=female&verified=1' . $datesOnlyQuery,
                        ],
                    ],
                    [
                        [
                            $dvvEuPrisonerClients,
                            'всего отбывающих наказание',
                            'clients?project=11&profileField=Awwe&profileValue=xhKq&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvEuPrisonerClientsMen,
                            'мужчин',
                            'clients?project=11&profileField=Awwe&profileValue=xhKq&gender=male' .
                                '&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvEuPrisonerClientsWomen,
                            'женщин',
                            'clients?project=11&profileField=Awwe&profileValue=xhKq&gender=female' .
                                '&verified=1' . $datesOnlyQuery,
                        ],
                    ],
                    [
                        [
                            $dvvEuPartners,
                            'всего партнеров',
                            'clients?project=11&profileField=cusF&profileValue=pSDq&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvEuPartnersMen,
                            'мужчин',
                            'clients?project=11&profileField=cusF&profileValue=pSDq&gender=male&verified=1' .
                                $datesOnlyQuery,
                        ],
                        [
                            $dvvEuPartnersWomen,
                            'женщин',
                            'clients?project=11&profileField=cusF&profileValue=pSDq&gender=female&verified=1' .
                                $datesOnlyQuery,
                        ],
                    ],
                    [
                        [
                            $dvvEuLegalServices,
                            'юридических услуг оказано',
                            'services?projects=11&parts=366&parts=370&parts=379&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvEuLegal,
                            'клиентов получили юридические услуги',
                            'clients?project=11&parts=366&parts=370&parts=379&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvEuLegalMen,
                            'мужчин',
                            'clients?project=11&parts=366&parts=370&parts=379&gender=male&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvEuLegalWomen,
                            'женщин',
                            'clients?project=11&parts=366&parts=370&parts=379&gender=female' .
                                '&verified=1' . $datesOnlyQuery,
                        ],
                    ],
                    [
                        [
                            $dvvEuPrisonerLegalServices,
                            'юридических услуг оказано отбывающим наказание',
                            'services?projects=11&parts=366&parts=370&parts=379' .
                                '&profileField=Awwe&profileValue=xhKq&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvEuPrisonerLegal,
                            'клиентов, отбывающих наказание, получили юридические услуги',
                            'clients?project=11&parts=366&parts=370&parts=379' .
                                '&profileField=Awwe&profileValue=xhKq&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvEuPrisonerLegalMen,
                            'мужчин',
                            'clients?project=11&parts=366&parts=370&parts=379&gender=male' .
                                '&profileField=Awwe&profileValue=xhKq&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvEuPrisonerLegalWomen,
                            'женщин',
                            'clients?project=11&parts=366&parts=370&parts=379&gender=female' .
                                '&profileField=Awwe&profileValue=xhKq&verified=1' . $datesOnlyQuery,
                        ],
                    ],
                    [
                        [
                            $dvvEuPsyServices,
                            'услуг психолога оказано',
                            'services?projects=11&parts=367&parts=371&parts=375&parts=376&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvEuPsy,
                            'клиентов получили услуги психолога',
                            'clients?project=11&parts=367&parts=371&parts=375&parts=376&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvEuPsyMen,
                            'мужчин',
                            'clients?project=11&parts=367&parts=371&parts=375&parts=376&gender=male' .
                                '&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvEuPsyWomen,
                            'женщин',
                            'clients?project=11&parts=367&parts=371&parts=375&parts=376&gender=female' .
                                '&verified=1' . $datesOnlyQuery,
                        ],
                    ],
                    [
                        [
                            $dvvEuPrisonerPsyServices,
                            'услуг психолога оказано отбывающим наказание',
                            'services?projects=11&parts=367&parts=371&parts=375&parts=376' .
                                '&profileField=Awwe&profileValue=xhKq&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvEuPrisonerPsy,
                            'клиентов, отбывающих наказание, получили услуги психолога',
                            'clients?project=11&parts=367&parts=371&parts=375&parts=376' .
                                '&profileField=Awwe&profileValue=xhKq&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvEuPrisonerPsyMen,
                            'мужчин',
                            'clients?project=11&parts=367&parts=371&parts=375&parts=376' .
                                '&profileField=Awwe&profileValue=xhKq&gender=male&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvEuPrisonerPsyWomen,
                            'женщин',
                            'clients?project=11&parts=367&parts=371&parts=375&parts=376' .
                                '&profileField=Awwe&profileValue=xhKq&gender=female&verified=1' . $datesOnlyQuery,
                        ],
                    ],
                    [
                        [
                            $dvvEuSocServices,
                            'услуг социального работника оказано',
                            'services?projects=11&parts=364&parts=365&parts=368&parts=369&parts=373' .
                                '&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvEuSoc,
                            'клиентов получили услуги социального работника',
                            'clients?project=11&parts=364&parts=365&parts=368&parts=369&parts=373' .
                                '&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvEuSocMen,
                            'мужчин',
                            'clients?project=11&parts=364&parts=365&parts=368&parts=369&parts=373&gender=male' .
                                '&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvEuSocWomen,
                            'женщин',
                            'clients?project=11&parts=364&parts=365&parts=368&parts=369&parts=373&gender=female' .
                                '&verified=1' . $datesOnlyQuery,
                        ],
                    ],
                    [
                        [
                            $dvvEuPrisonerSocServices,
                            'услуг социального работника оказано отбывающим наказание',
                            'services?projects=11&parts=364&parts=365&parts=368&parts=369&parts=373' .
                                '&profileField=Awwe&profileValue=xhKq&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvEuPrisonerSoc,
                            'клиентов, отбывающих наказание, получили услуги социального работника',
                            'clients?project=11&parts=364&parts=365&parts=368&parts=369&parts=373' .
                                '&profileField=Awwe&profileValue=xhKq&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvEuPrisonerSocMen,
                            'мужчин',
                            'clients?project=11&parts=364&parts=365&parts=368&parts=369&parts=373' .
                                '&profileField=Awwe&profileValue=xhKq&gender=male&verified=1' . $datesOnlyQuery,
                        ],
                        [
                            $dvvEuPrisonerSocWomen,
                            'женщин',
                            'clients?project=11&parts=364&parts=365&parts=368&parts=369&parts=373' .
                                '&profileField=Awwe&profileValue=xhKq&gender=female&verified=1' . $datesOnlyQuery,
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
                            'clients?project=11&profileField2=51.7Amw&profileOp2=notNull&profileDate=51.7Amw' .
                            '&profileField=51.792u->file&profileOp=null' .
                            '&dscField=51.tdak&dscValue=7ij9' .
                            '&verified=1' . $datesOnlyQuery,
                            null,
                            route('downloadTraining') . '?role=client&project=11&profileField2=51.7Amw' .
                                '&profileOp2=notNull&profileDate=51.7Amw&dscField=51.tdak&dscValue=7ij9' .
                                '&profileField=51.792u->file&profileOp=null' .
                                '&verified=1' . $datesOnlyQuery
                        ],
                        [
                            $trainingMen,
                            'мужчин',
                            'clients?project=11&profileField2=51.7Amw&profileOp2=notNull&profileDate=51.7Amw' .
                            '&profileField=51.792u->file&profileOp=null' .
                            '&dscField=51.tdak&dscValue=7ij9' .
                            '&gender=male&verified=1' . $datesOnlyQuery
                        ],
                        [
                            $trainingWomen,
                            'женщин',
                            'clients?project=11&profileField2=51.7Amw&profileOp2=notNull&profileDate=51.7Amw' .
                            '&profileField=51.792u->file&profileOp=null' .
                            '&dscField=51.tdak&dscValue=7ij9' .
                            '&gender=female&verified=1' . $datesOnlyQuery
                        ]
                    ],
                    [
                        [
                            $quitTraining,
                            'всего бросили обучение',
                            'clients?project=11&profileField2=51.7Amw&profileOp2=notNull&profileDate=51.7Amw' .
                            '&profileField=51.9Dbe&profileOp=true&dscField=51.tdak&dscValue=7ij9' .
                            '&verified=1' . $datesOnlyQuery
                        ],
                        [
                            $quitTrainingMen,
                            'мужчин',
                            'clients?project=11&profileField2=51.7Amw&profileOp2=notNull&profileDate=51.7Amw' .
                            '&profileField=51.9Dbe&profileOp=true&dscField=51.tdak&dscValue=7ij9' .
                            '&gender=male&verified=1' . $datesOnlyQuery
                        ],
                        [
                            $quitTrainingWomen,
                            'женщин',
                            'clients?project=11&profileField2=51.7Amw&profileOp2=notNull&profileDate=51.7Amw' .
                            '&profileField=51.9Dbe&profileOp=true&dscField=51.tdak&dscValue=7ij9' .
                            '&gender=female&verified=1' . $datesOnlyQuery
                        ]
                    ],
                    [
                        [
                            $finishedTraining,
                            'всего завершили обучение',
                            'clients?project=11&profileField=51.7Amw&profileOp=notNull&profileDate=51.iXCk' .
                            '&profileField2=51.iXCk&profileOp2=notNull' .
                            '&profileField3=51.792u->file&profileOp3=notNull' .
                            '&dscField=51.tdak&dscValue=7ij9' .
                            '&verified=1' . $datesOnlyQuery,
                            null,
                            route('downloadTraining') . '?role=client&project=11&profileField2=51.7Amw' .
                                '&profileOp2=notNull&profileDate=51.iXCk' .
                                '&profileField2=51.iXCk&profileOp2=notNull' .
                                '&profileField3=51.792u->file&profileOp3=notNull' .
                                '&dscField=51.tdak&dscValue=7ij9' .
                                '&verified=1' . $datesOnlyQuery
                        ],
                        [
                            $finishedTrainingMen,
                            'мужчин',
                            'clients?project=11&profileField=51.7Amw&profileOp=notNull&profileDate=51.iXCk' .
                            '&profileField2=51.iXCk&profileOp2=notNull' .
                            '&profileField3=51.792u->file&profileOp3=notNull' .
                            '&dscField=51.tdak&dscValue=7ij9' .
                            '&gender=male&verified=1' . $datesOnlyQuery
                        ],
                        [
                            $finishedTrainingWomen,
                            'женщин',
                            'clients?project=11&profileField=51.7Amw&profileOp=notNull&profileDate=51.iXCk' .
                            '&profileField2=51.iXCk&profileOp2=notNull' .
                            '&profileField3=51.792u->file&profileOp3=notNull' .
                            '&dscField=51.tdak&dscValue=7ij9' .
                            '&gender=female&verified=1' . $datesOnlyQuery
                        ]
                    ]
                ],
            ],
        ];
    }
}
