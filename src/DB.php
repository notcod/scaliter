<?php

class DB
{
    private static $CONN = null;

    private static $TABLE = null;
    private static $WHERE = null;

    public static function connection()
    {
        self::$CONN = new \mysqli(DB['HOST'], DB['USER'], DB['PASS'], DB['NAME']);

        if (self::$CONN->connect_error)
            die("Connection failed: " . self::$CONN->connect_error);

        self::$CONN->set_charset("utf8mb4");
    }

    public static function query($SQL)
    {
        self::$TABLE = self::$WHERE = null;

        $SQL = trim($SQL);

        $DB = self::$CONN;

        $RESULT = $DB->query($SQL) or die("$DB->error => [$SQL]");

        if ($DB->error)
            throw new \Exception("$DB->error => [$SQL]");

        if (strpos(strtoupper($SQL), 'INSERT INTO') !== false)
            $RESULT = $DB->insert_id;

        elseif (strpos(strtoupper($SQL), 'UPDATE') !== false)
            $RESULT = $DB->affected_rows;

        return $RESULT;
    }
    public static function table(string $TABLE)
    {
        self::$TABLE = $TABLE;
        return new self();
    }
    public static function where(array $WHERE)
    {
        self::$WHERE = $WHERE ? 'WHERE ' : '';

        foreach ($WHERE as $key => $val)
            self::$WHERE .= "$key = '$val' AND ";

        self::$WHERE = substr(self::$WHERE, 0, -5);

        return new self();
    }
    public static function select(array|string $SELECT)
    {
        //if empty throw
        $TABLE = self::$TABLE;
        $WHERE = self::$WHERE;

        $SELECT = is_array($SELECT) ? implode(', ', $SELECT) : '*';

        return (new self)->query("SELECT $SELECT FROM $TABLE $WHERE");
    }
    public static function insert($INSERT = [])
    {
        //if empty throw
        $TABLE = self::$TABLE;

        $KEY = implode(', ', array_keys($INSERT));
        $VAL = implode(', ', array_values($INSERT));

        return (new self)->query("INSERT INTO $TABLE ($KEY) VALUES ('$VAL')");
    }
    public static function delete()
    {
        $TABLE = self::$TABLE;
        $WHERE = self::$WHERE;

        return (new self)->query("DELETE FROM $TABLE $WHERE");
    }
    public static function update($update = [])
    {
        $TABLE = self::$TABLE;
        $WHERE = self::$WHERE;

        $SET = '';
        foreach ($update as $KEY => $VAL)
            $SET .= "$KEY = '$VAL', ";

        $SET = substr($SET, 0, -2);

        return (new self)->query("UPDATE $TABLE SET $SET $WHERE");
    }
    public static function count()
    {
        $TABLE = self::$TABLE;
        $WHERE = self::$WHERE;

        return (new self)->query("SELECT count(id) FROM $TABLE $WHERE");
    }
}
