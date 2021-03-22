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
        if(!empty($this->disabled)){
            $ret = ['_param_count' => $this->rules['_param_count']] ;
            foreach($this->rules as $k => $rule){
                if($this->isEnabled($k))
                    $ret[$k] = $rule;
            }
            return $ret;
        }else
            return $this->rules;
    }

    public function getMessages()
    {
        if(!empty($this->disabled)){
            $ret = [];
            foreach($this->messages as $k => $message){
                if($this->isEnabled($k))
                    $ret[$k] = $message;
            }
            return $ret;
        }else
            return $this->messages;
    }

    public function getMapping()
    {
        return $this->mapping;
    }


    public function addRule($name, Array $rules = null, Array $messages = null, Array $mapping = null)
    {
        if($rules){
            if(!isset($this->rules[$name]))
                $this->rules['_param_count']++;
            
            $this->rules[$name] = $rules;
        }

        if($messages)
            $this->messages[$name] = $messages;
        
        if($mapping)
            $this->mapping[$name] = $mapping;

        return $this;
    }

    /**
     * Alter a given rule. The rule with given name will be analyzed with the
     * new rule.
     * Let $rules null to delete the full rule
     * Send $rules as empty array to only alter the messages.
     *
     * @param string $name
     * @param array $rules
     * @param array $messages
     * @return object
     */
    public function alterRule($name, Array $rules = null, Array $messages = null)
    {
        if ($this->ruleExists($name)){
            // alter existing rule
            if(is_null($rules)){
                $this->rules[$name] = [];
                $this->messages[$name] = [];
            }else{
                if(!empty($rules))
                    $this->rules[$name] = $rules;
                if($messages)
                    $this->messages[$name] = $messages;
            }
        }else
            throw new Exception('Validation rule does not exists!', 500);

        $ct = count($this->rules);
        $this->rules['_param_count'] = isset($this->rules['_param_count']) ? $ct -1 : $ct;
        return $this;
    }

    public function alterMapping($key, $new_map, $new_key = '')
    {
        if ($this->ruleExists($key) && !empty($new_key))
            unset ($this->mapping[$key]);
        
        $this->mapping[$key] = $new_map;
        return $this;
    }

    /**
     * Disable rule for validation
     *
     * @param string $name
     * @return object
     */
    public function disableRule($name)
    {
        if ($this->ruleExists($name))
            $this->disabled[] = $name;
        else
            throw new Exception('Malformed validation rule', 500);

        return $this;
    }

    /**
     * Validate only with one rule
     *
     * @param string $name
     * @return object
     */
    public function useOnly($name){
        foreach($this->rules as $k => $rule){
            if($k !== $name && $k !== '_param_count'){
                $this->disableRule($k);
            }
        }
        $this->rules['_param_count'] = 1;
        return $this;
    }

    /**
     * Check if rule is disabled
     *
     * @param string $name
     * @return boolean
     */
    private function isEnabled($name){
        return (array_search($name, $this->disabled) === false);
    }
}
