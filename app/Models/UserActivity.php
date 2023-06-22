<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class UserActivity extends Pivot
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function timings()
    {
        return $this->hasManyThrough(Timing::class, Activity::class);
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    public function projectUser()
    {
        return $this->belongsTo(ProjectUser::class);
    }
}
