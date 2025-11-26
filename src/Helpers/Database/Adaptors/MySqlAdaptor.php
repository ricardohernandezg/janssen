<?php 

namespace Janssen\Helpers\Database\Adaptors;

use Janssen\Helpers\Database\Adaptor;
use Janssen\Helpers\Exception;
use PDO;
use PDOException;
use PDOStatement;

class MySqlAdaptor extends Adaptor
{

    use \Janssen\Traits\GenericSQLSyntax\GenericSelectSyntax;
    use \Janssen\Traits\GenericSQLSyntax\GenericWhereSyntax;
    use \Janssen\Traits\GenericSQLSyntax\GenericOrderBySyntax;

    protected $_config_fields = [
        'host'  => '',
        'user'  => '',
        'pwd'   => '',
        'db'    => '',
        'port'  => 3306
    ];

    private $last_result;

    /** 
     * Connects to database
     * 
     * @return PDO 
     */
    public function connect(): PDO
    {
        if ($this->isConnected())
            return $this->_cnx;
               
        $dsn = "mysql:host={$this->_config_fields['host']};dbname={$this->_config_fields['db']};port={$this->_config_fields['port']};charset=utf8mb4";

        try {
            $cnx = new PDO($dsn, $this->_config_fields['user'], $this->_config_fields['pwd'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,  
                    PDO::MYSQL_ATTR_FOUND_ROWS => true
                ]);
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

    /**
     * Check if query returns at least one row
     * 
     * @param String $sql
     * @return Bool
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

    public function tableExists($table_name, $schema = null){
        $sql = "SELECT TABLE_NAME 
            FROM information_schema.tables 
            WHERE table_schema = ? 
            AND TABLE_NAME = ?";
        
        return $this->exists($sql, [$schema, $table_name]);
    }

    public function viewExists($view_name, $schema = null){
        $sql = "SELECT TABLE_NAME 
            FROM information_schema.views 
            WHERE table_schema = ? 
            AND TABLE_NAME = ?";

        return $this->exists($sql, [$schema, $view_name]);
    }

    public function procedureExists($procedure_name, $schema = null){
        $sql = "SELECT ROUTINE_NAME 
            FROM information_schema.routines 
            WHERE routine_schema = ? 
            AND ROUTINE_TYPE = 'PROCEDURE'
            AND ROUTINE_NAME = ?";

        return $this->exists($sql, [$schema, $procedure_name]);
    }
    
    public function functionExists($function_name, $schema = null){
        $sql = "SELECT ROUTINE_NAME 
            FROM information_schema.routines 
            WHERE routine_schema = ? 
            AND ROUTINE_TYPE = 'FUNCTION'
            AND ROUTINE_NAME = ?";

        return $this->exists($sql, [$schema, $function_name]);
    } 

    public function query(string $sql, ?array $bindings = [])
    {
        $this->freeResult();

        try {
            $cnx = $this->connect();
            $stmt = $cnx->prepare($sql);
    
            self::bind($stmt, $bindings);
    
            $res = $stmt->execute();

            return ($res) ? $stmt->fetchAll() : false;     

        } catch (PDOException $e) {
            throw new Exception($e->getMessage(), 500);
        }
    }

    /**
     * Executes a statement and returns bool 
     * 
     * @param String $sql
     * @return Bool
     */
    public function statement(string $sql, ?array $bindings = [])
    {

        $this->freeResult();

        try {
            $cnx = $this->connect();
            $stmt = $cnx->prepare($sql);
    
            self::bind($stmt, $bindings);
    
            $res = $stmt->execute();
            
            if($res){
                $this->affected_rows = $stmt->rowCount();
                return true;

            }else return false;
            

        } catch (PDOException $e) {
            throw new Exception($e->getMessage(), 500);
        }        
    }

    
    /*
    private function flatSQL($parted_sql)
    {
        $sql = 'SELECT ' . (self::$distinct?'DISTINCT ':'') . $parted_sql['select'] . ' FROM ' . $parted_sql['from'];
        if(!empty(trim($parted_sql['where'])))
            $sql .= ' WHERE ' . trim($parted_sql['where']);
             
        if(!empty(trim($parted_sql['orderby'])))
            $sql .= ' ORDER BY ' . trim($parted_sql['orderby']);            

        if(!empty(trim($parted_sql['limit'])))
            $sql .= ' LIMIT ' . trim($parted_sql['limit']);
            
        return $sql;
    } 
    */   

    /**
     * Inserts a record and returns the corresponding Id
     * 
     * @param String $sql
     * @return Int 
     */
    public function insert(string $sql, ?array $bindings = [])
    {

        try {
            $cnx = $this->connect();
            $stmt = $cnx->prepare($sql);
    
            self::bind($stmt, $bindings);
    
            // execute para select preparados
            $res = $stmt->execute();
            
            $this->affected_rows = -1;
            if($res){
                $res2 = self::query("SELECT LAST_INSERT_ID();");
                if ($res2 && isset($res2[0]['LAST_INSERT_ID()'])) {
                    return $res2[0]['LAST_INSERT_ID()'];
                } else return false;
                

            }else return false;            

        } catch (PDOException $e) {
            throw new Exception($e->getMessage(), 500);
        }   
    }

    private function freeResult()
    {
        /*
        if($this->last_result && $this->last_result instanceof mysqli_result && mysqli_more_results($this->_cnx)){
            mysqli_free_result($this->last_result);
            mysqli_next_result($this->connect());
            $this->last_result = false;
        }
        */
    }

    public function setAutoFieldMapping($value = true)
    {
        $this->_map_return_fields = ($value == true) ? MYSQLI_ASSOC:MYSQLI_NUM;
        return $this;
    }

    public function translate(array $parted_sql, array $mapping = [])
    {
        $sql = $this->prepareSelect($parted_sql, $mapping);
        $sql .= " FROM " . $parted_sql['from'];
        if($parted_sql['where']){
            $sql .= " WHERE " . $this->flatWhere($parted_sql['where']);
        }

        if(!empty($parted_sql['orderby'])){
            $sql .= $this->prepareOrderby($parted_sql);
        }

        if($parted_sql['limit'] >= 0){
            $sql .= " LIMIT " . $parted_sql['limit'];
        }
        
        if($parted_sql['offset'] >= 0){
            $sql .= " OFFSET " . $parted_sql['offset'];
        }

        return $sql . ';';
    }

    /**
     * Prepare the bindings 
     */
    private static function bind(PDOStatement &$stmt, array $bindings = [])
    {
        // itera los bindings
        $j = 1;
        foreach ($bindings as $v){
            $stmt->bindValue($j, $v, self::determineType($v));
            $j++;
        }
    }
}