<?php namespace User\Manage\Lib;
use Dever;
class Data
{
    public function getServiceData()
    {
        $id = Dever::input('id');
        if ($id) {
            $info = Dever::db('user/data')->find($id);
            $project_id = $info['project_id'];
        } else {
            $project_id = Dever::load(\Manage\Lib\Util::class)->request('project_id');
        }
        $project = Dever::db('user/project')->find($project_id);
        $app = Dever::db('work/app')->find($project['app_id']);

        return Dever::db('work/service_data')->select(['service_id' => $app['service_id']]);
    }
}