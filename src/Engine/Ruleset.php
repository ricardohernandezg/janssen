<?php 

namespace Janssen\Engine;

class Ruleset
{

    private $rules = [];

    private $messages = [];

    private $mapping = [];


    public function __construct($rules, $messages = [], $mapping = [])
    {
        $this->addRules($rules)
            ->addMessages($messages)
            ->addMapping($mapping);
    }

    public function addRules(Array $rules)
    {
        $this->rules = $rules;
        return $this;
    }

    public function addMessages(Array $messages)
    {
        $this->messages = $messages;
        return $this;
    }

    public function addMapping(Array $mapping = [])
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


}
