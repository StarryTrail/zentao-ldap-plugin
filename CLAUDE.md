# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 项目概述

禅道开源版 22.3 LDAP/AD 认证插件。支持从 LDAP/AD 服务器同步用户信息（账号、真实姓名、邮箱），并使用 LDAP 凭据登录禅道。本地用户可在账号前加 `$` 符号以本地密码登录。

由原 18.13 版本适配至 22.3，主要适配点：禅道 22.3 中 user 模块的 `identify`、`updatePassword`、`resetPassword`、`update` 方法签名变更。

## 目录结构

```
extension/custom/
├── ldap/                        # 插件主模块：LDAP 配置与同步
│   ├── config.php               # 默认 LDAP 连接参数
│   ├── control.php              # 控制器：setting/save/test/sync/identify
│   ├── model.php                # 模型：LDAP 连接、用户搜索、同步到数据库
│   ├── view/setting.html.php    # 配置页面视图
│   ├── js/setting.js            # 前端：测试连接、保存、同步
│   └── lang/zh-cn.php           # 中文语言包
├── user/ext/                    # 对禅道 user 模块的扩展/覆盖
│   ├── config/config.php        # 设置 notMd5Pwd=true（LDAP 用户密码不 MD5）
│   └── model/
│       ├── identify.php         # 覆盖登录验证：$ 前缀走本地，否则走 LDAP
│       ├── resetPassword.php    # 禁止 LDAP 用户重置密码
│       ├── updatePassword.php   # 禁止 LDAP 用户修改密码
│       └── updateUser.php       # 禁止 LDAP 用户修改密码（用户编辑页）
├── common/ext/lang/zh-cn/ldap-plugin.php   # 注册顶级导航 "LDAP"
└── group/ext/lang/zh-cn/ldap-resource.php  # 注册权限资源
```

## 开发与部署

本项目是禅道扩展插件，无独立构建系统。开发工作流：

- **安装部署**：将 `extension/custom/` 目录打包为 `.zip`，通过禅道后台「插件管理 → 获得插件 → 本地安装」上传安装。
- **手动安装**：也可直接将 `extension/custom/` 下的文件复制到禅道安装目录的 `extension/custom/` 下。
- **开发调试**：修改文件后直接刷新页面生效，无需构建步骤。禅道扩展框架在运行时动态加载 `ext/model/*.php` 文件。
- **PHP 环境要求**：PHP 需启用 `ldap` 扩展（`php -m | grep ldap` 验证）。
- **测试 LDAP 连接**：安装插件后，访问禅道左侧导航「LDAP」页面，填写服务器信息后点击「测试连接」按钮。

## 架构说明

遵循禅道扩展框架的三层模式：

- **control.php** — 控制器层，处理 HTTP 请求。`setting()` 展示配置页，`save()` 通过 `file_put_contents` 将配置写入模块自身的 `config.php`，`test()` 测试 LDAP 连接，`sync()` 触发手动同步，`identify()` 验证单个 LDAP 用户凭据。
- **model.php** — 数据访问层，直接调用 PHP `ldap_*` 函数与 LDAP 服务器交互。`getUsers()` 搜索 LDAP 用户，`sync2db()` 将 LDAP 用户写入禅道 `TABLE_USER` 表（存在则更新/恢复已删除用户，不存在则插入）。
- **view/** — 使用禅道内置的 `html::input()`、`html::select()` 等辅助函数渲染表单。

### ext/model 覆盖机制

这是插件最核心的架构模式。禅道框架在运行时会将 `模块/ext/model/*.php` 中定义的方法合并到对应模块的 model 中，实现方法覆盖。

以 `user/ext/model/identify.php` 为例：该文件只定义了 `identify()` 方法体（无 class 声明），框架会将其注入到禅道原生 `userModel` 中，替换原有 `identify()` 方法。覆盖方法通过 `parent::methodName()` 调用原生逻辑。

本插件通过此机制覆盖了 user 模块的 4 个方法：
- `identify.php` — 替换登录验证流程，增加 LDAP 认证路径
- `resetPassword.php` — 拦截密码重置，LDAP 用户禁止操作
- `updatePassword.php` — 拦截密码修改，LDAP 用户禁止操作
- `updateUser.php` — 拦截用户编辑中的密码修改，LDAP 用户禁止操作

## 关键行为

- **双重登录路径**：`identify.php` 检查账号首字符是否为 `$`。是则去掉前缀调用 `parent::identify()` 走本地密码验证，否则查询 LDAP 用户 DN 后向 LDAP 服务器验证。方法签名适配 22.3：`identify($account, $password, $passwordStrength = 0)`。
- **密码保护**：
  - `config.php` 设置 `$config->notMd5Pwd = true`，让前端登录时不使用 MD5 对密码做客户端哈希处理。
  - `resetPassword.php`：覆盖 `resetPassword($user)`，禁止 `fromldap=true` 的用户重置密码。
  - `updatePassword.php`：覆盖 `updatePassword($user)`，`$user` 为对象（22.3 签名变更），禁止 LDAP 用户修改密码。
  - `updateUser.php`：覆盖 `update($user)`，`$user` 为对象，禁止 LDAP 用户在编辑资料页修改密码。
- **配置持久化**：`control.php` 的 `save()` 方法通过 `$this->app->getModuleRoot() . 'config.php'` 写入模块自身配置目录。
- **邮箱空值处理**：同步时若 LDAP 用户邮箱为空，自动生成 `{account}@test.ad` 作为默认邮箱。
- **本地用户逃生通道**：任何以 `$` 开头的账号名都会绕过 LDAP 认证，去掉 `$` 后走禅道原生密码验证。这是管理员在 LDAP 服务不可用时的应急登录方式。

## 依赖环境

- PHP 需启用 `ldap` 扩展
- 禅道开源版 22.3
- 目标 LDAP/AD 服务器（如 Active Directory）
