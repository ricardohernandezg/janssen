<?php 

namespace Janssen\Helpers\Database\Adaptors;

use Janssen\Helpers\Database\Adaptor;
use Janssen\Helpers\Exception;
use PgSql\Connection;

class PostgresAdaptor extends Adaptor
{

    protected $_config_fields = [
        'host' => '',
        'user' => '',
        'pwd' => '',
        'db' => '',
        'port' => 5432
    ];

    private $last_result;    

    /**
     * Connects to PostgreSQL database
     * 
     * @return Connection
     */
    public function connect(): Connection
    {
        if ($this->isConnected())
            return $this->_cnx;

        // Construir cadena de conexión para pg_connect
        $connStr = sprintf(
            "host=%s port=%s dbname=%s user=%s password=%s",
            $this->_config_fields['host'],
            $this->_config_fields['port'],
            $this->_config_fields['db'],
            $this->_config_fields['user'],
            $this->_config_fields['pwd']
        );

        $cnx = pg_connect($connStr);

        if ($cnx) {
            // No es necesario setear charset con pg_connect, puede hacerse en la consulta si es necesario
            $this->_cnx = $cnx;
            return $cnx;
        } else {
            throw new Exception('Unable to connect to PostgreSQL database', 500);
        }
    }

    public function query($sql)
    {
        $this->freeResult();

        $res = $this->last_result = pg_query($this->connect(), $sql);
        if ($res) {
            // Si $res es booleano verdadero (como TRUE), resulta en true
            // En PostgreSQL, pg_query devuelve un recurso o FALSE en error,
            // para obtener todos los resultados se usa pg_fetch_all
            $ret = (is_bool($res)) ? true : pg_fetch_all($res);
        } else {
            // Obtener último error
            $err = pg_last_error($this->_cnx);
            if ($err) {
                $this->setLastError(null, $err, null, $sql);
            }
            $ret = false;
        }

        return $ret;
    }

    /**
     * Executes a statement and returns bool 
     * 
     * @param String $sql
     * @return Bool
     */    
    public function statement($sql)
    {
        $res = pg_query($this->connect(), $sql);
        if ($res) {
            $ret = (is_bool($res));
            return $ret;
        }
        return $res;
    }

    /**
     * Returns number of rows
     *
     * @param String $sql
     * @return Integer
     */
    public function howMany($sql)
    {
        $res = pg_query($this->connect(), $sql);
        if ($res) {
            $rows = pg_num_rows($res);
        } else {
            $rows = 0;
        }
        return $rows;
    }

    /**
     * Check if query returns at least one row
     * 
     * @param String $sql
     * @return Bool
     */
    public function exists($sql): Bool 
    {
        $sql = "SELECT EXISTS($sql) as e";
        $r = $this->query($sql);
        if ($r && isset($r[0])) 
            $e = $r[0]['e'];
        else 
            $e = 0;

        return ($e === 1);
    }

    /**
     * Inserts a record and returns the corresponding Id if the $return_fields are provided
     * 
     * @param String $sql
     * @return Int 
     */
    public function insert($sql, Array $return_fields = [])
    {
        $c = $this->connect();
        $sql = trim($sql);

        // postgres accepts returning in INSERT, UPDATE and DELETE
        if (!preg_match('/^INSERT\s|^UPDATE\s|^DELETE\s.+$/im', $sql)) 
            throw new Exception('Insert requires an INSERT, UPDATE OR DELETE SQL statement', 500);

        // check that the $sql has returning statement and $return_fields were provided
        $has_returning = (preg_match('/\bRETURNING\b/i', $sql));
        $has_return = (!empty($return_fields));
        if($has_return && !$has_returning){
            $returning = implode(",",$return_fields);
            if(substr($sql, -1,1) == ';') $sql = substr($sql, 0,-1);
            $sql .= " RETURNING $returning;";
        }

        $res = pg_query($c, $sql);

        if ($res) {
            $ar_id = pg_fetch_row($res);
            return $ar_id[0];
        } else {
            $this->setLastError(pg_last_error($c), null, null, $sql);
            return false;
        }
    }

    public function tableExists($table_name, $schema = null){
        $sql = "SELECT tablename 
            FROM pg_catalog.pg_tables 
            WHERE schemaname = '$schema' 
            AND tablename = '$table_name';";

        return $this->exists($sql);
    }

    public function viewExists($view_name, $schema = null){
        $sql = "SELECT table_name 
            FROM information_schema.views 
            WHERE table_schema = '$schema' 
            AND table_name = '$view_name';";        
        
        return $this->exists($sql);
    }

    public function procedureExists($procedure_name, $schema = null){
        $sql = "SELECT routine_name 
            FROM information_schema.routines 
            WHERE specific_schema = '$schema' 
            AND routine_type = 'PROCEDURE' 
            AND routine_name = '$procedure_name';";

        return $this->exists($sql);
    }
    
    public function functionExists($function_name, $schema = null){
        $sql = "SELECT routine_name 
            FROM information_schema.routines 
            WHERE specific_schema = '$schema' 
            AND routine_type = 'FUNCTION' 
            AND routine_name = '$function_name';";

        return $this->exists($sql);
    } 

    private function freeResult()
    {
        if($this->last_result) pg_free_result($this->last_result);
        $this->last_result = false;
    }

}