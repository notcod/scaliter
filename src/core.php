<?php

class core
{
    public $data, $session;
    public function __construct($REQUEST = '', $rewrite = [])
    {
        \DB::connection();

        $this->session = $_COOKIE[session_name()] ?? 'undefined';
        $extension = pathinfo($REQUEST, PATHINFO_EXTENSION);
        if (array_key_exists($extension, STATIC_FILES)) exit($this->statics($REQUEST, $extension));

        if (PRODUCTION) ob_start('sanitize');

        define('CSRF', $this->session);

        $REQ = explode('/', $REQUEST);

        $directory = $this->isRequest() ? 'handler' : 'controller';
        $controller = 'home';
        $method = 'index';

        if (isset($REQ[0]) && !empty($REQ[0])) {
            $controller = array_shift($REQ);
            if (count($REQ)) $method = array_shift($REQ);
        }

        if (isset($rewrite['controller']) && array_key_exists($controller, $rewrite['controller']))
            $controller = $rewrite['controller'][$controller];

        if (isset($rewrite['method']) && array_key_exists($method, $rewrite['method']))
            $method = $rewrite['method'][$method];

        $controller = clean($controller);
        $method     = clean($method);

        $CLASS      = "\\$directory\\$controller";

        if (!class_exists($CLASS)) $this->NOT_FOUND('class not found');

        $CLASS = new $CLASS;

        if (!method_exists($CLASS, $method)) $this->NOT_FOUND('method not found => ' . $method);

        $this->data = call_user_func_array([$CLASS, $method], array_values($REQ));

        $this->confirm();

        $this->data['js'] = $this->asset(ASSETS, 'js', true);
        $this->data['css'] = $this->asset(ASSETS, 'css', true);
    }
    function manifest($list, $type)
    {
        foreach ($list as $inc)
            $manifest[] = external($inc) ? exist($inc) : cache($inc);

        $manifest_content = implode("\n", $manifest);

        $hash = md5($manifest_content);
        $file = SERVER['PUB'] . '/.manifest/' . $hash;
        if (!isReadable($file)) {
            $manifest_file = fopen($file, "w") or die("Unable to open file!");
            fwrite($manifest_file, $manifest_content);
            fclose($manifest_file);
        }
        print $this->_include($type, $hash . '.manifest.' . $type) . PHP_EOL;
    }
    function get($type = 'css' || 'js')
    {
        if (!isset($this->data[$type])) return;

        if (defined("ONEFILE")) {
            array_walk($this->data[$type], function (&$val, $key) {
                $val = SERVER['PUB'] . $val;
            });
            $uglify = new \NodejsPhpFallback\Uglify($this->data[$type]);
            print $type == 'css' ? '<style>' . $uglify . '</style>' : '<script>' . $uglify . '</script>';
            return;
        }

        if (PRODUCTION) return $this->manifest($this->data[$type], $type);

        foreach ($this->data[$type] as $inc) {
            $inc = external($inc) ? exist($inc) : cache($inc);
            if ($inc != false) print $this->_include($type, $inc) . PHP_EOL;
        }
    }
    public function asset($array, $extension = 'css' | 'js', $return = false)
    {
        $this->confirm();

        $data = $this->data;
        $extension = $extension == 'css' ? 'css' : 'js';
        $type = $extension == 'css' ? 'style' : 'script';
        $data[$type][] = $data['view'];
        $includes = [];
        if (isset($data[$type]) && count($data[$type]))
            foreach ($data[$type] as $asset)
                if (is_array($array[$extension]) && array_key_exists($asset, $array[$extension]))
                    $includes = array_merge($array[$extension][$asset], $includes);
        if (isset($data['view']) && isset($data['page'])) {
            $includes[] = "/$extension/$data[view].$extension";
            $includes[] = "/$extension/$data[view]/$data[page].$extension";
        }

        $init_assets = $array[$extension]['init'] ?? [];
        if (count($init_assets))
            foreach ($init_assets as $init)
                $includes[] = $init;

        $includes = array_unique($includes);
        if ($return) return $includes;
        //////////////////////////////////////////////////
        foreach ($includes as $inc) {
            $inc = external($inc) ? exist($inc) : cache($inc);
            // if ($inc != false) echo $extension == 'css' ? '<link rel="stylesheet" href="' . $inc . '">' . PHP_EOL : '<script defer src="' . $inc . '"></script>' . PHP_EOL;
            // if ($inc != false) echo $extension == 'css' ? "<link rel='stylesheet' href='$inc'>'" . PHP_EOL : "<script defer src='$inc'></script>" . PHP_EOL;
            if ($inc != false) print $this->_include($extension, $inc) . PHP_EOL;
        }
    }
    private function _include($e, $inc)
    {
        if ($e == 'css') return "<link rel='stylesheet' href='$inc'>";
        return "<script defer src='$inc'></script>";
    }
    public function content()
    {
        $this->confirm();

        $data = $this->data;
        $FILE = SERVER['APP'] . "/views/$data[view].php";
        if (isReadable($FILE)) require_once($FILE);
    }
    private function confirm()
    {
        if (
            !isset($this->data['view']) ||
            !isset($this->data['page']) ||
            empty($this->data['view']) ||
            empty($this->data['page'])
        ) $this->NOT_FOUND('page/view not found');
    }
    private function isRequest()
    {
        // var_dump($this->session);
        // var_dump(CSRF);
        // exit;

        // if (strpos($this->getHeader('ACCEPT'), 'application/json') === false) return false;

        // if ($this->session == CSRF) return true;

        // return $this->NOT_FOUND('Token is not valid!');

        return ($this->session == CSRF &&
            strpos($this->getHeader('ACCEPT'), 'application/json') !== false
        );

        // return ($this->getHeader('INDICUM') == CSRF &&
        //     strpos($this->getHeader('ACCEPT'), 'application/json') !== false
        // );
    }
    private function getHeader($header)
    {
        $HEADER = 'HTTP_' . strtoupper($header);
        return isset($_SERVER[$HEADER]) ? $_SERVER[$HEADER] : '';
    }
    private function NOT_FOUND($content = '')
    {
        HTTPStatus(404);
        if (strpos($this->getHeader('ACCEPT'), 'application/json') !== false) {
            header('Content-Type: application/json');
            $output = PRODUCTION ? 'Request is not valid!' : 'Request is not valid => ' . $content;
            // die(json_encode(['message' => CSRF.' '.$this->getHeader('INDICUM'). ':Request is not valid => ' . $content, 'error' => false]));
        } else
            $output = PRODUCTION ? 'Page not found!' : 'Page not found! => ' . $content;

        echo $output;
        exit;
    }

    static function email($recipient, $subject, $message)
    {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host   = EMAIL['HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = EMAIL['USER'];
            $mail->Password   = EMAIL['PASS'];
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port   = 587;
            $mail->setFrom(EMAIL['USER'], EMAIL['NAME']);
            $mail->addReplyTo(EMAIL['USER'], EMAIL['NAME']);
            $mail->addAddress($recipient);
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->AltBody = \Soundasleep\Html2Text::convert($mail->Body, ['ignore_errors' => true]);
            $mail->addCustomHeader('List-Unsubscribe', '<' . SITE['SUPPORT'] . '>, <https://' . SITE['DOMAIN'] . '/?unsubscribe=' . $recipient . '>');
            $mail->XMailer = SITE['NAME'];
            return $mail->send();
        } catch (\Exception $e) {
            return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }
    private function statics($static, $extension)
    {

        $directory = SERVER . '/public';

        header('Content-Type: ' . STATIC_FILES[$extension]);

        $static = str_replace(['//', '../'], '/', $static);

        if (str_ends_with($static, '.manifest.js') || str_ends_with($static, '.manifest.css')) {
            $manifest = str_replace(['.manifest.js', '.manifest.css'], '', $static);

            $file = $directory . '/.manifest/' . $manifest;

            $manifest_file = fopen($file, "r") or die("Unable to open file!");
            $manifest_content = fread($manifest_file, filesize($file));
            fclose($manifest_file);

            $files = explode("\n", $manifest_content);

            $manifest_files = [];
            foreach ($files as $value)
                if (!is_null($value) && $value !== '') $manifest_files[] = $directory  . substr($value, 0, strpos($value, "?"));

            $uglify = new \NodejsPhpFallback\Uglify(
                $manifest_files
            );
            exit($uglify);
        }

        $file = $directory . '/' . $static;


        if (file_exists($file)) {
            if (!in_array($extension, MINIMIZE_FILES)) exit(file_get_contents($file));

            $uglify = new \NodejsPhpFallback\Uglify([
                $file
            ]);
            exit($uglify);
        }

        if (in_array($extension, ['ico', 'jpg', 'jpeg', 'png', 'gif'])) {
            $image = imagecreatetruecolor(16, 16);
            imagepng($image);
            exit;
        }

        if ($extension == 'zip') {
            $dumb_name = explode('/', $static);
            $dumb_name = end($dumb_name);
            $dumb_file = __DIR__ . '/dumb/dumb.zip';
            header("Content-Type: application/zip");
            header("Content-Transfer-Encoding: Binary");
            header("Content-Length: " . filesize($dumb_file));
            header("Content-Disposition: attachment; filename=\"" . basename($dumb_name) . "\"");
            readfile($dumb_file);
        }

        if ($extension == 'xml') {
            $dumb_file = __DIR__ . '/dumb/dumb.xml';
            readfile($dumb_file);
        }
        exit;
    }
}
