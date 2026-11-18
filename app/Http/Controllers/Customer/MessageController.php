<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'property_id' => 'nullable|exists:properties,id',
            'reservation_id' => ['nullable', Rule::exists('reservations', 'id')->where('user_id', auth()->id())],
        ]);

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

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => auth()->id(),
            'receiver_id' => null,
            'message' => $validated['message'],
            'read' => false,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return redirect()->route('customer.messages.show', $conversation)->with('success', 'Message sent. Our team will reply here.');
    }

    public function reply(Request $request, Conversation $conversation)
    {
        abort_unless($conversation->user_id === auth()->id(), 403);
        $validated = $request->validate(['message' => 'required|string|max:2000']);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => auth()->id(),
            'receiver_id' => null,
            'message' => $validated['message'],
            'read' => false,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return back()->with('success', 'Reply sent');
    }

    public function startConversation(Request $request)
    {
        $request->validate([
            'property_id' => 'required|exists:properties,id',
            'message' => 'required|string',
        ]);

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
