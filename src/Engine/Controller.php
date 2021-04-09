<?php 

namespace Janssen\Engine;

use Janssen\Helpers\FlashMessage;
use Janssen\Helpers\Response\JsonResponse;
class Controller
{

    public function makeFlashMessagesWithAuthReasons(Array $reasons)
    {
        foreach($reasons as $reason)
        {
            FlashMessage::add($reason['field'], $reason['reason'], $reason['type']);
        }
    }

    public function makeJsonError($message) : Response
    {
        $r = new JsonResponse;
        $h = new Header;
        $h->setMessage('Internal Server Error', 500);
        $r->setHeader($h)
            ->setContent(['error' => $message]);
        return $r;
    }

}