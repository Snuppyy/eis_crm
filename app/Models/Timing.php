<?php

namespace App\Models;

use Auth;
use Staudenmeir\EloquentEagerLimit\HasEagerLimit;
use App\Lib\Etc;
use Faker\Provider\UserAgent;

class Timing extends Model
{
    use HasEagerLimit;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'activity_id',
        'project_user_id',
        'comment',
        'volunteering',
        'began_at',
        'ended_at',
        'timing',
        'flagged',
        'frozen',
        'note'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'volunteering' => 'boolean',
        'began_at' => 'datetime',
        'ended_at' => 'datetime',
        'verified' => 'boolean',
        'flagged' => 'boolean',
        'flagged_at' => 'datetime',
        'frozen' => 'boolean',
        'frozen_at' => 'datetime'
    ];

    protected $appends = [
        'verifiable',
        'unverifiable',
        'flaggable'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function projectUser()
    {
        return $this->belongsTo(ProjectUser::class);
    }

    public function verifiers()
    {
        return $this->morphToMany(User::class, 'verifiable', 'verifications')
            ->withTimestamps()
            ->orderByPivot('updated_at');
    }

    public function authorTiming()
    {
        return $this->hasOneThrough(Timing::class, Activity::class, 'id', 'activity_id', 'activity_id')
            ->whereColumn('timings.user_id', '=', 'activities.user_id');
    }

    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    protected function canBeVerified()
    {
        $user = Auth::user();

        return $this->projectUser && $this->projectUser->user_id &&
            (in_array($this->projectUser->project_id, [4, 6, 13, 15])
                ? $user->verifiedUsers
                    ->where('pivot.project_id', $this->projectUser->project_id)
                    ->where('id', $this->projectUser->user_id)->count()
                : (in_array('superuser', $this->projectUser->user->roles)
                        ? in_array($user->id, [1, 2]) : in_array('superuser', $user->roles)));
    }

    protected function canVerify()
    {
        $user = Auth::user();
        return $user && ($user->id == 5 || ($this->canBeVerified() && !$this->frozen));
    }

    public function getVerifiableAttribute()
    {
        $user = Auth::user();
        return $user && $this->canVerify() && !$this->verifiers->where('id', $user->id)->count();
    }

    public function getUnverifiableAttribute()
    {
        $user = Auth::user();
        return $user && $this->canVerify() && ($this->verified || $this->verifiers->count())
            && !$this->requests->where('status', 'requested')->where('user_id', $user->id)->count();
    }

    public function getFlaggableAttribute()
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        $userId = $user->id;

        return $userId == 5 || ($this->canBeVerified()
            && (
                !$this->frozen ||
                $this->verifiers->where('id', $userId)->count()) ||
                ($userId == 90 && $this->projectUser && $this->projectUser->project_id == 6 &&
                    in_array($this->projectUser->user_id, Etc::$employeesByManager[90][6]))
            );
    }
}
