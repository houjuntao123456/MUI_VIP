<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
use app\web\controller\Common;
use app\web\service\ErpWhere as EOW;

/**
 * Author hrl
 * Date 2018/1/4
 * Description 商品管理 --- 商品列表
 */
class Goods extends Common
{
    /**
     * 商品列表 
     * 1.根据查看人的身份，判断是否查询门店价格，如果不是门店内员工，本店价格为空
     */
    public function index()
    {
        /**
         * 高级筛选
         * 8.筛选
         * 拿出缓存的多个条件进行筛选 1. 如果存在扩展字段就是高级筛选 否则就是普通筛选
         */

        // 查询扩展字段进行好几筛选匹配
        $goodsExtField = Db::table($this->db.'.vip_goods_label')->field('name')->where('status', 1)->select();
        if (!empty($goodsExtField)) {
            // 不为空取出扩展字段的值
            $extenison = array_column($goodsExtField, 'name');
        } else {
            $extenison = [];
        }
        // 设定永真条件
        $highWhere = '1=1';
        // 判定是筛选
        if (input('high_screen') == 'true') {
            // 判断是筛选器还是缓存
            if (input('sizer') != 'false') {
                $sxq = Db::table($this->db.'.vip_goods_sizer')->find(input('sxq'));
                $tj = json_decode($sxq['content'], true);
            } else {
                // 取出缓存的索引条件
                $tj = cache('viphighScreenList_' . input('access_token'));
            }
            if (!empty($tj)) {
                $float = ['price'];
                // 根据扩展字段筛选 extends---- 循环判定扩展字段
                foreach ($tj as $k=>$v) {

                    // 如果字段是扩展字段  走json条件
                    if (in_array($v['tit'], $extenison)) {
                        $highWhere .= ' and json_search(json_extract(IF(IFNULL(extend, "[]") = "", "[]", extend), \'$."'.$v['tit'].'"\'), "all", "%'.$v['val'].'%")  is not null';
                    } else {
                        // 基础字段 走普通条件
                        if ($v['sym'] == 'LIKE') {
                            // 普通的模糊匹配(包含)
                            $highWhere .= ' and '.$v['tit'].' like "%'.$v['val'].'%"';
                        } else {
                            //  普通的准确匹配(大于 小于 )
                            if (in_array($v['tit'], $float)) {
                                $highWhere .= ' and '.$v['tit'].' '.$v['sym'].' '.$v['val'];
                            } else {
                                $highWhere .= ' and '.$v['tit'].' '.$v['sym'].' "'.$v['val'].'"';
                            }
                        }
                    }
                }

            }
        }

        // 分页配置
        [$limit, $page] = [input('limit'), input('page')];
        // 搜索 设立永真
        $where[] = ['1', '=', '1'];
        // 如果当前类不为空
        if (input('classify') != '') {
            $classifys = EOW::classfiyChild(input('classify'));
            $where[] = ['g.classify_code', 'in', $classifys];
        }
        if (session('info.store') == '') {
            $data = Db::table($this->db.'.vip_goods')
                    ->alias('g')
                    ->leftJoin($this->db.'.vip_goods_classify c', 'c.code = classify_code')
                    ->field('g.*,c.name as cname')
                    ->where($where)
                    ->where($highWhere)
                    ->page($page, $limit)
                    ->select();
            if ($data) {
                foreach ($data as $k=>$v) {
                    $data[$k]['sprice'] = '';
                }
            }
        } else {
            $data = Db::table($this->db.'.vip_goods')
                    ->alias('g')
                    ->leftJoin($this->db.'.vip_goods_store s' , 'g.code = s.goods_code')
                    ->leftJoin($this->db.'.vip_goods_classify c', 'c.code = classify_code')
                    ->where('s.store_code', session('info.store'))
                    ->where($where)
                    ->where($highWhere)
                    ->field('g.*,s.price as sprice,c.name as cname')
                    ->page($page, $limit)
                    ->select();
        }
        $count = Db::table($this->db.'.vip_goods')->count();

        webApi(0, '', $count, $data);
    }

    /**
     * 查看扩展详情
     */
    public function goodsDetail()
    {
        $code = input('code') ?? null;
        if (input('code') == '') {
            webApi(1, '参数错误');
        }
        $data = Db::table($this->db.'.vip_goods')->where('code',$code)->field('extend')->find();
        if ($data['extend'] == '') {
            $str = '';
        } else {
            // json转为数组
            $ext = json_decode($data['extend'], 1);
            // 获取数组的值
            $extVal = array_values($ext);
            // 获取数组的key
            $extKeys = array_keys($ext);
            // 数组的长度
            $extLength = count($ext);

            $str = '<div style="border: 1px solid #e6e6e6">';
            for ($i = 0; $i < $extLength; $i++) {
                // 是数组
                if ( is_array($extVal[$i]) ) {
                    $str .= '<div style="padding-left:8px;">'. $extKeys[$i] .': '. implode(', ', $extVal[$i]) .'</div>';
                } else {
                    $str .= '<div style="padding-left:8px;">'. $extKeys[$i] .': '. $extVal[$i] .'</div>';
                }
            }
            $str .= '</div>';
        }
        webApi(0, '', 0, $str);
    }

    /**
     * 编辑商品信息(只有扩展可以编辑)
     * 1. 请求标签列表
     * 2. 文本和时间类型赋初始值
     */
    public function editExtends()
    {   
        // 查询标签列表
        $data['label'] = Db::table($this->db.'.vip_goods_label')->where('status',1)->select();
        // 如果为空返回空数组
        if (empty($data['label'])) {
            $data['label'] = [];
        }
        // 查询扩展信息 给文本和时间类型赋初始值
        $goods = Db::table($this->db.'.vip_goods')->where('id', input('id'))->field('extend')->find();
        if ($goods['extend'] != '') {
            $data['extend'] = json_decode($goods['extend'], true);
            cache('goods_extend_edit'.input('access_token'), $data['extend'], 3600);
        } else {
            $data['extend'] = '';
        }
        webApi(0, '', 0, $data);

    }

    /**
     * 扩展标签 
     * 1. 获得扩展类型的详细内容插入到数据库
     */
    public function extendsInfo()
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

        $result = Db::table($this->db.'.vip_goods_label_info')->where('name',$data['name'])->where('info',$data['info'])->find();    
        if ($result) {
            unset($result); 
            webApi(0,'error',0,'标签字段已经存在');
        } else {
            //插入到数据库
            $res = Db::table($this->db.'.vip_goods_label_info')->insert($data);
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
    public function infoList()
    {
        $data = Db::table($this->db.'.vip_goods_label_info')->field('id, info as name')->where('name', input('name'))->select();
        // 悬停显示名字 加一个 type
        if ($data) {
            foreach ($data as $k=>$v) {
                $data[$k]['type'] = $v['name'];
            }
        }
        $ext = cache('goods_extend_edit'.input('access_token')) ?? [];
        if (!empty($ext) && array_key_exists(input('name'), $ext) && !empty($data)) {
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
    public function delInfo()
    {
        // 获得删除的内容的id
        $id = input('id');
        if (input('id') == '') {
            webApi(1, '参数错误');
        }
        $res = Db::table($this->db.'.vip_goods_label_info')->where('id',$id)->delete();
        unset($id);
        if($res){
            webApi(0,'yes',0,'删除成功');
        }else{
            webApi(0,'NO',0,'删除失败');
        }
    }

    /**
     * 点击扩展类型的内容  加入缓存
     */
    public function addInfoCache()
    {   
        // 获得缓存
        $data = cache('goods_extend_edit'.input('access_token')) ?? [];
        
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
        cache('goods_extend_edit'.input('access_token'), $data, 3600);
        webApi(0);
    }

    /**
     * 编辑扩展信息提交  和 批量编辑是一个  
     */
    public function editSubmit()
    {
        $id = trim(input('id'), ',');
        if (input('id') == '') {
            webApi(1, '参数错误');
        }

        $data = cache('goods_extend_edit'.input('access_token')) ?? [];
        if (!empty($data)) {
            $str = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $str = '';
        }

        $res = Db::table($this->db.'.vip_goods')->where('id', 'in', $id)->setField('extend', $str);
        if($res){
            cache('goods_extend_edit'.input('access_token'), null);   // 清理缓存
            webApi(0,'yes',0,'编辑成功');
        }else{
            webApi(0,'NO',0,'编辑失败');
        }
    }

    /**
     * 门店价格:
     *  查询组织机构
     */
    public function goodsOrg()
    {
        if (session('info.admin_org') == '') {
            webApi(0, '', 0, []);
        }
        // 获取自己管理的组织机构及子机构
        $orgs = EOW::orgChild();
        $data = Db::table($this->db.'.vip_org')->field('code as id,pid as parentId,name as title')->where('code', 'in', $orgs)->select();
        foreach ($data as $k=>$v) {
            $data[$k]['checkArr'] = '0';
        }
        $data = getTree($data, 'id', 'parentId');
        webApi(0, '', 0, $data);
    }

    /**
     * 门店价格:
     * 查询所有门店(门店在管理的组织机构里面)
     * 
     */
    public function storeList()
    {
        // 获得选择组织机构
        $org = input('org');
        // 查询该组织结构下的所有门店
        $stores = EOW::getStore(EOW::orgChild($org));
        $where[] = ['g.code', '=', input('goods_code')];
        $where[] = ['s.store_code', 'in', $stores];
        $data = Db::table($this->db.'.vip_goods_store')
                ->alias('s')
                ->leftJoin($this->db.'.vip_goods g', 's.goods_code = g.code')
                ->leftJoin($this->db.'.vip_store e', 's.store_code = e.code')
                ->field('g.name as gname,g.bar_code,g.frenum,e.name as store_name,s.price,g.price as gprice')
                ->where($where)
                ->select();
        webApi(0, '', 0, $data);
    }


    /**
     * 分类搜索的分类列表
     * 封装在test.php
     */
    public function vipClassify()
    {
        // 一定要指定field 
        $data = Db::table($this->db.'.vip_goods_classify')->field('code as id,pid as parentId,name as title')->select();
        // 列表的选择框
        foreach ($data as $k=>$v) {
            $data[$k]['checkArr'] = '0';
        }
        // 变成树结构
        $data = getTree($data, 'id', 'parentId');
        webApi(0, '', 0, $data);
    }

    /**
     * 编辑扩展信息 先清空缓存
     */
    public function cacheClear()
    {
        cache('goods_extend_edit'.input('access_token'), null);
        webApi(0);
    }

    //---------------------------------------------------------------------------------------------

    /**
     * 高级筛选
     * 1.下拉选择条件
     */
    public function screenSelectField()
    {
        // 指定筛选的条件字段
        $field = [
            ['value' => 'frenum', 'name' => '商品货号'],
            ['value' => 'name', 'name' => '商品名称'],
            ['value' => 'bar_code', 'name' => '商品条码'],
            ['value' => 'color', 'name' => '商品颜色'],
            ['value' => 'size', 'name' => '商品尺码'],
            ['value' => 'unit', 'name' => '商品单位'],
            ['value' => 'price', 'name' => '商品价格'],
            ['value' => 'remark', 'name' => '商品备注']
        ];

        // 获得扩展字段的名字
        $goodsExt = Db::table($this->db.'.vip_goods_label')->field('name')->select();
        // 追加数组
        if ($goodsExt) {
            foreach ($goodsExt as $k=>$v) {
                array_push($field, ['value' => $v['name'], 'name' => $v['name']]);
            }
        }
        webApi(0, '', 0, $field);

    }   

    /**
     * 高级筛选
     * 2.将筛选条件添加到缓存 
     */ 
    public function highScreenAdd()
    {   
        // 先获得缓存
        $data = cache('viphighScreenList_'.input('access_token'));
        // 如果缓存存在
        if ($data) {
            // 取出对应的值
            $field = array_column($data, 'tit');
            // 如果数组里存在key
            if (in_array(input('tit'), $field)) {
                foreach ($data as $k=>$v) {
                    // 替换key
                    if ($v['tit'] == input('tit')) {
                        // 替换值
                        $data[$k]['sym'] = input('sym');
                        $data[$k]['val'] = input('val');
                        $data[$k]['id'] = time();   // 用于条件删除
                    }
                }
            } else {
                // 否则就新增
                array_push($data, ['tit' => input('tit'), 'sym' => input('sym'), 'val' => input('val'), 'id' => time()]);
            }
        } else {
            $data = [];
            array_push($data, ['tit' => input('tit'), 'sym' => input('sym'), 'val' => input('val'), 'id' => time()]);
        }

        // 存入缓存
        cache('viphighScreenList_'.input('access_token'), $data, 3600);
        webApi(0);
    }


    /**
     * 高级筛选
     * 3.将条件添加到缓存 从缓存拿出显示到表格
     */
    public function vipHighScreenList()
    {
        // 获得缓存的数据
        $data = cache('viphighScreenList_'.input('access_token'));
        $field = [
            'frenum' => '商品货号',
            'name' => '商品名称',
            'bar_code' => '商品条码',
            'color' => '商品颜色',
            'size' => '商品尺码',
            'price' => '商品价格',
            'remarks' => '商品备注'
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
        // 替换成中文
        if ($data) {
            foreach ($data as $k=>$v) {
                if (!empty($field[$v['tit']])) {
                    $data[$k]['tit'] = $field[$v['tit']];
                }
                $data[$k]['sym'] = $symbol[$v['sym']];
            }
        } else {
            $data = [];
        }
        webApi(0, '', 0, $data);
    }

    /**
     * 高级筛选
     * 4.清除筛选列表缓存
     */
    public function clearHighScreenCache()
    {
        cache('viphighScreenList_'.input('access_token'), null);
        webApi(0);
    }

    /**
     * 高级筛选
     * 5.从缓存中拿出 删除表格添加的筛选条件
     */
    public function tiaojianDel()
    {
        // 获得缓存
        $data = cache('viphighScreenList_'.input('access_token'));
        if ($data) {
            foreach ($data as $k=>$v) {
                if ($v['id'] == input('id')) {
                    unset($data[$k]);
                }
            }
            sort($data);
            cache('viphighScreenList_'.input('access_token'), $data, 3600);
        }
        webApi(0);
    }


    /**
     * 高级筛选 --- 点击筛选
     * 6.检查筛选条件是否为空 不为空加载列表进行筛选
     */
    public function checkScreenIsEmpty()
    {
        $data = cache('viphighScreenList_'.input('access_token'));
        if ($data) {
            $res = true;
        } else {
            $res = false;
        }
        webApi(0, '', 0, $res); 
    }

    /**
     * 高级筛选
     * 7.保存并筛选
     */
    public function sizerAdd()
    { 
        $cache = cache('viphighScreenList_'.input('access_token'));
        if (!$cache) {
            webApi(0, 'no');
        }
        
        if (session('info.admin_org') == '') {
            $data[] = [
                'name' => input('text'),
                'content' => json_encode($cache, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'org_code' => session('info.org')
            ];
        } else {
            $orgs = explode(',', session('info.admin_org'));
            $orgsLength = count($orgs);
            for ($i = 0; $i < $orgsLength; $i++) {
                $data[$i] = [
                    'name' => input('text'),
                    'content' => json_encode($cache, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'org_code' => $orgs[$i]
                ];
            }
        }

        $res = Db::table($this->db.'.vip_goods_sizer')->insertAll($data);
        if ($res) {
            webApi(0, 'yes');
        } else {
            webApi(0, 'no');
        }
    }

    /**
     * 高级筛选
     * 9.筛选器列表
     */
    public function sxqList()
    {
        if (session('info.admin_org') == '') {
            $where[] = ['org_code', '=', session('info.org')];
        } else {
            $where[] = ['org_code', 'in', session('info.admin_org')];
        }
        $data = Db::table($this->db.'.vip_goods_sizer')->where($where)->group('content')->order('id', 'desc')->select();
        webApi(0, '', 0, $data);
    }

    /**
     * 10.筛选器删除
     */
    public function sxqDel()
    {
        $id = intval(input('id'));
        $res = Db::table($this->db.'.vip_goods_sizer')->delete($id);
        if ($res) {
            webApi(0, 'yes');
        } else {
            webApi(0, 'no');
        }
    }

    /**
     * 11.筛选器的编辑
     */

    public function sxqEdit()
    {
        $id = intval(input('id'));
        $data = Db::table($this->db.'.vip_goods_sizer')->find($id);
        $highScreen = json_decode($data['content'], true);
        cache('viphighScreenList_'.input('access_token'), $highScreen, 3600);
        webApi(0);
    }
}