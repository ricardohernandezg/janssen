<?php 

namespace Janssen\Traits;

use Janssen\Helpers\Exception;
use Janssen\Traits\InstanceGetter;

trait SQLWhere
{
    use InstanceGetter;
    
    private static $_where = [];

    private static $accepted_operators = [
        '=','!=','<>','>','<','>=','<=','IN','BETWEEN','LIKE','IS NULL','NULL','NOT NULL'
    ];

    /**
     * Initializes the $_where variable and adds the first criteria
     *
     * @param Array $fields
     * @param Array|String $operator
     * @return Object
     */
    public static function where($fields, $operator = '=')
    {
        self::$_where = [];
        return self::whereReal($fields, $operator);
    }

    /**
     * Adds a criteria using the AND relation against prior criteria
     *
     * @param Array $fields
     * @param Array|String $operator
     * @return Object
     */
    public static function andWhere($fields, $operator = '=')
    {
        if(empty(self::$_where))
            throw new Exception('AndWhere requires Where function to be called first!',500);

        return self::whereReal($fields, $operator, 'AND');
    }

    /**
     * Adds a criteria using the OR relation against prior criteria
     *
     * @param Array $fields
     * @param Array|String $operator
     * @return Object
     */
    public static function orWhere($fields, $operator = '='){
        if(empty(self::$_where))
            throw new Exception('OrWhere requires Where function to be called first!',500);

        return self::whereReal($fields, $operator, 'OR');
    }

    private static function whereReal($fields, $operator, $relation = '')
    {

        $s_where = [];
        if(is_array($fields)){
            // array must be an key/value combination of field/value. Default criteria 
            // is =, but it can be changed to any operator supported by SQL
            foreach($fields as $s_name=>$s_value){
                // if criteria is an array, it should come in key/value pair, if the don't exists, = will be applied 
                // if criteria is not array but string the same criteria will be applied to all fields in this
                // operation
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
            self::$_where[] = ['relation' => $relation,
                    'members' => $s_where];
            
        }else
            throw new Exception('Fields criteria must be Array', 500);

        return self::me();

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
    
    private static function criteriaRequiresArray($operator)
    {
        return in_array($operator, ['IN', 'BETWEEN']);
    }

    private static function makeComplexCriteriaSyntax($operator, $values)
    {
        
        if(!is_array($values) && self::criteriaRequiresArray($operator))
            throw new Exception('operators IN and BETWEEN expects Array as parameter',500);

        switch($operator){
            case 'IN':
                $ret = 'IN (';
                foreach($values as $v){
                    $ret .= self::processValue($v) . ',';
                }
                $ret = substr($ret,0, strlen($ret)-1) . ') ';
                break;
            case 'BETWEEN':
                $ret = " BETWEEN {$values[0]} AND {$values[1]} ";
                break;
            case 'NULL':
            case 'IS NULL':
                $ret = " IS NULL ";
                break;
            case 'NOT NULL':
                $ret = " IS NOT NULL ";
                break;                
            default:
                $ret = '';
        }
        return $ret;
    }

    private static function processValue($value){
        if (is_numeric($value) || $value === true || $value === false)
            return $value;
        else
            return "'$value'";
    }

    protected static function flatWhere()
    {
        $ret = '';
        foreach(self::$_where as $v)
        {
            
            if($v['relation'] !== '') $ret .= " {$v['relation']} ";
            $ret .= "(";
            foreach($v['members'] as $member){
                if(in_array($member['operator'], ['IN','BETWEEN','IS NULL','NULL','NOT NULL'])){
                    $ret .= "{$member['field']} " . self::makeComplexCriteriaSyntax($member['operator'], $member['value']) . " AND ";
                }else
                    $ret .= " {$member['field']} {$member['operator']} " . self::processValue($member['value']) . " AND ";
            }
            $ret = substr($ret, 0, strlen($ret)-5) . ') ';
            
        }
        return $ret;
    }

}