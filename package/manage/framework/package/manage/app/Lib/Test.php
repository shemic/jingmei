<?php namespace Manage\Lib;
use Dever;
use Dever\Helper\Str;
use Dever\Helper\Env;
use Dever\Helper\Secure;
use Dever\Helper\Date;
class Test
{
    # 仅为测试用
    public function out($data)
    {
        $result = [];
        $result['head'] = ['id', '姓名', '时间'];
        $result['body'] = [];
        foreach ($data['body'] as $k => $v) {
            $result['body'][$k] = [$v['id'], $v['name'], $v['cdate']];
        }
        return $result;
    }

    # 仅为测试用，展示表格更多内容，类型参考diy.php
    public function show($data)
    {
        $result['type'] = 'list';
        $result['content'] = [
            ['name'=>'标题', 'content'=>'内容'],
            ['name'=>'标题', 'content'=>'内容'],
            ['name'=>'标题', 'content'=>'内容'],
        ];
        return $result;
    }

    # 仅为测试用，展示表格更多内容
    public function show2($data)
    {
        return ['name' => '查看详情', 'content' => $this->show($data)];
    }

    # 仅为测试用，展示详情，类型参考diy.php
    public function view($page)
    {
        # 这里获取基本信息
        //print_r($page->info);die;
        $info[] = array
        (
            # 类型，info信息 desc描述 table表格，表格有head和body即可
            /*
            'type' => 'info',
            'name' => '资源订单',
            'info' => '订单',
            # 右侧按钮
            'button' => $button,
            # 具体内容
            'content' => [
                ['name' => '订单状态', 'value' => '未完成'],
                ['name' => '实际支付', 'value' => '¥ 15.00'],
                ['name' => '支付方式', 'value' => '余额'],
                ['name' => '支付时间', 'value' => '2025-06-03 06:46:15'],
            ],*/

            'type' => 'desc',
            'name' => '基本信息',
            # 每行展示数量
            'column' => 4,
            # 是否有边框
            'border' => true,
            # 排列方向：horizontal横向 vertical纵向
            'direction' => 'horizontal',
            # 右侧按钮
            'button' => array
            (
                array
                (
                    'name' => '编辑',
                    # fastedit、fastadd、oper、api、link、route，参数与list里的data_button一致，多了一个load，可以单独设置路由
                    'type' => 'fastedit',
                    'path' => 'platform/role',
                    # 增加权限，第三个参数是排序，建议大一些
                    //'func' => $page->getFunc('view_fastedit', '详情页-编辑角色', 1000),
                    # 这里是按钮用到的参数数据
                    'row' => [
                        'id' => 1,
                    ],
                ),
            ),
            # 具体内容
            'content' => array
            (
                [
                    'name' => '标题',
                    # 类型，text普通文本，tag标签，link链接，image图片 progress进度条 stat统计 timeline时间线 table表格
                    'type' => 'text',
                    'content' => '内容',
                    # 样式primary success warning danger info exception
                    'style' => 'primary',
                ],
                [
                    'name' => '标题',
                    'type' => 'tag',
                    'content' => '内容',
                    'style' => 'warning',
                ],
                [
                    'name' => '标题',
                    'type' => 'link',
                    'content' => '内容',
                ],
                [
                    'name' => '图片',
                    'type' => 'image',
                    'content' => 'https://fuss10.elemecdn.com/e/5d/4a731a90594a4af544c0c25941171jpeg.jpeg',
                    # 'fill', 'contain', 'cover', 'none', 'scale-down'
                    'fit' => 'fill',
                ],
                [
                    'name' => '进度条',
                    'type' => 'progress',
                    'content' => '10',
                    'style' => 'exception',
                    'width' => '20',
                    'inside' => true,
                    # line dashboard 仪表盘 circle 圆形
                    'show' => 'line',
                    # 开启条纹
                    'striped' => true,
                    # 开启动画
                    'indeterminate' => true,
                ],
                array
                (
                    'name' => '统计',
                    'type' => 'stat',
                    'content' => array
                    (
                        [
                            # 一共24
                            'span' => '6',
                            'name' => '测试',
                            'value' => '1000',
                        ],
                        [
                            'span' => '6',
                            'name' => '测试1',
                            'value' => '1000',
                        ],
                        [
                            'span' => '6',
                            'name' => '测试2',
                            'value' => '1000',
                        ],
                        [
                            'span' => '6',
                            'name' => '测试2',
                            'value' => '1000',
                        ],
                    ),
                ),

                array
                (
                    'name' => '时间线',
                    'type' => 'timeline',
                    'content' => array
                    (
                        [
                            'time' => '2020-10-11',
                            'name' => '测试',
                            'color' => '#0bbd87',
                            'size' => 'large',
                            'type' => 'primary',
                            'hollow' => true,
                        ],
                        [
                            'time' => '2020-10-11',
                            'name' => '测试',
                        ],
                        [
                            'time' => '2020-10-11',
                            'name' => '测试',
                        ],
                        [
                            'time' => '2020-10-11',
                            'name' => '测试',
                        ],
                    ),
                ),

                array
                (
                    'name' => '表格',
                    'type' => 'table',
                    'border' => true,
                    'height' => '200',
                    'head' => array
                    (
                        [
                            'key' => 'name',
                            'name' => '姓名',
                            'fixed' => 'fixed',
                        ],
                        [
                            'key' => 'desc',
                            'name' => '描述',
                            'fixed' => 'fixed',
                        ],
                    ),
                    'button' => array
                    (
                        [
                            'name' => '编辑',
                            'type' => 'fastedit',
                            'load' => 'platform/role',
                        ],
                    ),
                    'body' => array
                    (
                        [
                            'id' => 1,
                            'name' => 'test',
                            'desc' => 'dfdf',
                        ],
                    ),
                ),
            ),
        );

        $info[] = array
        (
            'type' => 'table',
            'name' => '表格信息',
            'border' => true,
            'height' => '200',
            'head' => array
            (
                [
                    'key' => 'name',
                    'name' => '姓名',
                    'fixed' => 'fixed',
                ],
                [
                    'key' => 'desc',
                    'name' => '描述',
                    'fixed' => 'fixed',
                ],
            ),
            'button' => array
            (
                array
                (
                    'name' => '编辑',
                    'type' => 'fastedit',
                    'load' => 'platform/role',
                    # 增加权限，第三个参数是排序，建议大一些
                    'func' => $page->getFunc('view_fastedit', '详情页-编辑角色', 1000),
                ),
            ),
            'body' => array
            (
                [
                    'id' => 1,
                    'name' => 'test',
                    'desc' => 'dfdf',
                ],
            ),
        );
        $tab = array
        (
            'active' => 'table1',
            'content' => array
            (
                'table1' => [
                    # 这里跟desc一样
                    'name' => '标题',
                    'type' => 'text',
                    'content' => '内容',
                    'style' => 'primary',
                ],

                'tab2' => array
                (
                    'name' => '表格',
                    'type' => 'table',
                    'border' => true,
                    'height' => '200',
                    'head' => array
                    (
                        [
                            'key' => 'name',
                            'name' => '姓名',
                            'fixed' => 'fixed',
                        ],
                        [
                            'key' => 'desc',
                            'name' => '描述',
                            'fixed' => 'fixed',
                        ],
                    ),
                    'button' => array
                    (
                        [
                            'name' => '编辑',
                            'type' => 'fastedit',
                            'load' => 'platform/role',
                        ],
                    ),
                    'body' => array
                    (
                        [
                            'id' => 1,
                            'name' => 'test',
                            'desc' => 'dfdf',
                        ],
                    ),
                ),
            )
        );
        return ['title' => '详情', 'info' => $info, 'tab' => $tab];
    }

    public function stat($where)
    {
        return array
        (
            [
                # 一共24
                'span' => '8',
                'name' => '测试',
                'value' => '1000',
            ],
            [
                'span' => '8',
                'name' => '测试1',
                'value' => '1000',
            ],
            [
                'span' => '8',
                'name' => '测试2',
                'value' => '1000',
            ],
        );
    }

    # 对diy页面进行赋值
    public function getDiy($where, $data)
    {
        $data['name']['body'] = [
            [
                'id' => 1,
                'name' => 'test',
                'desc' => 'dfdf',
            ],
        ];
        return $data;
    }

    # 获取过滤选项
    public function getFilter($where)
    {
        $result = [];
        $result[] = [
            'name' => '全部',
            'where' => [],
        ];
        $where['status'] = 1;
        $count = Dever::db('source', 'place_order')->count($where);
        $result[] = [
            'name' => '待支付('.$count.')',
            'where' => $where,
        ];

        $where['status'] = 2;
        $count = Dever::db('source', 'place_order')->count($where);
        $result[] = [
            'name' => '待发货('.$count.')',
            'where' => $where,
        ];

        return $result;
    }
}