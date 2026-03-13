<?php namespace Dever\Helper;
class Date
{
    # 获取时间戳
    public static function mktime($v)
    {
        if (!$v) {
            return '';
        }
        if (is_numeric($v)) {
            return $v;
        }
        $n = 0;
        if (strstr($v, 'T')) {
            $v = str_replace(['T', 'Z'], ' ', $v);
            $n = 8*3600;
        }
        if (strstr($v, ' ')) {
            $t = explode(' ', $v);
            $v = $t[0];
            $s = explode(':', $t[1]);
        } else {
            $s = [0, 0, 0];
        }
        if (!isset($s[1])) {
            $s[1] = 0;
        }
        if (!isset($s[2])) {
            $s[2] = 0;
        }
        if (strstr($v, '-')) {
            $t = explode('-', $v);
        } elseif (strstr($v, '/')) {
            $u = explode('/', $v);
            $t[0] = $u[2];
            $t[1] = $u[0];
            $t[2] = $u[1];
        }
        if (!isset($t)) {
            $t = [0, 0, 0];
        }
        if (!isset($t[1])) {
            $t[1] = '-1';
        }
        if (!isset($t[2])) {
            $t[2] = '01';
        }
        $v = mktime($s[0], $s[1], $s[2], $t[1], $t[2], $t[0]) + $n;
        return $v;
    }
    # 获取日期
	public static function mkdate($num, $type = 2)
    {
        $date = $num;
        $num = time() - $num;
        if ($num <= 0) {
            if ($type == 2) {
                return '1秒前';
            } else {
                return '1S';
            }
        }
        $config = array(
            [31536000, 'Y', '年'],
            [2592000, 'T', '个月'],
            [604800, 'W', '星期'],
            [86400, 'D', '天'],
            [3600, 'H', '小时'],
            [60, 'M', '分钟'],
            [1, 'S', '秒'],
        );
        if ($type == 2) {
            foreach ($config as $k => $v) {
                $value = intval($num / $v[0]);
                if ($v[1] == 'D' && $value >= 7) {
                    break;
                }
                if ($value != 0) {
                    return $value . $v[2] . '前';
                }
            }
            return date('Y-m-d H:i:s', $date);
        } else {
            $result = '';
            foreach ($config as $k => $v) {
                if ($num > $v[0]) {
                    $value = intval($num / $v[0]);
                    $num = $num - $v[0] * $value;
                    $result .= $value . $v[1] . ' ';
                }
            }
            return $result;
        }
    }
    # 获取周的开始时间和结束时间
    public static function week($time = false, $prefix = '-')
    {
        if (strpos($time, '-')) {
            $time = self::mktime($time);
        } elseif ($time > 0) {
            $time = strtotime($prefix . $time . ' week');
        } elseif (!$time) {
            $time = time();
        }
        $start = self::mktime(date('Y-m-d 00:00:00', strtotime('this week Monday', $time)));
        $end = self::mktime(date('Y-m-d 23:59:59', strtotime('this week Sunday', $time)));
        return [$start, $end];
    }

    # 获取月的开始时间和结束时间
    public static function month($time = false, $prefix = '-')
    {
        if (strpos($time, '-')) {
            $time = self::mktime($time);
        } elseif ($time > 0) {
            $time = strtotime($prefix . $time . ' month');
        } elseif (!$time) {
            $time = time();
        }
        list($year, $month, $end) = explode('-', date('Y-m-t', $time));
        $start = self::mktime($year . '-' . $month . '-01 00:00:00');
        $end = self::mktime($year . '-' . $month . '-' . $end . ' 23:59:59');
        return [$start, $end];
    }
    # 获取天的开始时间和结束时间
    public static function day($time = false, $prefix = '-')
    {
        if (strpos($time, '-')) {
            $time = self::mktime($time);
        } elseif ($time > 0) {
            $time = strtotime($prefix . $time . ' day');
        } elseif (!$time) {
            $time = time();
        }
        list($year, $month, $day) = explode('-', date('Y-m-d', $time));
        $start = self::mktime($year . '-' . $month . '-' . $day . ' 00:00:00');
        $end = self::mktime($year . '-' . $month . '-' . $day . ' 23:59:59');
        return [$start, $end];
    }
    # 获取毫秒
    public static function mtime()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return $msectime;
    }
    # 获取微秒
    public static function wtime()
    {
        list($usec, $sec) = explode(' ', microtime());
        $timestamp = sprintf('%d%06d', $sec, $usec*1000000);
        return $timestamp;
    }
    # 获取第几周
    public static function getWeek($time)
    {
        $week = abs(ceil((date('j', $time) - [0,6,5,4,3,2,1][date('w', strtotime(date('Y-m-01')))]) / 7));
        return $week + 1;
    }
}