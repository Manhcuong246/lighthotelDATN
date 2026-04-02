<?php

return [

    'check_in_time' => env('BOOKING_CHECK_IN_TIME', '14:00'),

    'cancel_free_hours' => (int) env('BOOKING_CANCEL_FREE_HOURS', 48),

    'cancel_mid_hours_low' => (int) env('BOOKING_CANCEL_MID_HOURS_LOW', 24),

    'cancel_penalty_mid_percent' => (int) env('BOOKING_CANCEL_PENALTY_MID_PERCENT', 50),

    'cancel_penalty_short_percent' => (int) env('BOOKING_CANCEL_PENALTY_SHORT_PERCENT', 100),

];
