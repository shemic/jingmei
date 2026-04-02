<?php namespace Work\Manage\Lib;
use Dever;
class Tool
{
    public function getList()
    {
       return Dever::load(Common::class)->getList('cate', ['type' => 3], 'tool');
    }

    public function getInputOption()
    {
        $id = Dever::input('id');
        $data = Dever::db('work/workflow_input_option')->select(['workflow_input_id' => $id, 'status' => 1], ['col' => 'id,name']);
        return $data;
    }
}