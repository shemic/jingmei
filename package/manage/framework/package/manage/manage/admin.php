<?php
return [
    # 数据来源，如果当前菜单不是表名，这里可以定义从哪个表获取数据
    //'source' => 'manage/admin',
    # 列表页配置
    'list' => [
        # 多选功能，配合批量操作
        //'selection' => true,
        # 列表页筛选功能
        //'filter' => 'Manage/Lib/Test.getFilter',
        //'where' => ['group_id' => $group],
        # 列表页类型 table表格、article文章、pic图片、goods商品，默认是表格，除table外，其余类型需要增加layout项，用以控制展示位置，并且批量操作功能将失效，如批量删除、批量操作等，后续增加更多类型
        'type' => 'table',
        # 是否显示序号
        /*
        'index' => [
            'name' => '序号',
            # 固定表头，field中也支持
            'fixed' => true,
        ],
        */
        # 展示的字段
        'field'      => [
            'id',
            'name',
            # 实现多级表头，两个字段合并到一起展示，因vue3问题，仅支持三级表头哦
            /*
            'test2' => [
                'name' => '基本资料',
                'type' => 'mul',
                'child' => [
                    'avatar' => 'image',
                    'mobile' => [
                        'type' => 'show',
                        # 加入排序
                        'sort' => true,
                    ],
                ],
            ],*/

            //'avatar' => 'image',
            'mobile' => [
                'sort' => true,
                # 多余字符按照...提示
                'truncate' => true,
            ],
            'role',
            /*
            # 自定义展示内容
            'role' => [
                //'show' => 'Dever::db("role", "manage")->find("{id}")["name"]',
                # 气泡卡片展示
                'type' => 'popover',
                # 位置：top/top-start/top-end/bottom/bottom-start/bottom-end/left/left-start/left-end/right/right-start/right-end
                'location' => 'right',
                # 是否用标签形式展示
                'tag' => true,
                # 返回一个数组['name' => '', 'content' => []]，这样可以展示多条[['name' => '', 'content' => []]]
                'show' => 'Dever::load("common")->show2("{role}")',
            ],*/
            'status' => [
                'type' => 'switch',
                'show'  => '{status}',
                'active_value' => 1,
                'inactive_value' => 2,
            ],
            'cdate',
        ],
        # 扩展展示内容
        //'expand' => 'Manage/Lib/Util.show',

        /*
        'type' => 'article',
        'layout' => [
            [
                'type' => 'avatar',
                'value' => [
                    'image' => 'avatar',
                ]
            ],
            [
                'type' => 'content',
                'value' => [
                    'title' => 'name',
                    'description' => 'role'
                ]
            ],
            [
                'type' => 'content',
                'value' => [
                    'item' => ['时间', 'cdate'],
                ]
            ],
        ],
        */

        /*
        'type' => 'goods',
        'layout' => [
            'tag' => 'mobile',
            'image' => 'avatar',
            'title' => 'name',
            'description' => 'role',
            //'icon' => 'icon',
        ],*/

        /*
        'type' => 'pic',
        'layout' => [
            'id' => 'id',
            'image' => 'avatar',
        ],*/
        # 列表页描述
        //'desc' => 'test',
        # 列表的高度，auto和100%或者具体数值，默认是auto
        'height' => 'auto',
        
        # 列表页按钮，默认是快速新增和删除
        'button' => [
            '新增' => 'add',
            //'新增' => 'fastadd',
            //'删除' => 'recycle',//删除到回收站 此功能暂时废弃，可以用filter和oper自行实现
            //'彻底删除' => 'delete',
            //'更改角色' => ['oper', 'role'],
            //'链接跳转' => ['link', 'https://www.baidu.com/'],
            //'路由跳转' => ['route', 'manage/menu?test=1'],
        ],
        # 列表页每条数据的按钮，默认是快速编辑和删除
        'data_button' => [
            '编辑' => 'edit',
            //'编辑' => 'fastedit',
            # 只处理某个字段，多个用逗号隔开
            //'编辑' => ['fastedit', 'name'],
            //'编辑' => ['fastedit'],
            # 打开其他表的新增
            //'新增子菜单' => ['fastadd', 'platform/admin'],
            //'新增子菜单' => ['fastadd', ['path' => 'place_user_log/wallet', 'field' => ['uid' => 'id']]],
            //'删除' => 'recycle',//删除到回收站 此功能暂时废弃，可以用filter和oper自行实现
            //'彻底删除' => 'delete',
            //'操作' => ['oper', 'role'],
            //'接口操作' => ['api', 'api'],
            //'详情' => ['view', 'platform/admin?type=view&id=id'],//view可以改成drawer，在本页右侧出现
            //'链接跳转' => ['link', 'https://www.baidu.com/'],
            # 第三个参数可以自定义图标：https://element-plus.org/zh-CN/component/icon.html#icon-collection
            # 第四个参数是判断是否展示该按钮，字段名=字段值& 如name=1&id=2
            //'路由跳转' => ['route', 'manage/menu?test=1', 'ChatLineSquare'],
            //'管理账户列表' => ['route', ['path' => 'set_group/group_user', 'param' => ['set' => ['module_id' => 2, 'relation_id' => 'id']]]],
        ],

        # 按钮组复杂例子
        /*
        'data_button' => [
            '编辑' => 'edit',
            '内容' => ['route', [
                'path' => 'source_manage/content',
                'param' => [
                    'set' => ['info_id' => 'id', 'menu' => 'source_manage/info', 'parent' => 'source_manage/info'],
                ],
            ]],
        ],
        # 更多按钮
        'data_button_list' => [
            '编辑' => 'edit',
            '内容' => ['route', [
                'path' => 'source_manage/content',
                'param' => [
                    'set' => ['info_id' => 'id', 'menu' => 'source_manage/info', 'parent' => 'source_manage/info'],
                ],
            ]],
        ],*/
        # 列表页导入
        'import' => [

        ],
        # 列表页导出
        'export' => [
            'out' => '导出',
            'Manage/Lib/Util.out' => '自定义导出',
        ],
        # 搜索字段 fulltext 模糊查询
        'search'    => [
            'name',
            'mobile',
            'role' => 'group',
        ],
        # 统计
        //'stat' => 'Manage/Lib/Util.stat',
    ],
    
    # 更新页配置
    'update' => [
        # 更新后是否更新后台用户登录信息，用于修改后台常用的配置
        'upAdmin' => true,
        # 更新页描述
        'desc' => '',
        # 自定义标签 支持分栏
        /*
        'tab' => [
            # 不设置分栏
            '基础设置' => 'name,mobile',
            # 设置分栏
            '基础设置' => [
                [
                    'name' => 12,
                    'mobile' => 12,
                ],
            ],
            '普通设置' => 'password',
            '其他设置' => 'role',
        ],
        # 自定义步骤 支持分栏 设置后，tab将失效
        'step' => [
            '第一步' => 'name,mobile', 
            # 设置分栏
            '第一步' => [
                [
                    'name' => 12,
                    'mobile' => 12,
                ],
            ],
            '第二步' => 'password',
            '提交' => 'role',
        ],
        # 自定义布局 24分栏布局，设置后，tab和step里的设置将失效
        'layout' => [
            [
                'name' => 12,
                'mobile' => 12,
            ],
            [
                'password' => 12,
                'role' => 12,
            ],
        ],*/
        # 要更新的字段
        'field'    => [
            'avatar' => [
                'type' => 'upload',
                # 这里传入上传规则
                'upload' => '1',
                # 是否支持多选
                'multiple' => false,
                # 提示
                'truncate' => '2222',
                # 展示类型 默认为list 列表 input 可输入地址 pic图片模式
                'style' => 'pic',
                # 裁剪时的默认宽度高度
                'wh' => '500*500',
                # 上传后影响的字段 这个目前仅有name字段
                //'upload_name' => 'name',
            ],
            'name' => [
                'type' => 'text',
                # 定义editor的工具栏，一般无需配置，参考wangEditor https://www.wangeditor.com/v5/toolbar-config.html#toolbarkeys，这里直接配置toolbarConfig的值
                /*
                'editorToolBar' => [
                    'toolbarKeys' => [
                        'headerSelect',
                        '|',
                        'bold', 'italic',
                    ],
                    'insertKeys' => [],
                    'excludeKeys' => [],
                ],*/
                # 定义editor的菜单配置，一般只需要配置上传规则即可，其余也可以配置，参考wangEditor https://www.wangeditor.com/v5/menu-config.html，这里直接配置editorConfig.MENU_CONF的值
                'editorMenu' => [
                    # 定义上传规则，与uploadVideo一样，无需定义server
                    'uploadImage' => [
                        # 这里传入上传规则id
                        'upload' => 1,
                        # 不传则默认为file
                        'fieldName' => 'file',
                    ],
                    # 也可以直接传入上传规则，其余配置默认
                    'uploadVideo' => 3,
                ],
                'maxlength' => 30,
                # 描述
                'desc' => '',
            ],
            /* type的值
            text:单行文本
            set:
            maxlength:最大输入长度
            minlength:最小输入长度
            size: 大小，'large' | 'default' | 'small'

            password:文本密码框
            textarea:多行文本
            autosize:true 高度是否自适应，也可以传入数组，['minRows' => 2, 'maxRows' => 6]
            rows:多行文本的行数，默认为2
            editor:编辑器

            number:数字计数器
            set:
            step:计数器步数，默认为1
            min:最小值
            max:最大值
            precision:精度，小数点几位
            position:按钮位置:left、right，不填为两端

            switch:开关
            set:
            open_color:开启颜色
            close_color:关闭颜色
            open_text:开启文字
            close_text:关闭文字

            slider:滑块
            set:
            max:最大值
            step:步数
            stops:是否显示间断点
            input:是否显示输入框，true/false
            range:是否范围选择，true/false
            format:格式化展示数字，这里是一个计算公式，如{a}/100,{a}为当前值变量名

            radio:单选
            radio_button:单选按钮样式
            set:
            border:单选边框样式

            checkbox:多选
            checkbox_button:多选按钮样式
            set:
            border:多选边框样式
            min:多选限制选择数目，最小选择数目
            max:多选限制选择数目，最大选择数目

            tree:树形选择器
            tree2:大量数据下的树形选择器，暂时不支持
            cascader:级联选择器，可以做地区等
            select:选择器
            select_tree:选择器，多选的树形模式
            select_text:选择器+文本
            set:
            clearable:是否可清空选择，true/false
            multiple:开启多选，true/false
            url:开启远程搜索，这里定义远程搜索的url
            remote:开启远程控制，这里定义远程控制的url

            rate:评分
            set:
            score:是否显示分数
            text:是否显示文字

            date:日期，不带具体时间
            date_type:
            dates:多个日期
            year:只显示年
            month:只显示月
            week:只显示周
            datetime:带有时间的日期
            datetimerange:带有时间的日期范围
            daterange:日期范围
            monthrange:月份范围
            set:
            disable_func:禁止选择方法，需要根据不同的date_type实现不同的方法，默认为不能选择今天之后的日期：return time.getTime() > Date.now()
            format:格式化展示,YYYY-MM-DD，参考：https://day.js.org/docs/en/display/format#list-of-all-available-formats
            shortcuts:默认展示的日期，如果没有填写则会吐出默认值，值如:
            [
                [
                    'text' => '今天',
                    'func' => 'return new Date()',//这里是js代码，怎么弄找ai要就行
                ],
            ],
            start_placeholder:开始日期文字描述
            end_placeholder:结束日期文字描述
            range_separator:日期范围文字描述
            step:步数
            start:开始日期
            end:结束日期
            default:默认日期

            time:仅选择时间
            step:步数
            start:开始时间
            end:结束时间

            */
            'mobile' => [
                # 仅限编辑，值为add/edit，不填则所有有效
                //'only'      => 'edit',
                'name'      => '手机号',
                'type'      => 'text',
                'disable'   => false,//是否禁用
                'placeholder' => '',//提示语
                # 校验规则，如rules => true，是必填, 无rules或者rules=false，就是选填
                # 参考：https://github.com/yiminghe/async-validator
                'rules'     => [
                    # 规则1
                    [
                        # 必填
                        'required' => true,
                        # 输入后触发
                        'trigger' => 'blur',
                        # 提示信息
                        'message' => '请输入手机号',
                    ],
                    # 规则2
                    [
                        # 最小字符
                        //'min' => 3,
                        # 最大字符
                        //'max' => 5,
                        # 长度
                        'len' => 11,
                        # 正则
                        'pattern' => Dever::rule('mobile', ''),
                        'trigger' => 'blur',
                        # 提示信息
                        'message' => '手机号错误',
                        # 验证类型 date,array,number,boolean,integer,float,url,email,enum,string
                        'type' => 'string',
                    ],
                ],
            ],
            'password' => [
                'type' => 'password',
                # 更新时的值，始终是空的，有值才更新
                'update' => '',
                # 对更新的值进行处理
                'handle' => 'manage/util.createPwd',
                # 空值不允许入库
                'empty'  => false,
                'rules'     => [
                    [
                        # 仅限新增时必填，值为add/edit，不填则所有有效
                        'only' => 'add',
                        'required' => true,
                        'trigger' => 'blur',
                        'message' => '请输入密码',
                    ],
                    [
                        'min' => 6,
                        'max' => 18,
                        'trigger' => 'blur',
                        'message' => '密码长度不能超过18或者少于6个字符',
                    ],
                ],
            ],
            'role' => [
                'type' => 'checkbox',
                # 是否开启控制功能，需要配置control
                //'control' => true,
                # 开启远程获取数据功能，这里设置接口即可，访问到api下
                'remote' => 'Manage/Api/Admin.getModuleData',
            ],
            'module_data' => [
                'type' => 'tree',
                'desc' => '',
            ],
            # 调用另外一个manage下update字段定义，如api/api_notify，就是调用api/manage/api_notify.php里的update
            /*
            #也可以这样设置
            'api/api_notify#' => [
                'field' => 'sign_arg',
                'name' => '签名参数',
                'where'  => ['api_id' => 'id'],
                'type' => 'text',
            ],
            'api/api_notify##' => [
                'field' => 'sign_id',
                'name' => '签名',
                'where'  => ['api_id' => 'id'],
            ],
            'api/api_notify' => [
                'name' => '基本设置',
                'where'  => ['api_id' => 'id'],
                'default' => [['type' => 2]],
                # 默认使用表格形式展示，可以改成每行展示
                'type' => 'line',
            ],
            'api/api_notify_body' => [
                'name' => '参数设置',
                'where'  => ['api_id' => 'id'],
            ],
            */
        ],

        # 是否开启控制功能
        /*
        'control' => [
            'avatar' => [
                'role' => 1,
            ],
        ],
        */

        # update提交之前的操作，需要验证哪些字段唯一，多个用逗号隔开
        'check' => 'mobile',
        # update提交之前的操作
        'start' => '',
        # update提交之后的操作
        'end' => '',
    ],

    # 详情页配置
    'view' => 'Manage/Lib/Util.view',

    # 自定义页配置
    //'diy1' => 'Manage/Lib/Test.diy',
];