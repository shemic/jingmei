<?php namespace Dever;
use Dever;
use Dever\Helper\Secure;
class Session
{
    private static $start = false;
    private static $save;
    private $key = '';
    private $prefix = 'dever_';
    private $method = 'session';
    public static function start()
    {
        if (!self::$start) {
            @header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
            @ini_set('session.gc_maxlifetime', 86400);
            if (isset(Dever::config('setting')['session']) && $session = Dever::config('setting')['session']) {
                ;
                if (isset($session['host'])) {
                    $link = 'tcp://'.$session['host'].':'.$session['port'] . '?persistent=1&weight=1&timeout=1&retry_interval=15';
                    if (isset($session['password']) && $session['password']) {
                        $link .= '&auth='.$session['password'];
                    }
                    @ini_set('session.save_handler', $session['type']);
                    @ini_set('session.save_path', $link);
                } elseif (isset($session['path'])) {
                    @ini_set('session.save_path', $session['path']);
                }
                if (isset($session['cookie'])) {
                    ini_set('session.cookie_domain', $session['cookie']);
                }
            }
            @session_start();
            self::$start = true;
        }
    }
    public static function oper($key, $value = false, $timeout = 86400, $type = 'session', $encode = true)
    {
        if (empty(self::$save[$key])) {
            self::$save[$key] = new self(DEVER_APP_NAME, $type);
        }
        if ($value) {
            if ($value == 'delete') {
                return self::$save[$key]->un($key);
            } else {
                return self::$save[$key]->set($key, $value, $timeout, $encode);
            }
        } else {
            return self::$save[$key]->get($key, $encode);
        }
    }
    public function __construct($key = false, $method = 'session')
    {
        if (Dever::get(Dever\Route::class)->server['type'] == 'cmd') {
            $method = 'file';
        } else {
            Session::start();
        }
        if ($key) {
            $this->key = $key;
        }
        if ($method) {
            $this->method = $method;
        }
        $this->method = ucwords($this->method);
        $this->key($this->key);
        return $this;
    }
    public function set($key, $value, $time = 3600, $encode = true)
    {
        $key = DEVER_PROJECT . '_' . $key;
        if ($encode) {
            $value = Secure::encode(serialize($value), $this->key);
        }
        $method = '_set' . $this->method;
        $this->$method($key, $value, $time);
        return $value;
    }
    public function get($key, $encode = true)
    {
        $key = DEVER_PROJECT . '_' . $key;
        $method = '_get' . $this->method;
        $value = $this->$method($key);
        if ($encode) {
            $value = unserialize(Secure::decode($value, $this->key));
        }
        return $value;
    }
    public function un($key)
    {
        $key = DEVER_PROJECT . '_' . $key;
        $method = '_unset' . $this->method;
        return $this->$method($key);
    }
    private function key($key)
    {
        $this->key = $this->prefix . '_' . $this->method . '_' . $key;
    }
    private function _setCookie($key, $value, $time = 3600)
    {
        return setCookie($this->prefix . $key, $value, time() + $time, '/', '');
    }
    private function _getCookie($key)
    {
        if (isset($_COOKIE[$this->prefix . $key])) {
            return $_COOKIE[$this->prefix . $key];
        }
        return false;
    }
    private function _unsetCookie($key)
    {
        return setCookie($this->prefix . $key, false, time() - 3600, '/', '');
    }
    private function _setSession($key, $value, $time = 3600)
    {
        setCookie(session_name(), session_id(), time() + $time, '/', ''); 
        return $_SESSION[$this->prefix . $key] = $value;
    }
    private function _getSession($key)
    {
        if ((isset($_SESSION[$this->prefix . $key]) && $_SESSION[$this->prefix . $key])) {
            return $_SESSION[$this->prefix . $key];
        }
        return false;
    }
    private function _unsetSession($key)
    {
        unset($_SESSION[$this->prefix . $key]);
        return true;
    }
    private function _initFile()
    {
        $this->id = md5($this->key);
        $this->file = File::get('session/' . $this->id);
        if (is_file($this->file)) {
            $this->data = unserialize(file_get_contents($this->file));
            return;
        }
        file_put_contents($this->file, null);
    }
    private function _setFile($key, $value, $time = 3600)
    {
        $this->_initFile();
        $key = $this->prefix . $key;
        $this->data[$key] = $value;
        file_put_contents($this->file, serialize($this->data));
        return $value;
    }
    private function _getFile($key)
    {
        $this->_initFile();
        $key = $this->prefix . $key;
        if (isset($this->data[$key]) && $this->data[$key]) {
            return $this->data[$key];
        }
        return false;
    }
    private function _unsetFile($key)
    {
        $this->_initFile();
        $key = $this->prefix . $key;
        unset($this->data[$key]);
        file_put_contents($this->file, serialize($this->data));
        return true;
    }
}