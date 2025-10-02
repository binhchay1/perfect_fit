<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'country',
        'province',
        'district',
        'ward',
        'address',
        'ip_address',
        'role',
        'postal_code',
        'phone',
        'status',
        'profile_photo_path',
        'email_verified_at',
        'facebook_id',
        'google_id',
        'tiktok_id',
        'avatar_url',
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
        'password' => 'hashed',
        'status' => 'integer',
    ];

    /**
     * Get the verification tokens for the user.
     */
    public function verificationTokens()
    {
        return $this->hasMany(UserVerify::class);
    }

    /**
     * Get the latest valid verification token.
     */
    public function getLatestValidToken()
    {
        return $this->verificationTokens()
            ->valid()
            ->latest()
            ->first();
    }

    /**
     * Get the cart for the user.
     */
    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    /**
     * Get the wishlist items for the user.
     */
    public function wishlistItems()
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Get the orders for the user.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the devices for the user.
     */
    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }

    /**
     * Get active devices for the user.
     */
    public function activeDevices()
    {
        return $this->hasMany(UserDevice::class)->where('is_active', true);
    }

    /**
     * Get trusted devices for the user.
     */
    public function trustedDevices()
    {
        return $this->hasMany(UserDevice::class)->where('is_trusted', true);
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is regular user.
     */
    public function isUser(): bool
    {
        return $this->role === 'user' || $this->role === null;
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function paymentAccounts()
    {
        return $this->hasMany(PaymentAccount::class);
    }

    public function bodyMeasurements()
    {
        return $this->hasOne(UserBodyMeasurement::class);
    }

    public function orderReturns()
    {
        return $this->hasMany(OrderReturn::class);
    }
}