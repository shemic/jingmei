<?php namespace Dever;
use Dever;
class Debug
{
    public $shell;
    private $data;
    private $trace;
    private $time;
    private $memory;
    private $start;
    public function init()
    {
        ini_set('display_errors', true);
        $this->start = microtime();
        set_error_handler([$this, 'error_handler'], E_ERROR | E_NOTICE | E_STRICT);
        set_exception_handler([$this, 'exception_handler']);
        $this->shell = Dever::shell(Dever::config('setting')['shell']);
    }
    public function error_handler($no, $str, $file, $line)
    {
        $this->shell = true;
        $data['msg'] = $str;
        $data['file'] = $file . ':' . $line;
        $content = file_get_contents($file);
        $lines = explode("\n", $content);
        $lines = array_slice($lines, $line - 10, 20, true);
        $code = '<code>';
        foreach ($lines as $k => $v) {
            $k+=1;
            if ($k == $line) {
                $code .= '<a style="display:inline-block;color:red">' . $k . $v . '</a>';
            } else {
                $code .= $k . $v;
            }
            $code .= "\n";
        }
        $code .= '</code>';
        $this->add($code, false);
        $this->add($this->formatTrace(debug_backtrace()), false);
        $this->out($data, false);
    }
    public function exception_handler($exception)
    {
        $this->add($this->formatTrace($exception->getTrace(), $exception), false);
        $this->error_handler($exception->getCode(), $exception->getMessage(), $exception->getFile(), $exception->getLine());
    }
    public function add($data, $type = 'log')
    {
        if ($this->shell) {
            $msg = $data;
            if ($type) {
                $msg = is_array($msg) ? $msg : ['msg' => $msg];
                $msg = array_merge($msg, $this->env());
            }
            $this->data($type, Dever::get(Output::class)->format($msg));
        }
        return $data;
    }
    public function out($data, $type = 'out')
    {
        if ($this->shell) {
            $this->add($data, $type);
            $this->total();
            throw new \RuntimeException($this->html());
        }
    }
    public function total()
    {
        $this->data('include', Dever::get(Output::class)->format($this->load()));
        $msg = array('time' => $this->time(), 'memory' => $this->memory());
        $this->data('total', Dever::get(Output::class)->format($msg));
        return $msg;
    }
    public function lib($class, $method)
    {
        if ($this->shell) {
            $class = new \ReflectionClass($class);
            $trace['file'] = $class->getFileName();
            $trace['line'] = $class->getStartLine();
            $trace['class'] = $class->getName();
            $trace['function'] = $method;
            $content = explode("\n", file_get_contents($trace['file']));
            foreach ($content as $k => $v) {
                if (strpos($v, 'function ' . $method . '(')) {
                    $trace['line'] = $k+1;
                    break;
                }
            }
            $key = $trace['file'] . ':' . $trace['line'];
            $this->trace[$key] = $trace;
        }
    }
    public function trace()
    {
        $debug = debug_backtrace();
        $trace = '';
        if ($debug) {
            foreach($debug as $k => $v) {
                if ($this->check($v)) {
                    $trace = $v['file'] . ':' . $v['line'];
                    $this->trace[$trace] = $v;
                    break;
                }
            }
        }
        if (!$trace && $this->trace) {
            $trace = array_keys($this->trace);
            $trace = $trace[0];
        }
        return $trace;
    }
    public function getTrace()
    {
        if ($this->trace) {
            return array_reverse(array_values($this->trace));
        }
        return [];
    }
    private function env()
    {
        $trace = $this->trace();
        return array
        (
            'time' => 'current:' . $this->time(2) . ' total:' . $this->time(),
            'memory' => 'current:' . $this->memory(2) . ' total:' . $this->memory(),
            'trace' => $trace
        );
    }
    private function data($method, $msg)
    {
        if (!$method) {
            $method = 'error';
        }
        $this->data[$method][] = $msg;
    }
    private function check($value)
    {
        if (isset($value['file']) && strpos($value['file'], DEVER_APP_PATH) !== false) {
            $config = ['app', 'manage', 'table'];
            foreach ($config as $k => $v) {
                if (strpos($value['file'], DEVER_APP_PATH . $v) !== false) {
                    return true;
                }
            }
        }
        return false;
    }
    private function time($state = 1)
    {
        $start = $this->startTime($state);
        $end = $this->endTime();
        return '[' . ($end - $start) . 'S]';
    }
    private function endTime()
    {
        $this->time = microtime();
        return $this->createTime($this->time);
    }
    private function startTime($state = 1)
    {
        $start = $this->start;
        if ($state == 2 && $this->time) {
            $start = $this->time;
        }
        return $this->createTime($start);
    }
    private function createTime($time)
    {
        list($a, $b) = explode(' ', $time);
        return ((float) $a + (float) $b);
    }
    private function memory($state = 1)
    {
        $memory = memory_get_usage();
        if ($state == 2 && $this->memory) {
            $memory = $memory - $this->memory;
        }
        $this->memory = $memory;
        return '[' . ($memory / 1024) . 'KB]';
    }
    private function load()
    {
        $files = get_included_files();
        $result = [];
        $path = DEVER_PATH;
        foreach ($files as $k => $v) {
            if (strpos($v, $path) === false) {
                $result[] = $v;
            }
        }
        return $result;
    }
    private function html($show = '')
    {
        $html = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.6.0/styles/atom-one-dark.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.6.0/highlight.min.js"></script><div>';
        if ($this->data) {
            foreach ($this->data as $k => $v) {
                $html .= $k;
                $html .= '<table style="width:100%;">';
                foreach ($v as $i => $j) {
                    $html .= '<tr>';
                    $html .= '<td style="background-color:#f5f5f5;padding: 8px;">' . $j . '</td>';
                    $html .= '</tr>';
                }
                $html .= '</table>';
            }
        }
        $html .= '</div><script>hljs.highlightAll();</script>';
        return $html;
    }

    private function formatTrace(array $trace, $exception = null)
    {
        $out = '<code>';
        $i = 0;
        if ($exception) {
            $out .= '#0 ' . $exception->getFile() . ':' . $exception->getLine() . ' ' . $exception->getMessage() . "\n";
            $i = 1;
        }
        foreach ($trace as $k => $v) {
            $file = $v['file'] ?? '[internal]';
            $line = $v['line'] ?? 0;
            $func = $v['function'] ?? '';
            $class = $v['class'] ?? '';
            $type = $v['type'] ?? '';
            $out .= '#' . ($k + $i) . ' ' . $file . ':' . $line . ' ' . $class . $type . $func . "()\n";
        }
        $out .= '</code>';
        return $out;
    }
}
