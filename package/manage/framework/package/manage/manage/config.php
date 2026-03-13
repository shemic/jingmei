<?php
return [
    'list' => [
        'field'      => [
            'name',
        ],
        'data_button' => [
            '编辑' => 'fastedit',
        ],
    ],
    'update' => [
        # 展示左侧分栏
        'column' => [
            'load' => 'manage/config',
            'add' => '新增',
            'key' => 'id',
            'data' => 'Config/Lib/Config.getTree',
            'active' => 1,
            'where' => 'id',
        ],
        'field'    => [
            'name',
        ],
    ],
];