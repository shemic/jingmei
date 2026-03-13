<?php namespace Dever;
use Dever;
class Log
{
    public function add($log, $type = 1)
    {
        if (!$config = Dever::config('setting')['log']) {
            return;
        }
        $config['level'] = $type;
        return $this->push($this->filter($log), $config);
    }
    private function push($log, $config)
    {
        $method = 'push_' . $config['type'];
        return $this->$method($log, $config);
    }
    private function push_http($log, $config)
    {
        return false;
    }
    private function push_udp($log, $config)
    {
        $handle = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($handle) {
            socket_sendto($handle, $log, strlen($log), 0, $config['host'], $config['port']);
            socket_close($handle);
        }
    }
    private function push_syslog($log, $config)
    {
        if ($config['level'] == 1) {
            $level = LOG_DEBUG;
            $name = LOG_LOCAL1;
        } elseif ($config['level'] == 2) {
            $level = LOG_NOTICE;
            $name = LOG_LOCAL2;
        } elseif ($config['level'] == 3) {
            $level = LOG_INFO;
            $name = LOG_LOCAL3;
        } else {
            $level = LOG_INFO;
            $name = LOG_LOCAL4;
        }
        openlog(DEVER_APP_NAME, LOG_PID, $name);
        syslog($level, $log);
        closelog();
    }
    private function push_file($log, $config)
    {
        $day = date('Y/m/d');
        $time = date('H:i:s');
        $log = $day . ' ' . $time . ' ' . DEVER_PROJECT . ' ' . DEVER_APP_NAME . ' ' . $log . "\r\n";
        $file = $this->file($config['level'], $day, substr($time, 0, 2));
        $size = 5242880;
        if (isset($config['size'])) {
            $size = $config['size'];
        }
        $exists = false;
        if (is_file($file)) {
            $exists = true;
        }
        if ($exists && $size <= filesize($file)) {
            rename($file, $file . '.' . str_replace(':', '_', $time) . '.bak');
            $exists = false;
        }
        $state = error_log($log, 3, $file);
        if ($state && !$exists) {
            @chmod($file, 0755);
            //@system('chmod -R 777 ' . $file);
        }
        return $state;
    }
    public function get($day, $type = 1)
    {
        if (is_array(Dever::config('setting')['log'])) {
            $method = Dever::config('setting')['log']['type'];
        } else {
            $method = 'syslog';
        }
        $method = 'get_' . $method;
        return $this->$method($day, $type);
    }
    private function get_http($day, $type)
    {
        return false;
    }
    private function get_udp($day, $type)
    {
        return false;
    }
    private function get_syslog($day, $type)
    {
        return false;
    }
    private function get_file($day, $type)
    {
        $file = $this->file($type, $day);
        $content = '';
        $path = dirname($file);
        if (is_dir($path)) {
            $dir = scandir($path);
            foreach ($dir as $k => $v) {
                if (strstr($v, $file[2])) {
                    $content .= file_get_contents($path . $v);
                }
            }
        }
        if ($content) {
            return explode("\n", $content); 
        }
        return [];    
    }
    public function filter($string)
    {
        if (is_array($string)) {
            $string = Dever::json_encode($string);
        }
        return $string;
    }
    private function file($level, $day, $hour = '')
    {
        if ($level == 1) {
            $file = 'debug';
        } elseif ($level == 2) {
            $file = 'notice';
        } elseif ($level == 3) {
            $file = 'info';
        } else {
            $file = $level;
        }
        if ($hour) {
            $file .= '_' . $hour;
        }
        return Dever::get(File::class)->get('logs/' . $day . DIRECTORY_SEPARATOR . $file);
    }
}