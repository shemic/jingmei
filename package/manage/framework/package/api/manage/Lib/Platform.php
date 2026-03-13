<?php namespace Api\Manage\Lib;
use Dever;
class Platform
{
    public function getId()
    {
        return Dever::load(\Manage\Lib\Util::class)->request('platform_id', 'id');
    }
}