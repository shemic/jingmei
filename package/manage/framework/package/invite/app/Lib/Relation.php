<?php
# 关系类
namespace Invite\Lib;
use Dever;

class Relation
{
    private $table = 'invite/relation';
    # 只记录几级关系
    private $total = 10;

    # 通用的邀请方法：
    # uid 当前用户的上级，需要通过code邀请码来得到
    # to_uid 被邀请人，当前登录用户，注册后得到
    public function set($uid, $to_uid)
    {
        $this->setParent($uid, $to_uid);
        $this->add($uid, $to_uid, 1);

        return true;
    }

    public function setParent($uid, $to_uid, $level = 1)
    {
        $parent = $this->getParent($uid);
        if ($parent) {
            $level = $level + 1;
            if ($level > $this->total) {
                return;
            }
            $this->add($parent['uid'], $to_uid, $level);
            $this->setParent($parent['uid'], $to_uid, $level);
        }
    }

    # 更换上级
    public function replaceParent($uid, $old_parent, $new_parent, $call = true)
    {
        $info = Dever::db($this->table)->find(['to_uid' => $uid, 'uid' => $old_parent]);
        if ($info) {
            $state = Dever::db($this->table)->update($info['id'], ['uid' => $new_parent]);
            if ($state && $call) {
                $child = $this->getChild($uid, false, false);
                if ($child) {
                    foreach ($child as $k => $v) {
                        $this->replaceParent($v['to_uid'], $old_parent, $new_parent, false);
                    }
                }
            }
        }
        return true;
    }

    # 重置上级
    public function resetParent($uid, $parent)
    {
        Dever::db($this->table)->delete(['to_uid' => $uid]);
        $this->set($parent, $uid);

        $child = $this->getChild($uid, 1, false);
        if ($child) {
            foreach ($child as $k => $v) {
                $this->resetParent($v['to_uid'], $uid);
            }
        }
        return true;
    }

    # 清理邀请关系
    public function dropParent($uid, $parent)
    {
        return Dever::db($this->table)->delete(['uid' => $parent, 'to_uid' => $uid]);
    }

    # 获取某个用户的上级数据
    public function getParent($uid, $level = 1)
    {
        return Dever::db($this->table)->find(['to_uid' => $uid, 'level' => $level]);
    }

    # 获取某个用户的所有上级数据
    public function getParentAll($uid, $level = false)
    {
        $where['to_uid'] = $uid;
        if ($level) {
            $where['level'] = $level;
        }
        return Dever::db($this->table)->select($where);
    }

    # 获取某个用户的下级数据
    public function getChild($uid, $level = 1, $page = 10)
    {
        $where['uid'] = $uid;
        if ($level) {
            $where['level'] = $level;
        }
        $set = [];
        if ($page) {
            $set['num'] = $page;
        }
        return Dever::db($this->table)->select($where, $set);
    }

    # 获取某个用户在x小时之内的下级数据
    public function getChildNum($uid, $level = 1, $start = false, $end = false, $method = 'count')
    {
        $where['uid'] = $uid;
        if ($level) {
            $where['level'] = $level;
        }
        if ($start) {
            $where['cdate#'] = ['>=', strtotime($start)];
        }
        if ($end) {
            $where['cdate##'] = ['<=', strtotime($end)];
        }
        if ($method == 'count') {
            $method = 'count';
        } else {
            $method = 'select';
        }
        return Dever::db($this->table)->$method($where);
    }

    # 插入数据
    public function add($uid, $to_uid, $level = 1)
    {   
        $data['uid'] = $uid;
        $data['to_uid'] = $to_uid;
        $data['level'] = $level;
        $info = Dever::db($this->table)->find($data);
        if (!$info) {
            return Dever::db($this->table)->insert($data);
        }
        
        return false;
    }
}
