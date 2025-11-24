<?php 

namespace Janssen\Traits;

use Janssen\Engine\Mapper;
use Janssen\Helpers\Exception;

/**
 * This class only takes care of
 * - Field mapping
 * - Statement flatting through adaptor
 * - Debug
 * - Gives as product the parted sql to be translated by adaptor
 */
trait SQLStatement
{

    use \Janssen\Traits\InstanceGetter;
    use \Janssen\Traits\StaticCall;
    use \Janssen\Traits\GenericSQLSyntax\GenericFieldSyntax;


    private static $defaults = [
        'orderBy' => [],
        'distinct' => false,
        'zeroBasedMapping' => false,
        'mapping' => [],
        'limit' => -1,
        'offset' => -1,
        'queryMode' => 0
    ];

    protected $zero_based_mapping = false;

    /** sql modifiers  */
    private static $fields = [];    
    
    private static $from = "";    

    private static ?Mapper $map;

    private static $distinct = false;

    private static $where = [];

    private static $accepted_operators = [
        '=','!=','<>','>','<','>=','<=','IN', 'NOT IN', 'NOTIN','BETWEEN','LIKE','IS NULL','NULL','NOT NULL'
    ];

    private static $order_by = [];

    private static $limit = -1;

    private static $offset = -1;

    private static $parted_sql = [];

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
     * @return array
     */
    /*
    private function makeParted()
    {
        
        //$o = $this->prepareOrderBy();
        //$lo = $this->prepareLimitOffset();
        //$w = $this->prepareWhere();

        if(!self::$zero_based_mapping)
            $this->prepareMapped($this->fields, $this->mapper);

        self::$parted_sql = [
            'select' => "*", 
            'distinct' => '',
            'from' => $this->table,  
            'where' => $w, 
            'orderby' => $o, 
            'limit' => $lo];
        
        return $this;
    }
    */

    /**
     * Cleans the mapping 
     *
     * @return object
     * @deprecated
     */
    public static function clearMapping()
    {
        //self::$external_mapping = self::$defaults['mapping'];
        return self::me();
    }

    /**
     * Prepares the where part of query
     *
     * @return String
     */
    /*
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
    */

    /**
     * Prepares the order by part of query
     *
     * @return String
     */
    /*
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
        */
    
    /**
     * Returns the SQL parts that will be used to make the query string
     * 
     * @return array
     */
    public static function getPartedSql() : array
    {
        $parted = [
            'select' => self::$fields, 
            'distinct' => self::$distinct,
            'from' => self::$from,  
            'where' => self::$where, 
            'orderby' => self::$order_by, 
            'limit' => self::$limit,
            'offset' => self::$offset
        ];
        
        return $parted;
    }

    /**
     * Sets the parts that Model will use to make the query string
     *
     * @param array $parted_sql
     * @return Object
     */
    public static function setPartedSql(array $parted_sql)
    {
        //parted must be an Array and have all the members
        $parts = ['select','distinct','from','where','orderby','limit','offset'];
        $f = true;
        foreach($parts as $v){
            $f = ($f && isset($parted_sql[$v]));
        }
        if($f){
            // extract the elements to local variables
            self::$fields = $parted_sql['select'];
            self::$distinct = $parted_sql['distinct'];
            self::$from = $parted_sql['from'];
            self::$where = $parted_sql['where'];
            self::$order_by = $parted_sql['orderby'];
            self::$limit = $parted_sql['limit'];
            self::$offset = $parted_sql['offset'];

            return self::me();
        }else
            throw new Exception('Parted sql needs all its parameter to be built correctly.', 500);
    }

    // - - - - MAPPING - - - - -
 
    /**
     * Sets the field array as select only to be flatten at query prepare time
     * 
     * @return Object
     */
    protected function prepareMapped(array &$fields, $map)
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

    /*
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
    */

    public static function noMap(){
        self::$zero_based_mapping = true;
        self::$map = null;    
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
        self::$zero_based_mapping = true;
        self::$map = $mapper;    
        return self::me();
    }

    /**
     * Returns the current map
     */
    public static function getMap() : Mapper
    {
        return self::$map;
    } 

    /**
     * Add specific field mapping to the next query
     *
     * @param string $field_name
     * @param string $new_name
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
     * @param array $map
     * @return Object
     */
    public static function addFieldMapFromArray(Array $map)
    {
        foreach($map as $k=>$v){
            self::addFieldMap($k, $v);
        }

        return self::me();
    }

    // - - - - END MAPPING - - - - 

    protected static function clean()
    {
        // clear distinct select
        self::distinct(self::$defaults['distinct']);
        // clear order by
        self::clearOrderBy();
        // clear specific field mapping
        self::clearMapping();
        // clear select fields created by mapping
        self::select([]);
        // clear limit and offset
        self::limit();
        self::offset();
        // clear parted_sql
        self::$parted_sql = [];
        // clean where
        self::clearWhere(); 
        // clean me() instance
        self::notMe();

        return self::me();
    }

    /**
     * From part of statement
    */
    public static function select(array $fields = [])
    {
        self::$fields = $fields;
        return self::me();
    }

    /**
     * Distinct part of statement
    */
    public static function distinct(bool $value = true)
    {
        self::$distinct = $value;
        return self::me();
    }
    
    /**
     * From part of statement
    */
    protected static function from(String $table_name)
    {
        self::$from = $table_name;
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
    * @return Object
    */
    public static function offset($rows_skip = -1)
    {
        self::$offset = $rows_skip;
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

 
    /** DEBUG */
    /**
     * Returns the SQL intended to be used in query
     *
     * @param bool $stop Stops the execution of program and shows the query
     * 
     * @return String
     */
    public static function debug($sql, ?Array $mapping = [], ?Array $bindings = [])
    {
        /*
        if(empty(self::$parted_sql))
                $this->makeBasicSelect();

        $sql = $this->prepareSelect(self::$parted_sql);

        if($stop)
            throw new Exception('Query: ' . $sql);
        */

        return $sql;
    }



    /** WHERE MODIFIERS */

    /**
     * Initializes the $_where variable and adds the first criteria
     *
     * @param array $criteria
     * @param array|string $operator
     * @return Object
     */
    public static function where($criteria, $operator = '=')
    {
        self::$where = [];
        return self::whereReal($criteria, $operator);
    }

    /**
     * Adds a criteria using the AND relation against prior criteria
     *
     * @param array $criteria
     * @param array|string $operator
     * @return Object
     */
    public static function andWhere($criteria, $operator = '=')
    {
        if(empty(self::$where))
            throw new Exception('AndWhere requires Where function to be called first!', 500);

        return self::whereReal($criteria, $operator, 'AND');
    }

    /**
     * Adds a criteria using the OR relation against prior criteria
     *
     * @param array $fields
     * @param array|string $operator
     * @return Object
     */
    public function orWhere($fields, $operator = '='){
        if(empty(self::$where))
            throw new Exception('OrWhere requires Where function to be called first!', 500);

        return self::whereReal($fields, $operator, 'OR');
    }

    private function whereReal($fields, $operator, $relation = '')
    {

        $s_where = [];
        if(is_array($fields)){
            // array must be an key/value combination of field/value. Default criteria 
            // is =, but it can be changed to any operator supported by SQL
            foreach($fields as $s_name=>$s_value){
                // if criteria is an array, it should come in key/value pair, if the don't exists, = will be applied 
                // if criteria is not array but string the same criteria will be applied to all fields in this
                // operation
                if (!self::isValidFieldName($s_name)){
                    $s_name = $s_value;
                    $s_value = null;
                } 

                if (is_array($operator)) {
                    if (array_key_exists($s_name, $operator))
                        $s_operator = self::prepareCriteria($operator[$s_name]);    
                    elseif(count($fields) == 1 && !is_array($operator))
                        $s_operator = self::prepareCriteria($operator);    
                    else
                        $s_operator = self::prepareCriteria(false);    
                }else
                    $s_operator = self::prepareCriteria($operator);
                    
                $s_where[] = self::makeWhereMember($s_name, $s_value, $s_operator);

            }
            $this->where[] = ['relation' => $relation,
                    'members' => $s_where];
            
        }else
            throw new Exception('Fields criteria must be Array', 500);

        return $this;

    }

    protected static function makeWhereMember($field, $value, $operator = '='){
        
        return [
            'field' => $field,
            'value' => $value,
            'operator' => $operator
        ];

    }

    private static function parameterIsAcceptable($param)
    {
        return (!is_object($param) && !is_bool($param));
    }

    private static function prepareCriteria($operator)
    {
        $operator = strtoupper(trim($operator));
        if (empty($operator) || $operator == false || $operator == '=')  return '=';

        if (in_array($operator, self::$accepted_operators))
            return $operator;
        else
            return false;
    }

    // - - - - - CLEAR STATEMENT - - - - - - 
    
    public static function clearWhere()
    {
        self::$where = [];
        return self::me();
    }

    public function clearSelect()
    {
        self::$fields = [];
        return self::me();
    }


}