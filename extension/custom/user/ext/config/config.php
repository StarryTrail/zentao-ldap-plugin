<?php
/** 
 * LDAP/AD用户的密码不需要MD5加密，必须设置为true，否则LDAP/AD用户会导致登录失败
 */
$config->notMd5Pwd = true;
