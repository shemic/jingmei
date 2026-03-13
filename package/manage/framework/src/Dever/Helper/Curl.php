<?php namespace Dever\Helper;

use Dever;

class Curl
{
    private $handle;
    private $url;
    private $get_info = false;
    private $log = false;

    // 是否采集响应头
    private $result_header = false;
    private $result_header_store = [];

    private $param = [];
    private $header = [];

    // 额外调试信息
    private $last_info = [];
    private $last_error = [];
    private $return_with_headers = false; // 给 resultCookie 用

    public function load($url, $param = false, $type = '', $json = false, $header = false, $agent = false, $proxy = false, $refer = false)
    {
        $this->resetState();

        if ($type == 'get_info') {
            $this->get_info = true;
            $type = 'get';
        }

        $this->init();
        $this->param($param);
        $this->setRequest($type);

        if ($header) {
            $this->setHeader($header);
        }

        if (!$agent) {
            $agent = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36';
        }
        if ($agent) {
            $this->setAgent($agent);
        }
        if ($proxy) {
            $this->setProxy($proxy);
        }
        if ($refer) {
            $this->setRefer($refer);
        }

        // --- body 设置 ---
        if ($json) {
            $this->setJson($param);
        } elseif ($type == 'file') {
            $this->setFormData($param);
        } elseif ($type == 'post' || $type == 'put') {
            $this->setForm($param);
        } elseif ($param) {
            if (strpos($url, '?') !== false) {
                $url .= '&';
            } else {
                $url .= '?';
            }
            $url .= http_build_query($param);
        }

        if (strpos($url, '??') !== false) {
            $url = str_replace('??', '?', $url);
        }

        $this->setUrl($url);

        // 默认 SSL 校验（更安全）。如需关闭可通过 setting 覆盖
        $this->setSslVerify(true);

        return $this;
    }

    private function resetState()
    {
        $this->handle = null;
        $this->url = '';
        $this->get_info = false;
        $this->log = false;

        $this->result_header = false;
        $this->result_header_store = [];

        $this->param = [];
        $this->header = [];

        $this->last_info = [];
        $this->last_error = [];
        $this->return_with_headers = false;
    }

    public function log($log)
    {
        $this->log = $log;
        return $this;
    }

    private function init()
    {
        $this->handle = curl_init();
    }

    private function param(&$param)
    {
        if ($param && is_array($param) && isset($param[0])) {
            $temp = $param;
            $param = [];
            foreach ($temp as $k => $v) {
                if (is_array($v)) {
                    $param = array_merge($param, $v);
                } else {
                    $param[$k] = $v;
                }
            }
        }
    }

    public function setStream($callback)
    {
        curl_setopt($this->handle, CURLOPT_BUFFERSIZE, 16384);
        curl_setopt($this->handle, CURLOPT_TCP_KEEPALIVE, 1);
        curl_setopt($this->handle, CURLOPT_TCP_KEEPIDLE, 10);
        curl_setopt($this->handle, CURLOPT_TCP_KEEPINTVL, 10);

        curl_setopt($this->handle, CURLOPT_WRITEFUNCTION, function ($ch, $data) use ($callback) {
            call_user_func($callback, $data);
            return strlen($data);
        });

        return $this;
    }

    public function setRequest($type)
    {
        if ($type == 'post' || $type == 'file') {
            curl_setopt($this->handle, CURLOPT_POST, true);
        } elseif ($type == 'put' || $type == 'delete') {
            curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, strtoupper($type));
        } else {
            curl_setopt($this->handle, CURLOPT_HTTPGET, true);
        }
    }

    /**
     * 修复：header 采用“同名覆盖”策略，避免重复 Content-Type/Length
     * 允许传：
     *  - ["Authorization"=>"Bearer xxx", "Content-Type"=>"application/json"]
     *  - ["Authorization: Bearer xxx", "X: y"]
     */
    public function setHeader($header)
    {
        $map = $this->headerToMap($this->header);

        if (is_array($header)) {
            foreach ($header as $k => $v) {
                if (is_string($k)) {
                    $name = trim($k);
                    $value = trim((string)$v);
                    $map[strtolower($name)] = $name . ': ' . $value;
                } else {
                    // 形如 "Authorization: Bearer xxx"
                    $line = trim((string)$v);
                    if ($line === '') continue;
                    $parts = explode(':', $line, 2);
                    if (count($parts) == 2) {
                        $name = trim($parts[0]);
                        $value = trim($parts[1]);
                        $map[strtolower($name)] = $name . ': ' . $value;
                    } else {
                        // 不规范行，直接追加
                        $map[] = $line;
                    }
                }
            }
        } else {
            $lines = explode("\n", $header);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') continue;
                $parts = explode(':', $line, 2);
                if (count($parts) == 2) {
                    $name = trim($parts[0]);
                    $value = trim($parts[1]);
                    $map[strtolower($name)] = $name . ': ' . $value;
                } else {
                    $map[] = $line;
                }
            }
        }

        // 还原为 list
        $this->header = array_values($map);
    }

    private function headerToMap($headerList)
    {
        $map = [];
        if (!is_array($headerList)) return $map;

        foreach ($headerList as $line) {
            $line = trim((string)$line);
            if ($line === '') continue;
            $parts = explode(':', $line, 2);
            if (count($parts) == 2) {
                $name = trim($parts[0]);
                $value = trim($parts[1]);
                $map[strtolower($name)] = $name . ': ' . $value;
            } else {
                $map[] = $line;
            }
        }
        return $map;
    }

    public function setAgent($agent)
    {
        curl_setopt($this->handle, CURLOPT_USERAGENT, $agent);
    }

    public function setProxy($proxy)
    {
        curl_setopt($this->handle, CURLOPT_PROXY, $proxy[0]);
        curl_setopt($this->handle, CURLOPT_PROXYPORT, $proxy[1]);
        curl_setopt($this->handle, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
    }

    public function setRefer($refer)
    {
        curl_setopt($this->handle, CURLOPT_REFERER, $refer);
    }

    // 新增：SSL 校验控制
    public function setSslVerify($verify)
    {
        if ($verify) {
            curl_setopt($this->handle, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($this->handle, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($this->handle, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($this->handle, CURLOPT_SSL_VERIFYHOST, 0);
        }
    }

    public function setJson($param)
    {
        if (is_array($param)) {
            $param = $param ? Dever::json_encode($param) : '{}';
        }

        // 注意：Content-Length 不一定要手动给，curl 会处理。
        // 但保留也行，只要避免重复
        $header = [
            'Content-Type' => 'application/json; charset=utf-8'
        ];

        $this->setHeader($header);
        $this->setParam($param);
    }

    public function setFormData($param)
    {
        $header = ['Content-Type' => 'multipart/form-data'];

        if (!is_array($param)) {
            $param = Dever::json_decode($param);
        }

        $this->setHeader($header);
        $this->setParam($param);
    }

    public function setForm($param)
    {
        $header = ['Content-Type' => 'application/x-www-form-urlencoded'];

        if (!is_array($param)) {
            $array = Dever::json_decode($param);
            if (json_last_error() === JSON_ERROR_NONE) {
                $param = $array;
            }
        }
        if (is_array($param)) {
            $param = http_build_query($param);
        }

        $this->setHeader($header);
        $this->setParam($param);
    }

    public function setUrl($url)
    {
        $this->url = $url;
        curl_setopt($this->handle, CURLOPT_URL, $url);
        curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, true);
    }

    /**
     * 关键修复：result($setting) 不再触发“重置 handle”
     */
    public function result($setting = false)
    {
        try {
            if ($setting) {
                $this->setting($setting); // 这里不再 init()
            }

            if ($this->header) {
                curl_setopt($this->handle, CURLOPT_HTTPHEADER, $this->header);
            }

            // 是否要返回 header（给 resultCookie 用）
            curl_setopt($this->handle, CURLOPT_HEADER, $this->return_with_headers ? true : false);

            if ($this->result_header) {
                $this->result_header_store = [];
                curl_setopt($this->handle, CURLOPT_HEADERFUNCTION, [$this, 'headerHandler']);
            }

            if (Dever::shell('debug')) {
                curl_setopt($this->handle, CURLINFO_HEADER_OUT, true);
            }

            curl_setopt($this->handle, CURLOPT_ACCEPT_ENCODING, 'gzip,deflate');

            $result = curl_exec($this->handle);

            $httpCode = curl_getinfo($this->handle, CURLINFO_HTTP_CODE);
            $totalTime = curl_getinfo($this->handle, CURLINFO_TOTAL_TIME);
            $errno = curl_errno($this->handle);
            $error = curl_error($this->handle);

            $debug = [];
            if (Dever::shell('debug')) {
                $debug['request'] = curl_getinfo($this->handle, CURLINFO_HEADER_OUT);
            } elseif ($this->get_info) {
                $result = curl_getinfo($this->handle);
            }

            $this->last_info = [
                'http_code' => $httpCode,
                'total_time' => $totalTime,
                'url' => $this->url,
            ];
            $this->last_error = [
                'errno' => $errno,
                'error' => $error,
            ];

            if ($this->handle) {
                curl_close($this->handle);
            }

            $debug['url'] = $this->url;
            $debug['request_body'] = $this->param;
            $debug['request_header'] = $this->header;

            $debug['http_code'] = $httpCode;
            $debug['total_time'] = $totalTime;
            $debug['errno'] = $errno;
            $debug['error'] = $error;

            $debug['response_body'] = $result;
            $debug['response_header'] = $this->result_header_store;
            //print_r($debug);
            if ($this->log) {
                Dever::log($debug, 'curl');
                Dever::debug($debug, 'curl');
            }

            if (Dever::input('test') == 1) {
                echo Dever::json_encode($debug);
                die;
            }

            if (isset($setting['stream'])) {
                exit;
            }

            return $result;

        } catch (\Exception $e) {
            if ($this->handle) {
                curl_close($this->handle);
            }
            return 'error';
        }
    }

    /**
     * 修复：setting 不再 init 重置 handle
     */
    public function setting($setting = [])
    {
        if ($setting) {
            foreach ($setting as $k => $v) {
                $method = 'set' . ucfirst($k);
                if (method_exists($this, $method)) {
                    $this->$method($v);
                }
            }
        }
    }

    public function setVerbose($verbose)
    {
        curl_setopt($this->handle, CURLOPT_VERBOSE, $verbose);
    }

    public function setUserPWD($userpwd)
    {
        curl_setopt($this->handle, CURLOPT_USERPWD, $userpwd);
    }

    public function setTimeOut($time)
    {
        curl_setopt($this->handle, CURLOPT_TIMEOUT, $time);
    }

    public function setConnectTimeout($time)
    {
        curl_setopt($this->handle, CURLOPT_CONNECTTIMEOUT, $time);
    }

    public function setCookie($cookie)
    {
        curl_setopt($this->handle, CURLOPT_COOKIE, $cookie);
    }

    public function setParam($param)
    {
        $this->param = $param;
        if (is_array($param)) {
            $param = http_build_query($param);
        }
        curl_setopt($this->handle, CURLOPT_POSTFIELDS, $param);
    }

    public function setEncode($encode)
    {
        curl_setopt($this->handle, CURLOPT_ENCODING, $encode);
    }

    public function setIp($ip)
    {
        $config['CLIENT-IP'] = $ip;
        $config['X-FORWARDED-FOR'] = $ip;
        $this->setHeader($config);
    }

    public function setResultHeader($value)
    {
        $this->result_header = $value;
    }

    public function header()
    {
        return $this->result_header_store;
    }

    private function headerHandler($curl, $headerLine)
    {
        $len = strlen($headerLine);
        $split = explode(':', $headerLine, 2);
        if (count($split) > 1) {
            $key = trim($split[0]);
            $value = trim($split[1]);
            $this->result_header_store[$key] = $value;
        }
        return $len;
    }

    /**
     * 修复：要拿 Set-Cookie / header 必须 CURLOPT_HEADER=true
     */
    public function resultCookie()
    {
        $this->return_with_headers = true;

        $result = $this->result();

        if (!is_string($result)) {
            return ['cookie' => '', 'content' => $result];
        }

        $headerSize = 0;
        // curl 返回可能包含 1+ 次 header（重定向），简单按最后一个分割
        $parts = preg_split("/\r\n\r\n/", $result);
        if (!$parts || count($parts) < 2) {
            return ['cookie' => '', 'content' => $result];
        }
        $body = array_pop($parts);
        $header = array_pop($parts);

        preg_match_all("/Set\-Cookie:\s*([^;]*)/i", $header, $matches);
        $cookie = '';
        if (!empty($matches[1][0])) {
            $cookie = trim($matches[1][0]);
        }

        return ['cookie' => $cookie, 'content' => $body];
    }

    // 方便你外部调试读取
    public function lastInfo()
    {
        return $this->last_info;
    }

    public function lastError()
    {
        return $this->last_error;
    }
}
