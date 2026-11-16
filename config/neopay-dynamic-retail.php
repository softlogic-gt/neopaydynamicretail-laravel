<?php

return [
    'url' => env('NEOPAY_POS_URL'),
    'auth_url' => env('NEOPAY_POS_AUTH_URL'),
    'membership_id' => env('NEOPAY_POS_MEMBERSHIP_ID'),
    'secret_key' => env('NEOPAY_POS_SECRET_KEY'),
    'merchant_user' => env('NEOPAY_POS_MERCHANT_USER'),
    'merchant_password' => env('NEOPAY_POS_MERCHANT_PASSWORD'),
    'terminal_id' => env('NEOPAY_POS_TERMINAL_ID'),
    'card_acq_id' => env('NEOPAY_POS_CARD_ACQ_ID'),
    'timeout' => env('NEOPAY_POS_TIMEOUT', 30),
];
