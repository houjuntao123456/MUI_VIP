<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
use app\web\controller\Common;

/**
 * Author hrl
 * Date 2018/1/15
 * Description STM员工管理 --- V票管理
 */
class Vmanage extends Common
{
    /**
     * v票规则列表
     */   
    public function index()
    {
        $data = Db::table($this->db.'.vip_vticket_rule')
                ->alias('r')
                ->leftJoin($this->db.'.vip_staff f', 'r.operate_code = f.code')
                ->field('r.*,f.name as staff_name')
                ->where('operate_code',session('info.staff'))
                ->select();
        if ($data) {
            foreach ($data as $k=>$v) {
                $staffs = Db::table($this->db.'.vip_staff')->field('name')->where('code', 'in', $v['staff_codes'])->select();
                if (!empty($staffs)) {
                    $data[$k]['staff_names'] = implode(',', array_column($staffs, 'name'));
                } else {
                    $data[$k]['staff_names'] = '';
                }
            }
        }
        webApi(0, '', '', $data);  
        
    }
    /**
     * 添加V票规则
     */
    public function vRuleAdd()
    {
        $data = [
            'name' => trim(input('name')),
            'staff_codes' => trim(input('staff'), ','),
            'operate_code' => session('info.staff'),
            'code' => 'GZ'.str_replace('.', '', microtime(true))    //code
        ];
        if ($data == '') {
            webApi(1, '参数错误');
        }
        // 验证器
        $result = $this->validate(input(),'app\web\validate\VmanageValidate');
        if ($result!== true) {
            webApi(0, 'error', 0, $result);
        }

        $codes = explode(',', $data['staff_codes']);
        $codesNum = count($codes); 
        for ($i = 0; $i < $codesNum; $i++) {
            $sql = 'select * from '.$this->db.'.vip_vticket_rule where find_in_set('.$codes[$i].', staff_codes)';
            if (!empty(Db::query($sql))) {
                webApi(0, 'error', 0, '所选员工已存在其他V票规则中，不可重复选择');
            }
        }


        $res = Db::table($this->db.'.vip_vticket_rule')->insert($data);
        unset($data);
        if($res){
            webApi(0,'yes',0,'添加成功');
        }else{
            webApi(0,'no',0,'添加失败');
        }

    }

    /**
     * 员工列表
     */
    public function staffList()
    {
        $adminOrg = session('info.admin_org');
        if ($adminOrg == '') {
            webApi(0, '', 0, []);
        }
        $data = Db::table($this->db.'.vip_staff')->field('code,name,phone')->where('org_code', 'in', $adminOrg)->select();
        // 默认选中
        if (strval(input('staff')) != '' && !empty($data)) {
            $codes = explode(',', trim(input('staff'), ','));
            foreach ($data as $k=>$v) {
                if ( in_array($v['code'], $codes) ) {
                    $data[$k]['LAY_CHECKED'] = true;
                }
            }
        }
        webApi(0, '', '', $data);
    }

    /**
     * 编辑V票规则列表
     */
    public function edit()
    {
        $data = [
            'name' => trim(input('name')),
            'staff_codes' => trim(input('staff'), ','),
            'operate_code' => session('info.staff')
        ];
        if ($data == '') {
            webApi(1, '参数错误');
        }
        // 验证器
        $result = $this->validate(input(),'app\web\validate\VmanageValidate');
        if ($result!== true) {
            webApi(0, 'error', 0, $result);
        }
        
        $res = Db::table($this->db.'.vip_vticket_rule')->where('id', input('id'))->update($data);
        unset($data);
        if($res){
            webApi(0,'yes',0,'编辑成功');
        }else{
            webApi(0,'NO',0,'编辑失败');
        }

    }

    /**
     * 删除v票规则
     */
    public function del()
    {
        $res = Db::table($this->db.'.vip_vticket_rule')->where('id', input('id'))->delete();
        unset($data);
        if($res){
            webApi(0,'yes',0,'删除成功');
        }else{
            webApi(0,'NO',0,'删除失败');
        }
    }

}