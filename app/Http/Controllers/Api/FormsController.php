<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use PhpOffice\PhpWord\TemplateProcessor;

use App\Http\Requests\Forms\StoreForm;
use App\Http\Requests\Forms\UpdateForm;

use App\Models\Form;
use App\Models\Location;
use App\Models\Part;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;

use Carbon\Carbon;
use PDO;
use Storage;

class FormsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $per_page = (int) $request->input('itemsPerPage');

        $items = Form::when(
            $search = $request->search,
            function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "$search%");
                });
            }
        );

        if ($request->has('page') && !in_array($request->user()->id, [4, 5])) {
            $items->whereIn('id', $request->user()->id == 65 ? [9, 10, 51, 53] : []);
        }

        if (!empty($request->role)) {
            $items->where('roles', 'like', "%{$request->role}%");
        }

        if ($request->has('forProjects')) {
            if ($per_page == -1) {
                $items->select('forms.*')
                    ->leftJoin('form_project', 'form_id', '=', 'id')
                    ->where(function ($query) use ($request) {
                        $query->whereNull('project_id');

                        if (!empty($request->forProjects)) {
                            $query->orWhereIn('project_id', $request->forProjects);
                        }
                    })
                    ->with('projects:id')
                    ->orderByDesc('order');
            }
        } else {
            if (!empty($request->projects)) {
                $items->whereHas('projects', function ($query) use ($request) {
                    $query->whereIn('id', $request->projects);
                });
            }

            $items->with('projects');
        }

        $itemsCount = $items->count('id');
        $items->groupBy('id');

        foreach ($request->input('sortBy', ['created_at']) as $index => $order) {
            $items->orderBy($order, isset($request->sortDesc[$index]) &&
                $request->sortDesc[$index] ? 'desc' : 'asc');
        }

        return $items->paginate($per_page != -1 ? $per_page : $itemsCount);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreForm  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreForm $request)
    {
        $data = $request->validated();

        $form = Form::create($data);

        return ['id' => $form->id];
    }

    /**
     * Display the specified resource.
     *
     * @param  Form  $form
     * @return \Illuminate\Http\Response
     */
    public function show(Form $form)
    {
        return $form;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateForm  $request
     * @param  Form  $form
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateForm $request, Form $form)
    {
        $data = $request->validated();

        $form->update($data);

        return $form;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Form  $form
     * @return \Illuminate\Http\Response
     */
    public function destroy(Form $form)
    {
        $form->delete();
    }

    public function downloadDocument(Project $project, User $user, Form $form, $document)
    {
        if (empty($user->profile[$form->id][$document])) {
            abort(404);
        }

        $doc = $user->profile[$form->id][$document];

        switch ($doc['i9qR']) {
            case 'waGs':
                $template = new TemplateProcessor(resource_path('doc/contract1.docx'));

                break;

            case 'nEee':
                $template = new TemplateProcessor(resource_path('doc/contract2.docx'));

                break;

            case 'bTTv':
                $template = new TemplateProcessor(resource_path('doc/contract3.docx'));

                break;
        }

        setlocale(LC_TIME, 'ru_RU.utf8');

        $data = array_merge(
            $user->profile[1],
            $doc
        );

        $total = 0;

        $parts = Part::where('type', 1)
            ->whereHas('projectUsers', function ($query) use ($project, $user, $data) {
                $query->where('project_id', $project->id)
                    ->where('user_id', $user->id)
                    ->where('position', $data['e8Mq']);
            })
            ->with(['projectUsers' => function ($query) use ($project, $user, $data) {
                $query->where('project_id', $project->id)
                    ->where('user_id', $user->id)
                    ->where('position', $data['e8Mq']);
            }])
            ->get()
            ->map(function ($part, $index) use (&$total) {
                $total += $part->projectUsers[0]->cost;

                return [
                    'index' => $index + 1,
                    'description' => $part->description,
                    'cost' => number_format($part->projectUsers[0]->cost, 0, '.', ' ')
                ];
            })
            ->toArray();

        $template->cloneBlock(
            'part',
            0,
            true,
            false,
            $parts
        );

        $template->cloneBlock(
            'part2',
            0,
            true,
            false,
            $parts
        );

        $name = explode(' ', $user->name);
        $name_short = $name[0] . ' ' . mb_substr($name[1], 0, 1) . '.' . (isset($name[2]) ? ' ' . mb_substr($name[2], 0, 1) . '.' : '');

        $formatter = new \NumberFormatter('ru', \NumberFormatter::SPELLOUT);

        $projectUser = ProjectUser::where('project_id', $project->id)
            ->where('position', $data['e8Mq'])
            ->firstOrFail();

        $data = array_merge(
            $data,
            [
                'name' => $user->name,
                'name_short' => $name_short,
                'date' => Carbon::parse($data['date'])->formatLocalized('«%-e» %B %Y'),
                'EDNs' => substr($data['EDNs'], 0, 4),
                'e8Mq' => mb_strtolower($data['e8Mq']),
                'dNMu' => !empty($data['dNMu']) ? ($data['dNMu'] == 'uWTF' ? 'неполную' : 'полную') : null,
                'project' => $project->description,
                'location' => $projectUser->location->name,
                '48m5' => Carbon::parse($data['48m5'])->formatLocalized('«%-e» %B %Y'),
                'aMQA' => Carbon::parse($data['aMQA'] ?? $data['date'])->formatLocalized('«%-e» %B %Y'),
                'ntpP' => !empty($data['ntpP']) ? Carbon::parse($data['ntpP'])->formatLocalized('«%-e» %B %Y') : null,
                'WRN9_spell' => !empty($data['ntpP']) ? $formatter->format($data['WRN9']) : null,
                'LGLM_spell' => !empty($data['LGLM']) ? $formatter->format($data['LGLM']) : null,
                'total' => number_format($total, 0, '.', ' ')
            ]
        );

        $template->setValues($data);

        $filename = $doc['8ake'] . '.docx';
        $template->saveAs(storage_path('app/public/' . $filename));

        return Storage::download($filename, $filename);
    }
}
