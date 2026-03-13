<?php namespace Dever;
class Config
{
    protected $data = [];
    public function get($key = 'setting', $data = false)
    {
        if (empty($this->data[$key])) {
            $path = DEVER_PROJECT_PATH . 'config' . DIRECTORY_SEPARATOR;
            $env = $this->env($path);
            $file = $path . $key . '.php';
            $this->data[$key] = [];
            if (is_file($file)) {
                $this->data[$key] = include($file);
            }
        }
        if ($data) {
            $this->data[$key] = array_merge($this->data[$key], $data);
        }
        return $this->data[$key];
    }
    protected function env($path)
    {
        if (empty($_SERVER['DEVER_ENV_NAME'])) {
            $_SERVER['DEVER_ENV_NAME'] = 'localhost';
        }
        if (empty($_SERVER['DEVER_ENV_PATH'])) {
            $_SERVER['DEVER_ENV_PATH'] = $path . 'env' . DIRECTORY_SEPARATOR;
        }
        $file = $_SERVER['DEVER_ENV_PATH'] . $_SERVER['DEVER_ENV_NAME'] . '.php';
        if (is_file($file)) {
            return include($file);
        }
    }
}