<?php namespace Manage\Api\Page;
use Dever;
use Manage\Lib\Page;
ini_set("memory_limit", -1);
set_time_limit(0);
ini_set('max_input_vars', 10000);
# 更新页 项目着急上线 以后优化并封装
class Update extends Page
{
    public function __construct($load = '', $input = true)
    {
        parent::__construct('update', $load, $input);
    }
    public function get(&$value = [], &$option = [])
    {
        $func = $this->checkFunc();
        $remote = $show = $spec = $source = $default = [];
        $data['update'] = $data['field'] = $data['option'] = [];
        $this->setting('field', $data['update'], true, 'text');
        foreach ($data['update'] as $k => $v) {
            if ($v['type'] == 'tree' || $v['type'] == 'upload' || $v['type'] == 'cascader' || $v['type'] == 'checkbox1' || isset($v['multiple'])) {
                if (isset($v['value']) && $v['value']) {
                    $v['value'] = explode(',', $v['value']);
                    foreach ($v['value'] as $k1 => $v1) {
                        if (is_numeric($v1)) {
                            $v['value'][$k1] = (float) $v1;
                        }
                    }
                } else {
                    $v['value'] = [];
                }
            }
            if (isset($v['source'])) {
                $source[$v['key']] = $v['source'];
            }
            if (isset($v['remote'])) {
                $remote[$v['key']] = [$k, $v['remote'], $v['key']];
                if (isset($v['remote_default']) && !$v['remote_default']) {
                    unset($remote[$v['key']][2]);
                }
            }
            if (isset($v['spec_data'])) {
                $spec[$v['key']] = [$k, $v['spec_data'], $v['key'] . '_spec', $v['spec'], $v['spec_field'], $v['spec_template'] ?? ''];
            }
            if (isset($v['show']) && is_string($v['show'])) {
                $show[$v['key']] = [$k, $v['show']];
            }
            if (isset($v['default']) && is_string($v['default']) && (strstr($v['default'], '(') || strstr($v['default'], '{'))) {
                $default[$v['key']] = [$k, $v['default']];
            }
            $data['field'][$v['key']] = $v['value'];
            if ($v['type'] == 'sku') {
                $data['field'][$v['key'] . '_spec'] = [];
            }
            if (isset($v['option'])) {
                $data['option'][$v['key']] = $v['option'];
                unset($data['update'][$k]['option']);
            }
        }
        $active = $this->column($data);
        $data['info_id'] = false;
        if (!$this->info && $active) {
            $this->info = $this->db->find($active);
            if ($this->info) {
                $data['info_id'] = $this->info['id'];
                if (!$func) {
                    $func = $this->getFunc('edit', '编辑', 1);
                    if (!$func && $this->menu && $this->menu['show'] == 1) {
                        Dever::error('无操作权限');
                    }
                }
            }
        } elseif (!$func) {
            $func = $this->getFunc('update', '更新', 1);
            if (!$func && $this->menu && $this->menu['show'] == 1) {
                Dever::error('无操作权限');
            }
        }
        if ($this->info) {
            $info = $this->info;
            $this->setInfo($info, $data, $remote, $show, $source, $default, 1, $this->config['field']);
            if ($spec) {
                foreach ($spec as $k => $v) {
                    $data['update'][$v[0]]['remote'] = Dever::url($v[1], ['value' => '', 'table' => $this->db->config['load'], 'id' => false]);
                    $result = Dever::call($v[1], [$v[3], $v[4], $this->info['id']]);
                    if ($result) {
                        $data['field'][$v[2]] = $result;
                    }
                }
            }
        } elseif ($value) {
            $field = [];
            if (isset($this->config['field']) && $this->config['field']) {
                foreach ($this->config['field'] as $k => $v) {
                    if (isset($v['field'])) {
                        $field[$v['field']] = $v;
                        if (!isset($field[$v['field']]['index'])) {
                            $field[$v['field']]['index'][] = $k;
                        }
                    } else {
                        $field[$k] = $v;
                    }
                }
            }
            foreach ($value as $k => &$v) {
                $this->setInfo($v, $data, $remote, $show, $source, $default, 2, $field);
                $option[$k] = $data['option'];
            }
            if (isset($data['reset'])) {
                foreach ($data['reset'] as $k1 => $v1) {
                    if (isset($data['option'][$k1])) {
                        unset($data['option'][$k1]);
                        $data['field'][$k1] = $v1;
                    }
                }
            }
        } else {
            if ($remote) {
                $info = [];
                foreach ($remote as $k => $v) {
                    $data['update'][$v[0]]['remote'] = Dever::url($v[1], ['value' => '', 'table' => $this->db->config['load'], 'id' => false]);
                    if (isset($v[2]) && isset($data['option'][$v[2]]) && $data['option'][$v[2]] && $m = Dever::issets($data['option'][$v[2]][0], 'id')) {
                        $result = Dever::call($v[1], [$m, $this->db->config['load'], false]);
                        if ($result) {
                            $this->setUpdate($info, $data, $result);
                        }
                    }
                }
            }
            if ($show) {
                foreach ($show as $k => $v) {
                    $data['update'][$v[0]]['show'] = true;
                }
            }

            if ($default) {
                foreach ($default as $k => $v) {
                    $data['update'][$v[0]]['value'] = $this->getShow($v[1], []);
                    $data['field'][$k] = $data['update'][$v[0]]['value'];
                }
            }
        }
        
        $data['desc'] = $this->config['desc'] ?? '';
        $data['drag'] = $this->config['drag'] ?? false;
        $this->layout($data);
        $data['control'] = $this->control($data);
        $this->tab($data, 'step');
        if (!$data['step']) {
            $this->tab($data);
        }
        return $data;
    }

    private function setInfo(&$info, &$data, $remote, $show, $source, $default, $type = 1, $field = [])
    {
        if ($source) {
            foreach ($source as $k => $v) {
                $t = [];
                foreach ($v as $v1) {
                    $t[] = $info[$v1] ?? '';
                }
                $info[$k] = implode(',', $t);
            }
        }
        foreach ($info as $k => $v) {
            if ($v === null) {
                $v = '';
            }
            if (isset($data['field'][$k])) {
                if (is_array($data['field'][$k])) {
                    if ($v) {
                        $v = explode(',', $v);
                        foreach ($v as $k1 => $v1) {
                            if (is_numeric($v1)) {
                                $v[$k1] = (float) $v1;
                            }
                        }
                    } else {
                        $v = [];
                    }
                    $info[$k] = $v;
                }
                # 处理一下select，后续优化
                if (isset($field[$k]) && isset($field[$k]['type']) && $field[$k]['type'] == 'select') {
                    if ($v) {
                        $v = (int) $v;
                    } else {
                        $v = $info[$k] = '';
                    }
                }

                if (isset($field[$k]) && isset($field[$k]['update'])) {
                    $v = $field[$k]['update'];
                }
                if ($k == 'cdate') {
                   $field[$k]['type'] = 'date';
                }
                if (isset($field[$k]) && isset($field[$k]['type']) && $field[$k]['type'] == 'date' && $v) {
                    $v = date('Y-m-d H:i:s', $v);
                }
                if ($type == 1 && $v) {
                    $data['field'][$k] = $v;
                }
                if (isset($remote[$k])) {
                    $data['update'][$remote[$k][0]]['remote'] = Dever::url($remote[$k][1], ['value' => '', 'table' => $this->db->config['load'], 'id' => false]);
                    if ($field[$k]['type'] == 'cascader' && !isset($field[$k]['option'])) {
                        
                    } else {
                        $result = Dever::call($remote[$k][1], [$v, $this->db->config['load'], $info['id'] ?? false]);
                        if ($result) {
                            $this->setUpdate($info, $data, $result);
                        }
                    }
                }
                if (isset($show[$k])) {
                    $data['update'][$show[$k][0]]['show'] = $this->getShow($show[$k][1], $info);
                    $info[$k] = $data['update'][$show[$k][0]]['show'];
                }
            }
            if (isset($field[$k]) && isset($field[$k]['index'])) {
                foreach ($field[$k]['index'] as $v1) {
                    $info[$v1] = $v;
                }
            }
        }
        if ($default) {
            foreach ($default as $k => $v) {
                $data['update'][$v[0]]['value'] = $this->getShow($v[1], $info);
                $info[$k] = $data['field'][$k] = $data['update'][$v[0]]['value'];
            }
        }
    }

    private function setUpdate(&$info, &$data, $result, $remote = [])
    {
        foreach ($data['update'] as $k => $v) {
            if (isset($result[$v['key']])) {
                # 批量更新时，默认数据需要重置
                if (!isset($data['reset'][$v['key']])) {
                    $data['reset'][$v['key']] = $data['field'][$v['key']];
                }
                if (isset($result[$v['key']]['option'])) {
                    $data['option'][$v['key']] = $result[$v['key']]['option'];
                    unset($result[$v['key']]['option']);
                }
                if (empty($data['field'][$v['key']]) && isset($result[$v['key']]['value'])) {
                    if (is_array($data['field'][$v['key']]) && !is_array($result[$v['key']]['value'])) {
                        $data['field'][$v['key']] = explode(',', $result[$v['key']]['value']);
                    } else {
                        $data['field'][$v['key']] = $result[$v['key']]['value'];
                    }
                }
                if (isset($result[$v['key']]['set']) && $info) {
                    $info = array_merge($info, $result[$v['key']]['set']);
                }
                $data['update'][$k] = array_merge($data['update'][$k], $result[$v['key']]);
            }
        }
    }

    private function control(&$data)
    {
        $result = [];
        if (isset($this->config['control']) && $this->config['control']) {
            foreach ($this->config['control'] as $k => $v) {
                if (is_string($v)) {
                    parse_str($v, $v);
                }
                if (strstr($k, ',')) {
                    $k = explode(',', $k);
                    foreach ($k as $k2) {
                        if (isset($data['update'])) $this->controlShow($data, $k2, $v);
                        $result[$k2] = $v;
                    }
                } else {
                    if (isset($data['update'])) $this->controlShow($data, $k, $v);
                    $result[$k] = $v;
                }
            }
        }
        return $result;
    }

    private function controlShow(&$data, $k, $v)
    {
        foreach ($data['update'] as $k1 => $v1) {
            if ($v1['key'] == $k) {
                $show = true;
                foreach ($v as $k2 => $v2) {
                    if (is_array($v2)) {
                        $temp = false;
                        foreach ($v2 as $k3 => $v3) {
                            if (is_array($data['field'][$k2]) && in_array($v3, $data['field'][$k2])) {
                                $temp = true;
                            } elseif ($data['field'][$k2] == $v3) {
                                $temp = true;
                            }
                        }
                        $show = $temp;
                    } else {
                        if (is_array($data['field'][$k2]) && !in_array($v2, $data['field'][$k2])) {
                            $show = false;
                        } elseif ($data['field'][$k2] != $v2) {
                            $show = false;
                        }
                    }
                }
                $data['update'][$k1]['show'] = $show;
                if ($show == false && isset($data['update'][$k1]['field'])) {
                    # 对重新命名的字段删除
                    if ($data['update'][$k1]['type'] == 'editor') {
                        $data['field'][$v1['key']] = '';
                    } else {
                        unset($data['field'][$v1['key']]);
                    }
                }
            }
        }
    }

    private function tab(&$data, $type = 'tab')
    {
        $field = $this->input('field', '');
        $data[$type] = [];
        //if (empty($data['layout']) && !$field && isset($this->config[$type])) {
        if (empty($data['layout']) && isset($this->config[$type])) {
            foreach ($this->config[$type] as $k => $v) {
                if (is_string($v)) {
                    $field = [];
                    $data[$type][] = array
                    (
                        'name' => $k,
                        'update' => $this->getUpdate($v, $data['update'], $field),
                        'field' => $field,
                    );
                } else {
                    $field = [];
                    $result = [];
                    $result['name'] = $k;
                    foreach ($v as $v1) {
                        $result['layout'][] = $this->getUpdate($v1, $data['update'], $field);
                    }
                    $result['field'] = $field;
                    $data[$type][] = $result;
                }
            }
            $data['update'] = [];
        }
    }

    private function layout(&$data)
    {
        $field = $this->input('field', '');
        $data['layout'] = [];
        if (isset($this->config['layout'])) {
            foreach ($this->config['layout'] as $k => $v) {
                $field = [];
                $data['layout'][] = $this->getUpdate($v, $data['update'], $field, '100%');
            }
            $data['update'] = [];
        }
    }

    private function getUpdate($set, $update, &$field, $width = '')
    {
        $result = [];
        if (is_string($set)) {
            $set = explode(',', $set);
            foreach ($set as $k => $v) {
                foreach ($update as $value) {
                    if ($value['key'] == $v) {
                        $width && $value['width'] = $width;
                        $result[] = $value;
                        $field[] = $v;
                    }
                }
            }
        } else {
            foreach ($set as $k => $v) {
                foreach ($update as $value) {
                    if ($value['key'] == $k) {
                        $width && $value['width'] = $width;
                        $result[] = array('span' => $v, 'update' => [$value]);
                        $field[] = $k;
                    }
                }
            }
        }
        return $result;
    }

    public function do_commit(){}
    public function do()
    {
        $this->checkFunc();
        $update = [];
        $this->setting('field', $update, true, 'text');
        if (empty($this->config['upAdmin'])) {
            $this->config['upAdmin'] = false;
        }
        if ($update) {
            $data = $other = $sku = [];
            $input = base64_decode(Dever::input('data'));
            $input = Dever::json_decode($input);
            $id = Dever::input('id');
            $control = $this->control($data);
            foreach ($update as $k => $v) {
                if (isset($input[$v['key']])) {
                    if (isset($v['rules'])) {
                        $this->checkRules($v, $input[$v['key']]);
                    }
                    if ($v['type'] == 'sku') {
                        if (isset($input[$v['key'] . '_spec']) && isset($input[$v['key']])) {
                            $sku[$v['key']] = [$v['where'], $v['content']['field'], $v['spec'], $v['spec_field'], $input[$v['key'] . '_spec'], $input[$v['key']]];
                        }
                        
                    } elseif (strpos($v['key'], '/') && $v['type'] != 'hidden') {
                        if (isset($v['field'])) {
                            $value = $input[$v['key']] ?? false;
                            if (strpos($v['key'], '#')) {
                                $v['key'] = str_replace('#', '', $v['key']);
                            }
                            $other_id = $input[$v['key'] . '_id'] ?? 0;
                            $value = array
                            (
                                0 => ['id' => $other_id, $v['field'] => $value]
                            );
                            if (isset($other[$v['key']])) {
                                $other[$v['key']][3][0] += $value[0];
                            } else {
                                $other[$v['key']] = [$v['where'], false, false, $value];
                            }
                        } else {
                            $other[$v['key']] = [$v['where'], $v['content']['field'], $v['content']['drag'], $input[$v['key']]];
                        }
                    } else {
                        $this->doData($data, $v['key'], $input[$v['key']], $this->config['field'], $control);
                    }
                } elseif ($id) {
                    $data[$v['key']] = '';
                }
                if (isset($data[$v['key']]) && !$data[$v['key']] && isset($v['empty']) && !$v['empty']) {
                    unset($data[$v['key']]);
                }
            }
            if (!$data && !$other && !$sku) {
                Dever::error('无效数据');
            }
            if ($data) {
                if (isset($this->config['check']) && $this->config['check']) {
                    $this->exists($this->db, $this->config['check'], $id, $data, $this->config['field']);
                }
                $result = $this->start($id, $data);
                if ($result == 'end') {
                    return ['msg' => '操作成功', 'upAdmin' => $this->config['upAdmin']];
                }
                if ($id) {
                    $info = $this->db->find($id);
                    if ($info) {
                        $state = $this->db->update($info['id'], $data);
                        if ($state) {
                            $id = $info['id'];
                        }
                    } else {
                        $data['id'] = $id;
                        $id = $this->db->insert($data);
                    }
                } else {
                    $id = $this->db->insert($data);
                }
            }
            if (!$id) {
                Dever::error('操作失败');
            }
            $this->other($id, $data, $other);
            $this->sku($id, $data, $sku);
            $this->end($id, $data);
            return ['msg' => '操作成功', 'upAdmin' => $this->config['upAdmin']];
        }
    }

    private function doData(&$data, $key, $value, $field = [], $control = [])
    {
        if (is_array($value)) {
            # 用最傻的办法做，往往是最好的。。
            if (isset($value[0]) && !is_array($value[0])) {
                $value = implode(',', $value);
            } else {
                if ($value) {
                    $value = Dever::json_encode($value);
                } else {
                    $value = '';
                }
            }
        }
        if (isset($field[$key]) && isset($field[$key]['field'])) {
            if ($control && isset($control[$key])) {
                # 如果有重命名字段并且是控制项，需要单独设置
                $state = true;
                foreach ($control[$key] as $k => $v) {
                    if ($data[$k] != $v) {
                        //$state = false;
                    }
                }
                if ($state) {
                    $key = $field[$key]['field'];
                }
            } else {
                $key = $field[$key]['field'];
            }
        }
        if ($value && isset($field[$key]) && $handle = Dever::issets($field[$key], 'handle')) {
            $value = Dever::call($handle, [$value]);
            if (is_array($value) && isset($value[$key])) {
                foreach ($value as $k => $v) {
                    $data[$k] = trim($v);
                }
                return;
            }
        } elseif (isset($field[$key]) && isset($field[$key]['type']) && $field[$key]['type'] == 'date' && $value) {
            $value = \Dever\Helper\Date::mktime($value);
        }
        /*
        if (empty($data[$key])) {
            $data[$key] = trim($value);
        }
        */
        $data[$key] = trim($value);
        if (!$data[$key] && isset($field[$key]) && isset($field[$key]['default'])) {
            $data[$key] = $field[$key]['default'];
        }
    }

    private function exists($db, $check, $id, $data, $field)
    {
        $check = explode(',', $check);
        $where = [];
        $name = [];
        foreach ($check as $k => $v) {
            if (isset($data[$v]) && $data[$v]) {
                if (isset($field[$v]) && isset($field[$v]['name'])) {
                    $n = $field[$v]['name'];
                } elseif (isset($db->config['struct'][$v])) {
                    $n = $db->config['struct'][$v]['name'];
                } else {
                    $n = $v;
                }
                $where[$v] = $data[$v];
                $name[] = $n;
            }
        }
        if ($where) {
            if ($id) {
                $where['id'] = ['!=', $id];
            }
            $info = $db->find($where);
            if ($info) {
                $name = implode('、', $name);
                Dever::error($name . '已存在');
            }
        }
    }

    private function start($id, &$data)
    {
        if (isset($this->config['start']) && $this->config['start']) {
            $data['id'] = $id;
            if (is_array($this->config['start'])) {
                $result = $data;
                foreach ($this->config['start'] as $k => $v) {
                    $result = Dever::call($v, [$this->db, $result]);
                }
            } else {
                $result = Dever::call($this->config['start'], [$this->db, $data]);
            }
            if ($result) {
                if ($result == 'end') {
                    return $result;
                }
                if (is_object($result)) {
                    $this->db = $result;
                } else {
                    $data = $result;
                }
            }
        }
    }

    private function end($id, $data)
    {
        if (isset($this->config['end']) && $this->config['end']) {
            $data['id'] = $id;
            if (is_array($this->config['end'])) {
                foreach ($this->config['end'] as $k => $v) {
                    Dever::call($v, [$this->db, $data]);
                }
            } else {
                Dever::call($this->config['end'], [$this->db, $data]);
            }
        }
    }

    private function other($rid, $data, $other)
    {
        if ($other) {
            foreach ($other as $k => $v) {
                if (strpos($k, '#')) {
                    $k = str_replace('#', '', $k);
                }
                $set = new Update($k, false);
                $common = $v[0];
                $update = $v[1];
                $drag = $v[2];
                $input = $v[3];
                $value = [];
                foreach ($input as $k1 => $v1) {
                    if (isset($v1['id']) && $v1['id']) {
                        $value['id'] = $v1['id'];
                    }
                    foreach ($common as $k2 => $v2) {
                        if (!is_array($v2)) {
                            if ($v2 == 'id') {
                                $value[$k2] = $rid;
                            } elseif (isset($data[$v2])) {
                                $value[$k2] = $data[$v2];
                            } else {
                                $value[$k2] = $v2;
                            }
                        }
                    }
                    if ($update) {
                        foreach ($update as $k2 => $v2) {
                            if (isset($v1[$k2])) {
                                $this->doData($value, $k2, $v1[$k2], $set->config['field']);
                            } else {
                                $value[$k2] = '';
                            }
                        }
                    } else {
                        $value += $v1;
                    }
                    if ($drag) {
                        $value[$drag] = $k1+1;
                    }
                    $db = Dever::db($k);
                    if (isset($db->config['manage']['update']['check'])) {
                        $this->exists($db, $db->config['manage']['update']['check'], $value['id'], $value, $db->config['manage']['update']['field']);
                    }
                    if (isset($value['id']) && $value['id'] > 0) {
                        $id = $value['id'];
                        unset($value['id']);
                        $db->update($id, $value);
                    } else {
                        $db->insert($value);
                    }
                }
            }
        }
    }

    private function sku($rid, $data, $sku)
    {
        if ($sku) {
            if (isset($data['spec_type']) && $data['spec_type'] <= 2) {
                return;
            }
            foreach ($sku as $k => $v) {
                if (strpos($k, '#')) {
                    $k = str_replace('#', '', $k);
                }
                $common = $v[0];
                $update = $v[1];
                $spec_table = $v[2];
                $spec_value_table = $spec_table . '_value';
                $spec_field = $v[3];
                $spec = $v[4];
                $input = $v[5];
                $spec_value = [];
                Dever::db($spec_table)->update([$spec_field => $rid], ['state' => 2]);
                Dever::db($spec_value_table)->update([$spec_field => $rid], ['state' => 2]);
                foreach ($spec as $k1 => &$v1) {
                    $spec_data = [];
                    $spec_data['state'] = 1;
                    $spec_data[$spec_field] = $rid;
                    $spec_data['name'] = $v1['name'];
                    $spec_data['sort'] = $k1+1;
                    if (isset($v1['id']) && $v1['id']) {
                        Dever::db($spec_table)->update($v1['id'], $spec_data);
                    } else {
                        $v1['id'] = Dever::db($spec_table)->insert($spec_data);
                    }
                    if ($v1['id']) {
                        foreach ($v1['value'] as $k2 => &$v2) {
                            $spec_value_data = [];
                            $spec_value_data['state'] = 1;
                            $spec_value_data[$spec_field] = $rid;
                            $spec_value_data['spec_id'] = $v1['id'];
                            $spec_value_data['value'] = $v2['value'] ?? $v2['name'];
                            $spec_value_data['pic'] = $v2['pic'] ?? '';
                            $spec_value_data['sort'] = $k2+1;
                            $spec_value_data['is_checked'] = $v2['checked'] == 'true' ? 1 : 2;
                            if (isset($v2['id']) && $v2['id']) {
                                Dever::db($spec_value_table)->update($v2['id'], $spec_value_data);
                            } else {
                                $v2['id'] = Dever::db($spec_value_table)->insert($spec_value_data);
                            }
                            $spec_value[$v1['key']][$spec_value_data['value']] = [$v2['id'], $spec_data['sort']];
                        }
                    }
                }
                Dever::db($spec_table)->delete([$spec_field => $rid, 'state' => 2]);
                Dever::db($spec_value_table)->delete([$spec_field => $rid, 'state' => 2]);
                Dever::db($k)->update([$spec_field => $rid], ['state' => 2]);
                foreach ($input as $k1 => $v1) {
                    $value = [];
                    if (isset($v1['id'])) {
                        $value['id'] = $v1['id'];
                    }
                    foreach ($common as $k2 => $v2) {
                        if (!is_array($v2)) {
                            if ($v2 == 'id') {
                                $value[$k2] = $rid;
                            } elseif (isset($data[$v2])) {
                                $value[$k2] = $data[$v2];
                            } else {
                                $value[$k2] = $v2;
                            }
                        }
                    }
                    foreach ($update as $k2 => $v2) {
                        if (isset($v1[$k2])) {
                            if (is_array($v1[$k2])) {
                                $v1[$k2] = implode(',', $v1[$k2]);
                            }
                            $value[$k2] = $v1[$k2];
                        }
                    }
                    $value['key'] = [];
                    foreach ($v1 as $k2 => $v2) {
                        if (isset($spec_value[$k2]) && isset($spec_value[$k2][$v2])) {
                            $value['key'][$spec_value[$k2][$v2][1]] = $spec_value[$k2][$v2][0];
                        }
                    }
                    if ($value['key']) {
                        $value['key'] = implode(',' , $value['key']);
                    }
                    $value['state'] = 1;
                    if (isset($value['id']) && $value['id'] > 0) {
                        $id = $value['id'];
                        unset($value['id']);
                        Dever::db($k)->update($id, $value);
                    } else {
                        Dever::db($k)->insert($value);
                    }
                }
                Dever::db($k)->delete([$spec_field => $rid, 'state' => 2]);
            }
        }
    }
}