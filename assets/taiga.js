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

/**
 * Renders Bootstrap pagination dynamically based on Taiga API headers.
 * 
 * @param {XMLHttpRequest} xhr - The AJAX response object containing headers.
 * @param {string} containerSelector - jQuery selector for the pagination ul container.
 * @param {function} onPageChange - Callback function triggered when a page is clicked, receives (pageNumber).
 */
function taigaRenderPagination(xhr, containerSelector, onPageChange) {
	const isPaginated = xhr.getResponseHeader('X-Paginated');
	const $container = $(containerSelector);

	if (isPaginated !== 'true') {
		$container.empty();
		return;
	}

	const total = parseInt(xhr.getResponseHeader('X-Pagination-Count')) || 0;
	const byPage = parseInt(xhr.getResponseHeader('X-Paginated-By')) || 1;
	const current = parseInt(xhr.getResponseHeader('X-Pagination-Current')) || 1;
	const totalPages = Math.ceil(total / byPage);

	if (totalPages <= 1) {
		$container.empty();
		return;
	}

	let paginationHtml = '';

	function getPageItems(current, total) {
		if (total <= 7) {
			return Array.from({ length: total }, (_, i) => i + 1);
		}
		
		if (current <= 4) {
			return [1, 2, 3, 4, 5, '...', total];
		}
		
		if (current >= total - 3) {
			return [1, '...', total - 4, total - 3, total - 2, total - 1, total];
		}
		
		return [1, '...', current - 1, current, current + 1, '...', total];
	}
	
	const pageItems = getPageItems(current, totalPages);
	
	pageItems.forEach(item => {
		if (item === '...') {
			paginationHtml += `
				<li class="page-item disabled">
					<span class="page-link">...</span>
				</li>
			`;
		} else {
			paginationHtml += `
				<li class="page-item ${item === current ? 'active' : ''}">
					<a class="page-link" href="#" data-page="${item}">${item}</a>
				</li>
			`;
		}
	});

	$container.html(paginationHtml);

	// Pagination click handler
	$container.find('.page-link').off('click').on('click', function (e) {
		e.preventDefault();
		const page = $(this).data('page');
		if (page && !$(this).parent().hasClass('active')) {
			if (typeof onPageChange === 'function') {
				onPageChange(page);
			}
			$('html, body').animate({ scrollTop: 0 }, 'slow');
		}
	});
}