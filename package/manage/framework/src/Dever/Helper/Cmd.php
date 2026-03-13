<?php namespace Dever\Helper;
use Dever;
class Cmd
{
    public static function run($api, $param = [], $app = false, $daemon = true)
    {
        if (strpos($api, 'http') !== false) {
            return self::shell('curl "' . $api . '"');
        }
        if (strpos($api, '/')) {
            $temp = explode('/', $api);
            $app = $temp[0];
            $api = str_replace($app . '/', '', $api);
        } else {
            $app = $app ? $app : DEVER_APP_NAME;
        }
        $app = Dever::project($app);
        if (isset($app['setup'])) {
            $app['path'] = $app['setup'];
        }
        if (strpos($app['path'], 'http') !== false) {
            return self::shell('curl "' . $app['path'] . $api . '"', $daemon);
        } else {
            $php = Dever::config('setting')['php'] ?? 'php';
            if (strpos($api, '?')) {
                $temp = explode('?', $api);
                if (isset($temp[1])) {
                    parse_str($temp[1], $send);
                    $param = array_merge($param, $send);
                }
                $api = $temp[0];
            }
            $param['l'] = $api;
            return self::shell($php . ' ' . $app['path'] . 'index.php \'' . Dever::json_encode($param) . '\'', $daemon);
        }
    }
    public static function kill($command)
    {
        self::shell("ps -ef | grep " . $command . " | grep -v grep | awk '{print $2}' | xargs kill -9");
    }
    public static function shell($shell, $daemon = true)
    {
        if ($daemon) {
            $shell .= ' 1>/dev/null 2>&1 &';
        }
        //$shell = escapeshellcmd($shell);
        exec($shell, $output, $state);
        return [$state == 0, $output];
    }
    public static function bash($cmd)
    {
        $file = Dever::path('shell/' . md5($cmd));
        $cmd = '#!/usr/bin/env sh ' . "\n" . $cmd;
        file_put_contents($file, $cmd);
        exec('chmod +x ' . $file);
        return $file;
    }
    public static function process($command, $count = false)
    {
        $shell = "ps -ef | grep " . $command . " | grep -v grep";
        if ($count) {
            $shell .= ' | wc -l';
        }
        $result = exec($shell, $output, $state);
        return $count ? $output[0] : $output;
    }
}