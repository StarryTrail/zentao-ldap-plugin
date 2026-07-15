<?php
?>
<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<div id='mainContent' class='main-content'>
  <div class='center-block'>
    <div class='main-header'>
      <h2><?php echo $lang->ldap->common . $lang->colon . $lang->ldap->setting;?></h2>
    </div>
    <form class='form-condensed' method='post' action='<?php echo inlink('save');?>' onsubmit='return false;'>
      <table class='table table-form'>
        <tr>
          <th class='w-120px'><?php echo $lang->ldap->host;?></th>
          <td><?php echo html::input('ldapHost', $config->ldap->host, "class='form-control'");?></td>
        </tr>
        <tr>
          <th><?php echo $lang->ldap->version;?></th>
          <td><?php echo html::input('ldapVersion', $config->ldap->version, "class='form-control'");?></td>
        </tr>
        <tr>
          <th><?php echo $lang->ldap->bindDN;?></th>
          <td><?php echo html::input('ldapBindDN', $config->ldap->bindDN, "class='form-control'");?></td>
        </tr>
        <tr>
          <th><?php echo $lang->ldap->password;?></th>
          <td><?php echo html::password('ldapPassword', $config->ldap->bindPWD, "class='form-control'");?></td>
        </tr>
        <tr>
          <th></th>
          <td>
            <label id='testRlt'></label>
            <button type='button' onclick='onClickTest()' class='btn'><?php echo $lang->ldap->test;?></button>
          </td>
        </tr>
        <tr>
          <th><?php echo $lang->ldap->baseDN;?></th>
          <td><?php echo html::input('ldapBaseDN', $config->ldap->baseDN, "class='form-control'");?></td>
        </tr>
        <tr>
          <th><?php echo $lang->ldap->filter;?></th>
          <td><?php echo html::input('ldapFilter', $config->ldap->searchFilter, "class='form-control'");?></td>
        </tr>
        <tr>
          <th><?php echo $lang->ldap->attributes;?></th>
          <td><?php echo html::input('ldapAttr', $config->ldap->uid, "class='form-control'");?></td>
        </tr>
        <tr>
          <th><?php echo $lang->ldap->mail;?></th>
          <td><?php echo html::input('ldapMail', $config->ldap->mail, "class='form-control'");?></td>
        </tr>
        <tr>
          <th><?php echo $lang->ldap->name;?></th>
          <td><?php echo html::input('ldapName', $config->ldap->name, "class='form-control'");?></td>
        </tr>
        <tr>
          <th><?php echo $lang->ldap->group;?></th>
          <td>
            <?php echo html::select('group', $groupList, (!empty($group) ? $group : ''), "class='form-control chosen'");?>
            <span class='text-muted'><?php echo $lang->ldap->placeholder->group;?></span>
          </td>
        </tr>
        <tr class='text-center form-actions'>
          <td colspan='2'>
            <?php
            echo html::submitButton($lang->ldap->save, 'onclick="save()"');
            echo html::commonButton($lang->ldap->sync, 'onclick="sync()"');
            ?>
          </td>
        </tr>
      </table>
    </form>
  </div>
</div>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
<script>
var testUrl = '<?php echo inlink('test');?>';
var syncUrl = '<?php echo inlink('sync');?>';

function onClickTest() {
    $.post(testUrl, {
        host: $('#ldapHost').val(),
        dn: $('#ldapBindDN').val(),
        pwd: $('#ldapPassword').val(),
    }, function(data) {
        $('#testRlt').html(data);
    });
}

function sync() {
    $.ajax({
        url: syncUrl,
        type: 'POST',
        success: function(ret) {
            if (/^\d+$/.test(ret)) {
                alert("成功同步" + ret + "位用户信息");
            } else {
                alert("同步结果: " + ret);
            }
        },
        error: function(xhr, status, error) {
            alert("同步请求失败: " + status + " " + error);
        }
    });
}

function save() {
    var form = $('form.form-condensed');
    var url = form.attr('action');
    var data = form.serialize();
    $.ajax({
        url: url,
        type: 'POST',
        data: data,
        success: function(ret) {
            if (ret === 'ok') {
                alert("保存配置成功！");
            } else {
                alert("保存异常: " + ret);
            }
        },
        error: function(xhr, status, error) {
            alert("保存失败: " + status + " " + error);
        }
    });
    return false;
}
</script>
