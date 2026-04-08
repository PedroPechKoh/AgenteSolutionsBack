<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // Fetch unread notifications for the logged-in user
    public function getUnread(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'success' => true,
            'notifications' => $user->unreadNotifications
        ], 200);
    }

    // Mark a specific notification as read
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->find($id);

        if ($notification) {
            $notification->markAsRead();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Notification not found'], 404);
    }

    ///Listar todas las Notificaciones
    public function getAll(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'success' => true,
            'notifications' => $user->notifications 
        ], 200);
    }
}