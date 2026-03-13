<?php namespace Api\Lib;
use Dever;
class Cert
{
    # 获取证书列表
    public function list($platform_id)
    {
        return Dever::db("api/platform_cert")->select(["platform_id" => $platform_id]);
    }

    # 获取加密列表
    public function getEncrypt($platform_id)
    {
        $encrypt = [
            -1 => '无需加密',
            -2 => 'md5',
            -3 => 'sha256',
            -4 => 'sha1',
        ];
        if ($platform_id) {
            $info = Dever::db('api/platform_ssl')->select(['platform_id' => $platform_id]);
            if ($info) {
                foreach ($info as $k => $v) {
                    $encrypt[$v['id']] = $v['name'];
                }
            }
        }
        return $encrypt;
    }
}