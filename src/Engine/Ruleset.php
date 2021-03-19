<?php 

namespace Janssen\Engine;

use Janssen\Helpers\Exception;
class Ruleset
{

    private $rules = [];

    private $messages = [];

    private $mapping = [];

    private $disabled = [];


    public function __construct($rules, $messages = [], $mapping = [])
    {
        $this->addRules($rules)
            ->addMessages($messages)
            ->addMapping($mapping);
    }

    protected function ruleExists($name)
    {
        return array_key_exists($name, $this->rules);
    }

    protected function addRules(Array $rules)
    {
        $this->rules = $rules;
        return $this;
    }

    protected function addMessages(Array $messages)
    {
        $this->messages = $messages;
        return $this;
    }

    protected function addMapping(Array $mapping = [])
    {
        if(empty($mapping))
            $this->mapping = 'auto';
        else
            $this->mapping = $mapping;
        return $this;
    }

    public function getRules()
    {
        return $this->rules;
    }

    public function getMessages()
    {
        return $this->messages;
    }

    public function getMapping()
    {
        return $this->mapping;
    }    

    public function addRule($name, Array $rules = null, Array $messages = null, Array $mapping = null)
    {
        if($rules)
            $this->rules[$name] = $rules;

        if($messages)
            $this->messages[$name] = $messages;
        
        if($mapping)
            $this->mapping[$name] = $mapping;

        return $this;
    }

    public function alterRule($name, Array $rules = null, Array $messages = null, Array $mapping = null)
    {
        if ($this->ruleExists($name)){
            // alter existing rule
        }else
            throw new Exception('Validation rule does not exists!', 500);

        return $this;
    }

    public function alterMapping($key, $new_map, $new_key = '')
    {
        if ($this->ruleExists($key) && !empty($new_key))
            unset ($this->mapping[$key]);
        
        $this->mapping[$key] = $new_map;
        return $this;
    }

    public function disableRule($name)
    {
        if ($this->ruleExists($name))
            $this->disabled[] = $name;
        else
            throw new Exception('Malformed validation rule', 500);

        return $this;
    }

}
