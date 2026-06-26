<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Item, Category, StockMovement};
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\{Storage, DB};

class ItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Item::with(['category:id,name,color', 'supplier:id,name'])
            ->when($request->search, fn($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%");
            }))
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->status === 'low', fn($q) => $q->whereColumn('stock', '<=', 'min_stock'))
            ->when($request->status === 'empty', fn($q) => $q->where('stock', '<=', 0))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')));

        $sortField = in_array($request->sort, ['name','code','stock','price','created_at']) ? $request->sort : 'created_at';
        $sortDir   = $request->dir === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortField, $sortDir);

        return response()->json($query->paginate($request->per_page ?? 15));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'unit'        => 'required|string|max:20',
            'stock'       => 'nullable|numeric|min:0',
            'min_stock'   => 'nullable|numeric|min:0',
            'max_stock'   => 'nullable|numeric|min:0',
            'price'       => 'nullable|numeric|min:0',
            'location'    => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|max:2048',
        ]);

        $category = Category::findOrFail($data['category_id']);
        $data['code'] = Item::generateCode($category->code);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('items', 'public');
        }

        $item = Item::create($data);

        if (($data['stock'] ?? 0) > 0) {
            StockMovement::create([
                'item_id'      => $item->id,
                'user_id'      => $request->user()->id,
                'type'         => 'in',
                'quantity'     => $data['stock'],
                'stock_before' => 0,
                'stock_after'  => $data['stock'],
                'notes'        => 'Stok awal saat barang ditambahkan',
            ]);
        }

        return response()->json($item->load(['category', 'supplier']), 201);
    }

    public function show($id): JsonResponse
    {
        $item = Item::findOrFail($id);
        return response()->json($item->load(['category', 'supplier', 'stockMovements' => fn($q) => $q->with('user:id,name')->limit(20)]));
    }

    public function update(Request $request, $id): JsonResponse
    {
        $item = Item::findOrFail($id);
        $data = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'category_id' => 'sometimes|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'unit'        => 'sometimes|string|max:20',
            'stock'       => 'nullable|numeric|min:0',
            'min_stock'   => 'nullable|numeric|min:0',
            'max_stock'   => 'nullable|numeric|min:0',
            'price'       => 'nullable|numeric|min:0',
            'location'    => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'is_active'   => 'sometimes|boolean',
            'image'       => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($item->image) Storage::disk('public')->delete($item->image);
            $data['image'] = $request->file('image')->store('items', 'public');
        }

        $item->update($data);
        return response()->json($item->fresh()->load(['category', 'supplier']));
    }

    public function destroy($id): JsonResponse
    {
        Item::findOrFail($id)->delete();
        return response()->json(['message' => 'Barang berhasil dihapus.']);
    }

    public function adjustStock(Request $request, $id): JsonResponse
    {
        $item = Item::findOrFail($id);
        $data = $request->validate([
            'stock'  => 'required|numeric|min:0',
            'notes'  => 'required|string|max:500',
        ]);

        $oldStock = $item->stock;
        $diff = $data['stock'] - $oldStock;

        $item->update(['stock' => $data['stock']]);

        StockMovement::create([
            'item_id'      => $item->id,
            'user_id'      => $request->user()->id,
            'type'         => 'adjustment',
            'quantity'     => abs($diff),
            'stock_before' => $oldStock,
            'stock_after'  => $data['stock'],
            'notes'        => 'Penyesuaian stok: ' . $data['notes'],
        ]);

        return response()->json($item->fresh());
    }
}