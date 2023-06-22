<?php

namespace App\Lib;

use DB;

use App\Lib\Exceptions\MergeUserHasTimingsException;
use App\Models\Part;
use App\Models\Project;
use App\Models\User;

class UserMerger
{
    public static function mergeUser(User $source, User $destination)
    {
        if ($source->id === $destination->id) {
            return;
        }

        if (DB::table('timings as t')
                ->where('user_id', $source->id)
                ->count()
            ||
            DB::table('timings as t')
                ->join('project_user as pu', 'pu.id', '=', 't.project_user_id')
                ->where('pu.user_id', $source->id)
                ->count()
        ) {
            throw new MergeUserHasTimingsException("Found timings for {$source->id}!");
        }

        $projects = $source->projects()->where(function ($query) {
            $query->whereExists(function ($query) {
                $query->from('user_activity')
                    ->join('activities', 'id', 'activity_id')
                    ->whereColumn('user_activity.user_id', 'project_user.user_id')
                    ->whereColumn('activities.project_id', 'projects.id');
            });
        })->withPivot('location_id')->get();

        foreach ($projects as $project) {
            $parts = Part::where('type', 0)
                ->whereHas('projectUsers', function ($query) use ($project) {
                    $query->where('project_id', $project->id);
                })
                ->whereDoesntHave('projectUsers', function ($query) use ($project, $destination) {
                    $query->where('project_id', $project->id)
                        ->where('user_id', $destination->id);
                })
                ->get('id');

            foreach ($parts as $part) {
                $destination->parts()->attach($part->id, [
                    'project_id' => $project->id,
                    'location_id' => $project->pivot->location_id
                ]);
            }
        }

        $count = DB::table('user_activity as ua')
            ->join('activities as a', 'a.id', '=', 'ua.activity_id')
            ->join('project_user as pu', function ($query) use ($destination) {
                $query->on('pu.project_id', '=', 'a.project_id')
                    ->on('pu.part_id', '=', 'ua.part_id')
                    ->where('pu.user_id', $destination->id);
            })
            ->where('ua.user_id', $source->id)
            ->update([
                'ua.user_id' => $destination->id,
                'ua.project_user_id' => DB::raw('pu.id')
            ]);

        foreach ($source->documents as $document) {
            if (empty($document->form->schema['multiple'])
                && !empty($destination->profile[$document->form_id])
            ) {
                $document->original_id = $destination->profile[$document->form_id][0]->original_id;
                $document->timestamps = false;
                $document->save();
            }

            $document->users()->detach($source->id);
            $document->users()->attach($destination->id);
        }

        $source->delete();

        if (empty($destination->phone)) {
            $destination->phone = $source->phone;
            $destination->save();
        }

        if (empty($destination->email)) {
            $destination->email = $source->email;
            $destination->save();
        }

        return $count;
    }
}
