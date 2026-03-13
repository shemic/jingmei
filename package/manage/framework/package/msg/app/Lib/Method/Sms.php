<?php namespace Msg\Lib\Method;
use Dever;
use Api\Lib\Account;
class Sms
{
    private $config;

    # 初始化
    public function init($template_id, $config_id, $account, $project = 'api')
    {
        if (empty($account['mobile'])) {
            Dever::error('手机号不存在');
        }
        $msg = 'msg';
        if ($project != 'api') {
            $msg = $project;
        }
        $this->config = Dever::db($msg . '/account_sms')->find(['account_id' => $config_id, 'template_id' => $template_id]);
        if (!$this->config) {
            Dever::error('短信配置不存在');
        }
        $this->config['project'] = $project;
        $this->config['account'] = $account['mobile'];
        return $this->config['account'];
    }

    # 发送短信
    public function send($api, $content, $param)
    {
        if (!$this->config) {
            Dever::error('短信配置不存在');
        }
        $send['mobile'] = $this->config['account'];
        $send['TemplateCode'] = $this->config['code'];
        $send['TemplateParam'] = $param;
        $send['log'] = true;
        $result = Dever::load(Account::class)->run($api, 'send_sms', $send, 1, 'run', $this->config['project']);
        return Dever::json_encode($result);
    }
}