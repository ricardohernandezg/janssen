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

    /**
     * Add the rules 
     *
     * @param Array $rules
     * @return object
     */
    protected function addRules(Array $rules)
    {
        $this->rules = $rules;
        return $this;
    }

    /**
     * Add the messages
     *
     * @param Array $messages
     * @return object
     */
    protected function addMessages(Array $messages)
    {
        $this->messages = $messages;
        return $this;
    }

    /**
     * Add mapping
     *
     * @param Array $mapping
     * @return object
     */
    protected function addMapping(Array $mapping = [])
    {
        if(empty($mapping))
            $this->mapping = 'auto';
        else
            $this->mapping = $mapping;
        return $this;
    }

    /**
     * Returns the current set of rules excluding disabled
     *
     * @return array
     */
    public function getRules()
    {
        $this->updateParamCount();
        if(!empty($this->disabled)){
            $ret = [];
            foreach($this->rules as $k => $rule){
                if($this->isEnabled($k))
                    $ret[$k] = $rule;
            }
            $ret['_param_count'] = $this->rules['_param_count'];
            return $ret;
        }else
            return $this->rules;
    }

    /**
     * Returns the current set of rules excluding disabled
     *
     * @return array
     */
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

    /**
     * Returns the current mapping
     *
     * @return void
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * Adds or alters a rule
     *
     * @param string $name
     * @param array $rules
     * @param array $messages
     * @param array $mapping
     * @return void
     */
    public function addRule($name, Array $rules = null, Array $messages = null, Array $mapping = null)
    {
        if($rules)
            $this->rules[$name] = $rules;
        
        if($messages)
            $this->messages[$name] = $messages;
        
        if($mapping)
            $this->mapping[$name] = $mapping;

        return $this->updateParamCount();
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

        return $this->updateParamCount();
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

        $this->updateParamCount();
    }

    /**
     * Alias for disableRule
     * @return object
     */
    public function without($name){
        return $this->disableRule($name);
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

    /**
     * Updates parameter count if exists in rules
     *
     * @return object
     */
    private function updateParamCount(){
        if(isset($this->rules['_param_count'])){
            $dc = count($this->disabled);
            $rc = count($this->rules) - 1;
            $this->rules['_param_count'] = $rc-$dc;
        }
        return $this;
    }
}
