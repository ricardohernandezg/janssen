<?php 

namespace Janssen\Engine;

class Header
{

    CONST HTTP_VERSION = 'HTTP/1.1';
    private $collection = [];

    public function getMessage(){
        return $this->collection;
    }

    public function setMessage($message, $code = null, $replace = true){
        if(empty($message))
            $message = ' '; // blank space in message will be interpreted by browser as put-your-own-message
        $this->collection[] = [
            'message' => $message,
            'code' => $code,
            'replace' => $replace
        ];
        return $this;
    }

    public function hasRedirect()
    {
        $redirect_codes = [301,302,303,307,308];
        $found = false;
        foreach($this->collection as $k=>$v){
            $found = (in_array($v['code'], $redirect_codes));
            if($found)
                break;
        }
        return $found;
    }

    public function send()
    {
        foreach($this->collection as $k=>$v){
            
            if(empty($v['code']))
                header($v['message']);
            else
                header($v['message'], $v['replace'], $v['code']);
        }
    }

    /**
     * Asking for this class as string will give an well formed 
     * HTTP status response string 
     *
     * @return string
     */
    public function __toString()
    {
        //'HTTP/1.1 519 My Custom Status'
        $ret = '';
        foreach ($this->message as $k=>$v) {
            $ret .= self::HTTP_VERSION . " " . $v['code'] . " " . $v['message'] . "\n";
        }
        return $ret;
    }

    private function normalizeHeaderName($name)
    {
        $ret = str_replace("\\", '-', $name);
        $ret = str_replace(" ", '_', $ret);
        return $ret;
    }
}