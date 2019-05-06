<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
// use app\web\controller\Common;
use app\web\service\ErpWhere as EW;
/**
 * Author lxy
 * Date 2018/12/28
 * Description 员工管理
 */
class Sfpersonnel extends Common
{
    /**
     * 员工管理查询
     */
    public function personnelSel()
    {
        //获取分页数据
        [$page,$limit] = [input('page'),input('limit')];
        
        // 搜索条件
        if(input('search') != ''){
            $ws[] =['vgname|code|name|phone|vpname', 'like', '%'.input('search').'%'];
        } else {
            $ws = true;
        }

        //当前登入人
        $operate = Db::table($this->db.'.vip_staff')
                ->where('code', session('info.staff'))
                ->find();

        //门店限制
        if ($operate['id'] == 1) {
            $where = true;
        } else if ($operate['store_code'] == "") {
            $where = true;
        } else {
            $where[] = ['store_code', '=', $operate['store_code']];
        }
        //统计数量
        $count = Db::table($this->db.'.view_vip_staff')
                ->where($where)
                ->where($ws)
                ->count();
        //查询
        $data = Db::table($this->db.'.view_vip_staff')
                ->where($where)
                ->where($ws)
                ->order('id','desc') 
                ->page($page,$limit)
                ->select();

        //统计数量
        // $count = Db::table($this->db. '.vip_staff')
        //         ->alias('v')
        //         ->leftJoin($this->db. '.vip_org vg', 'vg.code = v.org_code')
        //         ->leftJoin($this->db. '.vip_store vs', 'vs.code = v.store_code')
        //         ->leftJoin($this->db. '.vip_position vp', 'vp.code = v.position_code')
        //         ->leftJoin($this->db. '.vip_auth_group vq', 'vq.code = v.pc_auth_code')
        //         ->leftJoin($this->db. '.vip_auth_groupm vm', 'vm.code = v.m_auth_code')
        //         ->field('v.*, vg.name vgname, vs.name  vsname, vp.name vpname, vq.name vqname, vm.name vmname')
        //         ->where($ws)
        //         ->where($where)
        //         ->count();

        // //查询的数据
        // $data = Db::table($this->db. '.vip_staff')
        //         ->alias('v')
        //         ->leftJoin($this->db. '.vip_org vg', 'vg.code = v.org_code')
        //         ->leftJoin($this->db. '.vip_store vs', 'vs.code = v.store_code')
        //         ->leftJoin($this->db. '.vip_position vp', 'vp.code = v.position_code')
        //         ->leftJoin($this->db. '.vip_auth_group vq', 'vq.code = v.pc_auth_code')
        //         ->leftJoin($this->db. '.vip_auth_groupm vm', 'vm.code = v.m_auth_code')
        //         ->field('v.*, vg.name vgname, vs.name  vsname, vp.name vpname, vq.name vqname, vm.name vmname')
        //         ->where($ws)
        //         ->where($where)
        //         ->order('v.id','desc') //按照登记时间降序排列
        //         ->page($page,$limit)
        //         ->select();

        //更改格式
        // foreach ($data as $k => $v){
        //     //角色 0 管理 1 员工
        //     $data[$k]['role'] !== 1 ? $data[$k]['role_g'] = '管理' : $data[$k]['role_g'] = '员工';
        //     //到期时间
        //     $data[$k]['time_g'] = date('Y-m-d H:i:s', $v['exp_time']);
        // }

        //清除变量
        unset($page,$limit,$operate,$where,$ws);

        //返回数据
        webApi(0, '', $count, $data);
    }

    /**
     * 查询员工下拉框 
     */
    public function staffSel()
    {
        //查询当前登入人
        $operate = Db::table($this->db.'.vip_staff')
                ->where('code', session('info.staff'))
                ->find();
        //门店限制
        if ($operate['id'] == 1) {
            $where = true;
        } else if ($operate['store_code'] == "") {
            $where = true;
        } else {
            $where[] = ['store_code', '=', $operate['store_code']];
        }
        //查询
        $data = Db::table($this->db.'.vip_staff')
                ->where($where)
                ->order('id','desc') //按照登记时间降序排列
                ->select();
        unset($operate,$where);
        webApi(0, '', 0, $data);
    }

    /**
     * 查询门店下拉框
     */
    public function storeSel()
    {
        //查询当前登入人
        $operate = Db::table($this->db.'.vip_staff')->where('code', session('info.staff'))->find();
        //门店限制
        if ($operate['id'] == 1) {
            $where = true;
        } else if ($operate['store_code'] == "") {
            $where = true;
        } else {
            $where[] = ['code', '=', $operate['store_code']];
        }

        $data = Db::table($this->db.'.vip_store')
                ->where($where)
                ->order('create_time','desc') //按照登记时间降序排列
                ->select();

        unset($operate, $where);
        webApi(0, '', 0, $data);
    }

    /**
     * 查询组织机构
     */
    public function staffTree()
    {
        $org = EW::orgChild(input('org_s'));
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
     * 查询PC端权限
     */
    public function pcAuthority()
    {
        $data = Db::table($this->db.'.vip_auth_group')->select();

        webApi(0, '', 0, $data);
    }

    /**
     * 查询手机端端权限
     */
    public function mAuthority()
    {
        $data = Db::table($this->db.'.vip_auth_groupm')->select();

        webApi(0, '', 0, $data);
    }

    /**
     * 添加员工
     */
    public function addPersonnel()
    {
        $data = [
            'code' => input('code'),
            'name' => input('name'),
            'password' => input('password'),
            'passwords' => input('passwords'),
            'phone' => input('phone'),
            'pc_auth_code' => input('pc_auth_code'),
            'm_auth_code' => input('m_auth_code'),
            'position_code' => input('position_code'),
            'org_code' => input('splb'),
            'store_code' => input('store_code'),
            'exp_time' => 0,
            'role' => 1 ,
            'pc_status' => input('pc_status') !== null ? 1 : 0,
            'm_status' => input('m_status') !== null ? 1 : 0
        ];

        if ($data['pc_status'] == 1) {
            $data['exp_time'] = time() + 31536000;
        }

        //组织机构限制
        $z = Db::table($this->db.'.vip_org')->where('pid',$data['org_code'])->find();

        if ($z) {
            webApi(0, 'error', 0, '组织机构必须选最底层！');
        }

        //工号验证
        $staffExists = Db::table($this->db.'.vip_staff')->where('code', $data['code'])->find();
        if ($staffExists) {
            webApi(0, 'error', 0, '工号已存在');
        }

        //密码验证
        if ($data['password'] !== $data['passwords']) {
            webApi(0, 'error', 0, '两次密码不符,请重新输入!');
        }

        // if ($data['status'] == 1) {
        //     $url = $this->ssl.config('api.inside_url').'index.php/v1/insideStoreDeductions/?bus_account='.$this->db;
        //     $deductions = doHttpPost($url, []);
        //     $deductions = json_decode($deductions, true);
        //     if ($deductions['code'] != 200) {
        //         webApi(0, 'error', 0, $deductions['msg']);
        //     } else {
        //         $data['exp_time'] = time() + $deductions['time'];
        //     }
        // }

        $data['password'] = md5($data['password']);
        unset($data['passwords']);
        // 启动事务
        Db::startTrans();
        try {
            // $id = Db::table($this->db.'.vip_staff')->insertGetId($data);
            // Db::table($this->db.'.user_auth_group_access')->insert(['uid' => $id, 'group_id' => input('authority')]);
            Db::table($this->db.'.vip_staff')->insert($data);
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $res = false;
        }
        unset($data, $staffExists, $url, $deductions, $result);
        if ($res) {
            webApi(0, 'yes', 0, '添加成功!');
        } else {
            webApi(0, 'no', 0, '添加失败!');
        }
    }

    /**
     * 添加管理
     */
    public function addManage()
    {
        $data = [
            'code' => input('code'),
            'name' => input('name'),
            'password' => input('password'),
            'passwords' => input('passwords'),
            'phone' => input('phone'),
            'pc_auth_code' => input('pc_auth_code'),
            'm_auth_code' => input('m_auth_code'),
            'position_code' => input('position_code'),
            'org_code' => input('splb'),
            'admin_org_code' => input('m_splb'),
            'exp_time' => 0,
            'role' => 0,
            'pc_status' => 1,
            'm_status' => 1,
            'exp_time' => time() + 31536000 //有效期
        ];
        
        $staffExists = Db::table($this->db.'.vip_staff')->where('code', $data['code'])->find();
        if ($staffExists) {
            webApi(0, 'error', 0, '工号已存在');
        }

        //密码验证
        if ($data['password'] !== $data['passwords']) {
            webApi(0, 'error', 0, '两次密码不符,请重新输入!');
        }

        // if ($data['status'] == 1) {
        //     $url = $this->ssl.config('api.inside_url').'index.php/v1/insideStoreDeductions/?bus_account='.$this->db;
        //     $deductions = doHttpPost($url, []);
        //     $deductions = json_decode($deductions, true);
        //     if ($deductions['code'] != 200) {
        //         webApi(0, 'error', 0, $deductions['msg']);
        //     } else {
        //         $data['exp_time'] = time() + $deductions['time'];
        //     }
        // }

        $data['password'] = md5($data['password']);
        unset($data['passwords']);
        // 启动事务
        Db::startTrans();
        try {
            // $id = Db::table($this->db.'.vip_staff')->insertGetId($data);
            // Db::table($this->db.'.user_auth_group_access')->insert(['uid' => $id, 'group_id' => input('authority')]);
            Db::table($this->db.'.vip_staff')->insert($data);
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $res = false;
        }

        unset($data, $staffExists, $url, $deductions, $result);

        if ($res) {
            webApi(0, 'yes', 0, '添加成功!');
        } else {
            webApi(0, 'no', 0, '添加失败!');
        }
    }
    
    /**
     * 删除员工
     */
    public function delPersonnel()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }
        $noDel = [1,2,3,4,5,6];
        if (in_array($id, $noDel)) {
            webApi(0, 'error', 0, '不能删除默认员工!');
        }

        if (input('exp_time') > time()) {
            webApi(0, 'error', 0, '该员工未到期,不能删除!');
        }
        // 执行删除
        $res = Db::table($this->db.'.vip_staff')->delete($id);

        unset($id, $noDel);

        if ($res) {
            webApi(0, 'yes', 0, '删除成功!');
        } else {
            webApi(0, 'no', 0, '删除失败!');
        }
    }

    /**
     * 批量删除
     */
    public function delPersonnelAll()
    {
        if (input('ids') == '') {
            webApi(1, '参数错误');
        }
        
        $ids = trim(input('ids'), ',');

        $originalid  = [1,2,3,4,5,6];
        if (in_array($ids, $originalid)) {
             webApi(0, 'error', 0, '默认员工不能删除!');
        }

        $exptimes = explode(',', trim(input('exptimes'), ','));
        for ($i = 0; $i < count($exptimes); $i++) {
            if (intval($exptimes[$i]) > time()) {
                webApi(0, 'error', 0, '该员工未到期,不能删除!');
            }
        }
        
        $res = Db::table($this->db.'.vip_staff')->where('id', 'in', $ids)->delete();

        unset($ids, $originalid);

        if ($res) {
            webApi(0, 'yes', 0, '删除成功!');
        } else {
            webApi(0, 'no', 0, '删除失败!');
        }
    }

    /**
     * 修改员工与管理
     */
    public function editPersonnel()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }
        //判断管理
        if (intval(input('role')) !== 1){
            $data = [
                'id' => $id,
                'name' => input('name'),
                'phone' => input('phone'),
                'pc_auth_code' => input('pc_auth_code'),
                'm_auth_code' => input('m_auth_code'),
                'position_code' => input('position_code'),
                'org_code' => input('splb'),
                'admin_org_code' => input('m_splb')
            ];
        } else {
            $data = [
                'id' => $id,
                'name' => input('name'),
                'phone' => input('phone'),
                'pc_auth_code' => input('pc_auth_code'),
                'm_auth_code' => input('m_auth_code'),
                'position_code' => input('position_code'),
                'org_code' => input('splb'),
                'store_code' => input('store_code')
            ];

            //组织机构限制 ---查询数据
            $d = Db::table($this->db.'.vip_org')->where('name', $data['org_code'])->field('code')->find();
            //进行判断
            if ($d['code'] == "") {
                $data['org_code'];
            } else {
                $data['org_code']= $d['code'];
            }
            //查询判断
            $z = Db::table($this->db.'.vip_org')
                ->where('pid',$data['org_code'])
                ->find();
            if ($z) {
                webApi(0, 'error', 0, '组织机构必须选最底层！');
            }
        }
        
        $res = Db::table($this->db.'.vip_staff')->update($data);

        unset($id, $d, $z, $data);
        if ($res) {
            webApi(0, 'yes', 0, '修改成功!');
        } else {
            webApi(0, 'no', 0, '修改失败!');
        }
    }

    /**
     * 修改密码
     */
    public function resetPass()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }
        //查询当前登入人
        $operate = Db::table($this->db.'.vip_staff')->where('code', session('info.staff'))->find();

        $data = [
            'password' => input('password'), 
            'passwords' => input('passwords')
        ];

        //密码验证
        if ($data['password'] !== $data['passwords']) {
            webApi(0, 'error', 0, '两次密码不符,请重新输入!');
        }

        $pass = md5($data['password']);

        $info = Db::table($this->db.'.vip_staff')->where('id', $id)->find();
        if ($info['password'] == $pass) {
            webApi(0, 'no', 0, '与近期密码相同，请重新修改');
        }
        
        if ($operate['id'] == 1) {
            $res = Db::table($this->db.'.vip_staff')->where('id', $id)->setField('password', $pass);
            if ($res) {
                if ($id == $operate['id']) {
                    webApi(1001, '修改成功!');
                } else {
                    webApi(0, 'yes', 0, '修改成功!');
                }
            } else {
                webApi(0, 'no', 0, '修改失败!');
            }
        } else {
            if ($id == 1){
                webApi(0, 'error', 0, '不能修改总帐号密码！');
            }
            $res = Db::table($this->db.'.vip_staff')->where('id', $id)->setField('password', $pass);
            if ($res) {
                if ($id == $operate['id']) {
                    webApi(1001, '修改成功!');
                } else {
                    webApi(0, 'yes', 0, '修改成功!');
                }
            } else {
                webApi(0, 'no', 0, '修改失败!');
            }
        }
        unset($id, $operate, $data, $result, $pass, $info);
    }
    
     /**
     * 修改pc端状态
     */
    public function pcReplace()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }
        //查询当前登入人
        $operate = Db::table($this->db.'.vip_staff')->where('code', session('info.staff'))->find();

        if (intval($operate['role']) !== 0) {
            webApi(0, 'error', 0, '员工不能修改PC端状态!');
        }
        // 获取状态
        $replace =Db::table($this->db.'.vip_staff')->find($id);
        
        $pc_status = intval(!$replace['pc_status']);
        //判断pc变手机端也变
        if (intval($replace['m_status']) == 1 && $pc_status == 1) {
            $m_status = $replace['m_status'];
        } else {
            $m_status = intval(!$replace['m_status']);
        }

        //修改的数据
        $data = [
            'id' => $id, 'pc_status' => $pc_status, 'm_status' => $m_status
        ];

        // 执行修改
        $res = Db::table($this->db.'.vip_staff')->update($data);

        //清除变量
        unset($id, $pc_status, $data, $replace);

        //判断并返回数据
        if ($res) {
            webApi(0, 'yes', 0, '修改成功!');
        } else {
            webApi(0, 'no', 0, '修改失败!');
        }
    }

     /**
     * 修改手机端状态
     */
    public function mReplace()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }

        // 获取状态
        $replace =Db::table($this->db.'.vip_staff')->find($id);

        if (intval($replace['m_status']) == 1) {
            webApi(0, 'error', 0, '手机端已启用状态不能修改!');
        }
        $m_status = intval(!$replace['m_status']);

        //修改的数据
        $data = [
            'id' => $id, 'm_status' => $m_status
        ];

        // 执行修改
        $res = Db::table($this->db.'.vip_staff')->update($data);

        // 清除变量
        unset($id, $m_status, $data, $replace);
        
        //判断并返回数据
        if ($res) {
            webApi(0, 'yes', 0, '修改成功!');
        } else {
            webApi(0, 'no', 0, '修改失败!');
        }
    }

}