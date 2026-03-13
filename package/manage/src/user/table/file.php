<?php
return [
    'name'  => '上传文件表',
    'order' => 'id desc',
    'struct' => [
        'uid' => [
            'name' => '用户ID',
            'type' => 'bigint',
        ],
        'key' => [
            'name' => '文件Key',
            'type' => 'varchar(200)',
        ],
        'hash' => [
            'name' => '文件Hash',
            'type' => 'varchar(64)',
        ],
        'mime' => [
            'name' => 'MIME类型',
            'type' => 'varchar(128)',
        ],
        'size' => [
            'name' => '文件大小',
            'type' => 'bigint',
        ],
        'bucket' => [
            'name' => '存储桶',
            'type' => 'varchar(64)',
        ],
        'domain' => [
            'name' => '访问域名',
            'type' => 'varchar(200)',
        ],
        'url' => [
            'name' => '访问地址',
            'type' => 'varchar(500)',
        ],
        'type' => [
            'name'    => '文件类型',
            'type'    => 'varchar(32)',
            'default' => 'user_upload',
        ],
        'status' => [
            'name'    => '状态',
            'type'    => 'varchar(32)',
            'default' => 'uploaded',
        ],
        'udate' => [
            'name' => '更新时间',
            'type' => 'bigint',
        ],
    ],
    'index' => [
        // Go: Key unique:"key"
        'key'    => 'key.unique',

        // Go: UID index:"uid,status"
        'uid'    => 'uid,status',

        // Go: Status index:"status"
        'status' => 'status',

        // Go: Type index:"type"
        'type'   => 'type',
    ],
];
