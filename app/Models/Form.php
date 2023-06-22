<?php

namespace App\Models;

class Form extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'roles', 'schema'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'schema' => 'array'
    ];

    public function getRolesAttribute()
    {
        return array_filter(explode(',', $this->attributes['roles']));
    }

    public function setRolesAttribute($roles)
    {
        $this->attributes['roles'] = is_array($roles) ? implode(',', $roles) : $roles;
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class);
    }
}
