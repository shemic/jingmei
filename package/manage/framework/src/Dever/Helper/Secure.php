<?php namespace Dever\Helper;
use Dever;
class Secure
{
    private static $token = false;
    public static function login($uid, $extend = false)
    {
        $auth = '';
        $data = Dever::json_encode(array($uid, time(), $extend));
        return self::encode($data);
    }
    public static function checkLogin($signature, $time = 31536000)
    {
        self::repeat($signature, $time);
        $auth = Dever::json_decode(self::decode($signature));
        if (isset($auth[0]) && isset($auth[1]) && $auth[0]) {
            if (time() - $auth[1] < $time) {
                return ['uid' => $auth[0], 'time' => $auth[1], 'extend' => $auth[2]];
            }
        }
        return false;
    }
    public static function get($request, $token = false)
    {
        if ($token) {
            self::$token = $token;
        }
        $time = $request['time'] ?? self::timestamp();
        $nonce = $request['nonce'] ?? self::nonce();
        $signature = self::signature($time, $nonce, $request);
        $request += [
            'time' => $time,
            'nonce' => $nonce,
            'signature' => $signature,
        ];
        return $request;
    }
    public static function check($request = [], $time = 300, $token = false)
    {
        if ($token) {
            self::$token = $token;
        }
        if (!$request) {
            $request = Dever::input();
        }
        if (empty($request['signature']) || empty($request['nonce'])) {
            Dever::error('signature不存在');
        }
        if (isset($request['l'])) {
            unset($request['l']);
        }
        if (isset($request['shell'])) {
            unset($request['shell']);
        }
        if (empty($request['time'])) {
            return self::checkLogin($request['signature']);
        }
        self::repeat($request['signature'], $time);
        if (time() - $request['time'] > $time) {
            Dever::error('signature已过期');
        }
        $signature = self::signature($request['time'], $request['nonce'], $request);
        if ($request['signature'] != $signature) {
            Dever::error('signature验签失败');
        }
        return $signature;
    }
    public static function signature($time, $nonce, $request = [])
    {
        if (isset($request['signature'])) {
            unset($request['signature']);
        }
        $request['token'] = self::token();
        $request['time'] = $time;
        $request['nonce'] = $nonce;
        ksort($request);
        $signature_string = '';
        foreach ($request as $k => $v) {
            if (is_array($v)) {
                continue;
            }
            $signature_string .= $k . '=' . $v . '&';
        }
        $signature_string = rtrim($signature_string, '&');
        return md5($signature_string);
    }
    public static function token()
    {
        if (self::$token) {
            return self::$token;
        }
        return Dever::config('setting')['token'];
    }
    public static function nonce()
    {
        return substr(sha1(microtime()), rand(10, 15));
    }
    public static function timestamp()
    {
        return Date::mtime();
    }
    public static function repeat($value, $expire)
    {
        return;
        if (isset(Dever::config('setting')['redis']) && !Redis::lock($value, 1, $expire)) {
            Dever::error('signature不能重复使用');
        }
    }
    public static function encode($string, $key = '')
    {
        $ckey_length = 5;
        if (!$key) {
            $key = self::token();
        }
        $key = sha1($key);
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = ''; //md5串后4位，每次不一样
        if ($ckey_length) {
            $keyc = substr(md5(microtime()), -$ckey_length);
        }
        $cryptkey = $keya . md5($keya . $keyc); //两个md5串
        $key_length = strlen($cryptkey); //64
        $string = sprintf('%010d', time()) . substr(md5($string . $keyb), 0, 16) . $string;
        $string_length = strlen($string);
        $result = '';
        $box = range(0, 255);
        $rndkey = [];
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]); //生成一个255个元素的数组
        }
        for ($j = $i = 0; $i < 256; $i++) {
            //将$box数组转换为无序并且个数不变的数据
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        return $keyc . str_replace('=', '', self::base64_encode($result));
    }
    public static function decode($string, $key = "")
    {
        $ckey_length = 5;
        if (!$key) {
            $key = self::token();
        }
        $key = sha1($key);
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = '';//和encrypt时的$keyc一样
        if ($ckey_length) {
            $keyc = substr($string, 0, $ckey_length);
        }
        $cryptkey = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);
        $string = self::base64_decode(substr($string, $ckey_length));
        $string_length = strlen($string);
        $result = '';
        $box = range(0, 255);
        $rndkey = [];
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }
        for ($j = $i = 0; $i < 256; $i++) {
            //和encrypt时的$box一样
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            //核心操作，解密
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        if (substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    }
    public static function base64_encode($string)
    {
        if (!$string) {
            return false;
        }
        $encodestr = base64_encode($string);
        $encodestr = str_replace(['+', '/'], ['-', '_'], $encodestr);
        return $encodestr;
    }
    public static function base64_decode($string)
    {
        if (!$string) {
            return false;
        }
        $string = str_replace(['-', '_'], ['+', '/'], $string);
        $decodestr = base64_decode($string);
        return $decodestr;
    }
    public static function xss($data)
    {
        if (!is_string($data)) {
            return $data;
        }
        $data = htmlspecialchars_decode($data);
        $data = str_replace('\/', '/', $data);
        $data = str_replace(['&amp;', '&lt;', '&gt;'], ['&amp;amp;', '&amp;lt;', '&amp;gt;'], $data);
        $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
        $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
        $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');
        $data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);
        $data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

        do {
            $old_data = $data;
            $data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
        } while ($old_data !== $data);
        return $data;
    }
}