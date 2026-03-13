<?php namespace Manage\Api\Page;
use Dever;
use Manage\Lib\Page;
# 操作
class Oper extends Page
{
    public function __construct()
    {
        parent::__construct('oper');
        $this->id = Dever::input('id');
        if (!$this->id) {
            Dever::error('无效数据');
        }
        $this->checkFunc();
    }

    # 更改某个字段的值
    public function up_commit(){}
    public function up()
    {
        $input = base64_decode(Dever::input('data'));
        $input = Dever::json_decode($input);
        if (!$input) {
            $input = Dever::input();
        }
        $field = Dever::input('field');
        if (is_array($field)) {
            $field = $field['field'];
        }
        $field = explode(',', $field);
        foreach ($field as $k => $v) {
            if (isset($input[$v]) && $value = $input[$v]) {
                if (is_array($value)) {
                    $value = implode(',', $value);
                }
                $data[$v] = $value;
            }
        }
        $param['id'] = $this->id;
        $config = $this->config['update'];
        if (isset($config['up_start']) && $config['up_start']) {
            $param = Dever::call($config['up_start'], [$this->db, $param]);
        }
        $where['id'] = ['in', $this->id];
        $state = $this->db->update($where, $data);
        if ($state) {
            if (isset($config['up_end']) && $config['up_end']) {
                Dever::call($config['up_end'], [$this->db, $param]);
            }
            return ['msg' => '操作成功', 'upAdmin' => false];
        } else {
            Dever::error('操作失败');
        }
    }

    # 删除 删除到回收站
    public function recycle_commit(){}
    public function recycle()
    {
        $where['id'] = ['in', $this->id];
        $data = $this->db->select($where);
        if ($data) {
            foreach ($data as $k => $v) {
                $insert['table'] = $this->db->config['load'];
                $insert['table_id'] = $v['id'];
                $insert['content'] = Dever::json_encode($v);
                $state = Dever::db('manage/recycler')->insert($insert);
                if (!$state) {
                    Dever::error('删除失败，请重试');
                }
                $state = $this->db->delete($v['id']);
                if (!$state) {
                    Dever::error('删除失败，请重试');
                }
            }
        }
        return '操作成功';
    }

    # 从回收站恢复
    public function recover_commit(){}
    public function recover()
    {
        $where['id'] = ['in', $this->id];
        $data = $this->db->select($where);
        if ($data) {
            foreach ($data as $k => $v) {
                $v['content'] = Dever::json_decode($v['content']);
                $state = Dever::db($v['table'])->insert($v['content']);
                if (!$state) {
                    Dever::error('恢复失败，请重试');
                }
                $state = $this->db->delete($v['id']);
                if (!$state) {
                    Dever::error('恢复失败，请重试');
                }
            }
        }
        return '操作成功';
    }

    # 直接删除
    public function delete_commit(){}
    public function delete()
    {
        $where['id'] = ['in', $this->id];
        $state = $this->db->delete($where);
        if (!$state) {
            Dever::error('删除失败，请重试');
        }
        return '操作成功';
    }
}