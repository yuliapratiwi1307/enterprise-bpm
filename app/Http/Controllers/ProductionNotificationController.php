<?php

namespace App\Http\Controllers;

use App\Services\BpmRoleAuthorization;
use Illuminate\Http\Request;

class ProductionNotificationController extends Controller
{
    public function index(Request $request, BpmRoleAuthorization $roles)
    {
        $this->authorizeProductionUser($request, $roles);
        $notifications = $request->user()->notifications()->latest()->paginate(20);

        return view('prod03.notifications.index', compact('notifications'));
    }

    public function markRead(Request $request, string $notification, BpmRoleAuthorization $roles)
    {
        $this->authorizeProductionUser($request, $roles);
        $record = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $record->markAsRead();
        $designRequestId = $record->data['details']['design_request_id'] ?? null;

        return $designRequestId
            ? redirect()->route('prod03.design-requests.show', $designRequestId)
            : back()->with('message', 'Notifikasi ditandai telah dibaca.');
    }

    private function authorizeProductionUser(Request $request, BpmRoleAuthorization $roles): void
    {
        abort_unless(
            $roles->has($request->user(), 'MARKETING') || $roles->has($request->user(), 'DESIGNER'),
            403
        );
    }
}
