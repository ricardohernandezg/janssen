<?php 

namespace Janssen\Traits;

use \ReflectionClass;
use Janssen\Helpers\Exception;

Trait StaticCall
{
    
    /**
     * Call a function defined as static concrete way. Useful
     * for chaining
     *
     * @param String $name
     * @param Array $args
     * @return void
     */
    public function __call($name, $args)
    {
        $r = new ReflectionClass($this);
        if ($r->hasMethod($name)) {
            $m = $r->getMethod($name);
            if (!$m->isPrivate()) {
                $p = $m->getParameters();
                if (count($p) > 0) {
                    $ret = $m->invokeArgs(null, $args);
                } else
                    $ret = self::$name();
            } else
                throw new Exception("Trying to call private method {$name}() in " . __CLASS__);
        } else
            throw new Exception("Trying to call inexistent method {$name}() in " . __CLASS__);
        return $ret;
    }

}