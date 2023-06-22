<?php

namespace App\Models;

class Project extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'status'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function projectUsers()
    {
        return $this->hasMany(ProjectUser::class);
    }
}
