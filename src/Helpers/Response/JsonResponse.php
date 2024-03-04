<?php 

namespace Janssen\Helpers\Response;

use Janssen\Engine\Header;
use Janssen\Engine\Response;
use Janssen\Engine\Config;

class JsonResponse extends Response
{

    public function __construct()
    {
        parent::__construct();
        $this->setContentType('application/json');
    }

    /**
     * @return Object
     */
    public function render()
    {
        $content = $this->getContent();
        if(!is_array($content))
            $content = [$this->content];
            
        $this->setContent(json_encode($content, Config::get('json_encode_options')));
        return $this;
    }

    public function __toString()
    {  
        $t = $this->getContentType();            
        $this->header->setMessage('Content-Type: ' . $t)
            ->send();
        
        return $this->render()->getContent();
    }
}