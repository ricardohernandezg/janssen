<?php 

namespace Janssen\Engine;

use Janssen\Engine\Header;
use Janssen\Helpers\Exception;
use Janssen\Helpers\FlashMessage;

abstract class Response
{

    use \Janssen\Traits\InstanceGetter;

    protected $content;
    protected $header;
    protected $placetakers = [];
    protected $content_type;

    public function __construct()
    {
        $this->header = new Header;
    }

    /**
     * All the clases than extend this must implement its own
     * rendering routine
     *
     * @return void
     */
    abstract public function render();

    /**
     * Replaces the full header on the response
     *
     * @param Header $header
     * @return Response
     */
    public function setHeader(Header $header)
    {
        $this->header = $header;
        return $this;
    }

    public function getHeaders()
    {
        return $this->header;
    }

    public function hasHeaders()
    {
        return ($this->header instanceof Header);
    }

    public function addHeader(Header $header)
    {
        $hl = $header->getMessage();
        foreach($hl as $k=>$v){
            $this->header->setMessage($v['message'], $v['code'], $v['replace']);
        }
        return $this;
    }

    public function sendHeaders()
    {
        if($this->header instanceof Header)
            $this->header->send();
        
        return $this;
    }

    public function setContentType($type = null)
    {
        if(empty($type))
            $this->content_type = "";
        else
            $this->content_type = $type;
        
        return $this;            
    }

    public function getContentType()
    {
        return $this->content_type;
    }

    /**
     * Sets content of response
     *
     * @param String|Array $content
     * @return Response
     */
    public function setContent($content){
        $this->content = $content;
        return $this;
    }

    public function getContent(){
        if(is_bool($this->content)) {
            return ($this->content === true)?'true':'false';
        }
        return $this->content;
    }

    public function withMessageSet(Array $messages, $type = 'info')
    {
        foreach($messages as $k=>$v){
            FlashMessage::add($k, $v);
        }
        FlashMessage::forceUpdate();        
        return $this;
    }

    public function withMessage($key, $message, $type = 'info')
    {
        FlashMessage::add($key, $message, $type);
        return $this;
    }
    
    protected function isEmpty()
    {
        $c = $this->getContent();
        return (is_array($c) || is_bool($c))?false:empty($c);
    }

    public function __toString()
    {
        if(is_null($this->header))
            $this->setHeader(new Header());

        $this->render();

        // redirects can have empty responses (and they should have empty reponses)
        if($this->isEmpty() && !$this->header->hasRedirect())
            throw new Exception('Empty response', 500, 'Contact administrator', [], true, true);

        // force flash messages to be written to Session before sending the response
        // this time we can delete it as we're going to a new page
        if(FlashMessage::howMany() > 0)
            FlashMessage::forceUpdate(true);

        $this->header->send();
        if($this->content_type)
            header('Content-Type: ' . $this->content_type);
        return $this->getContent();
    }
    
}