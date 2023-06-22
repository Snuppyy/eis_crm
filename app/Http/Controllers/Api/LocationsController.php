<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Location;

class LocationsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $per_page = (int) $request->input('itemsPerPage');

        $items = Location::when(
            $search = $request->search,
            function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "$search%");
                });
            }
        );

        if (!in_array('superuser', $request->user()->roles)) {
            $items->whereHas('projectUsers', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            });
        }

        if ($request->forProjects) {
            foreach ($request->forProjects as $project) {
                $items->whereHas('projectUsers', function ($query) use ($project) {
                    $query->where('project_id', $project);
                });
            }
        }

        if ($request->projects) {
            $items->whereHas('projectUsers', function ($query) use ($request) {
                $query->whereIn('project_id', $request->projects);
            });
        }

        foreach ($request->input('sortBy', ['created_at']) as $index => $order) {
            $items->orderBy($order, isset($request->sortDesc[$index]) &&
                $request->sortDesc[$index] ? 'desc' : 'asc');
        }

        return $items->paginate($per_page != -1 ? $per_page : $items->count());
    }
}
