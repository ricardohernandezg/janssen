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

    private static $_valid_engines = ['mysqli', 'pgsql'];

    private static $_connected = false;

    /** 
     * Don't clean after this query
     */
    private static $keep = false;    

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
    public static function query($sql)
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
    public static function insert($sql)
    {
        return self::$_adaptor->insert($sql);
    }

    /**
     * Run a statement with no return
     * 
     * @return Bool
     */
    public static function statement($sql)
    {
        return self::$_adaptor->statement($sql);
    }

    /**
     * Makes a query and returns only the first row
     *
     * @param String $sql
     * @return Array|Bool
     */
    public static function queryOne($sql)
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

    public static function first(){
        self::$query_mode = 0;
        $r = self::me()->go();
        return $r[0] ?? false;
    }

    public function go()
    {
        try{

            if(self::$debug_and_wait)
                return $this->debug();

            if(empty(self::$parted_sql))
                $this->makeBasicSelect();

            $sql = $this->prepareSelect(self::$parted_sql);

            if(self::$zero_based_mapping)
                Database::setFieldMapping(false); 
            
            switch(self::$query_mode){
                case 0:
                case 1:
                    $ret = Database::query($sql);
                    break;                
                case 2:
                case 3:
                    $ret = Database::queryOne($sql);
            }
            
            if(self::$keep)
                self::$keep = false;
            else 
                $this->clean();
            
            return $ret ?? false;
        }catch(Throwable $e){
            $this->clean();
            throw new Exception($e->getMessage(), $e->getCode(), 'Contact administrator', $e->getTrace());
        }
        
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

/**
     * Returns the SQL intended to be used in query
     *
     * @param bool $stop Stops the execution of program and shows the query
     * 
     * @return String
     */
    public function debug($stop = false)
    {
        if(empty(self::$parted_sql))
                $this->makeBasicSelect();

        $sql = $this->prepareSelect(self::$parted_sql);

        if($stop)
            throw new Exception('Query: ' . $sql);

        return $sql;
    }

    /**
     * Sets debug mode on to return the SQL syntax intended to be used 
     *
     * @return Object
     */
    public function debugMode()
    {
        self::$debug_and_wait = true;
        return $this;
    }

    /**
     * Don't clean after the current query, useful to reuse same settings. It only
     * works for the next call, you can use succesive calls to keep the same object
     * settings
     */
    public static function keep()
    {
        self::$keep = true;
        return self::me();
    }

}