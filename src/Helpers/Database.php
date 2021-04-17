<?php 

namespace Janssen\Helpers;

use Janssen\Helpers\Database\Adaptor;

class Database
{

    /**
     * Connection variable
     *
     * @var Object
     */
    protected static $_adaptor = false;

    private static $_valid_engines = ['mysqli', 'pgsql'];

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
        } else {
            return false;
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

}