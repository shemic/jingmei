<?php namespace Area\Lib\Import;
use Dever;
class Core
{
    # 获取唯一id 已废弃
    public function id($id, $len = 6)
    {
        return $id;
        $id = substr($id, 0, $len);
        $id = str_pad($id, $len, '0', STR_PAD_RIGHT);
        return $id;
    }

    # 设置区县的等级
    public function setLevelCounty(&$update)
    {
        $num = substr($update['id'], 4);

        # type = 1城区 2郊区 3县城 4经济技术开发 5县级市
        if ($update['name'] == '门头沟区') {
            $update['type'] = 2;
            $update['level'] = 2;
        } elseif ($num <= 10) {
            $update['type'] = 1;
            $update['level'] = 1;
        } elseif ($num > 10 && $num <= 20) {
            $update['type'] = 2;
            $update['level'] = 2;
        } elseif ($num > 20 && $num <= 70) {
            $update['type'] = 3;
            $update['level'] = 3;
        } elseif ($num > 70 && $num <= 80) {
            $update['type'] = 4;
            $update['level'] = 2;
        } elseif ($num >= 80) {
            $update['type'] = 5;
            $update['level'] = 2;
        }
    }

    # 更新数据
    public function up($table, $id, $data)
    {
        $db = Dever::db('area/' .$table);
        $info = $db->find($id);
        if (!$info) {
            $db->insert($data);
        } else {
            $db->update($info['id'], $data);
        }
        return $id;
    }
}
