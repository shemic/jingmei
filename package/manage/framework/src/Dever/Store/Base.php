<?php namespace Dever\Store;
use Dever;
class Base
{
    protected $read;
    protected $update;
    protected $type;
    protected $pools = ['read' => null, 'update' => null];
    public static $instance = [];
    public static function getInstance($key, $setting, $partition)
    {
        if ($key == false) {
            print_r(static::$instance);die;
        }
        if (isset($partition['create']) && $partition['create'] && isset($partition['database']) && $partition['database']) {
            $key .= $partition['database'];
            $setting['name'] .= '_' . $partition['database'];
        }
        if (empty(static::$instance[$key])) {
            static::$instance[$key] = new static($setting);
        }
        return static::$instance[$key];
    }
    public function __construct($setting)
    {
        $defaultPool = $setting['pool'] ?? null;
        if (isset($setting[0])) {
            $this->initChannel('read', $setting[0], $defaultPool, 'read');
            $writeSetting = $setting[1] ?? $setting[0];
            $this->initChannel('update', $writeSetting, $defaultPool, 'write');
        } else {
            $this->initChannel('read', $setting, $defaultPool, 'default');
            if ($this->pools['read']) {
                $this->pools['update'] = $this->pools['read'];
            } else {
                $this->update =&$this->read;
            }
        }
    }
    protected function error($msg)
    {
        Dever::out()->error(Dever::json_encode($msg));
    }
    protected function log($msg)
    {
        Dever::debug($msg, $this->type);
    }
    public function bsql(&$sql, $bind)
    {
        foreach ($bind as $k => $v) {
            if (strstr($sql, 'select') && strpos($v, ',')) {
                $v = 'in('.$v.')';
            } else {
                $v = '\'' . $v . '\'';
            }
            $sql = str_replace($k, $v, $sql);
        }
    }
    protected function initChannel($channel, $setting, $defaultPool, $label)
    {
        $poolConfig = $this->normalizePoolConfig($setting['pool'] ?? $defaultPool, $label, $setting);
        $connectSetting = $this->sanitizeConnectionSetting($setting);
        if ($poolConfig) {
            $this->pools[$channel] = new Pool(function() use ($connectSetting) {
                return $this->connect($connectSetting);
            }, $poolConfig, function($connection) {
                $this->destroyConnection($connection);
            });
        } else {
            $this->$channel = $this->connect($connectSetting);
        }
    }
    protected function normalizePoolConfig($config, $label, $setting)
    {
        if ($config === null || $config === false) {
            return null;
        }
        if ($config === true) {
            $config = [];
        }
        if (!is_array($config)) {
            return null;
        }
        $enabled = array_key_exists('enable', $config) ? $config['enable'] : defined('DEVER_SERVER');
        if (!$enabled) {
            return null;
        }
        $config = array_merge([
            'min' => 1,
            'max' => 10,
            'idle_time' => 60,
            'wait_timeout' => 3,
        ], $config);
        if ($config['max'] < $config['min']) {
            $config['max'] = $config['min'];
        }
        unset($config['enable']);
        $config['label'] = $config['label'] ?? ($this->poolLabel($label, $setting));
        return $config;
    }
    protected function poolLabel($label, $setting)
    {
        $type = $setting['pdo_type'] ?? $setting['type'] ?? 'pdo';
        $host = $setting['host'] ?? 'localhost';
        return trim($type . ' ' . $label . ' ' . $host);
    }
    protected function sanitizeConnectionSetting($setting)
    {
        if (isset($setting['pool'])) {
            unset($setting['pool']);
        }
        return $setting;
    }
    protected function acquireConnection($channel)
    {
        if ($this->pools[$channel]) {
            return $this->pools[$channel]->acquire();
        }
        return $this->$channel;
    }
    protected function releaseConnection($channel, $connection, $broken = false)
    {
        if ($this->pools[$channel]) {
            $this->pools[$channel]->release($connection, $broken);
        }
    }
    protected function destroyConnection($connection)
    {
        if ($connection instanceof \PDO) {
            $connection = null;
            return;
        }
        if (is_object($connection) && method_exists($connection, 'close')) {
            $connection->close();
        }
    }
}
