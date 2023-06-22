<?php

namespace App\Models;

use Auth;
use Staudenmeir\EloquentEagerLimit\HasEagerLimit;
use Kalnoy\Nestedset\NodeTrait;

class Activity extends Model
{
    use HasEagerLimit,
        NodeTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'project_id', 'parent_id',
        'status', 'start_date', 'start_time',
        'end_date', 'end_time',
        'title', 'description', 'forms',
        'tree', 'location_id', 'verified', 'volunteering'
    ];

    protected $casts = [
        'forms' => 'array'
    ];

    protected $with = [];

    protected $appends = [
        'verified_timing',
        'verifiable',
        'deletable'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function verifiers()
    {
        return $this->morphToMany(User::class, 'verifiable', 'verifications')
            ->withTimestamps()
            ->orderByPivot('updated_at');
    }

    public function allUsers()
    {
        return $this->hasMany(UserActivity::class);
    }

    public function clients()
    {
        return $this->allUsers()->where('role', 'client');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_activity');
    }

    public function timings()
    {
        return $this->hasMany(Timing::class);
    }

    public function verifiedTimingRelation()
    {
        return $this->hasOne(Timing::class)
            ->where('verified', true);
    }

    public function frozenTiming()
    {
        return $this->hasOne(Timing::class)
            ->where('frozen', 1);
    }

    public function parts()
    {
        return $this->belongsToMany(Part::class, 'user_activity')->withPivot('user_id');
    }

    protected function getScopeAttributes()
    {
        return ['tree', 'project_id'];
    }

    protected function getVerifiedTimingAttribute()
    {
        return $this->tree && !in_array(request()->user()->id, [1, 2, 5]) ? 1 : $this->verifiedTimingRelation;
    }

    public function getEditableAttribute()
    {
        $user = Auth::user();
        return !$this->frozenTiming
            && (!$this->verifiedTiming || 5 == $user->id)
            && ($this->user_id == $user->id
                || $user->verifiedUsers
                        ->where('pivot.project_id', $this->project_id)
                        ->where('id', $this->user_id)
                        ->count()
                || in_array('superuser', $user->roles));
    }

    public function getVerifiableAttribute()
    {
        $user = Auth::user();
        return (!$this->verifiers->where('id', $user->id)->count()
            && !!$user->verifiedActivityUsers
                    ->where('pivot.project_id', $this->project_id)
                    ->where('id', $this->user_id)->count()
            ) || ($this->verifiers->count() && in_array($user->id, [5, 6721]));
    }

    public function getDeletableAttribute()
    {
        $user = Auth::user();

        return 5 == $user->id || (
            !$this->verifiedTiming && !$this->frozenTiming && (
                in_array('superuser', $user->roles)
                || $user->id == $this->user_id
                || !!$user->verifiedUsers
                    ->where('pivot.project_id', $this->project_id)
                    ->where('id', $this->user_id)->count()
            )
        );
    }
}
