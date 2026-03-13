<?php namespace Work\Manage\Lib;
use Dever;
class Workflow
{
    # 获取工作流列表
    public function getList()
    {
        return Dever::load(Common::class)->getList('cate', ['type' => 1], 'workflow');
    }

    # 获取节点列表
    public function getNodesList()
    {
        $id = Dever::input('id');
        if ($id) {
            $info = Dever::db('work/workflow_nodes')->find($id);
            $workflow_id = $info['workflow_id'];
        } else {
            $workflow_id = Dever::load(\Manage\Lib\Util::class)->request('workflow_id');
        }
        return Dever::db('work/workflow_nodes')->select(['workflow_id' => $workflow_id, 'status' => 1]);
    }

    # 获取输入项来源
    public function getSource()
    {
        $result = [];
        $result[] = ['id' => 1, 'name' => '业务应用'];

        $where = ['status' => 1];
        foreach ($result as $k => $v) {
            if ($v['id'] == 1) {
                $result[$k]['children'] = [];
                $service = Dever::db('service/info')->select($where, ['col' => 'id,name']);
                if ($service) {
                    $i = 0;
                    foreach ($service as $k1 => $v1) {
                        $where = ['service_id' => $v1['id'], 'status' => 1];
                        $data = Dever::db('service/app')->select($where, ['col' => 'id,name']);
                        if ($data) {
                            $v1['children'] = $data;
                        }
                        $result[$k]['children'][$i] = $v1;
                        $i++;
                    }
                }
            }
        }
        return $result;
    }
}