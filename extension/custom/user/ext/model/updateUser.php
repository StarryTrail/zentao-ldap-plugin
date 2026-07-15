<?php
public function update($user)
{
    if((!empty($_POST['password1']) || !empty($_POST['password2'])) && $this->app->user->fromldap == true)
    {
        dao::$errors['originalPassword'][] = 'LDAP/AD用户禁止修改密码';
        return false;
    }
    return parent::update($user);
}
