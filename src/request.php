<?php

namespace scaliter;

class request
{
    public static $value, $field;

    public static function json($data)
    {
        header('Content-Type: application/json');
        $output = ['message' => $data, "error" => false];
        if (is_array($data))
            $output = isset($data['success']) ? ['message' => $data["success"], "error" => true] : ['message' => $data[0], 'field' => $data[1], "error" => false];

        $output['error'] ? HTTPStatus(200) : HTTPStatus(202);
        unset($output['error']);

        die(json_encode($output));
    }
    public static function post($field, $htmlspecialchars = true, $trim = true)
    {
        self::$field = $field;
        self::$value = isset($_POST[$field]) ? $_POST[$field] : '';

        if ($htmlspecialchars)
            self::$value = htmlspecialchars(self::$value);

        if ($trim)
            self::$value = trim(self::$value);

        return new self;
    }
    public static function fn($functions)
    {
        $fu = explode(',', $functions);
        foreach ($fu as $fn)
            self::$value = call_user_func_array(trim($fn), [self::$value]);
        return new self;
    }
    public static function error($error_response = '')
    {
        $value = self::$value;
        $field = self::$field;

        self::$value = self::$field = '';

        if (empty($error_response) || (isset($value) && !empty($value) && $value != ''))
            return $value;

        die(json_encode([
            'message' => $error_response,
            'field' => $field,
            'success' => false,
        ]));
    }
    public static function get()
    {
        return self::error();
    }
    public static function response($message, $field = '', $success = false)
    {
        die(json_encode([
            'message' => $message,
            'field' => $field,
            'success' => $success,
        ]));
    }
}
