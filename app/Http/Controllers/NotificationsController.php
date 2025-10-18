<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DataTables\NotificationsDataTable;
use App\Models\Notification;

class NotificationsController extends Controller
{
    /**
     * Display a listing of notifications.
     */
    public function index(NotificationsDataTable $dataTable)
    {
        return $dataTable->render('notification.index');
    }

    /**
     * Mark a notification as read (example).
     */
     public function bulkMarkAsRead(Request $request)
    {
        $ids = $request->input('ids', []);
        Notification::whereIn('id', $ids)->update(['read' => 1]);

        return response()->json([
            'status' => 'success',
            'message' => count($ids) . ' notifications marked as read'
        ]);
    }

    // Bulk delete
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        Notification::whereIn('id', $ids)->delete();

        return response()->json([
            'status' => 'success',
            'message' => count($ids) . ' notifications deleted'
        ]);
    }
}
