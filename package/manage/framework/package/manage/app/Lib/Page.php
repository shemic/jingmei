<?php namespace Manage\Lib;
use Dever;
# 通用页面 项目着急上线 以后优化并封装
class Page extends Auth
{
    protected $db;
    protected $key;
    protected $id = 0;
    protected $input = false;
    protected $recycler = false;
    protected $menu = [];
    protected $config = [];
    protected $field = [];
    public $info = [];
    public function __construct($key = '', $load = '', $input = true, $config = [])
    {
        parent::__construct();
        $this->key = $key;
        $this->input = $input;
        $this->id = $this->input('id', 0);
        if (!$load) {
            $load = Dever::input('load');
        }
        list($this->db, $this->menu) = Dever::load(Util::class)->db($load);
        if ($this->menu && $this->menu['show'] == 1) {
            $this->checkMenu($this->menu['id'], false);
        }
        $this->config = $this->db->config['manage'][$key] ?? $this->db->config['manage'];
        if ($config) {
            $this->config = array_merge($this->config, $config);
        }
        if ($this->id && !strstr($this->id, ',')) {
            $this->info = $this->db->find($this->id);
        }
    }

    public function setting($key, &$data, $struct = true, $type = 'show', $disable = false)
    {
        if (empty($this->config[$key]) && $struct && isset($this->db->config['struct']) && $this->db->config['struct']) {
            $this->config[$key] = $this->db->config['struct'];
        }
        if (empty($this->config[$key])) {
            return;
        }
        $setting = $this->config[$key];
        if (is_string($setting)) {
            $setting = explode(',', $setting);
        }
        $field = $this->input('field', '');
        if ($field && is_string($field) && strstr($field, 'dever_')) {
            $field = '';
        }
        return $this->setData($key, $setting, $data, $field, $type, $disable);
    }

    # 获取某个数据的具体展示值
    public function getValue($key, $value, $data, $field = [])
    {
        if ($key == 'cdate') {
            $this->db->config['manage']['update']['field'][$key]['type'] = 'date';
        }
        $update = $this->db->config['manage']['update']['field'] ?? [];
        if ($show = Dever::issets($field, 'show')) {
            $value = $this->getShow($show, $data);
        } elseif ($value && isset($this->db->config['struct'][$key]['value']) && $this->db->config['struct'][$key]['value']) {
            $value = $this->db->value($key, $value);
        } elseif ($value && (isset($update[$key]) && isset($update[$key]['type']) && $update[$key]['type'] == 'date')) {
            if (isset($update[$key]['date_type']) && $update[$key]['date_type'] == 'year') {
                if ($update[$key]['date_type'] == 'year') {
                    $value = date('Y', $value);
                } elseif ($update[$key]['date_type'] == 'month') {
                    $value = date('Ym', $value);
                } else {
                    $value = date('Ymd', $value);
                }
            } else {
                if (strstr($value, 'T')) {
                    $value = Dever::cdate('Y-m-d H:i:s', strtotime($value));
                } elseif (is_numeric($value)) {
                    $value = Dever::cdate('Y-m-d H:i:s', $value);
                } else {
                    $value = '-';
                }
            }
        }
        
        return $value;
    }

    # 获取关联数据
    public function getOther($key, $set, $data)
    {
        $where = $config = [];
        if (isset($set['where'])) {
            foreach ($set['where'] as $k => $v) {
                if (!is_array($v) && is_string($v)) {
                    if (isset($data[$v])) {
                        $where[$k] = $data[$v];
                        continue;
                    }
                    // 约定：字段引用但当前数据不存在时跳过，避免把 "id" 当字面量传入
                    if ($v === 'id' || $v === $k) {
                        continue;
                    }
                }
                $where[$k] = $v;
            }
        }
        if (isset($set['col'])) {
            $config['col'] = $set['col'];
        }
        if ($where) {
            return Dever::db($key)->select($where, $config);
        }
        return [];
    }

    public function getShow($show, $data, $state = false)
    {
        return \Dever\Helper\Str::val($show, $data, $state);
    }

    # 获取菜单标题
    public function getTitle()
    {
        return $this->menu['name'];
    }

    # 获取左侧分栏
    protected function column(&$data, $name = '左侧分栏')
    {
        $data['column'] = false;
        if (isset($this->config['column'])) {
            if (empty($this->config['column']['hidden'])) {
                $data['column'] = $this->config['column'];
                if (isset($this->config['column']['add'])) {
                    $data['column']['add'] = array('name' => $this->config['column']['add'], 'func' => $this->getFunc('column_add', $name . '-' . $this->config['column']['add'], 101));
                    if (isset($this->config['column']['add_field'])) {
                        $data['column']['add']['field'] = $this->config['column']['add_field'];
                    }
                }
                if (isset($this->config['column']['edit'])) {
                    $data['column']['edit'] = array('name' => '编辑', 'func' => $this->getFunc('column_edit', $name . '-编辑', 102));
                }
                if (isset($this->config['column']['delete'])) {
                    $data['column']['delete'] = array('name' => '删除', 'func' => $this->getFunc('column_delete', $name . '-删除', 103));
                }
                $data['column']['data'] = $this->config['column']['data'];
                if (is_string($data['column']['data'])) {
                    $data['column']['data'] = Dever::call($data['column']['data']);
                }
                $data['height'] = '100%';
            }
            
            if (isset($this->config['column']['active']) && $this->config['column']['where'] == 'id') {
                return $this->config['column']['active'];
            }
        }
    }

    # 通用的规则验证 一般为更新数据时使用
    protected function checkRules($set, $data)
    {
        if ($set['rules']) {
            if (!is_array($set['rules'])) {
                $set['rules'] = array
                (
                    [
                        'required' => true,
                        'trigger' => 'blur',
                        'message' => $set['name'] . '不能为空',
                    ],
                );
            }
            foreach ($set['rules'] as $k => $v) {
                if (isset($v['required']) && $v['required'] && !$data && $data !== '0') {
                    Dever::error($v['message']);
                }
                if ($data || $data === '0') {
                    if (isset($v['pattern']) && $v['pattern'] && !preg_match('/' . $v['pattern'] . '/', $data)) {
                        Dever::error($v['message']);
                    }
                    if (isset($v['type']) && $v['type']) {
                        if ($v['type'] == 'number' && !is_numeric($data)) {
                            Dever::error($v['message']);
                        } elseif ($v['type'] == 'array' && !is_array($data)) {
                            Dever::error($v['message']);
                        } elseif ($v['type'] == 'integer' && !is_int($data)) {
                            Dever::error($v['message']);
                        } elseif ($v['type'] == 'float' && !is_float($data)) {
                            Dever::error($v['message']);
                        } elseif ($v['type'] == 'string' && !is_string($data)) {
                            Dever::error($v['message']);
                        } elseif ($v['type'] == 'boolean' && !is_bool($data)) {
                            Dever::error($v['message']);
                        } elseif ($v['type'] == 'url' && !filter_var($data, FILTER_VALIDATE_URL)) {
                            Dever::error($v['message']);
                        } elseif ($v['type'] == 'email' && !filter_var($data, FILTER_VALIDATE_EMAIL)) {
                            Dever::error($v['message']);
                        } elseif ($v['type'] == 'enum' && isset($v['enum']) && !in_array($data, $v['enum'])) {
                            Dever::error($v['message']);
                        }
                    }
                    if (isset($v['len']) && $v['len'] && strlen($data) > $v['len']) {
                        Dever::error($v['message']);
                    }
                    if (isset($v['min']) && $v['min'] && strlen($data) < $v['min']) {
                        Dever::error($v['message']);
                    }
                    if (isset($v['max']) && $v['max'] && strlen($data) > $v['max']) {
                        Dever::error($v['message']);
                    }
                }
            }
        }
    }

    private function setData($key, $setting, &$data, $field, $type, $disable)
    {
        $result = [];
        foreach ($setting as $k => $v) {
            $this->setDefault($key, $k, $v);
            if (!is_array($v)) {
                if (is_numeric($k)) {
                    $k = $v;
                    $v = $type;
                }
                if ($k == 'id') {
                    $v = ['name' => 'ID', 'type' => $v];
                } elseif ($k == 'cdate') {
                    $v = ['name' => '创建时间', 'type' => $v];
                } elseif(isset($this->db->config['struct'][$k])) {
                    $v = ['name' => $this->db->config['struct'][$k]['name'], 'type' => $v];
                } else {
                    $v = ['name' => $v];
                }
            } else {
                if (isset($v['only'])) {
                    if ($v['only'] == 'edit' && !$this->id) {
                        continue;
                    } elseif ($v['only'] == 'add' && $this->id) {
                        continue;
                    }
                }
            }
            if ($field) {
                if (is_string($field) && (strstr($field, '{') || strstr($field, '%7B'))) {
                    $field = htmlspecialchars_decode($field);
                    $field = Dever::json_decode($field);
                }
                if (is_array($field)) {
                    if (isset($field['param'])) {
                        if (isset($field['param']['set'])) {
                            $field = array_merge($field, $field['param']['set']);
                        }
                        if (isset($field['param']['search'])) {
                            $field = array_merge($field, $field['param']['search']);
                        }
                    } elseif (isset($field['field']) && !Dever::check($field['field'], $k)) {
                        continue;
                    }
                    if (isset($field[$k]) && $field[$k] != $k) {
                        $v['default'] = $field[$k];
                        $v['type'] = 'hidden';
                    }
                } elseif (!Dever::check($field, $k)) {
                    continue;
                }
            }
            $info = $this->setField($data, $k, $v, $field, $type, $disable);
            if ($info) {
                $result[] = $info;
            }
        }
        return $result;
    }

    private function setField(&$data, $key, $value, $field, $type = 'show', $disable = false)
    {
        if (empty($value['align'])) {
            $value['align'] = 'center';
        }
        $value['key'] = $key;
        $this->setName($value);
        # 对每个字段进行权限设置
        if (isset($value['func']) && $value['func']) {
            $func = $this->getFunc('field_' . $value['key'], '字段-' . $value['name'], 200);
            if (!$func) {
                return false;
            }
        }
        if (strpos($key, '/') && $this->key == 'update') {
            $this->setType($value, 'table');
            $this->setShow($value);
            if (strpos($key, '#')) {
                $key = str_replace('#', '', $key);
            }
            $sku = $value['type'] == 'sku' ? true : false;
            $value['value'] = $this->getOther($key, $value, $this->info);
            if (isset($value['default'])) {
                $value['value'] += $value['default'];
            }
            if (isset($value['field'])) {
                list($app, $table) = explode('/', $key);
                $set = Dever::project($app);
                $manage = @include($set['path'] . 'manage/'.$table.'.php');
                if (isset($manage['update']['field'][$value['field']])) {
                    $value = array_merge($value, $manage['update']['field'][$value['field']]);
                }
                if ($value['value']) {
                    $data[$key . '_id'] = [
                        'key' => $key . '_id',
                        'type' => 'hidden',
                        'value' => $value['value'][0]['id'],
                    ];
                    $value['value'] = $value['value'][0][$value['field']];
                }
                $this->setRules($value);
                $this->setForm($value, $field);
                $data[] = $value;
                return $value['name'] ?? 'test';
            }
            $update = new \Manage\Api\Page\Update($key, false);
            $value['option'] = [];
            $value['content'] = $update->get($value['value'], $value['option'], $sku);
            $data[] = $value;
            return $value['name'];
        } else {
            $this->setType($value, $type);
            $this->setDisable($value, $disable);
            if ($this->key == 'update') {
                # 一般为更新页面需要的参数
                $this->setShow($value);
                $this->setRules($value);
            } elseif (isset($value['remote']) && !strstr($value['remote'], 'Dever')) {
                $value['remote'] = Dever::url($value['remote']);
            }
            if ($type == 'show') {
                if (!isset($value['truncate'])) {
                    $value['truncate'] = false;
                }
                $in = ['switch', 'select', 'input'];
                if (in_array($value['type'], $in)) {
                    $value['func'] = $this->getFunc('list_edit_' . $value['key'], '列表更新-' . $value['name'], 104);
                    if (!$value['func']) {
                        $value['type'] = 'show';
                        if (isset($value['show'])) {
                            unset($value['show']);
                        }
                    }
                }
                if (isset($value['child'])) {
                    $child = [];
                    $this->setData($value['child'], $child, $field, $type, $disable);
                    $value['child'] = $child;
                } else {
                    $this->field[$key] = $value;
                }
            }
            $this->setForm($value, $field);
            if (empty($value['width'])) {
                $value['width'] = 'auto';
                if ($this->key == 'update') {
                    $fast = Dever::input('fast');
                    $value['width'] = '50%';
                    if (!$this->input || (empty($value['option']) && $fast == 1) || $value['type'] == 'editor') {
                        $value['width'] = '100%';
                    }
                }
            }
            $data[] = $value;
            return $value['name'] ?? 'test';
        }
    }

    private function setDefault($key, &$k, &$v)
    {
        if ($v == 'cdate') {
            $k = $v;
            $v = [
                'name' => '创建时间',
                'type' => 'date',
                'date_type' => 'datetimerange',
                'value_format' => 'YYYY-MM-DD HH:mm:ss',
                'start_placeholder' => '开始日期',
                'end_placeholder' => '结束日期',
                'range_separator' => '至',
                'truncate' => true,
            ];
        } elseif ($v == 'sort') {
            $k = $v;
            $v = [
                'type' => 'input',
                'tip' => '双击修改，正序排序',
                'width' => '90px',
            ];
        } elseif ($key != 'search' && $v == 'status') {
            if (isset($this->db->config['struct']['status']['value']) && count($this->db->config['struct']['status']['value']) == 2) {
                $k = $v;
                $v = [
                    'type' => 'switch',
                    'show'  => '{status}',
                    'active_value' => 1,
                    'inactive_value' => 2,
                    'width' => '75px',
                ];
            }
        }
    }

    private function setShow(&$value)
    {
        if ($value['type'] == 'hidden') {
            $value['show'] = false;
        } elseif (!isset($value['show'])) {
            $value['show'] = true;
        }
    }

    private function setName(&$value)
    {
        if (empty($value['name']) && isset($this->db->config['struct'][$value['key']])) {
            $value['name'] = $this->db->config['struct'][$value['key']]['name'];
        }
        if (empty($value['placeholder']) && isset($value['name'])) {
            $value['placeholder'] = $value['name'];
        }
    }

    private function setType(&$value, $type)
    {
        if (empty($value['type'])) {
            $value['type'] = $type;
        }
        if (strpos($value['type'], '(')) {
            $value['type'] = $type;
        }
        if (isset($value['upload']) && Dever::project('upload')) {
            $project = $value['project'] ?? 'api';
            $upload = $this->getUpload($value['key'], $project);
            if (is_array($value['upload'])) {
                $upload += $value['upload'];
            } else {
                $upload += ['id' => $value['upload']];
            }
            if (empty($upload['id'])) {
                Dever::error('上传配置错误');
            }
            
            $value['config'] = Dever::load(\Upload\Lib\Save::class)->get($upload['id'], $project);
            $value['yun'] = false;
            if ($value['config']['method'] == 2) {
                $value['yun'] = true;
            }
            $value['url'] = Dever::url('upload/save.act', $upload, true);
            $upload['wh'] = $value['wh'] ?? '500*500';
            $value['set'] = Dever::url('image/manage.set', $upload, true);
            if (isset($value['multiple']) && $value['multiple']) {
                $value['limit'] = 10;
            } else {
                $value['limit'] = 1;
            }
        }
        if (isset($value['editorMenu'])) {
            $this->setEditorUpload($value, ['uploadImage', 'uploadVideo']);
        }

        if (isset($value['date_type']) && $value['date_type'] == 'datetimerange' && empty($value['default_time'])) {
            $value['default_time'] = array(\Dever\Helper\Date::mktime(date('Y-m-d 00:00:00'))*1000, \Dever\Helper\Date::mktime(date('Y-m-d 23:59:59'))*1000);
        }
    }

    protected function getUpload($key, $project)
    {
        $upload['cate_id'] = 1;
        $upload['group_key'] = $this->db->config['table'] . '-' . $key;
        $upload['group_name'] = $this->db->config['name'];
        $upload['user_token'] = Dever::load(Util::class)->getToken();
        $upload['user_table'] = $this->user['table'];
        $upload['user_id'] = $this->user['id'];
        $upload['project'] = $project;
        return $upload;
    }

    protected function setEditorUpload(&$value, $key)
    {
        $project = $value['project'] ?? 'api';
        $upload = $this->getUpload($value['key'], $project);
        foreach ($key as $k) {
            if ($v = Dever::issets($value['editorMenu'], $k)) {
                if (!is_array($v)) {
                    $v = ['upload' => $v];
                }
                $upload['id'] = $v['upload'];
                $v['server'] = Dever::url('upload/save.wangEditor', $upload, true);
                if (empty($v['fieldName'])) {
                    $v['fieldName'] = 'file';
                }
                $value['editorMenu'][$k] = $v;
            }
        }
    }

    private function setDisable(&$value, $disable)
    {
        if (isset($value['disable'])) {
            $disable = $value['disable'];
        }
        $value['disable'] = $disable;
    }

    # 设置值
    private function setForm(&$value, $field)
    {
        if (empty($value['value'])) {
            $value['value'] = Dever::input('search')[$value['key']] ?? '';
        }
        # (float) 转换一下是为了前端select使用，必须是数字类型
        # 这个比较特殊
        if ($value['type'] == 'select_text') {
            if ($value['value']) {
                if (is_string($value['value'])) {
                    $value['value'] = explode('|', $v['value']);
                }
                $value['value'][0] = (float) $value['value'][0];
            } else {
                $value['value'] = [1,''];
            }
        } elseif ($value['type'] == 'date') {
            if (empty($value['shortcuts']) && isset($value['date_type'])) {
                $value['shortcuts'] = Dever::load(Data::class)->getShortcuts($value['date_type']);
            }
        } elseif (is_array($value['value'])) {
            foreach ($value['value'] as &$v) {
                $v = (float) $v;
            }
        }
        if (!$value['value']) {
            if (isset($value['default']) && !strstr($value['default'], '{')) {
                $value['value'] = $value['default'];
            } elseif ($this->key == 'update' && isset($this->db->config['struct'][$value['key']]['default'])) {
                $value['value'] = $this->db->config['struct'][$value['key']]['default'];
            }
        }
        if (isset($value['option']) && $value['option']) {
            $this->db->config['option'][$value['key']] = $value['option'];
        }
        if (isset($this->db->config['load'])) {
            $send = $this->info;
            $send['table'] = $this->db->config['load'];
            if ($field && is_array($field)) {
                $send += $field;
            }
            if ($option = $this->db->value($value['key'], false, 'id,name', $send)) {
                if ($value['type'] == 'checkbox') {
                    $value['value'] = $value['value'] ? explode(',', $value['value']) : [];
                }
                $value['option'] = $option;
                if ($value['type'] == 'text') {
                    $value['type'] = 'select';
                }
                if (empty($value['clearable'])) {
                    $value['clearable'] = true;
                }
                if (isset($this->db->config['struct'][$value['key']]['type']) && !is_array($value['value']) && $value['value'] && !strstr($this->db->config['struct'][$value['key']]['type'], 'char')) {
                    $value['value'] = (float) $value['value'];
                }
            }
        }
    }

    private function setRules(&$value)
    {
        if (isset($value['rules']) && $value['rules']) {
            if (!is_array($value['rules'])) {
                $value['rules'] = array
                (
                    [
                        'required' => true,
                        'trigger' => 'blur',
                        'message' => $value['name'] . '不能为空',
                    ],
                );
            }
            foreach ($value['rules'] as $k => $v) {
                if (isset($v['only'])) {
                    if ($v['only'] == 'edit' && !$this->id) {
                        unset($value['rules'][$k]);
                        break;
                    } elseif ($v['only'] == 'add' && $this->id) {
                        unset($value['rules'][$k]);
                        break;
                    }
                }
            }
            if (!isset($value['rules'][0])) {
                $value['rules'] = array_values($value['rules']);
            }
        }
    }

    protected function input($key, $value = '')
    {
        return $this->input ? Dever::input($key) : $value;
    }

    public function button($key = 'button', $data = [], $default = true)
    {
        if (empty($this->config[$key])) {
            return [];
        }
        /*
        if (empty($this->config[$key])) {
            $num = 0;
            if (isset($this->db->config['manage']['update']['field'])) {
                $num = count($this->db->config['manage']['update']['field']);
            } elseif (isset($this->db->config['struct']) && $this->db->config['struct']) {
                $num = count($this->db->config['struct']);
            }
            $fast = 'fast';
            if ($num > 8) {
                $fast = '';
            }
            if ($key == 'button') {
                $this->config[$key] = ['新增' => $fast . 'add'];
            } elseif ($key == 'data_button') {
                $this->config[$key] = ['编辑' => $fast . 'edit'];
            } else {
                $this->config[$key] = [];
            }
        }*/
        $result = [];
        $sort = 1;
        foreach ($this->config[$key] as $k => $v) {
            $d = '';
            $p = '';
            $i = '';
            if (is_array($v)) {
                if (isset($v[3]) && $data) {
                    $d = $v[3];
                    if (strstr($d, '{')) {
                        $state = $this->getShow($d, $data, true);
                    } else {
                        parse_str($d, $t);
                        $state = false;
                        foreach ($t as $k1 => $v1) {
                            if (isset($data[$k1])) {
                                $v1 = explode(',', $v1);
                                foreach ($v1 as $v2) {
                                    if ($data[$k1] == $v2) {
                                        $state = true;
                                    }
                                }
                            }
                        }
                    }
                    if (!$state) {
                        continue;
                    }
                }
                if (isset($v[2])) {
                    $i = $v[2];
                }
                if (isset($v[1])) {
                    $p = $v[1];
                    # 针对数据隔离做单独的处理
                    if (is_array($p) && isset($p['param']['set']['authorization'])) {
                        $value = explode(',', $p['param']['set']['authorization']);
                        if (empty($value[3])) {
                            $value[3] = 0;
                        }
                        $p['param']['set']['authorization'] = Dever::load(Util::class)->setAuth($value[0], $value[1], $data[$value[2]] ?? $temvaluep[2], $data[$value[3]] ?? $value[3]);
                    }
                }
                $v = $v[0];
            }
            if (strstr($v, 'add')) {
                $icon = 'Plus';
                $button = 'primary';
            } elseif (strstr($v, 'edit')) {
                $icon = 'Edit';
                $button = 'primary';
            } elseif (strstr($v, 'view')) {
                $icon = 'View';
                $button = '';
            } elseif (strstr($v, 'drawer')) {
                $icon = 'Drawer';
                $button = '';
            } elseif ($v == 'delete') {
                if ($key == 'button') {
                    if (isset($this->config['layout'])) {
                        continue;
                    }
                    $this->config['selection'] = true;
                }
                $icon = 'Delete';
                $button = 'danger';
            } elseif ($v == 'recycle') {
                if ($key == 'button') {
                    if (isset($this->config['layout'])) {
                        continue;
                    }
                    $this->config['selection'] = true;
                }
                $icon = 'Delete';
                $button = 'danger';
            } elseif ($v == 'oper') {
                if ($key == 'button') {
                    if (isset($this->config['layout'])) {
                        continue;
                    }
                    $this->config['selection'] = true;
                }
                $icon = 'Notification';
                $button = 'warning';
            } elseif ($v == 'api') {
                if ($key == 'button') {
                    if (isset($this->config['layout'])) {
                        continue;
                    }
                    $this->config['selection'] = true;
                }
                $p = Dever::url($p);
                $icon = 'Notification';
                $button = 'warning';
            } elseif ($v == 'link') {
                $icon = 'Link';
                $button = 'success';
                $p = $this->getShow($p, $data);
            } elseif ($v == 'route') {
                $icon = 'Link';
                $button = 'success';
            } elseif ($v == 'recover') {
                $icon = 'CirclePlus';
                $button = 'info';
            } else {
                continue;
            }
            # 权限验证
            $sort++;
            $func = $this->getFunc($v, $k, $sort, $p);
            if ($this->menu && $this->menu['show'] == 1 && !$func) {
                continue;
            }
            if ($i) {
                $icon = $i;
            }
            if ($key == 'button' && $data) {
                if ($p) {
                    if (is_string($p)) {
                        $p = explode(',', $p);
                    }
                } else {
                    $p = [];
                }
                $p += $data;
            }
            $result[] = [
                'name' => $k,
                'type' => $v,
                'param' => $p,
                'icon' => $icon,
                'button' => $button,
                'func' => $func,
            ];
            if (!$this->recycler && $v == 'recycle') {
                $this->recycler = true;
            }
        }
        return $result;
    }

    # 构造搜索
    protected function search(&$where)
    {
        $search = Dever::input('search');
        $set = Dever::input('set');
        $list_search = $result = [];
        $result['form'] = $result['field'] = $result['option'] = [];
        $this->setting('search', $list_search, false, 'text');
        if ($list_search) {
            foreach ($list_search as $v) {
                if ($v['type'] != 'hidden') {
                    $result['form'][] = $v;
                    if (is_numeric($v['value'])) {
                        $v['value'] = (float) $v['value'];
                    }
                    $result['field'][$v['key']] = $v['value'];
                    if ($v['type'] == 'sku') {
                        $result['field'][$v['key'] . '_spec'] = [];
                    }
                    if (isset($v['option'])) {
                        $result['option'][$v['key']] = $v['option'];
                    }
                }
                $this->searchWhere($search, $v, $where);
                $this->searchWhere($set, $v, $where);
            }
        }
        return $result;
    }

    protected function searchWhere($value, $v, &$where)
    {
        if ($value) {
            if ($value = Dever::issets($value, $v['key'])) {
                if (isset($v['search'])) {
                    if (is_callable($v['search'])) {
                        $value = $v['search']($v['key'], $v['type'], $value);
                    } elseif (is_array($v['search']) && isset($v['search']['table'])) {
                        $v['search']['where'] = Dever::json_decode(str_replace('{value}', $value, Dever::json_encode($v['search']['where'])));
                        $search = Dever::db($v['search']['table'])->select($v['search']['where'], $v['search']['set'] ?? []);
                        $value = [];
                        if ($search) {
                            foreach ($search as $v1) {
                                $value[] = $v1[$v['search']['field']];
                            }
                        }
                        $value = implode(',', $value);
                        $v['type'] = 'in';
                        if (isset($v['search']['key'])) {
                            $v['key'] = $v['search']['key'];
                        }
                    } else {
                        $r = Dever::call($v['search'], [$value]);
                        $v['key'] = $r[0];
                        $v['type'] = $r[1];
                        $value = $r[2];
                    }
                }
                if ($v['type'] == 'select_text') {
                    if ($value[1] === '') {
                        return;
                    }
                    $result = current(array_filter($v['option'], function($item) use($value) {
                        return $item['id'] == $value[0];
                    }));
                    if (!$result) {
                        return;
                    }
                    $v['key'] = $result['value'];
                    $v['type'] = '';
                    if (strstr($v['key'], '.')) {
                        $r = Dever::call($v['key'], $value[1]);
                        if ($r) {
                            $v['key'] = $r[0];
                            $v['type'] = $r[1];
                            $value = $r[2];
                        }
                    } else {
                        if (strstr($v['key'], ' ')) {
                            $temp = explode(' ', $v['key']);
                            $v['key'] = $temp[0];
                            $v['type'] = $temp[1];
                        }
                        $value = $value[1];
                    }
                }
                if (isset($v['col'])) {
                    $temp = explode(',', $v['col']);
                    $value = explode(',', $value);
                    foreach ($temp as $tk => $tv) {
                        $where[$tv] = $value[$tk];
                    }
                } elseif ($v['type'] == 'group') {
                    $where[$v['key']] = ['group', $value];
                } elseif ($v['type'] == 'selects') {
                    $where[$v['key']] = ['group', $value];
                } elseif ($v['type'] == 'cascader') {
                    $t = $value;
                    if (is_array($value)) {
                        $t = implode(',', $value);
                    }
                    $where[$v['key']] = ['group', $t];
                    //$where[$v['key']] = $t;
                    //print_r($where);die;
                } elseif ($v['type'] == 'like') {
                    $where[$v['key']] = ['like', $value];
                } elseif ($v['type'] == 'in') {
                    $where[$v['key']] = ['in', $value];
                } elseif ($v['type'] == 'date') {
                    if (strstr($v['date_type'], 'range')) {
                        $where[$v['key']] = array('>=', \Dever\Helper\Date::mktime($value[0]));
                        $where[$v['key'] . '#'] = array('<=', \Dever\Helper\Date::mktime($value[1]));
                    } else {
                        $where[$v['key']] = $value;
                    }
                } else {
                    $where[$v['key']] = $value;
                }
            }
        }
    }
}
