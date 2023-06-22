<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Lib\Etc;
use Illuminate\Http\Request;

use App\Models\Project;

class ProjectsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $per_page = (int) $request->input('itemsPerPage');

        $items = Project::when(
            $search = $request->search,
            function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "$search%");
                });
            }
        );

        if (!in_array('superuser', $request->user()->roles)) {
            $items->whereHas('users', function ($query) use ($request) {
                $query->where(function ($query) {
                        $query->whereNull('terminated_at')
                            ->orWhere('terminated_at', '>', now());
                    })
                    ->where(function ($query) use ($request) {
                        $userId = $request->user()->id;

                        $query->where('users.id', $userId);

                        if (isset(Etc::$employeesListedByManager[$userId])) {
                            $usersPerProjects = Etc::$employeesListedByManager[$userId];

                            foreach ($usersPerProjects as $projectId => $userIds) {
                                $query->orWhere(function ($query) use ($projectId, $userIds) {
                                    $query->where('project_id', $projectId);

                                    if (count($userIds)) {
                                        $query->whereIn('users.id', $userIds);
                                    }
                                });
                            }
                        }
                    });
            });
        }

        if ($request->forUser) {
            $items->whereHas('projectUsers', function ($query) {
                $query->whereHas('part', function ($query) {
                    $query->where('type', 0);
                });
            });

            if ($request->forUser != 'add') {
                $items->with([
                    'projectUsers' => function ($query) use ($request) {
                        $query->whereHas('userActivities', function ($query) use ($request) {
                            $query->where('user_id', $request->forUser);
                        });
                    },
                ]);
            }
        }

        if ($request->indicators) {
            $items->whereIn('id', [6, 7, 11, 12]);
        }

        foreach ($request->input('sortBy', ['created_at']) as $index => $order) {
            $items->orderBy($order, isset($request->sortDesc[$index]) &&
                $request->sortDesc[$index] ? 'desc' : 'asc');
        }

        return $items->paginate($per_page != -1 ? $per_page : $items->count());
    }
}
