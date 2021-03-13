<?php 

require_once ('../vendor/autoload.php');

/**
 * -- Application lifecycle --
 * Take request (GET|POST|PUT|DELETE) 
 * Preprocess
 * Find the right handler 
 * Handle request
 * Make response 
 * Postprocess
 * Echo response
 * 
 */

// Instantiate our app
$janapp = new Janssen\App();

// Init the app and load configurations
$janapp->init(__DIR__);

// run!
echo $janapp->run();