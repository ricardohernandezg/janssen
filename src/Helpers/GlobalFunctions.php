<?php

use Janssen\App;
use Janssen\Engine\Response;
use Janssen\Engine\Route;
use Janssen\Engine\Session;
use Janssen\Helpers\Guard;
use Janssen\Helpers\Response\ErrorResponse;

function create_global_functions($funcs = [])
{
    $mycncfunc = 'function __NAME__(__ARGS__){$i = new __CLASS__; return $i->__METHOD__(__UNTARGS__);}';
    $mycncfunc_woni = 'function __NAME__(__ARGS__){global $janapp; return $janapp->__METHOD__(__UNTARGS__);}';
    $mystafunc = 'function __NAME__(__ARGS__){return __CLASS__::__METHOD__(__UNTARGS__);}';

    foreach($funcs as $k=>$v){
        $args = '';
        $untargs = '';
        if(Route::isRoutedMethod($v)){
            $p = explode('@', $v);   
            if(method_exists($p[0], $p[1])){
                // use reflection to get the parameters needed
                $method_params = getMethodParams($p[0], $p[1]);
                foreach($method_params as $mp){
                    $def_value = '';
                    if(is_string($mp['default_value']))
                        $def_value = "'{$mp['default_value']}'";
                    else
                        $def_value = is_null($mp['default_value'])?'null':$mp['default_value'];
                    $args .= "\${$mp['name']}" . (($mp['optional']==true)?(' = ' . $def_value):'') . ',';
                    $untargs .= "\${$mp['name']},";
                }
                $args = substr($args,0, strlen($args)-1);
                $untargs = substr($untargs,0, strlen($untargs)-1);
                $is_static = isStaticMethod($p[0], $p[1]);
                if($is_static)
                    $myfunc = $mystafunc;
                else
                    $myfunc = ($p[0] == '\Janssen\App')?$mycncfunc_woni:$mycncfunc;
                $ftc = str_replace('__NAME__', $k, $myfunc);
                $ftc = str_replace('__ARGS__', $args, $ftc);
                $ftc = str_replace('__CLASS__', $p[0], $ftc);
                $ftc = str_replace('__METHOD__', $p[1], $ftc);
                $ftc = str_replace('__UNTARGS__', $untargs, $ftc);
                // do on-the-fly function
                eval($ftc);
            }
        }
    }
}

/**
 * Gets method parameters
 * 
 * Use reflection object to get the arguments of a class method
 * this will be useful to inject parameters in order
 * 
 * @see https://stackoverflow.com/a/3387672
 * @param String $class
 * @param String $method
 * @return Array
 */
function getMethodParams($class, $method) {
    $ret = [];
    $r = new ReflectionMethod($class, $method);
    $params = $r->getParameters();
    foreach ($params as $param) {
        //$param is an instance of ReflectionParameter
        $ret[] = ['name' => $param->getName(), 
            'optional' => $param->isOptional(),
            'default_value' => ($param->isOptional()?$param->getDefaultValue():null)];
    }
    return $ret;
}

function isStaticMethod($class, $method)
{
    $r = new ReflectionMethod($class, $method);
    return $r->isStatic();
}

/* * * * * * * * INTERNAL FUNCTIONS * * * * * * * */

/**
 * Get the class name without namespace
 * 
 * @see https://www.php.net/manual/en/function.get-class.php#114568
 *
 * @param String $class
 * @return void
 */
function get_class_name($class)
{
    $classname = (is_object($class))?get_class($class):$class;
    if ($pos = strrpos($classname, '\\')) return substr($classname, $pos + 1);
    return $pos;
}

function transform_to_class_name($text)
{
    // check if the first character is slash
    while (substr($text,0,1) == "\\" || substr($text,0,1) == "/"){
        $text = substr($text,1);
    }
    // determine if first letter of $text is lowercase, if so
    // make it uppercase.
    $ascii_code = ord($text); 
    if ($ascii_code < 65 || $ascii_code > 90)
        $text = ucfirst($text);
    //return ucfirst(strtolower($text));
    return $text;
}

function custom_exception_handler(\Exception $e)
{
    echo "exception!";
}

/**
 * @todo handle the exceptions more flawlessly!! Catching an exception
 * to throw another???
 */
function custom_error_handler($code, $message, $file, $line, $o = null)
{
    $msg = "$message in $file@$line";
    error_log($msg);
    $ps = [];
    $ps[] = ErrorResponse::makePrependableStackItem($file, $line);
    throw new \Janssen\Helpers\Exception($message, $code, '', $ps);    
}
set_error_handler('custom_error_handler');

/**
 * GLobal error function to make quick error responses
 *
 * @param string $message error message
 * @param integer $code http error code
 * @return Response
 */
function error($message, $code = 500) : Response
{
    return App::errorResponse($message, $code);
}

/**
 * Global redirect function to make quick redirect responses
 *
 * @param string $to path to make the redirection
 * @return Response
 */
function redirect($to = '/') : Response
{
    return App::redirectResponse($to);
}

/**
 * Retrieves the needed field from volatile data and erases the 
 * field as this is only one-time use
 *
 * @param String $name
 * @return void
 */
/*
function old($name = null)
{
    $o = Janssen\Engine\Session::getValue('_volatile_data');
    Janssen\Engine\Session::removeField('_volatile_data');
    if(empty($name)){
        return $o;
    }        
    else{
        $v = empty($o[$name])?null:$o[$name];
        unset($o[$name]);
        Janssen\Engine\Session::setValue('_volatile_data', $o);
        return $v;
    }
}
*/

/**
 * Register a second autoload register. If the composer one fails
 * we'll reach here, and of course it only will happen if we got an
 * error loading the class.
 * 
 * Good trick, doesn't??
 */
spl_autoload_register('loadClass2');
function loadClass2($class)
{
    // if we reach here and the class is not loaded, then we can 
    // assume there was an error and throw the correspondient exception
    throw new \Janssen\Helpers\Exception("Class $class not found", null, 'Contact administrator');    
}