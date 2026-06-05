<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Item, IncomingGood, OutgoingGood, StockMovement};
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function stock(Request $request): JsonResponse
    {
        $items = Item::with(['category:id,name,color', 'supplier:id,name'])
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->search, fn($q) => $q->where('name','like',"%{$request->search}%")->orWhere('code','like',"%{$request->search}%"))
            ->when($request->status === 'low', fn($q) => $q->whereColumn('stock','<=','min_stock'))
            ->orderBy('name')
            ->get(['id','code','name','category_id','supplier_id','unit','stock','min_stock','price','location']);

        $summary = [
            'total_items'        => $items->count(),
            'low_stock'          => $items->filter(fn($i) => $i->stock <= $i->min_stock && $i->stock > 0)->count(),
            'empty_stock'        => $items->filter(fn($i) => $i->stock <= 0)->count(),
            'total_stock_value'  => $items->sum(fn($i) => $i->stock * $i->price),
        ];

        return response()->json(['items' => $items, 'summary' => $summary]);
    }

    public function incoming(Request $request): JsonResponse
    {
        $query = IncomingGood::with(['item:id,name,code,unit','supplier:id,name','user:id,name'])
            ->when($request->date_from, fn($q) => $q->where('transaction_date', '>=', $request->date_from))
            ->when($request->date_to,   fn($q) => $q->where('transaction_date', '<=', $request->date_to))
            ->when($request->supplier_id, fn($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->category_id, fn($q) => $q->whereHas('item', fn($q) => $q->where('category_id', $request->category_id)))
            ->orderByDesc('transaction_date');

        $data = $query->get();
        $summary = [
            'total_transactions' => $data->count(),
            'total_quantity'     => $data->sum('quantity'),
            'total_value'        => $data->sum('total_value'),
        ];

        return response()->json(['transactions' => $data, 'summary' => $summary]);
    }

    public function outgoing(Request $request): JsonResponse
    {
        $query = OutgoingGood::with(['item:id,name,code,unit','user:id,name'])
            ->when($request->date_from, fn($q) => $q->where('transaction_date', '>=', $request->date_from))
            ->when($request->date_to,   fn($q) => $q->where('transaction_date', '<=', $request->date_to))
            ->when($request->category_id, fn($q) => $q->whereHas('item', fn($q) => $q->where('category_id', $request->category_id)))
            ->orderByDesc('transaction_date');

        $data = $query->get();
        $summary = [
            'total_transactions' => $data->count(),
            'total_quantity'     => $data->sum('quantity'),
        ];

        return response()->json(['transactions' => $data, 'summary' => $summary]);
    }

    public function movements(Request $request): JsonResponse
    {
        $query = StockMovement::with(['item:id,name,code,unit','user:id,name'])
            ->when($request->item_id, fn($q) => $q->where('item_id', $request->item_id))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->date_from, fn($q) => $q->where('created_at', '>=', $request->date_from))
            ->when($request->date_to,   fn($q) => $q->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay()))
            ->orderByDesc('created_at');

        return response()->json($query->paginate($request->per_page ?? 20));
    }
}
