<?php
namespace App\Models;
use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model {
    protected $fillable = [
        'item_id','user_id','type','quantity',
        'stock_before','stock_after','reference_type','reference_id','notes'
    ];
    protected $casts = [
        'quantity' => 'decimal:2',
        'stock_before' => 'decimal:2',
        'stock_after' => 'decimal:2',
    ];
    public function item(): BelongsTo { return $this->belongsTo(Item::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}

// ---- IncomingGood ----
