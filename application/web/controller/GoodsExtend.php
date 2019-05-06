<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
use app\web\controller\Common;

/**
 * Author hrl
 * Date 2018/12/28
 * Description 商品管理--商品标签
 *  1.添加商品扩展标签
 */
class GoodsExtend extends Common
{
    /**
     * 标签列表
     */
    public function index()
    {
        // 分页配置
        [$limit, $page] = [input('limit'), input('page')];
        //查询扩展标签
        $data = Db::table($this->db.'.vip_goods_label')->select();
        if ($data) {
            foreach ($data as $k=>$v) {
                if ($v['status'] == 1) {
                    $data[$k]['on'] = true;
                } else {
                    $data[$k]['on'] = false;
                }
            }
        }
        unset($limt);
        unset($page);
        webApi(0, '', 0, $data);
    }

    /**
     * 添加标签
     */
    public function addLabel()
    {
        //获得表单添加的数据
        $data = [
            'name' => trim(input('name')),
            'type' => trim(input('type')), // 获得标签的类型
            'status' => 0
        ];   
        
        //  验证器判断为空的情况
        $result = $this->validate($data,'app\web\validate\LabelValidate.addSpread');
            if(true !== $result  ){
                webApi(0,'error',0,$result);
        }

        // 查询数据库 判定是否存在
        $result = Db::table($this->db.'.vip_goods_label')->where('name', $data['name'])->find();
        if($result){
            unset($result);
            webApi(0, 'error', 0, '已经存在');
        }else{
            $res = Db::table($this->db.'.vip_goods_label')->insert($data);
            unset($data);
            if ($res) {
                webApi(0, 'yes', 0, '添加成功');
            } else {
                webApi(0, 'no', 0, '添加失败');
            }
        }
      
    }

    /**
     * 删除标签
     */
    public function delLabel()
    {
        // 获得删除的扩展类的id
        $id = input('id') ?? null;
        if (input('id') == '') {
            webApi(1, '参数错误');
        }
        //依据id删除
        $res = Db::table($this->db.'.vip_goods_label')->delete($id);
        unset($id);
        if ($res) {
            webApi(0, 'yes', 0, '删除成功');
        } else {
            webApi(0, 'no', 0, '删除失败');
        }
    }

    /**
     * 修改标签状态
     */
    public function editLabel()
    {
        //获得点击的标签
        $id = input('id') ?? null;
        if (input('id') == '') {
            webApi(1, '参数错误');
        }
        $rep = Db::table($this->db.'.vip_goods_label')->find($id);
        $status = intval(!$rep['status']);
        $res = Db::table($this->db.'.vip_goods_label')->where('id', $id)->setField('status', $status);
        unset($id);
        unset($status);
        if ($res) {
            webApi(0, 'yes', 0, '成功');
        } else {
            webApi(0, 'no', 0, '失败');
        }
    }
}