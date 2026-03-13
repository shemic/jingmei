<?php namespace Dever\Store;

use Dever;
use Dever\Debug;
use Dever\Sql;

class Pdo extends Base
{
    protected $tool;
    protected $transactionConnection;

    // remember driver
    protected string $pdoType = 'mysql';

    protected function connect($setting)
    {
        $this->tool = Dever::get(Sql::class);
        $this->type = $setting['type'];

        if (strpos($setting['host'], ':') !== false) {
            [$setting['host'], $setting['port']] = explode(':', $setting['host'], 2);
        }

        if (empty($setting['pdo_type'])) {
            $setting['pdo_type'] = 'mysql';
        }
        $this->pdoType = $setting['pdo_type'];

        if (empty($setting['charset'])) {
            $setting['charset'] = 'utf8mb4';
        }
        if (empty($setting['collation'])) {
            $setting['collation'] = 'utf8mb4_general_ci';
        }

        // sync SQL dialect: 1=mysql 2=pgsql
        $this->tool->setType($this->pdoType === 'pgsql' ? 2 : 1);

        // DSN
        $dsn = $this->pdoType . ':host=' . $setting['host'] . ';port=' . $setting['port'] . ';dbname=' . $setting['name'];

        if ($this->pdoType === 'mysql') {
            // MySQL DSN should use charset, not collation
            $dsn .= ';charset=' . $setting['charset'];
        } elseif ($this->pdoType === 'pgsql') {
            // Optional; usually not needed, but safe
            $dsn .= ";options='--client_encoding=UTF8'";
        }

        try {
            $persistent = !empty($setting['persistent']);
            $handle = new \PDO($dsn, $setting['user'], $setting['pwd'], [
                \PDO::ATTR_PERSISTENT => $persistent,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_STRINGIFY_FETCHES => false,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            Dever::debug('db ' . $setting['host'] . ' connected', $setting['type']);
            return $handle;

        } catch (\PDOException $e) {
            $msg = $e->getMessage();

            // MySQL: Unknown database
            // PG: database "... " does not exist
            if (stristr($msg, 'Unknown database') || stristr($msg, 'does not exist')) {
                $this->create($setting);
                return $this->connect($setting);
            }

            Dever::out()->error($msg);
        }
    }

    private function create($setting)
    {
        if (($setting['pdo_type'] ?? 'mysql') === 'pgsql') {
            // PG: connect to postgres then create db
            $dsn = "pgsql:host={$setting['host']};port={$setting['port']};dbname=postgres";
            $pdo = new \PDO($dsn, $setting['user'], $setting['pwd'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);

            // defensive: allow only safe db name
            $db = preg_replace('/[^a-zA-Z0-9_]/', '', $setting['name']);
            $pdo->exec('CREATE DATABASE "' . $db . '"');
            return;
        }

        // MySQL original behavior
        $method = 'mysql';
        if (function_exists('mysqli_connect')) {
            $method = 'mysqli';
        }
        $connect = $method . '_connect';
        $query = $method . '_query';
        $close = $method . '_close';

        $link = $connect($setting['host'] . ':' . $setting['port'], $setting['user'], $setting['pwd']);
        if ($link) {
            $sql = 'CREATE DATABASE `' . $setting['name'] . '` DEFAULT CHARACTER SET ' . $setting['charset'] . ' COLLATE ' . $setting['collation'];
            if ($method == 'mysql') {
                $query($sql, $link);
            } else {
                $query($link, $sql);
            }
            $close($link);
        }
    }

    public function struct($config, $state = 0)
    {
        if ($state) {
            $sql = $this->tool->alter($config['table'], $config['struct'], $this->query($this->tool->desc($config['table'])));
            if ($sql) {
                $this->query($sql);
            }
        } else {
            $this->query($this->tool->create($config));
        }

        if (isset($config['default']) && $config['default']) {
            $count = $this->count($config['table'], [], $config['struct']);
            if (!$count) {
                $this->query($this->tool->inserts($config['table'], $config['default']));
            }
        }
    }

    public function index($config, $state = 0)
    {
        $this->query($this->tool->index($config['table'], $config['index'], $this->query($this->tool->showIndex($config['table']))));
    }

    public function partition($config, $partition)
    {
        $sql = $this->tool->partition($config['table'], $partition, $this->query($this->tool->showIndex($config['table'])));
        if ($sql) {
            $this->query($sql);
        }
    }

    public function query($sql, $bind = [], $method = 'read')
    {
        if (!is_array($bind)) {
            $bind = (array) $bind;
        }
        return $this->withConnection($method, $sql, $bind, function ($connection, $statement) {
            return $statement;
        });
    }

    public function load($table, $param, $set, $field, $version)
    {
        $bind = [];
        $sql = $this->tool->select($table, $param, $bind, $set, $field, $version);
        return $this->query($sql, $bind);
    }

    public function sql($table, $param, $set, $field, $version)
    {
        $bind = '';
        return $this->tool->select($table, $param, $bind, $set, $field, $version);
    }

    public function select($table, $param, $set, $field, $version)
    {
        return $this->load($table, $param, $set, $field, $version)->fetchAll();
    }

    public function find($table, $param, $set, $field, $version)
    {
        return $this->load($table, $param, $set, $field, $version)->fetch();
    }

    public function column($table, $param, $set, $field, $version)
    {
        return $this->load($table, $param, $set, $field, $version)->fetchColumn();
    }

    public function columns($table, $param, $set, $field, $version)
    {
        return $this->load($table, $param, $set, $field, $version)->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function count($table, $param, $field)
    {
        return $this->load($table, $param, ['col' => 'count(*)'], $field, false)->fetch(\PDO::FETCH_NUM)[0];
    }

    public function explain($table, $param, $field)
    {
        $bind = [];
        $sql = $this->tool->explain($this->tool->select($table, $param, $bind, ['col' => 'count(*)'], $field, false));
        return $this->query($sql, $bind)->fetch();
    }

    public function insert($table, $data, $field)
    {
        $bind = [];
        $sql = $this->tool->insert($table, $data, $bind, $field);

        return $this->withConnection('update', $sql, $bind, function ($connection, $statement) {
            // PG insert() uses RETURNING id
            if ($this->pdoType === 'pgsql') {
                return (int) $statement->fetchColumn();
            }
            return (int) $connection->lastInsertId();
        });
    }

    public function inserts($table, $data, $field)
    {
        $sql = $this->tool->inserts($table, $data);

        return $this->withConnection('update', $sql, [], function ($connection, $statement) {
            // Batch insert returning is not standardized here; keep old behavior
            if ($this->pdoType === 'pgsql') {
                // If you later add "RETURNING id" for batch, you can adapt.
                return 0;
            }
            return (int) $connection->lastInsertId();
        });
    }

    public function update($table, $param, $data, $field)
    {
        $bind = [];
        $sql = $this->tool->update($table, $param, $data, $bind, $field);

        return $this->withConnection('update', $sql, $bind, function ($connection, $statement) {
            return $statement->rowCount();
        });
    }

    public function delete($table, $param, $field)
    {
        $bind = [];
        $sql = $this->tool->delete($table, $param, $bind, $field);

        return $this->withConnection('update', $sql, $bind, function ($connection, $statement) {
            return $statement->rowCount();
        });
    }

    public function copy($table, $dest, $param, $field)
    {
        $bind = [];
        $sql = $this->tool->copy($table, $dest, $param, $bind, $field);

        return $this->withConnection('update', $sql, $bind, function ($connection, $statement) {
            return $statement->rowCount();
        });
    }

    public function optimize($table)
    {
        $sql = $this->tool->optimize($table) . ';' . $this->tool->analyze($table);
        return $this->query($sql, [], 'update');
    }

    public function inTransaction()
    {
        if ($this->transactionConnection) {
            return $this->transactionConnection->inTransaction();
        }
        if ($this->pools['update']) {
            return false;
        }
        return $this->update->inTransaction();
    }

    public function begin()
    {
        if ($this->transactionConnection) {
            return;
        }
        $this->transactionConnection = parent::acquireConnection('update');
        $this->transactionConnection->beginTransaction();
    }

    public function commit()
    {
        if ($this->transactionConnection) {
            $this->transactionConnection->commit();
            parent::releaseConnection('update', $this->transactionConnection);
            $this->transactionConnection = null;
        }
    }

    public function rollback()
    {
        if ($this->transactionConnection) {
            $this->transactionConnection->rollBack();
            parent::releaseConnection('update', $this->transactionConnection);
            $this->transactionConnection = null;
        }
    }

    public function transaction($class, $param, $msg)
    {
        if (Dever::getCommit()) {
            try {
                Dever::setCommit();
                $this->begin();
                $result = call_user_func_array($class, $param);
                $this->commit();
                return $result;
            } catch (\Exception $e) {
                $this->rollback();
                Dever::out()->error($msg);
            }
        } else {
            return call_user_func_array($class, $param);
        }
    }

    protected function withConnection($method, $sql, array $bind, callable $callback)
    {
        $attempts = 0;

        while (true) {
            $connection = null;
            $broken = false;

            try {
                $connection = $this->borrowConnection($method);
                $statement = $this->runStatement($connection, $sql, $bind);

                if (Dever::get(Debug::class)->shell) {
                    $this->bsql($sql, $bind);
                    // statement could be null for DDL chains; guard it
                    $count = $statement ? $statement->rowCount() : 0;
                    $this->log(['sql' => $sql, 'count' => $count]);
                }

                return $callback($connection, $statement);

            } catch (\RuntimeException $runtime) {
                if (!$connection) {
                    Dever::out()->error($runtime->getMessage());
                }
                throw $runtime;

            } catch (\PDOException $exception) {
                $broken = $this->isDisconnectException($exception);

                if (!$broken || $attempts >= 1) {
                    $this->error(['sql' => $sql, 'msg' => $exception->getMessage()]);
                } else {
                    $attempts++;
                    if ($connection) {
                        $this->recycleConnection($method, $connection, true);
                        $connection = null;
                    }
                    continue;
                }

            } finally {
                if ($connection) {
                    $this->recycleConnection($method, $connection, $broken);
                }
            }

            break;
        }
    }

    protected function runStatement($connection, $sql, array $bind)
    {
        if ($bind) {
            $handle = $connection->prepare($sql);
            $handle->execute($bind);
            return $handle;
        }

        // IMPORTANT: split multi-statements for PG/MySQL both (more stable)
        if (strpos($sql, ';') !== false) {
            $parts = array_filter(array_map('trim', explode(';', $sql)));
            $handle = null;
            foreach ($parts as $part) {
                if ($part === '') continue;
                $handle = $connection->query($part);
            }
            return $handle; // may be null for pure DDL; caller should tolerate
        }

        return $connection->query($sql);
    }

    protected function borrowConnection($method)
    {
        if ($this->transactionConnection) {
            return $this->transactionConnection;
        }
        return parent::acquireConnection($method);
    }

    protected function recycleConnection($method, $connection, $broken)
    {
        if ($this->transactionConnection && $connection === $this->transactionConnection) {
            if ($broken) {
                if ($connection->inTransaction()) {
                    try {
                        $connection->rollBack();
                    } catch (\Throwable $e) {
                    }
                }
                parent::releaseConnection('update', $connection, true);
                $this->transactionConnection = null;
            }
            return;
        }
        parent::releaseConnection($method, $connection, $broken);
    }

    protected function isDisconnectException(\PDOException $exception): bool
    {
        $code = (int) $exception->getCode();
        if (in_array($code, [2006, 2013, 2055], true)) {
            return true;
        }
        $message = strtolower($exception->getMessage());
        return str_contains($message, 'server has gone away') ||
               str_contains($message, 'no connection to the server') ||
               str_contains($message, 'lost connection');
    }
}
