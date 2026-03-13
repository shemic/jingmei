<?php namespace Dever\Helper;
class Arr
{
    public static function replace($replace, $value, $key = 'col')
    {
        $result = [];
        foreach ($replace as $k => $v) {
            if (isset($value[$k])) {
                $v = $value[$k];
                $k = $value[$k][$key];
            }
            if ($v) {
                $result[$k] = $v;
            }
        }
        return $result;
    }

    public static function queryString($arr, $sort = 'ksort', $kvlink = '=', $link = '&', $kencode = '', $vencode = '')
    {
        if ($sort) {
            if is_array($sort) {
                list($method, $param) = $sort;
                $method($arr, $param);
            } else {
                $sort($arr);
            }
        }
        $result = [];
        foreach ($arr as $k => $v) {
            if (null === $v) {
                continue;
            }
            $str = rawurlencode($k);
            if ('' !== $v && null !== $v) {
                $str .= '=' . rawurlencode($v);
            } else {
                $str .= '=';
            }
            $result[] = $str;
        }
        return implode($link, $result);
    }
}