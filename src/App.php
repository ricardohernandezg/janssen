<?php

namespace Janssen;

use Janssen\Engine\Config;
use Janssen\Engine\Event;
use Janssen\Engine\Header;
use Janssen\Engine\Preprocessor;
use Janssen\Engine\Request;
use Janssen\Engine\Response;
use Janssen\Engine\Route;
use Janssen\Engine\Session;
use Janssen\Engine\Validator;
use Janssen\Helpers\Database;
use Janssen\Helpers\Database\Adaptor;
use Janssen\Helpers\Exception;
use Janssen\Helpers\Response\ErrorResponse;
use Janssen\Helpers\Response\JsonResponse;
use Janssen\Helpers\Response\RawResponse;
use Janssen\Helpers\FlashMessage;
use Janssen\Resource\ClassResolver;
use Throwable;

class App
{
    use Traits\InstanceGetter;

    /**
     * This is the base class for the core app.
     * It has the sole responsability of receive the request,
     * process it and give response using the designated
     * objects
     */

    private static $version = '0.8.2';
    private static $name = 'Janssen Core';
    private static $app_path;
    private static $s_assets_path;

    private static $request;
    private $header;
    private $response;
    private $validator;  // validator instance that was used to validate request.

    private static $engine_config;

    public static function name()
    {
        return self::$name;
    }

    public static function version()
    {
        return self::$version;
    }
   
    public function init(String $app_path)
    {

        Event::invoke('app.beforeinit', $this);

        $app_path = trim($app_path);
        if (empty($app_path)) 
            throw new Exception("App configuration is corrupt", 500, 'Contact administrator');
        if(substr($app_path,-1,1) == '/') 
            $app_path = substr($app_path,0,strlen($app_path) -1);

        self::$app_path = $app_path;

        // load external aliases
        $ca_candidate = self::getPathCandidate('aliases');
        $external_aliases = (is_file($ca_candidate)) ? (include $ca_candidate) : [];
        if(!empty($external_aliases))
            ClassResolver::append($external_aliases);

        // make the global functions mapped to aliases to be called
        // from everywhere in the app
        $ugf_conf_candidate = self::getPathCandidate('functions');
        $user_global_functions = (is_file($ugf_conf_candidate)) ? (include $ugf_conf_candidate) : [];
        create_global_functions($user_global_functions);

        // load config
        Config::loadConfigFromEnv($app_path . '/..');
    
        // load app config
        $engine_conf_candidate = self::getPathCandidate();
        Config::append((is_file($engine_conf_candidate)) ? (include $engine_conf_candidate) : []);
        self::$engine_config = Config::get();

        // create a header to the response
        $this->header = new Header;            
        
        // start session
        Session::start();
        
        // the only way to know what user want? the request
        Request::fill();
        
        // set self request
        self::$request = new Request;
        
        // load previous messages from session
        FlashMessage::bulkLoadFromSession();
        
        // fix path if engine needed
        if(self::$engine_config['relax_route'])
            self::$request::fixPath();
        
        // instanciate database if setted up
        $this->load_database_connection();
        
        Event::invoke('app.afterinit', $this);

        // we are ready to start!! turn off errors
        error_reporting(Config::get('php_error_reporting', 0));
    }

    // we'll put the running logic here, but is possible to modify this to
    // move it to another class
    public function run()
    {
        /**
         * @todo solve .htaccess when trying to access non existent files
         * inside public dir
         */
        
        try {
            $rm = self::$request->method();
            // as we process routing only for GET requests but the preprocessing is
            // for all types of requests, we need to check the routes before preprocessing
            if ($rm == 'GET') {
                $routes_conf_candidate = self::getPathCandidate('routes');
                $routes = (is_file($routes_conf_candidate)) ? (include $routes_conf_candidate) : [];
                Route::setRoutes($routes);
            }

            // run preprocessor, we expect a preprocessor to answer true, if
            // any preprocessor answers Response is output directly if false, throw exception
            $r = Preprocessor::processHandlers(self::$engine_config['preprocessors'], self::$request);
            if ($r instanceof Response) {
                $res = $r;
                goto response_section;
            }

            // if request method is GET, we'll use the routes.
            // if request method is POST, PUT or DELETE we will take the route from encrypted post
            if ($rm == 'GET') {
                $path = self::$request->getPath();
                $route = Route::getCurrent();
                if(empty($route))
                    $route = Route::getByPath($path);
                $action = $route['resolver'];

                // extract parameters from path
                $parameters = Route::getParameters($path);
                foreach ($parameters as $name => $value) {
                    self::$request->registerParameter($name, $value);
                }

                // if action is a callable, use it and process the response.
                if (!is_array($action) && Route::isRoutedMethod($action)) {
                    $p = explode('@', $action);
                    $res = $this->makeTheCall($p[0], $p[1]);
                } elseif (is_array($action)) {
                    /**
                     * @todo this must be refactorized. What happens if the renderer 
                     * is not a ViewResponse?
                     * 
                     * the user possibly configured template with a view handler
                     * we must have a template and a render that must be a heredor
                     * of Janssen\Response
                     */
                    $page = $action[0];
                    $renderer = ClassResolver::resolve($action[1]);
                    if($renderer && $renderer instanceof Response){
                        $res = $renderer->render(['filename' => $page]);
                    } else
                        throw new Exception("Indicated renderer for $page doesn't exists!", 500, 'Contact Administrator');
                } else {
                    $p = new RawResponse;
                    $p->loadPage($action);
                    $res = $p;
                }
            } elseif (in_array($rm, ['POST', 'PUT', 'DELETE'])) {

                $ua = self::$request->getUserAction();
                $method = $ua['method'];
                /**
                 * @todo remove this in production!
                 */
                $ca = 'Called-action: ' . $ua['controller'] . '/' . $ua['method'];
                $this->header->setMessage($ca);

                /**
                 * @todo put the validator inside Request to not instanciate more than 
                 * once
                 */
                $validator_name = transform_to_class_name($ua['controller']) . 'Validator';
                $validator_fullname = "App\\Validator\\" . $validator_name;
                $validator_method = 'validate' . transform_to_class_name($method);
                $v = $this->makeTheCall($validator_fullname, $validator_method);
                if ($v === true) {
                    $controller_name = transform_to_class_name($ua['controller']) . 'Controller';
                    $controller_fullname = "App\\Controller\\" . $controller_name;
                    // call the function
                    $res = $this->makeTheCall($controller_fullname, $method);
                } else {
                    // the validation didn't pass. Make a response with that
                    $ve = $this->validator->getValidationErrors();
                    Event::invoke('app.onvalidationerror', $this, $ve);
                    if(self::$request->expectsJSON()){
                        $this->header->setMessage('',400, true);
                        if (self::getConfig('detail_validator_error'))
                            $res = ['error' => ['validator' => $ve]];  // let's try with a 
                        else
                            $res = ['error' => 'VALIDATOR_REJECT_REQUEST'];
                    }else{
                        /**
                         * @todo We should send the validator errors detailed here no matter
                         * what the config says. As this would go in Session and will be erased
                         * on next use.
                         * 
                         * @todo check what happens when sending arrays to FlashMessage
                         */
                        FlashMessage::add('general', 'Data validation error', 'error');
                        $res = \redirect(self::$request->back());
                    }
                    
                }
            }
        } catch (\Janssen\Helpers\Exception $e) {            
            $res = $e;
        } catch (Throwable $e) {
            $s = $e->getTrace();
            $res = new ErrorResponse();
            $h = new Header;
            $h->setMessage('', 500, true);
            $res->setException($e)
                ->setHeader($h);
        }
        response_section: 
        return $this->handleResponse($res);
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
    private function getMethodParams($class, $method)
    {
        $ret = [];
        $r = new \ReflectionMethod($class, $method);
        $params = $r->getParameters();
        foreach ($params as $param) {
            //$param is an instance of ReflectionParameter
            $ret[] = ['name' => $param->getName(),
                'optional' => $param->isOptional(),
                'type' => $param->getType() ? $param->getType()->getName() : null,
                'default_value' => ($param->isOptional() ? $param->getDefaultValue() : null)];
        }
        return $ret;
    }

    private function load_database_connection()
    {
        $dc = self::getConfig('connections')[self::getConfig('default_connection')];
        $dbe = $dc['driver'];
        if ($dbe) {
            $adaptor = ClassResolver::resolve($dbe);
            if ($adaptor && $adaptor instanceof Adaptor){ 
                $cf = $adaptor->getAllConfigFields();
                foreach ($cf as $k => $v) {
                    $adaptor->setConfigField($k, empty($dc[$k]) ? null : $dc[$k]);
                }
                Database::setAdaptor($adaptor);
            } else {
                throw new Exception('Database class not implemented or inexistent', 500);
            }
        }
    }

    /**
     * Make a map of method parameters with RequestHelper
     * variables passed and validated. Intended only for internal
     * use
     *
     * @param Array $param_names
     * @return Array
     */
    private function makeParameterArray($param_names)
    {
        $ret = [];
        foreach ($param_names as $v) {
            if($v['type'] == 'Janssen\Engine\Request'){
                $ret[$v['name']] = self::$request;
                continue;
            }

            $p = self::$request->parameter($v['name']);
            if ($p || $p == 0) {
                $ret[$v['name']] = $p;
            } else {
                $ret[$v['name']] = ($v['optional'] ? $v['default_value'] : self::$request->nullValue());
            }
        }
        return $ret;
    }

    /**
     * Here we make the parameter attach and function call.
     * The parameters are assumed directly from request and if
     * the function accept it we inject it.
     *
     * @param String $class
     * @param String $method
     * @return void
     */
    private function makeTheCall($class, $method, $use_params = true)
    {
        if (class_exists($class)) {
            $instance = new $class;

            // if user wants to get detailed validator error, we need to save the object to use outside
            $is_validator = ($instance instanceof Validator);
            if($is_validator){
                $this->validator = $instance;
                $use_params = true;
            }

            if (method_exists($instance, $method)) {
                // sometimes we are pretty sure we won't use parameters
                // set $use_params to false to make the call directly
                if ($use_params) {
                    // get the parameters and inject needed
                    // validator will inject the Request always as first paremeter and ignore others. 
                    if(!$is_validator)
                        $method_params = $this->getMethodParams($instance, $method);    
                    else{
                        $method_params = [['type' => 'Janssen\Engine\Request', 'name' => 'request']];
                    }
                    $params = $this->makeParameterArray($method_params);
                    $ret = call_user_func_array([$instance, $method], $params);

                } else {
                    $ret = $instance->$method();
                }

            } else {
                throw new Exception("Method $method doesn't exists in class " . get_class_name($class), 0);
            }
        } else {
            throw new Exception("Controller " . get_class_name($class) . " doesn't exists", 0);
        }

        return $ret;
    }

    private function handleResponse($response)
    {
        if ($response instanceof Response) {
            // here we ADD headers, not resplace, as the response could have set
            // its own
            ($response->hasHeaders())?$response->addHeader($this->header):$response->setHeader($this->header);
            $ret = $response;

        } elseif ($response instanceof Exception) {
            $er = new ErrorResponse();
            $http_code = $response->isHttpCode() ? $response->getCode() : 500;
            $this->header->setMessage('', $http_code, true);
            $er->isJson(self::$request->expectsJSON())
                ->setException($response)
                ->setHeader($this->header);
            $ret = $er;
            
        } else {
            // response is not instance of Response, we'll make a new one
            $o = (is_array($response) || self::$request->expectsJSON())?(new JsonResponse):(new RawResponse);
            $o->setContent($response)
                ->setHeader($this->header);
            $ret = $o;

        }

        // here we should make the postprocessing if applyable
        return $ret;
    }

    /**
     * Adds or modify an engine configuration at runtime
     *
     * @param String $config
     * @param Any $value
     * @return void
     */
    public function setEngineConfig($config, $value)
    {
        $this->engine_config[$config] = $value;
    }

    /**
     * Gets the value of a engine config
     *
     * @param String $config
     * @return Any
     */
    public static function getConfig($config)
    {
        return empty(self::$engine_config[$config]) ? false : self::$engine_config[$config];
    }

    public static function getPathCandidate($which = 'engine')
    {
        return self::appPath() . "/../app/Config/$which.php";
    }

    public static function appPath()
    {
        return self::$app_path . '/';
    }

    public static function url()
    {

        $url = Request::getURI();
        return $url;
    }

    /**
     * Get assets path relative to /public
     */
    public static function assets($type = '', $filename = '')
    {
        $type = trim($type);
        $filename = trim($filename);

        $r = Request::getURI();
        $p = self::getConfig('assets');

        if(trim(substr($r,-1,1)) !== '/')
            $r .= '/';

        if ($p && $p[$type]) {
            $ts = trim($p[$type]);
            $fs = (substr($ts,-1,1) === '/')?'':'/';
            $r .= ((empty($p[$type])?'':$p[$type]) . $fs);
        }

        $ret = $r . $filename;
        if (substr($ret,-1,1) === '/')
            $ret = substr($ret,0, strlen($ret)-1);
        
        return $ret;
    }

    /**
     * @todo think how to send flash messages with this approach
     */
    public static function redirectResponse($to = '/')
    {
        
        $to = trim($to);
        // if the redirect url starts with '/' we'll assume
        // the user is redirecting to a internal route. 
        if(substr($to, 0,1) == '/')
            $to = self::$request->getURI() . substr($to,1);

        $h = new Header;
        $h->setMessage("Location: " . $to, 302, true);
        $r = new RawResponse;
        $r->setHeader($h)
            ->setContent('');
        if(FlashMessage::howMany() > 0){
            Session::setValue(FlashMessage::getSessionVarName(), FlashMessage::all());
        }
        return $r;
    }

    public static function errorResponse($message = 'Internal error', $code = 500)
    {
        $er = new ErrorResponse($message, $code);
        $h = new Header;
        $h->setMessage($message, $code, true);
        $er->setHeader($h);
        return $er;
    }    

    public function getCurrentHeader()
    {
        return $this->header;
    }

}
