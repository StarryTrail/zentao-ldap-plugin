function onClickTest() {
	$.post(createLink('ldap', 'test'), {
		host: $('#ldapHost').val(),
		dn: $('#ldapBindDN').val(),
		pwd: $('#ldapPassword').val(),
	}, function(data) {
		$('#testRlt').html(data);
	});
}

function sync() {
	$.post(createLink('ldap', 'sync'), function(ret) {
		alert("成功同步" + ret + "位用户信息");
	});
}

function save() {
	var form = $('form.form-condensed');
	$.post(form.attr('action'), form.serialize(), function(ret) {
		alert("保存配置成功！");
	});
	return false;
}
