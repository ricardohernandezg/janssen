<?php 

namespace Janssen\Traits\GenericSQLSyntax;

trait GenericOrderBySyntax
{

    private static $__orderby = [];

    protected function prepareOrderby(array $parted_sql)
    {
        self::$__orderby = $parted_sql['orderby']; 
        return " ORDER BY " . $this->flatOrderby();
    }
    
    private function flatOrderby()
    {
        $ret = "";
        foreach(self::$__orderby as $field_name=>$order){
            $ret .= $field_name . (trim(strtoupper($order)) == "DESC" ? " DESC, " : " ASC, ");
        }
        return substr($ret,0,strlen($ret)-2);
        
    }

}