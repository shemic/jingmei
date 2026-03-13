<?php namespace Msg\Lib\Type;
use Dever;
use Api\Lib\Account;
class Notify
{
    # 发送通知
    public function send($template, $account = [], $param = [], $project = 'api')
    {
        if ($template['content']) {
            $template['content'] = \Dever\Helper\Str::val($template['content'], $param);
        }
        foreach ($template['method'] as $k => $v) {
            $config = Dever::db('msg/account')->find(['method' => $v]);
            if ($config) {
                $handle = Dever::load('Msg\\Lib\\Method\\' . $config['method']);
                $data['account'] = $handle->init($template['id'], $config['id'], $account, $project);
                if ($config['test'] == 2) {
                    $api = Dever::load(Account::class)->get($config['api_account_id'], $project);
                    $data['record'] = $handle->send($api, $template['content'], $param);
                }
                $data['content'] = $template['content'];
                Dever::db('msg/notify')->insert($data);
            }
        }
        return '通知发送成功';
    }
}