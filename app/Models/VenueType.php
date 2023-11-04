<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VenueType extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'status'
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function businesses()
    {
        return $this->belongsToMany(Business::class, 'business_venue_types_table', 'venue_type_id', 'business_id');
    }
}
