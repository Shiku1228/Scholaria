<?php

return [

    /*
    |--------------------------------------------------------------------------
    | JWT Authentication Secret
    |--------------------------------------------------------------------------
    |
    | Don't add this file to your version control!
    |
    | Add the following line to your .env file
    | JWT_SECRET=your_secret_key
    |
    | NOTE: This should be in the .env file, not here
    |
    */

    'secret' => env('JWT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | JWT Time To Live
    |--------------------------------------------------------------------------
    |
    | Specify the length of time (in minutes) that the token will be valid for.
    |
    | Default: 1 hour
    |
    */
    'ttl' => env('JWT_TTL', 60),

    /*
    |--------------------------------------------------------------------------
    | JWT Refresh Time To Live
    |--------------------------------------------------------------------------
    |
    | Specify the length of time (in minutes) that the token will be
    | valid for.
    |
    | Default: 2 weeks
    |
    */
    'refresh_ttl' => env('JWT_REFRESH_TTL', 20160),

    /*
    |--------------------------------------------------------------------------
    | JWT Algorithm
    |--------------------------------------------------------------------------
    |
    | Specify the algorithm. See: https://github.com/namshi/jwt-auth#specifying-the-algorithm
    |
    | Supported: "HS256", "HS384", "RS256", "RS384"
    |
    | Default: "HS256"
    |
    */
    'algo' => env('JWT_ALGO', 'HS256'),

    /*
    |--------------------------------------------------------------------------
    | Required Claims
    |--------------------------------------------------------------------------
    |
    | Specify the required claims that must be present in the token.
    | A custom claim can be added using the format: 'claim_name' => 'claim_value'
    |
    */
    'required_claims' => [
        'iss',
        'iat',
        'exp',
        'nbf',
        'sub',
        'jti'
    ],

    /*
    |--------------------------------------------------------------------------
    | Persistent Claims
    |--------------------------------------------------------------------------
    |
    | Specify the claims that should be persisted when refreshing a token.
    |
    | Table: users
    |
    | */
    'persistent_claims' => [
        // 'email',
        // 'name'
    ],

    /*
    |--------------------------------------------------------------------------
    | Lock Subject
    |--------------------------------------------------------------------------
    |
    | Should the "sub" claim be automatically included on
    | successful token refresh?
    |
    | Default: false
    |
    | \App\Models\User::class, \App\Models\User::table
    |
    */
    'lock_subject' => env('JWT_LOCK_SUBJECT', false),

    /*
    |--------------------------------------------------------------------------
    | Add leeway to clock
    |--------------------------------------------------------------------------
    |
    | The leeway in seconds to account for clock differences, when
    | checking token expiration. Usually not needed.
    |
    | Default: 0
    |
    */
    'leeway' => env('JWT_LEEWAY', 0),

    /*
    |--------------------------------------------------------------------------
    | Blacklist Enabled
    |--------------------------------------------------------------------------
    |
    | Enable/Disable the blacklist feature.
    |
    | This specifies which algorithms are blacklisted, allowing you to
    | specify which algorithms should not be used.
    | By default, this is empty, meaning no algorithms are blacklisted
    | and all algorithms are considered valid.
    |
    | Default: []
    |
    */
    'blacklist_enabled' => env('JWT_BLACKLIST_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    |
    | Specify the providers that are used by package.
    |
    | Default: []
    |
    */
    'providers' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Pass By Request
    |--------------------------------------------------------------------------
    |
    | If the request contains a token, this will set the authentication on
    | request. This makes it easier to work with API requests that use
    | JWT authentication.
    |
    | Default: false
    |
    */
    'pass_by_request' => env('JWT_PASS_BY_REQUEST', false),

    /*
    |--------------------------------------------------------------------------
    | Users Model
    |--------------------------------------------------------------------------
    |
    |
    | Specify the model that represents the users in your application.
    |
    | Default: User::class
    |
    */
    'user_model' => env('JWT_USER_MODEL', 'App\Models\User'),

    /*
    |--------------------------------------------------------------------------
    | User Identifier
    |--------------------------------------------------------------------------
    |
    |
    | Specify the column that identifies the user in the users table.
    | |
    | Default: id
    |
    */
    'user_identifier' => 'id',

    /*
    |--------------------------------------------------------------------------
    | Decryption Key
    |--------------------------------------------------------------------------
    |
    | JWT decryption key. This value will be used to decrypt the payload.
    | Leave this field null if you don't want to use decryption.
    |
    | Default: null
    |
    */
    'decryption_key' => env('JWT_DECRYPTION_KEY', env('JWT_SECRET')),

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    |
    | Specify any helper functions that you would like to be available
    | for your JWT authentication.
    |
    */
    'helpers' => [],

];
