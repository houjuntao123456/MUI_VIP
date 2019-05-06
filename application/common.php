<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

/**
 * 内部网页端
 */
function webApi($code = 0, $msg = '', $count = 0, $data = []) {
    echo json_encode([
        'code'  => $code,
        'msg'   => $msg,
        'count' => $count,
        'data'  => $data
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * @param array $list 数据 必须传值
 * @param string $pk 主键 默认主键为id,可自行传入
 * @param string $pid 父键/父类标识 默认父类标识为pid,可自行传入
 * @param string $child 子类下标 children, 可自行传入
 * @return array 返回处理好的父子递进数据
 * @Description 回调函数
 */
function getTree($items, $pk = 'id', $pid = 'pid', $child = 'children') {

    [$map, $tree] = [[], []];
    foreach ($items as &$it) { 
        $map[$it[$pk]] = &$it; //数据的ID名生成新的引用索引树
    }

    foreach ($items as &$at){
        $parent = &$map[$at[$pid]];
        if($parent) {
            $parent[$child][] = &$at;
        }else{
            $tree[] = &$at;
        }
    }
    return $tree;
}

/**
 * [http_curl curl 用于请求微信接口]
 * @param  [type] $url  [请求路径 必须]
 * @param  string $type [默认get get|post]
 * @param  string $res  [json|other 传入'json' 或者任意其他字符串]
 * @param  string $data  [请求数据]
 * @return [type]       [description]
 */
function http_curl($url, $type = 'get', $res = '', $data = '') {
    // 1.初始化curl
    $ch = curl_init();
    // 2.设置curl参数
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if ($type == 'post') {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    // 3.采集
    $output = curl_exec($ch);
    // 4.关闭
    curl_close($ch);
    if ($res == 'json') {
        return json_decode($output, true);
    } else {
        return $output;
    }
}

/**
 * 正则匹配短信模板
 */
function matching($str, $a = '@var\(', $b = '\)') {
    $pattern = '/('.$a.').*?('.$b.')/is';
    preg_match_all($pattern, $str, $m);
    return $m[0];
}

/**
 * http post 请求
 */
function doHttpPost($url, $params) {
    $curl = curl_init();
    $response = false;
    do{
        // 1. 设置HTTP URL (API地址)
        curl_setopt($curl, CURLOPT_URL, $url);
        // 2. 设置HTTP HEADER (表单POST)
        $head = array(
            'Content-Type: application/x-www-form-urlencoded'
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $head);
        // 3. 设置HTTP BODY (URL键值对)
        $body = http_build_query($params);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        // 4. 调用API，获取响应结果
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_NOBODY, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curl);
        if ($response === false){
            $response = false;
            break;
        }
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($code != 200){
            $response = false;
            break;
        }
    } while (0);
    curl_close($curl);
    return $response;
}