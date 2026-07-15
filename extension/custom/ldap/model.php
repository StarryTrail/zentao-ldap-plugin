<?php
class ldapModel extends model
{
    public function getUserDn($config, $account)
    {
        $ret = null;
        $ds = ldap_connect($config->host);
        if (!$ds) return $ret;

        ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);

        $bind = @ldap_bind($ds, $config->bindDN, $config->bindPWD);
        if (!$bind) {
            ldap_close($ds);
            return $ret;
        }

        $filter = "({$config->uid}={$account})";
        $rlt = @ldap_search($ds, $config->baseDN, $filter, ['dn']);
        if ($rlt) {
            $count = ldap_count_entries($ds, $rlt);
            if ($count > 0) {
                $data = ldap_get_entries($ds, $rlt);
                $ret = $data[0]['dn'];
            }
        }

        ldap_close($ds);
        return $ret;
    }

    public function identify($host, $dn, $pwd)
    {
        $ds = ldap_connect($host);
        if (!$ds) {
            return '无法连接LDAP服务器';
        }

        ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);

        $bind = @ldap_bind($ds, $dn, $pwd);
        $error = ldap_error($ds);
        ldap_close($ds);

        return $bind ? 'Success' : $error;
    }

    public function getUsers($config)
    {
        $ds = ldap_connect($config->host);
        if (!$ds) {
            return array('error' => '无法创建LDAP连接: ' . $config->host);
        }

        ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);

        $bindResult = ldap_bind($ds, $config->bindDN, $config->bindPWD);
        if (!$bindResult) {
            $error = ldap_error($ds);
            ldap_close($ds);
            return array('error' => "LDAP绑定失败 (host={$config->host}, bindDN={$config->bindDN}): {$error}");
        }

        $attrs = [$config->uid, $config->mail, $config->name];
        $rlt = ldap_search($ds, $config->baseDN, $config->searchFilter, $attrs);
        if (!$rlt) {
            $error = ldap_error($ds);
            ldap_close($ds);
            return array('error' => "LDAP搜索失败 (baseDN={$config->baseDN}): {$error}");
        }

        $data = ldap_get_entries($ds, $rlt);
        ldap_close($ds);
        return $data;
    }

    public function sync2db($config)
    {
        $ldapUsers = $this->getUsers($config);

        /* 检查LDAP查询是否出错 */
        if (isset($ldapUsers['error'])) {
            return $ldapUsers['error'];
        }

        if (!isset($ldapUsers['count']) || $ldapUsers['count'] == 0) {
            return '未找到LDAP用户';
        }

        $user = new stdclass();
        $group = new stdclass();
        $account = '';
        $i=0;
        for (; $i < $ldapUsers['count']; $i++) {
            $user->account = $ldapUsers[$i][$config->uid][0];
            if (empty($ldapUsers[$i][$config->mail][0])) {
                $user->email = $user->account . '@test.ad';
            } else {
                $user->email = $ldapUsers[$i][$config->mail][0];
            }
            $user->realname = $ldapUsers[$i][$config->name][0];
            $group->group = (!empty($config->group) ? $config->group : $this->config->ldap->group);


            $account = $this->dao->select('*')->from(TABLE_USER)->where('account')->eq($user->account)->fetch('account');
            if ($account == $user->account) {
                $user->deleted = 0;
                $this->dao->update(TABLE_USER)->data($user)->where('account')->eq($user->account)->autoCheck()->exec();
            } else {
                $user->gender = 'm';
                $user->role = 'others';
                $this->dao->insert(TABLE_USER)->data($user)->autoCheck()->exec();
            }

            if (dao::isError()) {
                echo js::error(dao::getError());
                die(js::reload('parent'));
            }
        }

        return $i;
    }
}
