<?php
namespace App\Models;
use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Item extends Model {
    use SoftDeletes;
    protected $fillable = [
        'code','name','category_id','supplier_id','unit','stock',
        'min_stock','max_stock','price','location','image','barcode','description','is_active'
    ];
    protected $casts = [
        'stock' => 'decimal:2', 'min_stock' => 'decimal:2',
        'max_stock' => 'decimal:2', 'price' => 'decimal:2', 'is_active' => 'boolean'
    ];
    protected $appends = ['status','image_url'];

    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function stockMovements(): HasMany { return $this->hasMany(StockMovement::class)->latest(); }
    public function incomingGoods(): HasMany { return $this->hasMany(IncomingGood::class); }
    public function outgoingGoods(): HasMany { return $this->hasMany(OutgoingGood::class); }

    public function getStatusAttribute(): string {
        if ($this->stock <= 0) return 'empty';
        if ($this->stock <= $this->min_stock) return 'low';
        return 'normal';
    }

    public function getImageUrlAttribute(): string {
        return $this->image
            ? asset('storage/' . $this->image)
            : 'https://via.placeholder.com/100x100?text=' . urlencode($this->code);
    }

    public static function generateCode(string $categoryCode): string {
        $last = static::withTrashed()
            ->where('code', 'like', $categoryCode . '-%')
            ->orderByDesc('id')->first();
        $num = $last ? ((int) substr($last->code, strlen($categoryCode) + 1)) + 1 : 1;
        return $categoryCode . '-' . str_pad($num, 3, '0', STR_PAD_LEFT);
    }
}
