<?php
namespace App\Models;
use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutgoingGood extends Model {
    use SoftDeletes;
    protected $fillable = [
        'reference_no','item_id','user_id','quantity',
        'destination','purpose','transaction_date','notes','requested_by'
    ];
    protected $casts = ['quantity' => 'decimal:2', 'transaction_date' => 'date'];

    public function item(): BelongsTo { return $this->belongsTo(Item::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
