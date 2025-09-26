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

    public function tableExists($table_name, $schema = null){
        $sql = "SELECT tablename 
        FROM pg_catalog.pg_tables 
        WHERE schemaname = '$schema' 
        AND tablename = '$table_name';";
    }

    public function viewExists($view_name, $schema = null){
        $sql = "SELECT table_name 
        FROM information_schema.views 
        WHERE table_schema = '$schema' 
        AND table_name = '$view_name';";        
    }

    public function procedureExists($procedure_name, $schema = null){
        $sql = "SELECT routine_name 
        FROM information_schema.routines 
        WHERE specific_schema = '$schema' 
        AND routine_type = 'PROCEDURE' 
        AND routine_name = '$procedure_name';";
    }
    
    public function functionExists($function_name, $schema = null){
        $sql = "SELECT routine_name 
        FROM information_schema.routines 
        WHERE specific_schema = '$schema' 
        AND routine_type = 'FUNCTION' 
        AND routine_name = '$function_name';";
    } 

    /**
     * Try to find the default schema from configuration
     */
    private function resolveDefaultSchema()
    {

    }
}