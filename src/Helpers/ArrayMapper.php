<?php

namespace Janssen\Helpers;

use Janssen\Engine\Mapper;

class ArrayMapper {

    private static $map;

    private static $array_to_map;

    public static function toOutput(Mapper $map, Array $mappeable)
    {
        $mo = $map->asOutput();
        $ret = [];
        foreach($mappeable as $k=>$v){    
            if(is_array($v)){
                $ret[] = self::mapArray($mo, $v);
            }else{
                $r[$map[$k]] = $v;    
            }
        }
        return $ret;
    }

    public static function mapArray(Array $map, Array $ar){
        $r = [];
        foreach($ar as $k=>$v){
            $r[$map[$k]] = $v;
        }
        return $r;
    }

    
}