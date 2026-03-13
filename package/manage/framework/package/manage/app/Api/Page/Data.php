<?php namespace Manage\Api\Page;
use Dever;
use Manage\Lib\Page;
use Manage\Lib\Util;
# 数据获取
class Data extends Page
{
    private $expand = false;
    public function __construct($load = '')
    {
        parent::__construct('list', $load);
    }
    public function list()
    {
        if ($this->menu && $this->menu['show'] == 1 && !$this->getFunc('list', '列表', 1)) {
            Dever::error('无访问权限');
        }
        $data['title'] = $this->config['title'] ?? '';
        $data['recycler'] = $this->recycler;

        $where = [];
        if (isset($this->config['where']) && $this->config['where']) {
            foreach ($this->config['where'] as $k => $v) {
                if (is_numeric($k) || $k == $v) {
                    $where[$v] = Dever::load(Util::class)->request($v);
                } else {
                    $where[$k] = $this->getShow($v, []);
                }
            }
        }
        $data['button'] = $this->button('button', $where);
        $data = array_merge($data, $this->out($where));
        $data['total'] = Dever::page('total');
        $data['height'] = $this->config['height'] ?? '100%';
        $data['type'] = $this->config['type'] ?? 'table';
        $data['desc'] = $this->config['desc'] ?? '';
        $data['layout'] = $this->config['layout'] ?? [];
        $data['exportButton'] = $this->export();
        $data['show'] = [
            'selection' => $this->config['selection'] ?? false,
            'expand' => $this->expand,
            'index' => $this->config['index'] ?? false,
        ];
        $this->column($data);
        return $data;
    }
    public function out($where = [])
    {
        $set = $this->config['set'] ?? [];
        $data['field'] = $data['head'] = [];
        $data['search'] = $this->search($where);
        $ids = Dever::input('ids');
        if ($ids) {
            $where['id'] = ['in', $ids];
        }
        $set['num'] = Dever::input('pgnum', '', '', 10);
        $order_col = Dever::input('order_col');
        if ($order_col) {
            $order_value = Dever::input('order_value');
            if ($order_value) {
                $set['order'] = $order_col . ' ' . $order_value . ', id desc';
            }
        }
        $data['filter'] = [];
        if (isset($this->config['filter'])) {
            $data['filter'] = Dever::call($this->config['filter'], [$where]);
            if ($data['filter']) {
                $filter = Dever::input('filter', '', '', 0);
                if (isset($data['filter'][$filter])) {
                    $where = array_merge($where, $data['filter'][$filter]['where']);
                }
            }
        }

        if (isset($this->config['data'])) {
            $result = Dever::call($this->config['data'], [$where, $set]);
            $data = array_merge($data, $result);
        } else {
            $data['field'] = $this->setting('field', $data['head'], true, 'show');
            $data['body'] = $this->data($where, $set);
        }
        $method = Dever::input('method');
        if ($method && strstr($method, '.')) {
            $result = Dever::call($method, [$data]);
            unset($data);
            $data['field'] = $result['head'];
            $data['body'] = $result['body'];
        }
        $data['stat'] = [];
        if (isset($this->config['stat'])) {
            $data['stat'] = Dever::call($this->config['stat'], [$where]);
        }
        
        $data['bodyButton'] = (isset($this->config['data_button']) && $this->config['data_button']) || isset($this->config['data_button_list']) ? true : false;
        return $data;
    }

    private function data($where, $set = [])
    {
        if (isset($this->config['tree'])) {
            return $this->db->tree($where, $this->config['tree'], [$this, 'handleData']);
        }
        $data = $this->db->select($where, $set);
        $result = [];
        if ($data) {
            foreach ($data as $k => $v) {
                $result[$k] = $this->handleData($k, $v);
            }
        }
        return $result;
    }

    public function handleData($k, $v)
    {
        $result = $v;
        $result['index'] = $k+1*Dever::input('pg', '', '', 1);
        $button = $this->button('data_button', $v);
        if ($button) {
            $result['button'] = $button;
        }
        $button = $this->button('data_button_list', $v, false);
        if ($button) {
            $result['button_list'] = $button;
        }
        
        # 是否保留html代码，1是保留，2是不保留
        $html = Dever::input('html', '', '', 1);
        if (isset($v['_id'])) {
            $result['id'] = $v['_id'];
        } elseif (isset($v['id'])) {
            $result['id'] = $v['id'];
        }
        $result['cdate'] = $v['cdate'];
        foreach ($this->field as $value) {
            $key = $value['key'];
            if (isset($v[$key])) {
                $result[$key] = $this->getValue($key, $v[$key], $v, $value);
            } elseif (strpos($key, '/')) {
                $other = $this->getOther($key, $value, $v);
                if ($other) {
                    $otherName = [];
                    foreach ($other as $k1 => $v1) {
                        if (isset($v1['name'])) {
                            $otherName[] = $v1['name'];
                        }
                    }
                    if ($otherName) {
                        $result[$key] = implode('、', $otherName);
                    } else {
                        $result[$key] = $other;
                    }
                }
            } elseif (isset($value['show'])) {
                $result[$key] = $this->getShow($value['show'], $v);
            }
            if ($html == 2 && is_string($result[$key])) {
                $result[$key] = strip_tags($result[$key]);
            }
        }
        if (isset($this->config['expand']) && $this->config['expand']) {
            $result['expand'] = Dever::call($this->config['expand'], [$v]);
            $this->expand = true;
        }
        return $result;
    }

    private function export()
    {
        $result = false;
        if (isset($this->config['export']) && $this->config['export']) {
            $result = [];
            foreach ($this->config['export'] as $k => $v) {
                $func = $this->getFunc($k, $v, 300);
                if ($func) {
                    $result[$k] = $v;
                }
            }
        }
        return $result;
    }
}