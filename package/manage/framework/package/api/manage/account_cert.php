<?php
return [
    'list' => [
        'where' => ['account_id'],
        'field'      => [
            'id',
            'platform_cert_id' => [
                'show' => 'Dever::call("Api/Lib/App.getCertName", "{platform_cert_id}")',
            ],
            'number',
            'cdate',
        ],
        'data_button' => [
            '编辑' => ['fastedit'],
        ],
        'button' => [
            '新增' => ['fastadd'],
        ],
        'search' => [
            'account_id' => 'hidden',
            'number',
        ],
    ],
    'update' => [
        'desc' => '有的证书会自动同步，无需手动添加，如微信支付的平台证书',
        'field'    => [
            'account_id' => 'hidden',
            'platform_cert_id' => [
                'rules' => true,
                'type' => 'select',
                //'option'     => 'Dever::call("Api/Lib/App.getCert", '.$account['platform_id'].')',

                'option'     => 'Dever::call("Api/Lib/App.getCert", "{account_id}")',
                //'remote' => 'api/manage.getCertName',
                //'remote_default' => false,
            ],
            'number' => [
                'rules' => true,
            ],
            'public' => [
                //'rules' => true,
                'type' => 'textarea',
            ],
            'private' => [
                //'rules' => true,
                'type' => 'textarea',
            ],
        ],
    ],
];