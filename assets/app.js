
(() => {
	let model = localStorage.getItem('taiga_user')
	if (model) {
		model = JSON.parse(model)
	}

	globalThis.taigaModel = model;
	globalThis.taigaToken = localStorage.getItem('taiga_token');
})()

function jsonToSelect(json, el, valueKey = 'id', textKey = 'name') {
	let html = '<option value=""> ... </option>';
	json.forEach(item => {
		html += `<option value="${item[valueKey]}">${item[textKey]}</option>`;
	});
	$(el).html(html);
}

$(document).ready(function () {
	$('#logoutBtn').on('click', function () {
		localStorage.removeItem('taiga_token');
		localStorage.removeItem('taiga_user');
		$.post('session_sync.php', { action: 'logout' }).always(function () {
			window.location.href = 'login.php';
		});
	});
});
