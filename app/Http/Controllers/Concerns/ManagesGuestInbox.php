<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Conversation;
use App\Models\Message;
use App\Support\PropertyAccess;
use Illuminate\Http\Request;

/**
 * The guest message inbox shared by admin and staff. Guests write to the team,
 * not to one person: anyone on the team can read and reply, and a guest's
 * message counts as read once any team member opens the conversation.
 *
 * Staff see conversations about properties at their sites; general enquiries
 * (no property) go to head office.
 */
trait ManagesGuestInbox
{
    abstract protected function inboxLayout(): string;

    abstract protected function routePrefix(): string;

    public function index(Request $request)
    {
        $conversations = Conversation::visibleTo($request->user())->with(['user', 'property', 'reservation', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->withCount(['messages as unread_count' => fn ($q) => $q->where('read', false)->whereColumn('messages.sender_id', 'conversations.user_id')])
            ->whereNotNull('user_id')
            ->orderByDesc('unread_count')
            ->latest('last_message_at')
            ->paginate(20);

        return view('inbox.index', [
            'conversations' => $conversations,
            'layout' => $this->inboxLayout(),
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    public function show(Request $request, Conversation $conversation)
    {
        PropertyAccess::authorize($request->user(), $conversation->property_id);

        $conversation->load(['user', 'property', 'reservation', 'messages.sender']);

        // The guest's messages have now been seen by the team.
        Message::where('conversation_id', $conversation->id)
            ->where('sender_id', $conversation->user_id)
            ->where('read', false)
            ->update(['read' => true]);

        return view('inbox.show', [
            'conversation' => $conversation,
            'layout' => $this->inboxLayout(),
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    public function reply(Request $request, Conversation $conversation)
    {
        PropertyAccess::authorize($request->user(), $conversation->property_id);

        $validated = $request->validate(['message' => 'required|string|max:2000']);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $request->user()->id,
            'receiver_id' => $conversation->user_id,
            'message' => $validated['message'],
            'read' => false,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return back()->with('success', 'Reply sent to ' . ($conversation->user->name ?? 'the guest'));
    }
}
