<?php

class statics
{
    public function __construct($static, $extension)
    {
        header('Content-Type: ' . STATIC_FILES[$extension]);

        $static = str_replace(['//', '../'], '/', $static);

        if (str_ends_with($static, '.manifest.js') || str_ends_with($static, '.manifest.css')) {
            $manifest = str_replace(['.manifest.js', '.manifest.css'], '', $static);

            $file = SERVER['PUB'] . '/.manifest/' . $manifest;

            $manifest_file = fopen($file, "r") or die("Unable to open file!");
            $manifest_content = fread($manifest_file, filesize($file));
            fclose($manifest_file);

            $files = explode("\n", $manifest_content);

            $manifest_files = [];
            foreach ($files as $value)
                if (!is_null($value) && $value !== '') $manifest_files[] = SERVER['PUB']  . substr($value, 0, strpos($value, "?"));

            $uglify = new \NodejsPhpFallback\Uglify(
                $manifest_files
            );
            exit($uglify);
        }

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
