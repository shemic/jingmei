<?php namespace Dever\Store;
use Dever;
use Dever\Debug;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Command;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Query;
use MongoDB\BSON\Regex;
use MongoDB\BSON\ObjectId;
class Mongo extends Base
{
    protected function connect($setting)
    {
        $this->type = $setting['type'];
        $this->db = $setting['name'];
        if (strpos($setting['host'], ':') !== false) {
            list($setting['host'], $setting['port']) = explode(':', $setting['host']);
        }
        try {
            if (empty($setting['timeout'])) {
                $setting['timeout'] = 1000;
            }
            $handle = new Manager('mongodb://' . $setting['host'] . ':' . $setting['port'], ['username' => $setting['user'], 'password' => $setting['pwd'], 'connectTimeoutMS' => $setting['timeout']]);
            Dever::debug('mongodb ' . $setting['host'] . ' connected', $setting['type']);
            return $handle;
        } catch (\PDOException $e) {
            echo $e->getMessage();die;
        }
    }
    public function index($config, $state = 0)
    {
        return;
        $command = ['listIndexes' => $config['table']];
        $result = $this->read->executeCommand($this->db, new Command($command));
        foreach ($result as $k => $v) {
            if ($v->name != '_id_') {
                $command = ['dropIndexes' => $config['table'], 'index' => $v->name];
                $this->read->executeCommand($this->db, new Command($command));
            }
        }
        $index = [];
        foreach ($config['index'] as $k => $v) {
            $t = false;
            if (strpos($v, '.')) {
                list($v, $t) = explode('.', $v);
                if ($t == 'unique') {
                    $t = true;
                }
            }
            $v = explode(',', $v);
            $key = [];
            foreach ($v as $v1) {
                $key[$v1] = 1;
            }
            $index[] = [
                'name' => $k,
                'key' => $key,
                'background' => false,
                'unique' => $t
            ];
        }
        $command = ['createIndexes' => $config['table'],'indexes' => $index];
        $this->read->executeCommand($this->db, new Command($command));
    }
    public function load($table, $param, $set, $field, $lock)
    {
        $param = $this->param($param);
        $options = [];
        if (isset($set['order'])) {
            if (is_string($set['order'])) {
                $temp = explode(',', $set['order']);
                $set['order'] = [];
                foreach ($temp as $k => $v) {
                    $t = explode(' ', $v);
                    if ($t[0] == 'id') {
                        $t[0] = '_' . $t[0];
                    }
                    $set['order'][$t[0]] = $t[1] == 'desc' ? -1 : 1;
                }
            }
            $options['sort'] = $set['order'];
        }
        if (isset($set['limit'])) {
            if (is_array($set['limit'])) {
                $options['skip'] = $set['limit'][0];
                $options['limit'] = $set['limit'][1];
            } elseif (strstr($set['limit'], ',')) {
                $temp = explode(',', $set['limit']);
                $options['skip'] = $temp[0];
                $options['limit'] = $temp[1];
            } else {
                $options['skip'] = 0;
                $options['limit'] = $set['limit'];
            }
        }
        if (isset($set['col']) && $set['col'] && $set['col'] != '*') {
            $temp = explode(',', $set['col']);
            $total = [];
            foreach ($temp as $k => $v) {
                if (strstr($v, 'sum(')) {
                    if (strstr($v, ' as')) {
                        $t = explode(' as ', $v);
                        $k = $t[1];
                        $v = $t[0];
                    } else {
                        $k = $v;
                    }
                    $v = str_replace(array('sum(', ')'), '', $v);
                    $total[$k] = ['$sum' => '$' . $v];
                } else {
                    $options['projection'][$v] = true;
                }
            }
            if ($total && empty($set['group'])) {
                $set['group'] = [];
                foreach ($param as $k => $v) {
                    $set['group'][] = $k;
                }
            }
        }
        if (isset($set['group'])) {
            $pipeline = [];
            if ($param) {
                $pipeline[] = ['$match' => $param];
            }
            $group = [];
            if ($set['group'] == 'null') {
                $group = null;
            } else {
                if (is_string($set['group'])) {
                    $set['group']= explode(',', $set['group']);
                }
                foreach ($set['group'] as $k => $v) {
                    $group[$v] = '$' . $v;
                }
            }
            $group = ['_id' => $group];
            if (isset($total) && $total) {
                $group = array_merge($group, $total);
            } else {
                $group['count'] = ['$sum' => 1];
            }
            if (isset($options['projection'])) {
                foreach ($options['projection'] as $k => $v) {
                    $group[$k] = ['$push' => '$' . $k];
                }
            }
            $pipeline[] = ['$group' => $group];
            if (isset($options['sort'])) {
                $pipeline[] = ['$sort' => $options['sort']];
            }
            if (isset($options['skip'])) {
                $pipeline[] = ['$skip' => $options['skip']];
            }
            if (isset($options['limit'])) {
                $pipeline[] = ['$limit' => $options['limit']];
            }
            $options = array('aggregate' => $table,'pipeline' => $pipeline,'cursor' => new \stdClass());
            $command = new Command($options);
            $result = $this->read->executeCommand($this->db, $command)->toArray();
        } else {
            $query = new Query($param, $options);
            $result = $this->read->executeQuery($this->db . '.' . $table, $query);
        }
        if (Dever::get(Debug::class)->shell) {
            $this->log(['table' => $this->db . '.' . $table, 'param' => $param, 'option' => $options, 'result' => $result]);
        }
        return $result;
    }
    public function select($table, $param, $set, $field, $lock)
    {
        $result = [];
        $data = $this->load($table, $param, $set, $field, $lock);
        foreach ($data as $k => $v) {
            $result[] = $this->handle($v);
        }
        return $result;
    }
    public function find($table, $param, $set, $field, $lock)
    {
        $result = [];
        $data = $this->load($table, $param, $set, $field, $lock);
        foreach ($data as $k => $v) {
            $result = $this->handle($v);
            break;
        }
        return $result;
    }
    public function count($table, $param, $field)
    {
        $result = 0;
        $set['group'] = 'null';
        $data = $this->load($table, $param, $set, $field, false);
        if (isset($data[0]) && $data[0]->count) {
            $result = $data[0]->count;
        }
        if (Dever::get(Debug::class)->shell) {
            $this->log(['table' => $this->db . '.' . $table, 'param' => $param, 'result' => $result]);
        }
        return $result;
    }
    public function insert($table, $data, $field)
    {
        $insert = [];
        foreach ($data as $k => $v) {
            if ($field && empty($field[$k]) && strpos('id,cdate', $k) === false) {
                continue;
            }
            /*
            if (is_numeric($v)) {
                if (isset($field[$k]) && strpos($field[$k]['type'], 'char')) {
                } else {
                    $v = (float) $v;
                }
            }*/
            $insert[$k] = $v;
        }
        if ($field) {
            foreach ($field as $k => $v) {
                if (!isset($insert[$k])) {
                    $insert[$k] = $v['default'] ?? '';
                }
            }
        }
        //$insert['_id'] = new ObjectId();
        $bulk = new BulkWrite;
        $id = $bulk->insert($insert);
        $id = (array) $id;
        $id = $id['oid'];
        $result = $this->update->executeBulkWrite($this->db . '.' . $table, $bulk);
        if (Dever::get(Debug::class)->shell) {
            $this->log(['table' => $this->db . '.' . $table, 'insert' => $insert, 'result' => $id]);
        }
        if ($result->getInsertedCount() >= 1) {
            return $id;
        }
        return false;
    }
    public function update($table, $param, $data, $field)
    {
        $update = [];
        foreach ($data as $k => $v) {
            if ($field && empty($field[$k]) && strpos('id,cdate', $k) === false) {
                continue;
            }
            /*
            if (is_numeric($v)) {
                if (isset($field[$k]) && strpos($field[$k]['type'], 'char')) {
                } else {
                    $v = (float) $v;
                }
            }*/
            $update[$k] = $v;
        }
        $update = ['$set' => $update];
        $param = $this->param($param);
        $bulk = new BulkWrite;
        $bulk->update($param, $update, ['multi' => true, 'upsert' => false]);
        $result = $this->update->executeBulkWrite($this->db . '.' . $table, $bulk);
        $result = $result->getModifiedCount();
        if (Dever::get(Debug::class)->shell) {
            $this->log(['table' => $this->db . '.' . $table, 'param' => $param, 'update' => $update, 'result' => $result]);
        }
        return $result;
    }
    public function delete($table, $param, $field)
    {
        $param = $this->param($param);
        $bulk = new BulkWrite;
        $bulk->delete($param);
        $result = $this->update->executeBulkWrite($this->db . '.' . $table, $bulk);
        $result = $result->getDeletedCount();
        if (Dever::get(Debug::class)->shell) {
            $this->log(['table' => $this->db . '.' . $table, 'param' => $param, 'result' => $result]);
        }
        return $result;
    }
    private function param($param)
    {
        $result = [];
        if ($param) {
            if (is_array($param)) {
                foreach ($param as $k => $v) {
                    if (strpos($k, '#')) {
                        $k = trim($k, '#');
                    }
                    if ($k == 'id') {
                        $k = '_id';
                    }
                    if ($k == 'or' || $k == 'and') {
                        $where = [];
                        foreach ($v as $k1 => $v1) {
                            if (strpos($k1, '#')) {
                                $k1 = trim($k1, '#');
                            }
                            if ($k1 == 'id') {
                                $k1 = '_id';
                            }
                            $where[$k1] = $this->where($k1, $v1);
                        }
                        $result['$' . $k][] = $where;
                    } else {
                        if (isset($result[$k])) {
                            $result[$k] = array_merge($result[$k], $this->where($k, $v));
                        } else {
                            $result[$k] = $this->where($k, $v);
                        }
                    }
                }
            } elseif ($param) {
                $pk = '_id';
                $result[$pk] = $this->where($pk, $param);
            } else {
                $result = $param;
            }
        }
        return $result;
    }
    private function where(&$key, $value)
    {
        $method = '';
        if (is_array($value)) {
            $method = $value[0];
            $value = $value[1];
        }
        switch ($method) {
            case 'like':
                # 模糊查询
                $value = (string) $value;
                if (strpos($value, '%') !== false) {
                    $value = str_replace('%', '(.*?)', $value);
                    $value = new Regex($value, 'i');
                } else {
                    $value = new Regex($value . '(.*?)', 'i');
                }
                break;
            case 'in':
            case 'nin':
                # in查询
                $value = explode(',', $value);
                foreach ($value as $k => $v) {
                    $value[$k] = $this->value($key, $v);
                }
                $value = ['$' . $method => $value];
                break;
            case '>':
                $value = array('$gt' => $this->value($key, $value));
                break;
            case '>=':
                $value = array('$gte' => $this->value($key, $value));
                break;
            case '<':
                $value = array('$lt' => $this->value($key, $value));
                break;
            case '<=':
                $value = array('$lte' => $this->value($key, $value));
                break;
            case '!=':
                $value = array('$ne' => $this->value($key, $value));
                break;
            case '%':
                $value = array('$mod' => $this->value($key, $value));
                break;
            case 'between':
                $value = array('$gt' => $this->value($key, $value[0]), '$lt' => $this->value($key, $value[1]));
                break;
            case 'betweens':
                $value = array('$gte' => $this->value($key, $value[0]), '$lte' => $this->value($key, $value[1]));
                break;
            default : 
                $value = $this->value($key, $value);
                break;
        }
        return $value;
    }
    private function value(&$key, $value)
    {
        if ($key == '_id') {
            if (is_numeric($value)) {
                $key = 'id';
                $value = (float) $value;
            } else {
                $value = new ObjectId($value);
            }
        } elseif (strlen($value) != 11 && is_numeric($value)) {
            $value = (float) $value;
        }
        return $value;
    }
    private function handle($v)
    {
        $v = (array)$v;
        # 后续删除
        /*
        foreach ($v as &$v1) {
            if (is_numeric($v1) && strstr($v1, 'E')) {
                $v1 = number_format($v1, 0, '', '');
            }
        }*/
        $v['_id'] = (array) $v['_id'];
        if (isset($v['_id']['oid'])) {
            $v['_id'] = $v['_id']['oid'];
            if (!isset($v['id'])) {
                $v['id'] = $v['_id'];
            }
        } else {
            $v = array_merge($v['_id'], $v);
            unset($v['_id']);
        }
        return $v;
    }
    public function struct($config, $state = 0){}
    public function partition($config, $partition){}
    public function begin(){}
    public function commit(){}
    public function rollback(){}
    public function transaction(){}
}