<?php namespace Api\Lib\Platform;
use Dever;
class Ssl
{
    private $field;
    public function init($field)
    {
        $this->field = $field;
        return $this;
    }

    public function encrypt($id, $value)
    {
        $config = $this->config($id, 1, $value);
        if ($config) {
            if ($config['type'] == 1) {
                # 非对称
                openssl_public_encrypt($config['value'], $value, $config['cert'], $config['option']);
            } elseif ($config['type'] == 2 && $config['cipher_algo']) {
                # 对称
                $value = openssl_encrypt($config['value'], $config['cipher_algo'], $config['cert'], $config['option'], $config['iv'], $config['tag'], $config['aad'], $config['tag_len']);
            } elseif ($config['type'] == 3 && $config['cipher_algo']) {
                # 签名
                openssl_sign($config['value'], $value, $config['cert'], $config['cipher_algo']);
            }

            if ($config['after'] == 2) {
                $value = base64_encode($value);
            }
        }
        
        return $value;
    }

    public function decrypt($id, $value, $data = '')
    {
        if (is_array($value)) {
            $value = Dever::json_encode($value);
        }
        $config = $this->config($id, 2, $value);
        if ($config) {
            if ($config['type'] == 1) {
                # 非对称
                openssl_public_decrypt($config['value'], $value, $config['cert'], $config['option']);
            } elseif ($config['type'] == 2 && $config['cipher_algo']) {
                # 对称
                $value = openssl_decrypt($config['value'], $config['cipher_algo'], $config['cert'], $config['option'], $config['iv'], $config['tag'], $config['aad']);
            } elseif ($config['type'] == 3 && $config['cipher_algo']) {
                # 签名验证
                $value = openssl_verify($data, $config['value'], $config['cert'], $config['cipher_algo']);
            }
        }
        
        return $value;
    }

    protected function config($id, $type, $value)
    {
        $config = Dever::db('api/platform_ssl')->find($id);
        if (!$config) {
            return false;
        }
        $config['value'] = $value;
        $key = $type == 1 ? 'encrypt' : 'decrypt';
        $this->cert($config, $key);
        if (!$config['cert']) {
            return false;
        }
        if ($type == 2 && $config['after'] == 2) {
            $config['value'] = base64_decode($config['value']);
        }
        # 对称加密需要特殊处理一下
        if ($config['type'] == 2) {
            if (!$config['option']) {
                $config['option'] = 'OPENSSL_NO_PADDING';
            }
            $config['option'] = constant($config['option']);
            if ($config['option'] === null) {
                $config['option'] = OPENSSL_NO_PADDING;
            }

            $config['iv'] = $this->field->{$config['iv']} ?? $config['iv'];
            $config['aad'] = $this->field->{$config['aad']} ?? $config['aad'];
            if ($config['tag_len']) {
                $config['tag'] = substr($config['value'], -$config['tag_len']);
                $config['value'] = substr($config['value'], 0, -$config['tag_len']);
            }
            if (!$config['tag']) {
                $config['tag'] = null;
            }
        }
        return $config;
    }

    protected function cert(&$config, $key)
    {
        $config['cert_type'] = $config[$key . '_cert_type'];
        $config['cert'] = $config[$key . '_cert'];
        $config['cert_id'] = $config[$key . '_cert_id'];
        
        if ($config['cert_type'] == 3) {
            # 公钥
            $config['cert'] = $this->field->{$config['cert']} ?? $config['cert'];
        } else {
            $cert = false;
            #$set = Dever::db('platform_cert', 'api')->find($config['cert_id']);
            # 获取账户里的cert
            $project = $this->field->account_project;
            $account_id = $this->field->account_id;
            if ($project && $account_id) {
                $cert = Dever::db($project . '/account_cert')->find(['account_id' => $account_id, 'platform_cert_id' => $config['cert_id']], ['order' => 'edate desc']);
            }
            if (!$cert) {
                $config['cert'] = false;
                return $config;
            }
            $this->field->setNumber($cert['number']);
            if ($config['cert_type'] == 2) {
                $key = 'private';
                $method = 'openssl_get_privatekey';
            } else {
                $key = 'public';
                $method = 'openssl_x509_read';
            }
            if ($cert[$key]) {
                $config['cert'] = $cert[$key];
                $config['cert'] = $method($config['cert']);
            }
        }
    }
}