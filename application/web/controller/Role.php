<?php

namespace app\web\controller;

use think\Db;

class Role extends Common
{
    public function index()
    {
        $data = Db::table($this->db.'.vip_auth_group')->select();
        webApi(0, '', 0, $data);
    }

    public function add()
    {
        $data = [
            'code' => str_replace('.', '', microtime(true)), 'name' => input('name'),
            'status' => 1, 'rules' => trim(input('rules'), ',')
        ];
        $res = Db::table($this->db.'.vip_auth_group')->insert($data);
        if ($res) {
            webApi(0, 'yes', 0, '添加成功');
        } else {
            webApi(0, 'no', 0, '添加失败');
        }
    }

    public function getRules()
    {
        // 之后补上  只能选择自己已经拥有的权限
        if (!empty(input('code'))) {
            $group = Db::table($this->db.'.vip_auth_group')->where('code', input('code'))->find();
            if (!empty($group) && !empty($group['rules'])) {
                $isCheck = explode(',', $group['rules']);
            } else {
                $isCheck = [];
            }
        }
        $rules = Db::table('company.vip_auth_rule')->field('code as id,pid as parentId,name as title')->where('status', 1)->select();
        if ($rules) {
            foreach ($rules as $k=>$v) {
                $rules[$k]['checkArr'] = '0';
                if (isset($isCheck) && in_array($v['id'], $isCheck)) {
                    $rules[$k]['checkArr'] = ['type' => 0, 'isChecked' => 1];
                }
            }
            $data = getTree($rules, 'id', 'parentId');
        } else {
            $data = [];
        }
        webApi(0, '', 0, $data);
    }

    // /**
    //  * 侧边栏组装代码
    //  * @param array|string $array 该角色拥有的规则ID [一维数组或者字符串]
    //  * @return string 组装好的侧边栏json字符串
    //  */
    // protected function jsonZ($array)
    // {
    //     $all = [
    //         ['title' => 'HOME  首页', 'icon' => 'layui-icon-home', 'jump' => '/']
    //     ];
    //     // 查询 & 递归重组 & 拼接数组 & 转json字符串
    //     $rules = Db::table('company.vip_auth_rule')->field('code,name as title,icon,file name,pid,path')->where('code', 'in', $array)->where('file', '<>', '')->order('sort')->select();
    //     $other = getTree($rules, 'code', 'pid', 'list');
    //     $all = array_merge($all, $other);
    //     $menu = json_encode($all, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    //     return $menu;
    // }
}