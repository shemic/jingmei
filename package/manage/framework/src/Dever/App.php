<?php namespace Dever;
use Dever;
class App
{
    public $app = '';
    public $name = '';
    public $class = null;
    public function __initialize($class)
    {
        if ($class) {
            if (strpos($class, '/')) {
                $temp = explode('/', $class, 2);
                if (empty($temp[1])) {
                    Dever::error($class . ' error');
                }
                [$this->app, $this->name] = $temp;
                $class = strtr($class, '/', '\\');
            } else {
                $temp = explode('\\', $class, 2);
                if (empty($temp[1])) {
                    Dever::error($class . ' error');
                }
                [$this->app, $this->name] = $temp;
                $this->name = strtr($this->name, '\\', '/');
            }
            $project = Dever::project($this->app, false);
            if ($project) {
                if (strpos($project['path'], 'http') === 0) {
                    $this->class = $project;
                } else {
                    if (strpos($this->name, 'Manage') === 0) {
                        Dever::load(\Manage\Lib\Auth::class);
                        $this->name = str_replace('Manage/', '', $this->name);
                    }
                    //require_once $project['path'] . $path . DIRECTORY_SEPARATOR . $this->name . '.php';
                    
                    $this->class = Dever::get($class, true);
                }
            }
        }
        return $this;
    }
    public function __call($method, $param)
    {
        return $this->loadDevelop($method, $param);
    }
    public function loadDevelop($method, $param)
    {
        if (is_array($this->class)) {
            return $this->loadServer();
        }
        $this->ensureClass();
        if (!method_exists($this->class, $method)) {
            Dever::error($method . ' error');
        }
        if (strpos($this->name, 'Api') === 0) {
            if (method_exists($this->class, $method . '_secure')) {
                $key = false;
                $token = $method . '_token';
                if (method_exists($this->class, $token)) {
                    $key = $this->class->{$token}();
                }
                \Dever\Helper\Secure::check($param, 300, $key);
            }
            if ($param && is_array($param) && !isset($param[0])) {
                $reflectionMethod = new \ReflectionMethod($this->class, $method);
                $data = $reflectionMethod->getParameters();
                $result = [];
                foreach ($data as $k => $v) {
                    $name = $v->name;
                    if (isset($param[$name])) {
                        $result[] = $param[$name];
                    }
                }
                $param = $result;
            } else {
                if (!is_array($param)) {
                    $param = [$param];
                }
            }
        }
        return $this->loadDevelopCommit($method, $param);
    }
    private function loadDevelopCommit($method, $param)
    {
        $this->ensureClass();
        if (method_exists($this->class, $method . '_cmd') && Dever::get(Dever\Route::class)->server['type'] != 'cmd') {
            Dever::error('route error');
        }
        if (method_exists($this->class, $method . '_commit') && Dever::getCommit()) {
            $db = end(\Dever\Store\Pdo::$instance);
            if (!$db) {
                $db = Dever::store();
            }
            Dever::setCommit();
            $db->begin();
            try {
                $data = $this->loadDevelopMethod($method, $param);
                $db->commit();
                return $data;
            } catch (\Exception $e) {
                if (strpos($e->getMessage(), 'There is no active transaction') !== false) {
                    return $data ?? true;
                }
                if ($db->inTransaction()) {
                    $db->rollback();
                }
                $trace = $e->getTrace();
                Dever::get(Debug::class)->trace($trace);
                throw $e;
            }
        } else {
            return $this->loadDevelopMethod($method, $param);
        }
    }
    private function loadServer()
    {
        return 'error';
    }
    private function loadDevelopMethod($method, $param)
    {
        $this->ensureClass();
        $data = $this->class->$method(...$param);
        Dever::get(Debug::class)->lib($this->class, $method);
        return $data;
    }

    private function ensureClass()
    {
        if (!$this->class || !is_object($this->class)) {
            Dever::error(($this->name ?: 'class') . ' error');
        }
    }
}
