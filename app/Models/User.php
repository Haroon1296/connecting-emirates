<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'user_type',
        'password',
        'profile_image',
        'phone_number',
        'specialty',
        'bio',
        'zip_code',
        'is_profile_complete',
        'device_type',
        'device_token',
        'social_type',
        'social_token',
        'is_forgot',
        'push_notification',
        'is_verified',
        'is_social',
        'verified_code',
        'is_active',
        'is_blocked'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function scopeOtpVerified($query)
    {
        return $query->where('is_verified', '1');
    }
    
    public function venue_type()
    {
        return $this->belongsToMany(VenueType::class, 'business_venue_types', 'business_id', 'venue_type_id');
    }
}
