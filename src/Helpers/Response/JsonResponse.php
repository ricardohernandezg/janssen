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
        $rendered = '';
        if(is_array($this->content))
            $rendered = json_encode($this->content);
        else
            $rendered = json_encode([$this->content]);

        return $rendered;
    }

}