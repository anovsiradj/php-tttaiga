
(() => {
	const session = globalThis.TTTaigaSession || {};
	let model = session.user || localStorage.getItem('taiga_user')
	if (typeof model === 'string' && model) {
		try {
			model = JSON.parse(model)
		} catch (e) {
			model = null
		}
	}

	globalThis.taigaModel = model;
	globalThis.taigaToken = session.authenticated ? 'session' : localStorage.getItem('taiga_token');
	globalThis.apiUrl = session.apiUrl || localStorage.getItem('taiga_api_url');
})()

function tttaigaClearAuthState() {
	localStorage.removeItem('taiga_token');
	localStorage.removeItem('taiga_user');
	localStorage.removeItem('taiga_api_url');
}

function jsonToSelect(json, el, valueKey = 'id', textKey = 'name') {
	const $el = $(el);
	$el.empty().append($('<option>', { value: '', text: ' ... ' }));
	json.forEach(item => {
		$el.append($('<option>', {
			value: item[valueKey],
			text: item[textKey]
		}));
	});
}

$(document).ready(function () {
	$(document).ajaxError(function (_event, xhr, settings) {
		const url = settings && settings.url ? String(settings.url) : '';
		if ((xhr.status === 401 || xhr.status === 403) && url.indexOf('api.php') !== -1 && !window.location.pathname.endsWith('login.php')) {
			tttaigaClearAuthState();
			window.location.href = 'login.php';
		}
	});

	$('#logoutBtn').on('click', function () {
		tttaigaClearAuthState();
		$.post('login.php', { action: 'logout' }).always(function () {
			window.location.href = 'login.php';
		});
	});
});
