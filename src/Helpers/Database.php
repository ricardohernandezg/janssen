<?php 

namespace Janssen\Helpers;

use Janssen\Helpers\Database\Adaptor;
use Janssen\Traits\SQLStatement;
use Throwable;

class Database
{

    use \Janssen\Traits\InstanceGetter;
    use \Janssen\Traits\StaticCall;    
    use SQLStatement;

    /**
     * Connection variable
     *
     * @var Object
     */
    protected static $_adaptor = false;

    //private static $_valid_engines = ['mysqli', 'pgsql'];

    private static $_connected = false;

    /**
     * Database engine to be used
     */
    protected static $_engine = false;

    /**
     * Internal configurations
     */
    protected static $_internal_config = [];

    /**
     * No map return arrays (instead return zero-based arrays)
     */
    protected static $_nomap_return = false;

    /**
     * Checks if there is connection object set
     */
    public static function isConnected()
    {
        return (self::$_adaptor->isConnected());
    }

    /**
     * Sets up the database adaptor to be used 
     * during the request
     *
     * @param Adaptor $adaptor
     * @return void
     */
    public static function setAdaptor(Adaptor $adaptor)
    {
        self::$_adaptor = $adaptor;
    }

    /**
     * Gets an instance of database adaptor to be used 
     * during the request
     *
     * @return Adaptor
     */
    public static function getAdaptor()
    {
        return self::$_adaptor;
    }

    /**
     * Sets fields for adaptor
     */
    public static function setAdaptorConfigField($field, $value)
    {
        self::$_adaptor->setConfigField($field, $value);
    }

    /**
     * Makes a query and returns mapped array with data
     *
     * @param String $sql
     * @return Array|Bool
     */
    public static function query($sql, ?Array $mapping = [], ?Array $bindings = [])
    {
        $ret = self::$_adaptor->query($sql);
        self::setFieldMapping();
        return $ret;
    }
  
    /**
     * Makes a database query and returns the last inserted id
     * 
     * Running other than a INSERT INTO statement here will have the 
     * effect that the engine throws
     *
     * @param String $sql
     * @return Integer|Bool
     */
    public static function insert($sql, ?Array $bindings = [])
    {
        return self::$_adaptor->insert($sql);
    }

    /**
     * Run a statement with no return
     * 
     * @return Bool
     */
    public static function statement($sql, ?Array $bindings = [])
    {
        return self::$_adaptor->statement($sql);
    }
   
    /**
     * Makes a query and returns only the first row
    *
    * @param String $sql
    * @return Array|Bool
    */
    public static function first($sql, ?Array $mapping = [], ?Array $bindings = [])
    {
        $r = self::query($sql);
        if ($r && isset($r[0])) {
            return $r[0];
        } elseif (is_array($r) && count($r) == 0){
            return $r;
        } else {
            return false;
        }
    }

   /**
     * Make the query as count(*)
     * 
     * @return int
     */
    public static function count($sql, ?Array $bindings = [])
    {
        if(!substr(strtoupper(trim($sql)),1,6) == 'SELECT')
            throw new Exception('Count only works for SELECT statements',500);
        
        // use a regex to detect if $sql has fields and replace with count(*)
        $re = '/^SELECT\s(.+)\sFROM.+$/igm';
        $m = [];
        $i = preg_match($re, $sql, $m);
        if($i !== false){
            // find the start and end point in the fields part of sql statement
            
        }
        //self::$query_mode = 3;
        //self::$fields = ['count(*) as count'];
        $r = self::me()->query($sql);
        return (int) $r['count'];
    }

    /**
     * Destroys connection object
     */
    public static function disconnect()
    {
        self::$_adaptor = null;
        self::$_connected = false;
    }

    public static function setFieldMapping($value = true)
    {
        return self::$_adaptor->setAutoFieldMapping($value);
    }

    
    public static function getLastError()
    {
        return self::$_adaptor->getLastError();
    }

}