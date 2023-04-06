<?php

class statics
{
    public function __construct($static, $extension)
    {
        header('Content-Type: ' . STATIC_FILES[$extension]);

        $static = str_replace(['//', '../'], '/', $static);
        $file = SERVER['PUB'] . '/' . $static;

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
