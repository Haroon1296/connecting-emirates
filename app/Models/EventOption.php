<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id', 'type', 'capacity', 'notes'
    ];

    protected $hidden = [
        'event_id'
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
}
