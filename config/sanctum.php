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

    'token_length' => 40,

    /*
    |--------------------------------------------------------------------------
    | Include token id
    |--------------------------------------------------------------------------
    |
    | This value controls is the token id should be included when token string is created.
    |
    */

    'include_token_id' => true,

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
