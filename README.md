  禅道 22.3 LDAP 插件

  基于 Nuyoah (https://github.com/Nuyoah66/zentao_ldap) 二次开发，适配禅道开源版 22.3。
  php7.4

  功能

  - 从 LDAP/AD 服务器同步用户信息（账号、真实姓名、邮箱）
  - 使用 LDAP 凭据登录禅道
  - 本地用户通过 $ 前缀登录（如 $admin）
  - 禁止 LDAP 用户修改/重置密码
  - 支持嵌套 OU 用户搜索
  - 属性名大小写不敏感匹配

  安装

  1. 下载 zentao-ldap-plugin.zip
  2. 管理员登录禅道 → 插件管理 → 获得插件 → 本地安装
  3. 选择 zip 文件，按提示完成

  配置

  安装后左侧导航栏出现「LDAP」菜单，填写配置后：

  保存设置 → 测试连接 → 同步LDAP/AD用户

  - LDAP服务器：ldap://ldap.example.com:389
  - BindDN：cn=admin,dc=example,dc=com
  - BaseDN：ou=people,dc=example,dc=com
  - 查询条件：(objectclass=person)
  - 用户名字段：uid 或 sAMAccountName
  - 真实姓名字段：displayName 或 cn
  - 邮箱字段：mail

  登录方式

  - LDAP 用户：直接输入账号密码
  - 本地用户：账号前加 $，如 $admin

  适配说明（相比原 18.13 版本）

  - identify() 增加 $passwordStrength 参数
  - updatePassword() / resetPassword() / update() 参数改为对象
  - 配置保存路径用 __DIR__ 替代 getModuleRoot()
  - POST 数据用 $_POST 替代 $this->post
  - 页面渲染通过 oldPages 配置适配

  许可证

  Licensed under the Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
