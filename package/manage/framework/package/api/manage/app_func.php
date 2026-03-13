<?php
return [
    'list' => [
        'where' => ['app_id'],
        'field'      => [
            'id',
            'name',
            'key',
            'type',
            'status' => [
                'type' => 'switch',
                'show'  => '{status}',
                'active_value' => 1,
                'inactive_value' => 2,
            ],
            'cdate',
        ],
        'data_button' => [
            '编辑' => ['edit'],
        ],
        'button' => [
            '新增' => ['add'],
        ],
        'search' => [
            'app_id' => 'hidden',
            'name',
            'key',
            'type',
        ],
    ],
    'update' => [
        'end' => 'Api/Lib/App.update',
        'tab' => [
            '基本设置' => 'app_id,name,key,desc,cron_date,cron_time',
            '接口设置' => 'type,api/app_func_work',
            '价格设置' => 'spec_type,api/sku,api/sku#',
            '输入输出' => 'api/app_func_input,api/app_func_output',
        ],
        'field'    => [
            'app_id' => 'hidden',
            'name',
            'type',
            'key' => [
                'tip' => '如果为空，将自动按照名称拼音生成',
            ],
            'desc' => 'textarea',
            'status' => [
                'type' => 'radio',
            ],
            'cron_date' => [
                'type' => 'date',
                'date_type' => 'datetime',
            ],
            'cron_time' => [
                'type' => 'text',
                'desc' => '直接输入秒数，如60，就是间隔60秒执行一次，为0则不定时执行',
            ],
            'api/app_func_work' => [
                'name' => '功能接口',
                'desc' => '设置功能包含的接口',
                'where'  => ['app_func_id' => 'id', 'app_id' => 'app_id'],
            ],

            'spec_type' => [
                'type' => 'radio',
                'control' => true,
            ],
            'api/sku' => [
                'name' => '单规格设置',
                'where'  => ['app_func_id' => 'id', 'key' => '-1'],
                # 默认值，如果有默认值则无法添加和删除
                'default' => [
                    # 默认值
                    [
                        'key' => '-1',
                        'price' => '',
                        'num' => '',
                        'day_num' => '',
                    ],
                ],
            ],

            'api/sku#' => [
                'name' => '多规格设置',
                'where' => ['app_func_id' => 'id', 'key' => ['!=', '-1']],
                'type' => 'sku',
                # 设置规格表名
                'spec' => 'api/spec',
                # 设置规格表关联字段
                'spec_field' => 'app_func_id',
                # 获取规格数据的接口
                'spec_data' => 'Api/Lib/Spec.manage',
            ],

            'api/app_func_input' => [
                'name' => '参数输入',
                'where'  => ['app_func_id' => 'id'],
                'desc' => '[参数名：对应接口中的请求参数名，默认值/可选项：可以多行输入，默认值为第一行，其他行为可选项]',
            ],
            'api/app_func_output' => [
                'name' => '参数输出',
                'where'  => ['app_func_id' => 'id'],
            ],
        ],
        'control' => [
            'api/sku' => [
                'spec_type' => 2,
            ],

            'api/sku#' => [
                'spec_type' => 3,
            ],
        ],
    ],
];