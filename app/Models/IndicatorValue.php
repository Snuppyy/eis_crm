<?php

namespace App\Models;

class IndicatorValue extends Model
{

     /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'project_id',
        'location_ids',
        'user_ids',
        'period_start',
        'period_end',
        'outdated',
        'views',
        'last_viewed_at',
        'data'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'last_viewed_at' => 'datetime',
        'invalidated_at' => 'datetime',
        'data' => 'array'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
