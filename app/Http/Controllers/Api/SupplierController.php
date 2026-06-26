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

    public function update(Request $request, $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);

        // Menggunakan 'sometimes' supaya validasi tidak memblokir jika field tidak dikirim
        $data = $request->validate([
            'name'           => 'sometimes|string|max:255',
            'contact_person' => 'sometimes|nullable|string|max:100',
            'phone'          => 'sometimes|nullable|string|max:20',
            'email'          => 'sometimes|nullable|email|max:100',
            'address'        => 'sometimes|nullable|string',
            'city'           => 'sometimes|nullable|string|max:100',
            'is_active'      => 'sometimes', 
            'notes'          => 'sometimes|nullable|string',
        ]);

        // Konversi eksplisit untuk is_active
        if ($request->has('is_active')) {
            $data['is_active'] = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
        }

        $supplier->update($data);
        return response()->json($supplier->fresh());
    }

    public function destroy($id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);

        if ($supplier->items()->exists()) {
            return response()->json([
                'success' => false, 
                'message' => 'Gagal! Supplier masih terikat dengan barang.'
            ], 422);
        }

        $supplier->delete();
        return response()->json([
            'success' => true, 
            'message' => 'Supplier berhasil dihapus.'
        ]);
    }
}