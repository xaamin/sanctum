<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    |
    | This value controls the number of minutes until an issued token will be
    | considered expired. If this value is null, personal access tokens do
    | not expire. This won't tweak the lifetime of first-party sessions.
    |
    */

    'expiration' => null,

    /*
    |--------------------------------------------------------------------------
    | Token length
    |--------------------------------------------------------------------------
    |
    | This value controls the number of characters used to generate the tokens.
    |
    */

    'token_length' => 64,

    /*
    |--------------------------------------------------------------------------
    | Token usage tracking
    |--------------------------------------------------------------------------
    |
    | Setting this value to true means you want to keep tracking of the last
    | time the token was used
    |
    */

    'track_usage' => true

];
