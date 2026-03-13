<?php namespace Dever;
use Dever;
class Route
{
    public $data = [];
    public $server = [];
    protected $type = '?l=';
    public function input($key = false, $condition = '', $lang = '', $value = '')
    {
        if (!$key) {
            return $this->data;
        }
        if ($condition == 'set') {
            return $this->data[$key] = $lang;
        }
        if (is_array($key)) {
            foreach ($key as $v) {
                $v = $this->input($v, $condition, $lang, $value);
                if ($v) {
                    return $v;
                }
            }
        }
        if (is_string($key) && isset($this->data[$key]) && $this->data[$key] && $this->data[$key] != 'false') {
            $value = $this->data[$key];
        }
        if ($condition) {
            if (!$lang) {
                $lang = $key;
            }
            if (!$value && $value !== 0) {
                Dever::get(Output::class)->error($lang . '不能为空');
            }
            $state = true;
            if (strpos($condition, '/') === 0) {
                $state = preg_match($condition, $value);
            } elseif (function_exists($condition)) {
                $state = $condition($value);
            }
            if (!$state) {
                Dever::get(Output::class)->error($lang . '验证无效');
            }
        }
        return $value;
    }
    public function url($uri = false, $param = [], $auth = false, $rewrite = '')
    {
        if ($uri == false) {
            if (isset($this->server['app_host']) && $this->server['app_host']) {
                $uri = $this->server['scheme'] . '://' . $this->server['host'] . $this->server['uri'];
            } else {
                $argv = array_merge(\Dever::json_decode($_SERVER['argv'][1]), $param);
                return $_SERVER['argv'][0] . ' ' . \Dever::json_encode($argv);
            }
        }
        if (strpos($uri, 'http') === 0) {
            if ($param) {
                $uri .= '&' . http_build_query($param);
            }
            return $uri;
        }
        if (strpos($uri, '/')) {
            $temp = explode('/', $uri, 2);
            $app = $temp[0];
            $uri = $temp[1];
            if (strpos($uri, 'Manage/Api') === 0) {
                $uri = str_replace('Manage/Api/', '', $uri);
                $uri = 'manage/' . lcfirst($uri);
            } elseif (strpos($uri, 'Api') === 0) {
                $uri = str_replace('Api/', '', $uri);
                $uri = lcfirst($uri);
            }
        } else {
            $app = DEVER_APP_NAME;
        }
        $project = Dever::project($app);
        if (!$uri) {
            return $project['url'];
        }
        if ($auth) {
            $param['authorization'] = \Dever\Helper\Str::encode(\Dever\Helper\Env::header('authorization'));
        }
        if ($route = Dever::config('setting')['route']) {
            if ($search = array_search($uri, $route)) {
                $uri = $search;
            }
            if ($param) {
                $query = '';
                $data = [];
                $i = 1;
                foreach ($param as $k => $v) {
                    $query .= $k . '=$' . $i . '&';
                    $data[$i] = $v;
                    $i++;
                }
                $query = rtrim($query, '&');
                if ($key = array_search($uri . '?' . $query, $route)) {
                    $i = 0;
                    $uri = preg_replace_callback('/\(.*?\)/', function($param) use($data, &$i) {
                        $i++;
                        return $data[$i];
                    }, $key);
                } else {
                    $uri .= '&' . http_build_query($param);
                }
            }
        } elseif ($param) {
            $uri .= '&' . http_build_query($param);
        }
        if ($rewrite) {
            return str_replace($rewrite, '', $project['url']) . $uri;
        }
        if ($this->type == '?l=' && strpos($uri, '?')) {
            $uri = str_replace('?', '&', $uri);
        }
        return $project['url'] . $this->type . $uri;
    }
    public function host()
    {
        $temp = explode('/', DEVER_PROJECT_PATH);
        $name = '/' . $temp[count($temp)-2] . '/';
        $host = $this->server['scheme'] . '://' . $this->server['host'];
        if (strpos($this->server['uri'], $name) === 0) {
            $host .= $name;
        } else {
            $host .= '/';
        }
        return $host;
    }
    public function get($data = null)
    {
        if ($data) {
            $this->data = $data['request'];
            $this->server = $data['server'];
        } elseif (!$this->command()) {
            $this->fpm();
        }
        $this->match();
        $this->filter($this->data);
        if (isset($this->data['uuid']) && isset(Dever::config('setting')['redis']) && !\Dever\Helper\Redis::lock($this->data['uuid'], 1, 60)) {
            Dever::get(Output::class)->error('route repeat');
        }
        return $this->data;
    }
    protected function command()
    {
        if (isset($_SERVER['argc'])) {
            global $argv;
            if (isset($argv[1]) && $argv[1]) {
                $this->data = \Dever::json_decode($argv[1]);
            }
            $this->server = [
                'type' => 'cmd',
                'app_host' => '',
            ];
            return true;
        }
        return false;
    }
    protected function fpm()
    {
        $this->data = $_REQUEST;
        if (isset($_FILES) && $_FILES) {
            $this->data = array_merge($this->data, $_FILES);
        }
        $pathinfo = $this->pathinfo();
        if ($pathinfo) {
            $this->data['l'] = trim($pathinfo, '/');
        } else {
            $this->type = '?l=';
        }
        $this->server = [
            'type' => 'fpm',
            'uri' => $_SERVER['REQUEST_URI'],
            'host' => $_SERVER['HTTP_HOST'],
            'scheme' => ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https' : 'http',
        ];
        $this->server['app_host'] = $this->server['scheme'] . '://' . $_SERVER['HTTP_HOST'] . ($_SERVER['SCRIPT_NAME'] ? substr($_SERVER['SCRIPT_NAME'], 0, strpos($_SERVER['SCRIPT_NAME'], DEVER_ENTRY)) : DIRECTORY_SEPARATOR);
    }
    protected function pathinfo()
    {
        $pathinfo = '';
        if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO']) {
            $pathinfo = $_SERVER['PATH_INFO'];
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) {
            $pathinfo = $_SERVER['ORIG_PATH_INFO'];
        }
        return $pathinfo;
    }
    public function match()
    {
        if (!isset($this->data['l'])) {
            $this->data['l'] = '';
        }
        if ($route = Dever::config('setting')['route']) {
            $value = $this->data['l'];
            if (isset($route[$value])) {
                $this->data['l'] = $route[$value];
            } else {
                foreach ($route as $k => $v) {
                    $k = strtr($k, [
                        ':any' => '.+',
                        ':num' => '[0-9]+',
                    ]);
                    if (preg_match('#^' . $k . '$#', $value)) {
                        if (strpos($v, '$') !== false && strpos($k, '(') !== false) {
                            $v = preg_replace('#^' . $k . '$#', $v, $value);
                        }
                        if (strpos($v, '?')) {
                            $temp = explode('?', $v);
                            $v = $temp[0];
                            parse_str($temp[1], $data);
                            $this->data = array_merge($data, $this->data);
                        }
                        $this->data['l'] = $v;
                    }
                }
            }
        }
    }
    protected function filter(&$data)
    {
        if ($data) {
            foreach ($data as $k => $v) {
                if (is_array($v)) {
                    $this->filter($v);
                } else {
                    if ($data[$k] == 'undefined') {
                        $data[$k] = '';
                    } elseif (strstr($v, '<') && strstr($v, '>')) {
                        $data[$k] = htmlspecialchars($v);
                    }
                }
            }
        }
    }
}