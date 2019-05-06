<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
use app\web\controller\Common;
use app\web\service\ErpWhere as EW;

/**
 * Author lhp
 * Date 2018/12/28
 * Description 会员级别
 */
class Vipright extends Common
{
    /**
     * 会员级别权益 --- 会员级别
     */
    public function level()
    {
         // 获取分页数据
        [$page, $limit, $lookup] = [input('page'), input('limit'), input('lookup')];
        
         //统计数量
        $count = Db::table($this->db.'.vip_viplevel')->count();

         //模糊查询
        if ($lookup) {
            $where[] = ['username', 'like', '%' . $lookup . '%'];
        } else {
            $where = true;
        }
        unset($lookup);
        //查询Table表信息
        $data = Db::table($this->db.'.vip_viplevel')
            ->order('uid')
            ->field('id, uid, code, username, money, referee ,function, rights, remarks, discount, org_code')
            ->where($where)
            ->order('uid')
            ->page($page, $limit)
            ->select();
            unset($where, $page, $limit);
        //修改格式
        foreach ($data as $k => $v) {
            $data[$k]['function'] == 1 ? $data[$k]['function'] = '开启' : $data[$k]['function'] = '关闭';
            $data[$k]['uid'] = 'V'.$v['uid'];
        }
        webApi(0, '', $count, $data);
    }

    /**
     * 会员级别 添加功能
     */
    public function leveladd()
    {
        $data = [
            'uid' => input('uid'),
            'username' => input('username'),
            'money' => input('money'),
            'discount' => input('discount'),
            // 'validity' => input('validity'),
            'referee' => input('referee'),
            'function' => input('function') !== null ? 1 : 0,
            'rights' => input('rights'),
            'remarks' => input('remarks'),
            'code' => 'HYJB'.str_replace('.' , '', microtime(1))
        ];

        $orgs = explode(',', input('splb_dtrr_id'));
        $insertData = [];
        for ($i = 0; $i < count($orgs); $i++) {
            $insertData[$i] = $data;
            $insertData[$i]['org_code'] = $orgs[$i];
        }
        
        
        //验证序号是否存在
        $userrepeat = Db::table($this->db.'.vip_viplevel')->where('uid', $data['uid'])->find();
        if ($userrepeat) {
            webApi(0, 'error', 0, '序号已存在!');
        }

        //验证场景
        // $result = $this->validate($data, 'app\web\validate\v1\ViprightsValidate.addrights');
        // if (true !== $result) {
        //     webApi(0, 'error', 0, $result);
        // }

        //添加数据库信息
        $res = Db::table($this->db.'.vip_viplevel')->insertAll($insertData);
        unset($data);
        //提示信息
        if ($res) {
            webApi(0, 'yes', 0, '添加成功!');
        } else {
            webApi(0, 'no', 0, '添加失败!');
        }
    }

    /**
     * 点击编辑会员信息
     */
    public function leveledit()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }
        // $this->actionLock(input('access_token'), request()->controller().'_'.request()->action(), $id);
        $data = [
            'id' => $id,
            'username' => input('username'),//级别名称
            'discount' => input('discount'),//级别折扣
            'money' => input('money'), //售卡金额
            'referee' => input('referee'),//推荐人积分
            'function' => input('function') !== null ? 1 : 0,//开启/关闭
            'rights' => input('rights'),//会员权益
            'remarks' => input('remarks'),//备注
            'org_code' => input('org_code') //组织机构
        ];
        //验证场景
        // $result = $this->validate($data, 'app\web\validate\v1\ViprightsValidate.addrights');
        // if (true !== $result) {
        //     $this->actionUnlock(input('access_token'), request()->controller().'_'.request()->action(), $id);
        //     webApi(0, 'error', 0, $result);
        // }

        //修改数据库信息
        $res = Db::table($this->db.'.vip_viplevel')->update($data);
        unset($data);
        // $this->actionUnlock(input('access_token'), request()->controller().'_'.request()->action(), $id);
        //提示信息
        if ($res) {
            webApi(0, 'yes', 0, '修改信息成功!');
        } else {
            webApi(0, 'no', 0, '修改信息失败!');
        }
    }

    /**
     * 会员级别中工具条删除功能
     */
    public function leveldel()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }

        //删除数据库信息
        $res = Db::table($this->db.'.vip_viplevel')->delete($id);

        //提示信息
        if ($res) {
            webApi(0, 'yes', 0, '删除成功!');
        } else {
            webApi(0, 'no', 0, '删除失败!');
        }
    }

    /**
     * 批量删除
     */
    public function delMany()
    {
        if (input('ids') == '') {
            webApi(1, '参数错误');
        }
        
        $ids = trim(input('ids'), ',');
        $res = Db::table($this->db.'.vip_viplevel')->where('id', 'in', $ids)->delete();
        unset($ids);
        if ($res) {
            webApi(0, 'yes', 0, '删除成功');
        } else {
            webApi(0, 'no', 0, '删除失败');
        }
    }

    /**
     * 会员级别序号下拉框
     */
    public function levelNumber()
    {
        $data = array(
            ['value' => '0', 'name' => 'V0'],
            ['value' => '1', 'name' => 'V1'],
            ['value' => '2', 'name' => 'V2'],
            ['value' => '3', 'name' => 'V3'],
            ['value' => '4', 'name' => 'V4'],
            ['value' => '5', 'name' => 'V5'],
            ['value' => '6', 'name' => 'V6'],
            ['value' => '7', 'name' => 'V7'],
            ['value' => '8', 'name' => 'V8'],
            ['value' => '9', 'name' => 'V9'],
            ['value' => '10', 'name' => 'V10'],
            ['value' => '11', 'name' => 'V11'],
            ['value' => '12', 'name' => 'V12']
            // ['value' => '13', 'name' => 'V13'],
            // ['value' => '14', 'name' => 'V14'],
            // ['value' => '15', 'name' => 'V15'],
            // ['value' => '16', 'name' => 'V16'],
            // ['value' => '17', 'name' => 'V17'],
            // ['value' => '18', 'name' => 'V18'],
            // ['value' => '19', 'name' => 'V19'],
            // ['value' => '20', 'name' => 'V20']
        );

        foreach ($data as $k=>$v) {
            if (!empty(Db::table($this->db.'.vip_viplevel')->where('uid', $v['value'])->find())) {
                unset($data[$k]);
            }
        }
        sort($data);
        webApi(0, '', 0, $data);
    }

    /**
     * 组织机构
     */
    public function viprightdTree()
    {
        $org = EW::orgChild();
        if ($org == '') {
            $data = [];
        } else {
            $data = Db::table($this->db.'.vip_org')->field('code as id,pid as parentId,name as title')->where('code', 'in', $org)->select();
            if (!empty(input('tree'))) {
                $tree = explode(',', input('tree'));
            } else {
                $tree = false;
            }
            foreach ($data as $k=>$v) {
                $data[$k]['checkArr'] = '0';
                if (!empty($tree) && in_array($v['id'], $tree)) {
                    $data[$k]['checkArr'] = ['type' => 0, 'isChecked' => 1];
                } 
            }
            $data = getTree($data, 'id', 'parentId');
        }
        

        webApi(0, '', 0, $data);
    }

    /**
     * 编辑时组织机构赋值
     */
    public function splbvalue() 
    {
        $data = Db::table($this->db. '.vip_org')->where('code', 'in', input('code'))->select();
        if ($data) {
            $data = implode(',', array_column($data, 'name'));
        } else {
            $data = '';
        }
        webApi(0, '', 0, $data);
    }

}