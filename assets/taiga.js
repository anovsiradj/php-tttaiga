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

/**
 * Collects filter values from the standard header IDs and returns a params object.
 * @returns {Object} Params for Taiga API calls.
 */
function taigaGetFilterParams() {
	const params = {};
	const q = $('#searchInput').val()?.trim();
	const project = $('#projectSelect').val();
	const status = $('#statusSelect').val();
	const epic = $('#epicSelect').val();
	const usor = $('#userStorySelect').val();
	const orderBy = $('#sortSelect').val();

	if (q) params.q = q;
	if (project) params.project = project;
	if (status) params.status = status;
	if (epic) params.epic = epic;
	if (usor) params.user_story = usor;
	if (orderBy) params.order_by = orderBy;

	return params;
}

/**
 * Initializes a Select2 instance with remote data and infinite scrolling.
 */
function taigaInitRemoteSelect2(selector, endpoint, options = {}) {
	const $el = $(selector);
	if (!$el.length) return;

	$el.select2({
		theme: 'bootstrap-5',
		ajax: {
			url: function() { return window.apiUrl + endpoint; },
			dataType: 'json',
			delay: 250,
			headers: {
				'Authorization': `Bearer ${window.taigaToken}`,
				'Content-Type': 'application/json'
			},
			data: function (params) {
				let query = {
					q: params.term,
					page: params.page || 1,
					page_size: 10
				};
				if (typeof options.additionalParams === 'function') {
					query = { ...query, ...options.additionalParams() };
				}
				return query;
			},
			processResults: function (data, params) {
				params.page = params.page || 1;
				return {
					results: data.map(item => ({
						id: item.id,
						text: options.formatText ? options.formatText(item) : (item.name || item.subject || item.full_name)
					})),
					pagination: {
						more: data.length === 10
					}
				};
			},
			cache: true
		},
		placeholder: options.placeholder || 'Select an option',
		allowClear: true,
		width: '100%'
	});
}

/**
 * Binds standardized event listeners and initializes Select2 filters.
 * @param {Function} onFilterChange Callback function to trigger on filter update.
 */
function taigaBindFilters(onFilterChange) {
	let searchTimeout;

	// Search input with debounce
	$('#searchInput').on('input', function () {
		clearTimeout(searchTimeout);
		searchTimeout = setTimeout(() => onFilterChange(1), 500);
	});

	// Initialize Select2 Dropdowns
	if ($('#projectSelect').length) {
		taigaInitRemoteSelect2('#projectSelect', '/projects', {
			placeholder: 'All Projects',
			additionalParams: () => ({ member: taigaModel.id })
		});
	}

	if ($('#epicSelect').length) {
		taigaInitRemoteSelect2('#epicSelect', '/epics', {
			placeholder: 'All Epics',
			additionalParams: () => {
				const pid = $('#projectSelect').val();
				return pid ? { project: pid } : {};
			}
		});
	}

	if ($('#userStorySelect').length) {
		taigaInitRemoteSelect2('#userStorySelect', '/userstories', {
			placeholder: 'All User Stories',
			formatText: (item) => `#${item.ref}: ${item.subject}`,
			additionalParams: () => {
				const pid = $('#projectSelect').val();
				return pid ? { project: pid } : {};
			}
		});
	}

	// Status and Sort remain standard for now unless we want remote search for them too.
	// But let's at least make them Select2 (non-remote)
	$('#statusSelect, #sortSelect').select2({
		theme: 'bootstrap-5',
		width: '100%',
		placeholder: 'Select...',
		allowClear: true
	});

	// Dropdown and Select2 changes
	const $selectors = $('#projectSelect, #statusSelect, #epicSelect, #userStorySelect, #sortSelect');
	$selectors.on('change', function () {
		// When project changes, clear dependent Select2 dropdowns
		if ($(this).attr('id') === 'projectSelect') {
			$('#epicSelect, #userStorySelect').val(null).trigger('change.select2');
		}
		onFilterChange(1);
	});

	// Refresh button
	$('#refreshBtn').on('click', function () {
		onFilterChange(1);
	});
}

/**
 * Loads items for bulk operations (update/delete) based on current filters.
 * @param {string} endpoint - API endpoint (e.g., '/epics')
 * @param {jQuery} $container - Container to hold the checkboxes or select options
 * @param {Function} formatItemCallback - Function to format how each item appears
 */
function taigaLoadBulkItems(endpoint, $container, formatItemCallback) {
	const params = {
		...taigaGetFilterParams(),
		page_size: 100 // Load more for bulk operations
	};

	$container.html('<div class="text-center p-3"><div class="spinner-border spinner-border-sm" role="status"></div> Loading...</div>');

	$.ajax({
		url: window.apiUrl + endpoint,
		type: 'GET',
		data: params,
		headers: {
			'Authorization': `Bearer ${window.taigaToken}`,
			'Content-Type': 'application/json'
		},
		success: function (items) {
			if (items.length === 0) {
				$container.html('<div class="alert alert-info p-2 mb-0">No items found matching current filters.</div>');
				return;
			}
			let html = '';
			items.forEach(item => {
				html += formatItemCallback(item);
			});
			$container.html(html);
		},
		error: function (xhr) {
			console.error(`Failed to load bulk items from ${endpoint}:`, xhr);
			$container.html('<div class="alert alert-danger p-2 mb-0">Failed to load items.</div>');
		}
	});
}

/**
 * Executes bulk operations (PATCH or DELETE) sequentially.
 * @param {string} endpoint - Base API endpoint (e.g., '/epics/')
 * @param {Array} ids - List of item IDs to process
 * @param {string} method - 'PATCH' or 'DELETE'
 * @param {Object} data - Data to send for PATCH (optional)
 * @param {Function} onComplete - Callback when all requests finished (successCount, errorCount)
 */
function taigaExecuteBulk(endpoint, items, method, data, onComplete) {
	let doneCount = 0;
	let successCount = 0;
	let errorCount = 0;

	if (items.length === 0) {
		if (typeof onComplete === 'function') onComplete(0, 0);
		return;
	}

	items.forEach(item => {
		const id = typeof item === 'object' ? item.id : item;
		const version = typeof item === 'object' ? item.version : null;
		
		// Clone data and add version if PATCHing an object with version
		// SUPPORT: data can be a function (item) => requestData
		let requestData = typeof data === 'function' ? data(item) : data;
		
		if (method === 'PATCH' && version !== null) {
			requestData = { ...requestData, version: version };
		}

		$.ajax({
			url: window.apiUrl + endpoint + id,
			type: method,
			headers: {
				'Authorization': `Bearer ${window.taigaToken}`,
				'Content-Type': 'application/json'
			},
			data: requestData ? JSON.stringify(requestData) : undefined,
			success: function () {
				successCount++;
			},
			error: function (xhr) {
				console.error(`Bulk operation failed for ${endpoint}${id}:`, xhr.responseJSON);
				errorCount++;
			},
			complete: function () {
				doneCount++;
				if (doneCount === items.length) {
					if (typeof onComplete === 'function') onComplete(successCount, errorCount);
				}
			}
		});
	});
}

// Old taigaLoad* functions removed as they are replaced by dynamic Select2 loading for filters.

/**
 * Fetches and populates a status dropdown for bulk operations, avoiding hardcoded values.
 * @param {string} type - 'epic', 'us', 'task', 'issue'
 * @param {jQuery} $select - Select element to populate
 * @param {number} projectId - ID of the project to fetch statuses for
 * @param {string} defaultText - Text for the default option (e.g. 'Select Status' or 'No Change')
 */
function taigaPopulateBulkStatuses(type, $select, projectId, defaultText = 'Select Status') {
	if (!projectId) {
		$select.html(`<option value="">${defaultText} (Select Project first)</option>`);
		return;
	}

	taigaFetchStatuses(window.apiUrl, window.taigaToken, projectId, type)
		.done(function (statuses) {
			let html = `<option value="">${defaultText}</option>`;
			statuses.forEach(status => {
				html += `<option value="${status.id}">${status.name}</option>`;
			});
			$select.html(html);
			
			// Initialize Select2
			$select.select2({
				theme: 'bootstrap-5',
				width: '100%',
				placeholder: defaultText,
				allowClear: true,
				dropdownParent: $select.closest('.modal')
			});
		})
		.fail(function (xhr) {
			console.error(`Failed to fetch statuses for ${type} in project ${projectId}:`, xhr);
			$select.html(`<option value="">Error loading statuses</option>`);
		});
}

/**
 * Data Layer: Fetches project members (memberships).
 */
function taigaFetchMembers(apiUrl, token, projectId) {
	return $.ajax({
		url: `${apiUrl}/memberships`,
		data: { project: projectId },
		type: 'GET',
		headers: {
			'Authorization': `Bearer ${token}`,
			'Content-Type': 'application/json'
		}
	});
}

/**
 * Logic-View Layer: Populates a member dropdown for bulk operations with Select2.
 * @param {jQuery} $select - Select element to populate
 * @param {number} projectId - ID of the project to fetch members for
 * @param {string} defaultText - Text for the default option
 */
function taigaPopulateBulkMembers($select, projectId, defaultText = 'Assign to...') {
	if (!projectId) {
		$select.html(`<option value="">${defaultText} (Select Project first)</option>`);
		return;
	}

	taigaFetchMembers(window.apiUrl, window.taigaToken, projectId)
		.done(function (memberships) {
			let html = `<option value="">${defaultText}</option>`;
			memberships.forEach(m => {
				const name = m.full_name || m.user_email || 'Unknown';
				html += `<option value="${m.user}">${name}</option>`;
			});
			$select.html(html);
			
			// Initialize Select2
			$select.select2({
				theme: 'bootstrap-5',
				width: '100%',
				placeholder: defaultText,
				allowClear: true,
				dropdownParent: $select.closest('.modal')
			});
		})
		.fail(function (xhr) {
			console.error(`Failed to fetch members for project ${projectId}:`, xhr);
			$select.html(`<option value="">Error loading members</option>`);
		});
}
/**
 * Updates the selection bar visibility and counts.
 * @param {number} total
 * @param {number} filtered
 * @param {number} selected
 * @param {string} totalId - The base DOM id for total count (e.g. 'totalProjects')
 * @param {string} filteredId - The base DOM id for filtered count (e.g. 'filteredProjects')
 * @param {string} selectionId - The DOM id for selected count (e.g. 'selectedProjectsCount')
 */
function taigaUpdateSelectionUI(total, filtered, selected, totalId, filteredId, selectionId) {
	selectionId = selectionId || 'selectedCount';

	$('#' + totalId + '_simple').text(total);
	$('#' + filteredId + '_simple').text(filtered);
	$('#' + totalId).text(total);
	$('#' + filteredId).text(filtered);
	$('#' + selectionId).text(selected);

	var $bulkBar = $('#bulkActionsBar');
	var $simpleBar = $('#simpleInfoBar');

	if (selected > 0) {
		$bulkBar.removeClass('d-none');
		$simpleBar.addClass('invisible');
	} else {
		$bulkBar.addClass('d-none');
		$simpleBar.removeClass('invisible');
		$('#masterCheckbox, #initialMasterCheckbox').prop('checked', false);
	}
}

/**
 * Binds master checkboxes and individual item selection logic.
 * @param {string} itemCheckboxClass - CSS class of individual checkboxes
 * @param {Function} onSelectionChange - Callback receives (checkedCount)
 */
function taigaBindSelectionLogic(itemCheckboxClass, onSelectionChange) {
	$(document).off('change', '#masterCheckbox, #initialMasterCheckbox').on('change', '#masterCheckbox, #initialMasterCheckbox', function () {
		var isChecked = $(this).is(':checked');
		$('#masterCheckbox, #initialMasterCheckbox').prop('checked', isChecked);
		$('.' + itemCheckboxClass).prop('checked', isChecked).trigger('change.selection');
		var checkedCount = isChecked ? $('.' + itemCheckboxClass).length : 0;
		if (typeof onSelectionChange === 'function') {
			onSelectionChange(checkedCount);
		}
	});

	$(document).off('change.selection', '.' + itemCheckboxClass).on('change.selection', '.' + itemCheckboxClass, function () {
		var totalCount = $('.' + itemCheckboxClass).length;
		var checkedCount = $('.' + itemCheckboxClass + ':checked').length;
		var allChecked = totalCount > 0 && checkedCount === totalCount;
		$('#masterCheckbox, #initialMasterCheckbox').prop('checked', allChecked);
		if (typeof onSelectionChange === 'function') {
			onSelectionChange(checkedCount);
		}
	});

	$('#clearSelectionBtn').off('click').on('click', function () {
		$('#masterCheckbox, #initialMasterCheckbox').prop('checked', false);
		$('.' + itemCheckboxClass).prop('checked', false).trigger('change.selection');
		if (typeof onSelectionChange === 'function') {
			onSelectionChange(0);
		}
	});
}
