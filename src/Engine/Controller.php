<?php 

namespace Janssen\Engine;

use Janssen\Engine\Validator;
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

    /**
     * Helper method to make static rule validatons from controller
     * 
     */
    public function validateRule($value, $rule, ?array $params) : bool
    {
        return Validator::validateRuleStatic($value, $rule);
    }

    /**
     * Helper method to make static type validatons from controller
     * 
     */
    public function validateType($value, $type) : bool
    {
        return Validator::validateTypeStatic($value, $type);
    }

}