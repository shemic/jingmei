<?php
return [
    'name' => '平台证书配置',
    'order' => 'id asc',
    'struct' => [
        'platform_id' => [
            'name'      => '平台id',
            'type'      => 'int(11)',
        ],

        'name' => [
            'name'      => '证书名称',
            'type'      => 'varchar(50)',
        ],

        'type' => [
            'name'      => '证书类型',
            'type'      => 'varchar(50)',
        ],
    ],
];