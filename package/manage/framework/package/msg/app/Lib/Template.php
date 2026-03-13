<?php namespace Msg\Lib;
use Dever;
use Msg\Lib\Type\Notify;
use Msg\Lib\Type\Code;
class Template
{
    # 获取通知方式
    public function method()
    {
        $value = [
            'Letter' => '站内信',
            'Sms' => '短信',
            'WechatService' => '微信公众号模板消息',
            'WechatApplet' => '微信小程序订阅消息',
            //'App' => 'APP推送',
            //'Email' => '邮箱',
        ];
        return $value;
    } 

    # 发送消息
    public function send($template, $account = [], $param = [], $project = 'api')
    {
        $template = Dever::db('msg/template')->find(['key' => $template, 'status' => 1]);
        if (!$template) {
            Dever::error('消息模板不存在');
        }
        if ($template['type'] == 1) {
            $method = Notify::class;
        } elseif ($template['type'] == 2) {
            $method = Code::class;
        }
        $template['method'] = explode(',', $template['method']);
        return Dever::load($method)->send($template, $account, $param, $project);
    }

    # 验证码检查
    public function check($template, $account, $code, $update = 1)
    {
        $template = Dever::db('msg/template')->find(['key' => $template, 'status' => 1]);
        if (!$template) {
            Dever::error('消息模板不存在');
        }
        if ($template['type'] == 2) {
            $state = Dever::load(Code::class)->check($template['id'], $account, $code, $update);
            if (!$state) {
                Dever::error('验证码错误');
            }
        }
    }
}