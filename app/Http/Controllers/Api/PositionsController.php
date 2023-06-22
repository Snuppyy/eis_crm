<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Lib\Etc;
use Illuminate\Http\Request;

use App\Models\ProjectUser;

class PositionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $items = ProjectUser::whereNotNull('position')
            ->groupBy('user_id', 'position')
            ->when(
                $projects = $request->projects,
                function ($query) use ($projects) {
                    $query->where(function ($query) use ($projects) {
                        $query->whereIn('project_id', $projects);
                    });
                }
            )
            ->orderBy('order');

        if (!in_array('superuser', $request->user()->roles)) {
            $items->where(function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);

                $userId = $request->user()->id;

                if (in_array($userId, array_keys(Etc::$employeesByManager))) {
                    $usersPerProjects = Etc::$employeesByManager[$userId];

                    foreach ($usersPerProjects as $projectId => $userIds) {
                        $query->orWhere(function ($query) use ($projectId, $userIds) {
                            $query->where('project_id', $projectId)
                                ->whereHas('user', function ($query) use ($userIds) {
                                    $query->where('roles', 'not like', '%superuser%');

                                    if (count($userIds)) {
                                        $query->whereIn('user_id', $userIds);
                                    }
                                });
                        });
                    }
                }
            });
        }

        return $items->get();
    }
}
