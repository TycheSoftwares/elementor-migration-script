<?php

class Database
{
    public $error = "";
    public $errno = 0;
    public $isConnectedServer = false;
    public $isConnectedDatabase = false;
    public $show_error = "";
    public $sql;
    protected $affected_rows = 0;
    protected $query_counter = 0;
    protected $connection = 0;
    protected $query = 0;
    protected $query_show;
    private $server = "";
    private $user = "";
    private $pass = "";
    private $database = "";
    public $err;
    public $error_desc;

    /**
     * Database::__construct()
     *
     * @param mixed $server
     * @param mixed $user
     * @param mixed $pass
     * @param mixed $database
     * @return
     */
    function __construct($server, $user, $pass, $database)
    {
        $this->server = $server;
        $this->user = $user;
        $this->pass = $pass;
        $this->database = $database;
        $this->show_error = false;
    }

    /**
     * Database::connect()
     * Connect and select database using vars above
     * @return
     */
    public function connect()
    {

        $status = false;
        $this->connection = $this->connect_db($this->server, $this->user, $this->pass);

        if (!$this->connection) {
            $this->error("<div style='text-align:center'>"
                . "<span style='padding: 5px; border: 1px solid #999; background-color:#EFEFEF;"
                . "font-family: Verdana; font-size: 11px; margin-left:auto; margin-right:auto'>"
                . "<b>Database Error:</b>Connection to Database " . $this->database . " Failed</span></div>");
        } else $this->isConnectedServer = true;

        if (!$this->select_db($this->database, $this->connection)) {
            $this->error("<div style='text-align:center'>"
                . "<span style='padding: 5px; border: 1px solid #999; background-color: #EFEFEF;"
                . "font-family: Verdana; font-size: 11px; margin-left:auto; margin-right:auto'>"
                . "<b>Database Error:</b>mySQL database (" . $this->database . ")cannot be used</span></div>");
        } else $this->isConnectedDatabase = true;

        //mysqli_character_set_name($this->connection);
        mysqli_set_charset($this->connection, "utf8");
        //$this->eQuery("SET NAMES 'utf8'", $this->connection);
        //$this->eQuery("SET CHARACTER SET 'utf8'", $this->connection);
        //$this->eQuery("SET CHARACTER_SET_CONNECTION=utf8", $this->connection);
        //$this->eQuery("SET SQL_MODE = ''", $this->connection);

        unset($this->pass);
        if ($this->isConnectedServer && $this->isConnectedDatabase) $status = true;
        return $status;
    }

    /**
     * Database::connect_db()
     *
     * @param mixed $server
     * @param mixed $user
     * @param mixed $pass
     * @return
     */

    private function connect_db($server, $user, $pass)
    {
        return mysqli_connect($server, $user, $pass);
    }

    /**
     * Database::error()
     * Output error message
     * @param mixed $msg
     * @return
     */
    public function error($msg = '')
    {
        if (!is_resource($this->connection)) {
            $this->error_desc = mysqli_error($this->connection);
            $this->error_no = mysqli_errno($this->connection);
        } else {
            $this->error_desc = mysqli_error($this->connection);
            $this->error_no = mysqli_errno($this->connection);
        }

        $the_error = "<div style=\"background-color:#FFF; border: 3px solid #999; padding:10px\">";
        $the_error .= "<b>mySQL WARNING!</b><br />";
        $the_error .= "DB Error: $msg <br /> More Information: <br />";
        $the_error .= "<ul>";
        $the_error .= "<li> Mysql Error : " . $this->error_no . "</li>";
        $the_error .= "<li> Mysql Error no # : " . $this->error_desc . "</li>";
        $the_error .= "<li> Date : " . date("F j, Y, g:i a") . "</li>";
        $the_error .= "<li> Referer: " . isset($_SERVER['HTTP_REFERER']) . "</li>";
        $the_error .= "<li> Script: " . $_SERVER['REQUEST_URI'] . "</li>";
        $the_error .= '</ul>';
        $the_error .= '</div>';
        $this->err = "<strong>DATABASE QUERY ERROR</strong><br/>" . $msg . "<br/><strong>DESCRIPTION</strong> " . $this->error_desc . "<br/><strong>QUERY</strong> " . $this->sql;
        if ($this->show_error) echo $the_error;
    }

    /**
     * Database::eQuery()
     * Executes SQL query to an open connection
     * @param mixed $sql
     * @return (query_id)
     */

    public function inserError() {

    }

    public function eQuery($sql)
    {
        if (trim($sql != "")) {
            $this->query_counter ++;
            $this->query_show .= stripslashes($sql) . "<hr size='1' />";
            $this->query = mysqli_query($this->connection, $sql);

            $this->last_query = $sql . '<br />';
        }
        $this->sql = $sql;

        if (!$this->query)
            $this->error("mySQL Error on Query : " . $sql);

        return $this->query;

    }

    /**
     * Database::escape_()
     *
     * @param mixed $string
     * @return Database::quote()
     */
    public function escape_($string)
    {
        return mysqli_real_escape_string($this->connection, $string);
    }

    /**
     * Database::select_db()
     *
     * @param mixed $database
     * @param mixed $connection
     * @return
     */
    private function select_db($database, $connection)
    {
        return mysqli_select_db($connection, $database);
    }

    public function displayError($status)
    {
        if ($status) $this->show_error = true;
        else $this->show_error = false;
    }

    /**
     * Database::first()
     * Fetches the first row only, frees resultset
     * @param mixed $string
     * @param bool $type
     * @return array
     */
    public function first($string, $type = false)
    {
        $query = $this->eQuery($string);
        $record = $this->fetch($query, $type);
        $this->free($query);

        return $record;
    }

    /**
     * Database::free()
     * Frees the resultset
     * @param integer $query
     * @return query_id
     */
    private function free($query)
    {
        if ($query)
            $this->query = $query;

        return mysqli_free_result($this->query);
    }

    /**
     * Database::fetch()
     * Fetches and returns results one line at a time
     * @param integer $query
     * @param bool $type
     * @return array
     */
    public function fetchArray($query, $type = true)
    {
        if ($query)
            $this->query = $query;

        if (isset($this->query)) {
            $record = ($type) ? mysqli_fetch_array($this->query, MYSQLI_ASSOC) : mysqli_fetch_object($this->query);
        } else
            $this->error("Invalid query_id: <b>" . $this->query . "</b>. Records could not be fetched.");

        return $record;
    }

    /**
     * Database::fetch_all()
     * Returns all the results
     * @param mixed $sql
     * @param bool $type
     * @return assoc array
     */
    public function fetch_all($sql, $type = false)
    {
        $query = $this->eQuery($sql);
        $record = array();

        while ($row = $this->fetchArray($query)) :
            $record[] = $row;
        endwhile;

        $this->free($query);

        return $record;
    }

    /**
     * Database::insert()
     * Insert query with an array
     * @param mixed $table
     * @param mixed $data
     * @return id of inserted record, false if error
     */
    public function insert($table = null, $data)
    {
        if ($table === null or empty($data) or !is_array($data)) {
            $this->error("Invalid array for table: <b>" . $table . "</b>.");
            return false;
        }
        $q = "INSERT INTO `" . $table . "` ";
        $v = '';
        $k = '';

        foreach ($data as $key => $val) :
            $k .= "`$key`, ";
            if (strtolower($val) == 'null')
                $v .= "NULL, ";
            elseif (strtolower($val) == 'now()')
                $v .= "NOW(), ";
            else
                $v .= "'" . $this->escape($val) . "', ";
        endforeach;

        $q .= "(" . rtrim($k, ', ') . ") VALUES (" . rtrim($v, ', ') . ");";

        if ($this->eQuery($q)) {
            return $this->insertid();
        } else
            return false;
    }

    /**
     * Database::escape()
     * @param mixed $string
     * @return
     */
    public function escape($string)
    {
        if (is_array($string)) {
            foreach ($string as $key => $value) :
                $string[$key] = $this->escape_($value);
            endforeach;
        } else
            $string = $this->escape_($string);

        return $string;
    }

    /**
     * Database::insert_id()
     * Returns last inserted ID
     * @param integer $query
     * @return
     */
    public function insertid()
    {
        return mysqli_insert_id($this->connection);
    }

    /**
     * Database::update()
     * Update query with an array
     * @param mixed $table
     * @param mixed $data
     * @param string $where
     * @return query_id
     */
    public function update($table = null, $data, $where = '1')
    {
        if ($table === null or empty($data) or !is_array($data)) {
            $this->error("Invalid array for table: <b>" . $table . "</b>.");
            return false;
        }

        $q = "UPDATE `" . $table . "` SET ";
        foreach ($data as $key => $val) :
            if (strtolower($val) == 'null')
                $q .= "`$key` = NULL, ";
            elseif (strtolower($val) == 'now()')
                $q .= "`$key` = NOW(), ";
            elseif (strtolower($val) == 'default()')
                $q .= "`$key` = DEFAULT($val), ";
            elseif (preg_match("/^inc\((\-?[\d\.]+)\)$/i", $val, $m))
                $q .= "`$key` = `$key` + $m[1], ";
            else
                $q .= "`$key`='" . $this->escape($val) . "', ";
        endforeach;
        $q = rtrim($q, ', ') . ' WHERE ' . $where . ';';

        return $this->eQuery($q);
    }

    /**
     * Database::delete()
     * Delete records
     * @param mixed $table
     * @param string $where
     * @return
     */
    public function delete($table, $where = '')
    {
        $q = !$where ? 'DELETE FROM ' . $table : 'DELETE FROM ' . $table . ' WHERE ' . $where;
        return $this->eQuery($q);
    }

    /**
     * Database::affected()
     * Returns the number of affected rows
     * @param integer $query
     * @return
     */
    public function affected()
    {
        return mysqli_affected_rows($this->connection);
    }

    /**
     * Database::numrows()
     *
     * @param integer $query
     * @return
     */
    public function numRows($query)
    {
        if ($query)
            $this->query = $query;

        $this->num_rows = mysqli_num_rows($this->query);
        return $this->num_rows;
    }

    /**
     * Database::fetchrow()
     * Fetches one row of data
     * @param integer $query
     * @return fetched row
     */
    public function fetchrow($query)
    {
        if ($query)
            $this->query = $query;

        $this->fetch_row = mysqli_fetch_row($this->query);
        return $this->fetch_row;
    }

    /**
     * Database::numfields()
     *
     * @param integer $query
     * @return
     */
    public function numfields($query)
    {
        if ($query)
            $this->query = $query;

        $this->num_fields = mysqli_num_fields($this->query);
        return $this->num_fields;
    }

    /**
     * Database::show()
     *
     * @return
     */
    public function show()
    {
        return "<br /><br /><b> Debug Mode - All Queries :</b><hr size='1' /> " . $this->query_show . "<br />";
    }

    /**
     * Database::pre()
     *
     * @return
     */
    public function pre($arr)
    {
        print '<pre>' . @print_r($arr, true) . '</pre>';
    }

    /**
     * Database::getDB()
     *
     * @return
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Database::getServer()
     *
     * @return
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * Database::getLink()
     *
     * @return
     */
    public function getConnection()
    {
        return $this->connection;
    }

    public function close()
    {
        return mysqli_close($this->connection);
    }

    public function resetCounter($tablename)
    {
        return mysqli_data_seek($tablename, 0);
    }

}