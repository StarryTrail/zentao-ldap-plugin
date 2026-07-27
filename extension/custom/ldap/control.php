<?php

class ldap extends control
{
    public $referer;
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->locate(inlink('setting'));
    }

    public function setting()
    {
        $groups    = $this->dao->select('id, name, role')->from(TABLE_GROUP)->fetchAll();
        $groupList = array('' => '');
        foreach($groups as $group)
        {
            $groupList[$group->id] = $group->name;
        }

        $this->view->title      = $this->lang->ldap->common . $this->lang->colon . $this->lang->ldap->setting;
        $this->view->position[] = html::a(inlink('index'), $this->lang->ldap->common);
        $this->view->position[] = $this->lang->ldap->setting;
        $this->view->group      = $this->config->ldap->group;
        $this->view->groupList  = $groupList;

        $this->display();
    }

    /**
     * 保存LDAP/AD配置信息到config.php文件中
     */
    public function save()
    {
        if (empty($_POST)) return;

        $host     = isset($_POST['ldapHost'])     ? trim($_POST['ldapHost'])     : '';
        $version  = isset($_POST['ldapVersion'])   ? trim($_POST['ldapVersion'])   : '';
        $bindDN   = isset($_POST['ldapBindDN'])    ? trim($_POST['ldapBindDN'])    : '';
        $bindPWD  = isset($_POST['ldapPassword'])  ? trim($_POST['ldapPassword'])  : '';
        $baseDN   = isset($_POST['ldapBaseDN'])    ? trim($_POST['ldapBaseDN'])    : '';
        $filter   = isset($_POST['ldapFilter'])    ? trim($_POST['ldapFilter'])    : '';
        $uid      = isset($_POST['ldapAttr'])      ? trim($_POST['ldapAttr'])      : '';
        $mail     = isset($_POST['ldapMail'])      ? trim($_POST['ldapMail'])      : '';
        $name     = isset($_POST['ldapName'])      ? trim($_POST['ldapName'])      : '';
        $group    = isset($_POST['group'])         ? trim($_POST['group'])         : '';

        if (empty($host)) return;

        $ldapConfig = "<?php\n"
                      ."\$config->ldap = new stdclass();\n"
                      ."\$config->ldap->host = '{$host}';\n"
                      ."\$config->ldap->version = '{$version}';\n"
                      ."\$config->ldap->bindDN = '{$bindDN}';\n"
                      ."\$config->ldap->bindPWD = '{$bindPWD}';\n"
                      ."\$config->ldap->baseDN = '{$baseDN}';\n"
                      ."\$config->ldap->searchFilter = '{$filter}';\n"
                      ."\$config->ldap->uid = '{$uid}';\n"
                      ."\$config->ldap->mail = '{$mail}';\n"
                      ."\$config->ldap->name = '{$name}';\n"
                      ."\$config->ldap->group = '{$group}';\n";

        file_put_contents(__DIR__ . '/config.php', $ldapConfig);
        echo 'ok';
    }

    /**
     * 测试LDAP连接
     */
    public function test()
    {
        $host = isset($_POST['host']) ? trim($_POST['host']) : '';
        $dn   = isset($_POST['dn'])   ? trim($_POST['dn'])   : '';
        $pwd  = isset($_POST['pwd'])  ? trim($_POST['pwd'])  : '';
        $ret  = $this->ldap->identify($host, $dn, $pwd);
        echo $ret;
    }

    /**
     * 同步LDAP用户到禅道数据库
     */
    public function sync()
    {
        $config = $this->config->ldap;

        $ds = ldap_connect($config->host);
        if (!$ds) {
            echo "无法创建LDAP连接";
            return;
        }
        ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);

        $bind = ldap_bind($ds, $config->bindDN, $config->bindPWD);
        if (!$bind) {
            echo "LDAP绑定失败: " . ldap_error($ds);
            ldap_close($ds);
            return;
        }

        $attrs = [$config->uid, $config->mail, $config->name];
        $rlt = ldap_search($ds, $config->baseDN, $config->searchFilter, $attrs);
        if (!$rlt) {
            echo "LDAP搜索失败: " . ldap_error($ds);
            ldap_close($ds);
            return;
        }

        $data = ldap_get_entries($ds, $rlt);
        ldap_close($ds);

        if (!isset($data['count']) || $data['count'] == 0) {
            echo "未找到LDAP用户";
            return;
        }

        /* 建立属性名小写映射，解决大小写敏感问题 */
        $attrMap = array();
        for ($i = 0; $i < $data['count']; $i++) {
            if (!is_array($data[$i])) continue;
            foreach ($data[$i] as $key => $val) {
                $attrMap[strtolower($key)] = $key;
            }
            break;
        }

        $uidKey  = isset($attrMap[strtolower($config->uid)])  ? $attrMap[strtolower($config->uid)]  : $config->uid;
        $mailKey = isset($attrMap[strtolower($config->mail)]) ? $attrMap[strtolower($config->mail)] : $config->mail;
        $nameKey = isset($attrMap[strtolower($config->name)]) ? $attrMap[strtolower($config->name)] : $config->name;

        $count = 0;
        for ($i = 0; $i < $data['count']; $i++) {
            $account   = isset($data[$i][$uidKey][0])  ? $data[$i][$uidKey][0]  : '';
            $email     = isset($data[$i][$mailKey][0]) ? $data[$i][$mailKey][0] : '';
            $realname  = isset($data[$i][$nameKey][0]) ? $data[$i][$nameKey][0] : '';

            if (empty($account)) continue;

            $user = new stdclass();
            $user->account  = $account;
            $user->email    = empty($email) ? $account . '@test.ad' : $email;
            $user->realname = empty($realname) ? $account : $realname;

            $exists = $this->dao->select('account')->from(TABLE_USER)->where('account')->eq($account)->fetch('account');
            if ($exists) {
                /* 更新已存在的账号（包括已删除的），只更新必要字段，避免覆盖 role 等权限字段 */
                $this->dao->update(TABLE_USER)
                    ->set('deleted')->eq(0)
                    ->set('password')->eq('')
                    ->set('email')->eq($user->email)
                    ->set('realname')->eq($user->realname)
                    ->where('account')->eq($account)
                    ->autoCheck()->exec();
            } else {
                $user->gender   = 'm';
                $user->role     = 'others';
                $user->password = '';
                $this->dao->insert(TABLE_USER)->data($user)->autoCheck()->exec();
            }

            if (dao::isError()) {
                echo "数据库错误 ({$account}): " . dao::getError();
                return;
            }
            $count++;
        }

        echo $count;
    }

    /**
     * 验证单个LDAP用户凭据
     */
    public function identify($user, $pwd)
    {
        $ret = false;
        $account = $this->config->ldap->uid . '=' . $user . ',' . $this->config->ldap->baseDN;
        if (0 == strcmp('Success', $this->ldap->identify($this->config->ldap->host, $account, $pwd))) {
            $ret = true;
        }
        echo $ret;
    }
}
