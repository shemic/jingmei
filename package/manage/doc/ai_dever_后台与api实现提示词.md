# Dever PHP 项目 AI 开发提示词（先后台，后 API）

本文档给 AI 编程工具使用。目标是：在一个全新或半成品 Dever PHP 项目里，优先完成后台（`table + manage + 菜单`），再完成对外 API（`interface/app/Api`）。

对齐参考代码：
- 后台：`container/web/shenchuang/src/*/manage`、`container/web/shenchuang/src/*/manage/core.php`、`container/web/shenchuang/src/*/table`
- API：`container/web/yuandaibao/src/place/interface/app/Api`
- API 基类：`container/web/yuandaibao/src/place/interface/app/Lib/Core.php`

注意：你提到的 `interfapce` 在仓库中实际目录名是 `interface`。

---

## 1. 主提示词（可直接复制给 AI 编程工具）

```text
你是资深 Dever 框架 PHP 工程师。请在当前项目中实现“后台管理 + API 接口”，并严格遵循以下约束：

【总体目标】
1) 先完成后台：数据表定义（table）-> 菜单（manage/core.php）-> 管理页（manage/*.php）-> 管理辅助类（manage/Lib、manage/Api，按需）。
2) 再完成 API：基类（interface/app/Lib/Core.php，若已存在则复用）-> 接口类（interface/app/Api/*.php）。
3) 所有实现必须遵循 Dever 现有代码风格，不引入 Laravel/Symfony/TP 等其他框架写法。

【目录约定】
- 模块入口：src/<Module>/index.php
- 建表定义：src/<module>/table/<table>.php
- 后台菜单：src/<module>/manage/core.php
- 后台页面：src/<module>/manage/<page>.php
- 后台辅助：src/<module>/manage/Lib/*.php、src/<module>/manage/Api/*.php
- 对外 API：src/place/interface/app/Api/*.php
- API 基类：src/place/interface/app/Lib/Core.php

【后台实现规则】
1) table 文件返回数组结构：
   - name / order / struct / index
   - struct 字段常见键：name、type、default、value
   - value 可为枚举映射（数组）或关联表（如 "service/info"）
   - index 示例：{"code":"code.unique","search":"uid,cate_id,status,sort"}

2) manage/core.php 只负责菜单定义：
   - return ['menu' => [...]]
   - 顶级菜单常含：name/icon/sort/module
   - 子菜单常含：parent/name/icon/sort/show
   - show=3 常用于隐藏或仅路由页面

3) manage/<page>.php 采用 list + update 两段式：
   - list 常用键：type、where、field、button、data_button、search、export、height
   - update 常用键：tab、start、end、field、control、check
   - 字段支持简写：'xxx' => 'hidden'
   - 关联子表字段写法：'module/child_table' => ['where' => ['child_fk' => 'id']]
   - 动态联动写法：'remote' => 'Module/Manage/Api/Class.method'
   - 选项写法：'option' => 'Dever::call("Module/Manage/Lib/Class.method", [...])'
   - 展示转换写法：'show' => 'Dever::call("Module/Manage/Lib/Class.getName", "{field}")'

4) 需要联动/级联/动态默认值时，补充：
   - src/<module>/manage/Api/*.php（供 remote 调用）
   - src/<module>/manage/Lib/*.php（供 option/show/start/end 调用）

【API 实现规则】
1) API 类命名空间与继承：
   - namespace Pinterface\Api;
   - use Pinterface\Lib\Core;
   - class Xxx extends Core

2) 登录与准入控制：
   - protected $login = true/false;
   - protected $entry = true/false;
   - 需要登录接口默认：login=true, entry=true

3) 参数读取与校验：
   - 使用 Dever::input('field', 'is_numeric|is_string|!empty', '错误提示')
   - 业务校验失败用 Dever::error('提示', 可选错误码)

4) 初始化与服务层：
   - 在 init() 中加载服务：$this->service = Dever::load(Service::class);
   - 依赖用户上下文时用 $this->place->uid

5) 返回风格：
   - 列表返回：['list' => $data]
   - 详情返回：['info' => $data] 或直接返回对象
   - 成功无数据可返回 'ok'

6) 复杂提交可保留 *_commit 空方法（与现有风格保持一致）

【两种执行模式】
A. 全新项目：
1) 先补模块入口 index.php（DEVER_APP_NAME、DEVER_APP_LANG、DEVER_APP_PATH）。
2) 按业务实体先写 table。
3) 再写 manage/core.php 菜单。
4) 再写 manage 页面（先主表 CRUD，再子表路由页）。
5) 再写 manage/Lib 与 manage/Api（只有出现 option/show/remote/start/end 需要时才写）。
6) 最后写 interface/app/Api（按资源拆类）。

B. 半成品项目：
1) 先扫描现有目录，给出“缺失文件清单 + 冲突清单”。
2) 缺什么补什么，优先复用已有函数和命名。
3) 不重命名已有公开类/方法/字段，除非我明确要求。
4) 每次修改后输出：新增文件、变更文件、变更原因、受影响路径。

【编码风格硬性要求】
- 严格使用 Dever 生态：Dever::db / Dever::input / Dever::load / Dever::call。
- 保持数组配置式后台风格，不改成控制器+模板渲染模式。
- 命名贴近已有代码：code/name/sort/status/cdate 等通用字段优先复用。
- 先最小可用，再补增强；不要一次引入大量无关抽象。

【交付格式】
请按以下顺序输出：
1) 现状扫描结论（缺失项/冲突项）
2) 实施计划（后台优先，API 次之）
3) 实际改动（逐文件）
4) 关键代码片段
5) 待我确认项（如有）
```

---

## 2. 增量改造提示词（半成品项目专用）

```text
请接手当前 Dever PHP 半成品项目，目标是补齐后台和 API，且不能破坏已有逻辑。

先做三件事再写代码：
1) 扫描 src/*/table、src/*/manage、src/place/interface/app/Api 的现状；
2) 列出缺失项（按“必须/建议”分组）；
3) 给出最小改动方案（禁止大规模重构）。

编码顺序固定：
table -> manage/core.php -> manage/*.php -> manage/Api|Lib(按需) -> interface/app/Api/*.php

所有新增代码必须遵循 Dever 既有风格：
- 后台文件返回数组配置；
- API 使用 Pinterface\Lib\Core + Dever::input + Dever::error；
- 列表统一返回 ['list' => ...]；
- 涉及登录接口设置 protected $login=true 与 $entry=true。

完成后请输出：
- 改动文件清单
- 每个文件做了什么
- 还缺什么（如果有）
```

---

## 3. 后台与 API 代码骨架（给 AI 生成代码时参考）

### 3.1 `table` 骨架

```php
<?php
return [
    'name' => '示例表',
    'order' => 'sort asc,id asc',
    'struct' => [
        'name' => [
            'name' => '名称',
            'type' => 'varchar(64)',
        ],
        'code' => [
            'name' => '标识',
            'type' => 'varchar(64)',
        ],
        'status' => [
            'name' => '状态',
            'type' => 'tinyint(1)',
            'default' => 1,
            'value' => [
                1 => '正常',
                2 => '禁用',
            ],
        ],
    ],
    'index' => [
        'code' => 'code.unique',
        'search' => 'status',
    ],
];
```

### 3.2 `manage/core.php` 菜单骨架

```php
<?php
return [
    'menu' => [
        'demo' => [
            'name' => '演示',
            'icon' => 'apps-2-line',
            'sort' => '60',
            'module' => 'platform',
        ],
        'demo_manage' => [
            'parent' => 'demo',
            'name' => '演示管理',
            'icon' => 'folder-line',
            'sort' => '1',
        ],
        'info' => [
            'parent' => 'demo_manage',
            'name' => '列表',
            'icon' => 'list-check',
            'sort' => '1',
        ],
    ],
];
```

### 3.3 `manage/info.php` 骨架

```php
<?php
return [
    'list' => [
        'field' => [
            'code',
            'name',
            'status',
            'cdate',
        ],
        'button' => [
            '新增' => 'fastadd',
        ],
        'data_button' => [
            '编辑' => 'fastedit',
        ],
        'search' => [
            'code',
            'name',
            'status',
        ],
    ],
    'update' => [
        'start' => 'Demo/Manage/Lib/Common.update',
        'field' => [
            'code' => [
                'desc' => '唯一标识，不填自动生成',
                'type' => Dever::input('id') ? 'hidden' : 'text',
            ],
            'name' => [
                'rules' => true,
            ],
            'status' => [
                'type' => 'radio',
            ],
        ],
    ],
];
```

### 3.4 `manage/Api` 动态联动骨架

```php
<?php namespace Demo\Manage\Api;
use Dever;

class Main
{
    public function getDynamic($value, $table, $id)
    {
        $result = [];
        $result['target']['value'] = '';
        $result['target']['clearable'] = true;
        $result['target']['option'] = Dever::db('demo/info')->select(['status' => 1], ['col' => 'id,name']);
        return $result;
    }
}
```

### 3.5 `interface/app/Api` 骨架

```php
<?php namespace Pinterface\Api;
use Dever;
use Pinterface\Lib\Core;
use Demo\Lib\Info as Service;

class Demo extends Core
{
    protected $login = true;
    protected $entry = true;
    private $service;

    public function init()
    {
        $this->service = Dever::load(Service::class);
        $this->service->init($this->place->uid);
    }

    public function list()
    {
        return ['list' => $this->service->getList()];
    }

    public function info()
    {
        $id = Dever::input('id', 'is_numeric', 'ID');
        return ['info' => $this->service->getInfo($id, $this->place->uid)];
    }

    public function up_commit(){}
    public function up()
    {
        $id = Dever::input('id');
        $name = Dever::input('name', 'is_string', '名称');
        return $this->service->update($id, $name, $this->place->uid);
    }

    public function delete()
    {
        $id = Dever::input('id', 'is_numeric', 'ID');
        $this->service->delete($id, $this->place->uid);
        return 'ok';
    }
}
```

---

## 4. AI 完成定义（DoD）

把下面清单作为 AI 自检标准：

- 已创建或补齐目标实体对应的 `table/*.php`
- 已在 `manage/core.php` 配置菜单层级，路径可达
- 已完成 `manage/*.php` 的 `list/update`，并可完成增删改查
- 需要动态联动时已补齐 `manage/Api` 或 `manage/Lib`
- 已实现 `interface/app/Api/*.php`，并包含参数校验与权限控制
- 受保护接口已设置 `protected $login = true`（必要时 `entry = true`）
- 返回结构风格统一（列表 `list`，详情 `info`，成功返回 `ok` 或数据）
- 无跨框架写法、无无关重构、无大规模命名变更

---

## 5. 推荐给 AI 的输入模板（你每次提需求可复用）

```text
项目根目录：<填写绝对路径>
目标模块：<例如 user/work/service/place>
目标实体：<例如 coupon/order/address>
当前状态：<全新项目 | 半成品项目>
本次范围：
1) 后台：<菜单 + 页面 + 子页面 + 动态联动>
2) API：<接口列表>
约束：
- 先后台后 API
- 严格对齐 Dever 风格
- 只做最小必要改动
参考文件：
- 后台参考：container/web/shenchuang/src/*/manage + table
- API参考：container/web/yuandaibao/src/place/interface/app/Api
交付要求：
- 输出扫描结果、实施计划、逐文件改动说明、关键代码片段
```

