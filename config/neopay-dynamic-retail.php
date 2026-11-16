<?php

return [
    'url' => env('NEOPAY_POS_URL'),
    'merchant_id' => env('NEOPAY_POS_MERCHANT_ID'),
    'terminal_id' => env('NEOPAY_POS_TERMINAL_ID'),
    'user' => env('NEOPAY_POS_USER'),
    'password' => env('NEOPAY_POS_PASSWORD'),
    'timeout' => env('NEOPAY_POS_TIMEOUT', 30),
];
