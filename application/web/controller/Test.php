<?php

namespace app\web\controller;

use think\Db;

class Test
{
    public function index()
    {
        // $data = Db::table()->field('code as id,pid as parentId,name as title')->select();
        // foreach ($data as $k=>$v) {
        //     $data[$k]['checkArr'] = '0';
        // }
        // $data = getTree($data, 'id', 'parentId');

        $arr = [
            [
                'id' => 'a', 'title' => 'aaa', 'parentId' => '',
                "checkArr" => "0",
                'children' => [
                    ['id' => 'b', 'title' => 'bbb', 'parentId' => 'a',"checkArr" => "0",],
                    ['id' => 'c', 'title' => 'ccc', 'parentId' => 'a',"checkArr" => "0",]
                ]
            ],
            [
                'id' => 'e', 'title' => 'eee', 'parentId' => '', 
                "checkArr" => "0",
                'children' => [
                    ['id' => 'f', 'title' => 'fff', 'parentId' => 'e',"checkArr" => "0"],
                    ['id' => 'g', 'title' => 'ggg', 'parentId' => 'e',"checkArr" => "0"]
                ]
            ],
        ];

        webApi(0, '', 0, $arr);
    }
}