<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
use app\web\service\System as Sys;

/**
 * Author lwy
 * Date 2019/01/02
 * Description 登录退出
 */
class Login extends Controller
{
    protected $ssl = 'http://';
    protected function initialize()
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $this->ssl = 'https://';
        }
    }

    /**
     * 登录
     * 1. 检测数据库连接
     * 2. 检测商家是否存在
     * 3. 检测商家是否允许登录
     * 4. 检测数据库是否存在
     * 5. 检测账号是否存在 密码是否正确 账号是否被禁用
     * 6. 检测是否需要更新
     * 7. 验证通过 生成token 识别当前浏览器UA标识，设置登陆过期时间 将商家信息 组织机构信息 门店信息 员工信息 存入session
     */
    public function login()
    {
        if (input('business') == '' || input('username') == '' || input('password') == '') {
            webApi(1, '缺少参数');
        }
        $business = trim(input('business'));
        // 获取数据库配置
        $dbConfig = Db::getConfig();
        // 判断是否能连接成功
        $link = @mysqli_connect($dbConfig['hostname'], $dbConfig['username'], $dbConfig['password']);
        if (!$link) {
            webApi(1, '数据库链接失败');
        }
        unset($link);

        // 检测商家信息
        $url = $this->ssl.config('api.url').config('api.vipCheckUrl').'?bus_account='.input('business');
        $res = json_decode(doHttpPost($url, []), true);
        if ($res['code'] != 200) {
            webApi($res['code'], $res['msg']);
        }
        if ($res['data']['status'] != 1) {
            webApi(1, '系统账户被禁用，请联系代理商');
        }

        // 身份检测通过 检测数据库信息
        $dbs = array_column(Db::query('show databases'), 'Database');
        if (!in_array($business, $dbs)) {

            // 第一次登录 没有数据库信息 请求建库
            $res = Sys::dbInit($business);
            if ($res['code'] != 200) {
                webApi(1, $res['msg']);
            }
        }

        $user = Db::table(input('business').'.vip_staff')->where('code', input('username'))->find();
        if (!$user || $user['password'] != md5(input('password'))) {
            webApi(1, '用户名或密码错误');
        }

        if ($user['pc_status'] != 1) {
            webApi(1, '该用户PC端登录权限未开启');
        }

        if ($user['exp_time'] <= time()) {
            // 账号到期 请求付费接口
            $res = Sys::expire($business, $user['code']);
            if ($res['code'] != 200) {
                webApi(1, $res['msg']);
            }
        }
        
        $loginData = [
            'access_token' => md5(microtime(1).$user['code']),
            'ua' => $_SERVER['HTTP_USER_AGENT'],
            'invalid_time' => time() + 604800
        ];
        $res = Db::table($business.'.vip_staff')->where('id', $user['id'])->update($loginData);
        if (!$res) {
            webApi(1, '登录失败,请重试');
        }
        
        $sessionInfo = [
            'db' => $business, // 数据库名 商家企业号
            'staff' => $user['code'],  // 员工工号、账号
            'store' => $user['store_code'], // 所属门店编号
            'org' => $user['org_code'], // 所属机构编号
            'admin_org' => $user['admin_org_code'] // 管理机构编号（多个以逗号隔开）
        ];
        session('info', $sessionInfo);

        // 检测系统更新
        $res = Sys::update($business);
        if ($res['code'] != 200) {
            webApi(1, $res['msg']);
        }

        webApi(0, 'yes', 0, ['access_token' => $loginData['access_token']]);
    }

    public function logout()
    {
        if (!empty(session('info'))) {
            Db::table(session('info.db').'.vip_staff')->where('code', session('info.staff'))->setField('access_token', '');
            session('info', null);
        }
        webApi(1001);
    }
}