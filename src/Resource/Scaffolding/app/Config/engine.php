<?php

return [
    'preprocessors' => [
        '\App\Preprocessor\Maintenance',
        ['\App\Preprocessor\DecryptRoute', 'POST'],
        '\App\Preprocessor\AccessControl',
    ],

    'postprocessors' => [      
    ],
  
    'assets' => [
        'css' => 'assets/css/',
        'js' => 'assets/js/',
        'img' => 'assets/images/',
        'fonts' => 'assets/font/',
        'vendor' => 'assets/vendors/'
    ],

    /* base url to the website, it will be gathered from .env but in production this
    variable must be set to final url */
    'url' => (getenv('url') || 'http://localhost'),

    /* managed website path: if this app is and manager for another website and you
    think you'll need its URL, put it here */ 
    /* 'website_path' => getenv('website_path', '/var/www/html/my_other_website/'), */
 
    /* managed website assets path */
    'website_assets' => [
        'css' => 'css/',
        'js' => 'js/',
        'img' => 'images/',
    ],

    /**
     * Database connections
     * app will use database connection. Put false or remove to not 
     * use any database connection. You can also indicate psr-4 route
     * to your own implementation of Adaptor if not mysqli or postgres 
     */
    'connections' => [
        'mydb' => [
            'driver' => getenv('DB_DRIVER'),
            'host' => getenv('DB_HOST'),
            'user' => getenv('DB_USER'),
            'pwd' => getenv('DB_PASS'),
            'db' => getenv('DB_DB'),
        ]
    ],
    /**
     * Default connection when no specified (also used for start)
     */
    'default_connection' => 'mydb',

    /**
     * Guards are the instances that authenticate or authorize
     * a request
     */
    'guards' => [
       'admin',
       'api'
    ],

    /**
     * Default guard when controller doesn't designate one
     */
    'default_guard' => 'admin',

    /**
     * Authentication is the method to verify an identity. 
     * Each guard must implement its own way to make authentication.
     * If not method is found the request will be denied by default
     */
    'authentication' => [
        'admin',
        'api'
    ],

    /**
     * Autorization is the method to check if user can access
     * a resource.
     */    
    'autorization' => [
        'admin' => [
            'method' => 'session',
            'look_for' => ['is_admin', true]
            ]
    ],
];
