<?php
/**
 * 将 LDAP 插件页面注册为旧版渲染页面，使用 view/ 目录的视图文件
 */
$config->index->oldPages[] = 'ldap-setting';
$config->index->oldPages[] = 'ldap-save';
$config->index->oldPages[] = 'ldap-test';
$config->index->oldPages[] = 'ldap-sync';
