<?php
return [
    'name' => '参数转换配置',
    'struct' => [
        'platform_id' => [
            'name'      => '平台id',
            'type'      => 'int(11)',
        ],

        'before' => [
            'name'      => '转换前字段',
            'type'      => 'varchar(150)',
        ],

        'after' => [
            'name'      => '转换后字段',
            'type'      => 'varchar(150)',
        ],
    ],
];