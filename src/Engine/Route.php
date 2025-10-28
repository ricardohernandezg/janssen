<?php 

namespace Janssen\Engine;

// use Countable;
use Janssen\Helpers\Regexer;
use Janssen\Helpers\Exception;
use Janssen\Helpers\Encrypt;
use Janssen\Engine\Request;
use Janssen\Engine\Config;

class Route 
{
    /**
     * Saves the last succesfully found route to avoid searching it 
     * again with all the load that the regex brings
     *
     * @var array
     */
    protected static $current = [];

    /**
     * Saves all the routes that were configured
     *
     * @var array
     */
    public static $routes = [];

    public static function setRoutes(Array $routes = [])
    {
        self::$routes = $routes;
        // try to get the route from path
        self::getByPath(Request::getFullPath());
    }

    public static function getByPath($path)
    {
        // find a exclusive route, the first check will be returned
        $pd = Request::getPretendedSubdomain();
        $i = self::findByPath($path, 0, $pd);
        
        if($i !== FALSE && !empty(self::$routes[$i])){
            self::$current = self::$routes[$i];
            return self::$routes[$i];
        }else
            throw new Exception("Path '$path' not routed", 404);
            
    }

    public static function getAllByPath($path)
    {
        $max = count(self::$routes);
        $res = [];
        $i = 0;
        do{
            $i = self::findByPath($path, $i);
            if($i){
                $res[$i] = self::$routes[$i];
                $i++;
            } 
        }while($i !== false); 
        return $res;
    }

    /**
     * Extract parameters from get path
     * We'll take every named item in config and put the value 
     * of matched regex in the same order as passed.
     * 
     * If no names are found for a specific path, parameters will be
     * ommited
     * 
     */
    public static function getParameters($path)
    {
        // find a exclusive route, the first check will be returned
        $route = self::getCurrent();
        if(empty($route)){
            $idx = self::findByPath($path);
            $route = self::$routes[$idx];
        }
        $ret = [];
        if(empty($route['vars']))
            return $ret;
        else{
                
            // process the parameters and return in array
            $stripped_path = explode('/', $path);
            if(is_array($stripped_path))
                array_shift($stripped_path);
                
            $stripped_route = explode('/', $route['path']);
            if(is_array($stripped_route))
                array_shift($stripped_route);

            if(is_array($route['vars']))
                $named_params = $route['vars'];
            else{
                $named_params = [];
                $pc = explode(',', $route['vars']);
                foreach($pc as $v)
                {
                    $named_params[] = trim($v);
                }
            }
                
            $i = 0;
            foreach($stripped_route as $k=>$v){
                try{
                    $ir = Regexer::isRegex($v);
                }catch(\Exception $e){
                    $ir = false;
                }
                        
                if($ir)
                    {
                        $ret[$named_params[$i]] = $stripped_path[$k];
                        $i++;
                    }
                }
        
        /*
        }else
            throw new Exception("Route $path not found", 400);*/

        return $ret;
        }
    }

    /**
     * Search in routes configuration array for a match and
     * returns the index of matched member, FALSE if not found
     * 
     * @todo change this to make it faster. Maybe finding a way to key the 
     * route when user doesn't provide a route name
     * 
     */
    private static function findByPath($path, $offset = 0, $subdomain = '')
    {
        $found = $ret = false;

        for($i = $offset; $i < count(self::$routes); $i++)
        {
            if(isset(self::$routes[$i]))
                $v = self::$routes[$i];
            else
                break;

            if(!self::isWellFormed($v))
                throw new Exception('Error in route', 500);

            if(isset($v['domain']) && !empty($subdomain) && $v['domain'] !== $subdomain)
                continue;

            // take each bracketed sentence in $v and transform it
            // to regex
            $cr = self::bracketedToRegex($v['path']);
            $pte = "/^{$cr}$/";
            if(Regexer::isRegex($pte)){
                // evaluate
                $r = preg_match($pte, $path);
                if($r === FALSE)
                    throw new Exception("Regex to route $i not well formed");
                $found = ($r > 0);
            }else{
                // make direct match
                $found = (($v[0] ?? '') === $path);
            }
            
            if($found){
                $ret = $i;
                break;
            }       
        }

        return $ret;
    }

    public static function isRoutedMethod($action)
    {
        $er = '/^[\\\\\w]+@[\w]+$/';
        $a = [];
        $r = preg_match($er, $action);
        return ($r > 0);
    }

    /**
     * Returns the last succesfully found route
     *
     * @return Array
     */
    public static function getCurrent()
    {
        return self::$current;
    }

    private static function bracketedToRegex($text)
    {
        // get all coincidences and transform to correct regex
        $matches = [];
        $er = '/\{(.+)\}/mU';
        $i = preg_match_all($er,$text,$matches, PREG_OFFSET_CAPTURE);
        // each match comes with complementary info that will
        // allow to remake the full text in correct regex
        if($i > 0){
            $ret = '';
            $last = 0;
            // we need to know if after the last match there is text that
            // needs to be passed
            $last_index = count($matches[1]) - 1;
            $last_match_pos = $matches[1][$last_index][1] + strlen($matches[1][$last_index][0]) + 1;
            
            $over_text = (strlen($text) > $last_match_pos)?substr($text, $last_match_pos):'';
            foreach($matches[1] as $k=>$match){
                $t = substr($text, $last, $match[1] - $last -1);
                // transform this partial
                $d = Regexer::transform($t);
                // paste the matched and 
                $ret .= $d . $match[0];
                $last = $match[1] + strlen($match[0]) + 1;
            }
            $ret .= Regexer::transform($over_text);
        }else
            $ret = Regexer::transform($text);
        
        return $ret;

    }

    /**
     * UTILITY FOR ROUTE SHOWING 
     */
    public static function hrefTo($dst)
    {
        $dst = trim($dst);
        if(substr($dst, 0,1) == '/'){
            // if dest starts with slash means its a path
            $ddst = substr($dst, 1);
            $uri = Request::getURI();
            $proto = Request::getProtocol();
            if(strtolower($proto) == 'http' && Config::get('force_https', true)){
                $uri = str_replace('http://', 'https://', $uri);
            }
            return $uri . $ddst;
        }else{
            // if dest doesn't start with slash means its a named path
            /**  @todo this feature is pending!! */
            throw new Exception('Named routes are not implemented yet! Please use a path starting with \'/\'', 500);
        }
    }

    public static function back()
    {
        return Request::back();
    }


    /**
     * ENCRYPTION AND DECRYPTION METHODS
     */
    public static function encrypt($route)
    {
        /**
         * @todo add the timestamp to randomize the cipher when auth is done
         */
        if(substr($route, 0,1) == '/') $route = substr($route, 1);
        $er = Config::get('encrypt_route', true);
        if($er == "true"){
            $e = Encrypt::encrypt($route);
            return "/$e";
        }else
            return "/$route";
    }

    public static function decrypt($payload)
    {
        $er = Config::get('encrypt_route', true);
        if($er == "true")        
            return Encrypt::decrypt($payload);        
        else
            return $payload; 
    }

    private static function isWellFormed($route)
    {
        return !(empty($route['path']) || empty($route['resolver']));
    }
}