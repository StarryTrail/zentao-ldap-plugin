<?php
/**
 * 覆盖登录验证方法，增加 LDAP/AD 认证路径。
 * - 账号以 $ 开头：去掉前缀走禅道原生密码验证（本地管理员逃生通道）
 * - 账号在 LDAP 中存在：走 LDAP 认证
 * - 其他情况：拒绝登录（所有用户必须通过 LDAP 或 $ 前缀登录）
 */
public function identify($account, $password, $passwordStrength = 0)
{
    if(!$account || !$password) return false;

    /* 获取用户信息，确认账号在本地数据库中存在且未被删除。*/
    $record = $this->dao->select('*')->from(TABLE_USER)
        ->where('account')->eq($account)
        ->andWhere('deleted')->eq(0)
        ->fetch();

    /* $ 前缀用户：去掉前缀后走禅道原生密码验证。*/
    if(0 == strcmp('$', substr($account, 0, 1)))
    {
        return parent::identify(ltrim($account, '$'), $password, $passwordStrength);
    }

    /* LDAP 用户验证。*/
    if($record)
    {
        $ldap = $this->loadModel('ldap');
        $ldap_account = $ldap->getUserDn($this->config->ldap, $account);
        if($ldap_account)
        {
            $pass = $ldap->identify($this->config->ldap->host, $ldap_account, $password);
            if(0 == strcmp('Success', $pass))
            {
                $user = $record;
                /* 使用数据库中的密码，供禅道内部二次密码验证使用。*/
                $user->password = $record->password;

                $ip   = helper::getRemoteIp();
                $last = helper::now();

                if($this->app->isServing())
                {
                    $this->dao->update(TABLE_USER)->set('visits = visits + 1')->set('ip')->eq($ip)->set('last')->eq($last)->where('account')->eq($account)->exec();
                    $todoList = $this->dao->select('*')->from(TABLE_TODO)->where('cycle')->eq(1)->andWhere('deleted')->eq('0')->andWhere('account')->eq($user->account)->fetchAll('id');
                    if($todoList) $this->loadModel('todo')->createByCycle($todoList);
                }

                $user->last  = $last;
                $user->admin = strpos($this->app->company->admins, ",{$user->account},") !== false;
                $user = $this->checkNeedModifyPassword($user, $passwordStrength);

                if($user->avatar)
                {
                    $avatarRoot = substr($user->avatar, 0, strpos($user->avatar, 'data/upload/'));
                    if($this->config->webRoot != $avatarRoot) $user->avatar = substr_replace($user->avatar, $this->config->webRoot, 0, strlen($avatarRoot));
                }

                return $user;
            }
        }
    }

    return false;
}
