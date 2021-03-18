<?php

namespace Janssen\Helpers;

use Janssen\Engine\Request;
use Janssen\Engine\Header;
use Janssen\Helpers\Response\ErrorResponse;

class Exception extends \Exception
{
    /**+
     * Advise: Message that can help to solve the problem or a suggestion
     * to the user to continue after this error.
     */
    protected $advise;
    protected $stack_items = [];
    protected $rewrite_stack = false;
    protected $is_http_code = false;


    public function __construct($message = '', $code = 0, $advice = '', Array $stack_items = [], $rewrite_stack = false, $render = false)
    {
        //parent::__construct($message, $code);
        $this->message = $message;
        $this->setErrorCode($code);
        $this->advise = $advice;
        $this->rewrite_stack = $rewrite_stack;

        //if(!empty($stack_items))
        if($rewrite_stack)
            $this->stack_items = $stack_items;
        
        if($render)
            echo $this->render();
    }

    public function render()
    {
        $request = new Request;

        $h = current_header();
        if(!headers_sent())
            $h->setMessage('Internal Server Error', 500, true)
                ->send();

        $ej = $request->expectsJSON();
        // here we'll use the ErrorResponse object only for
        // making the html to return.
        $er = new ErrorResponse($this->message,$this->code, $this->advise);
        $er->setException($this)
            ->isJson($ej);
        if($this->rewrite_stack)
            $er->forceStackItems($this->stack_items);
        else
            $er->setPrependStackItems($this->stack_items);
        return $er->render();    
    }

    public function getAdvise()
    {
        return $this->advise;
    }

    public function getRewriteStack()
    {
        return $this->rewrite_stack;
    }

    public function getStackItems()
    {
        return $this->stack_items;
    }

    private function setErrorCode($code)
    {
        $this->code = $code;
        $this->is_http_code = ($code >= 100 and $code <= 599);
    }

    public function isHttpCode()
    {
        return $this->is_http_code;
    }

    public function __toString()
    {
        echo $this->render();    
        return $this->getMessage();
    }

}
