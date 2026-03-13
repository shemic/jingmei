<?php namespace Msg\Lib\Type;
use Dever;
use Api\Lib\Account;
class Code
{
    private $config;
    # 发送验证码
    public function send($template, $account = [], $param = [], $project = 'api')
    {
        $data['template_id'] = $template['id'];
        $data['day'] = date('Ymd', DEVER_TIME);
        if (empty($param['code'])) {
            $this->config = Dever::db('msg/template_code')->find(['template_id' => $template['id']]);
            if (!$this->config) {
                Dever::error('验证码未配置');
            }
            $param['code'] = $this->createCode();
        }
        if ($template['content']) {
            $template['content'] = \Dever\Helper\Str::val($template['content'], $param);
        }
        $test = 1;
        foreach ($template['method'] as $k => $v) {
            $config = Dever::db('msg/account')->find(['method' => $v]);
            if ($config) {
                $handle = Dever::load('Msg\\Lib\\Method\\' . $config['method']);
                $data['account'] = $handle->init($template['id'], $config['id'], $account, $project);
                $this->valid($data);
                if ($config['test'] == 2) {
                    $test = 2;
                    $api = Dever::load(Account::class)->get($config['api_account_id'], $project);
                    $data['record'] = $handle->send($api, $template['content'], $param);
                }
                $data['code'] = $param['code'];
                $data['status'] = 1;
                Dever::db('msg/code')->insert($data);
                # 这里以后加入计费机制
            }
        }
        $msg = '验证码发送成功';
        if ($test == 1) {
            $msg .= '::' . $param['code'];
        }
        return $msg;
    }

    # 检测并使用验证码
    public function check($template_id, $account, $code, $update = 1)
    {
        $info = Dever::db('msg/code')->find(['template_id' => $template_id, 'account' => $account], ['order' => 'cdate desc']);
        if ($info && $info['status'] == 1 && $code == $info['code']) {
            if ($update == 1) {
                Dever::db('msg/code')->update($info['id'], ['status' => 2]);
            }
            return true;
        }
        return false;
    }

    private function createCode()
    {
        $len = $this->config['length'] ? $this->config['length'] : 4;
        $type = $this->config['type'] ? $this->config['type'] : 1;
        return \Dever\Helper\Str::rand($len, $type - 1);
    }

    protected function valid($where)
    {
        $info = Dever::db('msg/code')->find($where);
        if ($info) {
            if (DEVER_TIME - $this->config['cdate'] < $this->config['interval']) {
                Dever::error('请不要在'.$this->config['interval'].'秒之内申请多次验证码，请您稍后再试');
            } elseif (Dever::db('msg/code')->count($where) >= $this->config['total']) {
                Dever::error('很抱歉，您已经申请获取验证码超过' . $this->config['total'] . '次，今天您已经无法获取验证码了，请您明天再来');
            }
        }
    }
}