<?php namespace Msg\Lib\Method;
use Dever;
use Api\Lib\Account;
class Wechat_applet
{
    private $config;

    # 初始化
    public function init($template_id, $config_id, $account, $project = 'api')
    {
        if (empty($param['openid'])) {
            Dever::error('openid不存在');
        }
        $msg = 'msg';
        if ($project != 'api') {
            $msg = $project;
        }
        $this->config = Dever::db($msg . '/account_wechat')->find(['account_id' => $config_id, 'template_id' => $template_id]);
        if (!$this->config) {
            Dever::error('小程序配置不存在');
        }
        $this->config['project'] = $project;
        $this->config['account'] = $param['openid'];
        return $this->config['account'];
    }

    # 发送
    public function send($api, $content, $param)
    {
        if (!$this->config) {
            Dever::error('小程序配置不存在');
        }
        $data = [];
        foreach ($param as $k => $v) {
            $data[$k]['value'] = $v;
        }
        $send['touser'] = $this->config['account'];
        $send['template_id'] = $this->config['code'];
        $send['page'] = $param['page'] ?? '';
        $send['data'] = $data;
        $send['log'] = true;
        $result = Dever::load(Account::class)->run($api, 'applet_send', $send, 1, 'run', $this->config['project']);
        return Dever::json_encode($result);
    }
}