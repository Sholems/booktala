<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WhatsAppSendRequest;
use App\Models\Reservation;
use App\Models\WhatsAppMessageLog;
use App\Services\WhatsApp\WhatsAppNotificationService;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    public function index(Request $request)
    {
        $logs = WhatsAppMessageLog::with(['user', 'reservation.property'])
            ->when($request->filled('template'), fn ($q) => $q->where('template', $request->string('template')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = '%'.$request->string('search').'%';
                $q->where(function ($sub) use ($search) {
                    $sub->where('recipient_phone', 'like', $search)
                        ->orWhere('message', 'like', $search)
                        ->orWhere('gateway_reference', 'like', $search);
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $stats = [
            'total_sent' => WhatsAppMessageLog::whereIn('status', ['sent', 'simulated'])->count(),
            'failed' => WhatsAppMessageLog::where('status', 'failed')->count(),
            'recent_24h' => WhatsAppMessageLog::where('created_at', '>=', now()->subDay())->count(),
        ];

        return view('admin.whatsapp.index', compact('logs', 'stats'));
    }

    public function send(WhatsAppSendRequest $request, WhatsAppNotificationService $service)
    {
        $data = $request->validated();

        $phone = $data['recipient_phone'] ?? $data['phone'] ?? null;
        if (! $phone) {
            return back()->with('error', 'A recipient phone number is required.');
        }

        $recipientName = $data['recipient_name'] ?? $data['name'] ?? null;
        $reservation = ! empty($data['reservation_id']) ? Reservation::find($data['reservation_id']) : null;
        $user = $reservation?->user;

        $log = $service->sendCustomMessage($phone, $data['message'], $user, $reservation, $recipientName);

        $msg = $log->status === 'failed'
            ? 'Failed to send WhatsApp message: '.$log->error_message
            : 'WhatsApp message sent to '.$log->recipient_phone;

        return back()->with($log->status === 'failed' ? 'error' : 'success', $msg);
    }
}
