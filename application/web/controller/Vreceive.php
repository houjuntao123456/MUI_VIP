<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
use app\web\controller\Common;

/**
 * Author hrl
 * Date 2018/1/17
 * Description STM员工管理 --- V票领取记录
 */
class Vreceive extends Common
{
    /**
     * V票领取记录
     */
    public function index()
    {
        // 获取分页数据
        [$page, $limit] = [input('page'), input('limit')];
        // 搜索
        if (input('search') != '') {
            $where[] = ['name', 'like', '%'.input('search').'%'];
        } else {
            $where = true; // 永真条件 等于 没有条件
        }
        //查询
        $data = Db::table($this->db.'.vip_vreceive')
                ->alias('r')
                ->leftJoin($this->db.'.vip_staff f', 'f.code = r.receive_claim')
                ->field('r.*,f.name')
                ->where($where)
                ->order('r.receive_time', 'desc')
                ->page($page, $limit)
                ->select();
        unset($page, $limit);
        //修改格式
        foreach ($data as $k => $v) {
            $data[$k]['receive_time'] = date('Y-m-d H:i', $v['receive_time']);
        }

        //统计数量
        $count = Db::table($this->db.'.vip_vreceive')->count();
        webApi(0, '', $count, $data);
    }

    /**
     * V票领取人列表
     */
    public function receiveList()
    {
        $adminOrg = session('info.admin_org');
        if ($adminOrg == '') {
            webApi(0, '', 0, []);
        }
        $rules = Db::table($this->db.'.vip_vticket_rule')->where('operate_code', session('info.staff'))->select();
        if (empty($rules)) {
            webApi(0, '', 0, []);
        }

        $codes = implode(',', array_column($rules, 'staff_codes'));
        $data = Db::table($this->db.'.vip_staff')->field('code,name,phone')->where('code', 'in', $codes)->select();
        webApi(0, '', '', $data);
    }

    /**
     * 执行领取
     */
    public function receival()
    {
        $data = [
            'receive_claim' => input('receive_claim'),    // 领取人
            'receive_number' => intval(input('receive_number')),  // 数量
            'receive_standard' => input('receive_standard'),    // 奖励标准
            'receive_time' => time()    // 领取时间
        ];

        if (intval(input('receive_number')) < 1 ) {
            webApi(0, 'error', 0, '领取数量不能小于');
        }
        // 验证器
        $result = $this->validate($data,'app\web\validate\VreceiveValidate.receival');
        if ($result!== true) {
            webApi(0, 'error', 0, $result);
        }

        // 如果有领取记录就setInc  否则就直接插入
        $exit = Db::table($this->db.'.vip_vreceive')->where('receive_claim',input('receive_claim'))->find();

        // 启动事务
        Db::startTrans();
        try {
            if ($exit) {
                $res = Db::table($this->db.'.vip_vreceive')->where('receive_claim',input('receive_claim'))->setInc('receive_number', input('receive_number'));
            } else {
                $res = Db::table($this->db.'.vip_vreceive')->insert($data);
            }
            // 领取加 奖池数量
            $sql = 'update '.$this->db.'.vip_vticket_rule set number = number + '.intval(input('receive_number')).' where find_in_set('.input('receive_claim').', staff_codes)';
            Db::query($sql);
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $res = false;
        }
        unset($data);
        //提示信息
        if ($res) {
            webApi(0, 'yes', 0, '领取成功!');
        } else {
            webApi(0, 'no', 0, '领取失败!');
        }
    }


    /**
     * 兑换
     * 1. 计算汇率 和 兑换总金额
     */
    public function exchangeOne () {

        // 兑换数量
        $val = intVal(input('val'));
        $amount = intVal(input('amount'));
        if ($val > $amount) {
            webApi(0, 'error', 0, '兑换数量大于自己可领取数量');
        }

        // 获得兑换人code
        $receive_claim = input('receive_claim');
        // 依据兑换人code 获得规则 
        $sql = 'select * from '.$this->db.'.vip_vticket_rule where find_in_set('.$receive_claim.', staff_codes)';
        $rule = Db::query($sql);
        // v票汇率  剩余金额 除以 剩余票数
        $rate = number_format($rule[0]['price'] / $rule[0]['number'], '2', '.', '');
        // 兑换总金额
        $totalPrice = $rate * $val;

        webApi(0, 'yes', 0, ['rate' => $rate, 'totalPrice' => $totalPrice]);
    }

    /**
     * 确认兑换
     */
    public function confirmExchange () {

        $data = [
            // 获得兑换数量
            'number' => intVal(input('val')),
            // 获得兑换总金额
            'price' => input('totalPrice'),
            // 获得兑换人
            'name' => input('name'),
            // 兑换人code
            'receive_claim' => input('receive_claim'),
            // 获得v票汇率
            'rate' => input('rate'),
            // 获得兑换时间
            'create_time' => time(),
        ];
        
        // 验证器
        $result = $this->validate(input(), 'app\web\validate\VreceiveValidate.exchange');
        if ($result!== true) {
            webApi(0, 'error', 0, $result);
        }

        // 依据兑换人code 获得规则 
        $sql = 'select * from '.$this->db.'.vip_vticket_rule where find_in_set('.$data['receive_claim'].', staff_codes)';
        $rule = Db::query($sql);

        // 启动事务
        Db::startTrans();
        try {
            // 减掉资金池里面的钱  和  数量
            Db::table($this->db.'.vip_vticket_rule')->where('code',$rule[0]['code'])->dec('price', $data['price'])->dec('number', $data['number'])->update();
            // 产生兑换记录
            Db::table($this->db.'.vip_exchange_record')->insert($data);
            // 扣除领取数量
            Db::table($this->db.'.vip_vreceive')->where('id',input('id'))->setDec('receive_number',$data['number']);
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $res = false;
        }
        //提示信息
        if ($res) {
            webApi(0, 'yes', 0, '兑换成功!');
        } else {
            webApi(0, 'no', 0, '兑换失败!');
        }
    }

    /**
     * 删除领取记录
     */
    public function del () {
        // 获得记录的id
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }
        $res = Db::table($this->db.'.vip_vreceive')->where('id',$id)->delete();
        if($res){
            webApi(0,'yes',0,'删除成功');
        }else{
            webApi(0,'NO',0,'删除失败');
        }       
    }
}