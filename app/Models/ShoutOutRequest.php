<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShoutOutRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'event_id', 'title', 'category_id', 'receiver', 'message', 'seat_number', 'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->select('id', 'first_name', 'last_name', 'profile_image');
    }
}
