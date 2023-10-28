<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favourite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'record_id', 'type'
    ];

    public function hats()
    {
        return $this->hasMany(User::class, 'id', 'record_id');
    }

    public function events()
    {
        return $this->hasMany(Event::class, 'id', 'record_id');
    }
}
