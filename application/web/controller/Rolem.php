<?php

namespace app\web\controller;

use think\Db;

class Rolem extends Common
{
    public function index()
    {
        $data = Db::table($this->db.'.vip_auth_groupm')->select();
        webApi(0, '', 0, $data);
    }

    public function add()
    {
        $data = [
            'code' => str_replace('.', '', microtime(true)), 'name' => input('name'),
            'status' => 1, 'rules' => trim(input('rules'), ',')
        ];
        $res = Db::table($this->db.'.vip_auth_groupm')->insert($data);
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
            $group = Db::table($this->db.'.vip_auth_groupm')->where('code', input('code'))->find();
            if (!empty($group) && !empty($group['rules'])) {
                $isCheck = explode(',', $group['rules']);
            } else {
                $isCheck = [];
            }
        }
        $rules = Db::table('company.vip_auth_rulem')->field('code as id,pid as parentId,name as title')->where('status', 1)->select();
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
}