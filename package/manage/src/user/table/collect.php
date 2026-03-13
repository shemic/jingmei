<?php
return [
    'name' => '收藏表',
    'order' => 'id desc',
    'struct' => [
        'uid' => [
            'name' => '用户ID',
            'type' => 'bigint',
        ],
        'content_id' => [
            'name'      => '内容ID',
            'type'      => 'bigint',
            'value'     => 'user/content',
        ],
    ],
    'index' => [
        'uid' => 'uid',
    ],
];