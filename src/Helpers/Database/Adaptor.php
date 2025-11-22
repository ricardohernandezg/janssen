<?php 

namespace Janssen\Helpers\Database;

use Janssen\Helpers\Exception;
use Janssen\Helpers\SQLStatement;
use PDO;

abstract class Adaptor
{

    protected $_config_fields = [
        'host'  => '',
        'user'  => '',
        'pwd'   => '',
        'port'  => '',
        'db'    => ''
    ];

    /**
     * Connection instance
     */
    protected $_cnx;

    protected $last_error;

    protected $affected_rows;

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
    public abstract function query(string $sql, ?array $bindings = []);

    /**
     * Runs a query and returns last insert id
     *
     * It must be developed the way for each driver. 
     * 
     * @param string $sql
     * @return integer|array|bool
     */
    public abstract function insert(string $sql, ?array $bindings = []);

    /**
     * Runs a query and returns the row count
     * 
     * @param string $sql
     * @return integer|bool
     * @deprecated
     */
    // public abstract function howMany($sql);

    /**
     * Returns the EXISTS statement of a query
     */
    public abstract function exists(string $sql, ?array $bindings = []);

    /**
     * Run query. 
     * 
     * MUST RETURN BOOL
     */
    public abstract function statement(string $sql, ?array $bindings = []);

    /**
     * Set the last error in a internal variable to allow the user 
     * to know what happened if the statement returns false
     * 
     * @param string $code
     * @param string $message
     * @param string $sqlstate
     * @return object
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
     * @return array
     */
    public function getLastError()
    {
        return $this->last_error;
    }

    /**
     * Return the count of affected rows in last statement
     *
     * @return int
     */
    public function affectedRows()
    {
        return $this->affected_rows;
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
     * Determines the var type to ease the binding functions. Returns 
     * the PDO correspondent constant
     * 
     * @param any $var
     */
    protected static function determineType($var)
    {
        // Detectar nulo
        if (is_null($var)) return PDO::PARAM_NULL;

        // Primero verificamos booleano
        if (is_bool($var)) return PDO::PARAM_BOOL;

        // Si es entero exacto (y no cadena)
        if (is_int($var)) return PDO::PARAM_INT;

        // Si es flotante (y no cadena)
        if (is_float($var)) return PDO::PARAM_STR;

        // Si es cadena, debemos diferenciar texto "numérico" de número real (como texto)
        if (is_string($var)) {
            // Si la cadena es numérica pero no queremos considerarla número (según criterio)
            // Por ejemplo, si queremos que cadenas numéricas siempre se consideren texto:
            return PDO::PARAM_STR;
        }

        // Si ha llegado aquí, y es numérico (string numérico), consideramos texto según requerimiento
        if (is_numeric($var)) return PDO::PARAM_STR;

        // Por defecto, cualquier otro caso lo consideramos texto
        return PDO::PARAM_STR;
    }


    /**
     * Translate query object to SQL. 
     * 
     * @return string
     */
    public abstract function translate(array $parted_sql);
    
    // - - - - - DATABASE INFORMATION SECTION

    public abstract function tableExists($name, $schema = null);
    public abstract function viewExists($name, $schema = null);
    public abstract function procedureExists($name, $schema = null);
    public abstract function functionExists($name, $schema = null);

}