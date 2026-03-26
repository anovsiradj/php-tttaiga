
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

	// Handle project selection change to load statuses
	$('#projectSelect').on('change', function () {
		const projectId = $(this).val();
		const $statusSelect = $('#statusSelect');
		const type = $statusSelect.data('status-type');
		const apiUrl = localStorage.getItem('taiga_api_url');
		const token = localStorage.getItem('taiga_token');

		if (projectId && type && apiUrl && token) {
			$statusSelect.html('<option value="">Loading statuses...</option>');
			taigaFetchStatuses(apiUrl, token, projectId, type)
				.then(statuses => {
					taigaPopulateStatusDropdown($statusSelect, statuses);
				})
				.catch(err => {
					console.error('Failed to load statuses:', err);
					$statusSelect.html('<option value="">Error loading statuses</option>');
				});
		} else {
			$statusSelect.html('<option value="">All Statuses</option>');
		}
	});
});
