<?php

/**
 * 
 */
if (!file_exists(dirname($_SERVER['SCRIPT_FILENAME']) . '/.htaccess')) {
    exit(file_put_contents(dirname($_SERVER['SCRIPT_FILENAME']) . '/.htaccess', "RewriteEngine on
    RewriteBase /
    
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule !(^public/) index.php
    RewriteRule ^public/(.*)?$ index.php?THEFOUNDATION_PUBLIC_STATIC_FILENAME=$1 [NC]"));
}

/**
 * 
 */
class vhost
{

    private static array $hosts = [];

    /**
     * 
     */
    public static function add(string $name, string $source)
    {
        self::$hosts[$name] = $source;
    }

    /**
     * 
     */
    public static function listen()
    {
        $http_parts = explode('/', trim($_SERVER['REDIRECT_URL'], '/'));

        if (!empty(self::$hosts[$http_parts[0]])) {
            $_SERVER['THEFOUNDATION_LISTEN_PREFIX'] = '/' . $http_parts[0] . '/';
            $_SERVER['THEFOUNDATION_SOURCE_DIRECTORY'] = self::$hosts[$http_parts[0]];

            if (empty($_GET['THEFOUNDATION_PUBLIC_STATIC_FILENAME']))
                exit(require_once $_SERVER['THEFOUNDATION_SOURCE_DIRECTORY'] . '/index.php');
            else {
                $filename = $_SERVER['THEFOUNDATION_SOURCE_DIRECTORY'] . '/public/' . $_GET['THEFOUNDATION_PUBLIC_STATIC_FILENAME'];
                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $mimetypes = [
                    'txt' => 'text/plain',
                    'htm' => 'text/html',
                    'html' => 'text/html',
                    'php' => 'text/html',
                    'css' => 'text/css',
                    'js' => 'application/javascript',
                    'json' => 'application/json',
                    'xml' => 'application/xml',
                    'swf' => 'application/x-shockwave-flash',
                    'flv' => 'video/x-flv',
                    // images
                    'png' => 'image/png',
                    'jpe' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'jpg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'bmp' => 'image/bmp',
                    'ico' => 'image/vnd.microsoft.icon',
                    'tiff' => 'image/tiff',
                    'tif' => 'image/tiff',
                    'svg' => 'image/svg+xml',
                    'svgz' => 'image/svg+xml',
                    // archives
                    'zip' => 'application/zip',
                    'rar' => 'application/x-rar-compressed',
                    'exe' => 'application/x-msdownload',
                    'msi' => 'application/x-msdownload',
                    'cab' => 'application/vnd.ms-cab-compressed',
                    // audio/video
                    'mp3' => 'audio/mpeg',
                    'qt' => 'video/quicktime',
                    'mov' => 'video/quicktime',
                    // adobe
                    'pdf' => 'application/pdf',
                    'psd' => 'image/vnd.adobe.photoshop',
                    'ai' => 'application/postscript',
                    'eps' => 'application/postscript',
                    'ps' => 'application/postscript',
                    // ms office
                    'doc' => 'application/msword',
                    'rtf' => 'application/rtf',
                    'xls' => 'application/vnd.ms-excel',
                    'ppt' => 'application/vnd.ms-powerpoint',
                    // open office
                    'odt' => 'application/vnd.oasis.opendocument.text',
                    'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
                ];

                if (file_exists($filename)) {
                    if ($mimetypes[$extension] ?? false)
                        header('Content-Type: ' . $mimetypes[$extension]);

                    header('Content-Length: ' . filesize($filename));
                    header('Pragma: public');
                    header('Cache-Control: max-age=604800, must-revalidate');
                    exit(readfile($filename));
                }
            }
        }

        exit(header('HTTP/1.1 404 Not Found'));
    }
}
