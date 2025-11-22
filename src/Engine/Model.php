<?php 

namespace Janssen\Engine;

use Janssen\Engine\Mapper;
// use Janssen\Engine\Ruleset;
use Janssen\Engine\Database;
use Janssen\Helpers\Exception;
use Janssen\Helpers\SQLStatement;
// use Janssen\Helpers\Database\Adaptor;
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

    use ForceDefinition;
    use InstanceGetter;
    use StaticCall;

    /** mandatory options  */
    protected $table;
    protected $primaryKey;
    protected $view;

    /** static modificers */
    protected static $use_view = false;
    private static $distinct = false;


    /** private per-query settings  */
    private static $pk_value_for_query = null;
    private static $fields = [];
    private static ?Mapper $map = null;

    /**
     * Statement object
     */
    private static SQLStatement $stmt;

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
     * 
     * This variable is to be setted internally using the funcions
     * 
     * @var Integer
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

    private function checkView()
    {
        if(self::$use_view && empty($this->view))
            throw new Exception('Query trying to use a view but no view attribute was defined in model', 500, 'Contact administrator');

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

    protected function getMap()
    {
        return self::$stmt->getMap();
    }

    protected function mapWith(Mapper $map)
    {
        self::$stmt->mapWith($map);
        return $this;
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
     * Select part of statement
     */
    public static function select(Array $fields, ?Mapper $map = null)
    {
        self::$fields = $fields;
        self::$map = $map;
        return self::me();
    }

    /**
     * Sets or disables the use of DISTINCT clause in query
     *
     * @param boolean $value
     * @return object
     */
    public static function distinct($value = true)
    {
        self::$distinct = $value;
        return self::me();
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

            $sql = $this->prepareStatement()
                ->makeStatement();
          
            switch(self::$query_mode){
                case 0:
                case 1:
                    $ret = Database::query($sql);
                    break;                
                case 2:
                case 3:
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

    private function prepareStatement()
    {

        self::$stmt = new SQLStatement();
        
        switch(self::$query_mode){
            case 1:
                $ret = Database::query($sql);
                break;                
            case 2:
                break;
            case 3:
            //$ret = Database::queryOne($sql);
                break;
            case 0:
            default:
                self::$stmt->clearWhere();
                    
        }
        return $this;
    }

    private function makeStatement() : string
    {
        // get the parted_sql from statement and translate
        // using the adaptor to run it
        if($this->connection_name !== ""){
            // resuelve el adaptador correcto y setealo en database
            $conn = \getConfig('connections')[$this->connection_name] ?? false;
            if($conn){
                $adaptor = \getDatabaseAdaptor($conn);
                Database::setAdaptor($adaptor);
            }else
                throw new Exception('Connection not set in config', 500, 'Contact administrator');
        }/*else
            $adaptor = Database::getAdaptor();*/

        
        self::$stmt->select(self::$fields, self::$map)
            ->distinct(self::$distinct)
            ->from(self::$use_view ? 
                $this->view : $this->table
                );
        
        if(self::$query_mode == 2){
            self::$stmt->where([$this->primaryKey => self::$pk_value_for_query]);
        }

        $parted_sql = self::$stmt->getPartedSql();
        $statement = Database::getAdaptor()->translate($parted_sql);
        return $statement;
    }

}