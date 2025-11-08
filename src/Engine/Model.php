<?php 

namespace Janssen\Engine;

use Janssen\Engine\Mapper;
use Janssen\Engine\Ruleset;
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
 * @todo create the list settings to autoload records to fill combos and lists
 * from only one call. This method should allow where modifiers to make it
 * more flexible
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
    protected ?Ruleset $mapping = null;
    
    /** 
     * Don't clean after this query
     */
    private static $keep = false;    

    protected static $debug_and_wait = false;

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

    protected function map()
    {
        return $this->mapping;
    }


    /**
     * Alias for one
     */
    public static function queryOne($sql)
    {
        return self::one($sql);
    }


    public function go()
    {
        try{

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
     * Sets debug mode on to return the SQL syntax intended to be used 
     *
     * @return Object
     */
    public static function debugMode()
    {
        self::$debug_and_wait = true;
        return self::me();
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