<?php

namespace App\Models;

class Part extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'description', 'description_past', 'type'
    ];

    public function activities()
    {
        return $this->belongsToMany(Activity::class, 'user_activity');
    }

    public function timings()
    {
        return $this->hasManyThrough(Timing::class, UserActivity::class, 'part_id', 'project_user_id', 'id', 'project_user_id');
    }

    public function projectUsers()
    {
        return $this->hasMany(ProjectUser::class);
    }
}
