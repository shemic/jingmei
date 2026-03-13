<?php
$platform_id = Dever::input('set')['platform_id'] ?? 0;
if (!$platform_id) {
    $platform_id = Dever::input('field')['platform_id'] ?? 0;
}
$platform = include('platform_info.php');
$config = [
    'list' => [
        'where' => ['platform_id'],
        'field'      => [
            //'id',
            'sort' => 'input',
            'name',
            'uri',
            'env',
            'platform_id',
            'status' => [
                'type' => 'switch',
                'show'  => '{status}',
                'active_value' => 1,
                'inactive_value' => 2,
            ],
        ],
        'data_button' => [
            '设置' => ['edit', ['platform_id' => $platform_id]],
            //'删除' => 'delete',
            '复制' => ['api', 'api/manage.copyApi'],
        ],
        'button' => [
            '新增' => ['fastadd', ['platform_id' => $platform_id, 'field' => 'name,env,platform_id,uri']],
        ],
        'search' => [
            'name',
            'uri',
            'status',
        ],
    ],
    'update' => [
        'end' => 'Api/Lib/App.updateApi',
        'desc' => $platform['update']['desc'],
        'tab' => [
            '基本设置' => 'name,env,api/api_setting',
            '请求地址' => 'uri,method,post_method,api/api_path,api/api_query',
            '请求参数' => 'api/api_request_header,api/api_request_body',
            '响应参数' => 'response_type,api/api_response_header,api/api_response_body',
            
            '存储设置' => 'api/api_save',
            '回调设置' => 'notify,api/api_notify,api/api_notify_body,api/api_notify_code',
            //'价格设置' => 'spec_type,api/sku,api/sku#',
            //'输入输出' => 'api/api_request_input,api/api_request_output',
        ],
        'field'    => [
            'name',
            'env' => 'radio',
            'platform_id' => [
                'desc' => '【提交后不能更改】',
            ],
            'uri',
            'api/api_path' => [
                'name' => '接口路径',
                'where'  => ['api_id' => 'id'],
                'desc' => '填写接口上的路径path参数',
            ],
            'api/api_query' => [
                'name' => '接口查询参数',
                'where'  => ['api_id' => 'id'],
                'desc' => '填写接口上的query参数',
            ],

            'api/api_setting' => [
                'name' => '基础参数',
                'where'  => ['api_id' => 'id'],
                'desc' => '设置仅限该接口使用的参数，用于定义一些特殊的参数',
            ],

            'method' => [
                'type' => 'radio',
                'control' => true,
            ],
            'post_method' => [
                'type' => 'radio',
                'show' => false,
            ],
            'api/api_request_body' => [
                'name' => '请求体',
                'where'  => ['api_id' => 'id'],
            ],
            'api/api_request_header' => [
                'name' => '请求头',
                'where'  => ['api_id' => 'id'],
            ],

            'response_type' => 'radio',

            'api/api_response_body' => [
                'name' => '响应体',
                'desc' => '填写后，平台中的标准响应体将失效，并且只保留填写后的响应体，格式：data[].name，不是列表则为data.name',
                'where'  => ['api_id' => 'id'],
            ],

            'api/api_response_header' => [
                'name' => '响应头',
                'desc' => '填写后，平台中的标准响应头将失效，不填写不保留响应头，格式：data[].name，不是列表则为data.name',
                'where'  => ['api_id' => 'id'],
            ],

            'api/api_save' => [
                'name' => '存储设置',
                'desc' => '用于将响应数据保存到数据表中，该数据表最好有api_id（int）和request_id（varchar）字段，用于区分是哪个接口哪次请求，当然也可以没有',
                'where'  => ['api_id' => 'id'],
            ],

            'notify' => [
                'type' => 'radio',
                'control' => true,
            ],
            /*
            #也可以这样设置
            'api/api_notify#' => [
                'field' => 'sign_arg',
                'name' => '签名参数',
                'where'  => ['api_id' => 'id'],
            ],
            'api/api_notify##' => [
                'field' => 'sign_id',
                'name' => '签名',
                'where'  => ['api_id' => 'id'],
            ],*/
            'api/api_notify' => [
                'name' => '基本设置',
                'where'  => ['api_id' => 'id'],
                'default' => [[]],
                # 默认使用表格形式展示，可以改成每行展示
                #'type' => 'line',
            ],
            'api/api_notify_body' => [
                'name' => '参数设置',
                'where'  => ['api_id' => 'id'],
            ],
            'api/api_notify_code' => [
                'name' => '状态码',
                'where'  => ['api_id' => 'id'],
            ],
        ],

        'control' => [
            'post_method' => [
                'method' => 2,
            ],
            'api/api_notify' => [
                'notify' => 1,
            ],
            'api/api_notify_body' => [
                'notify' => 1,
            ],
            'api/api_notify_code' => [
                'notify' => 1,
            ],
        ],
    ],
];
$id = Dever::input('id');
if (!$id) {
    unset($config['update']['tab']);
}
return $config;