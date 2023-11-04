<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessVenueType extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id', 'venue_type_id'
    ];

    public function venue_type()
    {
        return $this->belongsTo(VenueType::class, 'venue_type_id')->select('id', 'title');
    }
}
