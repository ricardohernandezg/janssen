<?php

namespace Janssen\Engine;

use Exception;
use Janssen\Engine\Rule;
use Janssen\Engine\Ruleset;
use Janssen\Engine\Mapper;
use Janssen\Engine\Request;
use Janssen\Traits\InstanceGetter;

class Validator
{
    use InstanceGetter;

    /**
     * Guard to authorize access to the controller asociated 
     *
     * @var String
     */
    public $guard;


    /**
     * Methods of controller that will be ignored by the guard
     *
     * @var Array
     */
    public $guard_except = [];

    /**
     * Set of errors found to report
     *
     * @var Array
     */
    protected $_validation_errors = [];

    /**
     * Rules for parameter type validation
     *
     * @var Array
     */
    private $paramTypeRules = [
        // 'string' => '^.+$',
        // 'integer' => '^[0-9]+$',
        // 'float' => '^[0-9]+[\.,][0-9]+$',
        // 'bool' => '^true|false$',
        'date' => '/^(?:19|20)\d\d-(?:[0]?[1-9]{1}|1[012]{1})-(?:[0]?[1-9]{1}|[12]{1}[0-9]{1}|[3]{1}[01]{1})$/',
        'time' => '/^([0]?[0-9]{1}|[1]{1}[0-9]{1}|[2]{1}[0-3]{1}):([0]?[0-9]{1}|[1-5]{1}[0-9]{1}):([0]?[0-9]{1}|[1-5]{1}[0-9]{1})$/',
        'time-hm' => '/^([0]?[0-9]{1}|[1]{1}[0-9]{1}|[2]{1}[0-3]{1}):([0]?[0-9]{1}|[1-5]{1}[0-9]{1})$/',
        'datetime' => '/^(19|20)\d\d-([0]?[1-9]{1}|1[012]{1})-([0]?[1-9]{1}|[12]{1}[0-9]{1}|[3]{1}[01]{1})\s([0]?[0-9]{1}|[1]{1}[0-9]{1}|[2]{1}[0-3]{1}):([0]?[0-9]{1}|[1-5]{1}[0-9]{1}):([0]?[0-9]{1}|[1-5]{1}[0-9]{1})$/',
        'isodate' => '/^(?:19|20)\d\d(?:[0]?[1-9]{1}|1[012]{1})(?:[0]?[1-9]{1}|[12]{1}[0-9]{1}|[3]{1}[01]{1})$/'
    ];

    private $rules = [];
    private $messages = [];

    /**
     * Return validation errors
     * 
     * @todo return mapped field names! This always goes to frontend
     *
     * @return Array
     */
    public function getValidationErrors($mapped = true)
    {
        return $this->_validation_errors;
    }

    /**
     * Sets new error to validation errors array
     *
     * @param String $key name of variable to be marked as bad
     * @param String $message message to be returned
     * @param Boolean $map_output Indicates if the key name have to be mapped for output 
     * @return Validator
     */
    protected function setValidationError($key, $message, $map_output = true)
    {
        if($map_output)
            $key = Request::parameters()->getOriginalName($key);
            
        $this->_validation_errors[] = [$key => $message];
        return $this;
    }

    /**
     * Sets new error to validation errors array from an array
     *
     * @param Array $errors key-value array with the errors to report
     * @return Validator
     */
    protected function setValidationErrors(Array $errors)
    {
        foreach($errors as $k=>$error)
        {
            $this->setValidationError($k, $error);
        }
        return $this;
    }

    /**
     * Indicate if there are errors to report
     *
     * @return Boolean
     */
    public function thereAreErrors()
    {
        return (count($this->_validation_errors) > 0);
    }

    /**
     * Validate a value against the type
     * 
     * @param String|Integer|Boolean $value
     * @param Integer $type
     * @return Boolean
     * 
     */
    public function validateType($value, $type)
    {
        
        switch($type)
        {
            case Rule::RULE_TYPE_ANY:
                return true;
                break;
            case Rule::RULE_TYPE_STRING:
                return is_string($value);
                break;
            case Rule::RULE_TYPE_INTEGER:
                if(strval($value) === '0')
                    return true;
                else{
                    $re = '/^-?\d+$/';
                    return $this->validateAgainstRegex($value, $re);
                }
                break;
            case Rule::RULE_TYPE_FLOAT:
                // we don't need to ask if its float as 1.0 is valid as integer and as float.
                return is_numeric($value);
                break;
            case Rule::RULE_TYPE_BOOL:
                return in_array(strtolower($value),[0,1,'true','false']) && is_bool(boolval($value));
                break;
            case Rule::RULE_TYPE_DATE:
                $re1 = $this->paramTypeRules['date'];
                $re2 = $this->paramTypeRules['isodate'];
                return $this->validateAgainstRegex($value, $re1) || $this->validateAgainstRegex($value, $re2);
                break;
            case Rule::RULE_TYPE_TIME:
                $re = $this->paramTypeRules['time'];
                return $this->validateAgainstRegex($value, $re);            
                break;
            case Rule::RULE_TYPE_TIMEHM:
                $re = $this->paramTypeRules['time-hm'];
                return $this->validateAgainstRegex($value, $re);            
                break;
            case Rule::RULE_TYPE_DATETIME:
                $re = $this->paramTypeRules['datetime'];
                return $this->validateAgainstRegex($value, $re); 
                break;
            case Rule::RULE_TYPE_FILE:
                // check if value exists in files
                //$request = new Request;
                //return !is_null($request->file($value));
                return !is_null(Request::file($value));
                break;
            case Rule::RULE_TYPE_ARRAY:
                // check if value is an array
                return is_array($value);
                break;
            case Rule::RULE_TYPE_DECIMAL:
                // check if value is a number, we don't need to check if it a strict decimal
                // return is_numeric( $value ) && floor( $value ) != $value;
                return is_numeric($value);
                break;
            default:
                return false;                
        }
    }

    /**
     * Validates a value against the rule
     * 
     * @param String|Integer|Boolean $value
     * @param Integer|Array $rule
     * @return Boolean
     * 
     */
    public function validateRule($value, $rule)
    {
        $ret = false;
        $param = $rule;
        // rule can be an array for the rules needing parameters
        if(is_array($rule))
            $rule = array_shift($param);
        
        switch ($rule) {
            case Rule::RULE_NUMBER_POSITIVE:
                $ret = (is_numeric($value) && $value >= 0);
                break;
            case Rule::RULE_NUMBER_NEGATIVE:
                $ret = (is_numeric($value) && $value < 0);
                break;                
            case Rule::RULE_GRTN_0:
                $ret = (is_numeric($value) && $value > 0);
                break;
            case Rule::RULE_REQUIRED:
                $ret = !empty($value) || $value == '0';
                break;
            case Rule::RULE_BETWEEN:
                // param should be an 2-element array, if not, assume the same value 
                $min = $param[0];
                $max = $param[1];
                $ret = (is_numeric($value) && ($value >= $min && $value <= $max));
                break; 
            case Rule::RULE_MINLENGTH:
                $minlength = $param[0];
                if(is_array($value))
                    $ret = (count($value) >= $minlength);
                else
                    $ret = ((empty($value) || strlen(strval($value)) >= $minlength));
                break;                
            case Rule::RULE_MAXLENGTH:
                $maxlength = $param[0];
                if(is_array($value))
                    $ret = (count($value) <= $maxlength);
                else
                    $ret = ((empty($value) || strlen(strval($value)) <= $maxlength));
                break; 
            case Rule::RULE_FIXED_LENGTH:
                $length = $param[0];
                if(is_array($value))
                    $ret = (count($value) == $length);
                else
                    $ret = (strlen(strval($value)) == $length);
                break;                 
            case Rule::RULE_FILE_MIMETYPE:
                if(is_array($param))
                    $ret = in_array($value, $param[0]);
                else
                    $ret = ($value == $param);
                break;
            case Rule::RULE_FILE_MAXSIZE:
                $ret = (($value > 0) && ($value <= $param[0]));
                break;
            case Rule::RULE_ONEOF:
                // value must be any of the given values
                if(is_array($param[0])){
                    $ret = in_array($value, $param[0]);
                }else{
                    $ret = ($value == $param[0]);
                }
                break;
            case Rule::RULE_STRICT_STRING:
                $re = '/^[A-Za-zÁÉÍÓÚáéíóúÑñ\-\s\']*$/m';
                $ret = $this->ValidateAgainstRegex($value, $re);
                break;
            case Rule::RULE_EMAIL:
                $ret = (filter_var($value, FILTER_VALIDATE_EMAIL) !== false);
                break;
            case Rule::RULE_ARRAY_MEMBER_TYPE:
                // array members must be this type
                $type = $param[0];
                $ret = true;
                if(is_array($value)){
                    foreach($value as $v){
                        $ret = ($ret && $this->validateType($v, $type));
                    }
                }else
                    $ret = false;
                break;
            default:
                $ret = false;
        }   
        return $ret;
    }

    /**
     * Validate the incoming request agaist the given rules
     * 
     * @param Array $rules
     * @param Array|'auto' $map
     * @param String|'post' $bag
     * @return Boolean
     * 
     */
    public function validateRequest()
    {

        $input = Request::getMapped();

        // check if rules have files in their declaration
        // this way we can validate files no matter the bag used
        // (obviously for _get we'll never get files) 
        $expected_files = $this->rulesHaveFiles($this->rules);        
        if($expected_files > 0){
            // we'll need to add the files to the input array
            foreach($_FILES as $k=>$file)
            {
                // first we need to check if the rul expects this as a file, as the Rules could change this
                $pn = Request::parameters()->getMap()->asInput($k);
                $fr = $this->rules[$pn];
                if($fr['type'] !== Rule::RULE_TYPE_FILE)
                    continue;

                $file['is_file'] = true;
                if($file['error'] == 0){
                    $file['server_type'] = mime_content_type($file['tmp_name']);
                    $cm = \Janssen\Helpers\Attachment::checkMimeType($file['server_type'], $file['type']);
                    // if there is a mapping via AttachmentHelper, we make reported and server
                    // mimetype the same so the validation can pass.
                    if($cm)
                        $file['type'] = $file['server_type'];
                }
                else
                    $file['server_type'] = $file['type'];
                // we need to know if the file has an internal mapped name to use it in the request
                $file['orig_name'] = $k;
                $input[$pn] = $file;
            }
        }

        // before any rule, let's validate the parameter count
        // this only will affect if _param_count is present
        // otherwise will be considered valid any number of params
        $pc = isset($this->rules['_param_count'])?$this->rules['_param_count']:0;
        if(($pc > 0 && count($input) > $pc) ||  count($input) < $pc && $this->allParamsAreRequired($this->rules))
        {
            $this->setValidationError('_param_count', 'Parameter count doesn\'t match!');
            return false;
        }            

        foreach($this->rules as $field_name=>$restriction)
        {
            if($field_name == '_param_count')
                continue;

            // check existance of member
            $ir = (isset($restriction['rules']) && $this->isRequired($restriction['rules']));
            
            $current_param_is_file = false;
            if(!isset($input[$field_name])){
                if($ir)
                    $this->setValidationError($field_name, 'Missing parameter');
                else
                    $input_value = '';
                continue;
            }else{
                $input_value = $input[$field_name];
            }
                
            // check parameter type
            // if parameter type is not defined, assume ANY
            $param_type = isset($restriction['type'])?$restriction['type']:Rule::RULE_TYPE_ANY;
            $file_uploaded = false;
            if(is_array($input_value) && isset($input_value['is_file']) && $input_value['is_file'] == true){
                $current_param_is_file = true;
                $file_uploaded = ($input_value['error'] == 0);
            }

            if(is_array($input_value) && !$current_param_is_file){
                if(count($input_value) > 0 && $ir)
                    $valid_type = $this->validateType($input_value, $param_type);
                else
                    $valid_type = true; // pass if not required and empty
            }elseif ($current_param_is_file){
                // we need the original parameter name as it will be queried from the
                // $_FILE object
                $file_param_name = $input[$field_name]['orig_name'];
                $valid_type = $this->validateType($file_param_name, $param_type);
            }else{
                if(trim($input_value) !== '' && $ir)
                    $valid_type = $this->validateType($input_value, $param_type);
                else
                    $valid_type = true; // pass if not required and empty
            }

            if(!$valid_type && !$current_param_is_file){
                $this->setValidationError($field_name, 'Incorrect parameter type');
                continue;
            }
            
            if(isset($restriction['rules'])){
                if(is_array($restriction['rules']) && !$current_param_is_file){
                    foreach($restriction['rules'] as $k=>$rule)
                    {
                        $valid_rule = $this->validateRule($input_value, $rule);
                        if(!$valid_rule)
                            $this->setValidationError($field_name, 'Parameter doesn\'t match the rules');                       
                    }
                }elseif ($current_param_is_file){
                    // for files we need to think a distinct way to apply restriction
                    // as we know what fields a normal file comes with. We can evaluate the sent
                    // data with our own measurement to be sure we're not being injected.
                    // check relevant fields: size and mimetype

                    // first check if browser reported mimetype is same as server reported.
                    // if it are different, give precedence to server
                    if($file_uploaded == false)
                        break;

                    if($input_value['type'] !== $input_value['server_type']){
                        $this->setValidationError($field_name, 'File type in server mismatchs type reported by browser');
                    }else{
                        foreach($restriction['rules'] as $k=>$rule)
                        {
                            $right_rule = (is_array($rule)?$rule[0]:$rule);

                            switch($right_rule)
                            {
                                case Rule::RULE_FILE_MIMETYPE:
                                    $valid_rule = $this->validateRule($input_value['type'], $rule);
                                    if(!$valid_rule)
                                        $this->setValidationError($field_name, 'File type not accepted by server');
                                    break;
                                case Rule::RULE_FILE_MAXSIZE:
                                    $valid_rule = $this->validateRule($input_value['size'], $rule);
                                    $size_invalidated_by_server = ($input_value['error'] == 1 || $input_value['error'] == 2);
                                    if(!$valid_rule || $size_invalidated_by_server)
                                        $this->setValidationError($field_name, 'File size is greater than accepted by server');
                                    break;
                                case Rule::RULE_REQUIRED:
                                    $valid_rule = ($input_value['error'] == 0);
                                    if(!$valid_rule)
                                        $this->setValidationError($field_name, 'File is required');
                            }
                        } // foreach
                    }// if

                }else{
                    $valid_rule = $this->validateRule($input_value, $restriction['rules']);
                    if(!$valid_rule)
                        $this->setValidationError($field_name, 'Parameter doesn\'t match the rules');
                }   
            }
        }

        return  (!$this->thereAreErrors());

        /**
         * @todo this is not necessary anymore
         */
        // register parameters 
        /*
        foreach($input as $k=>$v){
            Request::registerParameter($k, $v);
        }
        */
    }

    /**
     * Useful for group validation. It uses the 
     * mapped fields to check if at least one of the group
     * is present. False if none is present or valid.
     *
     * @todo Complete this function when refactoring the Validator
     * the idea is that Validator has its own attributes that defines
     * the controller, method and rules to be used so is not needed
     * to have a function for each call unless its needed
     * 
     * @param Array $fields
     * @return void
     */
    public function oneOf(Array $fields)
    {
        $r = true;
        $rq = new Request;
        return $r;
    }

    /**
     * Checks if an action is excepted of Guard restrictions
     */
    public function isExcepted($action)
    {
        if(empty($this->guard_except))
            return false;
        else
            return (array_search($action, $this->guard_except) !== false);
    }

    /**
     * Check if RULE_REQUIRED restriction is present
     *
     * @param Array $restrictions
     * @return boolean
     */
    protected function isRequired($rules)
    {
        return in_array(Rule::RULE_REQUIRED, $rules);
    }

    
    /**
     * Prepares validator rules and sets up the
     * messages and mapping
     *
     * @param String $rule_class
     * @param String $method
     * @param String $bag
     * @return Validator
     */
    protected function prepare(Ruleset $ruleset, $bag = 'post')
    {
        if(!Request::isValidBag($bag))
            throw new Exception("Parameter bag $bag is not valid");

        $this->rules = $ruleset->getRules();
        $this->messages = $ruleset->getMessages();
        Request::map(new Mapper($ruleset->getMapping()), $bag);

        return $this;
    }

    // MAGIC METHODS
    /**
     * Return all the errors found separated by new line
     *
     * @return string
     */
    public function __toString()
    {
        $r = "";
        foreach($this->_validation_errors as $k=>$v)
        {
            $r .= "$k - {$v[0]}:{$v[1]}\r\n";
        }
        return $r;
    }

    // PRIVATE FUNCTIONS
    /**
     * Validates a parameter against a regular expression
     *
     * @param String|Integer $value
     * @param String $re
     * @return Boolean
     */
    private function ValidateAgainstRegex($value, $re)
    {
        $i = preg_match($re, $value);
        return ($i == 1);
    }

    private function rulesHaveFiles($rules = [])
    {
        foreach($rules as $rule)
        {
            if(isset($rule['type'])){
                if ($rule['type'] == Rule::RULE_TYPE_FILE)
                    return true;
            }
        }
        return false;
    }


    /**
     * Check if all parameters are required to pass the validation
     * against the set rules
     *
     * @param Array $rules
     * @return Boolean
     */
    private function allParamsAreRequired(Array $rules)
    {
        if(is_array($rules) && count($rules) == 1 && isset($rules['_param_count']))
            return false;
     
        $r = true;
        foreach($rules as $k=>$rule){
            if($k == '_param_count')
                continue;
            if(is_array($rule) && isset($rule['rules']))
                $r = ($r && in_array(Rule::RULE_REQUIRED, $rule['rules']));
            else
                $r = ($r && false);
        }
        return $r;
    }

    // STATIC FUNCTIONS   
    /**
     * Useful to make validations without having to instantiate the Validator
     * 
     * validateRule = validateRuleStatic
     *  
     * validateType = validateType
     * */ 
    public static function validateRuleStatic($value, $rule){
        $oi = self::getOwnInstance();
        return $oi->validateRule($value, $rule);
    }

    public static function validateTypeStatic($value, $type)
    {
        $oi = self::getOwnInstance();
        return $oi->validateType($value, $type);
    }

}