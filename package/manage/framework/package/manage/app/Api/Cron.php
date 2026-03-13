<?php namespace Manage\Api;
use Dever;
use Dever\Helper\Cmd;
class Cron
{
    public function run()
    {
        $time = time();
        # 获取所有的计划任务
        $data = Dever::db('manage/cron')->load(array('ldate' => ['<=', $time]));
        if ($data) {
            foreach ($data as $k => $v) {
                Cmd::run($v['interface'], [], $v['project']);
                $param['ldate'] = $v['ldate'] + $v['time'];
                if ($param['ldate'] < $time) {
                    $param['ldate'] = $time + $v['time'];
                }
                if ($v['time'] <= 0) {
                    $param['state'] = 2;
                }
                Dever::db('manage/cron')->update($v['id'], $param);
            }
        }
    }
}