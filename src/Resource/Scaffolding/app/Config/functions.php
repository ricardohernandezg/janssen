<?php 
/**
 * The purpose of this file is to make global functions
 * to be called without writing entire namespaces or making
 * invocations. This is very useful to functions thar are
 * helpers and getters of data that are used very often.
 * 
 * The global constructor will take care of your call, determining
 * if your method is static or not and retrieving the
 * parameters needed. It will make the function on the fly
 * for you.
 * 
 * For security reasons we'll not allow the registration
 * of callback functions here. All calls must go to a method
 * in a namespaced class.
 * 
 */

return [
    'app_path' => '\Janssen\App@appPath',
    'url' => '\Janssen\App@url',
    'assets' => '\Janssen\App@assets',
    
    'file' => '\Janssen\App@fileResponse',
    'error' => '\Janssen\App@errorResponse',
    'redirect' => '\Janssen\App@redirectResponse',

    'request' => '\Janssen\App@getRequest',
    
    'engine_config' => '\Janssen\App@getConfig',
    'current_header' => '\Janssen\App@getCurrentHeader',

    /*
    'website_assets' => '\App\Controller\WebsiteController@websiteAssets',
    'website_path' => '\App\Controller\WebsiteController@websitePath',
    */

    ];