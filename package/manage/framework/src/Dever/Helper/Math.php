<?php namespace Dever\Helper;
use Dever;
class Math
{
    private static $bc = null;
    public static function value($cash, $value)
    {
        if (strstr($value, '%')) {
            $value = str_replace('%', '', $value);
            $value = self::divMul($value, 100, $cash);
        }
        return self::format($value, 2);
    }
    public static function check(): bool
    {
        if (self::$bc === null) {
            self::$bc = extension_loaded('bcmath');
        }
        return self::$bc;
    }

    # 格式化展示
    public static function format($number, $scale = 2)
    {
        return number_format((float)$number, $scale, '.', '');
    }

    # 加法
    public static function add($left, $right, $scale = 2)
    {
        if (self::check()) {
            return bcadd((string)$left, (string)$right, $scale);
        }
        return self::format((float)$left + (float)$right, $scale);
    }

    # 减法
    public static function sub($left, $right, $scale = 2)
    {
        if (self::check()) {
            return bcsub($left, $right, $scale);
        }
        return self::format((float)$left - (float)$right, $scale);
    }

    # 乘法
    public static function mul($left, $right, $scale = 2)
    {
        if (self::check()) {
            return bcmul($left, $right, $scale);
        }
        return self::format((float)$left * (float)$right, $scale);
    }

    # 除法
    public static function div($left, $right, $scale = 2)
    {
        if (self::check()) {
            return bcdiv($left, $right, $scale);
        }
        return self::format((float)$left / (float)$right, $scale);
    }

    # 先乘法后除法
    public static function mulDiv($left, $right, $after, $scale = 2)
    {
        $num = self::mul($left, $right);
        return self::div($num, $after);
    }

    # 先除法后乘法
    public static function divMul($left, $right, $after, $scale = 2)
    {
        $num = self::div($left, $right);
        return self::mul($num, $after);
    }

    # 四舍五入
    public static function round($number, $scale = 2)
    {
        if (self::check()) {
            $factor = bcpow('10', (string)($precision + 1));
            $tmp = bcmul($number, $factor, 0); // 扩大精度 1 位并截断
            $lastDigit = (int)bcmod($tmp, '10');
            $roundUp = $lastDigit >= 5 ? '1' : '0';

            $scaled = bcdiv($tmp, '10', 0); // 移除最后一位（整数部分）
            if ($roundUp === '1') {
                $scaled = bcadd($scaled, '1');
            }
            return bcdiv($scaled, bcpow('10', (string)$precision), $precision);
        }
        return self::format($number, $scale);
    }

    # 复杂算式
    public static function calc($expr, $vars = [], $scale = 2)
    {
        if (self::check()) {
            return Bc::evaluate($expr, $vars, $scale);
        }
        if (preg_match('/[^0-9\.\+\-\*\/\%\(\) \$a-zA-Z_]/', $expr)) {
            return 0;
        }
        foreach ($vars as $k => $v) {
            $expr = preg_replace('/\b' . preg_quote($k, '/') . '\b/', $v, $expr);
        }
        $number = eval('return ' . $expr . ';');
        return self::format($number, $scale);
    }

	# 笛卡尔积
    public static function cartesian($data)
    {
        $len = count($data);
        $result = [];
        if ($len == 1) {
            foreach ($data[0] as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k1 => $v1) {
                        $v[$k1] = [$v1];
                    }
                }
                $result[] = $v;
            }
        } else {
            $k = 0;
            $result = $data[0];
            for ($i = 1; $i < $len; $i++) {
                $arr = $result;
                $result = [];
                foreach ($arr as $v) {
                    foreach($data[$i] as $v1) { 
                        if(!is_array($v)) {
                            $v = [$v];
                        }
                        if(!is_array($v1)){
                            $v1 = [$v1];
                        }
                        $result[] = array_merge_recursive($v, $v1);
                    }
                }
            }
        }
        return $result;
    }
    # 计算距离
    public static function distance($lng1, $lat1, $lng2, $lat2, $miles = true)
    {
        $pi80 = M_PI / 180;
        $lat1 *= $pi80;
        $lng1 *= $pi80;
        $lat2 *= $pi80;
        $lng2 *= $pi80;
        $r = 6372.797;
        $dlat = $lat2 - $lat1;
        $dlng = $lng2 - $lng1;
        $a = sin($dlat/2)*sin($dlat/2)+cos($lat1)*cos($lat2)*sin($dlng/2)*sin($dlng/2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $km = $r * $c;
        if ($miles) {
            $km = $km * 0.621371192;
        }
        return $km;
    }
    # 将数值金额转换为中文大写金额
    public static function convertNum($amount, $type = 1) {
        if (!is_numeric($amount) || strlen($amount) > 12) {
            return false;
        }
        if($amount == 0) {
            return "零元整";
        }
        $result = '';
        if($amount < 0) {
            $result = '负';
        }
        $digital = ['零', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖'];
        $position = ['仟', '佰', '拾', '亿', '仟', '佰', '拾', '万', '仟', '佰', '拾', '元'];
        $amountArr = explode('.', $amount);
        $integerArr = str_split($amountArr[0], 1);
        $integerArrLength = count($integerArr);
        $positionLength = count($position);
        for ($i = 0; $i < $integerArrLength; $i++) {
            if ($integerArr[$i] != 0) {
                $result = $result . $digital[$integerArr[$i]] . $position[$positionLength - $integerArrLength + $i];
            } else {
                if (($positionLength - $integerArrLength + $i + 1)%4 == 0) {
                    $result = $result . $position[$positionLength - $integerArrLength + $i];
                }
            }
        }
        if ($type == 0) {
            $decimalArr = str_split($amountArr[1], 1);
            if ($decimalArr[0] != 0){
                $result = $result . $digital[$decimalArr[0]] . '角';
            }
            if ($decimalArr[1] != 0){
                $result = $result . $digital[$decimalArr[1]] . '分';
            }
        } else {
            $result = $result . '整';
        }
        return $result;
    }
}