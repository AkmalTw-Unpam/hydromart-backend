<?php
namespace App\Models;
use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomingGood extends Model {
    use SoftDeletes;
    protected $fillable = [
        'reference_no','item_id','supplier_id','user_id',
        'quantity','price_per_unit','transaction_date','notes','invoice_no'
    ];
    protected $casts = [
        'quantity' => 'decimal:2',
        'price_per_unit' => 'decimal:2',
        'transaction_date' => 'date',
    ];
    protected $appends = ['total_value'];

    public function item(): BelongsTo { return $this->belongsTo(Item::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function getTotalValueAttribute(): float { return $this->quantity * $this->price_per_unit; }
}
