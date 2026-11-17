<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Concerns\ManagesRoomServiceQueue;
use App\Http\Controllers\Controller;

class RoomServiceController extends Controller
{
    use ManagesRoomServiceQueue;

    protected function queueView(): string
    {
        return 'staff.room-service.index';
    }

    protected function routePrefix(): string
    {
        return 'staff.room-service';
    }
}
