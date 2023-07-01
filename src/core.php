<?php

namespace scaliter;

class core
{
    public $data, $session;
    public function __construct($REQUEST = '', $rewrite = [])
    {

        $this->session = $_COOKIE[session_name()] ?? null;
        $extension = pathinfo($REQUEST, PATHINFO_EXTENSION);
        if ($this->static_files($extension)) exit($this->statics($REQUEST, $extension));

        if (getCons('PRODUCTION')) ob_start('sanitize');

        define('CSRF', $this->getHeaderCookie(session_name()));

        $REQ = explode('/', $REQUEST);

        // $directory = $this->isRequest() ? 'model' : 'controller';
        $directory = $this->typeOfRequest();
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

        $CALL = call_user_func_array([$CLASS, $method], array_values($REQ)) ?? [];

        if ($directory == 'model') is_array($CALL) ? error($CALL[0], $CALL[1] ?? '') : error($CALL);

        if ($directory == 'json') json_print($CALL);

        $this->data = $CLASS->data;

        $this->data['page'] = $method;
        $this->data['view'] = $controller;

        $this->confirm();

        $this->data['style'] = $this->data['style'] ?? [];
        $this->data['script'] = $this->data['script'] ?? [];

        $this->data['description'] = $CALL['description'] ?? getCons('SITE_NAME');
        $this->data['keywords'] = $CALL['keywords'] ?? getCons('SITE_NAME');

        $this->data = array_merge($this->data, $CALL);

        $this->data['js'] = $this->asset(getCons('ASSETS'), 'js', true);
        $this->data['css'] = $this->asset(getCons('ASSETS'), 'css', true);
    }
    private function typeOfRequest()
    {
        if (!$this->isRequest()) return 'controller';

        if ($this->session == CSRF && $this->getHeader('ACCEPT_RESPONSE') == 'bool/response') return 'model';

        if ($this->session == CSRF && $this->getHeader('ACCEPT_RESPONSE') == 'text/json') return 'json';

        $this->NOT_FOUND('Missmatched creditinails');
    }
    function manifest($list, $type)
    {
        foreach ($list as $inc)
            $manifest[] = external($inc) ? exist($inc) : cache($inc);

        $manifest_content = implode("\n", $manifest);

        $hash = md5($manifest_content);
        $file = getCons('SERVER') . '/public/.manifest/' . $hash;
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
                $val = getCons('SERVER') . '/public' . $val;
            });
            $uglify = new \NodejsPhpFallback\Uglify($this->data[$type]);
            print $type == 'css' ? '<style>' . $uglify . '</style>' : '<script>' . $uglify . '</script>';
            return;
        }

        if (getCons('PRODUCTION')) return $this->manifest($this->data[$type], $type);

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

        $init_assets = $array[$extension]['init'] ?? [];
        if (count($init_assets))
            foreach ($init_assets as $init)
                $includes[] = $init;

        if (isset($data[$type]) && count($data[$type]))
            foreach ($data[$type] as $asset)
                if (is_array($array[$extension]) && array_key_exists($asset, $array[$extension]))
                    $includes = array_merge($array[$extension][$asset], $includes);
        if (isset($data['view']) && isset($data['page'])) {
            $includes[] = "/$extension/$data[view].$extension";
            $includes[] = "/$extension/$data[view]/$data[page].$extension";
        }

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
        $FILE = getCons('SERVER') . "/views/$data[view].php";
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
    private function getHeaderCookie($lookFor)
    {
        $cookies = explode('; ', $this->getHeader('COOKIE'));
        foreach ($cookies as $cookie) {
            $try = explode('=', $cookie);
            if ($try[0] == $lookFor) return $try[1];
        }
        return null;
    }
    private function isRequest()
    {
        return (
            // $this->session == CSRF &&
            $this->session != null &&
            strpos($this->getHeader('ACCEPT'), 'application/json') !== false
        );
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
            $output = getCons('PRODUCTION') ? 'Request is not valid!' : 'Request is not valid => ' . $content;
            // die(json_encode(['message' => CSRF.' '.$this->getHeader('INDICUM'). ':Request is not valid => ' . $content, 'error' => false]));
        } else
            $output = getCons('PRODUCTION') ? 'Page not found!' : 'Page not found! => ' . $content;

        echo $output;
        exit;
    }

    static function email($recipient, $subject, $message)
    {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host   = getCons('EMAIL_HOST');
            $mail->SMTPAuth   = true;
            $mail->Username   = getCons('EMAIL_USER');
            $mail->Password   = getCons('EMAIL_PASS');
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port   = 587;
            $mail->setFrom(getCons('EMAIL_USER'), getCons('EMAIL_NAME'));
            $mail->addReplyTo(getCons('EMAIL_USER'), getCons('EMAIL_NAME'));
            $mail->addAddress($recipient);
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->AltBody = \Soundasleep\Html2Text::convert($mail->Body, ['ignore_errors' => true]);
            $mail->addCustomHeader('List-Unsubscribe', '<' . getCons('SITE_SUPPORT') . '>, <https://' . getCons('SITE_DOMAIN') . '/?unsubscribe=' . $recipient . '>');
            $mail->XMailer = getCons('SITE_NAME');
            return $mail->send();
        } catch (\Exception $e) {
            return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }
    private function static_files($ext){
        $STATIC_FILES = [
            'js'    => 'application/javascript',
            'pdf'   => 'application/pdf',
            'json'  => 'application/json',
            'zip'   => 'application/zip',
            'ttf'   => 'application/octet-stream',
            'woff'  => 'application/font-woff',
            'css'   => 'text/css',
            'scss'   => 'text/css',
            'xml'   => 'text/xml',
            'txt'   => 'text/plain',
            'ico'   => 'image/x-icon',
            'png'   => 'image/png',
            'jpeg'  => 'image/jpeg',
            'jpg'   => 'image/jpeg',
            'gif'   => 'image/gif',
            'svg'   => 'image/svg+xml',
            // eot, woff2, mp4
        ];
        return array_key_exists($ext, $STATIC_FILES) ? $STATIC_FILES[$ext] : false;
    }
    private function statics($static, $extension)
    {
        $directory = getCons('SERVER') . '/public';
        header('Content-Type: ' . $this->static_files($extension));
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
                // array_reverse($manifest_files)
            );
            exit($uglify);
        }
        $file = $directory . '/' . $static;
        if (file_exists($file)) {
            if (!in_array($extension, ['css', 'js'])) exit(file_get_contents($file));

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
