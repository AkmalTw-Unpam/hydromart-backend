<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\{Request, JsonResponse};

class SupplierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = Supplier::withCount('items')
            ->when($request->search, fn($q) => $q->where('name','like',"%{$request->search}%")->orWhere('code','like',"%{$request->search}%"))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name');
        return response()->json($q->paginate($request->per_page ?? 15));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:100',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:100',
            'address'        => 'nullable|string',
            'city'           => 'nullable|string|max:100',
            'notes'          => 'nullable|string',
        ]);

        $last = Supplier::withTrashed()->orderByDesc('id')->first();
        $num  = $last ? ((int) substr($last->code, 3)) + 1 : 1;
        $data['code'] = 'SUP' . str_pad($num, 3, '0', STR_PAD_LEFT);

        return response()->json(Supplier::create($data), 201);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        return response()->json($supplier->load(['items:id,name,code,stock,unit']));
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $data = $request->validate([
            'name'           => 'sometimes|string|max:255',
            'contact_person' => 'nullable|string|max:100',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:100',
            'address'        => 'nullable|string',
            'city'           => 'nullable|string|max:100',
            'is_active'      => 'sometimes|boolean',
            'notes'          => 'nullable|string',
        ]);
        $supplier->update($data);
        return response()->json($supplier->fresh());
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        if ($supplier->items()->exists()) {
            return response()->json(['message' => 'Supplier masih terhubung dengan barang.'], 422);
        }
        $supplier->delete();
        return response()->json(['message' => 'Supplier dihapus.']);
    }
}
