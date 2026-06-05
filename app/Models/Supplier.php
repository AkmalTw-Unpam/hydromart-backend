<?php
namespace App\Models;
use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model {
    use SoftDeletes;
    protected $fillable = ['code','name','contact_person','phone','email','address','city','is_active','notes'];
    protected $casts = ['is_active' => 'boolean'];
    public function items(): HasMany { return $this->hasMany(Item::class); }
    public function incomingGoods(): HasMany { return $this->hasMany(IncomingGood::class); }
}
