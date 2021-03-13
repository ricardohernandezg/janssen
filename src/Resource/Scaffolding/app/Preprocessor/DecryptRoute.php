<?php 

namespace App\Preprocessor;

use Janssen\Engine\Preprocessor;
use Janssen\Engine\Route;
use Janssen\Engine\Request;
use Janssen\Helpers\Exception;


class DecryptRoute extends Preprocessor
{

    public function handle(Request $request)
    {
        $request = new Request;
        // get the full path and extract the payload to get the route
        $payload = $request->getQueryStringPayload();
        if (!$payload) {
            throw new Exception('Invalid request', 400);
        }
        // decipher route
        $route = Route::decrypt($payload);
        // load the correct controller
        $a = explode('/', $route);
        if (!is_array($a)) {
            throw new Exception('Invalid request', 400);
        }
        // save the path to Request object
        $request->setUserAction($a[0], $a[1]);
        return true;
    }

    public function handleError()
    {
        return false;
    }

}