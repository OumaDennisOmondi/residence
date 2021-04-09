<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationsContoller extends Controller
{
    //
    public function __construct(){
        $this->middleware('auth');
        
    }
    //get user all notifs
    public function all(){
        return response()->json(['all_notifications' => auth()->user()->notifications,'count' => auth()->user()->notifications->count()],200);
    }

    ////get user all notifs unread
    public function unread(){
        return response()->json(['unread_notifications' => auth()->user()->unreadNotifications, 'count' => auth()->user()->unreadNotifications->count()],200);
    }
  //mark as read/read
    public function read($id){
        $notif=auth()->user()->unreadNotifications->where('id', $id);
        $read_notif=$notif->markAsRead();
        return response()->json(['notification' => $notif],200);
    }
}
