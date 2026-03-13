<?php
return [
    'list' => [
        'where' => ['platform_id'],
        'field'      => [
            'id',
            'name',
            'type',
            'cdate',
        ],
        'data_button' => [
            '编辑' => ['fastedit'],
        ],
        'button' => [
            '新增' => ['fastadd'],
        ],
        'search' => [
            'platform_id' => 'hidden',
            'name',
        ],
    ],
    'update' => [
        'tab' => [
            '基本设置' => 'name,type,cipher_algo,option,iv,tag_len,aad,after',
            '加密设置' => 'encrypt_cert_type,encrypt_cert,encrypt_cert_id',
            '解密设置' => 'decrypt_cert_type,decrypt_cert,decrypt_cert_id',
        ],

        'field'    => [
            'platform_id' => 'hidden',
            'name',
            'type' => 'radio',
            'cipher_algo' => [
                'desc' => '直接填写密码学方式的值，如aes-256-cbc',
            ],
            'option' => [
                'desc' => '直接输入填充模式的常量即可，如：OPENSSL_NO_PADDING'
            ],
            'iv' => [
                'desc' => '可以直接使用平台设置中的参数名',
            ],
            'tag' => [
                'desc' => '可以直接使用平台设置中的参数名',
            ],
            'tag_len',
            'aad' => [
                'desc' => '可以直接使用平台设置中的参数名',
            ],
            'after' => 'radio',

            'encrypt_cert_type' => 'radio',
            'encrypt_cert' => [
                'type' => 'text',
                'desc' => '可以直接使用平台设置中的参数名',
            ],
            'encrypt_cert_id' => [
                'type' => 'select',
                'clearable' => true,
                'option'     => 'Dever::call("Api/Lib/Cert.list", "{platform_id}")',
            ],

            'decrypt_cert_type' => 'radio',
            'decrypt_cert' => [
                'type' => 'text',
                'desc' => '可以直接使用平台设置中的参数名',
            ],
            'decrypt_cert_id' => [
                'type' => 'select',
                'clearable' => true,
                'option'     => 'Dever::call("Api/Lib/Cert.list", "{platform_id}")',
            ],
        ],

        'control' => [
            'cipher_algo' => [
                'type' => [2,3]
            ],

            'option' => [
                'type' => [1,2]
            ],

            'iv' => [
                'type' => 2,
            ],
            
            'tag' => [
                'type' => 2,
            ],
            'aad' => [
                'type' => 2,
            ],
            'tag_len' => [
                'type' => 2,
            ],

            'encrypt_cert' => [
                'encrypt_cert_type' => 3,
            ],
            'encrypt_cert_id' => [
                'encrypt_cert_type' => [1,2],
            ],

            'decrypt_cert' => [
                'decrypt_cert_type' => 3,
            ],
            'decrypt_cert_id' => [
                'decrypt_cert_type' => [1,2],
            ],
        ],
    ],
];