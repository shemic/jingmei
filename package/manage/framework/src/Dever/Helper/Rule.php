<?php namespace Dever\Helper;
class Rule
{
	public static function get($method, $fix = '/', $rule = '')
    {
        return $fix . self::$method($rule) . $fix;
    }
    protected static function idcard($rule)
    {
        return '^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X)$';
    }
    protected static function mobile($rule)
    {
        return '^(1([0123456789][0-9]))\d{8}$';
    }
    protected static function email($rule)
    {
        return '^([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+@([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+\.[a-zA-Z]{2,3}$';
    }
    protected static function zh($rule)
    {
        $rule = $rule ? $rule : 8;
        return '^([\x{4e00}-\x{9fa5}]){'.$rule.'}$';
    }
    protected static function name($rule)
    {
        $rule = $rule ? $rule : 16;
        return '^([\x{4e00}-\x{9fa5}_a-zA-Z0-9\-]){'.$rule.'}$';
    }
}