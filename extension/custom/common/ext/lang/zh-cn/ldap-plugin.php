<?php
/**
 * 设置顶级导航条
 **/
$lang->ldap	=	new stdclass();
$lang->ldap->common	=	'LDAP';
$lang->mainNav->ldap	=	"<i class='icon icon-cog-outline'></i>{$lang->ldap->common}|ldap|setting|";
$lang->mainNav->menuOrder[90] = 'ldap';