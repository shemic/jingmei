# 邀请组件

使用方法：

```
# 1、获取uid等于1的邀请码
$uid = 1;
$code = Dever::load('code', 'invite')->get($uid);

# 2、根据邀请码获取邀请人uid
$code = '1234';
$uid = Dever::load('code', 'invite')->getUid($code);

# 3、设置邀请关系 uid为1的用户，下级用户uid为3
$child = 3;
$uid = Dever::load('relation', 'invite')->set($uid, $child);

# 4、获取某个用户的下级列表
level为关系级数，默认为6级
$data = Dever::load('relation', 'invite')->getChild($uid, $level = 6);

# 5、获取某个用户的上级
level为关系级数，默认为1级
$uid = Dever::load('relation', 'invite')->getParent($uid, $level = 1);

# 6、获取某个用户的所有上级
$data = Dever::load('relation', 'invite')->getParentAll($uid);

# 7、获取来源用户uid(根据参数source_signature)
uid 当前用户uid
error为错误提示类型，默认为true,直接报错退出，false为return错误信息
$uid = Dever::load('relation', 'invite')->getSourceUid($uid, $error);


```