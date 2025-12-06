<?php 

namespace Janssen\Engine;

use Janssen\Engine\Mapper;
use Janssen\Engine\Database;
use Janssen\Helpers\Exception;
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

    use \Janssen\Traits\ForceDefinition;
    use \Janssen\Traits\InstanceGetter;
    use \Janssen\Traits\StaticCall;    
    use \Janssen\Traits\SQLStatement;    
    use \Janssen\Traits\GenericSQLSyntax\GenericFieldSyntax;

    /** mandatory options  */
    protected $table;
    protected $primaryKey;
    protected $view;
    protected $list = [];

    /** static modificers */
    protected static $use_view = false;
    
    /** private per-query settings  */
    private static $pk_value_for_query = null;
    
    /** 
     * Don't clear after this query
     */
    private static $keep = false;    

    private static $debug_and_wait = false;

    /**
     * Fields that must be defined in order to use the Model
     */
    private $mustBeDefined = [
        'table',
        'primaryKey'
    ];

    /**
     * Connection in settings that will be used to query
     * the model interactions
     */
    protected $connection_name = "";

    /**
     * Mode of query, defined by the last call to all, allById or one
     * 0 - all (default)
     * 1 - allById @deprecated
     * 2 - one
     * 3 - count
     * 4 - first
     * 
     * This variable is to be setted internally using the funcions
     * 
     * @var integer
     */
    private static $query_mode = 0;

    // - - - - - STATIC QUERY RUNNERS  - - - - - //

    public static function all()
    {
        self::$query_mode = 0;
        return self::me()->go();
    }

    /**
     * @deprecated
     */
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

    public static function first()
    {
        // usa el limit 1
        self::$query_mode = 4;
        return self::me()->go();
    }

    public static function count()
    {
        self::$query_mode = 3;
        return self::me()->go();
    }

    /**
     * Get elements from database key and item columns, ordered by $key_column
     * if additional fields are required we give them too
     * 
     * @param string $key_column
     * @param string $item_column
     * @param string|array $additional_columns
     * @return array|bool
     */
    public static function list(string $key_column = "", string $item_column = "", $additional_columns = null)
    {
        $key = '';
        if(trim($key_column) && trim($item_column)){
            // use the provided vars
            // we could have $additional_column as array or string
            if($additional_columns && is_array($additional_columns)){
                self::select([$key_column, $item_column] +  $additional_columns);
            }elseif ($additional_columns && is_string($additional_columns)){
                self::select([$key_column, $item_column, $additional_columns]);
            }else{ // ignore additional column
                self::select([$key_column, $item_column]);
            }
            $key = $key_column;
        }else{
            // use the model list config
            $list = self::me()->checkList();
            if($list){
                
                if($list['additional'] ?? false)
                {
                    // append the additionals fields to the query
                    $additional_columns = $list['additional'];
                    if(is_array($additional_columns)){
                        self::select([$list['key'], $list['item']] +  $additional_columns);
                    }else{
                        self::select($list['key'], $list['item'], $additional_columns);
                    }
                }else{
                    // create the select with the fields
                    self::select([$list['key'], $list['item']]);
                }
                $key = $list['key'];    
            }
        }
        self::orderBy($key);
        self::$query_mode = 0;
        return self::me()->go();
    }
    

    private function checkView() : bool
    {
        if(self::$use_view && empty(trim($this->view)))
            throw new Exception('Query trying to use a view but no view attribute was defined in model', 500, 'Contact administrator');

        return true;
    }

    /**
     * Checks the list attribute
     * @return array|bool
     */
    private function checkList() : array|bool
    {
        /* we try to find the defaults set up
        * look for an array called $list that must have
        * at least 2 members called key and item and 
        * optionally additional
        */
        if(empty($this->list))
            throw new Exception('Query trying to use a list but no list attribute was defined in model', 500, 'Contact administrator');
        elseif (is_array($this->list) && (trim($this->list['key']) ?? false) && (trim($this->list['item']) ?? false))
            return $this->list;
        else
            throw new Exception('Malformed list attribute was defined in model', 500, 'Contact administrator');
        
    }

    private function checkListHasAdditionals() : bool
    {
        $list = $this->checkList();
        return ($list && ($list['additional'] ?? false));
    }

    /**
     * Gets the Model's table name
     * @return string
     */
    public function tableName() : string
    {
        return $this->table;
    }

    /**
     * Gets the Model's view name
     * @return string
     */
    public function viewName() : string
    {
        return $this->view;    
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    protected function getMap()
    {
        return self::getMap();
    }

    
    
    // - - - - - - QUERY MODIFIERS - - - - - -
    
    /**
     * Alias of select
     * 
     * @deprecated 
    */
    public static function selectOnly(Array $fields)
    {
        return self::select($fields);
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
    
    /**
     * Alias for one
     * 
     * @deprecated
     */
    public static function queryOne($sql)
    {
        return self::one($sql);
    }


    public function go()
    {
        try{

            $sql = $this->prepareStatement()
                ->makeStatement();
          
            switch(self::$query_mode){
                case 0:
                case 1: 
                    $ret = Database::query($sql);
                    break;                
                case 3:
                    $ret = Database::count($sql);
                    break;
                case 2:
                case 4:
                    $ret = Database::first($sql);
            }
            
            if(self::$keep)
                self::$keep = false;
            else 
                $this->clearQuery();
            
            return $ret ?? false;
        }catch(Throwable $e){
            $this->clearQuery();
            throw new Exception($e->getMessage(), $e->getCode(), 'Contact administrator', $e->getTrace());
        }
        
    }

    /**
     * Sets debug mode on to return the SQL syntax intended to be used 
     *
     * @return Object
     */
    public static function debug()
    {
        self::$debug_and_wait = true;
        return self::me();
    }

    /**
     * Don't clear after the current query, useful to reuse same settings. It only
     * works for the next call, you can use succesive calls to keep the same object
     * settings
     */
    public static function keep()
    {
        self::$keep = true;
        return self::me();
    }

    /**
     * clear all the query modifiers
     */
    private function clearQuery()
    {
        return $this;
    }

    /**
     * Get the parted_sql from statement and adapt to query mode
     */
    private function prepareStatement()
    {
        
        if($this->connection_name !== ""){
            // resuelve el adaptador correcto y setealo en database
            $conn = \getConfig('connections')[$this->connection_name] ?? false;
            if($conn){
                $adaptor = \getDatabaseAdaptor($conn);
                Database::setAdaptor($adaptor);
            }else
            throw new Exception('Connection not set in config', 500, 'Contact administrator');
        }
        
        // obtengo el parted sql y lo modifico segun necesite
        $parted_sql = self::getPartedSql();
        
        $parted_sql['from'] = (self::$use_view && self::checkView()) ? $this->view : $this->table;

        $parted_sql['distinct'] = self::$distinct;

        $parted_sql['orderby'] = self::$order_by;

        switch(self::$query_mode){
            case 1:
            case 2:
                $w = self::makeWhereMember($this->primaryKey, self::$pk_value_for_query);
                $parted_sql['where'][] = ['members' => [$w]];
                break;                
            case 3:
                $parted_sql['select'] = [];                //$ret = Database::queryOne($sql);
                $parted_sql['distinct'] = false;
                break;
            case 4:
                $parted_sql['limit'] = 1;
                break;
            case 0:
            default:
                self::clearWhere();
                    
        }

        self::setPartedSql($parted_sql);
        return $this;
    }
    
    /**
     * Translate the statement to query using the adaptor
     */
    private function makeStatement() : string
    {        
        $parted_sql = self::getPartedSql();
        $statement = Database::getAdaptor()->translate($parted_sql, (self::getMapper()->getMap() ?? []));
        return $statement;
    }


}