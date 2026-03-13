<?php namespace Dever\Store;

class Pool
{
    protected $creator;
    protected $destroyer;
    protected $min;
    protected $max;
    protected $idleTime;
    protected $waitTimeout;
    protected $idle = [];
    protected $using = 0;
    protected $total = 0;
    protected $label;

    public function __construct(callable $creator, array $config = [], callable $destroyer = null)
    {
        $this->creator = $creator;
        $this->destroyer = $destroyer;
        $this->min = max(0, (int)($config['min'] ?? 0));
        $this->max = (int)($config['max'] ?? ($this->min ?: 1));
        $this->idleTime = (int)($config['idle_time'] ?? 60);
        $this->waitTimeout = (float)($config['wait_timeout'] ?? 3);
        $this->label = $config['label'] ?? 'database';
        if ($this->max <= 0) {
            $this->max = 1;
        }
        if ($this->max < $this->min) {
            $this->max = $this->min;
        }
        $this->warm();
    }

    protected function warm(): void
    {
        while ($this->total < $this->min) {
            $this->idle[] = ['resource' => call_user_func($this->creator), 'time' => microtime(true)];
            $this->total++;
        }
    }

    public function acquire()
    {
        if ($connection = $this->shiftIdle()) {
            $this->using++;
            return $connection;
        }
        if ($this->total < $this->max) {
            $this->using++;
            $this->total++;
            return call_user_func($this->creator);
        }
        if ($connection = $this->waitForConnection()) {
            $this->using++;
            return $connection;
        }
        throw new \RuntimeException($this->label . ' connection pool exhausted');
    }

    protected function shiftIdle()
    {
        if (!$this->idle) {
            return null;
        }
        $now = microtime(true);
        while ($item = array_pop($this->idle)) {
            if ($this->idleTime > 0 && ($now - $item['time']) > $this->idleTime && $this->total > $this->min) {
                $this->destroy($item['resource']);
                $this->total--;
                continue;
            }
            return $item['resource'];
        }
        return null;
    }

    protected function waitForConnection()
    {
        if ($this->waitTimeout <= 0) {
            return null;
        }
        $start = microtime(true);
        $interval = 1000;
        do {
            if ($connection = $this->shiftIdle()) {
                return $connection;
            }
            usleep($interval);
        } while ((microtime(true) - $start) < $this->waitTimeout);
        return null;
    }

    public function release($connection, $broken = false): void
    {
        if ($broken) {
            $this->destroy($connection);
            $this->total = max(0, $this->total - 1);
        } else {
            $this->idle[] = ['resource' => $connection, 'time' => microtime(true)];
        }
        $this->using = max(0, $this->using - 1);
        if ($this->total < $this->min) {
            $this->warm();
        }
    }

    protected function destroy($connection): void
    {
        if ($this->destroyer) {
            call_user_func($this->destroyer, $connection);
            return;
        }
        if (is_object($connection) && method_exists($connection, 'close')) {
            $connection->close();
        }
    }

    public function stats(): array
    {
        return [
            'label' => $this->label,
            'total' => $this->total,
            'idle' => count($this->idle),
            'using' => $this->using,
            'max' => $this->max,
        ];
    }
}
