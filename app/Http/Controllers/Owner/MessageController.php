<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Owner;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    private function owner(Request $request): Owner
    {
        $owner = Owner::where('user_id', $request->user()->id)->where('is_active', true)->first();
        abort_unless($owner, 403, 'Your login is not linked to an active owner account.');

        return $owner;
    }

    public function index(Request $request)
    {
        $owner = $this->owner($request);

        $conversations = Conversation::where('user_id', $request->user()->id)
            ->where('type', 'owner')
            ->with(['property', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->withCount(['messages as unread_count' => fn ($q) => $q->where('read', false)->where('sender_id', '!=', $request->user()->id)])
            ->latest('last_message_at')
            ->paginate(15);

        $properties = $owner->properties()->orderBy('title')->get(['id', 'title', 'site_id']);

        return view('owner.messages.index', compact('owner', 'conversations', 'properties'));
    }

    public function show(Request $request, Conversation $conversation)
    {
        $this->owner($request);
        abort_unless($conversation->user_id === $request->user()->id, 403);

        $conversation->load(['property.site', 'messages.sender']);

        // Mark incoming replies as read
        Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $request->user()->id)
            ->where('read', false)
            ->update(['read' => true]);

        return view('owner.messages.show', compact('conversation'));
    }

    public function store(Request $request)
    {
        $owner = $this->owner($request);

        $validated = $request->validate([
            'property_id' => 'nullable|integer',
            'message' => 'required|string|max:3000',
        ]);

        if (! empty($validated['property_id'])) {
            $ownsProperty = $owner->properties()->where('id', $validated['property_id'])->exists();
            abort_unless($ownsProperty, 403, 'You do not own this property.');
        }

        $conversation = Conversation::create([
            'user_id' => $request->user()->id,
            'property_id' => $validated['property_id'] ?? null,
            'type' => 'owner',
            'last_message_at' => now(),
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $request->user()->id,
            'receiver_id' => null,
            'message' => trim($validated['message']),
            'read' => false,
        ]);

        return redirect()->route('owner.messages.show', $conversation)
            ->with('success', 'Your inquiry has been submitted to Asset Management. We will reply shortly.');
    }

    public function reply(Request $request, Conversation $conversation)
    {
        $this->owner($request);
        abort_unless($conversation->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'message' => 'required|string|max:3000',
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $request->user()->id,
            'receiver_id' => null,
            'message' => trim($validated['message']),
            'read' => false,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return back()->with('success', 'Reply sent to Asset Management.');
    }
}
