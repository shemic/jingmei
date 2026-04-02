<?php
return [
    'list' => [
        'field'      => [
            'code',
            'name',
            'type',
            'platform_id',
            'protocol',
            'model',
            'sort',
            'status',
            'cdate',
        ],
        'button' => [
            '新增' => ['fastadd'],
        ],
        'data_button' => [
            '编辑' => ['fastedit'],
            //'删除' => 'delete',
        ],
        'search'    => [
            'type',
            'platform_id',
            'code',
            'name',
            'status',
        ],
    ],
    'update' => [
        'start' => 'Work/Manage/Lib/Common.update',
        'tab' => [
            '基本信息' => 'code,name,type,platform_id,protocol,model',
            '参数设置' => 'work/model_param',
        ],
        'field'    => [
            'code' => [
                'desc' => '唯一标识，不填写将自动生成',
                'type' => Dever::input('id') ? 'hidden' : 'text',
            ],
            'name' => [
                'rules' => true,
            ],
            'type' => [
                'type' => 'radio',
                'rules' => true,
            ],
            'platform_id' => [
                'type' => 'select',
                'rules' => true,
            ],
            'protocol' => [
                'type' => 'radio',
                'rules' => true,
            ],
            'model' => [
                'desc' => '如模型名，或者接入点，或者工作流ID，换行可以输入多个，将按照顺序提交，如第一个失败，会自动重试第二个',
                'type' => 'textarea',
                'autosize' => ['minRows' => 4],
            ],
            'work/model_param' => [
                'name' => '自定义参数',
                'desc' => '如果模型对应的参数和工作输入项不一致，这里要设置一下转换关系，如果是工作流ID，这里要设置一下节点的ID',
                'where'  => ['model_id' => 'id'],
            ],
        ],
    ],
];