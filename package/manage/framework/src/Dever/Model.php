<?php namespace Dever;
use Dever;
class Model
{
    protected $method = '';
    protected $store;
    protected $partition = false;
    public $config = [];
    protected static $configCache = [];
    protected static $schemaCache = [];
    public function __initialize($table, $store, $partition, $path)
    {
        [$app, $table] = explode('/', $table, 2);
        $project = Dever::project($app);
        if ($table) {
            $appName = strtolower($app);
            $base = $project['path'] . $path . DIRECTORY_SEPARATOR . $table . '.php';
            if (is_file($base)) {
                if (!isset(self::$configCache[$base])) {
                    $this->config = include $base;
                    $this->config['app'] = $appName;
                    $this->config['table'] = DEVER_PROJECT . '_' . $appName . '_' . $table;
                    $this->config['load'] = $appName . '/' . $table;
                    $this->lang();
                    self::$configCache[$base] = $this->config;
                }
                $this->config = self::$configCache[$base];
                if (isset($this->config['partition']) && empty($partition)) {
                    $partition = $this->config['partition'];
                }
                if (isset($this->config['store']) && $store == 'default') {
                    $store = $this->config['store'];
                }
                $file = $appName . DIRECTORY_SEPARATOR . $table . '.php';
            } else {
                $this->config['table'] = $table;
                $this->config['struct'] = false;
            }
        }
        if ($partition) {
            $this->partition($partition);
        }
        $this->store = Dever::store($store, $this->partition);
        if (isset($file)) {
            $this->write($path, $store, $file);
        }
        return $this;
    }
    public function partition($partition)
    {
        $setting = Dever::config('setting')['database']['partition'];
        if (is_array($partition)) {
            $this->partition = $partition;
        } elseif (is_string($partition)) {
            $e = '$v=' . $partition . ';';
            eval($e);
            $this->partition = $v;
        } else {
            $this->partition = $setting;
        }
        if ($this->partition) {
            foreach ($this->partition as $k => &$v) {
                $t = Dever::session($k);
                if ($t) {
                    $v = $t;
                } elseif (($k == 'database' || $k == 'table') && strstr($v, '(')) {
                    $e = '$v=' . $v . ';';
                    eval($e);
                }
            }
            $this->partition['create'] = $setting['create'];
        }
    }
    private function lang()
    {
        if (isset($this->config['lang']) && isset($this->config['struct']) && $pack = Dever::config('setting')['lang_pack']) {
            foreach ($this->config['lang'] as $lang) {
                if (isset($this->config['struct'][$lang])) {
                    foreach ($pack as $key => $value) {
                        if (Dever::config('setting')['lang'] != $key) {
                            $this->config['struct'][$key . '_' . $lang] = $this->config['struct'][$lang];
                        }
                    }
                }
            }
        }
    }
    private function write($path, $store, $file)
    {
        $path .= DIRECTORY_SEPARATOR . $store;
        $data['index'] = $data['struct'] = 0;
        if ($this->partition) {
            if (isset($this->partition['database']) && $this->partition['database']) {
                if (!$this->partition['create']) {
                    $this->config['table'] .= '_' . $this->partition['database'];
                }
                $path .= '/' . $this->partition['database'];
            }
            if (isset($this->partition['table']) && $this->partition['table']) {
                $this->config['table'] .= '_' . $this->partition['table'];
                $file = rtrim($file, '.php') . '_';
                $file .= $this->partition['table'] . '.php';
            }
            if (isset($this->partition['field']) && $this->partition['field']) {
                if (is_string($this->partition['field']['value']) && strstr($this->partition['field']['value'], 'date(')) {
                    $this->partition['field']['type'] = 'time';
                    $e = '$v=' . $this->partition['field']['value'] . ';';
                    eval($e);
                    $this->partition['field']['value'] = \Dever\Helper\Date::mktime($v);
                }
                $data['field'] = 0;
            }
        }
        $file = Dever::get(File::class)->get($path . DIRECTORY_SEPARATOR . $file);
        if (isset(self::$schemaCache[$file])) {
            $data = self::$schemaCache[$file];
        } elseif (is_file($file)) {
            $data = include $file;
        }
        $dirty = false;
        foreach ($data as $k => $v) {
            if (isset($this->config[$k])) {
                $num = count($this->config[$k]);
                if ($v != $num) {
                    $this->store->$k($this->config, $v);
                    $data[$k] = $num;
                    $dirty = true;
                }
            } elseif ($k == 'field' && $this->partition && isset($this->partition['field']) && $v != $this->partition['field']['value']) {
                $this->store->partition($this->config, $this->partition['field']);
                $data['field'] = $this->partition['field']['value'];
                $dirty = true;
            }
        }
        if ($dirty) {
            file_put_contents($file, '<?php return ' . var_export($data, true) . ';');
        }
        self::$schemaCache[$file] = $data;
    }
    public function optimize()
    {
        return $this->store->optimize($this->config['table']);
    }
    public function load($param, $set = [], $version = false)
    {
        return $this->store->load($this->config['table'], $param, $set, $this->config['struct'], $version);
    }
    public function sql($param, $set = [], $version = false)
    {
        return $this->store->sql($this->config['table'], $param, $set, $this->config['struct'], $version);
    }
    public function select($param, $set = [], $version = false)
    {
        if (isset($this->partition['where']) && $this->partition['where']) {
            $param = array_merge($this->partition['where'], $param);
        }
        if (empty($set['order'])) {
            if (isset($this->config['order'])) {
                if (strstr($this->config['order'], 'id asc')) {
                    $set['order'] = $this->config['order'];
                } else {
                    $set['order'] = $this->config['order'] . ',id desc';
                }
            } else {
                $set['order'] = 'id desc';
            }
        }
        if (isset($set['num'])) {
            $set['limit'] = Dever::get(Paginator::class)->init($set['num'], $set['page'] ?? 1, function()use($param){return $this->count($param);});
        }
        if ($version && isset($this->config['type']) && $this->config['type'] == 'myisam') {
            $version = false;
        }
        $result = $this->store->select($this->config['table'], $param, $set, $this->config['struct'], $version);
        if (isset($set['num']) && empty($set['page'])) {
            Dever::get(Paginator::class)->status(empty($result));
        }
        return $result;
    }
    public function find($param, $set = [], $version = false)
    {
        if (isset($this->partition['where']) && $this->partition['where']) {
            if (is_numeric($param)) {
                $param = ['id' => $param];
            }
            $param = array_merge($this->partition['where'], $param);
        }
        return $this->store->find($this->config['table'], $param, $set, $this->config['struct'], $version);
    }
    public function sum($param, $field)
    {
        return $this->column($param, 'sum(`'.$field.'`)', 0);
    }
    public function column($param, $field = 'name', $default = '')
    {
        return $this->store->column($this->config['table'], $param, ['col' => $field], $this->config['struct'], false) ?? $default;
        //$info = $this->find($param, ['col' => $field . ' as value']);
        //return $info && $info['value'] ? $info['value'] : $default;
    }
    public function columns($param, $field = 'id')
    {
        return $this->store->columns($this->config['table'], $param, ['col' => $field], $this->config['struct'], false);
    }
    public function count($param)
    {
        if (isset($this->partition['where']) && $this->partition['where']) {
            $param = array_merge($this->partition['where'], $param);
        }
        if (isset($this->config['count']) && $this->config['count'] = 2) {
            $data = $this->store->explain($this->config['table'], $param, $this->config['struct']);
            return $data['rows'] ?? 0;
        }
        return $this->store->count($this->config['table'], $param, $this->config['struct']);
    }
    public function kv($param, $set = [])
    {
        $result = [];
        $data = $this->select($param, $set);
        if ($data) {
            if (empty($set['kv'])) {
                $set['kv'] = ['id', 'name'];
            }
            if (is_array($set['kv']) && isset($set['kv'][1])) {
                foreach ($data as $k => $v) {
                    $result[$v[$set['kv'][0]]] = $v[$set['kv'][1]];
                }
            } else {
                foreach ($data as $k => $v) {
                    $result[] = $v[$set['kv']];
                }
            }
        }
        return $result;
    }
    public function up($param, $data, $version = false)
    {
        $info = $this->find($param, [], $version);
        if ($info) {
            $state = $this->update($info['id'], $data);
            if ($state) {
                return $info['id'];
            }
            return false;
        } else {
            return $this->insert($data);
        }
    }
    public function insert($data)
    {
        if (empty($data['cdate'])) {
            $data['cdate'] = (int)(microtime(true) * 1000);
        }
        if (isset($this->partition['where']) && $this->partition['where']) {
            $data = array_merge($this->partition['where'], $data);
        }
        return $this->store->insert($this->config['table'], $data, $this->config['struct']);
    }
    public function inserts($data)
    {
        if (isset($this->partition['where']) && $this->partition['where']) {
            $data['value'] = array_merge($this->partition['where'], $data['value']);
        }
        return $this->store->inserts($this->config['table'], $data, $this->config['struct']);
    }
    public function update($param, $data, $version = false)
    {
        if ($version && isset($this->config['struct']['version'])) {
            if ($version > 1) {
                $info = ['version' => $version];
            } else {
                $info = $this->find($param, ['col' => 'id,version']);
            }
            if ($info) {
                if (is_numeric($param)) {
                    $param = ['id' => $param];
                }
                $param['version'] = $info['version'];
                $data['version'] = ['+', 1];
            } else {
                return false;
            }
        }
        return $this->store->update($this->config['table'], $param, $data, $this->config['struct']);
    }
    public function delete($param)
    {
        return $this->store->delete($this->config['table'], $param, $this->config['struct']);
    }
    public function copy($table, $where, $field)
    {
        return $this->store->copy($this->config['table'], $table, $where, $field);
    }
    public function begin()
    {
        return $this->store->begin();
    }
    public function commit()
    {
        return $this->store->commit();
    }
    public function rollback()
    {
        return $this->store->rollback();
    }
    public function query($sql, $bind = [], $options = [])
    {
        if (strpos($sql, '{table}')) {
            $sql = str_replace('{table}', $this->config['table'], $sql);
        }
        $page = is_array($options) ? $options : [];
        $method = $page['method'] ?? 'read';
        if (isset($page['method'])) {
            unset($page['method']);
        }
        if (isset($page['num'])) {
            if (strpos($sql, 'limit')) {
                $temp = explode('limit', $sql);
                $sql = $temp[0];
            }
            $limit = self::get(Paginator::class)->init($page['num'], $page['page'] ?? 1, function()use($sql, $bind){return $this->queryCount($sql, $bind);});
            if (is_array($limit)) {
                $limit = implode(',', $limit);
            }
            $sql .= ' limit ' . $limit;
        }
        $result = $this->store->query($sql, $bind, $method);
        if (isset($page['num']) && empty($page['page'])) {
            $result = $result->fetchAll();
            self::get(Paginator::class)->status(empty($result));
        }
        return $result;
    }
    public function queryCount($sql, $bind)
    {
        $sql = mb_strtolower($sql);
        if (strpos($sql, ' from ')) {
            $temp = explode(' from ', $sql);
        }
        if (isset($temp[1])) {
            if (strpos($temp[1], ' order ')) {
                $temp = explode(' order ', $temp[1]);
                $sql = $temp[0];
            } else {
                $sql = $temp[1];
            }
            if (strpos($sql, 'group')) {
                $sql = 'SELECT count(1) as num FROM (SELECT count(1) FROM '.$sql.' ) a ';
            } else {
                $sql = 'SELECT count(1) as num FROM ' . $sql;
            }
            return $this->store->query($sql, $bind)->fetchColumn();
        }
    }
    public function value($key, $value = false, $col = 'id,name', $data = [])
    {
        if (isset($this->config['option'][$key])) {
            $option = $this->config['option'][$key];
        } elseif (isset($this->config['struct'][$key])) {
            $option = Dever::issets($this->config['struct'][$key], 'option');
            if (!$option) {
                $option = Dever::issets($this->config['struct'][$key], 'value');
            }
        }

        if (isset($option)) {
            if (is_string($option)) {
                if ($data) {
                    $option = \Dever\Helper\Str::val($option, $data);
                }
                if (is_string($option)) {
                    if (strpos($option, 'http') === 0) {
                        if (strpos($option, 'Dever') === 0) {
                            eval('$option=' . $option . ';');
                        }
                        return $option;
                    }
                    if (strpos($option, 'Dever') === 0) {
                        eval('$option=' . $option . ';');
                    } else {
                        $option = Dever::db($option)->select([], ['col' => $col]);
                    }
                }
            }
            if (is_array($option) && !isset($option[0])) {
                $temp = $option;
                $option = [];
                $col = explode(',', $col);
                foreach ($temp as $k => $v) {
                    $option[] = [$col[0] => $k, $col[1] => $v];
                }
            }
            if ($value && $option) {
                if (is_array($value) && isset($value[$key])) {
                    $value = $value[$key];
                }
                if (strpos($value, ',')) {
                    $temp = explode(',', $value);
                    $result = [];
                    foreach ($temp as $v) {
                        $state = Dever::in_array($option, $v);
                        if ($state) {
                            $result[] = $state;
                        }
                    }
                    return implode('、', $result);
                }
                return Dever::in_array($option, $value);
            }
            return $option;
        }
        return false;
    }
    public function tree($where, $config, $func = false, $set = [], $index = 0)
    {
        $where[$config[0]] = $config[1];
        $data = $this->select($where, $set);
        if ($data) {
            foreach ($data as $k => &$v) {
                if ($func) $v = call_user_func($func, $k, $v);
                //if ($index > 0) $v['name'] = ' -- ' . $v['name'];
                $config[1] = $v[$config[2]];
                $child = $this->tree($where, $config, $func, $set, 1);
                if ($child) {
                    $v['children'] = $child;
                }
            }
        }
        return $data;
    }
    public function show($where, $field = 'name', $str = '、')
    {
        $result = [];
        $data = $this->select($where);
        foreach ($data as $k => $v) {
            $result[] = $v[$field];
        }
        $result = implode($str, $result);
        return $result;
    }
    public function __call($method, $data)
    {
        if (isset($this->config['request'][$method])) {
            $method = $this->config['request'][$method];
            $param = [];
            if (isset($method['where'])) {
                $temp = [];
                foreach ($method['where'] as $k => $v) {
                    if ($k == 'or' || $k == 'and') {
                        foreach ($v as $k1 => $v1) {
                            $this->callCreate($data, $temp, $k1, $v1);
                        }
                    } else {
                        $this->callCreate($data, $temp, $k, $v);
                    }
                }
                $param[] = $temp;
            }
            if (isset($method['data'])) {
                $temp = [];
                foreach ($method['data'] as $k => $v) {
                    $this->callCreate($data, $temp, $k, $v);
                }
                $param[] = $temp;
            }
            if (isset($method['set']) && isset($data[1])) {
                $param[] = array_merge($method['set'], $data[1]);
            }
            $type = $method['type'];
            return $this->$type(...$param);
        }
        return false;
    }
    private function callCreate($data, &$param, $k, $v)
    {
        if (is_array($v)) {
            if (empty($data[0][$k])) {
                if (empty($v[2])) {
                    return;
                }
                $data[0][$k] = $v[2];
            }
            $i = $v[0];
            $j = [$v[1], $data[0][$k]];
        } else {
            if (empty($data[0][$k])) {
                $data[0][$k] = $v;
            }
            $i = $k;
            $j = $data[0][$k];
        }
        $this->callCreateParam($param, $i, $j);
    }
    private function callCreateParam(&$param, $i, $j)
    {
        if (isset($param[$i])) {
            $i .= '#';
            return $this->callCreateParam($param, $i, $j);
        }
        $param[$i] = $j;
    }
}
