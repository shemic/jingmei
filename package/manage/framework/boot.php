<?php
header('Content-Type: text/html; charset=utf-8');date_default_timezone_set("PRC");define('DEVER_TIME', $_SERVER['REQUEST_TIME']);define('DEVER_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
if (defined('DEVER_CRON')) {
    Dever::cron();
} elseif (defined('DEVER_SERVER')) {
    Dever::server();
} else {
    Dever::fpm();
}
class Dever
{
    protected static $instances = [];
    protected static $requestId = null;
    protected static $requestInstances = [];
    protected static $counter = 0;
    protected static $reflectionCache = [];
    protected static $bindings = [];
    protected static $commit = [];
    protected static $data = [];
    protected static $dependencyCache = [];
    protected static $bootstrapped = false;
    protected static $requestScopedClasses = [
        Dever\App::class => true,
        Dever\Route::class => true,
        Dever\Debug::class => true,
        Dever\Output::class => true,
        Dever\Paginator::class => true,
        Dever\Model::class => true,
        Dever\Helper\Curl::class => true,
    ];
    protected static $settingCache = [];

    public static function cron()
    {
        self::bootstrap();
        $http_worker = new \Workerman\Worker();

        $http_worker->count = DEVER_WORKER;
        $http_worker->reloadable = true;
        $http_worker->max_request = 0;
        $http_worker->onWorkerStart = function($worker) {
            self::get(Dever\Project::class)->register();
            self::call(DEVER_CRON, $worker->id);
        };
        \Workerman\Worker::runAll();
    }

    public static function server()
    {
        self::bootstrap();
        $http_worker = new \Workerman\Worker("http://0.0.0.0:" . DEVER_SERVER);

        $http_worker->count = DEVER_WORKER;
        $http_worker->reloadable = true;
        $http_worker->max_request = 0;
        $http_worker->onMessage = function(\Workerman\Connection\TcpConnection $connection, $request) {
            self::beginRequest();
            try {
                $path = $request->path();
                if ($path === '/favicon.ico') {
                    $connection->send(new \Workerman\Protocols\Http\Response(204)); // 返回空内容
                    return;
                }
                $env = new Dever\Helper\Wokerman($request, $connection);

                try {
                    $output = self::run($env->getData());
                } catch (\Throwable $e) {
                    $output = $e->getMessage();
                    if (!self::isJsonString($output)) {
                        $output = '';
                        try {
                            Dever::get(Dever\Debug::class)->exception_handler($e);
                        } catch (\Throwable $inner) {
                            $output = $inner->getMessage();
                        }
                        if ($output === '') {
                            $output = $e->getMessage() . "\n" . $e->getTraceAsString();
                        }
                    }
                }
                //$output = 'test';
                $headers = [];
                if (!strstr($output, '<pre>')) {
                    $headers = [
                        'Content-Type'                 => 'application/json; charset=utf-8',
                        'Access-Control-Allow-Origin'  => '*',                            // 允许所有域
                        'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',           // 允许请求方法
                        'Access-Control-Allow-Headers' => 'Content-Type, Authorization',  // 允许的自定义头
                    ];
                }
                // 统一处理 OPTIONS 预检请求
                if ($request->method() === 'OPTIONS') {
                    $connection->send(new \Workerman\Protocols\Http\Response(204, $headers)); // 不返回内容
                    return;
                }
                $connection->send(new \Workerman\Protocols\Http\Response(200, $headers, $output));
            } catch (\Throwable $e) {
                $msg = $e->getMessage();
                if (!self::isJsonString($msg)) {
                    $msg = '';
                    try {
                        Dever::get(Dever\Debug::class)->exception_handler($e);
                    } catch (\Throwable $inner) {
                        $msg = $inner->getMessage();
                    }
                    if ($msg === '') {
                        $msg = $e->getMessage() . "\n" . $e->getTraceAsString();
                    }
                    $connection->send(self::out()->error($msg));
                } else {
                    $connection->send(new \Workerman\Protocols\Http\Response(200, [
                        'Content-Type' => 'application/json; charset=utf-8',
                        'Access-Control-Allow-Origin'  => '*',
                        'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
                        'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
                    ], $msg));
                }
                Dever::log('server_error', $msg);
            } finally {
                self::endRequest();
            }
        };
        \Workerman\Worker::runAll();
    }
    public static function fpm()
    {
        self::bootstrap();
        self::beginRequest();
        try {
            $result = self::run();
            header('Content-Type: application/json');
            header('Content-Length: ' . strlen($result));
            print_r($result);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (self::isJsonString($msg)) {
                print_r($msg);
            } else {
                try {
                    Dever::get(Dever\Debug::class)->exception_handler($e);
                } catch (\Throwable $inner) {
                    print_r($inner->getMessage());
                }
            }
        } finally {
            self::endRequest();
        }
    }

    public static function bootstrap()
    {
        if (self::$bootstrapped) {
            return;
        }
        spl_autoload_register(['Dever', 'autoload']);
        self::$bootstrapped = true;
    }

    public static function request(callable $callback)
    {
        self::beginRequest();
        try {
            return $callback();
        } finally {
            self::endRequest();
        }
    }

    public static function run($data = null)
    {
        self::get(Dever\Debug::class)->init();
        $route = self::get(Dever\Route::class)->get($data);
        unset($data);
        //$out = self::out();
        self::get(Dever\Project::class)->register();
        $settings = self::setting();
        if (!empty($settings['cache'])) {
            $index = DEVER_APP_NAME . DIRECTORY_SEPARATOR . $route['l'];
            if (isset($settings['cache'][$index])) {
                $expire = $settings['cache'][$index];
                if (isset($route['shell'])) {
                    unset($route['shell']);
                }
                $key = md5(DEVER_APP_NAME . http_build_query($route));
                if ($result = self::cache($key)) {
                    return self::out()->success($result);
                }
            }
        }
        if ($route['l'] && strpos($route['l'], '.')) {
            list($class, $method) = explode('.', $route['l']);
            $class = strtr(ucwords(strtr($class, '/', ' ')), ' ', '\\');
            if (strpos($class, 'Manage') === 0) {
                $class = str_replace('Manage\\', '', $class);
                $class = DEVER_APP_NAME . '\\Manage\\Api\\' . $class;
            } else {
                $class = DEVER_APP_NAME . '\\Api\\' . $class;
            }
            $result = self::out()->success(self::load($class)->loadDevelop($method, self::input(), true));
        } else {
            $result = self::out()->success('ok');
        }
        if (isset($expire)) {
            self::cache($key, $result, $expire);
        }
        return $result;
    }

    protected static function beginRequest()
    {
        self::$counter++;
        self::$requestId = 'req_' . getmypid() . '_' . self::$counter . '_' . microtime(true);
        self::$requestInstances[self::$requestId] = [];
        self::$commit[self::$requestId] = false;
        self::$data[self::$requestId] = [];
    }
    public static function endRequest()
    {
        if (self::$requestId) {
            self::clearRequestState(self::$requestId);
        }
        self::$requestId = null;
        if ((self::$counter % 50) === 0 && function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }
    protected static function clearRequestState($requestId)
    {
        if (isset(self::$requestInstances[$requestId])) {
            unset(self::$requestInstances[$requestId]);
        }
        if (isset(self::$commit[$requestId])) {
            unset(self::$commit[$requestId]);
        }
        if (isset(self::$data[$requestId])) {
            unset(self::$data[$requestId]);
        }
    }
    public static function getCommit()
    {
        if (!self::$requestId) {
            return true;
        }
        return empty(self::$commit[self::$requestId]);
    }
    public static function setCommit()
    {
        if (!self::$requestId) {
            self::beginRequest();
        }
        self::$commit[self::$requestId] = true;
    }
    public static function setData($key, $data)
    {
        if (!self::$requestId) {
            self::beginRequest();
        }
        return self::$data[self::$requestId][$key] = $data;
    }
    public static function getData($key)
    {
        if (!self::$requestId) {
            return false;
        }
        return self::$data[self::$requestId][$key] ?? false;
    }
    public static function resolve($class)
    {
        if (isset(self::$bindings[$class])) {
            $binding = self::$bindings[$class];
            $concrete = $binding['concrete'];
            if ($concrete instanceof \Closure) {
                $object = $concrete();
            } elseif (is_string($concrete)) {
                $object = self::make($concrete);
            } else {
                $object = $concrete;
            }
            if ($binding['shared']) {
                self::$instances[$class] = $object;
            }
            return $object;
        }
        return self::build($class);
    }
    public static function build($class)
    {
        if (!isset(self::$reflectionCache[$class])) {
            self::$reflectionCache[$class] = new \ReflectionClass($class);
        }
        $refClass = self::$reflectionCache[$class];
        $constructor = $refClass->getConstructor();
        if (!$constructor || $constructor->getNumberOfParameters() === 0) {
            return new $class();
        }
        if (!isset(self::$dependencyCache[$class])) {
            $dependencies = [];
            foreach ($constructor->getParameters() as $param) {
                $type = $param->getType();
                if ($type && !$type->isBuiltin()) {
                    $dependencies[] = ['class' => $type->getName()];
                } elseif ($param->isDefaultValueAvailable()) {
                    $dependencies[] = ['value' => $param->getDefaultValue()];
                } else {
                    throw new \Exception("无法解析 {$class} 的依赖参数 \${$param->getName()}");
                }
            }
            self::$dependencyCache[$class] = $dependencies;
        }
        $dependencies = [];
        foreach (self::$dependencyCache[$class] as $dependency) {
            if (isset($dependency['class'])) {
                $dependencies[] = self::get($dependency['class']);
            } else {
                $dependencies[] = $dependency['value'];
            }
        }
        return $refClass->newInstanceArgs($dependencies);
    }
    public static function bind($abstract, $concrete, $shared = false)
    {
        self::$bindings[$abstract] = compact('concrete', 'shared');
    }
    public static function get($class, $requestScope = null, $key = null)
    {
        if ($key === null) $key = $class;
        if ($requestScope === null) {
            $requestScope = self::shouldRequestScope($key, $class);
        }
        if ($requestScope) {
            if (!self::$requestId) {
                self::beginRequest();
            }
            if (!isset(self::$requestInstances[self::$requestId][$key])) {
                self::$requestInstances[self::$requestId][$key] = self::resolve($class);
            }
            return self::$requestInstances[self::$requestId][$key];
        } else {
            if (!isset(self::$instances[$key])) {
                self::$instances[$key] = self::resolve($class);
            }
            return self::$instances[$key];
        }
    }
    protected static function shouldRequestScope($key, $class)
    {
        if (isset(self::$requestScopedClasses[$key])) {
            return self::$requestScopedClasses[$key];
        }
        if (isset(self::$requestScopedClasses[$class])) {
            return self::$requestScopedClasses[$class];
        }
        return false;
    }
    public static function registerRequestScoped($class, $flag = true)
    {
        self::$requestScopedClasses[$class] = $flag;
    }
    public static function make($class)
    {
        return self::resolve($class);
    }
    public static function autoload($class)
    {
        if (strpos($class, 'Dever') === 0 || strpos($class, 'Workerman') === 0) {
            require_once DEVER_PATH . 'src/' . str_replace('\\', '/', $class) . '.php';
        } else {
            $temp = explode('\\', $class, 2);
            if (empty($temp[1])) {
                self::error($class . ' error');
            }
            [$app, $name] = $temp;
            $name = strtr($name, '\\', '/');
            $project = self::project($app, false);
            if ($project) {
                if (strpos($project['path'], 'http') === 0) {
                    $class = $project;
                } else {
                    if (strpos($name, 'Manage') === 0) {
                        $path = 'manage';
                        $name = str_replace('Manage/', '', $name);
                    } else {
                        $path = 'app';
                    }
                    require_once $project['path'] . $path . DIRECTORY_SEPARATOR . $name . '.php';
                }
            }
        }
    }
    public static function call($class, $param = [])
    {
        if (!is_array($param)) $param = [$param];
        if (strpos($class, '?')) {
            list($class, $temp) = explode('?', $class);
            parse_str($temp, $temp);
            foreach ($temp as $k => $v) {
                array_unshift($param, $v);
            }
        }
        
        list($class, $method) = explode('.', $class);
        $class = strtr($class, '/', '\\');
        return self::load($class)->$method(...$param);
    }
    public static function load($class)
    {
        return self::get(Dever\App::class, true, 'app:' . $class)->__initialize($class);
    }
    public static function db($table, $store = 'default', $partition = false, $path = 'table')
    {
        return self::get(Dever\Model::class, true, 'model:' . $table)->__initialize($table, $store, $partition, $path);
    }
    public static function option($table, $type = '', $where = [])
    {
        $data = Dever::db($table)->select($where);
        if ($type) {
            if (is_bool($type)) {
                $type = '不选择';
            }
            $default = [0 => ['id' => -1, 'name' => $type]];
            $data = array_merge($default, $data);
        }
        return $data;
    }
    public static function field($table, $id, $default = '无', $key = 'name')
    {
        if ($id && $id > 0) {
            $info = Dever::db($table)->find($id);
            return $info[$key];
        }
        return $default;
    }
    # 定义常用方法，这里不用__callStatic
    public static function input(...$args)
    {
        return self::get(Dever\Route::class)->input(...$args);
    }
    public static function url(...$args)
    {
        return self::get(Dever\Route::class)->url(...$args);
    }
    public static function host(...$args)
    {
        return self::get(Dever\Route::class)->host(...$args);
    }
    public static function debug(...$args)
    {
        return self::get(Dever\Debug::class)->add(...$args);
    }
    public static function page(...$args)
    {
        return self::get(Dever\Paginator::class)->get(...$args);
    }
    public static function config(...$args)
    {
        $result = self::get(Dever\Config::class)->get(...$args);
        if ($args && $args[0] === 'setting') {
            self::$settingCache[self::projectCacheKey()] = $result;
        }
        return $result;
    }
    public static function setting($key = null, $default = null)
    {
        $cacheKey = self::projectCacheKey();
        if (!isset(self::$settingCache[$cacheKey])) {
            self::$settingCache[$cacheKey] = self::config('setting');
        }
        $settings = self::$settingCache[$cacheKey];
        if ($key === null) {
            return $settings;
        }
        return $settings[$key] ?? $default;
    }
    protected static function projectCacheKey()
    {
        if (defined('DEVER_PROJECT_PATH')) {
            return DEVER_PROJECT_PATH;
        }
        return 'global';
    }
    public static function project(...$args)
    {
        return self::get(Dever\Project::class)->load(...$args);
    }
    public static function log(...$args)
    {
        return self::get(Dever\Log::class)->add(...$args);
    }

    public static function out()
    {
        return self::get(Dever\Output::class);
    }
    public static function error(...$args)
    {
        return self::out()->error(...$args);
    }
    public static function success(...$args)
    {
        return self::out()->success(...$args);
    }
    public static function apply($file)
    {
        [$app, $file] = explode('/', $file, 2);
        $project = Dever::project($app);
        require_once $project['path'] . $file . '.php';
    }
    public static function session(...$args)
    {
        return Dever\Session::oper(...$args);
    }
    public static function view(...$args)
    {
        return self::get(Dever\View::class)->show(...$args);
    }
    public static function file(...$args)
    {
        return self::get(Dever\File::class)->get(...$args);
    }
    public static function data()
    {
        return self::get(Dever\File::class)->data();
    }
    
    public static function rule(...$args)
    {
        return Dever\Helper\Rule::get(...$args);
    }
    public static function number(...$args)
    {
        return Dever\Helper\Math::format(...$args);
    }
    public static function math(...$args)
    {
        $key = $args[0];
        $args = array_slice($args, 1);
        return Dever\Helper\Math::$key(...$args);
    }
    public static function curl(...$args)
    {
        return self::get(Dever\Helper\Curl::class, false)->load(...$args);
    }
    public static function cache($key, $value = false)
    {
        if (self::setting('redis')) {
            if ($value) {
                if ($value == 'delete') {
                    return \Dever\Helper\Redis::delete($key);
                }
                return \Dever\Helper\Redis::set($key, self::json_encode($value));
            } else {
                return self::json_decode(\Dever\Helper\Redis::get($key));
            }
        }
        return false;
    }
    public static function shell($value)
    {
        return self::check(self::input('shell'), $value);
    }
    public static function store($store = 'default', $partition = false)
    {
        $database = self::setting('database', []);
        if (!isset($database[$store])) {
            throw new \RuntimeException('database store not configured: ' . $store);
        }
        $setting = $database[$store];
        $class = 'Dever\\Store\\' . $setting['type'];
        return $class::getInstance($store, $setting, $partition);
    }
    public static function in_array($array, $value, $key = 'id', $show = 'name')
    {
        $column = array_column($array, $key);
        if ($column) {
            $index = array_search($value, $column);
            if ($index >= 0) {
                return $array[$index][$show];
            }
        }
        return false;
    }
    public static function issets($input, $value = false)
    {
        if (isset($input[$value])) {
            if (is_string($input[$value]) && !strlen($input[$value])) {
                return false;
            }
            return $input[$value];
        }
        return false;
    }
    public static function check($var, $find)
    {
        if (is_array($var)) {
            $var = implode(',', $var);
        }
        return strpos(',' . $var . ',', ',' . $find . ',') !== false;
    }

    private static function isJsonString($value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        $trim = trim($value);
        if ($trim === '' || ($trim[0] !== '{' && $trim[0] !== '[')) {
            return false;
        }
        json_decode($trim, true);
        return json_last_error() === JSON_ERROR_NONE;
    }
    public static function json_encode($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    public static function json_decode($value)
    {
        return json_decode($value, true);
    }
    public static function array_order($array, $key, $sort)
    {
        $reorder = array_column($array, $key);
        array_multisort($reorder, $sort, $array);
        return $array;
    }
    public static function uuid()
    {
        $ts = (int) floor(microtime(true) * 1000); // ms
        $rand = random_bytes(10);                 // 80-bit random

        // 48-bit timestamp
        $timeHex = str_pad(dechex($ts), 12, '0', STR_PAD_LEFT);
        $timeBytes = hex2bin($timeHex);

        $data = $timeBytes . $rand; // 16 bytes

        // set version 7 (0b0111)
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x70);
        // set RFC4122 variant (10xxxxxx)
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    public static function id()
    {
        $charid = strtoupper(md5(uniqid(mt_rand(), true)));
        return substr($charid, 0, 8) . substr($charid, 8, 4) . substr($charid, 12, 4) . substr($charid, 16, 4) . substr($charid, 20, 12);
    }
    public static function is_file($file)
    {
        if (strtolower(substr($file, 0, 4)) == 'http') {
            $header = get_headers($file, true);
            return isset($header[0]) && (strpos($header[0], '200') || strpos($header[0], '304'));
        } else {
            return is_file($file);
        }
    }
    public static function subdir($dir)
    {
        return array_filter(scandir($dir), function($file) use ($dir) {
            return is_dir($dir . '/' . $file) && $file !== '.' && $file !== '..';
        });
    }
    public static function cdate(string $str = 'Y-m-d H:i:s', int $ms, string $tz = 'Asia/Shanghai')
    {
        $dt = DateTime::createFromFormat('U.u', sprintf('%.3f', $ms / 1000));
        $dt->setTimezone(new DateTimeZone($tz));
        return $dt->format($str);
    }
}
