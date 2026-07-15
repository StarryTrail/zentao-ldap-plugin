<?php
public function resetPassword($user)
{
    if($this->app->user->fromldap == true)
    {
        dao::$errors['account'][] = 'LDAP/AD用户禁止重置密码';
        return false;
    }
    return parent::resetPassword($user);
}
