<?php 

namespace Janssen\Helpers\Database;

use Janssen\Helpers\Exception;

abstract class Adaptor
{

    protected $_config_fields = [
        'host'  => '',
        'user'  => '',
        'pwd'   => '',
        'port'  => '',
        'db'    => ''
    ];

        
    protected static $debug_and_wait = false;

    /**
     * Connection instance
     */
    protected $_cnx;

    protected $last_error;

    private static $parted_sql = [];

    /**
     * Map automatically fields when return array. False means
     * use zero-base index for field names
     */
    protected $_map_return_fields = true;

    public function __construct()
    {
        $this->setAutoFieldMapping();
    }

    /**
     * Connect to database using the php function
     *
     * @return void
     */
    public abstract function connect();
    
    /**
     * Disconnect from database
     *
     * @return void
     */
    public abstract function disconnect();

    /**
     * Run query. 
     * 
     * MUST RETURN THE ARRAY WITH RESULTS or FALSE
     */
    public abstract function query($sql);

    /**
     * Runs a query and returns last insert id
     *
     * It must be developed the way for each driver. 
     * 
     * @param String $sql
     * @return Integer|Array|Bool
     */
    public abstract function insert($sql);

    /**
     * Runs a query and returns the row count
     * 
     * @param String $sql
     * @return Integer|Bool
     * @deprecated
     */
    // public abstract function howMany($sql);

    /**
     * Returns the EXISTS statement of a query
     */
    public abstract function exists($sql);

    /**
     * Run query. 
     * 
     * MUST RETURN BOOL
     */
    public abstract function statement($sql);

    /**
     * Set the last error in a internal variable to allow the user 
     * to know what happened if the statement returns false
     * 
     * @param String $code
     * @param String $message
     * @param String $sqlstate
     * @return $this
     */
    public function setLastError($code, $message, $sqlstate, $query)
    {
        $this->last_error = [
            'code' => $code,
            'message' => $message,
            'SQLstate' => $sqlstate,
            'query' => $query
        ];
        return $this;
    }
    
    /**
     * Return the user an array with the last error data
     *
     * @return Array
     */
    public function getLastError()
    {
        return $this->last_error;
    }

    /**
     * Checks if there is connection object set
     */
    public function isConnected()
    {
        return is_object($this->_cnx);
    }

    /**
     * Get internal array with the fields needed to
     * instanciate a connection with this adapter
     *
     * @return Array
     */
    public function getAllConfigFields()
    {
        return $this->_config_fields;
    }

    /**
     * Sets the value for the configuration field 
     * Fields MUST BE already a member of $_config_fields
     *
     * @param String $field
     * @param String $value
     * @return Adaptor
     */
    public function setConfigField($field, $value)
    {
        if(array_key_exists($field, $this->_config_fields))
            $this->_config_fields[$field] = $value;

        return $this;
    }

    /**
     * This map refers to make the fields return DB FIELD NAMES instead
     * of 0-based column ordering. Related config fields to this are model->no_map
     * 
     * @param boolean $value
     * @return Adaptor
     */    
    public abstract function setAutoFieldMapping($value = true);

    /**
     * Returs the connection native object
     * 
     * @return Object
     */
    public function getConnector(){
        return $this->_cnx;
    }

    

    /**
     * Translate query object to SQL. 
     * 
     * @return string
     */
    protected abstract function translate(array $parted_sql);

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

    // DATABASE INFORMATION SECTION

    public abstract function tableExists($name, $schema = null);
    public abstract function viewExists($name, $schema = null);
    public abstract function procedureExists($name, $schema = null);
    public abstract function functionExists($name, $schema = null);

}