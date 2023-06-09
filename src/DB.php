<?php

class DB
{
    private static $CONN = null;

    private static $TABLE = null;
    private static $WHERE = null;
    private static $ERROR = null;

    public static function connection()
    {
        self::$CONN = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if (self::$CONN->connect_error)
            die("Connection failed: " . self::$CONN->connect_error);

        self::$CONN->set_charset("utf8mb4");
    }
    public static function query(string $SQL)
    {
        self::$TABLE = null; 
        self::$WHERE = null; 
        self::$ERROR = null;

        try {
            $result = self::$CONN->query($SQL);
        } catch (Exception $e) {
            die($e->getMessage() . "<br><i>[$SQL]</i>");
        }

        if (strpos($SQL, 'INSERT INTO') !== false)
            return self::$CONN->insert_id;

        if (strpos($SQL, 'UPDATE') !== false)
            return self::$CONN->affected_rows;

        return $result;
    }
    public static function esacpe(string $STRING)
    {
        return self::$CONN->real_escape_string($STRING);
    }
    public static function table(string $TABLE)
    {
        self::$TABLE = $TABLE;
        return new self;
    }
    public static function error(string|array $ERROR)
    {
        self::$ERROR = $ERROR;
        return new self;
    }
    public static function where(array $WHERE)
    {
        self::$WHERE = $WHERE ? 'WHERE ' : '';

        foreach ($WHERE as $key => $val)
            self::$WHERE .= "$key = '$val' AND ";

        self::$WHERE = substr(self::$WHERE, 0, -5);

        return new self;
    }
    public static function select(array|string $SELECT)
    {
        if (empty($SELECT)) die("SELECT IS EMPTY");

        $TABLE = self::$TABLE;
        $WHERE = self::$WHERE;

        $SELECT = is_array($SELECT) ? implode(', ', $SELECT) : '*';

        return (new self)->query("SELECT $SELECT FROM $TABLE $WHERE")->fetch_all(MYSQLI_ASSOC);
    }
    public static function insert($INSERT = [])
    {
        if (empty($INSERT)) die("SELECT IS EMPTY");

        array_walk($INSERT, function (&$value, $key) {
            $value = "'$value'";
        });

        $TABLE = self::$TABLE;

        $KEY = implode(', ', array_keys($INSERT));
        $VAL = implode(', ', array_values($INSERT));

        return (new self)->query("INSERT INTO $TABLE ($KEY) VALUES ($VAL)");
    }
    public static function delete()
    {
        $TABLE = self::$TABLE;
        $WHERE = self::$WHERE;

        return (new self)->query("DELETE FROM $TABLE $WHERE");
    }
    public static function update(array $UPDATE = [])
    {
        $TABLE = self::$TABLE;
        $WHERE = self::$WHERE;

        $SET = '';
        foreach ($UPDATE as $KEY => $VAL)
            $SET .= "$KEY = '$VAL', ";

        $SET = substr($SET, 0, -2);

        return (new self)->query("UPDATE $TABLE SET $SET $WHERE");
    }
    public static function fetch(string $FETCH)
    {
        $TABLE = self::$TABLE;
        $WHERE = self::$WHERE;

        return (new self)->query("SELECT $FETCH FROM $TABLE $WHERE LIMIT 1")->fetch_array(MYSQLI_ASSOC);
    }
    public static function get(string $GET, $default = 0)
    {
        $result = (new self)->fetch($GET);
        $result = is_array($result) ? end($result) : $default;
        return empty($result) ? $default : $result;
    }
    public static function count(string $COUNT = 'id')
    {
        return (new self)->fetch("count($COUNT)");
    }
    public static function confirm($RESULT = true)
    {
        return (new self)->count() != $RESULT ? ['error' => self::$ERROR] : true;
    }
    // public static function confirm(array $QUERIES, $INVERSE = false)
    // {
    //     $OUTPUT = [];
    //     $OUTPUT['errors'] = [];

    //     foreach ($QUERIES as $QUERY => $ERROR) {
    //     }
    // }
}
