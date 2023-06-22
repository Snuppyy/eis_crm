<?php

namespace App\Models;

class ProjectUser extends Model
{
    protected $table = 'project_user';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'location_id'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'terminated_at' => 'datetime'
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    public function userActivities()
    {
        return $this->hasMany(UserActivity::class);
    }
}
