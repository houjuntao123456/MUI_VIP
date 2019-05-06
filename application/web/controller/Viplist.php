<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
use app\web\controller\Common;
use app\web\service\ErpWhere as EW;

/**
 * Author lhp
 * Date 2018/12/28
 * Description 会员列表
 */
class Viplist extends Common
{
    public function index() 
    {
        [$page, $limit] = [input('page'), input('limit')];

        $ext = Db::table($this->db.'.vip_viplabel')->field('name')->select();
        if (!empty($ext)) {
            $extend = array_column($ext, 'name');
        } else {
            $extend = [];
        }

        $where = '1=1';
        if (input('screen') == 'true') {
            if (input('sizer') != 'false') {
                $sxq = Db::table($this->db.'.vip_filter')->find(input('sizer'));
                $sx = json_decode($sxq['content'], true);
            } else {
                $sx = cache('tj_'.input('access_token'));
            }
            
            if (!empty($sx)) {
                $float = ['acs', 'total_consumption', 'stored_value', 'residual_value', 'total_value', 'total_frozen_value'];
                foreach ($sx as $v) {
                    if (in_array($v['tit'], $extend)) {
                        $where .= ' and json_search(json_extract(IF(IFNULL(extension, "[]") = "", "[]", extension), \'$."'.$v['tit'].'"\'), "all", "%'.$v['val'].'%")  is not null';
                    } else {
                        if ($v['sym'] == 'LIKE') {
                            $where .= ' and '.$v['tit'].' '.$v['sym'].' "%'.$v['val'].'%"';
                        } else {
                            if (in_array($v['tit'], $float)) {
                                $where .= ' and '.$v['tit'].$v['sym'].' '.$v['val'];
                            } else {
                                $where .= ' and '.$v['tit'].$v['sym'].' "'.$v['val'].'"';
                            }
                        }
                    }
                }
            }
        }

        if (session('info.store') == '') {
            $stores = EW::getStore(EW::orgChild());
        } else {
            $stores = session('info.store');
        }

        $data = Db::table($this->db.'.view_viplist')
            ->field('id, code, username, sex, identity, nation, phone, store_code, vsname, level_code, vlname, reg_store, vrname, calendar, birthday, consultant_code, vgname, area, address, activate_code, activate_name, introducer_code, adult_name, adult_code, acs, payment_method, consumption_times, consumption_number, exchange_number, total_consumption, first_time, final_purchases, remarks, telephone, initiation, qq, weixin, img, date_registration, residual_integral, already_used_integral, offset_integral, gift_integral, total_integral, stored_value, residual_value, total_value, total_frozen_value, days, years, r_days, extension, img')
            ->where('store_code', 'in', $stores)
            ->where($where)
            ->page($page, $limit)
            ->select();
            

            foreach ($data as $k=>$v) {
                $data[$k]['extension'] = json_decode($v['extension'], true);
            }
    
            $ext = Db::table($this->db.'.vip_viplabel')->field('name')->where('status', 1)->select();
            foreach ($ext as $k=>$v) {
                if (!empty($v['name'])) {
                    array_push($ext, $v['name']);
                    unset($ext[$k]);
                } else {
                    unset($ext[$k]);
                }
            }
            sort($ext);
            for ($i = 0; $i < count($ext); $i++) {
                foreach ($data as $k=>$v) {
                    if ($v['extension'] && !empty($v['extension'][$ext[$i]])) {
                        if (is_array($v['extension'][$ext[$i]])) {
                            $data[$k][$ext[$i]] = implode(' ', $v['extension'][$ext[$i]]);
                        } else {
                            $data[$k][$ext[$i]] = $v['extension'][$ext[$i]];
                        }
                    } else {
                        $data[$k][$ext[$i]] = '';
                    }
                }
            }
        $count = Db::table($this->db. '.view_viplist')->count();

        webApi(0, '', $count, $data);
    }

    /**
     * 列表追加扩展信息
     */
    public function ext()  
    {
        $ext = Db::table($this->db.'.vip_viplabel')->field('name')->where('status', 1)->select();
        if (!empty($ext)) {
            $ext = array_column($ext, 'name');
        } else {
            $ext = [];
        }
        webApi(0, '', 0, $ext);
    }

    /**
     * 形象顾问下拉选
     */
    public function staff() 
    {
        $data = Db::table($this->db.'.vip_staff')->field('id, code, name')->select();
        webApi(0, '', 0, $data);
    }

    /**
     * 门店下拉选
     */
    public function store()
    {
        $stores = EW::getStore(EW::orgChild());
        $data = Db::table($this->db.'.vip_store')->where('code', 'in', $stores)->field('code, name')->select();
        webApi(0, '', 0, $data);
    }

    /**
     * 清除缓存
     */
    public function delCache() {
        cache('vip_extend_edit'.input('access_token'), null);
        cache('tj_'.input('access_token'), null);
        webApi(0);
    }

    /**
     * 会员转移
     */
    public function transfer()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }
        $ids = explode(',', trim($id, ','));

        if (input('transfer_xxgw') != '') {
            $data['consultant_code'] = input('transfer_xxgw');
        }

        if (input('transfer_ssmd') != '') {
            $data['store_code'] = input('transfer_ssmd');
        }
        
        $pre_transfer = [];
        for ($i=0; $i<count($ids); $i++) {
            $pre_transfer[$i] = Db::table($this->db. '.vip_viplist')
                            ->alias('v')
                            ->leftJoin($this->db.'.vip_staff vg', 'vg.code = v.consultant_code')
                            ->leftJoin($this->db.'.vip_store vs', 'vs.code = v.store_code')
                            ->field('vg.name vgname, vs.name vsname, v.code, v.username, v.consultant_code, v.store_code')
                            ->where('v.id', $ids[$i])
                            ->find();
        }
        $ope = session('info.staff');
        $ope_name = Db::table($this->db.'.vip_staff')->where('code', $ope)->field('name')->find();
        $trans = [];
        foreach ($pre_transfer as $k=>$v) {
            $trans[$k]['pre_transfer_store_code'] = $v['store_code'] == null ? '' : $v['store_code']; // 转移前门店
            $trans[$k]['pre_transfer_store_name'] = $v['vsname'] == null ? '' : $v['vsname']; // 转移前门店
            $trans[$k]['pre_transfer_code'] = $v['consultant_code'] == null ? '' : $v['consultant_code']; //转移前形象顾问
            $trans[$k]['pre_transfer_name'] = $v['vgname'] == null ? '' : $v['vgname']; //转移前形象顾问
            $trans[$k]['username'] = $v['username']; // 会员姓名
            $trans[$k]['vip_code'] = $v['username']; // 会员卡号
            if (input('transfer_xxgw') != '') {
                $trans[$k]['post_transfer_code'] = input('transfer_xxgw'); //转移后形象顾问
                $trans[$k]['post_transfer_name'] = input('staff_name'); //转移后形象顾问
            }
            if (input('transfer_ssmd') != '') {
                $trans[$k]['post_transfer_store_code'] = input('transfer_ssmd'); //转移后门店
                $trans[$k]['post_transfer_store_name'] = input('store_name'); //转移后门店
            }
            $trans[$k]['transfer_time'] = time();
            $trans[$k]['operate_code'] = $ope == null ? '' : $ope;
            $trans[$k]['operate_name'] = $ope_name['name'] == null ? '' : $ope_name['name'];
        }
        unset($pre_transfer);
        // 启动事务
        Db::startTrans();
        try {
            for ($i=0; $i<count($ids); $i++) {
                Db::table($this->db.'.vip_viplist')->where('id', $ids[$i])->update($data);
            }
            unset($data);
            Db::table($this->db.'.vip_transfer_record')->insertAll($trans);
            unset($trans);
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $res = false;
        }
        if ($res) {
            webApi(0, 'yes', 0, '转移成功');
        } else {
            webApi(0, 'no', 0, '转移失败');
        }
    } 

    /**
     * 查询扩展信息表
     */
    public function editExtends()
    {   
        $data['label'] = Db::table($this->db.'.vip_viplabel')->where('status',1)->select();
        if (empty($data['label'])) {
            $data['label'] = [];
        }
        $goods = Db::table($this->db.'.vip_viplist')->where('id', input('id'))->field('extension')->find();
        if ($goods['extension'] != '') {
            $data['extension'] = json_decode($goods['extension'], true);
            cache('vip_extend_edit'.input('access_token'), $data['extension'], 3600);
        } else {
            $data['extension'] = '';
        }
        webApi(0, '', 0, $data);

    }

     /**
     * 点击扩展类型的内容  加入缓存
     */
    public function vipaddInfoCache()
    {   
        // 获得缓存
        $data = cache('vip_extend_edit'.input('access_token'));
        if (!$data) $data = [];
        
        switch (input('type')) {
            case 'string':
                $data[input('key')] = input('val');
            break;
            case 'array':
                // 判断该扩展型标签是否已经选择内容
                if (array_key_exists(input('key'), $data)) {
                    // 判断是选中还是取消
                    if (in_array(input('val'), $data[input('key')])) { // 取消
                        unset($data[input('key')][array_search(input('val'), $data[input('key')])]);
                        sort($data[input('key')]);
                    } else { // 选中
                        array_push($data[input('key')], input('val'));
                    }
                } else {
                    $data[input('key')][0] = input('val');
                }
            break;
        }
        cache('vip_extend_edit'.input('access_token'), $data, 3600);
        webApi(0);
    }

    /**
     * 扩展标签 
     * 1. 获得扩展类型的详细内容插入到数据库
     */
    public function vipextendsInfo()
    {
        // 获得扩展标签的详细内容
        $data = [
            'name' => trim(input('name')),
            'info' => trim(input('info')),
            'label_code' => input('code') 
        ];
        if ($data == '') {
            webApi(1, '参数错误');
        }

        $result = Db::table($this->db.'.vip_viplabel_info')->where('name',$data['name'])->where('info',$data['info'])->find();    
        if ($result) {
            unset($result); 
            webApi(0,'error',0,'标签字段已经存在');
        } else {
            //插入到数据库
            $res = Db::table($this->db.'.vip_viplabel_info')->insert($data);
        }        
        unset($data);
        if($res){
            webApi(0,'yes',0,'添加成功');
        }else{
            webApi(0,'NO',0,'添加失败');
        }
    }

    /**
     * 扩展类型 添加内容的展示
     */
    public function vipinfoList()
    {
        $data = Db::table($this->db.'.vip_viplabel_info')->field('id, info as name,name as type')->where('name', input('name'))->select();
        $ext = cache('vip_extend_edit'.input('access_token')) ?? [];
        if (!empty($ext) && array_key_exists(input('name'), $ext)) {
            foreach ($data as $k=>$v) {
                $data[$k]['on'] = false;
                for ($i=0; $i < count($ext[input('name')]); $i++) {
                    if ($ext[input('name')][$i] == $v['name']) {
                        $data[$k]['on'] = true;
                    }
                }
            }
            unset($ext);
        }
        webApi(0, '', 0, $data);
    }

    /**
     * 扩展类型 添加内容的删除
     */
    public function vipdelInfo()
    {
        // 获得删除的内容的id
        $id = input('id');
        if (input('id') == '') {
            webApi(1, '参数错误');
        }
        $res = Db::table($this->db.'.vip_viplabel_info')->where('id',$id)->delete();
        unset($id);
        if($res){
            webApi(0,'yes',0,'删除成功');
        }else{
            webApi(0,'NO',0,'删除失败');
        }
    }

    /**
     * 编辑扩展信息提交
     */
    public function vipeditSubmit()
    {
        $id = trim(input('id'), ',');
        if (input('id') == '') {
            webApi(1, '参数错误');
        }
        $data = cache('vip_extend_edit'.input('access_token')) ?? [];
        if (!empty($data)) {
            $str = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $str = '';
        }

        $res = Db::table($this->db.'.vip_viplist')->where('id', 'in', $id)->setField('extension', $str);
        if($res){
            cache('vip_extend_edit'.input('access_token'), null);   // 清理缓存
            webApi(0,'yes',0,'编辑成功');
        }else{
            webApi(0,'NO',0,'编辑失败');
        }

    }

    /**
     * 查看会员时的扩展
     */
    public function getlistkzval()
    {
        $id = input('id') ?? null;
        $res = Db::table($this->db.'.vip_viplist')->where('code', $id)->field('code,extension')->find();
        $extion = [];
        if ($res['extension'] != '') {
            $ext = json_decode($res['extension'], true);
            if (!empty($ext) && is_array($ext)) {
                foreach ($ext as $k=>$v) {
                    if ( is_array($v) ) {
                        $ext[$k] = implode(',', $v);
                    }
                }
                $i = 0;
                foreach ($ext as $k=>$v) {
                    $extion[$i]['key'] = $k;
                    $extion[$i]['val'] = $v;
                    $i++;
                }
            }
            unset($ext);
        }
        unset($res);
        webApi(0, '', 0, $extion);
    }

    /**
     * 查看中的RFM
     */
    public function viprfm()
    {
        $card = input('card') ?? null;
        if ($card == null) {
            webApi(1, '参数错误');
        }
        $data = Db::table($this->db.'.vip_viplist')
                ->field('code,final_purchases')
                ->where('code' ,$card)
                ->find();

        $data['r_days'] = round((time()-$data['final_purchases'])/86400);
        
        $rfmTime = Db::table($this->db.'.vip_rfm_days')->find();

        $data['introduction'] = Db::table($this->db.'.vip_introducer')->where('lnt_code', $card)->count();

        $data['i_introduction'] = Db::table($this->db.'.vip_introducer')->where('lnttime', '>=', time() - (86400 * $rfmTime['i_consumption']))->where('lnt_code', $card)->count(); // I转介绍数
        $data['f_frequency'] = Db::table($this->db.'.vip_goods_order')->where('create_time', '>=', time() - (86400 * $rfmTime['f_consumption']))->where('vip_code', $card)->count(); // F消费次数

        if (!empty(Db::table($this->db.'.vip_goods_order')->field('real_pay')->where('vip_code', $card)->find())) { //M金额
            $data['m_money'] = number_format(Db::table($this->db.'.vip_goods_order')->field('sum(real_pay) as pay')->where('create_time', '>=', time() - (86400 * $rfmTime['m_consumption']))->where('vip_code', $card)->find()['pay'], 2, '.', ''); // M金额
        } else {
            $data['m_money'] = '0.00';
        }

        if (!empty(Db::table($this->db.'.vip_goods_order')->field('number')->where('vip_code', $card)->find())) { //N件数
            $data['n_number'] = Db::table($this->db.'.vip_goods_order')->field('sum(number) as num')->where('create_time', '>=', time() - (86400 * $rfmTime['n_consumption']))->where('vip_code', $card)->find()['num']; 
        } else {
            $data['n_number'] = 0;
        }
        
        //R类型和R得分
        if (!empty(Db::table($this->db.'.vip_rfm_r')->where('r_intervalone', '<=', $data['r_days'])->where('r_intervaltwo', '>', $data['r_days'])->find())) {
            $data['r_type'] = Db::table($this->db.'.vip_rfm_r')->where('r_intervalone', '<=', $data['r_days'])->where('r_intervaltwo', '>', $data['r_days'])->find()['r_type'];
            $data['r_score'] = Db::table($this->db.'.vip_rfm_r')->where('r_intervalone', '<=', $data['r_days'])->where('r_intervaltwo', '>', $data['r_days'])->find()['r_score'];
        } else {
            $data['r_type'] = '';
            $data['r_score'] = 0;
        }
        
        //I类型 I得分
        if (!empty(Db::table($this->db.'.vip_rfm_i')->where('i_intervalone', '<=', $data['i_introduction'])->where('i_intervaltwo', '>', $data['i_introduction'])->find())) { 
            $data['i_type'] = Db::table($this->db.'.vip_rfm_i')->where('i_intervalone', '<=', $data['i_introduction'])->where('i_intervaltwo', '>', $data['i_introduction'])->find()['i_type'];
            $data['i_score'] = Db::table($this->db.'.vip_rfm_i')->where('i_intervalone', '<=', $data['i_introduction'])->where('i_intervaltwo', '>', $data['i_introduction'])->find()['i_score'];
        } else {
            $data['i_type'] = '';
            $data['i_score'] = 0;
        }

        //F类型 F得分
        if (!empty(Db::table($this->db.'.vip_rfm_f')->where('f_intervalone', '<=', $data['f_frequency'])->where('f_intervaltwo', '>', $data['f_frequency'])->find())) { 
            $data['f_type'] = Db::table($this->db.'.vip_rfm_f')->where('f_intervalone', '<=', $data['f_frequency'])->where('f_intervaltwo', '>', $data['f_frequency'])->find()['f_type'];
            $data['f_score'] = Db::table($this->db.'.vip_rfm_f')->where('f_intervalone', '<=', $data['f_frequency'])->where('f_intervaltwo', '>', $data['f_frequency'])->find()['f_score'];
        } else {
            $data['f_type'] = '';
            $data['f_score'] = 0;
        }

        //M类型 M得分
        if (!empty(Db::table($this->db.'.vip_rfm_m')->where('m_intervalone', '<=', $data['m_money'])->where('m_intervaltwo', '>', $data['m_money'])->find())) { 
            $data['m_type'] = Db::table($this->db.'.vip_rfm_m')->where('m_intervalone', '<=', $data['m_money'])->where('m_intervaltwo', '>', $data['m_money'])->find()['m_type'];
            $data['m_score'] = Db::table($this->db.'.vip_rfm_m')->where('m_intervalone', '<=', $data['m_money'])->where('m_intervaltwo', '>', $data['m_money'])->find()['m_score'];
        } else {
            $data['m_type'] = '';
            $data['m_score'] = 0;
        }

        $data['rfm'] = $data['m_score'] + $data['f_score'] + $data['r_score'];

        //N类型 N得分
        if (!empty(Db::table($this->db.'.vip_rfm_n')->where('n_intervalone', '<=', $data['n_number'])->where('n_intervaltwo', '>', $data['n_number'])->find())) { 
            $data['n_type'] = Db::table($this->db.'.vip_rfm_n')->where('n_intervalone', '<=', $data['n_number'])->where('n_intervaltwo', '>', $data['n_number'])->find()['n_type'];
            $data['n_score'] = Db::table($this->db.'.vip_rfm_n')->where('n_intervalone', '<=', $data['n_number'])->where('n_intervaltwo', '>', $data['n_number'])->find()['n_score'];
        } else {
            $data['n_type'] = '';
            $data['n_score'] = 0;
        }
    
        if ($data['f_frequency'] != 0) { // P客单价
            $data['p_univalent'] = number_format($data['m_money'] / $data['f_frequency'], 2, '.', '');  
        } else {
            $data['p_univalent'] = '0.00';
        }

        if ($data['n_number'] != 0) {  // A件单价
            $data['a_univalent'] = number_format($data['m_money'] / $data['n_number'], 2, '.', ''); 
        } else {
            $data['a_univalent'] = '0.00';
        }

        if ($data['f_frequency'] != 0) {  // J连带率
            $data['j_related_rate'] = number_format($data['n_number'] / $data['f_frequency'], 2); 
        } else {
            $data['j_related_rate'] = '0';
        }

        if (!empty(Db::table($this->db.'.vip_goods_order')->field('real_pay')->where('create_time', '>=', time() - ($rfmTime['c_consumption'] * 86400))->where('vip_code', $card)->find())) { //C年消费
            $data['c_consumption'] = number_format(Db::table($this->db.'.vip_goods_order')->field('sum(real_pay) as pay')->where('create_time', '>=', time() - ($rfmTime['c_consumption'] * 86400))->where('vip_code', $card)->find()['pay'], 2, '.', ''); // M金额
        } else {
            $data['c_consumption'] = '0.00';
        }

        // C类型 C得分
        if (!empty(Db::table($this->db.'.vip_rfm_c')->where('c_intervalone', '<=', $data['c_consumption'])->where('c_intervaltwo', '>', $data['c_consumption'])->find())) { 
            $data['c_type'] = Db::table($this->db.'.vip_rfm_c')->where('c_intervalone', '<=', $data['c_consumption'])->where('c_intervaltwo', '>', $data['c_consumption'])->find()['c_type'];
            $data['c_score'] = Db::table($this->db.'.vip_rfm_c')->where('c_intervalone', '<=', $data['c_consumption'])->where('c_intervaltwo', '>', $data['c_consumption'])->find()['c_score'];
        } else {
            $data['c_type'] = '';
            $data['c_score'] = 0;
        }

        //P类型 P得分
        if (!empty(Db::table($this->db.'.vip_rfm_p')->where('p_intervalone', '<=', $data['p_univalent'])->where('p_intervaltwo', '>', $data['p_univalent'])->find())) { 
            $data['p_type'] = Db::table($this->db.'.vip_rfm_p')->where('p_intervalone', '<=', $data['p_univalent'])->where('p_intervaltwo', '>', $data['p_univalent'])->find()['p_type'];
            $data['p_score'] = Db::table($this->db.'.vip_rfm_p')->where('p_intervalone', '<=', $data['p_univalent'])->where('p_intervaltwo', '>', $data['p_univalent'])->find()['p_score'];
        } else {
            $data['p_type'] = '';
            $data['p_score'] = 0;
        }

        // A类型 A得分
        if (!empty(Db::table($this->db.'.vip_rfm_a')->where('a_intervalone', '<=', $data['a_univalent'])->where('a_intervaltwo', '>', $data['a_univalent'])->find())) { 
            $data['a_type'] = Db::table($this->db.'.vip_rfm_a')->where('a_intervalone', '<=', $data['a_univalent'])->where('a_intervaltwo', '>', $data['a_univalent'])->find()['a_type'];
            $data['a_score'] = Db::table($this->db.'.vip_rfm_a')->where('a_intervalone', '<=', $data['a_univalent'])->where('a_intervaltwo', '>', $data['a_univalent'])->find()['a_score'];
        } else {
            $data['a_type'] = '';
            $data['a_score'] = 0;
        }

        // J类型 J得分
        if (!empty(Db::table($this->db.'.vip_rfm_j')->where('j_intervalone', '<=', $data['j_related_rate'])->where('j_intervaltwo', '>', $data['j_related_rate'])->find())) { 
            $data['j_type'] = Db::table($this->db.'.vip_rfm_j')->where('j_intervalone', '<=', $data['j_related_rate'])->where('j_intervaltwo', '>', $data['j_related_rate'])->find()['j_type'];
            $data['j_score'] = Db::table($this->db.'.vip_rfm_j')->where('j_intervalone', '<=', $data['j_related_rate'])->where('j_intervaltwo', '>', $data['j_related_rate'])->find()['j_score'];
        } else {
            $data['j_type'] = '';
            $data['j_score'] = 0;
        }

        unset($card,$rfmTime);
        webApi(0, '', 0, $data);
    }

    /**
     * 循环高级筛选中的下拉框信息
     */
    public function screening()
    {
        $data = array(
            ['value' => 'code', 'name' => '会员卡号'],
            ['value' => 'username', 'name' => '会员姓名'],
            ['value' => 'sex', 'name' => '性别'],
            ['value' => 'level_code', 'name' => '会员级别'],
            ['value' => 'phone', 'name' => '手机号码'],
            ['value' => 'telephone', 'name' => '固定电话'],
            ['value' => 'identity', 'name' => '身份证号'],
            ['value' => 'nation', 'name' => '民族'],
            ['value' => 'birthday', 'name' => '会员生日'],
            ['value' => 'calendar', 'name' => '公历/农历'],
            ['value' => 'store_code', 'name' => '所属门店'],
            ['value' => 'reg_store', 'name' => '登记店面'],
            ['value' => 'consultant_code', 'name' => '形象顾问'],
            ['value' => 'activate_code', 'name' => '开卡人'],
            ['value' => 'introducer_code', 'name' => '介绍人卡号'],
            ['value' => 'introducer_name', 'name' => '介绍人'],
            ['value' => 'adult_code', 'name' => '提成人'],
            ['value' => 'acs', 'name' => '入会费'],
            ['value' => 'initiation', 'name' => '入会方式'],
            ['value' => 'qq', 'name' => 'QQ'],
            ['value' => 'weixin', 'name' => '微信'],
            ['value' => 'area', 'name' => '所属地区'],
            ['value' => 'address', 'name' => '详细地址'],
            ['value' => 'payment_method', 'name' => '支付方式'],
            ['value' => 'consumption_times', 'name' => '消费次数'],
            ['value' => 'consumption_number', 'name' => '消费件数'],
            ['value' => 'exchange_number', 'name' => '兑换次数'],
            ['value' => 'total_consumption', 'name' => '总消费额'],
            ['value' => 'first_time', 'name' => '首次购物时间'],
            ['value' => 'final_purchases', 'name' => '最后购物时间'],
            ['value' => 'days', 'name' => '消费天数'],
            ['value' => 'years', 'name' => '消费年数'],
            ['value' => 'r_days', 'name' => '未消费天数'],
            ['value' => 'residual_integral', 'name' => '剩余总积分'],
            ['value' => 'already_used_integral', 'name' => '已用积分'],
            ['value' => 'offset_integral', 'name' => '剩余抵现积分'],
            ['value' => 'gift_integral', 'name' => '剩余礼品积分'],
            ['value' => 'total_integral', 'name' => '总积分'],
            ['value' => 'stored_value', 'name' => '已用储值'],
            ['value' => 'residual_value', 'name' => '剩余储值'],
            ['value' => 'total_value', 'name' => '总储值'],
            ['value' => 'total_frozen_value', 'name' => '总冻结储值'],
        );
        $res = Db::table($this->db.'.vip_viplabel')->field('code, name')->select();
        if ($res) {
            foreach($res as $k=>$v){
                array_push($data,["value"=>$v['code'],"name"=>$v['name']]);
            }
        }
        webApi(0, '', 0, $data);
    }

/**
     * 高级筛选列表
     */
    public function list()
    {
        $token = input('access_token');
        $arraylist = [
            'code' => '会员卡号',
            'username' => '会员姓名',
            'sex' => '性别',
            'level_code' => '会员级别',
            'phone' => '手机号码',
            'telephone' => '固定电话',
            'identity' => '身份证号',
            'nation' => '民族',
            'birthday' => '会员生日',
            'calendar' => '公历/农历',
            'store_code' => '所属门店',
            'reg_store' => '登记店面',
            'consultant_code' => '形象顾问',
            'activate_code' => '开卡人',
            'introducer_name' => '介绍人',
            'introducer_code' => '介绍人卡号',
            'adult_code' => '提成人',
            'acs' => '入会费',
            'initiation' => '入会方式',
            'qq' => 'QQ',
            'weixin' => '微信',
            'area' => '所属地区',
            'address' => '详细地址',
            'payment_method' => '支付方式',
            'consumption_times' => '消费次数',
            'consumption_number' => '消费件数',
            'exchange_number' => '兑换次数',
            'total_consumption' => '总消费额',
            'first_time' => '首次购物时间',
            'final_purchases' => '最后购物时间',
            'days' => '消费天数',
            'years' => '消费年数',
            'r_days' => '未消费天数',
            'residual_integral' => '剩余总积分',
            'already_used_integral' => '已用积分',
            'offset_integral' => '剩余抵现积分',
            'gift_integral' => '剩余礼品积分',
            'total_integral' => '总积分',
            'stored_value' => '已用储值',
            'residual_value' => '剩余储值',
            'total_value' => '总储值',
            'total_frozen_value' => '总冻结储值',
        ];
        $symbol = [
            '=' => '等于',
            '<>' => '不等于',
            '>' => '大于',
            '>=' => '大于等于',
            '<' => '小于',
            '<=' => '小于等于',
            'LIKE' => '包含'
        ];
        $data = cache('tj_' . $token);
        if ($data) {
            foreach ($data as $k=>$v) {
                $data[$k]['tit'] = $arraylist[$v['tit']];
                $data[$k]['sym'] = $symbol[$v['sym']];
            }
        } else {
            $data = []; 
        }
        webApi(0, '', count($data), $data);
    }

    /**
     * 修改高级筛选列表中的值
     */
    public function updateVlaue()
    {
        $token = input('access_token');
        $data = cache('tj_'.$token);
        foreach ($data as $k=>$v) {
            if (input('id') == $v['id']) {
                $data[$k]['val'] = input('val');
            }
        }
        cache('tj_'.$token, $data, 3600);
        unset($token, $data);
        webApi(0);
    }

     /**
     * 添加筛选条件
     */
    public function addscreen()
    {
        $token = input('access_token');

        $data = cache('tj_' . $token);

        [$tit, $sym, $val] = [input('tit'), input('sym'), input('val')];

        if ($tit == null) {
            webApi(0, 'no', 0, '输入标题为空！');
        } elseif ($sym == null) {
            webApi(0, 'no', 0, '输入条件为空！');
        } elseif ($val == null) {
            webApi(0, 'no', 0, '输入值为空！');
        } 

        $tj = [
            'id' => time(),
            'tit' => $tit,
            'sym' => $sym,
            'val' => $val   
        ];
        
        if ($data) {
            array_push($data, $tj);
        } else {
            $data[0] = $tj;
        }
        unset($tj);
        cache('tj_' . $token, $data, 3600);
        unset($token, $data);
        webApi(0, 'yes', 0, '');
    }

    /**
     * 查询筛选前是不是有缓存
     */
    public function seeCache()
    {
        $data = cache('tj_' . input('access_token'));
        if ($data == null) {
            webApi(0, 'no', 0, '筛选条件不能为空');
        } else {
            webApi(0, 'yes', 0, '');
        }
    }

    /**
     * 保存并筛选
     */
    public function filterinfo()
    {

        $token = input('access_token');

        $data = [
            'filtertitle' => input('text'),
            'content' => json_encode(cache('tj_' . $token), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ];
        unset($token);
        //筛选器标题是否存在
        $filterinfotrepeat = Db::table($this->db.'.vip_filter')->where('filtertitle', $data['filtertitle'])->find();
        if ($filterinfotrepeat) {
            webApi(0, 'error', 0, '筛选标题已存在!');
        }
        unset($filterinfotrepeat);
        $res = Db::table($this->db.'.vip_filter')->insert($data);
        unset($data);
        //提示信息
        if ($res) {
            webApi(0, 'yes', 0, '保存信息成功!');
        } else {
            webApi(0, 'no', 0, '保存信息失败!');
        }
    }

    /**
     * 筛选器信息列表
     */
    public function filterlist()
    {
        $data = Db::table($this->db.'.vip_filter')->field('id, filtertitle')->select();
        webApi(0, '', 0, $data);
    }

    /**
     * 删除筛选器列表中的信息
     */
    public function filterdel()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }

        $data = Db::table($this->db.'.vip_filter')->delete($id);

        if ($data) {
            webApi(0, 'yes', 0, '删除成功');
        } else {
            webApi(0, 'no', 0, '删除失败');
        }
    }

    /**
     * 点击筛选器中编辑时将信息赋值给高级筛选列表框
     */
    public function ditea()
    {
        $data = Db::table($this->db.'.vip_filter')->find(input('id'));
        $sx = json_decode($data['content'], true);
        cache('tj_' . input('access_token'), $sx, 3600);
        unset($data, $sx);
        webApi(0);
    }

    /**
     * 删除高级筛选信息
     */
    public function listdel()
    {
        $token = input('access_token');

        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }
        $data = cache('tj_'.$token) ?? [];
        if (!empty($data)) {
            foreach ($data as $k=>$v) {
                if ($v['id'] == input('id')) {
                    unset($data[$k]);
                }
            }
        }
        if (count($data) > 0) {
            cache('tj_' . $token, $data, 3600);
        } else {
            cache('tj_' . $token, null);
        }
        unset($token, $data);
        webApi(0);
    }

}
