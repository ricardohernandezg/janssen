<?php 

namespace Janssen\Engine;

use Janssen\Engine\Mapper;
use Janssen\Helpers\Exception;
use Janssen\Helpers\Database;
use Janssen\Traits\SQLWhere;
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
    use \Janssen\Traits\ForceDefinition;
    use \Janssen\Traits\InstanceGetter;
    use \Janssen\Traits\StaticCall;
    USE \Janssen\Traits\SQLWhere;

    private static $defaults = [
        'orderBy' => [],
        'distinct' => false,
        'zeroBasedMapping' => false,
        'mapping' => [],
        'useView' => false,
        'limit' => -1,
        'offset' => -1,
        'queryMode' => 0
    ];


    protected static $zero_based_mapping = false;
    
    protected static $external_mapping;

    protected static $use_view = false;

    protected static $distinct = false;

    protected static $order_by = [];

    protected static $limit = -1;

    protected static $offset = -1;

    protected static $fields = [];

    protected static $pk_value_for_query = 0;

    private static $parted_sql = [];

    private static $debug_and_wait = false;

    /**
     * Mode of query, defined by the last call to all, allById or one
     * 0 - all (default)
     * 1 - allById 
     * 2 - one
     * 3 - count
     * 
     * This variable is to be setted internally using the funcions
     * 
     * @var Integer
     */
    private static $query_mode = 0;

    /**
     * Fields that must be defined in order to use the Model
     */
    protected $mustBeDefined = [
        'table',
        'primaryKey'
    ];

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
            
            $this->clean();
            return $ret ?? false;
        }catch(Throwable $e){
            $this->clean();
            throw new Exception($e->getMessage(), $e->getCode(), 'Contact administrator', $e->getTrace());
        }
        
    }

    protected function clean()
    {
        // disable use associated view
        self::useView(self::$defaults['useView']);
        // clear distinct select
        self::distinct(self::$defaults['distinct']);
        // clear order by
        self::clearOrderBy();
        // clear specific field mapping
        self::clearMapping();
        // clear select fields created by mapping
        self::selectOnly([]);
        // clear limit and offset
        self::limit();
        self::offset();
        // make default query mode
        self::$query_mode = self::$defaults['queryMode'];
        // clear parted_sql
        self::$parted_sql = [];
        // clean me() instance
        self::notMe();
        // set debugMode off
        self::$debug_and_wait = false;
        return $this;
    }

    /**
     * Returns the SQL intended to be used in query
     *
     * @return String
     */
    public function debug()
    {
        if(empty(self::$parted_sql))
                $this->makeBasicSelect();

        $sql = $this->prepareSelect(self::$parted_sql);
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
     * Alias of makeBasicSelect()
     *
     * @return Object
     */
    public function prepareStatement(){
        return $this->makeBasicSelect();
    }

    /**
     * Makes the query string with the internal parted SQL object
     *
     * @return Object
     */
    public function makeBasicSelect()
    {
        if(self::$use_view && empty($this->view))
            throw new Exception('Query trying to use a view but no view attribute was defined in model',0, 'Contact administrator');

        $o = $this->prepareOrderBy();
        $lo = $this->prepareLimitOffset();
        $w = $this->prepareWhere();

        if(!self::$zero_based_mapping)
            $this->prepareMapping();

        self::$parted_sql = [
            'select' => ((self::$distinct)?'DISTINCT':'') . " * ", 
            'from' => ((self::$use_view)?$this->view:$this->table),  
            'where' => $w, 
            'orderby' => $o, 
            'limit' => $lo];
        
        return $this;
    }

    /**
     * Returns the SQL parts that will be used to make the query string
     */
    public function getPartedSql() : Array
    {
        return self::$parted_sql;
    }

    /**
     * Sets the parts that Model will use to make the query string
     *
     * @param Array $parted_sql
     * @return Object
     */
    public function setPartedSql(Array $parted_sql)
    {
        //parted must be an Array and have all the members
        $parts = ['select','from','where','orderby','limit'];
        $f = true;
        foreach($parts as $v){
            $f = ($f && isset($parted_sql[$v]));
        }
        if($f){
            self::$parted_sql = $parted_sql;
            return $this;
        }else
            throw new Exception('Parted sql needs all its parameter to be built correctly.', 500);
    }

    /**
     * Prepares the where part of query
     *
     * @return String
     */
    protected function prepareWhere(){
        $w = '';
        // query mode 0 is all(), that can be all rows or all using where statement
        switch (self::$query_mode){
            case 0:
            case 3:
                $w = self::flatWhere();
                break;
            default:
                $w = $this->primaryKey . " = '" . self::$pk_value_for_query . "'";
        }
            
        return $w;
    }

    /**
     * Prepares the order by part of query
     *
     * @return String
     */
    protected function prepareOrderBy(){
        $o = '';
        if(count(self::$order_by) > 0)
        {
            foreach(self::$order_by as $k=>$v)
            {
                $o .= "$k $v, ";
            }
            $o = substr($o, 0, strlen($o)-2);
        }
        return $o;
    }
    
    /**
     * Sets the field array as select only to be flatten at query prepare time
     * 
     * @return Object
     */
    protected function prepareMapping()
    {
        // check if user restricted the query to only some fields
        $r = [];
        if(self::$fields){
            
            // check if there are mapping to that fields
            foreach(self::$fields as $v)
            {

                $mmfa = $this->manualMappingFromAlias($v);
                if($mmfa) 
                    $v = $mmfa[1];
                $em = $this->getExternalMapping($v);

                if($mmfa){
                    $r[] = "{$mmfa[0]} as '$em'";
                }elseif ($em)
                    $r[] = "$v as '$em'";
                else
                    $r[] = $v;
                        
            }
            self::$fields = $r;            
        }else{
            // user has not restricted the fields, we'll get all, but still 
            // we can have specific or rule mapping
            if(self::$external_mapping){
                foreach(self::$external_mapping as $k=>$v){
                    $r[] = "$k as '$v'";
                }
                self::$fields = $r;
            }
        }
        return $this;
    }

    private function getExternalMapping($field_name)
    {
        return (!empty(self::$external_mapping) && array_key_exists($field_name,self::$external_mapping)) ?
            self::$external_mapping[$field_name] : $field_name;
    }

    private function manualMappingFromAlias($field_name)
    {
        $field_name = trim($field_name);
        if(!strpos($field_name, ' ')) 
            return false;
        else{
            $found = false;
            do{
                $field_name = str_replace('  ', ' ', $field_name);
                $found = strpos($field_name, '  ');
            }while($found);
        }
            
        $er = '/(.+)\sas\s([a-zA-Z0-9_\-#@$]+)$/mi';
        $a = [];
        $i = preg_match($er,$field_name,$a);
        return ($i > 0) ? [$a[1],$a[2]] : false;
    }

    protected function prepareLimitOffset()
    {
        // sql doesn't allow use only offset
        $lo = '';
        if(self::$limit > -1 && self::$offset > -1)
            $lo = self::$limit . ' OFFSET ' . self::$offset; 
        elseif (self::$limit > -1)
            $lo = self::$limit; 
        
        return $lo;
    }

    protected function prepareSelect(Array $parted_sql)
    {
        $parted_sql['select'] = $this->flatFields();
        return $this->flatSQL($parted_sql);
    }

    private function flatFields()
    {
        if(!empty(self::$fields))
            return implode(', ', self::$fields);
        else
            return '*';
    }

    private function flatSQL($parted_sql)
    {
        $sql = 'SELECT ' . (self::$distinct?'DISTINCT ':'') . $parted_sql['select'] . ' FROM ' . $parted_sql['from'];
        if(!empty(trim($parted_sql['where'])))
            $sql .= ' WHERE ' . trim($parted_sql['where']);
             
        if(!empty(trim($parted_sql['orderby'])))
            $sql .= ' ORDER BY ' . trim($parted_sql['orderby']);            

        if(!empty(trim($parted_sql['limit'])))
            $sql .= ' LIMIT ' . trim($parted_sql['limit']);
            
        return $sql;
    }

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

    public static function count()
    {
        self::$query_mode = 3;
        self::$fields = ['count(*) as count'];
        $r = self::me()->go();
        return $r['count'];
    }

    public static function first(){
        self::$query_mode = 0;
        $r = self::me()->go();
        return $r[0] ?? false;
    }

    // - - - - STATIC QUERY MODIFIERS  - - - -  //

    public static function selectOnly(Array $fields)
    {
        self::$fields = $fields;
        return self::me();
    }

    public static function noMap(){
        self::$zero_based_mapping = true;
        self::$external_mapping = null;
        return self::me();
    }

    /**
     * Set a Mapper object for output fields
     *
     * @param Mapper $mapper
     * @return Object
     */
    public static function mapWith(Mapper $mapper)
    {
        self::$zero_based_mapping = false;
        self::$external_mapping = $mapper->getMap();    
        return self::me();
    }

    /**
     * Add specific field mapping to the next query
     *
     * @param String $field_name
     * @param String $new_name
     * @return Object
     */
    public static function addFieldMap($field_name, $new_name = '')
    {
        if(!empty($field_name))
            self::$external_mapping[$field_name] = empty($new_name)?$field_name:$new_name;

        return self::me();
    }

    /**
     * Add specific field mapping from array. 
     *
     * @param Array $map
     * @return Object
     */
    public static function addFieldMapFromArray(Array $map)
    {
        foreach($map as $k=>$v){
            self::addFieldMap($k, $v);
        }

        return self::me();
    }

    /**
     * Cleans the mapping 
     *
     * @return object
     */
    public static function clearMapping()
    {
        self::$external_mapping = self::$defaults['mapping'];
        return self::me();
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
     * Sets the order by fields in the query
     *
     * @param string $field
     * @param string $mode
     * @return object
     */
    public static function orderBy($field, $mode = '')
    {
        $mode = strtoupper(trim($mode));
        if(!in_array($mode, ['','ASC','DESC']) || empty($field))
            throw new Exception('Bad criteria for order query results');

        self::$order_by[$field] = $mode;
        return self::me();
    }

    /**
     * Cleans the order by clause
     *
     * @return object
     */
    public static function clearOrderBy()
    {
        self::$order_by = self::$defaults['orderBy'];
        return self::me();
    }

    /**
     * Sets the limit of rows in query
     *
     * @param integer $rows_count
     * @return object
     */
    public static function limit($rows_count = -1)
    {
        self::$limit = $rows_count;
        return self::me();
    }

    /**
     * Sets the offset in query
     * @todo Check the compatibility with engines. Actually offset works well 
     * with Postgres
     *
     * @param integer $rows_skip
     * @return object
     */
    public static function offset($rows_skip = -1)
    {
        self::$offset = $rows_skip;
        return self::me();
    }
   
}