<?php

namespace auth;

use think\Db;

class Auth
{
    public $db;
    private $trule = 'vip_auth_rule';
    private $tgroup = 'vip_auth_group';
    private $tuser = 'vip_staff';

    public function __construct($db = 'vip')
    {
        $this->db = $db;
    }

    public function check($rule, $ucode)
    {
        $sql = 'select g.rules from '.$this->db.'.'.$this->tuser.' u left join '.$this->db.'.'.$this->tgroup.' g on u.pc_auth_code = g.code where g.status = 1 and u.code = "'.$ucode.'"';
        $group = Db::query($sql);
        if (!empty($group)) {
            $group = $group[0];
            $where[] = ['status', '=', 1];
            $where[] = ['rule', '=', $rule];
            $where[] = ['code', 'in', $group['rules']];
            $isHaveThisRule = Db::table('company.'.$this->trule)->field('rule')->where($where)->find();
            if (!empty($isHaveThisRule)) {
                $return = true;
            } else {
                $return = false;
            }
        } else {
            $return = false;
        }
        return $return;
    }
}