<?php
$info = Dever::project('area');
$path = $info['setup'];
return [
    'list' => [
        'desc' => '导入数据请在服务器执行：php '.$path.'index.php \'{"l":"import.json"}\';
        数据来源：' . Dever::load(Area\Lib\Import\Json::class)->getUrl(),
        'field'      => [
            'id',
            'name',
            'pinyin',
            'pinyin_first',
            'sort' => 'input',
            'status' => [
                'type' => 'switch',
                'show'  => '{status}',
                'active_value' => 1,
                'inactive_value' => 2,
            ],
            'cdate',
        ],
        'button' => [
            '新增' => 'fastadd',
        ],
        'data_button' => [
            '编辑' => 'fastedit',
        ],
        'search'    => [
            'name',
            'pinyin',
            'pinyin_first',
            'status',
        ],
    ],
    'update' => [
        'field'    => [
            'name' => [
                'rules' => true,
            ],
            //'pinyin',
            //'pinyin_first',
            //'sort',
        ],
        'start' => 'Area/Lib/Manage.up',
    ],
];