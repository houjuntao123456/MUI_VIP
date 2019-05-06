<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
// use app\web\controller\Common;

/**
 * Author lhp
 * Date 2018/12/28
 * Description 门店管理--组织结构
 */
class Organization extends Common
{   
    /**
     * tableTree列表展示  查询
     */
    public function index()
    {
        $data = Db::table($this->db.'.vip_org')->field('code as id,name,pid,path,sort,id as zid')->order('sort', 'asc')->select();
        webApi(0, '', 0, $data);
    }

    /**
     * 添加分类
     */
    public function add()
    {
        // 如果传递id 就是添加子类 如果没有就是添加父类
        $pid = input('id') ?? 0;
        // 获得表单提交的数据
        $data = [
            'pid' => $pid,
            'name' => input('name'), // 分类名称
            'sort' => input('sort'),  // 排序
            'path' => input('path').$pid.',',
            'code' => 'ZZJG'.str_replace('.' , '', microtime(1))
        ];
        unset($pid);
       // 验证规则
        // $result = $this->validate($data,'app\web\validate\v1\ClassifyValidate');
        // if ($result!== true) {
        // webApi(0, 'error', 0, $result);
        // }
        // unset($result);
        $res = Db::table($this->db.'.vip_org')->insert($data);
        unset($data);
        if ($res) {
            webApi(0,'yes',0,'添加成功');
        } else {
            webApi(0,'NO',0,'添加失败');
        }
    }

    /**
     * 删除分类
     */
    public function del()
    {
        //  获得要删除的id
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }

        //查询当前要删除节点的某一条数据
        $req = Db::table($this->db.'.vip_org')->find($id);

        //拼接子类的全路径
        $path = $req['path'].$id.',';
        unset($req);
        //查询所有子类
        $child = Db::table($this->db.'.vip_org')->where('path','like',$path.'%')->select();

        // 拼接删除条件
        if (!empty($child)) {
            foreach ($child as $v) {
                $id .=','.$v['id'];
            }
        }
        unset($child);
        $res = Db::table($this->db.'.vip_org')->where('id', 'in' ,$id)->delete();

        if ($res) {
            webApi(0,'yes',0,'删除成功');
        } else {
            webApi(0,'NO',0,'删除失败');
        }
    }

    /**
     * 编辑分类
     */
    public function edit()
    {
        $data = [
            'id' => input('id'),
            'name' => input('name'),
            'sort' => input('sort'),
        ];
            
        // 验证规则
        // $result = $this->validate($data,'app\web\validate\v1\ClassifyValidate');
        // if ($result!== true) {
        //     webApi(0, 'error', 0, $result);
        // }
        // unset($result);
        $res = Db::table($this->db.'.vip_org')->update($data);
        unset($data);
        if ($res) {
            webApi(0, 'yes', 0, '修改信息成功');
        } else if ($res === 0) {
            webApi(0, 'no', 0, '未修改');
        } else {
            webApi(0, 'no', 0, '修改信息失败');
        }
    }

    /**
     * 修改分类状态  修改父类子类全部改变
     * */
    public function replace()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }
        if ($id == 1) {
            webApi(1, '不能关闭全部分类');
        }
       
        // 查询当前要节点的某一条数据
        $req = Db::table($this->db.'.vip_org')->find($id);
        $status = intval(!$req['status']);
        //拼接子类的全路径
        $path = $req['path'].$id.',';
        unset($req);
        //查询所有子类
        $child = Db::table($this->db.'.vip_org')->where('path','like',$path.'%')->select();
        unset($path);
        // 拼接修改条件
        if (!empty($child)) {
            foreach ($child as $v) {
                $id .=','.$v['id'];
            }
        }
        unset($child);
        // 执行修改
        $res = Db::table($this->db.'.vip_org')->where('id', 'in', $id)->setField('status', $status);
        unset($status);
        if ($res) {
            webApi(0, 'yes', 0, '修改成功');
        } else {
            webApi(0, 'no', 0, '修改失败');
        }
    }

}