<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'email_verified_at',
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

    protected $attributes = [
        'active' => 0,

    ];

    public function hasVerifiedEmail()
    {
        $autoVerify = env('AUTO_VERIFY_EMAIL');
        if ($autoVerify !== null) {
            $isAuto = filter_var($autoVerify, FILTER_VALIDATE_BOOLEAN);
        } else {
            // Si el driver es log o no hay credenciales SMTP configuradas, auto-verificar
            $isAuto = env('MAIL_MAILER', 'log') === 'log' || empty(env('MAIL_USERNAME'));
        }

        if ($isAuto) {
            if (is_null($this->email_verified_at)) {
                $this->email_verified_at = $this->freshTimestamp();
                $this->saveQuietly();
            }
            return true;
        }

        return ! is_null($this->email_verified_at);
    }

    public function orders()
    {
        return  $this->hasMany(Order::class);
    }

    public function payments()
    {
        return $this->orders();
    }

    public function events()
    {
        return  $this->hasMany(Event::class);
    }
    public function locations()
    {
        return  $this->hasMany(Location::class);
    }
}
