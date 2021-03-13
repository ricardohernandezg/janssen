<?php 

/**
 * 
 * Fill the array with route, destination that will be processed and optionally params
 * 
 * THIS IS ONLY FOR GET REQUESTS
 * 
 * Array MUST BE:
 * Regex|literal: this will be compared to request and first match will be returned
 * Controller|Template_file: This will process the request, if a controller is passed it
 * will be invoked, if a file is passed the path will be searched in templates folder, loaded
 * and returned.
 * Parameter: If your regex has capture variables, this will be named with this literals
 * and passed to controller (is used) or saved in Request parameters to be used in your
 * app normally. This parameters are processed in order and POST overwrite GET parameters.
 * 
 **/ 

return [

        ['path' => '/dynjs/login', 
        'resolver' => '\App\Controller\ScriptController@getLoginScript', 
        'guard' => 'nobody'], 
        
        ['path' =>'/', 
        'resolver' => 'welcome.php', 
        'guard' => 'nobody'],
    
];