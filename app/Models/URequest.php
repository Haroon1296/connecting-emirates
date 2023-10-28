<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class URequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'event_id', 'title', 'song', 'receiver', 'message', 'seat_number', 'thumbnail', 'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->select('id', 'first_name', 'last_name', 'profile_image');
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id')->select('id', 'title', 'description', 'date_time', 'thumbnail', 'qr_code', 'code', 'created_at');
    }
}
