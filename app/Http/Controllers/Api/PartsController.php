<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Part;
use App\Models\User;

class PartsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->project) {
            $user = User::with(['parts' => function ($query) use ($request) {
                $query->wherePivot('project_id', $request->project)
                    ->orderBy('project_user.order')
                    ->orderBy('project_user.id');
            }])->find($request->user);

            return $user->parts;
        } else {
            $items = Part::orderBy('description')->where('type', 0);

            if ($request->forProjects) {
                $items->whereHas('projectUsers', function ($query) use ($request) {
                    $query->whereIn('project_id', $request->forProjects);
                });
            }
            return $items->get();
        }
    }
}
