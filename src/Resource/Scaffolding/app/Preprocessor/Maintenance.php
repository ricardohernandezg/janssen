<?php 

namespace App\Preprocessor;

use Janssen\Engine\Preprocessor;
use Janssen\Engine\Request;


class Maintenance extends Preprocessor
{

    public function handle(Request $request)
    {
        if(getenv('maintenance') == 'true'){
            // make a response to handle the maintenance
            $r = new \Janssen\Helpers\Response\RawResponse;
            $r->setContent('Estamos en mantenimiento!');
            return $r;
        }else
            return true;
    }

    public function handleError()
    {
        return false;
    }

}