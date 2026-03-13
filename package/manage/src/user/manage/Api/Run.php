<?php namespace User\Manage\Api;
use Dever;
class Run
{
    # 获取运行节点里的
    public function getType($project_code, $value)
    {
        if ($project_code && $value) {
            $result = [];
            $result['type_code']['value'] = '';
            $project = Dever::db('user/project')->find(['code' => $project_code]);
            $where['service_id'] = $project['service_id'];
            $result['type_code']['option'] = Dever::db('work/' . $value)->select($where);
            return $result;
        }
    }
}