<?php
namespace App\Models;
use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Category extends Model {
    use SoftDeletes;
    protected $fillable = ['name','code','color','description','is_active'];
    protected $casts = ['is_active' => 'boolean'];
    public function items(): HasMany { return $this->hasMany(Item::class); }
    public function getItemsCountAttribute(): int { return $this->items()->count(); }
}
