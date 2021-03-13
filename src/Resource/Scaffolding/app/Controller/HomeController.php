<?php 

namespace App\Controller;

use Janssen\Engine\Controller;
use Janssen\Engine\Request;

class HomeController extends Controller
{

    public function welcome()
    {
        return "Hello user!";
    }

}