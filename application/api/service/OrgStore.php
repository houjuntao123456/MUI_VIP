<?php

namespace app\api\service;

use think\Db;

class OrgStore
{
    private $db;
    private $org;

    public function orgs($orgs, $db)
    {
        $this->db = $db;

        $codes = explode(',', $orgs);
        $orgInfo = Db::table($db.'.vip_org')->where('code', 'in', $orgs)->select();
        if (!empty($orgInfo)) {
            $where = '';
            $orgLength = count($orgInfo);
            for ($i = 0; $i < $orgLength; $i++) {
                $where .= ' or path like "'.$orgInfo[$i]['path'].$orgInfo[$i]['code'].',%"';
            }
            $where = substr(trim($where), 2);
            $orgData = Db::table($db.'.vip_org')->field('code')->where($where)->select();
            if (!empty($orgData)) $codes = array_merge($codes, array_column($orgData, 'code')); //$codes += array_column($orgData, 'code'); 错误写法 偶尔会出错
        }
        $this->org = implode(',', array_unique($codes));
        return $this;
    }

    public function stores()
    {
        if ($this->org == '') {
            return '';
        }
        $stores = Db::table($this->db.'.vip_store')->field('code')->where('org_code', 'in', $this->org)->select();
        if (empty($stores)) {
            return '';
        }
        return implode(',', array_column($stores, 'code'));
    }
}