<?php

namespace App\Models;

use App\Enums\Event\RequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'event_type_id', 'title', 'description', 'date_time', 'thumbnail', 'venue_name', 'venue_address', 'latitude', 'longitude', 'city', 'state', 'zip_code', 'code'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function event_type()
    {
        return $this->belongsTo(EventType::class, 'event_type_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'event_id')->latest()->select(['id', 'user_id', 'event_id', 'comment', 'created_at']);
    }

    public function interested_people()
    {
        return $this->hasMany(EventInterestedPeople::class, 'event_id');
    }

    public function event_options()
    {
        return $this->hasMany(EventOption::class, 'event_id')->select(['id', 'event_id', 'type', 'capacity', 'notes']);
    }

    public function coming_user()
    {
        return $this->hasMany(EventComingUser::class, 'event_id');
    }

    public function u_request()
    {
        return $this->hasMany(URequest::class, 'event_id')->where('status', RequestStatus::ACCEPT->value);
    }

    public function shout_out_request()
    {
        return $this->hasMany(ShoutOutRequest::class, 'event_id')->where('status', RequestStatus::ACCEPT->value);
    }
}
