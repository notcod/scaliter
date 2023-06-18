<?php

namespace scaliter;

class database
{
    private static $CONNE = null;
    private static $dumpe = null;
    private static $TABLE = null;
    private static $WHERE = null;
    private static $ERROR = null;

    public static function connection()
    {
        self::$CONNE = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if (self::$CONNE->connect_error)
            die("Connection failed: " . self::$CONNE->connect_error);

        self::$CONNE->set_charset("utf8mb4");
    }
    public static function query(string $SQL)
    {
        if(self::$CONNE == null) self::connection();

        if (self::$dumpe) die($SQL);

        self::$TABLE = null;
        self::$WHERE = null;
        self::$ERROR = null;
        self::$dumpe = null;

        try {
            $result = self::$CONNE->query(trim($SQL));
        } catch (\Exception $e) {
            die($e->getMessage() . "<br><i>[$SQL]</i>");
        }

        if (strpos($SQL, 'INSERT INTO') !== false)
            return self::$CONNE->insert_id;

        if (strpos($SQL, 'UPDATE') !== false)
            return self::$CONNE->affected_rows;

        return $result;
    }
    public static function escape(string $STRING)
    {
        return self::$CONNE->real_escape_string($STRING);
    }
    public static function dump()
    {
        self::$dumpe = true;
        return new self;
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
    private static function isWhere()
    {
        return empty(self::$WHERE) || self::$WHERE == null ? '' : 'WHERE ' . self::$WHERE;
    }
    private static function buildWhere(string $indicator)
    {
        return empty(self::$WHERE) || self::$WHERE == null ? '' : self::$WHERE . " $indicator ";
    }
    private static function makeWhere(array $WHERE, string $symbol, bool $quotes, bool $brackets, string $indicator, string $pre_indicator)
    {
        self::$WHERE = self::buildWhere($pre_indicator);

        $makeWhere = '';
        foreach ($WHERE as $key => $val)
            $makeWhere .= $quotes ? "$key $symbol '$val' $indicator " : "$key $symbol $val $indicator ";
        $makeWhere = substr($makeWhere, 0, -5);
        self::$WHERE .= $brackets ? "($makeWhere)" : $makeWhere;
        return new self;
    }
    public static function whereStr(string $WHERE)
    {
        self::$WHERE .= $WHERE;
        return new self;
    }
    public static function where(array $WHERE, bool $quotes = true, bool $brackets = false, string $indicator = 'AND', string $pre_indicator = 'AND')
    {
        return self::makeWhere($WHERE, '=', $quotes, $brackets, $indicator, $pre_indicator);
    }
    public static function whereOver(array $WHERE, bool $quotes = false, bool $brackets = false, string $indicator = 'AND', string $pre_indicator = 'AND')
    {
        return self::makeWhere($WHERE, '>', $quotes, $brackets, $indicator, $pre_indicator);
    }
    public static function whereUnder(array $WHERE, bool $quotes = false, bool $brackets = false, string $indicator = 'AND', string $pre_indicator = 'AND')
    {
        return self::makeWhere($WHERE, '<', $quotes, $brackets, $indicator, $pre_indicator);
    }
    public static function whereNot(array $WHERE, bool $quotes = false, bool $brackets = false, string $indicator = 'AND', string $pre_indicator = 'AND')
    {
        return self::makeWhere($WHERE, '<>', $quotes, $brackets, $indicator, $pre_indicator);
    }
    public static function select(array $SELECT = [])
    {
        $TABLE = self::$TABLE;
        $WHERE = self::isWhere();

        $SELECT = count($SELECT) ? implode(', ', $SELECT) : '*';

        return (new self)->query("SELECT $SELECT FROM $TABLE $WHERE")->fetch_all(MYSQLI_ASSOC);
    }
    public static function insert(array $INSERT)
    {
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
        $WHERE = self::isWhere();

        return (new self)->query("DELETE FROM $TABLE $WHERE");
    }
    public static function update(array $UPDATE = [])
    {
        $TABLE = self::$TABLE;
        $WHERE = self::isWhere();

        $SET = '';
        foreach ($UPDATE as $KEY => $VAL)
            $SET .= "$KEY = '$VAL', ";

        $SET = substr($SET, 0, -2);

        return (new self)->query("UPDATE $TABLE SET $SET $WHERE");
    }
    public static function fetch(string|array $FETCH)
    {
        $TABLE = self::$TABLE;
        $WHERE = self::isWhere();

        if (is_array($FETCH)) $FETCH = implode(', ', $FETCH);

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
        return (new self)->get("count($COUNT)");
    }
    public static function confirm($RESULT = true)
    {
        return (new self)->count() != $RESULT ? ['error' => self::$ERROR] : true;
    }
}
