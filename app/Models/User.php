<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable {
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name','email','password','role_id','phone',
        'avatar','department','is_active','last_login_at',
    ];

    protected $hidden = ['password','remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at'     => 'datetime',
        'is_active'         => 'boolean',
        'password'          => 'hashed',
    ];

    public function role(): BelongsTo { return $this->belongsTo(Role::class); }
    public function incomingGoods(): HasMany { return $this->hasMany(IncomingGood::class); }
    public function outgoingGoods(): HasMany { return $this->hasMany(OutgoingGood::class); }
    public function notifications(): HasMany { return $this->hasMany(Notification::class); }
    public function stockMovements(): HasMany { return $this->hasMany(StockMovement::class); }

    public function hasRole(string $role): bool {
        return $this->role?->name === $role;
    }

    public function hasAnyRole(array $roles): bool {
        return in_array($this->role?->name, $roles);
    }

    public function getAvatarUrlAttribute(): string {
        return $this->avatar
            ? asset('storage/' . $this->avatar)
            : 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=0ABFBC&color=fff';
    }
}
