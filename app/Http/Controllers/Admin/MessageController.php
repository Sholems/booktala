<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ManagesGuestInbox;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MessageStartConversationRequest;
use App\Models\Conversation;
use App\Models\Message;

class MessageController extends Controller
{
    use ManagesGuestInbox;

    protected function inboxLayout(): string
    {
        return 'layouts.admin';
    }

    protected function routePrefix(): string
    {
        return 'admin.messages';
    }

    public function startConversation(MessageStartConversationRequest $request)
    {
        $request->validated();

        $existingConversation = Conversation::where('user_id', $request->user_id)
            ->where('property_id', $request->property_id)
            ->first();

        if ($existingConversation) {
            $conversation = $existingConversation;
        } else {
            $conversation = Conversation::create([
                'user_id' => $request->user_id,
                'admin_id' => auth()->id(),
                'property_id' => $request->property_id,
            ]);
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => auth()->id(),
            'receiver_id' => $request->user_id,
            'message' => $request->message,
            'read' => false,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return back()->with('success', 'Conversation started');
    }
}
