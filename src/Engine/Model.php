<?php 

namespace Janssen\Engine;

use Janssen\Engine\Mapper;
use Janssen\Helpers\Exception;
use Janssen\Helpers\Database;
use Janssen\Traits\SQLStatement;
use Janssen\Traits\ForceDefinition;
use Janssen\Traits\InstanceGetter;
use Janssen\Traits\StaticCall;
use Throwable;

/**
 * Encapsulates methods to retrieve data from corresponding
 * table in database easily with focus on readability and consistency
 * 
 * @todo Members that mustbedefined should not be public by rule, we 
 * could implement public methods to access private members so they cannot
 * be written by mistake nor accessed directly and affect future queries
 * 
 */
class Model
{

    use SQLStatement;
    use ForceDefinition;
    use InstanceGetter;
    use StaticCall;

    protected static $use_view = false;

    protected static $pk_value_for_query = null;

    protected $table;
    protected $primaryKey;
    protected $view;

    /*
    protected static $zero_based_mapping = false;
    
    protected static $external_mapping;



    protected static $distinct = false;

    protected static $order_by = [];

    protected static $limit = -1;

    protected static $offset = -1;

    protected static $fields = [];
    
    private static $debug_and_wait = false;
    */
    
    /**
     * Fields that must be defined in order to use the Model
     */
    protected $mustBeDefined = [
        'table',
        'primaryKey'
    ];

    // - - - - - STATIC QUERY RUNNERS  - - - - - //

    public static function all()
    {
        self::$query_mode = 0;
        return self::me()->go();
    }

    public static function allById($id)
    {
        self::$query_mode = 1;
        self::$pk_value_for_query = $id;
        return self::me()->go();
    }

    public static function one($id)
    {
        self::$query_mode = 2;
        self::$pk_value_for_query = $id;
        return self::me()->go();
    }

    private function checkView()
    {
        if(self::$use_view && empty($this->view))
            throw new Exception('Query trying to use a view but no view attribute was defined in model',0, 'Contact administrator');

        return $this;
    }

    /**
     * Sets the use of view or table in Model
     *
     * @param boolean $value
     * @return object
     */
    public static function useView($value = true)
    {
        self::$use_view = $value;
        return self::me();
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getView()
    {
        return $this->view;    
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }
}