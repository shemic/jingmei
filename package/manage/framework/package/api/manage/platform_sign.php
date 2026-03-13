<?php
$platform = include('platform_info.php');
return [
    'list' => [
        'where' => ['platform_id'],
        'field'      => [
            'id',
            'name',
            //'sort' => 'input',
            'cdate',
        ],
        'data_button' => [
            '编辑' => ['edit'],
            '删除' => 'delete',
        ],
        'button' => [
            '新增' => ['add'],
        ],
        'search' => [
            'name',
        ],
    ],
    'update' => [
        'desc' => $platform['update']['desc'],
        'tab' => [
            '基本设置' => 'platform_id,name,arg,encrypt,after',
            '键名键值设置' => 'kv_type,kv_sort,kv_value_empty,kv_key_handle,kv_value_handle,kv_join,kv_join_handle',
        ],
        'field'    => [
            'platform_id' => 'hidden',
            'name' => [
                'desc' => '填写后，以{签名名称}形式调用',
            ],
            'arg' => [
                'type' => 'textarea',
                'autosize' => ['minRows' => 2],
                'desc' => '按顺序做加密，用换行隔开，为空则所有请求体参数参与签名',
            ],
            'encrypt' => [
                'type' => 'radio',
                'option'     => 'Dever::call("Api/Lib/Cert.getEncrypt", "{platform_id}")',
            ],
            'after' => [
                'type' => 'select',
                'clearable' => true
            ],


            'kv_type' => 'radio',
            'kv_sort' => 'radio',
            'kv_value_empty' => [
                'type' => 'radio',
                'desc' => '【如果参数中有空值，是否参与签名】',
            ],
            'kv_key_handle' => [
                'type' => 'select',
                'clearable' => true
            ],
            'kv_value_handle' => [
                'type' => 'select',
                'clearable' => true
            ],
            'kv_join',
            'kv_join_handle' => 'radio',
        ],
    ],
];