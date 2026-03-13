<?php namespace Dever\Store;
use Dever;
use Dever\Debug;
use Dever\Sql;
#https://docs.influxdata.com/influxdb/v2/api-guide/api_intro/
class Influxdb extends Base
{
    public function __construct($setting)
    {
        $this->type = $setting['type'];
        $this->read = $setting;
    }
    public function query($param = [], $type = 1)
    {
        $header['Authorization'] = 'Token ' . $this->read['token'];
        $header['Content-Type'] = 'text/plain; charset=utf-8';
        $header['Accept'] = 'application/json';
        if ($type == 1) {
            $type = 'get';
            $host = $this->read['host'] . '/query?db='.$this->read['name'];
            $param = ['q' => $param];
            $json = false;
        } else {
            $type = 'post';
            $host = $this->read['host'] . '/api/v2/write?org='.$this->read['user'].'&bucket='.$this->read['name'].'&precision=' . $this->read['precision'];
            $json = true;
        }

        /*
        //$curl = 'curl --get '.$this->read['host'].'/query?db=api  --header "Authorization: '.$header['Authorization'].'" --data-urlencode "q='.$param['q'].'"';
        //$curl = 'curl --request POST "'.$host.'" --header "Authorization: '.$header['Authorization'].'" --header "Content-Type: '.$header['Content-Type'].'" --header "Accept: '.$header['Accept'].'" --data-binary \''.$param.'\'';
        //echo $curl;die;
        */
        $result = Dever::curl($host, $param, $type, $json, $header)->result();
        if (Dever::get(Debug::class)->shell) {
            $this->log(['param' => $param, 'result' => $result]);
        }
        return $result;
    }
    public function struct($config, $state = 0)
    {
        if (!$state) {
            $this->query('DELETE FROM ' . $config['table']);
        }
    }
    public function load($table, $param, $set, $field, $lock)
    {
        $bind = [];
        $sql = Dever::get(Sql::class)->select($table, $param, $bind, $set, $field, $lock, $this->type);
        $data = $this->query($sql);
        $data = Dever::json_decode($data);
        if (isset($data['results'][0]['series'][0]['values'])) {
            return $data['results'][0]['series'][0];
        } else {
            return [];
        }
    }
    public function select($table, $param, $set, $field, $lock)
    {
        $data = $this->load($table, $param, $set, $field, $lock);
        if ($data) {
            $columns = $data['columns'];
            $values = $data['values'];
            $result = [];
            foreach ($values as $k => $v) {
                foreach ($columns as $k1 => $v1) {
                    $result[$k][$v1] = $this->set($v[$k1], $v1);
                }
            }
            return $result;
        }
        return $data;
    }
    public function find($table, $param, $set, $field, $lock)
    {
        $data = $this->load($table, $param, $set, $field, $lock);
        if ($data) {
            $columns = $data['columns'];
            $values = $data['values'];
            $result = [];
            foreach ($columns as $k => $v) {
                $result[$v] = $this->set($values[0][$k], $v);
            }
            return $result;
        }
        return $data;
    }
    public function count($table, $param, $field)
    {
        $result = $this->load($table, $param, array('col'=>'count(*)'), $field, false);
        if ($result && isset($result['values'][0][1])) {
            return $result['values'][0][1];
        }
        return 0;
    }
    public function insert($table, $data, $field)
    {
        $param = $table;
        $time = $data['cdate'];
        $tags = $fields = [];
        if (isset($data['id'])) {
            if (!$data['id']) {
                $data['id'] = 1;
            }
            $tags[] = 'id=' . $data['id'];
        }
        foreach ($field as $k => $v) {
            $value = $v['default'] ?? "null";
            if (isset($data[$k])) {
                $value = $data[$k];
            }
            if (!$value) {
                $value = 'null';
            }
            if (!strstr($v['type'], 'char') && !strstr($v['type'], 'text') && $value == 'null') {
                $value = 0;
            }
            if (isset($v['base64']) && $v['base64']) {
                $value = 'base64_' . base64_encode($value);
            }
            if (isset($v['fields']) && $v['fields']) {
                if (strstr($v['type'], 'char') || strstr($v['type'], 'text')) {
                    $value = '"' . $value . '"';
                }
                $fields[] = $k . '=' . $value;
            } else {
                $tags[] = $k . '=' . $value;
            }
        }
        if ($tags) {
            $param .= ',' . implode(',', $tags);
        } else {
            Dever::out()->error('influxdb tags not null');
        }
        if ($fields) {
            $param .= ' ' . implode(',', $fields);
        }
        $param .= ' ' . $time;
        $this->query($param, 2);
        return $time;
    }
    public function update($table, $param, $data, $field)
    {
        $data = array_merge($param, $data);
        return $this->insert($table, $data, $field);
    }
    private function set($k, &$v)
    {
        if ($v == 'time') {
            $v = 'cdate';
            $k = strtotime($k);
        }
        if (strstr($k, 'base64_')) {
            $k = base64_decode(str_replace('base64_', '', $k));
        }
        if ($k == 'null') {
            $k = '';
        }
        return $k;
    }
    public function delete($table, $param, $field){}
    public function index($config, $state = 0){}
    public function partition($config, $partition){}
    public function begin(){}
    public function commit(){}
    public function rollback(){}
    public function transaction(){}
}