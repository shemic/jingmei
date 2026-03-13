<?php namespace Dever\Helper;

use Workerman\Protocols\Http\Request;
use Workerman\Connection\TcpConnection;

class Wokerman
{
    protected Request $request;
    protected ?TcpConnection $connection;

    public array $server = [];
    public array $requestData = [];

    public function __construct(Request $request, TcpConnection $connection = null)
    {
        $this->request = $request;
        $this->connection = $connection;
        $this->init();
    }

    protected function init(): void
    {
        $headers = $this->request->header();
        $scheme = (
            (isset($headers['x-forwarded-proto']) && $headers['x-forwarded-proto'] === 'https') ||
            (isset($headers['front-end-https']) && strtolower($headers['front-end-https']) === 'on')
        ) ? 'https' : 'http';

        $host = $headers['host'] ?? 'localhost';
        $path = $this->request->path();
        $entry = defined('DEVER_ENTRY') ? DEVER_ENTRY : 'index.php';
        $basePath = '/';
        if (strpos($path, $entry) !== false) {
            $basePath = substr($path, 0, strpos($path, $entry));
        }
        $this->server['type'] = 'workerman';
        $this->server['host'] = $host;
        $this->server['scheme'] = $scheme;
        $this->server['uri'] = $this->request->uri();
        $this->server['app_host'] = $scheme . '://' . $host . $basePath;

        $get     = $this->request->get() ?? [];
        $post    = $this->request->post() ?? [];
        $files   = $this->request->file() ?? [];
        $raw   = $this->request->rawBody();
        $json  = [];

        if ($raw) {
            $tmp = json_decode($raw, true);
            if (is_array($tmp)) {
                $json = $tmp;
            }
        }
        $this->requestData = array_merge($get, $post, $files, $json);
    }

    public function getData(): array
    {
        $data['server'] = $this->server;
        $data['request'] = $this->requestData;
        return $data;
    }
}
