<?php
$nodes = Dever::db('user/run_nodes')->find(1);
return [
    'list' => [
        'where' => ['project_code'],
        'field'      => [
            'code',
            'input',
            //'result',
            'current_node',
            'status' => '',
            'cdate',
        ],
        'button' => [
            '新增' => ['fastadd'],
        ],
        'data_button' => [
            '节点记录' => ['route', [
                'path' => 'user_manage/run_nodes',
                'param' => [
                    'set' => ['project_code' => 'project_code', 'run_code' => 'code', 'menu' => 'user_manage/project', 'parent' => 'user_manage/run'],
                ],
            ]],
        ],
        'search'    => [
            'status',
        ],
    ],

    'update' => [
        'start' => 'User/Manage/Lib/Run.handle',
        'control' => [
            'input_text' => 'input_type=text',
            'input_file' => 'input_type=file',
        ],
        'field'    => [
            'project_code' => 'hidden',
            'type' => [
                'type' => 'radio',
                'remote' => 'User/Manage/Api/Run.getType?project_code=' . Dever::load(\Manage\Lib\Util::class)->request('project_code'),
            ],
            'type_code' => [
                'rules' => true,
                'type' => 'select',
                'option'    => [],
            ],
            'input_type' => [
                'type' => 'radio',
            ],
            'input_text' => [
                'name' => '文本内容',
                'type' => 'textarea',
                'autosize' => ['minRows' => 4],
                'desc' => '输入文本内容即可',
                //'field' => 'input',
                //'default' => '{input}',
            ],
            'input_file' => [
                'tips' => '支持视频、音频、图片、office、文本、pdf等格式',
                'name' => '文件',
                'type' => 'upload',
                'upload' => '8',
                'multiple' => false,
                'style' => 'list',
                'upload_name' => 'name',
            ],
        ],
    ]
];