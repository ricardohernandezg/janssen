<?php 

namespace Janssen\Helpers\Database\Adaptors;

use Janssen\Helpers\Database\Adaptor;
use Janssen\Helpers\Exception;
use mysqli_sql_exception;
class MySqlAdaptor extends Adaptor
{

    protected $_config_fields = [
        'host' => '',
        'user' => '',
        'pwd' => '',
        'db' => ''
    ];

    /** 
     * Connects to database
     */
    public function connect()
    {
        if($this->isConnected())
            return $this->_cnx;
        
        $cnx = mysqli_connect($this->_config_fields['host'], $this->_config_fields['user'], $this->_config_fields['pwd'], $this->_config_fields['db']);

        /**
         * @todo remove this query when in production!
         * mysqli_query($cnx, "SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
         */        
        if($cnx){
            mysqli_set_charset($cnx, "utf8");
            $this->_cnx = $cnx;
            return $cnx;
        }else
            throw new Exception('Unable to connect to database', 500);
        
    }    

    public function query($sql)
    {
        $res = mysqli_query($this->connect(), $sql);
        if ($res) {
            if (is_bool($res)) {
                $ret = true;
            } else {
                $ret = mysqli_fetch_all($res, $this->_map_return_fields);
                // check if we got an exception
                if(isset($ret[0]) && is_array($ret[0]) && key($ret[0]) === '@p2') // it's a database exception!!    
                    throw new Exception('Internal Database error', 500);
            }

        } else 
            $ret = false;
            
        
        return $ret;
        
    }

    public function statement($sql)
    {
        $res = mysqli_query($this->connect(), $sql);
        if ($res) {
            if (is_bool($res)) {
                $ret = true;
            } else {
                $ret = false;
            }
        return $ret;
        }
    }

    /**
     * Returns number of rows
     *
     * @param String $sql
     * @return Integer
     */
    public function howMany($sql)
    {
        $res = mysqli_query($this->connect(), $sql);
        if ($res) {
            $row = mysqli_num_rows($res);
        } else {
            $row = "0";
        }
        return $row;
    }

    
    public function insert($sql)
    {
        $c = $this->connect();
        $res = mysqli_query($c, $sql);
        if($res){
            $res2 = mysqli_query($c, 'SELECT LAST_INSERT_ID()');
            if($res2){
                $ar_id = mysqli_fetch_row($res2);
                return $ar_id[0];
            }else{
                $this->setLastError(mysqli_errno($c),mysqli_error($c), mysqli_sqlstate($c), $sql);
                return false;
            }
        }else{
            $this->setLastError(mysqli_errno($c),mysqli_error($c), mysqli_sqlstate($c), $sql);
            return false;
        }         
    }

}