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
            // Mengelompokkan parameter OR agar tidak merusak filter is_active
            ->when($request->search, function ($query) use ($request) {
                $query->where(function ($sub) use ($request) {
                    $sub->where('name', 'like', "%{$request->search}%")
                        ->orWhere('code', 'like', "%{$request->search}%");
                });
            })
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

    // PERBAIKAN UTAMA: Mengubah instance parameter agar mendukung mutasi POST murni
    public function update(Request $request, $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);

        $data = $request->validate([
            'name'           => 'sometimes|string|max:255',
            'contact_person' => 'nullable|string|max:100',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:100',
            'address'        => 'nullable|string',
            'city'           => 'nullable|string|max:100',
            'is_active'      => 'sometimes', 
            'notes'          => 'nullable|string',
        ]);

        // Menangkap status is_active secara dinamis baik dari format string maupun boolean
        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        $supplier->update($data);
        return response()->json($supplier->fresh());
    }

    public function destroy($id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);

        // KONTROL KEAMANAN: Tolak hapus jika supplier masih memiliki barang terikat di gudang
        if ($supplier->items()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus! Supplier ini masih memiliki barang aktif yang terikat di gudang.'
            ], 422);
        }

        $supplier->delete();
        return response()->json([
            'success' => true,
            'message' => 'Supplier berhasil dihapus.'
        ]);
    }
}