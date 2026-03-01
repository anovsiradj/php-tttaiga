function taigaLoadProjects(apiUrl, token, targetSelectId = '#projectSelect') {
	$.ajax({
		url: apiUrl + '/projects',
		data: {
			'member': taigaModel.id,
		},
		type: 'GET',
		headers: {
			'Authorization': `Bearer ${taigaToken}`,
			'Content-Type': 'application/json'
		},
		success: function (projects) {
			let html = '<option value="">All Projects</option>';
			projects.forEach(project => {
				html += `<option value="${project.id}">${project.name}</option>`;
			});
			$(targetSelectId).html(html);
		},
		error: function (xhr) {
			console.error('Failed to load projects:', xhr);
		}
	});
}

function taigaGetStatusClass(status) {
	if (!status || typeof status !== 'string') return 'secondary';
	const statusString = String(status).toLowerCase().trim();
	switch (statusString) {
		case 'new': return 'new';
		case 'ready': return 'ready';
		case 'in progress': return 'in-progress';
		case 'done': return 'done';
		case 'archived': return 'archived';
		case 'blocked': return 'blocked';
		default: return 'secondary';
	}
}

function taigaGetIssueStatusClass(status) {
	if (!status || typeof status !== 'string') return 'secondary';
	const statusLower = status.toLowerCase();
	switch (statusLower) {
		case 'new': return 'info';
		case 'in progress': return 'warning';
		case 'ready for test': return 'primary';
		case 'closed': return 'success';
		case 'needs info': return 'secondary';
		case 'rejected': return 'danger';
		case 'postponed': return 'dark';
		default: return 'secondary';
	}
}