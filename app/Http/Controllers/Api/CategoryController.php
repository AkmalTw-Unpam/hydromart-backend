<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Category, Supplier, Notification, Item, IncomingGood, OutgoingGood, StockMovement};
use Illuminate\Http\{Request, JsonResponse};

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $cats = Category::withCount('items')
            ->when($request->search, fn($q) => $q->where('name','like',"%{$request->search}%"))
            ->orderBy('name')->get();
        return response()->json($cats);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:categories',
            'code'        => 'required|string|max:10|unique:categories',
            'color'       => 'nullable|string|max:7',
            'description' => 'nullable|string',
        ]);
        return response()->json(Category::create($data), 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'sometimes|string|max:100|unique:categories,name,' . $category->id,
            'color'       => 'nullable|string|max:7',
            'description' => 'nullable|string',
            'is_active'   => 'sometimes|boolean',
        ]);
        $category->update($data);
        return response()->json($category->fresh());
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->items()->exists()) {
            return response()->json(['message' => 'Kategori masih digunakan oleh barang.'], 422);
        }
        $category->delete();
        return response()->json(['message' => 'Kategori dihapus.']);
    }
}

// ==================== SUPPLIER ====================
