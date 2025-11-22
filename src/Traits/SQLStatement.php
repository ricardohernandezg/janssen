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
    // private static $query_mode = 0;

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
    protected $fields = [];    
    
    protected ?Mapper $mapper;

    protected $distinct = false;

    protected $table = '';

    protected $where = [];

    protected $accepted_operators = [
        '=','!=','<>','>','<','>=','<=','IN', 'NOT IN', 'NOTIN','BETWEEN','LIKE','IS NULL','NULL','NOT NULL'
    ];

    protected $order_by = [];

    protected $limit = -1;

    protected $offset = -1;

    private $parted_sql = [];

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
     */
    public function getPartedSql() : array
    {
        $parted = [
            'select' => $this->fields, 
            'distinct' => $this->distinct,
            'from' => $this->table,  
            'where' => $this->where, 
            'orderby' => $this->order_by, 
            'limit' => $this->limit,
            'offset' => $this->offset
        ];
        
        return $parted;
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
        $parts = ['select','from','where','orderby','limit','offset'];
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

    public function noMap(){
        $this->zero_based_mapping = true;
        $this->mapper = null;    
        return $this;
    }

    /**
     * Set a Mapper object for output fields
     *
     * @param Mapper $mapper
     * @return Object
     */
    public function mapWith(Mapper $mapper)
    {
        $this->zero_based_mapping = true;
        $this->mapper = $mapper;    
        return $this;
    }

    /**
     * Returns the current map
     */
    public function getMap() : Mapper
    {
        return $this->mapper;
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

    // - - - - END MAPPING - - - - 

    protected function clean()
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

        return $this;
    }

    /**
     * From part of statement
    */
    public function select(array $fields = [], ?Mapper $mapper = null)
    {
        $this->fields = $fields;
        if ($mapper){
            self::mapWith($mapper);
        }
        return $this;
    }

    /**
     * Distinct part of statement
    */
    public function distinct(bool $value = true)
    {
        $this->distinct = $value;
        return $this;
    }
    
    /**
     * From part of statement
    */
    public function from(String $table_name)
    {
        $this->table = $table_name;
        return $this;
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
    public function limit($rows_count = -1)
    {
        $this->limit = $rows_count;
        return $this;
    }
    
    /**
     * Sets the offset in query
     * @todo Check the compatibility with engines. Actually offset works well 
     * with Postgres
    *
    * @param integer $rows_skip
    * @return object
    */
    public function offset($rows_skip = -1)
    {
        $this->offset = $rows_skip;
        return $this;
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
     * @param Array $criteria
     * @param Array|String $operator
     * @return Object
     */
    public function where($criteria, $operator = '=')
    {
        $this->where = [];
        return $this->whereReal($criteria, $operator);
    }

    /**
     * Adds a criteria using the AND relation against prior criteria
     *
     * @param Array $criteria
     * @param Array|String $operator
     * @return Object
     */
    public function andWhere($criteria, $operator = '=')
    {
        if(empty($this->where))
            throw new Exception('AndWhere requires Where function to be called first!',500);

        return $this->whereReal($criteria, $operator, 'AND');
    }

    /**
     * Adds a criteria using the OR relation against prior criteria
     *
     * @param Array $fields
     * @param Array|String $operator
     * @return Object
     */
    public function orWhere($fields, $operator = '='){
        if(empty($this->where))
            throw new Exception('OrWhere requires Where function to be called first!',500);

        return $this->whereReal($fields, $operator, 'OR');
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

    private static function makeWhereMember($field, $value, $operator){
        
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

    public function clearWhere()
    {
        $this->where = [];
        return $this;
    }

    public function clearSelect()
    {
        $this->fields = [];
        return $this->noMap();
    }


}