<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
use app\web\controller\Common;

/**
 * Author lhp
 * Date 2019/01/09
 * Description 会员标签
 */
class Viplabel extends Common
{

    // 查询扩展类标签 
    public function labelList()
    {
        // 分页配置
        [$limit, $page] = [input('limit'), input('page')];
        //查询扩展标签
        $data = Db::table(session('info.db').'.vip_viplabel')->select();
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

    // 添加扩展标签
    public function addLabel()
    {
        //获得表单添加的数据
        $data = [
            'name' => trim(input('name')),
            'type' => trim(input('type')), 
            'status' => 0,
            'code' => 'LABEL'.str_replace('.' , '', microtime(1))
        ];   
        
        //  验证器判断为空的情况
        // $result = $this->validate($data,'app\web\validate\v1\LabelValidate.addSpread');
        //     if(true !== $result  ){
        //         webApi(0,'error',0,$result);
        // }

        // 查询数据库 判定是否存在
        $result = Db::table($this->db.'.vip_viplabel')->where('name', $data['name'])->find();
        if($result){
            unset($result);
            webApi(0, 'error', 0, '已经存在');
        }else{
            $res = Db::table($this->db.'.vip_viplabel')->insert($data);
            unset($data);
            if ($res) {
                webApi(0, 'yes', 0, '添加成功');
            } else {
                webApi(0, 'no', 0, '添加失败');
            }
        }
      
    }

    // 点击扩展标签修改到数据库
    public function editLabel()
    {
        //获得点击的标签
        $id = input('id') ?? null;
        if (input('id') == '') {
            webApi(1, '参数错误');
        }
        $rep = Db::table(session('info.db').'.vip_viplabel')->find($id);
        $status = intval(!$rep['status']);
        $res = Db::table(session('info.db').'.vip_viplabel')->where('id', $id)->setField('status', $status);
        unset($id);
        unset($status);
        if ($res) {
            webApi(0);
        } else {
            webApi(0, 'no', 0, '失败');
        }
    }

    // 删除扩展类
    public function delLabel()
    {
        // 获得删除的扩展类的id
        $id = input('id') ?? null;
        if (input('id') == '') {
            webApi(1, '参数错误');
        }
        //依据id删除
        $res = Db::table(session('info.db').'.vip_viplabel')->delete($id);
        unset($id);
        if ($res) {
            webApi(0, 'yes', 0, '删除成功');
        } else {
            webApi(0, 'no', 0, '删除失败');
        }
    }

}