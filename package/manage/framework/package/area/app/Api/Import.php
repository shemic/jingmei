<?php namespace Area\Api;
use Dever;

class Import
{
    public function web()
    {
        return Dever::load(\Area\Lib\Import\Web::class)->get();
    }

    public function json()
    {
        return Dever::load(\Area\Lib\Import\Json::class)->get();
    }
}
