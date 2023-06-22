<?php

namespace App\Models;

class Location extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'name'
    ];

    public function projectUsers()
    {
        return $this->hasMany(ProjectUser::class);
    }
}
