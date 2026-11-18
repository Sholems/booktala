<?php

return [
    'processing_fee' => (float) env('BOOKING_PROCESSING_FEE', 2000),
    'caution_fee' => (float) env('BOOKING_CAUTION_FEE', 20000),
    'hold_minutes' => (int) env('BOOKING_HOLD_MINUTES', 15),
];
