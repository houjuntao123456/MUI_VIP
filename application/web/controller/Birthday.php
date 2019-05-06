<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
use app\web\controller\Common;


/**
 * Author lhp
 * Date 2018/01/15
 * Description 会员权益 --- 生日折扣
 */
class Birthday extends Common
{
    /**
     * 生日折扣列表
     */
    public function index()
    {
         //分页信息
         [$page, $limit, $lookup] = [input('page'), input('limit'), input('lookup')];
        
         //统计数量
         $count = Db::table($this->db.'.vip_vipbirthday')->count();

        // if (empty(session('info.store'))) {
        //     $storeWhere = true;
        // } else {
        //     $storeWhere[] = ['vp.ERP', '=', session('info.store')['ERP']];
        // }

        //模糊查询
        if ($lookup) {
            $where[] = ['vp.name|vg.username', 'like', '%'.$lookup.'%'];
        } else {
            $where = true;
        }
        
        //查询
        $data = Db::table($this->db.'.vip_vipbirthday')
                ->alias('v')
                ->leftJoin($this->db.'.vip_viplevel vg', 'vg.code = v.level_code')
                ->leftJoin($this->db.'.vip_store vp', 'vp.code = v.store_code')
                ->field('vg.username vgname, vp.name vpname, v.id, v.store_code, v.level_code, v.discount, v.prev_days, v.after_days, v.project, v.type')
                // ->where($storeWhere)
                ->where($where)
                ->order('v.id','desc')
                ->page($page,$limit)
                ->select();

        unset($page, $limit, $lookup, $where);

        //修改格式
        foreach($data as $k=>$v){
            $data[$k]['exclusive_days'] = '前'.$v['prev_days']. ' 天 至 后 ' .$v['after_days'].'天';
        }
        webApi(0,'',$count,$data);
    }


    /**
     * 添加编辑消费项目相关商品列表
     */
    public function getGoods()
    {
        $token = input('access_token');
        $id = input('code') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }

        // $where[] = ['status', '=', 1];
        $path = input('path') ?? null;
        if ($path == null) {
            webApi(1, '参数错误');
        }

        if (input('wh') != '') {
            $where[] = ['g.bar_code|g.frenum|g.name|g.color|g.size|g.price', 'like', '%'.input('wh').'%'];
        }

        // 查询所有要被查询商品的分类
        $types = Db::table($this->db.'.vip_goods_classify')->where('path', 'like', $path.$id.',%')->select();
        unset($path);
        if ($types) {
            foreach ($types as $v) {
                $id .= ','.$v['code'];
            }
        }
        unset($types);
        $where[] = ['classify_code', 'in', $id];
        
        $goods = Db::table($this->db.'.vip_goods')
                // ->leftJoin($this->db.'.vip_goods_unit u', 'u.code = g.unit')
                ->field('code,name,frenum,bar_code,classify_code,color,size,unit,price,code') //,u.name unit_name
                ->where($where)
                ->select();
        unset($where);
        if ($goods) {
            foreach ($goods as $k=>$v) {
                $goods[$k]['price'] = number_format($v['price'], 2, '.', '');
            }
            $check = cache('birTx_'.$token);
            unset($token);
            if ($check != false) {
                switch ($check['type']) {
                    case 0:
                        $cpath = Db::table($this->db.'.vip_goods_classify')->field('code,path')->where('code', input('code'))->find();
                        $ctype = Db::table($this->db.'.vip_goods_classify')->field('code')->where('path', 'like', $cpath['path'].$cpath['code'].',%')->select();
                        unset($cpath);
                        if ($ctype) {
                            $tcode = array_column($ctype, 'code');
                            array_push($tcode, $check['goods']);
                            foreach ($goods as $k=>$v) {
                                if (in_array($v['classify_code'], $tcode)) {
                                    $goods[$k]['LAY_CHECKED'] = true;
                                }
                            }
                        }
                        unset($ctype);
                    break;
                    case 1:
                        if ($check && !empty($check['goods'])) {
                            $checks = explode(',', $check['goods']);
                        } else {
                            $checks = [];
                        }
                        if ($checks) {
                            foreach ($goods as $k=>$v) {
                                if (in_array($v['code'], $checks)) {
                                    $goods[$k]['LAY_CHECKED'] = true;
                                }
                            }
                        }
                        unset($checks);
                    break;
                }
            }
        }
        
        webApi(0, '', 0, $goods);
    }

    /**
     * 给选择的商品加入缓存
     */
    public function cacheGoods()
    {
        $token = input('access_token');
        $goods = input('goods') ?? null;
        $type = input('type') ?? null;
        // if ($goods == null || $type == null) {
        //     webApi(1, '参数错误');
        // }
        $cache = ['type' => intVal($type), 'goods' => trim($goods, ',')];
        unset($goods);
        $res = cache('birTx_'.$token, $cache, 3600);
        unset($token, $cache);
        if ($res) {
            webApi(0, 'yes', 0, '成功');
        } else {
            webApi(0, 'no', 0, '失败');
        }
    }

    /**
     * 将所属门店加入缓存
     */
    public function getcacheAdd() 
    {
        $token = input('access_token');
        if (cache('?birthday_'.$token) == false) {
            $data = [];
        } else {
            $data = cache('birthday_'.$token);
            foreach ($data as $k=>$v) {
                if (input('ssmd_birthday_name') == $v['id']) {
                    webApi(0, 'no', 0, '该门店已存在！');
                } 
            }
        }
        
        array_push($data, ['name' => input('name'), 'id' => input('ssmd_birthday_name')]);

        $res = cache('birthday_'.$token, $data, 3600);
        unset($token, $data);
        if ($res) {
            webApi(0, 'yes', 0, '添加成功');
        } else {
            webApi(0, 'no', 0, '添加失败');
        }
    }

    /**
     * 查询所属店面缓存信息
     */
    public function getBirthdaycache() 
    {
        $token = input('access_token');

        $res = cache('birthday_'.$token);
        if ($res) {
            foreach($res as $k=>$v) {
                $res[$k]['type'] = $v['name'];
            }
        }
        webApi(0, '', 0, $res);
    }

    /**
     * 将所属店面在缓存中删除
     */
    public function getcacheDel() 
    {
        $token = input('access_token');
        $data = cache('birthday_'.$token);
        if ($data != false) {
            foreach ($data as $k=>$v) {
                if (input('id') == $v['id']) {
                    unset($data[$k]);
                } 
            }
        }
        $res = cache('birthday_'.$token, $data, 3600);
        unset($token, $data);
        if ($res) {
            webApi(0, 'yes', 0, '删除成功');
        } else {
            webApi(0, 'no', 0, '删除失败');
        }
    }

     /**
     * 给选择的商品加入缓存
     */
    public function bircacheGoods()
    {
        $token = input('access_token');
        $goods = input('goods') ?? null;
        $type = input('type') ?? null;
        // if ($goods == null || $type == null) {
        //     webApi(1, '参数错误');
        // }
        $cache = ['type' => intVal($type), 'goods' => trim($goods, ',')];
        unset($goods);
        $res = cache('birTx_'.$token, $cache, 3600);
        unset($token, $cache);
        if ($res) {
            webApi(0, 'yes', 0, '成功');
        } else {
            webApi(0, 'no', 0, '失败');
        }
    }

    /**
     * 添加功能
     */
    public function add()
    {
        $token = input('access_token');
        // if (cache('?actTx_'.$token) == false) {
        //     webApi(0, 'error', 0, '请选择消费项目');
        // }

        $up = [
            'level_code' => input('levelname'),//会员级别
            'project' => cache('birTx_'.$token)['goods'],//消费项目
            'type' => cache('birTx_'.$token)['type'],// 消费项目属性 分类还是产品
            'discount' => input('birthday_discounts'), //专属折扣
            // 'integral_multiple' => input('birthday_multiple'),//积分倍数
            'prev_days' => input('time_start'),//前多少天
            'after_days' => input('time_end'),//后多少天
        ];

        $data = [];
        $cache = cache('birthday_'.$token);
        if ($cache == false) {
            webApi(0, 'error', 0, '请选择所属门店');
        }
        
        if ($cache) {
            foreach ($cache as $k=>$v) {
                $data[$k] = $up;
                $data[$k]['store_code'] = $v['id'];
            }
        }
        unset($cache, $token);
        //验证场景
        // $result = $this->validate($up,'app\web\validate\v1\ActivityValidate.activity');
        // if(true !== $result  ){
        //     webApi(0,'error',0,$result);
        // }

        unset($up);
        //添加信息
        $res = Db::table($this->db.'.vip_vipbirthday')->insertAll($data);
        unset($data);
        //提示信息
        if($res){
            webApi(0,'yes',0,'添加成功!');
        }else{
            webApi(0,'no',0,'添加失败!');
        }
    }

    /**
     * 工具条编辑功能
     */
    public function edit()
    {
        $token = input('access_token');
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }

        $data = [
            'id' => $id,
            'level_code' => input('levelname'),//会员级别
            'store_code' => input('storename'),//所属店面
            'project' => cache('birTx_'.$token)['goods'],//消费项目
            'type' => cache('birTx_'.$token)['type'],// 消费项目属性 分类还是产品
            'discount' => input('birthday_discounts'), //专属折扣
            // 'integral_multiple' => input('birthday_multiple'),//积分倍数
            'prev_days' => input('time_start'),//前多少天
            'after_days' => input('time_end'),//后多少天
        ];

        //修改数据库信息
        $res = Db::table($this->db.'.vip_vipbirthday')->update($data);
        unset($data);

        //提示信息
        if($res){
            cache('birTx_'.$token, null);
            webApi(0, 'yes', 0, '修改信息成功!');
        }else if ($res === false){
            webApi(0, 'no', 0, '修改信息失败!');
        } else {
            webApi(0, 'no', 0, '未修改!');
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
        $res = Db::table($this->db.'.vip_vipbirthday')->where('id', 'in', $ids)->delete();
        if ($res) {
            webApi(0, 'yes', 0, '删除成功');
        } else {
            webApi(0, 'no', 0, '删除失败');
        }
    }

    /**
     * 工具条删除功能
     */
    public function birthdaydel()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }

        $res = Db::table($this->db.'.vip_vipbirthday')->delete($id);

        if ($res) {
            webApi(0, 'yes', 0, '删除成功!');
        } else {
            webApi(0, 'no', 0, '删除失败!');
        }
    }

    /**
     * 清空缓存
     */
    public function BirsetCacheNull()
    {  
        $token = input('access_token');
        cache('birTx_'.$token, null);
        cache('birthday_'.$token, null);
        webApi(0);
    }

}