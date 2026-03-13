<?php namespace Dever\Helper;
class Str
{
    public static function encode($string, $key = '')
    {
        return Secure::encode($string, $key);
    }
    public static function decode($string, $key = '')
    {
        return Secure::decode($string, $key);
    }
    public static function hide($string, $start = 3, $len = 4, $hide = '****')
    {
        return substr_replace($string, $hide, $start, $len);
    }
    public static function salt($len)
    {
        return bin2hex(random_bytes($len));
    }
    public static function rand($len, $type = 4)
    {
        $source = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z"];
        $config = array (
            0 => ["min" => 0, "max" => 9], /// 全数字
            1 => ["min" => 10, "max" => 35], /// 全小写
            2 => ["min" => 36, "max" => 61], /// 全大写
            3 => ["min" => 10, "max" => 61], /// 大小写
            4 => ["min" => 0, "max" => 61], /// 数字+大小写
        );
        if (!isset($config[$type])) {
            $type = 4;
        }
        $rand = "";
        for ($i = 0; $i < $len; $i++) {
            $rand .= $source[rand($config[$type]["min"], $config[$type]["max"])];
        }
        return $rand;
    }
    public static function order($prefix = '', $type = 1)
    {
        if ($type == 1) {
            return $prefix . date('Ymd').substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(1000, 9999));
        } elseif ($type == 2) {
            return $prefix . time() . substr(microtime(), 2, 5) . sprintf('%02d', rand(100000, 999999));
        } elseif ($type == 3) {
            return $prefix . date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        } elseif ($type == 4) {
            if (function_exists('session_create_id')) {
                return $prefix . strtolower(session_create_id());
            } else {
                $charid = strtolower(md5(uniqid(mt_rand(), true)));
                return $prefix . substr($charid, 0, 8) . substr($charid, 8, 4) . substr($charid, 12, 4) . substr($charid, 16, 4) . substr($charid, 20, 12);
            }
        }
    }
    public static function orderNum($id)
    {
        // 时间戳（到秒）
        $time = date('YmdHis');
        // 两位随机（36进制，36^2 = 1296）
        $rand = self::base36(mt_rand(0, 1295));
        // 转为大写，固定两位
        $rand = str_pad($rand, 2, '0', STR_PAD_LEFT);
        // ID 的 36 进制
        $id36 = self::base36($id);
        return $time . $rand . $id36;
    }
    public static function base36($num)
    {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $result = '';

        while ($num > 0) {
            $result = $chars[$num % 36] . $result;
            $num = intval($num / 36);
        }

        return $result ?: '0';
    }
    public static function code($num = 4)
    {
        $code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $rand = $code[rand(0,25)]
            .strtoupper(dechex(date('m')))
            .date('d')
            .substr(time(),-5)
            .substr(microtime(),2,5)
            .sprintf('%02d',rand(0,99));
        for(
            $a = md5( $rand, true ),
            $s = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            $d = '',
            $f = 0;
            $f < $num;
            $g = ord( $a[ $f ] ),
            $d .= $s[ ( $g ^ ord( $a[ $f + 8 ] ) ) - $g & 0x1F ],
            $f++
        );
        return $d;
    }
    public static function uid($uid, $type = true, $salt = 123, $xorKey = 456)
    {
        $source = 'E5FCDG3HQA4B1NOPIJ2RSTUV67MWX89KLYZ';
        $base = strlen($source);
        $minLength = 6;
        if ($type) {
            $num = ($uid * $salt) ^ $xorKey;
            $code = '';
            while ($num > 0) {
                $mod = $num % $base;
                $num = intdiv($num, $base);
                $code = $source[$mod] . $code;
            }
            $padLen = $minLength - strlen($code);
            for ($i = 0; strlen($code) < $minLength; $i++) {
                $padChar = $source[($i * 7 + $uid) % $base];
                $code = $padChar . $code;
            }
            return $code . $padLen; // 补位长度加到最后1位，解码时识别
        } else {
            $padLen = (int)substr($uid, -1);        // 最后一位是补位长度
            $uid = substr($uid, 0, -1);             // 去掉末尾
            $uid = substr($uid, $padLen);           // 去掉前面 padLen 个补位字符

            $num = 0;
            $len = strlen($uid);
            for ($i = 0; $i < $len; $i++) {
                $pos = strpos($source, $uid[$i]);
                if ($pos === false) return 0;
                $num = $num * $base + $pos;
            }
            $num = $num ^ $xorKey;
            return intdiv($num, $salt);
        }
    }

    public static function idtostr($id)
    {
        if (!is_numeric($id) || $id < 0) {
            return false;
        }
        $id = substr("00000000" . $id, -8);
        $sandNum = $id % 10;
        srand($id);
        $randstr = "" . rand(1, 9) . self::rand(7, 0);

        $retstr1 = "";
        $retstr2 = "";
        for ($i = 0; $i < 4; $i++) {
            $retstr1 .= $randstr[$i] . $id[$i];
            $retstr2 .= $id[7 - $i] . $randstr[7 - $i];
        }
        $retstr1 = substr(self::rand(6) . "g" . dechex($retstr1), -7);
        $retstr2 = substr(self::rand(6) . "g" . dechex($retstr2), -7);
        srand(time() + $id);
        $retstr = "1" . $sandNum;
        for ($i = 0; $i < 7; $i++) {
            $retstr .= $retstr1[$i] . $retstr2[$i];
        }
        return $retstr;
    }
    public static function strtoid($str)
    {
        if (strlen($str) != 16) {
            return $str;
        }
        //$type = $str1[0];
        $sandNum = $str[1];
        $retstr1 = $retstr2 = '';
        for ($i = 0; $i < 7; $i++) {
            if ($str[2+$i*2] == 'g') {
                $retstr1 = "";
            } else {
                $retstr1 .= $str[2+$i*2];
            }

            if ($str[3+$i*2] == 'g') {
                $retstr2 = "";
            } else {
                $retstr2 .= $str[3+$i*2];
            }
        }
        $retstr1 = "g".substr("00000000".hexdec($retstr1),-8);
        $retstr2 = "g".substr("00000000".hexdec($retstr2),-8);
        $ret1 = $ret2 = "";
        for ($i = 0; $i < 4; $i++) {
            $ret1 .= $retstr1[$i*2+2];
            $ret2 .= $retstr2[7-$i*2];
        }
        $ret = $ret1 * 10000 + $ret2;
        return $ret;
    }
    public static function cut($string, $length = 80, $etc = '...')
    {
        $result = '';
        $string = html_entity_decode(trim(strip_tags($string)), ENT_QUOTES, 'utf-8');
        for ($i = 0, $j = 0; $i < strlen($string); $i++) {
            if ($j >= $length) {
                for ($x = 0, $y = 0; $x < strlen($etc); $x++) {
                    if ($number = strpos(str_pad(decbin(ord(substr($string, $i, 1))), 8, '0', STR_PAD_LEFT), '0')) {
                        $x += $number - 1;
                        $y++;
                    } else {
                        $y += 0.5;
                    }
                }
                $length -= $y;
                break;
            }
            if ($number = strpos(str_pad(decbin(ord(substr($string, $i, 1))), 8, '0', STR_PAD_LEFT), '0')) {
                $i += $number - 1;
                $j++;
            } else {
                $j += 0.5;
            }
        }
        for ($i = 0; (($i < strlen($string)) && ($length > 0)); $i++) {
            if ($number = strpos(str_pad(decbin(ord(substr($string, $i, 1))), 8, '0', STR_PAD_LEFT), '0')) {
                if ($length < 1.0) {
                    break;
                }
                $result .= substr($string, $i, $number);
                $length -= 1.0;
                $i += $number - 1;
            } else {
                $result .= substr($string, $i, 1);
                $length -= 0.5;
            }
        }
        //$result = htmlentities($result, ENT_QUOTES, 'utf-8');
        if ($i < strlen($string)) {
            $result .= $etc;
        }
        return $result;
    }
    public static function length($string)
    {
        preg_match_all("/./us", $string, $match);
        return count($match[0]);
    }
    public static function addstr($str, $index, $sub)
    {
        $str = self::explode($str, 1);
        $length = count($str);
        $num = floor($length / $index);
        for ($a = 1; $a <= $num; $a++) {
            $start = '';
            $b = $a * $index;
            foreach ($str as $k => $v) {
                if ($k == $b) {
                    $str[$k] = $sub . $v;
                }
            }
        }
        return implode('', $str);
    }
    public static function explode($value, $num = 2)
    {
        $len = mb_strlen($value);
        $result = [];
        for ($i = 0; $i < $len; $i = $i + $num) {
            $result[$i / $num] = mb_substr($value, $i, $num);
        }
        return $result;
    }
    public static function ishtml($html)
    {
        if($html != strip_tags($html)) {
            return true;
        } else {
            return false;
        }
    }
    public static function encodePic($file, $type = 1)
    {
        $base64 = '';
        if (is_file($file)) {
            $info = getimagesize($file);
            $fp = fopen($file, "r");
            if ($fp) {
                $content = chunk_split(base64_encode(fread($fp, filesize($file))));
                switch ($info[2]) {
                    case 1: $img_type = 'gif';
                        break;
                    case 2: $img_type = 'jpg';
                        break;
                    case 3: $img_type = 'png';
                        break;
                }
                if ($type == 1) {
                    $base64 = 'data:image/' . $img_type . ';base64,' . $content;
                } else {
                    $base64 = $content;
                }
            }
            fclose($fp);
        }
        return $base64;
    }
    public static function getLink($text)
    {
        preg_match("/(https:|https:)(\/\/[A-Za-z0-9_#?.&=\/]+)([".chr(0xb0)."-".chr(0xf7)."][".chr(0xa1)."-".chr(0xfe)."])?(\s)?/i", $text, $result);

        if (isset($result[0])) {
            return $result[0];
        }
        return false;
    }
    public static function val($show, $data = [], $state = false)
    {
        if (is_array($show) || is_object($show)) {
            return $show;
        }
        $show = (string)$show;
        if (is_array($data) && strpos($show, '{') !== false && strpos($show, '{"') === false) {
            $func = function ($r) use ($data) {
                if (isset($data[$r[1]])) {
                    return $data[$r[1]];
                }
                return false;
            };
            $show = preg_replace_callback('/{(.*?)}/', $func, $show);
        }

        if (strstr($show, '"') || strstr($show, "'") || $state) {
            $eval = '$show =  ' . $show . ';';
        } else {
            $eval = '$show = "' . $show . '";';
        }
        @eval($eval);
        if (is_numeric($show)) {
            $show = (float) $show;
        }
        return $show;
    }
}
