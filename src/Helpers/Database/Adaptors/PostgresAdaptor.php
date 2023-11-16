<?php 

namespace Janssen\Helpers\Database\Adaptors;

use Janssen\Helpers\Database\Adaptor;

class PostgresAdaptor extends Adaptor
{

    protected $_config_fields = [
        'host' => '',
        'user' => '',
        'pwd' => '',
        'db' => '',
        'port' => 5432
    ];

    public function connect(){

    }

    public function query($sql){
        
    }

    public function statement($sql){
        
    }

    public function howMany($sql)
    {
    }
        
    public function insert($sql){
        // the last insert id here is mad attaching 
        // RETURNING id field statement to the query
    }
}