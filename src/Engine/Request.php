<?php 

/**
* Request 
*
* This class handles all data that is sent from client and saves
* variables from server to be used internally. All validated data is 
* saved in this object instance and MUST BE used to all data retrieving
* inside the project to guarantee the quality of data.
* 
*
* @package  Janssen\Engine
* @todo We could need a function that checks the existence of parameter just returning bool
*/

namespace Janssen\Engine;

use Janssen\Engine\Parameter;
use Janssen\Engine\Mapper;
use Janssen\Engine\Config;
use Janssen\Engine\Session;
use Janssen\Helpers\Exception;
//use Janssen\Helpers\Auth;
use Janssen\Helpers\Guard;

class Request
{

    use \Janssen\Traits\InstanceGetter;
    use \Janssen\Traits\StaticCall;

    /**
     * Set of bags that save variables. Currently
     * used bags are $_GET, $_POST, $_FILES and
     * headers
     *
     * @var array
     */
    private static $bags = [];

    /**
     * The method to the current request
     *
     * @var string
     */
    private static $method;
    
    /**
     * The referrer of the request
    *
    * @var String
    */
    private static $from;
    
    /**
     * Base URI calculated at start of request
    *
    * @var URL
    */
    private static $url;

    /**
     * The path must be fixed at construct
     */
    private static $fix_path = false;

    /**
     * The request is to an IP address instead of domain
     * 
     * @var bool
     */
    private static $host_is_domain = true;

    /**
     * Stores the user called action when decrypted
     *
     * @var array
     */
    private static $userAction = [];

    /**
     * Parameter object
     *
     * @var Parameter
     */
    private static $_parameters;

    /**
     * Indicates wether a request expects a json response
     *
     * @var Bool
     */
    private static $expects_json = false;

    /**
     * Guards that were set to authorize or authenticate a given request
     *
     * @var array
     */
    private static $intended_guards = [];

    /**
     * Guard watching this request currently
     *
     * @var Object
     */
    private static $guarded_by = false;

    /**
     * Guard who autorizes this request
     *
     * @var String
     */    
    private static $authorized_by = '';

    /**
     * The current route
     * 
     * @var Array
     */
    private static $current_route;

    /**
     * The pretended host in the route
     * 
     * @var String
     */
    private static $pretended_host = '';

    /**
     * Fill out the bags with all the data. This function is ran
     * only by process.php when starting.
     *
     * @return void
     */
    public static function fill()
    {
        // fill server
        foreach($_SERVER as $k=>$v){
            self::$bags['SERVER'][$k] = $v;
            if(strtoupper($k) == 'REQUEST_METHOD')
                self::$method = strtoupper($v);
        }
        // fill post 
        // we set default post as empty array as we can handle
        // empty requests
        self::$bags['POST'] = [];
        foreach($_POST as $k=>$v){
            self::$bags['POST'][$k] = $v;
        }        
        // fill get
        foreach($_GET as $k=>$v){
            self::$bags['GET'][$k] = $v;
        }
        // fill file
        foreach($_FILES as $k=>$v){
            if(is_array($v)){
                if(substr($v['name'], strlen($v['name'])-5) == '.jpeg')
                    $v['name'] = substr($v['name'], 0, strlen($v['name'])-5) . '.jpg';
            }
            self::$bags['FILES'][$k] = $v;
        }
        // fill headers 
        $h = apache_request_headers();
        foreach($h as $k=>$v){
            self::$bags['HEADER'][strtolower($k)] = $v;
        }
        
        // fill Uri and payload
        $domain = Config::get('domain');
        if(is_null($domain))
            throw new Exception('Domain setting not set. We need this setting to continue', 500, 'Contact administrator to fix this issue');

        self::$url = new URL(self::getFullUrl(), $domain, self::$fix_path);        
        // fill back
        self::calculateFrom();
        // expecting json?
        self::setExpectsJSON();
        // fill authentication
        self::setAuthGuards();
        // fill parameter
        self::$_parameters = new Parameter();   
    }

    /**
     * Sets the guards that can approve a request
     *
     * @param Array $guards
     * @return void
     */
    public static function setIntendedGuards($guards)
    {
        self::$intended_guards = $guards;
    }

    /**
     * Returns all the guards that were set up to approve a given
     * request
     *
     * @return Array
     */
    public static function getIntendedGuards()
    {
        return self::$intended_guards;
    }

    /**
     * Sets the Guard for this request
     *
     * @param \Janssen\Helpers\Guard $guard
     * @return Request
     */
    /*
    public static function setGuard(Guard $guard)
    {
        self::$guarded_by = $guard;
    }
    */

    /**
     * Returns the Guard for this request
     *
     * @return Array
     */
    /*
    public static function getGuards()
    {
        return self::$guarded_by;
    }
    */

    /**
     * Saves the name of guard who autorized the request
     *
     * @param String $guard_name        
     * @return void
     */ 
    public static function authorizedBy($guard_name)
    {
        self::$authorized_by = $guard_name;
    }

    /**
     * Returns the guard who authorizes the request
     *
     * @return String
     */
    public static function whoAuthorizes()
    {
        return self::$authorized_by;
    }

    /**
     * Returns true if the request was made to a domain false if IP address
     *
     * @return String
     */
    public static function isValidDomain()
    {
        return self::$host_is_domain;
    }

    private static function getFullUrl()
    {
        $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $requestUri = $_SERVER['REQUEST_URI'];
        return $protocolo . $host . $requestUri;
    }

    /**
     * Only useful for POST, PUT and DELETE request
     * Extracts the last part of query string to get the ciphered
     * route
     *
     * @return string|bool
     */
    public static function getQueryStringPayload()
    {
        return self::$url->payload();
    }

    public static function setUserAction($class, $method)
    {
        $class = transform_to_class_name($class);

        self::$userAction = [
            'controller' => $class,
            'method' => $method
        ];
    }

    /**
     * Returns the action called by user
     *
     * @return Array
     */
    public static function getUserAction()
    {
        return self::$userAction;
    }

    /**
     * Returns the action called by user as path
     *
     * @return void
     */
    public static function getUserActionAsPath()
    {
        $ua = self::getUserAction();
        if(!empty($ua))
            return strtolower($ua['controller']) . "/" . strtolower($ua['method']);
        else
            return "";
    }

    /**
     * Indicates if a bag is valid to use
     *
     * @param String $name
     * @return boolean
     */
    public static function isValidBag($name)
    {
        $name = strtoupper($name);
        return isset(self::$bags[$name]);
    }

     /**
     * Returns all bag contents of all bags
     *
     * @param String $bag
     * @return Array
     */
    public static function all($bag = null){
        if(is_null($bag))
            return self::$bags;
        elseif(strtoupper(trim($bag)) == 'PARAMETER')
            return self::$_parameters;
        else{
            $bag = strtoupper($bag);
            return isset(self::$bags[$bag])?self::$bags[$bag]:[];
        }
    }

    /**
     * Gets a variable from $_SERVER
     *
     * @param String $name
     * @return String|null
     */
    public static function server($name){
        return isset(self::$bags['SERVER'][$name])?self::$bags['SERVER'][$name]:null;
    }

    /**
     * Gets a variable from HTTP Request headers
     *
     * @param String $name
     * @return String|null
     */    
    public static function header($name, $default = null){
        $name = strtolower($name);
        return isset(self::$bags['HEADER'][$name])?self::$bags['HEADER'][$name]:$default;
    }

    /**
     * Gets a variable from $_POST
     * 
     * Not intended for app use. Use instead parameter::post
     *
     * @param String $name
     * @param String $default
     * @return String|$default
     */
    public static function post($name, $default = null){
        return isset(self::$bags['POST'][$name])?self::$bags['POST'][$name]:$default;
    }

    /**
     * Allos to alter a post value or add a new one
     *
     * @param String $name
     * @param String $value
     * @return void
     */
    public static function alterPost($name, $value = null)
    {
        self::$bags['POST'][$name] = $value;
    }

    /**
     * Gets a variable from $_GET
     * 
     * Not intended for app use. Use instead parameter::get
     *
     * @param String $name
     * @param String $default
     * @return String|$default
     */
    public static function get($name, $default = null){
        return isset(self::$bags['GET'][$name])?self::$bags['GET'][$name]:$default;
    }

    /**
     * Gets a variable from $_FILES
     *
     * @param String $name
     * @return String|null
     */
    public static function file($name){
        $f = isset(self::$bags['FILES'][$name])?self::$bags['FILES'][$name]:false;
        if($f)
            return ($f['error'] == 0)?$f:null;
        else
            return null;
    }

    /**
     * Gets a parameter from parameters bag
     *
     * @param String $name
     * @param String $default
     * @return String|$default
     */    
    public static function parameter($name, $default = null){
        return self::$_parameters->getMember($name, $default);
    }
    
    /**
     * Returns the full Parameter object
     *
     * @return Parameter
     */
    public static function parameters()
    {
        return self::$_parameters;
    }

    /** 
     * Add value to parametes bag
     * 
     * @param String $name
     * @param String|Integer|Boolean $value
     * @return Object
     */
    public static function registerParameter($name, $value)
    {
        self::$_parameters->setMember($name, $value);
        return self::me();
    }

    /**
     * Returns the full mapping used to validate the request
     *
     * @return Array
     */
    public static function mapping()
    {
        return self::$_parameters->getMapping();
    }

    /**
     * Maps input based on mapper rules and saves the mapping into 
     * the parameters object
     *
     * @param Mapper $mapper
     * @param String $bag
     * @return Object
     */
    public static function map(Mapper $mapper, $bag = 'post')
    {
        $input = self::all($bag);
        if($mapper->isAuto()){
            foreach($input as $k=>$v){
                self::registerParameter($k, $v);    
            }
        }else{
            $map = $mapper->getMap();
            self::parameters()->clear();
            foreach($input as $k=>$v){
                // find in map the correspondent name
                $kk = array_search($k, $map, true);
                if($kk !== false)
                    self::registerParameter($kk, $v);    
            }
        }
        self::parameters()->setMapping($mapper);
        return self::me();
    }

    /**
     * Return all the parameter fields mapped
     *
     * @return Array
     */
    public static function getMapped()
    {
        return self::$_parameters->getAll();
    }

    /**
     * @todo try to determine how to change index.php, it could be another index file
     */
    /*
    private static function calculatePath()
    {
        $ru = self::server('REQUEST_URI');
        $sn = self::server('SCRIPT_NAME');
        // extract directory from script name
        $snd = dirname($sn);
        if ($snd == "/")
        $r = $ru;
        else  
        $r = str_replace($snd, '', $ru);
        $er = '/^[\/]*index\.php/m';
        // remove the /index.php if was sent by user request
        $r = trim(preg_replace($er, '', $r));
        self::$path = ($r == '')?'/':$r;
    }
    */

    private static function calculateFrom()
    {
        $t = self::server('HTTP_REFERER');
        if(\is_null($t))
            $t = self::server('SCRIPT_URI');
        if(\is_null($t)){
            $scheme = self::getRealScheme();
            $host = self::server('HTTP_HOST');
            $sn = self::server('SCRIPT_NAME');
            $t = "$scheme://$host/$sn";
        }
        self::$from = $t;
    }

    /*
    private static function calculateTo()
    {
        $t = self::server('HTTP_HOST');
        if(!empty($t)){
            $er = '/^(.*)\./mU';
            $a = [];
            $i = preg_match_all($er, $t, $a);
            if($i){
                self::$pretended_host = $a[1][0];
            }

        }
        
    }
    */

    /*
    private static function determineProtocol()
    {
        $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ??
            $_SERVER['HTTPS'] ??
            $_SERVER['REQUEST_SCHEME'];

        if($proto !== null && (in_array(strtolower($proto), ['https','on'])))
            self::$protocol = 'https';
        else
            self::$protocol = 'http';
    }
    */

    public static function getScheme()
    {
        if(Config::get('force_https', true) == true)
            return 'https';
        else
            return self::getRealScheme();
    }

    public static function getRealScheme()
    {
        return self::$url->protocol();
    }

    /** Alias for getRealScheme */
    public static function getProtocol()
    {
        return self::getRealScheme();
    }

    public static function getPath()
    {
        return self::$url->path();
    }

    public static function getFullPath()
    {
        return self::$url->path() . self::$url->payload();
    }

    public static function getUrl()
    {
        return self::$url;
    }
    
    public static function setMatchedRoute($route)
    {
        self::$current_route = $route;
    }

    /**
     * @return Array
     */
    public static function getMatchedRoute()
    {
        return self::$current_route;
    }

    /**
     * Returns the previous path or referrer
     *
     * @return String
     */
    public static function getPrevious()
    {
        return self::$from;
    }

    /**
     * Returns the subdomain in case it exists
     *
     * @return String
     */
    public static function getPretendedSubdomain()
    {
        return self::$url->fullSubdomain();
    }

    /**
     * Returns the host
     *
     * @return String
     */
    public static function getPretendedHost()
    {
        return self::$url->host();
    }

    /**
     * Alias method for getPrevious()
     *
     * @return String
     */
    public static function back()
    {
        return self::getPrevious();
    }

    /**
     * Returns the base URI calculated from request
     * It should be the base URL of web page
     *
     * @return String
     */
    public static function getURI()
    {
        return self::$url->protocol() . '://' . 
            self::$url->hostPort() . '/' . 
            self::$url->path() ;
    }

    /**
     * Gets the method to the current request
     *
     * @return string
     */
    public static function method()
    {
        return self::$method;
    }

    public static function expectsJSON()
    {
        return self::$expects_json;
    }

    public static function fixPath($value = true)
    {
        self::$fix_path = $value;
    }

    /**
     * Indicates if user request expects JSON response
     * based in Request HTTP header
     *
     * return void
     */
    private static function setExpectsJSON()
    {
        $json_headers = ['application/json'];
        $accept = self::header('accept', '*/*');
        $found = false;
        foreach($json_headers as $v){
            $f = str_replace('/', '\/', $v);
            $er = "/.*$f.*/";
            $found = preg_match($er, $accept);
            if($found > 0)
                break;
        }
        self::$expects_json = boolval($found);
    }

    /**
     * Fills guards from $_SESSION at request init
     * based in Request HTTP header
     *
     * return void
     */
    private static function setAuthGuards()
    {
        $g = Session::getValue(Guard::getGuardVarName());
        if($g)
            self::authorizedBy(key($g));
    }

}

class URL
{

    private $fullURL;
    
    private $protocol;
    
    private $host;
    
    private $port;

    private $path;

    private $queryString;

    private $payload;

    private $host_is_domain;

    private $domain;

    private $fullSubdomain;

    private $firstSubdomain;

    public function __construct($fullURL, $domain, $fixPath = false)
    {
        $parts = parse_url($fullURL);

        $this->fullURL = $fullURL;

        // Protocolo
        $this->protocol = $parts['scheme'] ?? '';

        // Host y puerto
        $this->host = $parts['host'] ?? '';
        $this->port = $parts['port'] ?? '';

        // Ruta
        $this->path = $parts['path'] ?? '';
        if($fixPath)
            $this->path = $this->fix($this->path);

        // Query string original
        $this->queryString = $parts['query'] ?? '';

        $this->payload = $this->queryStringToFriendlyPath($this->queryString);

        // Verificar si host es IP o dominio
        $this->host_is_domain = filter_var($this->host, FILTER_VALIDATE_IP) ? true : false;

        $si = $this->getSubdomainsInfo($this->host(), $domain);
        $this->firstSubdomain = $si['first'];
        $this->fullSubdomain = $si['full'];
    }

    // Convertir query string to friendly path
    private static function queryStringToFriendlyPath($queryString)
    {
        parse_str($queryString, $params);
        $friendlyPath = '';
        foreach ($params as $key => $value) {
            $friendlyPath .= '/' . urlencode($key) . '/' . urlencode($value);
        }
        return $friendlyPath;
    }

    /**
     * Removes the / from the given text
     *
     * @param String $text
     * @return String
     */
    private static function fix($text){
        if(!in_array($text, ['','/']) && substr($text, -1) =='/')
            return substr($text, 0, strlen($text)-1);
        
        return $text;
    }

    /**
     * Calculate the subdomain
     */
    private function getSubdomainsInfo($fullDomain, $baseDomain)
    {
        $fullDomain = strtolower($fullDomain);
        $baseDomain = strtolower($baseDomain);

        if (substr($fullDomain, -strlen($baseDomain)) === $baseDomain) {
            $subdomainPart = substr($fullDomain, 0, -strlen($baseDomain));
            $subdomainPart = rtrim($subdomainPart, '.');

            if (empty($subdomainPart)) {
                return ['first' => null, 'full' => null]; // No hay subdominio
            }

            $parts = explode('.', $subdomainPart);

            $firstSubdomain = $parts[0]; // el primer subdominio, puede ser "www"
            $fullSubdomain = implode('.', $parts); // todo el subdominio completo

            return ['first' => $firstSubdomain, 'full' => $fullSubdomain];
        } else {
            // Dominio base no coincide, retorna null
            return ['first' => null, 'full' => null];
        }
    }

    public function fullUrl()
    {
        return $this->fullURL;
    }

    public function hostPort()
    {
        return $this->host . ($this->port ? ':' . $this->port : '');
    }

    public function protocol()
    {
        return $this->protocol;
    }

    public function host()
    {
        return $this->host;
    }

    public function port()
    {
        return $this->port;
    }

    public function path()
    {
        return $this->path;
    }

    public function queryString()
    {
        return $this->queryString();
    }

    public function payload()
    {
        return $this->payload;
    }

    public function hostIsDomain()
    {
        return $this->host_is_domain;
    }

    /**
     * Returns the first level subdomain detected
     */
    public function firstSubdomain()
    {
        return $this->firstSubdomain;
    }

    /**
     * Returns the full subdomain detected
     */
    public function fullSubdomain()
    {
        return $this->fullSubdomain;
    }

    /**
     * Returns the nth level subdomain detected
     */
    public function nthSubdomain($level)
    {
        return $this->fullSubdomain;
    }    

    public function __toString()
    {
        return $this->fullUrl();
    }

}