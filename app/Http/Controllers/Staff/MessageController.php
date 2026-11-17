<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Concerns\ManagesGuestInbox;
use App\Http\Controllers\Controller;

/**
 * Front-desk access to the shared guest inbox.
 */
class MessageController extends Controller
{
    use ManagesGuestInbox;

    protected function inboxLayout(): string
    {
        return 'layouts.staff';
    }

    protected function routePrefix(): string
    {
        return 'staff.messages';
    }
}
