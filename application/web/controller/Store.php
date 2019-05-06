<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
// use app\web\controller\Common;
use app\web\service\ErpWhere as EW;

/**
 * Author lxy
 * Date 2018/12/28
 * Description 门店管理
 */
class Store extends Common
{
    /**
     * 查表
     */
    public function storeSel()
    {
        // 获取分页数据
        [$page, $limit] = [input('page'), input('limit')];

        //模糊查询
        if (input('search') != '') {
            $where[] = ['v.code|v.name|v.contacts|v.phone|vg.name', 'like', '%' . input('search') . '%'];
        } else {
            $where = true;
        }
        //查询当前登入人
        $operate = Db::table($this->db.'.vip_staff')->where('code', session('info.staff'))->find();
        //门店限制
        if ($operate['id'] == 1) {
            $w = true;
        } else if ($operate['store_code'] == "") {
            $w = true;
        } else {
            $w[] = ['v.code', '=', $operate['store_code']];
        }
        //统计数量
        $count = Db::table($this->db.'.vip_store')
                ->alias('v')
                ->leftJoin($this->db.'.vip_org vg', 'vg.code = v.org_code')
                ->field('v.*,vg.name vgname')
                ->where($where)
                ->where($w)
                ->count();

        //查询数据
        $data = Db::table($this->db.'.vip_store')
            ->alias('v')
            ->leftJoin($this->db.'.vip_org vg', 'vg.code = v.org_code')
            ->field('v.*,vg.name vgname')
            ->where($where)
            ->where($w)
            ->order('v.create_time','desc') //按照登记时间降序排列
            ->page($page, $limit)
            ->select();
        
        //清除变量
        unset($page,$limit);

        //修改格式
        foreach ($data as $k => $v) {
            //时间格式的转换
            $data[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
        }

        //返回数据
        webApi(0, '', $count, $data);
    }

    /**
     * 添加
     */
    public function storeAdd()
    {
        //接受数据
        $data = [
            'code' => 'MDERP'.str_replace('.' , '', microtime(1)),
            'org_code' => input('splb'),
            'name' => input('name'),
            'contacts' => input('contacts'),
            'phone' => input('phone'),
            'address' => input('address'),
            'full_address' => input('full_address'),
            'create_time' => time()
        ];

        //门店名称限制
        $store_name = Db::table($this->db.'.vip_store')->where('name', $data['name'])->find();

        if ($store_name) {
            webApi(0, 'error', 0, '门店名称不能重复!');
        }
        //组织机构限制
        $z = Db::table($this->db.'.vip_org')
            ->where('pid',$data['org_code'])
            ->find();

        if ($z) {
            webApi(0, 'error', 0, '组织机构必须选最底层！');
        }

        //周期
        $rfm = [
            'f_consumption' => 180, 'm_consumption' => 180, 'i_consumption' => 180,
            'n_consumption' => 180, 'p_consumption' => 180, 'a_consumption' => 180,
            'j_consumption' => 180, 'c_consumption' => 365, 'store_code' => $data['code']
        ];
        //R未消费天数
        $r = [
            [ 
                'r_type' => '流失', 'r_intervalone' => 456, 'r_intervaltwo' => 10000000, 'r_score' => 0, 
                'r_create_time' => time(), 'r_update_time' => time(), 'store_code' => $data['code']
            ],[
                'r_type' => '休眠', 'r_intervalone' => 366, 'r_intervaltwo' => 455, 'r_score' => 1,
                'r_create_time' => time(), 'r_update_time' => time(), 'store_code' => $data['code']
            ],[
                'r_type' => '睡眠', 'r_intervalone' => 271, 'r_intervaltwo' => 365, 'r_score' => 2,
                'r_create_time' => time(), 'r_update_time' => time(), 'store_code' => $data['code']
            ],[
                'r_type' => '沉默', 'r_intervalone' => 181, 'r_intervaltwo' => 270, 'r_score' => 3,
                'r_create_time' => time(), 'r_update_time' => time(), 'store_code' => $data['code']
            ],[
                'r_type' => '常规', 'r_intervalone' => 91, 'r_intervaltwo' => 180, 'r_score' => 4,
                'r_create_time' => time(), 'r_update_time' => time(), 'store_code' => $data['code']
            ],[
                'r_type' => '活跃', 'r_intervalone' => 0, 'r_intervaltwo' => 90, 'r_score' => 5,
                'r_create_time' => time(), 'r_update_time' => time(), 'store_code' => $data['code']
            ]
        ];
        //F消费的次数
        $f = [
            [ 
                'f_type' => '无', 'f_intervalone' => 0, 'f_intervaltwo' => 1, 'f_score' => 0, 
                'f_create_time' => time(), 'f_update_time' => time(), 'store_code' => $data['code']
            ],[
                'f_type' => '超低回头率', 'f_intervalone' => 1, 'f_intervaltwo' => 2, 'f_score' => 1,
                'f_create_time' => time(), 'f_update_time' => time(), 'store_code' => $data['code']
            ],[
                'f_type' => '低回头率', 'f_intervalone' => 2, 'f_intervaltwo' => 3, 'f_score' => 2,
                'f_create_time' => time(), 'f_update_time' => time(), 'store_code' => $data['code']
            ],[
                'f_type' => '中回头率', 'f_intervalone' => 3, 'f_intervaltwo' => 4, 'f_score' => 3,
                'f_create_time' => time(), 'f_update_time' => time(), 'store_code' => $data['code']
            ],[
                'f_type' => '高回头率', 'f_intervalone' => 4, 'f_intervaltwo' => 5, 'f_score' => 4,
                'f_create_time' => time(), 'f_update_time' => time(), 'store_code' => $data['code']
            ],[
                'f_type' => '超高回头率', 'f_intervalone' => 5, 'f_intervaltwo' => 10000000, 'f_score' => 5,
                'f_create_time' => time(), 'f_update_time' => time(), 'store_code' => $data['code']
            ]
        ];
        //M消费总金额
        $m = [
            [ 
                'm_type' => '无', 'm_intervalone' => 0, 'm_intervaltwo' => 3000, 'm_score' => 0, 
                'm_create_time' => time(), 'm_update_time' => time(), 'store_code' => $data['code']
            ],[
                'm_type' => '超低价值', 'm_intervalone' => 3000, 'm_intervaltwo' => 8000, 'm_score' => 1,
                'm_create_time' => time(), 'm_update_time' => time(), 'store_code' => $data['code']
            ],[
                'm_type' => '低价值', 'm_intervalone' => 8000, 'm_intervaltwo' => 10000, 'm_score' => 2,
                'm_create_time' => time(), 'm_update_time' => time(), 'store_code' => $data['code']
            ],[
                'm_type' => '中价值', 'm_intervalone' => 10000, 'm_intervaltwo' => 20000, 'm_score' => 3,
                'm_create_time' => time(), 'm_update_time' => time(), 'store_code' => $data['code']
            ],[
                'm_type' => '高价值', 'm_intervalone' => 20000, 'm_intervaltwo' => 30000, 'm_score' => 4,
                'm_create_time' => time(), 'm_update_time' => time(), 'store_code' => $data['code']
            ],[
                'm_type' => '超高价值', 'm_intervalone' => 30000, 'm_intervaltwo' => 10000000, 'm_score' => 5,
                'm_create_time' => time(), 'm_update_time' => time(), 'store_code' => $data['code']
            ]
        ];
        //I转介绍人数
        $i = [
            [ 
                'i_type' => '无', 'i_intervalone' => 0, 'i_intervaltwo' => 1, 'i_score' => 0, 
                'i_create_time' => time(), 'i_update_time' => time(), 'store_code' => $data['code']
            ],[
                'i_type' => '超低转介绍', 'i_intervalone' => 1, 'i_intervaltwo' => 2, 'i_score' => 1,
                'i_create_time' => time(), 'i_update_time' => time(), 'store_code' => $data['code']
            ],[
                'i_type' => '低转介绍', 'i_intervalone' => 2, 'i_intervaltwo' => 3, 'i_score' => 2,
                'i_create_time' => time(), 'i_update_time' => time(), 'store_code' => $data['code']
            ],[
                'i_type' => '中转介绍', 'i_intervalone' => 3, 'i_intervaltwo' => 4, 'i_score' => 3,
                'i_create_time' => time(), 'i_update_time' => time(), 'store_code' => $data['code']
            ],[
                'i_type' => '高转介绍', 'i_intervalone' => 4, 'i_intervaltwo' => 5, 'i_score' => 4,
                'i_create_time' => time(), 'i_update_time' => time(), 'store_code' => $data['code']
            ],[
                'i_type' => '超高转介绍', 'i_intervalone' => 5, 'i_intervaltwo' => 6, 'i_score' => 5,
                'i_create_time' => time(), 'i_update_time' => time(), 'store_code' => $data['code']
            ]
        ];
        //N消费的件数
        $n = [
            [ 
                'n_type' => '无', 'n_intervalone' => 0, 'n_intervaltwo' => 1, 'n_score' => 0, 
                'n_create_time' => time(), 'n_update_time' => time(), 'store_code' => $data['code']
            ],[
                'n_type' => '超低件数', 'n_intervalone' => 1, 'n_intervaltwo' => 2, 'n_score' => 1,
                'n_create_time' => time(), 'n_update_time' => time(), 'store_code' => $data['code']
            ],[
                'n_type' => '低件数', 'n_intervalone' => 2, 'n_intervaltwo' => 3, 'n_score' => 2,
                'n_create_time' => time(), 'n_update_time' => time(), 'store_code' => $data['code']
            ],[
                'n_type' => '中件数', 'n_intervalone' => 3, 'n_intervaltwo' => 4, 'n_score' => 3,
                'n_create_time' => time(), 'n_update_time' => time(), 'store_code' => $data['code']
            ],[
                'n_type' => '高件数', 'n_intervalone' => 4, 'n_intervaltwo' => 5, 'n_score' => 4,
                'n_create_time' => time(), 'n_update_time' => time(), 'store_code' => $data['code']
            ],[
                'n_type' => '超高件数', 'n_intervalone' => 5, 'n_intervaltwo' => 6, 'n_score' => 5,
                'n_create_time' => time(), 'n_update_time' => time(), 'store_code' => $data['code']
            ]
        ];
        //P消费客单价
        $p = [
            [ 
                'p_type' => '无', 'p_intervalone' => 0, 'p_intervaltwo' => 500, 'p_score' => 0, 
                'p_create_time' => time(), 'p_update_time' => time(), 'store_code' => $data['code']
            ],[
                'p_type' => '超低客单价', 'p_intervalone' => 500, 'p_intervaltwo' => 1000, 'p_score' => 1,
                'p_create_time' => time(), 'p_update_time' => time(), 'store_code' => $data['code']
            ],[
                'p_type' => '低客单价', 'p_intervalone' => 1000, 'p_intervaltwo' => 2000, 'p_score' => 2,
                'p_create_time' => time(), 'p_update_time' => time(), 'store_code' => $data['code']
            ],[
                'p_type' => '中客单价', 'p_intervalone' => 2000, 'p_intervaltwo' => 3000, 'p_score' => 3,
                'p_create_time' => time(), 'p_update_time' => time(), 'store_code' => $data['code']
            ],[
                'p_type' => '高客单价', 'p_intervalone' => 3000, 'p_intervaltwo' => 4000, 'p_score' => 4,
                'p_create_time' => time(), 'p_update_time' => time(), 'store_code' => $data['code']
            ],[
                'p_type' => '超高客单价', 'p_intervalone' => 4000, 'p_intervaltwo' => 10000000, 'p_score' => 5,
                'p_create_time' => time(), 'p_update_time' => time(), 'store_code' => $data['code']
            ]
        ];
        //A消费件单价
        $a = [
            [ 
                'a_type' => '无', 'a_intervalone' => 0, 'a_intervaltwo' => 500, 'a_score' => 0, 
                'a_create_time' => time(), 'a_update_time' => time(), 'store_code' => $data['code']
            ],[
                'a_type' => '超低价位', 'a_intervalone' => 500, 'a_intervaltwo' => 1000, 'a_score' => 1,
                'a_create_time' => time(), 'a_update_time' => time(), 'store_code' => $data['code']
            ],[
                'a_type' => '低价位', 'a_intervalone' => 1000, 'a_intervaltwo' => 2000, 'a_score' => 2,
                'a_create_time' => time(), 'a_update_time' => time(), 'store_code' => $data['code']
            ],[
                'a_type' => '中价位', 'a_intervalone' => 2000, 'a_intervaltwo' => 3000, 'a_score' => 3,
                'a_create_time' => time(), 'a_update_time' => time(), 'store_code' => $data['code']
            ],[
                'a_type' => '高价位', 'a_intervalone' => 3000, 'a_intervaltwo' => 4000, 'a_score' => 4,
                'a_create_time' => time(), 'a_update_time' => time(), 'store_code' => $data['code']
            ],[
                'a_type' => '超高价位', 'a_intervalone' => 4000, 'a_intervaltwo' => 10000000, 'a_score' => 5,
                'a_create_time' => time(), 'a_update_time' => time(), 'store_code' => $data['code']
            ]
        ];
        //J消费连带率
        $j = [
            [ 
                'j_type' => '无', 'j_intervalone' => 0, 'j_intervaltwo' => 1, 'j_score' => 0, 
                'j_create_time' => time(), 'j_update_time' => time(), 'store_code' => $data['code']
            ],[
                'j_type' => '超低连带率', 'j_intervalone' => 1, 'j_intervaltwo' => 2, 'j_score' => 1,
                'j_create_time' => time(), 'j_update_time' => time(), 'store_code' => $data['code']
            ],[
                'j_type' => '低连带率', 'j_intervalone' => 2, 'j_intervaltwo' => 3, 'j_score' => 2,
                'j_create_time' => time(), 'j_update_time' => time(), 'store_code' => $data['code']
            ],[
                'j_type' => '中连带率', 'j_intervalone' => 3, 'j_intervaltwo' => 4, 'j_score' => 3,
                'j_create_time' => time(), 'j_update_time' => time(), 'store_code' => $data['code']
            ],[
                'j_type' => '高连带率', 'j_intervalone' => 4, 'j_intervaltwo' => 5, 'j_score' => 4,
                'j_create_time' => time(), 'j_update_time' => time(), 'store_code' => $data['code']
            ],[
                'j_type' => '超高连带率', 'j_intervalone' => 5, 'j_intervaltwo' => 6, 'j_score' => 5,
                'j_create_time' => time(), 'j_update_time' => time(), 'store_code' => $data['code']
            ]
        ];
        //C会员年消费
        $c = [
            [ 
                'c_type' => '无消费', 'c_intervalone' => 0, 'c_intervaltwo' => 5000, 'c_score' => 0, 
                'c_create_time' => time(), 'c_update_time' => time(), 'store_code' => $data['code']
            ],[
                'c_type' => '超低消费', 'c_intervalone' => 5000, 'c_intervaltwo' => 10000, 'c_score' => 1,
                'c_create_time' => time(), 'c_update_time' => time(), 'store_code' => $data['code']
            ],[
                'c_type' => '低消费', 'c_intervalone' => 10000, 'c_intervaltwo' => 20000, 'c_score' => 2,
                'c_create_time' => time(), 'c_update_time' => time(), 'store_code' => $data['code']
            ],[
                'c_type' => '中消费', 'c_intervalone' => 20000, 'c_intervaltwo' => 30000, 'c_score' => 3,
                'c_create_time' => time(), 'c_update_time' => time(), 'store_code' => $data['code']
            ],[
                'c_type' => '高消费', 'c_intervalone' => 30000, 'c_intervaltwo' => 50000, 'c_score' => 4,
                'c_create_time' => time(), 'c_update_time' => time(), 'store_code' => $data['code']
            ],[
                'c_type' => '超高消费', 'c_intervalone' => 50000, 'c_intervaltwo' => 10000000, 'c_score' => 5,
                'c_create_time' => time(), 'c_update_time' => time(), 'store_code' => $data['code']
            ]
        ];
        
        // 启动事务
        Db::startTrans();
        try {
            //操作添加
            Db::table($this->db.'.vip_store')->insert($data);// 添加门店
            Db::table($this->db.'.vip_rfm_days')->insert($rfm);// rfm周期添加
            Db::table($this->db.'.vip_rfm_r')->insertAll($r, true);// R未消费天数添加
            Db::table($this->db.'.vip_rfm_f')->insertAll($f, true);// F消费的次数添加
            Db::table($this->db.'.vip_rfm_m')->insertAll($m, true);// M消费总金额添加
            Db::table($this->db.'.vip_rfm_i')->insertAll($i, true);// I转介绍人数添加
            Db::table($this->db.'.vip_rfm_n')->insertAll($n, true);// N消费的件数添加
            Db::table($this->db.'.vip_rfm_p')->insertAll($p, true);// P消费客单价添加
            Db::table($this->db.'.vip_rfm_a')->insertAll($a, true);// A消费件单价添加
            Db::table($this->db.'.vip_rfm_j')->insertAll($j, true);// J消费连带率添加
            Db::table($this->db.'.vip_rfm_c')->insertAll($c, true);// C会员年消费添加
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $res = false;
        }

        //清除变量
        unset($data,$number,$z);

        //返回数据进行判断
        if ($res) {
            webApi(0, 'yes', 0, '添加成功！');
        } else {
            webApi(0, 'no', 0, '添加失败！');
        }

    }

    /**
     * 删除
     */
    public function delStore()
    {
        //接受数据
        [$id, $code] = [input('id'), input('code')];

        //进行判断
        if ($id == null || $code == null) {
            webApi(1, '参数错误');
        }

        // 启动事务
        Db::startTrans();
        try {
            // 执行删除 删除多个数据
            Db::table($this->db.'.vip_store')->delete($id);// 门店删除
            Db::table($this->db.'.vip_rfm_days')->where('store_code', $code)->delete();// rfm周期删除
            Db::table($this->db.'.vip_rfm_r')->where('store_code', $code)->delete();// R未消费天数删除
            Db::table($this->db.'.vip_rfm_f')->where('store_code', $code)->delete();// F消费的次数删除
            Db::table($this->db.'.vip_rfm_m')->where('store_code', $code)->delete();// M消费总金额删除
            Db::table($this->db.'.vip_rfm_i')->where('store_code', $code)->delete();// I转介绍人数删除
            Db::table($this->db.'.vip_rfm_n')->where('store_code', $code)->delete();// N消费的件数删除
            Db::table($this->db.'.vip_rfm_p')->where('store_code', $code)->delete();// P消费客单价删除
            Db::table($this->db.'.vip_rfm_a')->where('store_code', $code)->delete();// A消费件单价删除
            Db::table($this->db.'.vip_rfm_j')->where('store_code', $code)->delete();// J消费连带率删除
            Db::table($this->db.'.vip_rfm_c')->where('store_code', $code)->delete();// C会员年消费删除
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            dump($e);
            $res = false;
        }

        //清除变量
        unset($id);

        //返回数据进行判断
        if ($res) {
            webApi(0, 'yes', 0, '删除成功!');
        } else {
            webApi(0, 'no', 0, '删除失败!');
        }
    }

    /**
     * 批量删除
     */
    public function delStoreAll()
    {
        [$ids, $stores] = [input('ids'), input('store_codes')];
        //接受数据并进行判断
        if ($ids == '' || $stores == '') {
            webApi(1, '参数错误');
        }
        
        //格式化数据
        $idss = trim($ids, ',');
        $storess = trim($stores, ',');

        // 启动事务
        Db::startTrans();
        try {
            // 执行删除 删除多个数据
            Db::table($this->db.'.vip_store')->where('id', 'in', $idss)->delete();
            Db::table($this->db.'.vip_rfm_days')->where('store_code', 'in', $storess)->delete();// rfm周期删除
            Db::table($this->db.'.vip_rfm_r')->where('store_code', 'in', $storess)->delete();// R未消费天数删除
            Db::table($this->db.'.vip_rfm_f')->where('store_code', 'in', $storess)->delete();// F消费的次数删除
            Db::table($this->db.'.vip_rfm_m')->where('store_code', 'in', $storess)->delete();// M消费总金额删除
            Db::table($this->db.'.vip_rfm_i')->where('store_code', 'in', $storess)->delete();// I转介绍人数删除
            Db::table($this->db.'.vip_rfm_n')->where('store_code', 'in', $storess)->delete();// N消费的件数删除
            Db::table($this->db.'.vip_rfm_p')->where('store_code', 'in', $storess)->delete();// P消费客单价删除
            Db::table($this->db.'.vip_rfm_a')->where('store_code', 'in', $storess)->delete();// A消费件单价删除
            Db::table($this->db.'.vip_rfm_j')->where('store_code', 'in', $storess)->delete();// J消费连带率删除
            Db::table($this->db.'.vip_rfm_c')->where('store_code', 'in', $storess)->delete();// C会员年消费删除
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            dump($e);
            $res = false;
        }

        //清除变量
        unset($ids, $stores, $idss, $storess);

        //返回数据进行判断
        if ($res) {
            webApi(0, 'yes', 0, '删除成功!');
        } else {
            webApi(0, 'no', 0, '删除失败!');
        }
    }

    /**
     * 修改
     */
    public function editStore()
    {
        //接受数据并进行判断
        $id = input('id') ?? null;

        if ($id == null) {
            webApi(1, '参数错误');
        }

        //接受数据
        $data = [
            'org_code' => input('splb'),
            'contacts' => input('contacts'),
            'phone' => input('phone'),
            'name' => input('name'),
            'address' => input('address'),
            'full_address' => input('full_address'),
            'id' => $id
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

        //执行修改
        $res = Db::table($this->db.'.vip_store')->update($data);

        //清除变量
        unset($data,$id,$d);

        //返回数据进行判断
        if ($res) {
            webApi(0, 'yes', 0, '修改成功!');
        } else {
            webApi(0, 'no', 0, '修改失败!');
        }
    }

    /**
     * 组织机构
     */
    public function categoryList()
    {
        //查询登入的管理组织机构和子机构
        // $org = EW::orgChild();
        // // //判断机构
        // if ($org == '') {
        //     $data = [];
        // } else {
        //查询当前登入人
        $operate = Db::table($this->db.'.vip_staff')->where('code', session('info.staff'))->find();
        //所属机构限制
        if ($operate['id'] == 1) {
            $where = true;
        } else {//判断是否是管理
            if ($operate['role'] == 0) {//管理查询管理机构
                $org = $operate['admin_org_code'].','.$operate['org_code'];
                $where[] = ['code', 'in', $org];
            } else { //员工查询所属机构
                $org = $operate['org_code'];
                $where[] = ['code', 'in', $org];
            }
        }
        //查询数据
        $data = Db::table($this->db.'.vip_org')->field('code as id, pid as parentId, name as title')->where($where)->select();
        //格式化数据
        $data = getTree($data, 'id', 'parentId');
        // }
        //返回数据
        webApi(0, '', 0, $data);
    }

}