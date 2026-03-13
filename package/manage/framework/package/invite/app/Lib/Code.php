<?php
namespace Invite\Lib;
use Dever;
use Dever\Helper\Str;
class Code 
{
    # 获取邀请码
    public function get($uid)
    {   
        $info = Dever::db('invite/code')->find(['uid' => $uid]);
        if ($info) {
            return $info['value'];
        } else {
            //$code = Dever::uid($uid);
            $code = Str::rand(5, 0);
            return $this->createCode($uid, $code);
        }
    }

    private function createCode($uid, $code)
    {
        $info = Dever::db('invite/code')->find(['value' => $code]);
        if ($info) {
            $code = Str::rand(5, 0);
            return $this->createCode($uid, $code);
        } else {
            Dever::db('invite/code')->insert(['value' => $code, 'uid' => $uid]);
            return $code;
        }
    }

    # 根据邀请码获取邀请人uid
    public function getUid($code)
    {   
        $info = Dever::db('invite/code')->find(['value' => $code]);
        if ($info) {
            return $info['uid'];
        }
        return false;
    }
}