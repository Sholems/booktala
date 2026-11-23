<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\CommunicationAlertService;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public const CATEGORIES = [
        'general' => ['label' => 'General Inquiry', 'icon' => 'fa-question-circle', 'color' => 'bg-slate-100 text-slate-800'],
        'payroll' => ['label' => 'Payroll & Compensation', 'icon' => 'fa-money-bill-wave', 'color' => 'bg-emerald-100 text-emerald-800'],
        'leave_emergency' => ['label' => 'Emergency & Leave', 'icon' => 'fa-ambulance', 'color' => 'bg-amber-100 text-amber-800'],
        'grievance' => ['label' => 'Confidential Grievance', 'icon' => 'fa-user-shield', 'color' => 'bg-rose-100 text-rose-800'],
    ];

    public function index(Request $request)
    {
        $conversations = Conversation::where('user_id', $request->user()->id)
            ->where('type', 'staff')
            ->with(['messages' => fn ($q) => $q->latest()->limit(1)])
            ->withCount(['messages as unread_count' => fn ($q) => $q->where('read', false)->where('sender_id', '!=', $request->user()->id)])
            ->latest('last_message_at')
            ->paginate(15);

        return view('staff.support.index', [
            'conversations' => $conversations,
            'categories' => self::CATEGORIES,
        ]);
    }

    public function show(Request $request, Conversation $conversation)
    {
        abort_unless($conversation->user_id === $request->user()->id && $conversation->type === 'staff', 403);

        $conversation->load(['messages.sender']);

        // Mark incoming replies as read
        Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $request->user()->id)
            ->where('read', false)
            ->update(['read' => true]);

        return view('staff.support.show', [
            'conversation' => $conversation,
            'categories' => self::CATEGORIES,
        ]);
    }

    public function store(Request $request, CommunicationAlertService $alertService)
    {
        $validated = $request->validate([
            'category' => 'required|string|in:'.implode(',', array_keys(self::CATEGORIES)),
            'message' => 'required|string|max:3000',
        ]);

        $categoryLabel = self::CATEGORIES[$validated['category']]['label'] ?? 'General Inquiry';
        $fullMessage = "[{$categoryLabel}]\n".trim($validated['message']);

        $conversation = Conversation::create([
            'user_id' => $request->user()->id,
            'type' => 'staff',
            'last_message_at' => now(),
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $request->user()->id,
            'receiver_id' => null,
            'message' => $fullMessage,
            'read' => false,
        ]);

        $alertService->notifyDirectMessage($message, $conversation, $request->user());

        return redirect()->route('staff.support.show', $conversation)
            ->with('success', 'Your private inquiry has been submitted directly to Head Office.');
    }

    public function reply(Request $request, Conversation $conversation, CommunicationAlertService $alertService)
    {
        abort_unless($conversation->user_id === $request->user()->id && $conversation->type === 'staff', 403);

        $validated = $request->validate([
            'message' => 'required|string|max:3000',
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $request->user()->id,
            'receiver_id' => null,
            'message' => trim($validated['message']),
            'read' => false,
        ]);

        $conversation->update(['last_message_at' => now()]);

        $alertService->notifyDirectMessage($message, $conversation, $request->user());

        return back()->with('success', 'Reply sent to Management.');
    }
}
