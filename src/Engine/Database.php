<?php 

namespace Janssen\Engine;

use Janssen\Helpers\Database\Adaptor;
use Janssen\Helpers\SQLStatement;
use Janssen\Helpers\Exception;
use Throwable;

class Database
{

    use \Janssen\Traits\InstanceGetter;
    use \Janssen\Traits\StaticCall;    
    use \Janssen\Traits\GenericSQLSyntax\GenericFieldSyntax;

    /**
     * Connection variable
     *
     * @var Object
     */
    protected static $adaptor = false;

    //private static $_valid_engines = ['mysqli', 'pgsql'];

    private static $connected = false;

    /**
     * Database engine to be used
     */
    protected static $engine = false;

    /**
     * Internal configurations
     */
    protected static $internal_config = [];

    /**
     * No map return arrays (instead return zero-based arrays)
     */
    protected static $nomap_return = false;

    /**
     * Checks if there is connection object set
     */
    public static function isConnected()
    {
        return (self::$adaptor->isConnected());
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
        self::$adaptor = $adaptor;
    }

    /**
     * Gets an instance of database adaptor to be used 
     * during the request
     *
     * @return Adaptor
     */
    public static function getAdaptor()
    {
        return self::$adaptor;
    }

    /**
     * Sets fields for adaptor
     */
    public static function setAdaptorConfigField($field, $value)
    {
        self::$adaptor->setConfigField($field, $value);
    }

    /**
     * Makes a query and returns mapped array with data
     *
     * @param String $sql
     * @return Array|Bool
     */
    public static function query($sql, ?Array $bindings = [], ?Array $mapping = [])
    {
        $ret = self::$adaptor->query($sql);
        self::setFieldMapping();
        return $ret;
    }
  
    /**
     * Makes a database query and returns the last inserted id
     * 
     * Running other than a INSERT INTO statement here will throw an exception
     *
     * @param String $sql
     * @param ?Array $bindings
     * @return Any
     */
    public static function insert($sql, ?Array $bindings = [])
    {
        // check that the statement is a INSERT INTO
        if (!strtoupper((substr(trim($sql), 0,11)) == 'INSERT INTO'))
            throw new Exception('Wrong insert into statement', 500);

        return self::$adaptor->insert($sql);
    }

    /**
     * Run a statement with no return
     * 
     * @param String $sql
     * @param ?Array $bindings
     * @return Bool
     */
    public static function statement($sql, ?Array $bindings = [])
    {
        return self::$adaptor->statement($sql);
    }
   
    /**
     * Makes a query and returns only the first row
    *
    * @param String $sql
    * @param ?Array $mapping
    * @param ?Array $bindings    
    * @return Array|Bool
    */
    public static function first($sql, ?Array $bindings = [], ?Array $mapping = [])
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
    public static function count($sql, ?Array $bindings = [], $count_alias = 'count')
    {
        if(!substr(strtoupper(trim($sql)),1,6) == 'SELECT')
            throw new Exception('Count only works for SELECT statements', 500);
        
        if(!self::isValidFieldName($count_alias))
            throw new Exception('Invalid field name for count alias', 500);

        // use a regex to detect if $sql has fields and replace with count(*)
        $re = '/^SELECT\s(.+)\sFROM.+$/im';
        $m = [];
        $i = preg_match($re, $sql, $m, PREG_OFFSET_CAPTURE);
        if($i !== false && count($m) > 1){
            // find the start and end point in the fields part of sql statement
            $s = $m[1][1];
            $e = $s + strlen($m[1][0]);
            $fixed_sql = "SELECT count(*) as $count_alias " . substr($sql, $e+1);
            $r = self::me()->first($fixed_sql);
            return (int) $r[$count_alias];
        }else
            throw new Exception('Invalid SQL statement', 500);

    }

    /**
     * Make the bindings and return the query translated
     * by adaptor
     * 
     * @param string $sql
     * @param ?Array $bindings
     * @return string
     */
    public static function debug($sql, ?Array $bindings = []) : string
    {
        /**
         * @todo before return, make the bindings and translate
         */
        return $sql;
    }

    /**
     * Destroys connection object
     */
    public static function disconnect()
    {
        self::$adaptor = null;
        self::$connected = false;
    }

    public static function setFieldMapping($value = true)
    {
        return self::$adaptor->setAutoFieldMapping($value);
    }

    public static function getLastError()
    {
        return self::$adaptor->getLastError();
    }

}