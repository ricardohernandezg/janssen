<?php 

namespace Janssen\Traits\GenericSQLSyntax;

use Janssen\Helpers\Exception;
use Janssen\Traits\InstanceGetter;

trait GenericWhereSyntax
{
    use InstanceGetter;
    
    private static function criteriaRequiresArray($operator)
    {
        return in_array($operator, ['IN', 'NOT IN', 'NOTIN', 'BETWEEN']);
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
            case 'NOT IN':
            case 'NOTIN':
                $ret = 'NOT IN (';
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

    protected static function flatWhere(Array $where = [])
    {
        $ret = '';
        foreach($where as $v)
        {
            
            if(($v['relation'] ?? '') !== '') $ret .= " {$v['relation']} ";
            $ret .= "(";
            foreach($v['members'] as $member){
                if(in_array($member['operator'], ['IN','NOT IN', 'NOTIN','BETWEEN','IS NULL','NULL','NOT NULL'])){
                    $ret .= "{$member['field']} " . self::makeComplexCriteriaSyntax($member['operator'], $member['value']) . " AND ";
                }else
                    $ret .= " {$member['field']} {$member['operator']} " . self::processValue($member['value']) . " AND ";
            }
            $ret = substr($ret, 0, strlen($ret)-5) . ') ';
            
        }
        return $ret;
    }


}