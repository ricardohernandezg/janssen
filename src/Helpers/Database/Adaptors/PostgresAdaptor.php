<?php 

namespace Janssen\Helpers\Database\Adaptors;

use Janssen\Helpers\Database\Adaptor;

class PostgresAdaptor extends Adaptor
{

    protected $_config_fields = [
        'host' => '',
        'user' => '',
        'pwd' => '',
        'db' => ''
    ];

    public function connect(){

    }

    public function query(){
        
    }

    public function statement(){
        
    }

    public function howMany()
    {
    }
        
    public function insert(){
        // the last insert id here is mad attaching 
        // RETURNING id field statement to the query
    }
}