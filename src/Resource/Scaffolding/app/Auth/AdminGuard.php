<?php 

namespace App\Auth;

use Janssen\Helpers\Guard;
use Janssen\Engine\Request;

class AdminGuard extends Guard
{

    /**
     * Make the authentication of a user through the Admin Guard.
     * This will grant a request comes from a user that is in the 
     * list that can access the resource managed by Admin
     *
     * @param Request $request
     * @return void
     */
    public function authenticate(Request $request)
    {
        return true;
    }

    /**
     * Authorize an action that must come from an authenticated user
     * as the route should be protected with this guard
     *
     * @param Request $request
     * @return void
     */
    public function authorize(Request $request)
    {
        return true;
    }
    
}