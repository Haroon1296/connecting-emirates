<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id', 'title', 'brand_name', 'model_nmae', 'category', 'description', 'amount', 'stock_quantity', 'discount', 'promotion_period_start_at', 'promotion_period_end_at'
    ];
}
