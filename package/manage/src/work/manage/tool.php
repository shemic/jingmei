<?php
Dever::db('work/tool_param');
Dever::db('work/tool_method');
$tool_id = Dever::input('id');
$show = false;
$tool_param = [];
if ($tool_id) {
    $param = Dever::db('work/tool_param')->select(['tool_id' => $tool_id]);
    if ($param) {
        $show = true;
        foreach ($param as $k => $v) {
            $tool_param[] = ['tool_id' => $tool_id, 'name' => $v['name'], 'value' => $v['value']];
        }
    }
}
return [
    'list' => [
        'field'      => [
            'code',
            'name',
            'cate_id',
            'model' => [
                'show' => 'Dever::call("Work/Manage/Lib/Model.getName", "{model}")',
            ],
            'info',
            'sort',
            'status',
            'cdate',
        ],
        'button' => [
            '新增' => 'fastadd',
            '分类' => ['route', [
                'path' => 'work_app_manage/cate',
                'param' => [
                    'set' => ['type' => 3, 'menu' => 'work_app_manage/tool', 'parent' => 'work_app_manage/tool'],
                ],
            ]],
        ],
        'data_button' => [
            '编辑' => 'fastedit',
            /*
            '方法' => ['route', [
                'path' => 'work_app_manage/tool_method',
                'param' => [
                    'set' => ['tool_id' => 'id', 'menu' => 'work_app_manage/tool', 'parent' => 'work_app_manage/tool'],
                ],
            ]],*/
        ],
        'search'    => [
            'cate_id' => [
                'rules' => true,
                'option'    => 'Dever::call("Work/Manage/Lib/Common.getList", ["cate", ["type"=>3]])',
            ],
            'code',
            'name',
            'status',
        ],
    ],
    'update' => [
        'field'    => [
            'code' => [
                'rules' => true,
            ],
            'name' => [
                'rules' => true,
            ],
            'cate_id' => [
                'rules' => true,
                'option'    => 'Dever::call("Work/Manage/Lib/Common.getList", ["cate", ["type"=>3]])',
            ],
            'model' => [
                'rules' => true,
                'type' => 'cascader',
                'option' => 'Dever::call("Work/Manage/Lib/Model.getList")',
            ],
            'info' => [
                'type' => 'textarea',
                'autosize' => ['minRows' => 4],
            ],
            /*
            'work/tool_param' => [
                'name' => '平台参数配置',
                'where'  => ['tool_id' => 'id'],
                'show' => $show,
                'default' => $tool_param,
            ],*/
        ],
    ],
];