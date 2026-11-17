<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ManagesRoomServiceQueue;
use App\Http\Controllers\Controller;

class RoomServiceController extends Controller
{
    use ManagesRoomServiceQueue;

    protected function queueView(): string
    {
        return 'admin.room-service.index';
    }

    protected function routePrefix(): string
    {
        return 'admin.room-service';
    }
}
