<?php namespace Dever\Helper;
use Dever;
class Lang
{
    private static $data = [];
    public static function get($key = 'host', $param = [])
    {
        $name = 'lang/' . Dever::config('setting')['lang'];
        if (empty(self::$data[$name])) {
            self::$data[$name] = Dever::get(Config::class)->get($name);
        }
        if (isset(self::$data[$name][$key])) {
            self::$data[$name][$key] = self::replace(self::$data[$name][$key], $param);
            return self::$data[$name][$key];
        }
        return $key;
    }
    private static function replace($value, &$param)
    {
        if ($param) {
            $param = self::param($param);
            foreach ($param as $k => $v) {
                self::set($value, $k, $v);
            }
        }
        return $value;
    }
    private static function param($param)
    {
        if (is_string($param)) {
            $param = [$param];
        }
        return $param;
    }
    private static function set(&$value, $k, $v)
    {
        $k = '{' . $k . '}';
        if (strpos($value, $k) !== false) {
            $value = str_replace($k, $v, $value);
        }
    }
}