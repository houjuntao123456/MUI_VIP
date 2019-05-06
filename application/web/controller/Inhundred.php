<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
// use app\web\controller\Common;

/**
 * Author lxy
 * Date 2019/01/26
 * Description 100天跟进
 */
class Inhundred extends Common
{
    /**
     * 查询100天跟进
     */
    public function hundredSel()
    {

        //分页信息
        [$page, $limit, $search] = [input('page'), input('limit'), input('search')];
        
        //模糊查询
        if ($search != '') {
            $where[] = ['v.name|vg.name', 'like', '%' . $search . '%'];
        } else {
            $where = true;
        }
        //查询当前登入人
        $operate = Db::table($this->db.'.vip_staff')->where('code', session('info.staff'))->find();
        //所属机构限制
        if ($operate['id'] == 1) {
            $org = true;
        } else {//判断是否是管理
            if ($operate['role'] == 0) {//管理查询管理机构
                $org[] = ['v.org_code', 'in', $operate['admin_org_code'].','.$operate['org_code']];
            } else { //员工查询所属机构
                $org[] = ['v.org_code', '=', $operate['org_code']];
            }
        }
        //统计数量
        $count = Db::table($this->db.'.vip_hundred_interaction_tpl')
                ->alias('v')
                ->leftJoin($this->db.'.vip_org vg', 'vg.code = v.org_code')
                ->field('v.*,vg.name vgname')
                ->where($where)
                ->where($org)
                ->count();
        //所需数据
        $data = Db::table($this->db.'.vip_hundred_interaction_tpl')
                ->alias('v')
                ->leftJoin($this->db.'.vip_org vg', 'vg.code = v.org_code')
                ->field('v.*,vg.name vgname')
                ->where($where)
                ->where($org)
                ->order('v.id','desc') //按照登记时间降序排列
                ->page($page, $limit)
                ->select();

        unset($page, $limit, $where, $org);

        webApi(0, '', $count, $data);
    }

    /**
     * 按单号查询100天跟进
     */
    public function hundredDillSel()
    {
        $bill = input('bill') ?? null;
        if ($bill == null) {
            webApi(1, '参数错误');
        }
        //统计数量
        $count = Db::table($this->db.'.vip_hundred_interaction_tpl_info')->where('hundred_tpl_code',$bill)->count();
        // 所需数据
        $data = Db::table($this->db.'.vip_hundred_interaction_tpl_info')->where('hundred_tpl_code',$bill)->select();

        unset($bill);

        webApi(0, '', $count, $data);
    }

    /**
     * 100天跟进添加
     */
    public function hundredAdd()
    {
        $bill = 'MBHTHD'.str_replace('.' , '', microtime(1));
        $token = input('access_token');
        $datacache = cache('inh_'.$token);
        if ($datacache == null) {
            webApi(0, 'error',0,'添加失败,请创建100天跟进!');
        }
        $data = [
            'code' => $bill,
            'org_code' => input('splb'),
            'name' => input('name')
        ];

        $z = Db::table($this->db.'.vip_org')->where('pid',$data['org_code'])->find();

        if ($z) {
            webApi(0, 'error', 0, '所属机构必须选最底层！');
        }
        // 启动事务
        Db::startTrans();
        try {
            Db::table($this->db.'.vip_hundred_interaction_tpl')->insert($data);
            foreach($datacache as $k=>$v){
                $c = array_merge($v , array('hundred_tpl_code' => $bill));
                Db::table($this->db.'.vip_hundred_interaction_tpl_info')->insert($c);
            }
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            dump($e);
            $res = false;
        }

        unset($bill, $data, $c);

        if ($res) {
            cache('inh_' . $token, null);
            webApi(0,'yes', 0, '添加成功!');
        } else {
            webApi(0,'no', 0, '添加失败!');
        }
    }

    /**
     * 小跟进查表查出缓存中的内容
     */
    public function hundredCacheSel()
    {   
        $token = input('access_token');
        $datacache = cache('inh_'.$token);
        webApi(0, '', 0, $datacache);
    }

    /**
     * 小跟进添加到緩存
     */
    public function hundredCacheAdd()
    {
        $token = input('access_token');

        $d = [
            'delid' => 'mhthd'.str_replace('.' , '', microtime(1)),
            'time'=> input('time'),
            'purpose'=> input('purpose'),
            'function'=> input('function'),
            'speech'=> input('speech')
        ];

        $data = cache('inh_'.$token);

        if ($data){
            foreach ($data as $k => $v) {
                if ($k >= 8) {
                    webApi(0, 'error', 0, '100天跟进默认为9条!');
                }
            }
        }
        
        if (cache('?inh_'.$token)) {
            $data = cache('inh_'.$token);
        } else {
            $data = [];
        }
        array_push($data, $d);
        cache('inh_'.$token, $data, 3600);
        unset($d);
        if(cache('inh_'.$token) !== ""){
            webApi(0, 'yes', 0, '添加成功!');
        }else {
            webApi(0,'no', 0, '添加失败!');
        }
    }

    /**
     * 清除缓存
     */
    public function hundredCacheClean()
    {
        $token = input('access_token');
        cache('inh_'.$token, null);
        webApi(0, '清除缓存！');
    }

    /**
     * 小跟进删除
     */
    public function hundredCacheDel()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }
        $token = input('access_token');
        $datacache = cache('inh_'.$token);

        if (count($datacache) > 1) {
            foreach ($datacache as $k=>$v) {
                if ($id == $v['delid']) {
                    unset($datacache[$k]);
                }
            }
            sort($datacache);
            cache('inh_'.$token, $datacache, 3600);
        } else {
            cache('inh_'.$token, null);
        }
        unset($id);
        webApi(0,'删除成功!');
    }

    /**
     * 删除100天跟进也删除中的小100天跟进
     */
    public function hundredDel()
    {
        [$id, $bill] = [input('id'), input('bill')];
        if ($id == null || $bill == null) {
            webApi(1,'参数错误');
        }
        //启动事务
        Db::startTrans();
        try {
            Db::table($this->db.'.vip_hundred_interaction_tpl')->delete($id);
            Db::table($this->db.'.vip_hundred_interaction_tpl_info')->where('hundred_tpl_code', $bill)->delete();
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            dump($e);
            $res = false;
        }
        unset($id,$bill);
        if ($res) {
            webApi(0, 'yes', 0, '删除成功!');
        } else {
            webApi(0, 'no', 0, '删除失败!');
        }
    }

    /**
     * 批量删除100天跟进也删除中的小100天跟进
     */
    public function hundredDelAll()
    {
        [$ids, $bills] = [input('ids'), input('bill')];
        if ($ids == null || $bills == null) {
            webApi(1,'参数错误');
        }

        $ids = trim($ids, ',');
        $bills = trim($bills, ',');
        //启动事务
        Db::startTrans();
        try {
            Db::table($this->db.'.vip_hundred_interaction_tpl')->where('id', 'in', $ids)->delete();
            Db::table($this->db.'.vip_hundred_interaction_tpl_info')->where('hundred_tpl_code', 'in', $bills)->delete();
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            dump($e);
            $res = false;
        }
        unset($ids,$bills);
        if ($res) {
            webApi(0, 'yes', 0, '删除成功!');
        } else {
            webApi(0, 'no', 0, '删除失败!');
        }
    }

    /**
     * 编辑互动名称与组织机构
     */
    public function hundredEdit()
    {
        $id = intval(input('id')) ?? null;
        if ($id == null) {
            webApi(1, '参数错误!');
        }

        $data = [
            'id' => $id,
            'org_code' => input('splb'),
            'name' => input('name')
        ];
        $res = Db::table($this->db.'.vip_hundred_interaction_tpl')->update($data);
        
        unset($data, $id);
        if ($res) {
            webApi(0, 'yes', 0, '修改成功!');
        } else {
            webApi(0, 'no', 0, '修改失败!');
        }
    }

    /**
     * 修改小跟进
     */
    public function hundredRditSmall()
    {
        $id = intval(input('id'))?? null;
        if ($id == null) {
            webApi(1, '参数错误!');
        }
        $data = [
            'id' => $id,
            'time' => input('time'),
            'purpose' => input('purpose'),
            'function' => input('function'),
            'speech' => input('speech')
        ];
        
        $res = Db::table($this->db.'.vip_hundred_interaction_tpl_info')->update($data);
        
        unset($data, $id);
        if ($res) {
            webApi(0, 'yes', 0, '修改成功!');
        } else {
            webApi(0, 'no', 0, '修改失败!');
        }
    }

}