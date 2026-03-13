<?php namespace Api\Api;
use Dever;
use Dever\Helper\Secure;
class Oauth
{
    public function __construct()
    {
        $this->t = Dever::input('t', 'is_string', 't');
        $this->account = Dever::input('account', 'is_string', '通信账户');
    }

    # 获取code
    public function code()
    {
        $refer = Dever::input('refer');
        $scope = Dever::input('scope', 'is_string', 'scope', 'snsapi_base');
        $param['scope'] = $scope;
        $param['redirect_uri'] = urlencode(Dever::url('api/oauth.token', array
            (
                't' => $this->t,
                'account' => $this->account,
                'refer' => Secure::encode($refer),
            )
        ));
        Dever::load(\Api\Lib\Account::class)->run($this->account, 'oauth_code', $param, 1, 'jump');
    }

    # 获取token
    public function token()
    {
        $param['code'] = Dever::input('code', 'is_string', 'code');
        $data = Dever::load(\Api\Lib\Account::class)->run($this->account, 'oauth_token', $param);
        if ($data && isset($data['openid'])) {
            if ($t = Secure::checkLogin($this->t)) {
                if ($t['uid'] && $t['uid'] > 0) {
                    $update['uid'] = $t['uid'];
                    $update['account_id'] = $data['account_id'];
                    $update['env'] = 3;
                    $info = Dever::db('api/openid')->find($update);
                    if (!$info) {
                        $update['openid'] = $data['openid'];
                        Dever::db('api/openid')->insert($update);
                    }
                }
            }
            if (isset($data['scope']) && $data['scope'] == 'snsapi_userinfo') {
                $user = $this->user($data);
            }
        }
        $refer = Secure::decode(Dever::input('refer'));
        if ($refer) {
            header('location:' . $refer);
        }
    }

    # 获取用户信息
    public function user($data)
    {
        $param['access_token'] = $data['access_token'];
        $param['openid'] = $data['openid'];
        $data = Dever::load(\Api\Lib\Account::class)->run($this->account, 'oauth_user', $param);
        if ($data) {
            # 获取到用户了
        }
    }

    # 根据refresh_token获取token
    public function refreshToken($id)
    {

    }
}
