<?php 

namespace Janssen\Engine;

use Janssen\Helpers\FlashMessage;
class Controller
{

    public function makeFlashMessagesWithAuthReasons(Array $reasons)
    {
        foreach($reasons as $reason)
        {
            FlashMessage::add($reason['field'], $reason['reason'], $reason['type']);
        }
    }

}