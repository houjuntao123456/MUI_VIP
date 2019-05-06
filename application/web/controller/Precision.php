<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
use app\web\controller\Common;
use app\web\service\ErpWhere as EW;

/**
 * Author lhp
 * Date 2019/01/17
 * Description 精准营销列表
 */
class Precision extends Common
{
    public function index() 
    {
        [$page, $limit] = [input('page'), input('limit')];

        $goodsExtField = Db::table($this->db.'.vip_goods_label')->field('name')->where('status', 1)->select();
        if (!empty($goodsExtField)) {
            $extenison = array_column($goodsExtField, 'name');
        } else {
            $extenison = [];
        }

        $where[] = ['1', '=', '1'];
        if (input('where') == 'true') {
            if (input('sxq') != 'false') {
                $sxq = Db::table($this->db.'.vip_precise_filter')->find(input('sxq'));
                $sx = json_decode($sxq['content'], true);
            } else {
                $sx = cache('gj_'.input('access_token'));
            }
            
            if (!empty($sx)) {
                $gw = '1=1';
                $float = ['info_price', 'info_disprice', 'info_discount', 'info_dismoney', 'info_money', 'o_money', 'o_dis_money'];
                foreach ($sx as $v) {
                    if (in_array($v['tit'], $goodsExtField)) {
                        $gw .= ' and json_search(json_extract(IF(IFNULL(extension, "[]") = "", "[]", goods_extend), \'$."'.$v['tit'].'"\'), "all", "%'.$v['val'].'%")  is not null';
                    } else {
                        if ($v['sym'] == 'LIKE') {
                            $gw .= ' and '.$v['tit'].' '.$v['sym'].' "%'.$v['val'].'%"';
                        } else {
                            if (in_array($v['tit'], $float)) {
                                $gw .= ' and '.$v['tit'].$v['sym'].' '.$v['val'];
                            } else {
                                $gw .= ' and '.$v['tit'].$v['sym'].' "'.$v['val'].'"';
                            }
                        }
                    }
                }

                if (session('info.store') == '') {
                    $stores = EW::getStore(EW::orgChild());
                } else {
                    $stores = session('info.store');
                }
 
                $vipCards = Db::table($this->db.'.view_precision')->field('o_vip_card')->where($gw)->where('o_store_code', 'in', $stores)->where('o_vip_card', '<>', '')->group('o_vip_card')->select();
                if ($vipCards) {
                    $where[] = ['code', 'in', array_column($vipCards, 'o_vip_card')];
                } else {
                    webApi(0, '', 0, []);
                }
            }
        }
        // dump($where);exit;
        $data = Db::table($this->db.'.view_viplist')
            ->field('id, code, username, sex, identity, nation, phone, store_code, vsname, level_code, vlname, reg_store, vrname, calendar, birthday, consultant_code, vgname, area, address, activate_code, activate_name, introducer_code, adult_name, adult_code, acs, payment_method, consumption_times, consumption_number, exchange_number, total_consumption, first_time, final_purchases, remarks, telephone, initiation, qq, weixin, img, date_registration, residual_integral, already_used_integral, offset_integral, gift_integral, total_integral, stored_value, residual_value, total_value, total_frozen_value, days, years, r_days, extension, img')
            ->where($where)
            // ->where('final_purchases', '<>', '未消费')
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
        
        $count = Db::table($this->db. '.view_viplist')->where('final_purchases', '<>', '未消费')->count();

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
     * 清除缓存
     */
    public function delCache() {
        cache('gj_'.input('access_token'), null);
        webApi(0);
    }

    
    /**
     * 查看会员时的扩展
     */
    public function getlistkz()
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
     * 循环高级筛选中的下拉框信息
     */
    public function otherning()
    {
        $data = array(
            ['value' => 'goods_barcode', 'name' => '商品条码'],
            ['value' => 'goods_name', 'name' => '商品名称'],
            ['value' => 'goods_frenum', 'name' => '货号'],
            ['value' => 'type_name', 'name' => '分类名'],
            ['value' => 'goods_color', 'name' => '颜色'],
            ['value' => 'goods_size', 'name' => '尺码'],
            ['value' => 'info_price', 'name' => '商品的单价'],
            ['value' => 'info_disprice', 'name' => '折扣价'],
            ['value' => 'info_discount', 'name' => '折扣力度'],
            ['value' => 'info_dismoney', 'name' => '折后合计'],
            ['value' => 'info_number', 'name' => '购买数量'],
            ['value' => 'info_giveintegral', 'name' => '获得积分'],
            ['value' => 'info_staffname', 'name' => '促销员'],
        );
        $goodsExt = Db::table($this->db.'.vip_goods_label')->field('name')->select();
        if ($goodsExt) {
            foreach ($goodsExt as $k=>$v) {
                array_push($data,["value"=>$v['name'],"name"=>$v['name']]);
            }
        }
        unset($goodsExt);
        webApi(0, '', 0, $data);

    }

    /**
     * 高级筛选列表
     */
    public function list()
    {
        $token = input('access_token');
        $arraylist = [
            'goods_barcode' => '商品条码',
            'goods_name' => '商品名称',
            'goods_frenum' => '货号',
            'type_name' => '分类名',
            'goods_color' => '颜色',
            'goods_size' => '尺码',
            'info_price' => '商品的单价',
            'info_disprice' => '折扣价',
            'info_discount' => '折扣力度',
            'info_dismoney' => '折扣合计',
            'info_staffname' => '购买数量',
            'info_giveintegral' => '获得积分',
            'info_staffname' => '促销员',
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
        
        $goodsExt = Db::table($this->db.'.vip_goods_label')->field('name')->select();
        if ($goodsExt) {
            foreach ($goodsExt as $k=>$v) {
                $arraylist[$v['name']] = $v['name'];
            }
        }
        unset($goodsExt);
        $data = cache('gj_' . $token);
        if ($data != false) {
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
     * 添加筛选条件
     */
    public function OtherAdd()
    {
        $token = input('access_token');
        $data = cache('gj_' . $token);

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
        unset($tit, $sym, $val);
        if ($data) {
            array_push($data, $tj);
        } else {
            $data[0] = $tj;
        }
        unset($tj);
        cache('gj_' . $token, $data, 3600);
        unset($token, $data);
        webApi(0, 'yes', 0, '');
    }

    /**
     * 删除高级筛选信息
     */
    public function gaojidel()
    {
        $token = input('access_token');

        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }
        $data = cache('gj_'.$token);
        foreach ($data as $k=>$v) {
            if ($v['id'] == input('id')) {
                unset($data[$k]);
            }
        }
        if (count($data) > 0) {
            cache('gj_' . $token, $data, 3600);
        } else {
            cache('gj_' . $token, null);
        }
        unset($data, $token);
        webApi(0);
    }

    /**
     * 修改高级筛选列表中的值
     */
    public function updateVlaue()
    {   
        $token = input('access_token');
        $data = cache('gj_'.$token);
        foreach ($data as $k=>$v) {
            if (input('id') == $v['id']) {
                $data[$k]['val'] = input('val');
            }
        }
        cache('gj_'.$token, $data, 3600);
        unset($token, $data);
        webApi(0);
    }

    /**
     * 保存并筛选
     */
    public function Otherinfo()
    {

        $token = input('access_token');

        $data = [
            'filtertitle' => input('text'),
            'content' => json_encode(cache('gj_' . $token), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'org_code' => session('info.org')
        ];
        unset($token);
        //筛选器标题是否存在
        $filterinfotrepeat = Db::table($this->db.'.vip_precise_filter')->where('filtertitle', $data['filtertitle'])->find();
        if ($filterinfotrepeat) {
            webApi(0, 'error', 0, '筛选标题已存在!');
        }
        unset($filterinfotrepeat);
        $res = Db::table($this->db.'.vip_precise_filter')->insert($data);
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
    public function Otherfilterlist()
    {
        $data = Db::table($this->db.'.vip_precise_filter')->field('id, filtertitle')->select();
        webApi(0, '', 0, $data);
    }

    /**
     * 删除筛选器列表中的信息
     */
    public function Ofilterlistdel()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }

        $data = Db::table($this->db.'.vip_precise_filter')->delete($id);

        if ($data) {
            webApi(0, 'yes', 0, '删除成功');
        } else {
            webApi(0, 'no', 0, '删除失败');
        }
    }

    /**
     * 点击筛选器中编辑时将信息赋值给高级筛选列表框
     */
    public function Otherassignment()
    {
        $data = Db::table($this->db.'.vip_precise_filter')->find(input('id'));
        $sx = json_decode($data['content'], true);
        unset($data);
        cache('gj_' . input('access_token'), $sx, 3600);
        unset($sx);
        webApi(0);
    }

    /**
     * 查询筛选前是不是有缓存
     */
    public function issCache()
    {
        $data = cache('gj_' . input('access_token'));

        if ($data == null) {
            webApi(0, 'no', 0, '筛选条件不能为空');
        } else {
            webApi(0, 'yes', 0, '');
        }
    }

    /**
     * 高级筛选短信
     */
    public function PrescisionMessage()
    {
        $db = $this->db;
        
        $goodsExtField = Db::table($this->db.'.user_goods_extend_field')->field('name')->where('status', 1)->select();
        if (!empty($goodsExtField)) {
            $extenison = array_column($goodsExtField, 'name');
        } else {
            $extenison = [];
        }
        $sx = cache('gj_' . input('access_token'));
        $gw = '1=1';
        if (!empty($sx)) {
            $gw = '1=1';
            foreach ($sx as $k=>$v) {
                if (!in_array($v['tit'], $extenison)) {
                    if ($v['sym'] == 'LIKE') {
                        $gw .= ' and '.$v['tit'].' '.$v['sym'].' "%'.$v['val'].'%"';
                    } else {
                        $gw .= ' and '.$v['tit'].$v['sym'].'"'.$v['val'].'"';
                    }
                } else {
                    $gw .= ' and json_search(json_extract(IF(IFNULL(goods_extend, "[]") = "", "[]", goods_extend), \'$."'.$v['tit'].'"\'), "all", "%'.$v['val'].'%")  is not null';
                }
            }
            if (empty(session('info.store'))) {
                $storeWhere = true;
            } else {
                $storeWhere[] = ['o_store_id', '=', session('info.store')['ERP']];
            }
            $vipCards = Db::table($db.'.view_precision')->field('o_member_card')->where($storeWhere)->where('o_member_card', '<>', '')->where($gw)->group('o_member_card')->select();
            if ($vipCards) {
                $where[] = ['card', 'in', array_column($vipCards, 'o_member_card')];
            } else {
                webApi(1, '没有符合条件的会员');
            }
        }
        $data = Db::table($db.'.view_viplist')
                ->field('card,username,sex,mg_name,consultant_name,total_integral,residual_value,total_consumption,introducer,contact')
                ->where($where)
                ->select();
        // dump($data);exit;
        $res = $this->smsBatch($data, input('sms'));
        $res = json_decode($res, true);
        if ($res['code'] == 200) {
            webApi(0, '', 0, ['yes' => $res['yes'], 'no' => $res['no']]);
        } else {
            webApi(1, $res['msg']);
        }
    }


    /**
     * 查看RFM
     */
    public function precisionrfm()
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
     * 查看购买商品列表
     */
    public function purchasegoods()
    {
        [$page, $limit] = [input('page'), input('limit')];

        $data = Db::table($this->db.'.view_precision')
                ->field('info_id, info_code, info_goodscode, info_price, info_disprice, info_discount, info_dismoney, info_number, info_money, info_giveintegral, info_staffcode, info_staffname, o_store_code, o_vip_card, o_vip_name, o_number, o_money, o_dis_money, goods_name, goods_frenum, goods_barcode, goods_typecode, goods_color, goods_size, goods_extend, goods_img, type_name')
                ->where('o_vip_card', input('cards'))
                ->page($page, $limit)
                ->select();
        
            foreach ($data as $k=>$v) {
                $data[$k]['goods_extend'] = json_decode($v['goods_extend'], true);
            }
    
            $ext = Db::table($this->db.'.vip_goods_label')->field('name')->where('status', 1)->select();
            foreach ($ext as $k=>$v) {
                if (!empty($v['name'])) {
                    array_push($ext, $v['name']);
                    unset($ext[$k]);
                }
            }
            sort($ext);
            for ($i = 0; $i < count($ext); $i++) {
                foreach ($data as $k=>$v) {
                    if ($v['goods_extend'] && !empty($v['goods_extend'][$ext[$i]])) {
                        if (is_array($v['goods_extend'][$ext[$i]])) {
                            $data[$k][$ext[$i]] = implode(' ', $v['goods_extend'][$ext[$i]]);
                        } else {
                            $data[$k][$ext[$i]] = $v['goods_extend'][$ext[$i]];
                        }
                    } else {
                        $data[$k][$ext[$i]] = '';
                    }
                }
            }
        $count = Db::table($this->db.'.view_precision')->where('o_vip_card', '=', input('cards'))->count();

        webApi(0, '', $count, $data);
    }

    /**
     * 查看购买商品扩展
     */
    public function pext()
    {
        $ext = Db::table($this->db.'.vip_goods_label')->field('name')->where('status', 1)->select();
        if (!empty($ext)) {
            $ext = array_column($ext, 'name');
        } else {
            $ext = [];
        }
        webApi(0, '', 0, $ext);
    }

}
