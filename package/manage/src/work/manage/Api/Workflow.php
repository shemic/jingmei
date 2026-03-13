<?php namespace Work\Manage\Api;
use Dever;
class Workflow
{
    # 获取节点类型值
    public function getTypeValue($value, $table, $id)
    {
        if ($value) {
            $result = [];
            $result['type_value']['clearable'] = true;
            $result['type_value']['value'] = '';
            if ($value == 'agent') {
                $result['type_value']['name'] = '智能体';
                $result['type_value']['placeholder'] = '请选择智能体';
                $result['type_value']['type'] = 'cascader';
                $result['type_value']['option'] = Dever::load(\Work\Manage\Lib\Agent::class)->getList();
            } elseif ($value == 'tool') {
                $result['type_value']['name'] = '工具';
                $result['type_value']['placeholder'] = '请选择工具';
                $result['type_value']['type'] = 'cascader';
                $result['type_value']['option'] = Dever::load(\Work\Manage\Lib\Tool::class)->getList();
            } elseif ($value == 'shenzhu') {
                $result['type_value']['name'] = 'AI助理';
                $result['type_value']['placeholder'] = '请选择AI助理';
                $result['type_value']['type'] = 'cascader';
                $result['type_value']['option'] = Dever::load(\Shenzhu\Manage\Lib\Role::class)->getList();
            } elseif ($value == 'workflow') {
                $result['type_value']['name'] = '工作流';
                $result['type_value']['placeholder'] = '请选择工作流';
                $result['type_value']['type'] = 'cascader';
                $result['type_value']['option'] = Dever::load(\Work\Manage\Lib\Workflow::class)->getList();
            } elseif ($value == 'parallel') {
                $result['type_value']['name'] = '多个工作流';
                $result['type_value']['placeholder'] = '请选择工作流';
                $result['type_value']['type'] = 'cascader';
                $result['type_value']['multiple'] = true;
                $result['type_value']['option'] = Dever::load(\Work\Manage\Lib\Workflow::class)->getList();
            } else {
                $result['type_value']['name'] = '用户反馈';
                $result['type_value']['type'] = 'show';
                $result['type_value']['value'] = '无需设置';
            }
            
            return $result;
        }
    }

    # 获取输入项来源
    public function getSource()
    {
        return Dever::load(\Manage\Lib\Util::class)->cascader(3, function($level, $parent) {
            if ($level == 1) {
                $data = [1 => '业务应用'];
            } elseif ($level == 2) {
                if ($parent == 1) {
                    $data = Dever::db('service/info')->select(['status' => 1]);
                }
            } elseif ($level == 3) {
                $data = Dever::db('service/app')->select(['service_id' => $parent, 'status' => 1]);
            }
            return $data;
        });
    }

    # 获取默认值
    public function getFormDefault($value, $table, $id)
    {
        if ($value) {

            if ($id) {
                $info = Dever::db($table)->find($id);
            }
            $result = [];
            if ($value == 'radio') {
                $result['default']['type'] = 'select';
                $result['default']['option'] = [
                    ['id' => 'yes', 'name' => '是'],
                    ['id' => 'no', 'name' => '否'],
                ];
                $result['default']['value'] = (isset($info) && $info['default']) ? $info['default'] : 'yes';
            } elseif ($value == 'select') {
                $result['default']['type'] = 'select';
                $result['default']['option'] = Dever::db('work/workflow_input_option')->select(['workflow_input_id' => $id, 'status' => 1], ['col' => 'value as id,name']);
                if (isset($result['default']['option1']) && $result['default']['option']) {
                    $result['default']['value'] = (isset($info) && $info['default']) ? $info['default'] : $result['default']['option'][0]['id'];
                } else {
                    $result['default']['type'] = 'show';
                    $result['default']['value'] = '第一个选项';
                }
            } else {
                $result['default']['type'] = 'show';
                $result['default']['value'] = '第一条数据';
            }
            
            return $result;
        }
    }
}