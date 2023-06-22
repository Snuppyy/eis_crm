<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Traits\UnescapedUnicodeJson;
use Faker\Provider\UserAgent;
use Staudenmeir\EloquentEagerLimit\HasEagerLimit;

use App\Lib\Etc;

class User extends Authenticatable
{
    use UnescapedUnicodeJson, Notifiable, HasEagerLimit;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'phone', 'password', 'roles'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'profile' => 'array'
    ];

    protected $with = [];

    public function getRolesAttribute()
    {
        return array_filter(explode(',', $this->attributes['roles']));
    }

    public function setRolesAttribute($roles)
    {
        $this->attributes['roles'] = is_array($roles) ? implode(',', $roles) : $roles;
    }

    public function getProfileAttribute()
    {
        return $this->documents->groupBy('form_id');
    }

    public function getLegacyProfileAttribute()
    {
        return json_decode($this->attributes['profile'], true);
    }

    public function activities()
    {
        return $this->belongsToMany(Activity::class, 'user_activity')
            ->using(UserActivity::class);
    }

    public function activities2()
    {
        return $this->activities();
    }

    public function activities3()
    {
        return $this->activities();
    }

    public function activities4()
    {
        return $this->activities();
    }

    public function activities5()
    {
        return $this->activities();
    }

    public function activities6()
    {
        return $this->activities();
    }

    public function activities7()
    {
        return $this->activities();
    }

    public function screenings()
    {
        return $this->activities()
            ->wherePivot('part_id', 365)
            ->where(function ($query) {
                $keyword = 'скрининг';

                $query->where('title', 'like', "%$keyword%")
                    ->orWhere('description', 'like', "%$keyword%")
                    ->orWhereHas('timings', function ($query) use ($keyword) {
                        $query->where('comment', 'like', "%$keyword%");
                    });
            })
            ->select(['id', 'start_date'])
            ->orderBy('start_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->limit(1);
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class)->groupBy('project_id');
    }

    public function projectsUsers()
    {
        return $this->hasMany(ProjectUser::class);
    }

    public function parts()
    {
        return $this->belongsToMany(Part::class, 'project_user')
            ->withPivot(['id', 'position', 'order']);
    }

    public function currentActivity()
    {
        return $this->hasOne(Activity::class)->where('ongoing', 1);
    }

    public function location()
    {
        return $this->hasOneThrough(Location::class, ProjectUser::class, 'user_id', 'id', 'id', 'location_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_user', 'related_user_id')
            ->withTimestamps();
    }

    public function relatedUsers()
    {
        return $this->belongsToMany(User::class, 'user_user', 'user_id', 'related_user_id')
            ->withTimestamps();
    }

    public function timings()
    {
        return $this->hasManyThrough(Timing::class, Activity::class, 'user_id');
    }

    public function projectUserTimings()
    {
        return $this->hasManyThrough(Timing::class, ProjectUser::class, 'user_id');
    }

    public function documents()
    {
        return $this->belongsToMany(Document::class)
            ->whereNotNull('approved_at')
            ->orderByDesc('approved_at')
            ->orderByDesc('id');
    }

    public function verifiedUsers($type = 'timing')
    {
        return $this->belongsToMany(User::class, 'user_verified_user', 'user_id', 'verified_user_id')
            ->withPivot(['project_id'])
            ->where('user_verified_user.type', $type);
    }

    public function verifiedActivityUsers()
    {
        return $this->verifiedUsers('activity');
    }

    public function verifyingUsers($type = 'timing')
    {
        return $this->belongsToMany(User::class, 'user_verified_user', 'verified_user_id', 'user_id')
            ->withPivot(['project_id'])
            ->where('user_verified_user.type', $type);
    }

    public function getCanAttribute()
    {
        return [
            'filter_timings_by_user' => in_array('superuser', $this->roles) || in_array($this->id, [90, 2449, 4545, 6849]),
            'list_services_by_user' => in_array($this->id, array_keys(Etc::$employeesListedByManager))
        ];
    }
}
