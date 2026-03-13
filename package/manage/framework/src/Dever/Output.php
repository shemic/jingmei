<?php namespace Dever;
use Dever;
class Output
{
    private $format = 'json';
    public function success($data, $uuid = false, $code = 200)
    {
        $result = [];
        $result['status'] = 1;
        $result['msg'] = 'success';
        $result['data'] = $data;
        $result['code'] = $code;
        if ($page = Dever::get(Paginator::class)->get()) {
            $result['page'] = $page;
        }
        if ($uuid) {
            $result['uuid'] = Dever::uuid();
        }
        $result = $this->setting($result);
        $this->handle($result);
        return $result;
    }
    public function error($msg, $code = 500)
    {
        $result = [];
        $result['status'] = 2;
        $result['code'] = $code;
        $result['msg'] = $msg;
        $this->handle($result);
        Dever::get(Debug::class)->out($result);
        $throwMsg = is_string($result) ? $result : Dever::json_encode($result);
        throw new \RuntimeException($throwMsg);
    }
    public function out($result)
    {
        return $this->handle($result);
    }
    public function format($data)
    {
        if (is_array($data)) {
            $data = htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE));
        }
        $content = "<pre>\n";
        $content .= $data;
        $content .= "\n</pre>\n";
        return $content;
    }
    public function setting($result)
    {
        $setting = Dever::config('setting');
        if (isset($setting['output_app']) && $setting['output_app'] && !in_array(DEVER_APP_NAME, $setting['output_app'])) {
            $setting['output'] = [];
        }
       
        if ($setting = Dever::issets($setting, 'output')) {
            foreach ($setting as $k => $v) {
                if (isset($result[$k])) {
                    if (is_array($v)) {
                        if (is_array($result[$k])) {
                            $result[$v[0]] = $result[$k][$v[1]] ?? $result[$k];
                        } else {
                            $result[$v[0]] = $v[1][$result[$k]] ?? $result[$k];
                        }
                    } else {
                        $result[$v] = $result[$k];
                    }
                    unset($result[$k]);
                } elseif (strstr($v, 'Dever')) {
                    $result[$k] = \Dever\Helper\Str::val($v);
                } else {
                    $result[$k] = Dever::call($v);
                }
            }
        }
        return $result;
    }
    public function handle(&$result)
    {
        Dever::get(Debug::class)->out($result);
        $this->json($result);
        $this->callback($result);
        $this->func($result);
        if ($this->format == 'json') {
        } else {
            $this->html($result);
        }
    }
    private function json(&$result)
    {
        if ($this->format == 'json' || Dever::get(Route::class)->input('json') == 1) {
            if (!$result) {
                $result = (object) $result;
            }
            $result = Dever::json_encode($result);
            $this->format = 'json';
        } else {
            $this->format = 'str';
        }
    }
    private function callback(&$result)
    {
        if ($callback = Dever::get(Route::class)->input('callback')) {
            $result = $callback . '(' . $result . ')';
        }
    }
    private function func(&$result)
    {
        if ($function = Dever::get(Route::class)->input('function')) {
            $result = '<script>parent.' . $function . '(' . $result . ')' . '</script>';
        }
    }
    public function html($msg)
    {
        $html = '' . $msg['msg'];
        $host = Dever::get(Route::class)->url('');
        $name = '404';
        if ($msg['code'] > 1) {
            $name = $msg['code'];
        }
        if ($name == 404) {
            header("HTTP/1.1 404 Not Found");
            header("Status: 404 Not Found");
        }
        $file = DEVER_APP_PATH . 'config/html/' . $name . '.html';
        if (is_file($file)) {
            include $file;
        } else {
            $file = DEVER_PROJECT_PATH . 'config/html/' . $name . '.html';
            if (is_file($file)) {
                include $file;
            } else {
                include DEVER_PATH . 'config/html/default.html';
            }
        }
    }
}
