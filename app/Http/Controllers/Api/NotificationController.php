<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Notification, Item, IncomingGood, OutgoingGood, StockMovement};
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifs = Notification::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(20);
        $unread = Notification::where('user_id', $request->user()->id)->where('is_read', false)->count();
        return response()->json(['notifications' => $notifs, 'unread_count' => $unread]);
    }

    public function markRead(Request $request): JsonResponse
    {
        $ids = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer'])['ids'];
        Notification::where('user_id', $request->user()->id)
            ->whereIn('id', $ids)
            ->update(['is_read' => true, 'read_at' => now()]);
        return response()->json(['message' => 'Notifikasi ditandai sudah dibaca.']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
        return response()->json(['message' => 'Semua notifikasi telah dibaca.']);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::where('user_id', $request->user()->id)->where('is_read', false)->count();
        return response()->json(['count' => $count]);
    }
}
