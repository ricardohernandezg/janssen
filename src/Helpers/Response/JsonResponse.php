<?php 

namespace Janssen\Helpers\Response;

use Janssen\Engine\Header;
use Janssen\Engine\Response;

class JsonResponse extends Response
{

    public function __construct()
    {
        parent::__construct();
        $this->setContentType('application/json');
    }


    public function render()
    {
        $content = $this->getContent();
        if(!is_array($content))
            $content = json_encode([$this->content]);
            
        $this->setContent($content);
        return $this;
    }

}