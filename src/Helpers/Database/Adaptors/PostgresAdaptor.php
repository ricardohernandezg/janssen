<?php 

namespace Janssen\Helpers\Database\Adaptors;

use Janssen\Helpers\Database\Adaptor;
use Janssen\Helpers\Exception;
use PDO;
use PDOException;
use PDOStatement;

class PostgresAdaptor extends Adaptor
{

    use \Janssen\Traits\GenericSQLSyntax\GenericSelectSyntax;
    use \Janssen\Traits\GenericSQLSyntax\GenericWhereSyntax;
    use \Janssen\Traits\GenericSQLSyntax\GenericOrderBySyntax;

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
     * @return PDO
     */
    public function connect(): PDO
    {
        if ($this->isConnected())
            return $this->_cnx;

        $dsn = "pgsql:host={$this->_config_fields['host']};port={$this->_config_fields['port']};dbname={$this->_config_fields['db']};";

        try {
            $cnx = new PDO(
                $dsn,
                $this->_config_fields['user'],
                $this->_config_fields['pwd'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false
                ]
            );
            $this->_cnx = $cnx;
            return $cnx;
        } catch (PDOException $e) {
            $this->disconnect();
            throw new Exception("Unable to connect to database (" . $e->getMessage() . ")", 500);
        }

    }

    public function disconnect()
    {
        $this->_cnx = null;
    }

    public function query($sql, ?array $bindings = [])
    {
        $this->freeResult();

        try {
            $cnx = $this->connect();
            $stmt = $cnx->prepare($sql);

            $res = $stmt->execute($bindings);

            $this->last_result = $res;

            if ($res) {
                $ret = $stmt->fetchAll(PDO::FETCH_ASSOC);
                //$ret = !empty($ret) ? $ret : true;             
            } else {
                // Error en execute
                $err = $cnx->errorInfo()[2] ?? 'UNKNOWN_ERROR';
                $this->setLastError(null, $err, null, $sql);
                $ret = false;
            }

            return $ret;
        } catch (PDOException $e) {
            $this->setLastError(null, $e->getMessage(), null, $sql);
            return false;
        }
    }

    /**
     * Executes a statement and returns bool 
     * 
     * @param string $sql
     * @param ?array $bindings
     * @return bool
     */    
    public function statement(string $sql, ?array $bindings = [])
    {
        $res = $this->query($sql, $bindings);
        if ($res) {
            $ret = (is_bool($res));
            return $ret;
        }
        return $res;
    }

    /**
     * Check if query returns at least one row
     * 
     * @param string $sql
     * @return bool
     */
    public function exists(string $sql, ?array $bindings = []): Bool 
    {
        $sql = "SELECT EXISTS($sql) as e";
        $r = $this->query($sql, $bindings);
        if ($r && isset($r[0])) 
            $e = $r[0]['e'];
        else 
            $e = 0;

        return ($e === 1);
    }

    /**
     * Inserts a record and returns the corresponding Id if the $return_fields are provided
     * 
     * @param string $sql
     * @param ?array $bindings
     * @return int 
     */
    public function insert(string $sql, ?array $bindings = [])
    {
        try {
            $cnx = $this->connect();
            $stmt = $cnx->prepare($sql);

            $res = $stmt->execute($bindings);

            $this->affected_rows = -1;
            if ($res) {
                $lastId = $cnx->lastInsertId(); 
                if ($lastId !== false && $lastId != '0') {
                    return $lastId;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } catch (PDOException $e) {
            throw new Exception($e->getMessage(), 500);
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
        //if($this->last_result) pg_free_result($this->last_result);
        $this->last_result = null;
    }

    public function setAutoFieldMapping($value = true)
    {
        $this->_map_return_fields = ($value == true)?PGSQL_ASSOC:PGSQL_NUM;
        return $this;
    }

    public function translate($parted_sql, array $mapping = [])
    {
        $sql = $this->prepareSelect($parted_sql, $mapping);
        $sql .= " FROM " . $parted_sql['from'];
        if ($parted_sql['where']) {
            $sql .= " WHERE " . $this->flatWhere($parted_sql['where']);
        }

        if (!empty($parted_sql['orderby'])) {
            $sql .= $this->prepareOrderby($parted_sql);
        }

        if ($parted_sql['limit'] >= 0) {
            $sql .= " LIMIT " . $parted_sql['limit'];
        }

        if ($parted_sql['offset'] >= 0) {
            $sql .= " OFFSET " . $parted_sql['offset'];
        }

        return $sql . ';';
    }


}