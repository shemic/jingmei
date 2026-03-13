<?php
$config = [
    'source' => 'api/platform',
    'list' => [
        'field'      => [
            'id',
            'name',
            'host',
        ],
        'data_button' => [
            '设置' => 'edit',
            '加密' => ['route', [
                'path' => 'api_manage/platform_ssl',
                'param' => [
                    'set' => ['platform_id' => 'id', 'menu' => 'api_manage/platform_info', 'parent' => 'api_manage/platform_info'],
                ],
            ]],
            '签名' => ['route', [
                'path' => 'api_manage/platform_sign',
                'param' => [
                    'set' => ['platform_id' => 'id', 'menu' => 'api_manage/platform_info', 'parent' => 'api_manage/platform_info'],
                ],
            ]],
            '接口' => ['route', [
                'path' => 'api_manage/api',
                'param' => [
                    'set' => ['platform_id' => 'id', 'menu' => 'api_manage/platform_info', 'parent' => 'api_manage/platform_info'],
                ],
            ]],
        ],
        'button' => [
            '新增' => ['fastadd', 'name,host'],
            '定义格式转换' => ['route', [
                'path' => 'api_manage/format',
                'param' => [
                    'set' => ['menu' => 'api_manage/platform_info', 'parent' => 'api_manage/platform_info'],
                ],
            ]],
        ],
        'search' => [
            'name',
        ],
    ],
    'update' => [
        'desc' => "所有参数定义后均可以直接使用，也支持函数，参数值可以是变量、函数、字符串，如果函数中或者字符串中需要增加变量或者常量，请用{}隔开，默认常量：method请求方式，url请求完整地址，host主机域名，uri请求路径，time秒时间戳，timestamp毫秒时间戳，nonce随机值，notify请求回调地址，order_num请求订单号，sign签名信息，aad签名附加数据，query请求查询参数，query_json请求查询参数(json格式)，body请求体参数，body_json请求体参数(json格式)，header请求头参数，header_json请求头参数(json格式)",
        'tab' => [
            '基本设置' => 'name,host,api/platform_setting,api/platform_cert',
            '标准请求' => 'method,post_method,api/platform_request_header,api/platform_request_body',
            '标准响应' => 'response_type,api/platform_response_header,api/platform_response_body,api/platform_response_code',
        ],
        'field'    => [
            'name',
            'host',
            'method' => [
                'type' => 'radio',
                'control' => true,
            ],
            'post_method' => [
                'type' => 'radio',
            ],

            'api/platform_setting' => [
                'name' => '账户参数',
                'desc' => '设置账户需要的参数',
                'where'  => ['platform_id' => 'id'],
            ],

            'api/platform_cert' => [
                'name' => '账户证书',
                'desc' => '设置账户需要的证书',
                'where'  => ['platform_id' => 'id'],
            ],

            'api/platform_request_body' => [
                'name' => '请求体设置',
                'desc' => '设置平台标准请求体',
                'where'  => ['platform_id' => 'id'],
            ],
            'api/platform_request_header' => [
                'name' => '请求头设置',
                'desc' => '设置平台标准请求头',
                'where'  => ['platform_id' => 'id'],
            ],

            'response_type' => 'radio',

            'api/platform_response_body' => [
                'name' => '标准响应体',
                'desc' => '设置平台标准响应体，填写后，将只保留填写后的响应体，格式：data[].name，不是列表则为data.name，如果填写了“数据字段”，这里仅返回“数据字段”里的数据',
                'where'  => ['platform_id' => 'id'],
            ],
            'api/platform_response_header' => [
                'name' => '标准响应头',
                'desc' => '设置平台标准响应头，不填写不保留响应头，格式：data[].name，不是列表则为data.name',
                'where'  => ['platform_id' => 'id'],
            ],

            'api/platform_response_code' => [
                'name' => '响应状态码',
                'desc' => '设置标准的响应状态码，也可以只设置成功值，其他值均为失败',
                'where'  => ['platform_id' => 'id'],
            ],

            'api/platform_convert' => [
                'name' => '字段转换',
                'where'  => ['platform_id' => 'id'],
                'desc' => '设置之后，所有平台过来的字段，都将按照这个转换规则进行转换',
            ],
        ],

        'control' => [
            'post_method' => [
                'method' => 2,
            ],
        ],
    ],
];
$id = Dever::input('id');
$load = Dever::input('load');
if (($load == '/api_manage/platform_info' || $load == '/api_manage/api') && !$id) {
    $config['update']['desc'] = '';
    unset($config['update']['tab']);
}
return $config;