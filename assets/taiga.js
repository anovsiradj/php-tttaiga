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
 * Data Layer: Fetches status objects for a specific project and type.
 * @returns {Promise}
 */
function taigaFetchStatuses(apiUrl, token, projectId, type) {
	let endpoint = '';
	switch (type) {
		case 'epic': endpoint = '/epic-statuses'; break;
		case 'us': case 'userstory': endpoint = '/userstory-statuses'; break;
		case 'task': endpoint = '/task-statuses'; break;
		case 'issue': endpoint = '/issue-statuses'; break;
		default: return Promise.reject('Invalid status type');
	}

	return $.ajax({
		url: `${apiUrl}${endpoint}`,
		data: { project: projectId },
		type: 'GET',
		headers: {
			'Authorization': `Bearer ${token}`,
			'Content-Type': 'application/json'
		}
	});
}

/**
 * Logic Layer: Extracts readable name and color from an item.
 * @param {object} item - The Taiga item (epic, us, task, issue)
 * @returns {object} { name, color }
 */
function taigaGetStatusInfo(item) {
	if (item.status_extra) {
		return {
			name: item.status_extra.name || 'Unknown',
			color: item.status_extra.color || '#666666'
		};
	}
	
	// Fallback if status_extra is missing
	return {
		name: item.status || 'Unknown',
		color: '#666666'
	};
}

/**
 * View Layer: Generates HTML for a status badge.
 * @param {object} statusInfo - { name, color }
 * @returns {string} HTML string
 */
function taigaRenderStatusBadge(statusInfo) {
	const color = statusInfo.color || '#666666';
	// Simple luminance check for text color
	const r = parseInt(color.slice(1, 3), 16);
	const g = parseInt(color.slice(3, 5), 16);
	const b = parseInt(color.slice(5, 7), 16);
	const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
	const textColor = luminance > 0.5 ? '#000000' : '#ffffff';

	return `<span class="badge status-badge" style="background-color: ${color}; color: ${textColor}; border: 1px solid rgba(0,0,0,0.1);">${statusInfo.name}</span>`;
}

/**
 * View Layer: Updates select elements with status options.
 * @param {jQuery} $select - The jQuery select element
 * @param {Array} statuses - List of status objects
 */
function taigaPopulateStatusDropdown($select, statuses) {
	let html = '<option value="">All Statuses</option>';
	statuses.forEach(status => {
		html += `<option value="${status.id}">${status.name}</option>`;
	});
	$select.html(html);
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