<?php

namespace App\Helpers;

use App\Models\SystemSetting;

class AppMode
{
    public static function isHotel(): bool
    {
        return SystemSetting::get('hotel_mode', false) == true || SystemSetting::get('hotel_mode', false) == '1';
    }

    public static function isShortlet(): bool
    {
        return ! self::isHotel();
    }

    public static function getMode(): string
    {
        return self::isHotel() ? 'hotel' : 'shortlet';
    }

    public static function getPropertyLabel(): string
    {
        return self::isHotel() ? 'Room' : 'Property';
    }

    public static function getPropertyLabelPlural(): string
    {
        return self::isHotel() ? 'Rooms' : 'Properties';
    }

    public static function getBookingLabel(): string
    {
        return self::isHotel() ? 'Reservation' : 'Booking';
    }

    public static function getBookingLabelPlural(): string
    {
        return self::isHotel() ? 'Reservations' : 'Bookings';
    }
}
