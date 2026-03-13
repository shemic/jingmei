<?php
return [
    'name' => '平台加解密',
    'order' => 'id asc',
    'struct' => [
        'platform_id' => [
            'name'      => '平台id',
            'type'      => 'int(11)',
        ],

        'name' => [
            'name'      => '名称',
            'type'      => 'varchar(50)',
        ],

        'type' => [
            'name'      => '算法',
            'type'      => 'tinyint(1)',
            'default'   => '1',
            'value'     => [
                1 => '非对称',
                2 => '对称',
                3 => '签名',
            ],
        ],

        'cipher_algo' => [
            'name'      => '密码学方式',
            'type'      => 'varchar(50)',
        ],

        'option' => [
            'name'      => '填充模式',
            'type'      => 'varchar(30)',
            'default'   => 'OPENSSL_NO_PADDING',
        ],

        # 对称加密特有
        'iv' => [
            'name'      => '初始化向量',
            'type'      => 'varchar(50)',
        ],

        'tag' => [
            'name'      => '验证标签',
            'type'      => 'varchar(50)',
        ],

        'tag_len' => [
            'name'      => '标签长度',
            'type'      => 'tinyint(1)',
            'default'   => '16',
        ],

        'aad' => [
            'name'      => '附加验证数据',
            'type'      => 'varchar(50)',
        ],

        'after' => [
            'name'      => '数据处理',
            'type'      => 'tinyint(1)',
            'default'   => '1',
            'value'     => [
                1 => '不处理',
                2 => '转base64',
            ],
        ],

        'encrypt_cert_type' => [
            'name'      => '加密密钥类型',
            'type'      => 'tinyint(1)',
            'default'   => '1',
            'value'     => [
                1 => '公钥',
                2 => '私钥',
                3 => '文本',
            ],
        ],

        'encrypt_cert' => [
            'name'      => '加密密钥文本',
            'type'      => 'varchar(50)',
        ],

        'encrypt_cert_id' => [
            'name'      => '加密证书',
            'type'      => 'int(11)',
        ],

        'decrypt_cert_type' => [
            'name'      => '解密密钥类型',
            'type'      => 'tinyint(1)',
            'default'   => '1',
            'value'     => [
                1 => '公钥',
                2 => '私钥',
                3 => '文本',
            ],
        ],

        'decrypt_cert' => [
            'name'      => '解密密钥文本',
            'type'      => 'varchar(50)',
        ],

        'decrypt_cert_id' => [
            'name'      => '解密证书',
            'type'      => 'int(11)',
        ],
    ],
];