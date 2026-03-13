<?php
return [
    'name' => 'api日志',
    'partition' => 'Dever::call("Manage/Lib/Util.system")',
    'struct' => [
        'platform_id' => [
            'name'      => '平台id',
            'type'      => 'int(11)',
        ],

        'api_id' => [
            'name'      => '接口id',
            'type'      => 'int(11)',
        ],

        'account_id' => [
            'name'      => '账户id',
            'type'      => 'int(11)',
        ],

        'account_project' => [
            'name'      => '账户所在项目',
            'type'      => 'varchar(100)',
        ],

        'url' => [
            'name'      => '请求url',
            'type'      => 'text',
        ],

        'method' => [
            'name'      => '请求方式',
            'type'      => 'varchar(50)',
        ],

        'body' => [
            'name'      => '请求体',
            'type'      => 'text',
        ],

        'header' => [
            'name'      => '请求头',
            'type'      => 'text',
        ],

        'response_body' => [
            'name'      => '响应体',
            'type'      => 'text',
        ],

        'response_header' => [
            'name'      => '响应头',
            'type'      => 'text',
        ],

        'data' => [
            'name'      => '返回数据',
            'type'      => 'text',
        ],
    ],
];