<?php

namespace App\Core;

class Log
{
    public static function URITracker($response)
    {
        $timestamp = $_SERVER['REQUEST_TIME'];
        $logfile = fopen('main.log', 'a+') or die('unable to open file');
        $logline = http_response_code() . ' ' .
            gmdate('d M Y H:i:s', $timestamp) . ' ' .
            $_SERVER['REMOTE_ADDR'] . ' ' .
            'Tried to ' .
            Request::method() . ' into ' .
            Request::uri() . ' API Responded with ' . $response . "\n";
        fwrite($logfile, $logline);
        fclose($logfile);
    }
}
