<?php

/*
 * BookTala brand and organisation details.
 *
 * These are the fallbacks used when an admin has not set a value on the
 * Settings page. Contact details marked "placeholder" must be replaced with
 * G-Briel Property Ltd's real details before going live (here, in .env, or
 * from Admin > Settings).
 */
return [
    'name' => env('BRAND_NAME', 'BookTala'),
    'tagline' => env('BRAND_TAGLINE', 'Stays · Travels · Better Living'),
    'owner' => env('BRAND_OWNER', 'G-Briel Property Ltd'),
    'description' => env('BRAND_DESCRIPTION', 'BookTala makes it simple to find, book and enjoy comfortable, fully furnished short-let stays. Every apartment is managed by G-Briel Property Ltd, with secure online payment and a team on hand from check-in to check-out.'),

    // Placeholders until the organisation's details are provided.
    'phone' => env('BRAND_PHONE', '+234 000 000 0000'),
    'email' => env('BRAND_EMAIL', 'hello@booktala.test'),
    'address' => env('BRAND_ADDRESS', 'Address to be confirmed'),
    'city' => env('BRAND_CITY', 'Lagos, Nigeria'),
    'google_profile' => env('BRAND_GOOGLE_PROFILE'),

    'social' => [
        'facebook' => env('BRAND_FACEBOOK'),
        'twitter' => env('BRAND_TWITTER'),
        'instagram' => env('BRAND_INSTAGRAM'),
    ],

    'colors' => [
        'primary' => '#0B2D5B',  // Deep Navy
        'accent' => '#E11D2E',   // Rich Red
        'background' => '#F6F7F9', // Soft Neutral
    ],
];
