# 禅道开源版 LDAP 插件（适用于 ZenTao Open Source 22.3）

基于 [Nuyoah/zentao_ldap](https://github.com/Nuyoah66/zentao_ldap) 二次开发，适配 禅道开源版 22.3。

> **运行环境**
>
> - ZenTao Open Source **22.3**
> - PHP **7.4**

---

## ✨ 功能特性

- 支持 LDAP / Active Directory（AD）认证登录
- 从 LDAP/AD 同步用户信息
  - 账号（Account）
  - 真实姓名（Real Name）
  - 邮箱（Email）
- 使用 LDAP 凭据登录禅道
- 支持本地管理员账号登录（账号前添加 `$`，如 `$admin`）
- 禁止 LDAP 用户修改或重置密码
- 支持嵌套 OU 用户搜索
- LDAP 属性名大小写不敏感匹配

---

## 📦 安装

1. 下载 `zentao-ldap-plugin.zip`
2. 使用管理员账号登录禅道
3. 进入：

   ```
   后台 → 插件管理 → 获得插件 → 本地安装
   ```

4. 选择插件 ZIP 文件并完成安装

---

## ⚙️ 配置

安装完成后，左侧导航栏会新增 **LDAP** 菜单。

配置完成后依次执行：

1. 保存设置
2. 测试连接
3. 同步 LDAP/AD 用户

### 示例配置

| 配置项 | 示例 |
| ------- | ---- |
| LDAP Server | `ldap://ldap.example.com:389` |
| Bind DN | `cn=admin,dc=example,dc=com` |
| Base DN | `ou=people,dc=example,dc=com` |
| Search Filter | `(objectclass=person)` |
| Username Attribute | `uid` 或 `sAMAccountName` |
| Real Name Attribute | `displayName` 或 `cn` |
| Email Attribute | `mail` |

---

## 🔐 登录方式

### LDAP 用户

直接使用 LDAP 用户名和密码登录。

例如：

```text
用户名：admin
密码：******
```

---

### 本地用户

为了区分 LDAP 用户，本地账号需要在用户名前加 `$`。

例如：

```text
用户名：$admin
密码：******
```

---

## 🔄 相比原版（18.13）修改内容

为适配 **ZenTao Open Source 22.3**，进行了以下兼容性调整：

- `identify()` 增加 `$passwordStrength` 参数
- `updatePassword()`、`resetPassword()`、`update()` 参数改为对象
- 配置保存路径使用 `__DIR__` 替代 `getModuleRoot()`
- POST 数据获取改为 `$_POST`，替代 `$this->post`
- 页面渲染通过 `oldPages` 配置兼容新版框架

---

## 🙏 致谢

本项目基于以下开源项目进行二次开发：

- **Nuyoah**  
  https://github.com/Nuyoah66/zentao_ldap

感谢原作者的开源贡献。

---

## 📄 License

Licensed under the GNU GENERAL PUBLIC LICENSE。
