<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{IncomingGood, OutgoingGood, Item, StockMovement, Notification, User};
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    // ==================== INCOMING ====================
    public function incomingIndex(Request $request): JsonResponse
    {
        $query = IncomingGood::with(['item:id,name,code,unit','supplier:id,name','user:id,name'])
            ->when($request->search, fn($q) => $q->whereHas('item', fn($q) => $q->where('name','like',"%{$request->search}%")->orWhere('code','like',"%{$request->search}%")))
            ->when($request->supplier_id, fn($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->date_from, fn($q) => $q->where('transaction_date', '>=', $request->date_from))
            ->when($request->date_to,   fn($q) => $q->where('transaction_date', '<=', $request->date_to))
            ->latest('transaction_date')->latest();

        return response()->json($query->paginate($request->per_page ?? 15));
    }

    public function incomingStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'item_id'         => 'required|exists:items,id',
            'supplier_id'     => 'nullable|exists:suppliers,id',
            'quantity'        => 'required|numeric|min:0.01',
            'price_per_unit'  => 'nullable|numeric|min:0',
            'transaction_date'=> 'required|date',
            'notes'           => 'nullable|string|max:500',
            'invoice_no'      => 'nullable|string|max:100',
        ]);

        return DB::transaction(function () use ($data, $request) {
            $item = Item::lockForUpdate()->findOrFail($data['item_id']);
            $stockBefore = $item->stock;
            $item->increment('stock', $data['quantity']);

            $year  = now()->format('Y');
            $month = now()->format('m');
            $count = IncomingGood::whereYear('created_at', $year)->whereMonth('created_at', $month)->count() + 1;
            $data['reference_no'] = "IN-{$year}{$month}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
            $data['user_id'] = $request->user()->id;

            $incoming = IncomingGood::create($data);

            StockMovement::create([
                'item_id'        => $item->id,
                'user_id'        => $request->user()->id,
                'type'           => 'in',
                'quantity'       => $data['quantity'],
                'stock_before'   => $stockBefore,
                'stock_after'    => $item->fresh()->stock,
                'reference_type' => 'incoming',
                'reference_id'   => $incoming->id,
                'notes'          => 'Barang masuk dari ' . ($incoming->supplier?->name ?? 'Tidak diketahui'),
            ]);

            // Notify all users if stock was low and now replenished
            $this->notifyIncoming($item->fresh(), $incoming, $request->user());

            return response()->json($incoming->load(['item','supplier','user']), 201);
        });
    }

    // ==================== OUTGOING ====================
    public function outgoingIndex(Request $request): JsonResponse
    {
        $query = OutgoingGood::with(['item:id,name,code,unit','user:id,name'])
            ->when($request->search, fn($q) => $q->whereHas('item', fn($q) => $q->where('name','like',"%{$request->search}%")))
            ->when($request->date_from, fn($q) => $q->where('transaction_date', '>=', $request->date_from))
            ->when($request->date_to,   fn($q) => $q->where('transaction_date', '<=', $request->date_to))
            ->latest('transaction_date')->latest();

        return response()->json($query->paginate($request->per_page ?? 15));
    }

    public function outgoingStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'item_id'          => 'required|exists:items,id',
            'quantity'         => 'required|numeric|min:0.01',
            'destination'      => 'required|string|max:255',
            'purpose'          => 'nullable|string|max:255',
            'transaction_date' => 'required|date',
            'notes'            => 'nullable|string|max:500',
            'requested_by'     => 'nullable|string|max:100',
        ]);

        return DB::transaction(function () use ($data, $request) {
            $item = Item::lockForUpdate()->findOrFail($data['item_id']);

            if ($item->stock < $data['quantity']) {
                return response()->json([
                    'message' => "Stok tidak mencukupi. Stok tersedia: {$item->stock} {$item->unit}.",
                    'errors'  => ['quantity' => ["Stok tersedia hanya {$item->stock} {$item->unit}."]],
                ], 422);
            }

            $stockBefore = $item->stock;
            $item->decrement('stock', $data['quantity']);

            $year  = now()->format('Y');
            $month = now()->format('m');
            $count = OutgoingGood::whereYear('created_at', $year)->whereMonth('created_at', $month)->count() + 1;
            $data['reference_no'] = "OUT-{$year}{$month}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
            $data['user_id'] = $request->user()->id;

            $outgoing = OutgoingGood::create($data);
            $itemFresh = $item->fresh();

            StockMovement::create([
                'item_id'        => $item->id,
                'user_id'        => $request->user()->id,
                'type'           => 'out',
                'quantity'       => $data['quantity'],
                'stock_before'   => $stockBefore,
                'stock_after'    => $itemFresh->stock,
                'reference_type' => 'outgoing',
                'reference_id'   => $outgoing->id,
                'notes'          => 'Barang keluar ke ' . $data['destination'],
            ]);

            // Check low stock
            if ($itemFresh->stock <= $itemFresh->min_stock) {
                $this->notifyLowStock($itemFresh);
            }

            return response()->json($outgoing->load(['item','user']), 201);
        });
    }

    private function notifyLowStock(Item $item): void
    {
        $users = User::whereHas('role', fn($q) => $q->whereIn('name', ['admin','manager']))->get();
        foreach ($users as $user) {
            Notification::create([
                'user_id' => $user->id,
                'type'    => 'low_stock',
                'title'   => 'Stok Menipis: ' . $item->name,
                'message' => "Stok {$item->name} ({$item->code}) saat ini {$item->stock} {$item->unit}, di bawah minimum {$item->min_stock} {$item->unit}.",
                'data'    => ['item_id' => $item->id, 'stock' => $item->stock, 'min_stock' => $item->min_stock],
            ]);
        }
    }

    private function notifyIncoming(Item $item, IncomingGood $incoming, User $actor): void
    {
        $users = User::whereHas('role', fn($q) => $q->whereIn('name', ['admin','manager']))->where('id', '!=', $actor->id)->get();
        foreach ($users as $user) {
            Notification::create([
                'user_id' => $user->id,
                'type'    => 'incoming',
                'title'   => 'Barang Masuk: ' . $item->name,
                'message' => "{$incoming->quantity} {$item->unit} {$item->name} telah masuk ke gudang oleh {$actor->name}.",
                'data'    => ['item_id' => $item->id, 'quantity' => $incoming->quantity],
            ]);
        }
    }
}
