<?php

class controller
{
    public $DB, $IP, $data, $_req;
    public function __construct()
    {
        $this->IP = IP;
        $this->DB = new database();
    }
    public function view($arr = [])
    {
        $this->data['style'] = $this->data['style'] ?? [];
        $this->data['script'] = $this->data['script'] ?? [];
        $this->data['description'] = $arr['description'] ?? SITE_NAME;
        $this->data['keywords'] = $arr['keywords'] ?? SITE_NAME;
        $this->data['page'] = $arr['page'] ?? (debug_backtrace())[1]['function'];
        $this->data['view'] = strtolower($arr['view'] ?? substr(get_class($this), strrpos(get_class($this), '\\') + 1));

        return array_merge($this->data, $arr);
    }
    public function set($z)
    {
        foreach ($z as $v) {
            $variable = is_array($v) ? array_shift($v) : $v;
            $val = isset($_POST[$variable]) ? trim(htmlspecialchars($_POST[$variable])) : '';
            if (is_array($v))
                foreach ($v as $fu)
                    if (is_array($fu))
                        foreach ($fu as $fc) $val = call_user_func_array($fc, [$val]);
                    else
                        $val = call_user_func_array($fu, [$val]);
            $this->_req[clean($variable)] = $this->DB->escape($val);
        }
    }
    public function setArray($z)
    {
        foreach ($z as $v)
            $this->_req[$v] = isset($_POST[$v]) ? $v : '';
    }
    public function setFiles($z)
    {
        foreach ($z as $v)
            $this->_req[$v] = $_FILES[$v] ?? [];
    }
    public function json($data, $t = false)
    {
        header('Content-Type: application/json');
        if ($t) die(json_encode($data));
        $output = ['message' => $data, "error" => false];
        if (is_array($data))
            $output = isset($data['success']) ? ['message' => $data["success"], "error" => true] : ['message' => $data[0], 'field' => $data[1], "error" => false];
        // die(json_encode($output));


        $output['error'] ? HTTPStatus(200) : HTTPStatus(202);
        unset($output['error']);

        die(json_encode($output));
    }
    public function check($arr = [])
    {
        $r = (object) $this->_req;
        $r->_errors = [];
        foreach ($arr as $v => $s)
            if ((isset($this->_req[$v]) && !empty($this->_req[$v]) ? $this->_req[$v] : false) == false) {
                $r->_errors[] = $s;
                $r->_fields[] = $v;
            }
        return $r;
    }

    public function model($model)
    {
        $run = '\\model\\' . $model;
        return new $run();
    }
}
