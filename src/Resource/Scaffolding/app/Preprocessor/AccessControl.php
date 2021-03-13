<?php 

namespace App\Preprocessor;

//use Janssen\Engine\Factory;
use Janssen\Engine\Preprocessor;
use Janssen\Engine\Request;
use Janssen\Engine\Route;
use Janssen\Helpers\Exception;
use Janssen\Helpers\Guard;
use Janssen\Helpers\Response\ErrorResponse;
use Janssen\Helpers\Auth;

class AccessControl extends Preprocessor
{

    public function handle(Request $request)
    {
        // if request method is get we find for a authorization in guard
        // if post we check the validator to check what guards are enabled
        $rm = $request->method();
        if($rm == 'GET'){
            $path = $request->getPath();
            $route = Route::getByPath($path);
            $guard = empty($route['guard'])?engine_config('default_guard'):$route['guard'];
            if($guard !== 'nobody'){
                return Auth::guard($guard)->authorize($request);
            }else
                return true;

        }elseif (in_array($rm, ['POST','PUT','DELETE'])){
            $ua = $request->getUserAction();
            $v_name = '\App\Validator\\' . transform_to_class_name($ua['controller']) . 'Validator';
            $v = new $v_name;
            $guard = Guard::resolve($v->guard);
            // as guards authorize the access, with only one that allows the action
            // it will be allowed
            $authorized = false || $v->guard == 'nobody';
            foreach($guard as $g)
            {
                $authorized = $g->authorize($request);
                if($authorized)
                    break;
            }
            if(!$authorized)
                return new ErrorResponse('Request not allowed', 403);

        }else
            throw new Exception('Request not acceptable', 406);
        
        // this function must return a boolean
        return true;
    }

    public function handleError()
    {
        return redirect('login')->withData(['error' => 'Your session has ended. Login again']);
    }

}