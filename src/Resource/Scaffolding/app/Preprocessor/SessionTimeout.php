<?php 

namespace App\Preprocessor;

use Janssen\Engine\Preprocessor;
use Janssen\Engine\Request;

class SessionTimeout extends Preprocessor
{

    public function handle(Request $request)
    {
        return true;
    }

    public function handleError()
    {
        return false;
    }

}