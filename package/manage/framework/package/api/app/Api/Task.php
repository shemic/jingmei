<?php namespace Api\Api;
use Dever;
class Task
{
    # 定时更新功能
    public function func()
    {
        $where['status'] = 1;
        $where['cron_time'] = ['>', '0'];
        $data = Dever::db('api/app_func')->select($where);
        if ($data) {
            foreach ($data as $k => $v) {
                $account = Dever::db('api/account')->find(['app_id' => $v['app_id']]);
                if ($account) {
                    Dever::load(\Api\Lib\Account::class)->run($account, $v);
                }
            }
        }
    }

    # 单独执行某个接口
    # 仅命令行执行
    public function api_cmd(){}
    public function api()
    {
        $param = Dever::input();
        return Dever::load(\Api\Lib\Api::class)->run($param['api_id'], $param);
    }
}
