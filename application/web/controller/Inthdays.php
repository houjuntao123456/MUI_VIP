<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
// use app\web\controller\Common;
use lunar\Lunar;

/**
 * Author lxy
 * Date 2019/01/26
 * Description 365天跟进
 */
class Inthdays extends Common
{
    /**
     * 查询365天跟进
     */
    public function thdaysSel()
    {

        //获取所需数据
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
        $count = Db::table($this->db.'.vip_365_interaction_tpl')
                ->alias('v')
                ->leftJoin($this->db.'.vip_org vg', 'vg.code = v.org_code')
                ->field('v.*,vg.name vgname')
                ->where($where)
                ->where($org)
                ->count();
        //所需数据
        $data = Db::table($this->db.'.vip_365_interaction_tpl')
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
     * 按单号查询小365天跟进
     */
    public function thdaysDillSel()
    {
        $bill = input('bill') ?? null;
        if ($bill == null) {
            webApi(1, '参数错误');
        }
        //统计数量
        $count = Db::table($this->db.'.vip_365_interaction_tpl_info')->where('365_tpl_code',$bill)->count();
        //所需数据
        $data = Db::table($this->db.'.vip_365_interaction_tpl_info')->where('365_tpl_code',$bill)->select();
        //更改格式
        foreach($data as $k=>$v ){
            $data[$k]['time_g'] = date('Y-m-d', $v['time']);
        }
        unset($bill);
        webApi(0, '', $count, $data);
    }

    /**
     * 365天跟进添加
     */
    public function thdaysAdd()
    {
        $bill = 'MB365HD'.str_replace('.' , '', microtime(1));
        $token = input('access_token');
        $datacache = cache('viah_'.$token);
        if ($datacache == null) {
            webApi(0, 'error',0 ,'添加失败,请创建365天跟进!');
        }
        $data = [
            'code' => $bill,
            'name' => input('name'),
            'org_code' => input('splb'),
            'remark' => input('remark')
        ];

        $z = Db::table($this->db.'.vip_org')->where('pid', $data['org_code'])->find();

        if ($z) {
            webApi(0, 'error', 0, '所属机构必须选最底层！');
        }
        // 启动事务
        Db::startTrans();
        try {
            Db::table($this->db.'.vip_365_interaction_tpl')->insert($data);
            foreach($datacache as $k=>$v){
                $c = array_merge($v , array('365_tpl_code' => $bill));
                Db::table($this->db.'.vip_365_interaction_tpl_info')->insert($c);
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
        unset($bill,$data,$c);
        if ($res) {
            cache('viah_' . $token, null);
            webApi(0,'yes' , 0, '添加成功!');
        } else {
            webApi(0,'no', 0, '添加失败!');
        }
        
    }

    /**
     * 小跟进查表查出缓存中的内容
     */
    public function thdaysCacheSel()
    {   
        $token = input('access_token');
        $datacache = cache('viah_'.$token);
        if($datacache){
            foreach($datacache as $k=>$v ){
                $datacache[$k]['time_g'] = date('Y-m-d', $v['time']);
            }
        }
        webApi(0, '', 0, $datacache);
    }

    /**
     * 小跟进添加到緩存
     */
    public function thdaysCacheAdd()
    {
        $token = input('access_token');
        $d = [
            'delid' => 'mb365'.str_replace('.' , '', microtime(1)),
            'name' => '',
            'time'=> strtotime(input('time')),
            'purpose'=> input('purpose'),
            'function'=> input('function'),
            'speech'=> input('speech')
        ];
        //判断日子并赋值
        if (input('follow_up') == 0) {
            $family = Db::table($this->db.'.vip_family')->where('code', input('name'))->find();
            $d['name'] = $family['relation'];
        } else if (input('follow_up') == 1) {
            $c = $this->getTradition();
            foreach ($c as $k => $v) {
                if ($v['code'] == input('name')) {
                    $d['name'] = $v['name'];
                }
            }
        } else if (input('follow_up') == 2) {
            $f = $this->getTpublics();
            foreach ($f as $k => $v) {
                if ($v['code'] == input('name')) {
                    $d['name'] = $v['name'];
                }
            }
        } else if (input('follow_up') == 3) {
            $d['name'] = input('name');
        }
        
        if (cache('?viah_'.$token)) {
            $data = cache('viah_'.$token);
        } else {
            $data = [];
        }
        array_push($data, $d);
        cache('viah_'.$token, $data, 3600);
        unset($d);
        if(cache('viah_'.$token) !== ""){
            webApi(0, 'yes', 0, '添加成功!');
        }else {
            webApi(0,'no', 0, '添加失败!');
        }
    }

    /**
     * 清除缓存
     */
    public function thdaysCacheClean()
    {
        $token = input('access_token');
        //365天跟进
        cache('viah_'.$token, null);
        webApi(0, '清除缓存！');
    }

    /**
     * 小跟进删除
     */
    public function thdaysCacheDel()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }
        $token = input('access_token');
        $datacache = cache('viah_'.$token);

        if (count($datacache) > 1) {
            foreach ($datacache as $k=>$v) {
                if ($id == $v['delid']) {
                    unset($datacache[$k]);
                }
            }
            sort($datacache);
            cache('viah_'.$token, $datacache, 3600);
        } else {
            cache('viah_'.$token, null);
        }
        unset($id);
        webApi(0,'删除成功!');
    }

    /**
     * 删除365天跟进也删除365天跟进中的小跟进
     */
    public function thdaysDel()
    {
        [$id, $bill] = [input('id'), input('bill')];
        if ($id == null || $bill == null) {
            webApi(1,'参数错误');
        }
        //启动事务
        Db::startTrans();
        try {
            Db::table($this->db.'.vip_365_interaction_tpl')->delete($id);
            Db::table($this->db.'.vip_365_interaction_tpl_info')->where('365_tpl_code', $bill)->delete();
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
     * 批量删除365天跟进
     */
    public function thdaysDelAll()
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
            Db::table($this->db.'.vip_365_interaction_tpl')->where('id', 'in', $ids)->delete();
            Db::table($this->db.'.vip_365_interaction_tpl_info')->where('365_tpl_code', 'in', $bills)->delete();
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
    public function thdaysEdit()
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
        
        $res = Db::table($this->db.'.vip_365_interaction_tpl')->update($data);
        
        unset($data, $id);
        if ($res) {
            webApi(0, 'yes', 0, '修改成功!');
        } else {
            webApi(0, 'no', 0, '修改失败!');
        }
    }

    /**
     * 选择日子
     */
    public function festival()
    {
        if (input('follow_up') ==  "0") {//感动日子
            $g = Db::table($this->db.'.vip_family')->field('relation as name, code')->group('relation')->select();
            webApi(0, '', 0, $g);
        } else if (input('follow_up') == "1") {//传统日子
            $c = $this->getTradition();
            webApi(0, '', 0, $c);
        } else if (input('follow_up') == "2") {//公众日子
            $f = $this->getTpublics();
            webApi(0, '', 0, $f);
        }
    }

    /**
     * 赋值时间
     */
    public function thdaysTime()
    {
        //定义时间
        $time = ['time_g' => ''];
        //判断
        if (input('follow_up') ==  "0") {//感动日子
            $g = Db::table($this->db.'.vip_family')->where('code', input('name'))->find();
            $g['time_g'] = date('Y-m-d', $g['date']);
            webApi(0, '', 0, $g);
        } else if (input('follow_up') == "1") {//传统日子
            
            $c = $this->getTradition();

            foreach ($c as $k => $v) {
                if ($v['code'] == input('name')) {
                    $time['time_g'] = $v['date'];
                }
            }

            webApi(0, '', 0, $time);
        } else if (input('follow_up') == "2") {//公众日子

            $f = $this->getTpublics();
            
            foreach ($f as $k => $v) {
                if ($v['code'] == input('name')) {
                    $time['time_g'] = $v['date'];
                }
            }
            webApi(0, '', 0, $time);
        }
    }

    /**
     * 封装传统日子数组
     */
    private function getTradition()
    {
        return [
                0 => ['code' => 1,'name' => '春节', 'date' => $this->getDate(date('Y'), '01', '01') ],
                1 => ['code' => 2,'name' => '元宵节', 'date' => $this->getDate(date('Y'), '01', '15') ],
                2 => ['code' => 3,'name' => '二月二', 'date' => $this->getDate(date('Y'), '02', '02') ],
                3 => ['code' => 4,'name' => '端午节', 'date' => $this->getDate(date('Y'), '05', '05') ],
                4 => ['code' => 5,'name' => '七夕节', 'date' => $this->getDate(date('Y'), '07', '07') ],
                5 => ['code' => 6,'name' => '中秋节', 'date' => $this->getDate(date('Y'), '08', '15') ],
                6 => ['code' => 7,'name' => '重阳节', 'date' => $this->getDate(date('Y'), '09', '09') ],
                7 => ['code' => 8,'name' => '腊八节', 'date' => $this->getDate(date('Y'), '12', '08') ]
            ];
    }

    /**
     * 封装公众日子数组
     */
    private function getTpublics()
    {
        return [
                0 => ['code' => 1,'name' => '元旦', 'date' => date('Y',time()).'-01-01' ],
                1 => ['code' => 2,'name' => '情人节', 'date' => date('Y',time()).'-02-14' ],
                2 => ['code' => 3,'name' => '妇女节', 'date' => date('Y',time()).'-03-08' ],
                3 => ['code' => 4,'name' => '劳动节', 'date' => date('Y',time()).'-05-01' ],
                4 => ['code' => 5,'name' => '青年节', 'date' => date('Y',time()).'-05-04' ],
                5 => ['code' => 6,'name' => '儿童节', 'date' => date('Y',time()).'-06-01' ],
                6 => ['code' => 7,'name' => '建党节', 'date' => date('Y',time()).'-07-01' ],
                7 => ['code' => 8,'name' => '建军节', 'date' => date('Y',time()).'-08-01' ],
                8 => ['code' => 9,'name' => '教师节', 'date' => date('Y',time()).'-09-10' ],
                9 => ['code' => 10,'name' => '国庆节', 'date' => date('Y',time()).'-10-01' ],
                10 => ['code' => 11,'name' => '平安夜', 'date' => date('Y',time()).'-12-24' ],
                11 => ['code' => 12,'name' => '圣诞节', 'date' => date('Y',time()).'-12-25' ]
            ];
    }

    /**
     * 封装转换公历
     */
    private function getDate($y, $m, $d)
    {
        $lunar = new Lunar;
        $res = $lunar->convertLunarToSolar($y, $m, $d);
        return $res[0].'-'.$res[1].'-'.$res[2];
    }

}