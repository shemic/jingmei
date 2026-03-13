<?php namespace Msg\Lib\Method;
use Dever;
class Email
{
    private $config;

    # 初始化
    public function init($template_id, $config_id, $account, $project = 'api')
    {
        if (!Dever::project('email')) {
            Dever::error('账户不存在');
        }
        if (empty($account['email'])) {
            Dever::error('账户不存在');
        }
        $msg = 'msg';
        if ($project != 'api') {
            $msg = $project;
        }
        $this->config = Dever::db($msg . '/account_email')->find(['account_id' => $config_id]);
        if (!$this->config) {
            Dever::error('邮件配置不存在');
        }
        $this->config['account'] = $account['email'];
        return $this->config['account'];
    }

    # 发送短信
    public function send($api, $content, $param)
    {
        if (!$this->config) {
            Dever::error('短信配置不存在');
        }
        Dever::apply('PHPMailer', 'email', 'src');
        Dever::apply('Exception', 'email', 'src');
        Dever::apply('SMTP', 'email', 'src');
        $mail = new \PHPMailer\PHPMailer\PHPMailer();

        list($host, $port) = explode(':', $this->config['host']);
        $mail->isSMTP();
        //$mail->SMTPDebug = 2;
        $mail->CharSet = 'UTF-8';
        $mail->Host = $host;
        $mail->Port = $port;
        if ($port == 465) {
            $mail->SMTPSecure = 'ssl';
        } else {
            $mail->SMTPSecure = 'tls';
        }
        $mail->SMTPAuth = true;
        $mail->Username = $this->config['user'];
        $mail->Password = $this->config['pwd'];
        $mail->setFrom($this->config['user'], $this->config['username']);
        $mail->addAddress($this->config['account'], $this->config['account']);
        if (empty($param['title'])) {
            $param['title'] = $this->config['title'];
        }
        $mail->Subject = "=?utf-8?B?" . base64_encode($param['title']) . "?=";
        $mail->Body = $content;
        $mail->isHTML(true);
        if (isset($param['file'])) {
            $mail->addAttachment($param['file']);
        }
        if (!$mail->send()) {
            $param['error'] = $mail->ErrorInfo;
            Dever::log($param, 'email');
            Dever::error("Mailer Error: " . $mail->ErrorInfo);
        } else {
            return 'ok';
        }
    }
}