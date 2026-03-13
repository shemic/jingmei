<?php namespace Msg\Lib;
use Dever;
class Task
{
    # 定期清理记录
    public function drop()
    {
        # 删除1个月前的数据
        list($start, $end) = Date::month(1);
        $where['cdate'] = ['<=', $end];
        Dever::db('sms/code')->delete($where);
        Dever::db('smg/notify')->delete($where);
    }
}