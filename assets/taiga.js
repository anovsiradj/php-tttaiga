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