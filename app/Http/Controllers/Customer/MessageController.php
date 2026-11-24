<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\MessageReplyRequest;
use App\Http\Requests\Customer\MessageStartConversationRequest;
use App\Http\Requests\Customer\MessageStoreRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\User;
use App\Services\CommunicationAlertService;

class MessageController extends Controller
{
    public function index()
    {
        $conversations = Conversation::where('user_id', auth()->id())
            ->with(['user', 'property', 'messages' => function ($q) {
                $q->latest()->limit(1);
            }])
            ->latest('last_message_at')
            ->get();

        return view('customer.messages.index', compact('conversations'));
    }

    public function show(Conversation $conversation)
    {
        abort_unless($conversation->user_id === auth()->id(), 403);
        $conversation->load(['user', 'admin', 'property', 'reservation', 'messages.sender', 'messages.receiver']);

        // Replies from the team are now read by the guest.
        Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', auth()->id())
            ->where('read', false)
            ->update(['read' => true]);

        return view('customer.messages.show', compact('conversation'));
    }

    public function store(MessageStoreRequest $request)
    {
        $validated = $request->validated();

        // A message about a booking goes to that property's site team as well as head office.
        if (empty($validated['property_id']) && ! empty($validated['reservation_id'])) {
            $validated['property_id'] = Reservation::whereKey($validated['reservation_id'])->value('property_id');
        }

        // Guests write to the team inbox (admin and front desk), not to one person.
        $conversation = Conversation::firstOrCreate([
            'user_id' => auth()->id(),
            'property_id' => $validated['property_id'] ?? null,
            'reservation_id' => $validated['reservation_id'] ?? null,
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => auth()->id(),
            'receiver_id' => null,
            'message' => $validated['message'],
            'read' => false,
        ]);

        $conversation->update(['last_message_at' => now()]);

        app(CommunicationAlertService::class)->notifyDirectMessage($message, $conversation, auth()->user());

        return redirect()->route('customer.messages.show', $conversation)->with('success', 'Message sent. Our team will reply here.');
    }

    public function reply(MessageReplyRequest $request, Conversation $conversation)
    {
        abort_unless($conversation->user_id === auth()->id(), 403);
        $validated = $request->validated();

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => auth()->id(),
            'receiver_id' => null,
            'message' => $validated['message'],
            'read' => false,
        ]);

        $conversation->update(['last_message_at' => now()]);

        app(CommunicationAlertService::class)->notifyDirectMessage($message, $conversation, auth()->user());

        return back()->with('success', 'Reply sent');
    }

    public function startConversation(MessageStartConversationRequest $request)
    {
        $request->validated();

        $property = Property::find($request->property_id);
        $admin = User::role('admin')->first();

        $existingConversation = Conversation::where('user_id', auth()->id())
            ->where('property_id', $request->property_id)
            ->first();

        if ($existingConversation) {
            $conversation = $existingConversation;
        } else {
            $conversation = Conversation::create([
                'user_id' => auth()->id(),
                'admin_id' => $admin?->id,
                'property_id' => $request->property_id,
            ]);
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => auth()->id(),
            'receiver_id' => $admin?->id,
            'message' => $request->message,
            'read' => false,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return redirect()->route('customer.messages.show', $conversation)
            ->with('success', 'Conversation started');
    }
}
