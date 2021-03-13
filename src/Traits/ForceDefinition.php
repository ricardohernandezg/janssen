<?php 

namespace Janssen\Traits;

use Janssen\Helpers\Exception;

/**
 * Use Reflection to determine if class has defined
 * the properties needed to work
 * 
 * A variation of:
 * @see https://stackoverflow.com/a/10368627
 * 
 */
trait ForceDefinition
{

    protected function requiredPropertiesExists()
    {
        $r = new \ReflectionClass($this);
        if($r->hasProperty('mustBeDefined') && is_array($this->mustBeDefined)){
            $p = $r->getProperties(\ReflectionProperty::IS_PUBLIC);
            $tp = $this->transformReflectionProperties($p);
            $found = true;
            foreach($this->mustBeDefined as $v)
            {
                if(in_array($v, $tp))
                    $found = ($found && true);
                else
                    throw new Exception('Model ' . get_class($this) . " must define '$v' property", 500);
            }
            return $found;
        }else
            throw new Exception('To use ForceDefinition you must define $mustBeDefined Array', 500);
            

    }

    private function transformReflectionProperties($rp)
    {
        $r = [];
        foreach($rp as $v){
            $r[] = $v->name;
        }
        return $r;
    }

}