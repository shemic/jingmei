<?php namespace Dever\Helper;

use Dever\Debug;

class Redis
{
    private static $handle;
    private static $expire = 3600;

    public static function connect()
    {
        if (!self::$handle) {
            $config = \Dever::config('setting')['redis'] ?? false;
            if (!$config) {
                \Dever::error('redis error');
            }

            self::$expire = $config['expire'];
            self::$handle = new \Redis;
            self::$handle->pconnect($config["host"], $config["port"]);

            if (!empty($config['password'])) {
                self::$handle->auth($config['password']);
            }

            // 读超时不限制，配合 keepalive 降低空闲断链风险
            self::$handle->setOption(\Redis::OPT_READ_TIMEOUT, -1);
            if (defined('\Redis::OPT_TCP_KEEPALIVE')) {
                self::$handle->setOption(\Redis::OPT_TCP_KEEPALIVE, 60);
            }
        }
        return self::$handle;
    }

    public static function reconnect()
    {
        if (self::$handle) {
            try {
                self::$handle->close();
            } catch (\Throwable $e) {
                // ignore close failure
            }
            self::$handle = null;
        }

        return self::connect();
    }

    /** -------------------------
     *  KV 基础操作
     * ------------------------ */
    public static function get($key)
    {
        return self::connect()->get($key);
    }

    public static function set($key, $value, $expire = 0)
    {
        self::expire($expire);
        return self::connect()->set($key, $value, self::$expire);
    }

    public static function del($key)
    {
        return self::connect()->del($key);
    }

    /** -------------------------
     *  分布式锁
     * ------------------------ */
    public static function lock($key, $value, $expire = 0)
    {
        self::expire($expire);
        return self::connect()->set($key, $value, ['NX', 'EX' => $expire]);
    }

    public static function unlock($key, $value)
    {
        $script = <<<LUA
        if redis.call("get", KEYS[1]) == ARGV[1] then
            return redis.call("del", KEYS[1])
        else
            return 0
        end
LUA;
        return self::connect()->eval($script, [$key, $value], 1);
    }

    /** -------------------------
     *  自增自减
     * ------------------------ */
    public static function incr($key, $value = false)
    {
        return $value ? self::connect()->incrBy($key, $value) : self::connect()->incr($key);
    }

    public static function decr($key, $value = false)
    {
        return $value ? self::connect()->decrBy($key, $value) : self::connect()->decr($key);
    }

    /** -------------------------
     *  列表队列
     * ------------------------ */
    public static function push($key, $value)
    {
        return self::connect()->lpush($key, $value);
    }

    public static function pop($key)
    {
        $data = self::connect()->brPop($key, 10);
        if ($data) {
            return $data[1] ?? $data;
        } else {
            $pong = self::connect()->ping();
            if ($pong != '+PONG') {
                throw new \Exception('Redis ping failure!', 500);
            }
            usleep(100000);
        }
        return false;
    }

    public static function len($key)
    {
        return self::connect()->lLen($key);
    }

    /** -------------------------
     *  Hash 操作
     * ------------------------ */
    public static function hGet($key, $hkey = false)
    {
        if ($hkey) {
            return self::connect()->hGet($key, $hkey);
        }
        return self::connect()->hGetAll($key);
    }

    public static function hDel($key, $hkey)
    {
        return self::connect()->hDel($key, $hkey);
    }

    public static function hExists($key, $hkey)
    {
        return self::connect()->hExists($key, $hkey);
    }

    public static function hKeys($key)
    {
        return self::connect()->hKeys($key);
    }

    public static function hSet($key, $hkey, $value)
    {
        $res = self::connect()->hSet($key, $hkey, $value);
        return $res;
    }

    public static function hMSet($key, $value)
    {
        $res = self::connect()->hMSet($key, $value);
        return $res;
    }

    # 原子操作
    public static function hOper($key, $field, $amount)
    {
        $lua = <<<LUA
    local balance_raw = redis.call("HGET", KEYS[1], KEYS[2])
    if not balance_raw then
        return -1
    end

    local balance = tonumber(balance_raw)
    local change = tonumber(ARGV[1])
    local new_balance = balance + change

    if new_balance < 0 then
        return 0
    end

    local formatted = string.format("%.2f", new_balance)
    redis.call("HSET", KEYS[1], KEYS[2], formatted)
    return formatted
    LUA;

        return self::connect()->eval($lua, [$key, $field, $amount], 2);
    }

    public static function oper($key, $amount)
    {
        $lua = <<<LUA
    local balance_raw = redis.call("GET", KEYS[1])
    if not balance_raw then
        return -1   --余额未初始化
    end

    local balance = tonumber(balance_raw)
    local change = tonumber(ARGV[1])
    local new_balance = balance + change

    if new_balance < 0 then
        return 0    --余额不足
    end

    local formatted = string.format("%.2f", new_balance)
    redis.call("SET", KEYS[1], formatted)
    return formatted
    LUA;

        return self::connect()->eval($lua, [$key, $amount], 1);
    }


    public static function xAdd($key, $col, $value)
    {
        return self::connect()->xAdd($key, $col, $value);
    }

    public static function xRead($search, $num, $time)
    {
        return self::connect()->xRead($search, $num, $time);
    }

    /** -------------------------
     *  关闭连接
     * ------------------------ */
    public static function close()
    {
        return self::connect()->close();
    }

    /** -------------------------
     *  设置过期
     * ------------------------ */
    private static function expire($expire)
    {
        if ($expire) {
            self::$expire = $expire;
        }
    }
}
