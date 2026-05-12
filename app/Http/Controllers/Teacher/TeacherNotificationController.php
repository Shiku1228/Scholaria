<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Schema;

class TeacherNotificationController extends Controller
{
    public function readAll(Request $request): RedirectResponse
    {
        if (Schema::hasTable('notifications')) {
            $request->user()->unreadNotifications()->update(['read_at' => now()]);
        }

        return redirect()->back();
    }

    public function open(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->whereKey($notification)->first();
        if (!$item instanceof DatabaseNotification) {
            return redirect()->back();
        }

        if ($item->read_at === null) {
            $item->markAsRead();
        }

        $url = (string) data_get($item->data, 'url', '');
        if ($url === '') {
            return redirect()->back();
        }

        return redirect()->to($url);
    }
}
