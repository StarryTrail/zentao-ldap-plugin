<?php 
$config->ldap = new stdclass();
$config->ldap->host = 'ldap://127.0.0.1:389';
$config->ldap->version = '3';
$config->ldap->bindDN = 'cn=administrator,cn=users,dc=test,dc=ad';
$config->ldap->bindPWD = '12356';
$config->ldap->baseDN = 'ou=company,dc=test,dc=ad';
$config->ldap->searchFilter = '(objectclass=person)';
$config->ldap->uid = 'samaccountname';
$config->ldap->mail = 'mail';
$config->ldap->name = 'cn';
$config->ldap->group = '11';
