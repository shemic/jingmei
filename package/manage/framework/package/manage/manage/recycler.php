<?php
return [
    'list' => [
        'selection' => true,
        # 设置数据来源
        'data'      => 'Manage/Lib/Recycler.getData', 
        'search'    => [
            'table' => 'hidden',
        ],
        'button' => [
            '批量恢复' => 'recover',
        ],
        'data_button' => [
            '恢复' => 'recover',
            '彻底删除' => 'delete',
        ],
    ],
];