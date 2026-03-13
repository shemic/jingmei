<?php
$manage = Dever::project('manage');
$path = $manage['setup'];
return [
    'source' => 'manage/cron',
    'list' => [
        'desc' => '请将 * * * * * root php '.$path.'index.php \'{"l":"cron.run"}\'放到cron中[建议每分钟执行一次]',
        'field'      => [
            'name',
            'project',
            'interface',
            'ldate' => [
                'show' => 'date("Y-m-d H:i:s", {ldate})',
            ],
            'time',
            'state' => [
                'type' => 'switch',
                'show'  => '{state}',
                'active_value' => 1,
                'inactive_value' => 2,
            ],
        ],

        'button' => [
            //'新增' => ['fastadd'],
        ],
    ],

    'update' => [
        'field'      => [
            'name',
            'project',
            'interface',
            'ldate' => [
                'type' => 'date',
                'date_type' => 'datetime',
                //'value_format' => 'YYYY-MM-DD HH:mm:ss',
                'handle' => 'Manage/Lib/Util.crateDate',
                'rules' => true,
            ],
            'time' => [
                'desc' => '输入秒数',
            ],
            /*
            'state' => [
                'type' => 'radio',
            ],*/
        ],
    ],
];