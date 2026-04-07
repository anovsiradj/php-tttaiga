function taigaLoadProjects(apiUrl, token, targetSelectId = '#projectSelect') {
	$.ajax({
		url: `api.php/projects`,
		data: {
			'member': taigaModel?.id ?? null,
		},
		type: 'GET',
		dataType: 'json',
		headers: {
			'Authorization': `Bearer ${window.taigaToken}`,
			'Content-Type': 'application/json',
			'X-Taiga-Api-Url': apiUrl
		},
		success: function (projects) {
			let html = '<option value="">(Semua Project)</option>';
			if (Array.isArray(projects)) {
				projects.forEach(project => {
					html += `<option value="${project.id}">${project.name}</option>`;
				});
			} else {
				console.warn('taigaLoadProjects: projects is not an array', projects);
			}
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

	window.taigaCache = window.taigaCache || {};
	window.taigaCache.statusByProjectType = window.taigaCache.statusByProjectType || {};
	window.taigaCache.statusFetchedAt = window.taigaCache.statusFetchedAt || {};

	const cacheKey = `${type}:${String(projectId)}`;
	const cached = window.taigaCache.statusByProjectType[cacheKey];
	const fetchedAt = window.taigaCache.statusFetchedAt[cacheKey];
	const ttlMs = 5 * 60 * 1000;

	if (cached && fetchedAt && (Date.now() - fetchedAt) < ttlMs) {
		return $.Deferred().resolve(cached).promise();
	}

	return $.ajax({
		url: `api.php${endpoint}`,
		data: { project: projectId },
		type: 'GET',
		dataType: 'json',
		headers: {
			'Authorization': `Bearer ${token}`,
			'Content-Type': 'application/json',
			'X-Taiga-Api-Url': apiUrl
		}
	}).done(function (statuses) {
		window.taigaCache.statusByProjectType[cacheKey] = statuses;
		window.taigaCache.statusFetchedAt[cacheKey] = Date.now();
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
	let html = '<option value="">(Semua Status)</option>';
	if (Array.isArray(statuses)) {
		statuses.forEach(status => {
			html += `<option value="${status.id}">${status.name}</option>`;
		});
	} else {
		console.warn('taigaPopulateStatusDropdown: statuses is not an array', statuses);
	}
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
	const assignedTo = $('#assignedToSelect').val();
	const orderBy = $('#sortSelect').val();

	if (q) params.q = q;
	if (project) params.project = project;
	if (status) params.status = status;
	if (epic) params.epic = epic;
	if (usor) params.user_story = usor;
	if (assignedTo) params.assigned_to = assignedTo;
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
			url: function() {
				return 'api.php' + endpoint;
			},
			dataType: 'json',
			delay: 250,
			headers: {
				'Authorization': `Bearer ${window.taigaToken}`,
				'Content-Type': 'application/json',
				'X-Taiga-Api-Url': window.apiUrl
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
		width: '100%',
		dropdownParent: options.dropdownParent || ($el.closest('.modal').length ? $el.closest('.modal') : $(document.body))
	});
}

function taigaInitStaticSelect2($el, options = {}) {
	if (!$el || !$el.length) return;

	if ($el.data('select2')) {
		$el.select2('destroy');
	}

	$el.select2({
		theme: 'bootstrap-5',
		placeholder: options.placeholder || 'Select an option',
		allowClear: true,
		width: '100%',
		dropdownParent: options.dropdownParent || ($el.closest('.modal').length ? $el.closest('.modal') : $(document.body))
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
			placeholder: '(Semua Project)',
			additionalParams: () => ({ member: taigaModel.id })
		});
	}

	if ($('#epicSelect').length) {
		taigaInitRemoteSelect2('#epicSelect', '/epics', {
			placeholder: '(Semua Epik)',
			additionalParams: () => {
				const pid = $('#projectSelect').val();
				return pid ? { project: pid } : {};
			}
		});
	}

	if ($('#userStorySelect').length) {
		taigaInitRemoteSelect2('#userStorySelect', '/userstories', {
			placeholder: '(Semua Usor)',
			formatText: (item) => `#${item.ref}: ${item.subject}`,
			additionalParams: () => {
				const pid = $('#projectSelect').val();
				return pid ? { project: pid } : {};
			}
		});
	}

	if ($('#assignedToSelect').length) {
		taigaInitStaticSelect2($('#assignedToSelect'), {
			placeholder: '(Semua User)'
		});

		const refreshAssignedToFilter = function () {
			const pid = $('#projectSelect').val();
			const $assigned = $('#assignedToSelect');
			const dropdownParent = $assigned.closest('.modal').length ? $assigned.closest('.modal') : $(document.body);

			$assigned.val(null);

			if (!pid) {
				$assigned.prop('disabled', true);
				$assigned.html('<option value="">(Pilih Project dulu)</option>');
				taigaInitStaticSelect2($assigned, {
					placeholder: '(Pilih Project dulu)',
					dropdownParent: dropdownParent
				});
				return;
			}

			$assigned.prop('disabled', true);
			$assigned.html('<option value="">Loading...</option>');
			taigaInitStaticSelect2($assigned, {
				placeholder: '(Semua User)',
				dropdownParent: dropdownParent
			});

			taigaFetchMembers(window.apiUrl, window.taigaToken, pid)
				.done(function (memberships) {
					let html = '<option value="">(Semua User)</option>';
					if (Array.isArray(memberships)) {
						memberships.forEach(m => {
							const name = m.full_name || m.user_email || 'Unknown';
							html += `<option value="${m.user}">${name}</option>`;
						});
					}
					$assigned.html(html);
					$assigned.prop('disabled', false);
					taigaInitStaticSelect2($assigned, {
						placeholder: '(Semua User)',
						dropdownParent: dropdownParent
					});
				})
				.fail(function () {
					$assigned.html('<option value="">Error loading users</option>');
					$assigned.prop('disabled', true);
					taigaInitStaticSelect2($assigned, {
						placeholder: 'Error loading users',
						dropdownParent: dropdownParent
					});
				});
		};

		refreshAssignedToFilter();

		$('#projectSelect').off('change.assignedFilter').on('change.assignedFilter', function () {
			refreshAssignedToFilter();
		});
	}

	if ($('#statusSelect').length) {
		taigaInitStaticSelect2($('#statusSelect'), {
			placeholder: '(Pilih Project dulu)'
		});

		const refreshStatusFilter = function () {
			const pid = $('#projectSelect').val();
			const $status = $('#statusSelect');
			const type = $status.data('status-type');
			const dropdownParent = $status.closest('.modal').length ? $status.closest('.modal') : $(document.body);

			$status.val(null);

			if (!pid) {
				$status.prop('disabled', true);
				$status.html('<option value="">(Pilih Project dulu)</option>');
				taigaInitStaticSelect2($status, {
					placeholder: '(Pilih Project dulu)',
					dropdownParent: dropdownParent
				});
				return;
			}

			if (!type) {
				$status.prop('disabled', true);
				$status.html('<option value="">Status type missing</option>');
				taigaInitStaticSelect2($status, {
					placeholder: 'Status type missing',
					dropdownParent: dropdownParent
				});
				return;
			}

			$status.prop('disabled', true);
			$status.html('<option value="">Loading...</option>');
			taigaInitStaticSelect2($status, {
				placeholder: '(Semua Status)',
				dropdownParent: dropdownParent
			});

			taigaFetchStatuses(window.apiUrl, window.taigaToken, pid, type)
				.done(function (statuses) {
					taigaPopulateStatusDropdown($status, statuses);
					$status.prop('disabled', false);
					taigaInitStaticSelect2($status, {
						placeholder: '(Semua Status)',
						dropdownParent: dropdownParent
					});
				})
				.fail(function () {
					$status.html('<option value="">Error loading statuses</option>');
					$status.prop('disabled', true);
					taigaInitStaticSelect2($status, {
						placeholder: 'Error loading statuses',
						dropdownParent: dropdownParent
					});
				});
		};

		refreshStatusFilter();
		$('#projectSelect').off('change.statusFilter').on('change.statusFilter', function () {
			refreshStatusFilter();
		});
	}

	$('#sortSelect').select2({
		theme: 'bootstrap-5',
		width: '100%',
		placeholder: 'Urutkan...',
		allowClear: true
	});

	// Dropdown and Select2 changes
	const $selectors = $('#projectSelect, #statusSelect, #epicSelect, #userStorySelect, #assignedToSelect, #sortSelect');
	$selectors.on('change', function () {
		// When project changes, clear dependent Select2 dropdowns
		if ($(this).attr('id') === 'projectSelect') {
			$('#epicSelect, #userStorySelect').val(null).trigger('change.select2');
			$('#statusSelect').val(null).trigger('change.select2');
			$('#assignedToSelect').val(null).trigger('change.select2');
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
		url: 'api.php' + endpoint,
		type: 'GET',
		data: params,
		headers: {
			'Authorization': `Bearer ${window.taigaToken}`,
			'Content-Type': 'application/json',
			'X-Taiga-Api-Url': window.apiUrl
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
			url: 'api.php' + endpoint + id,
			type: method,
			headers: {
				'Authorization': `Bearer ${window.taigaToken}`,
				'Content-Type': 'application/json',
				'X-Taiga-Api-Url': window.apiUrl
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
			if (Array.isArray(statuses)) {
				statuses.forEach(status => {
					html += `<option value="${status.id}">${status.name}</option>`;
				});
			} else {
				console.warn('taigaPopulateBulkStatuses: statuses is not an array', statuses);
			}
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
	window.taigaCache = window.taigaCache || {};
	window.taigaCache.memberByProject = window.taigaCache.memberByProject || {};
	window.taigaCache.memberFetchedAt = window.taigaCache.memberFetchedAt || {};

	const cacheKey = String(projectId);
	const cached = window.taigaCache.memberByProject[cacheKey];
	const fetchedAt = window.taigaCache.memberFetchedAt[cacheKey];
	const ttlMs = 5 * 60 * 1000;

	if (cached && fetchedAt && (Date.now() - fetchedAt) < ttlMs) {
		return $.Deferred().resolve(cached).promise();
	}

	return $.ajax({
		url: `api.php/memberships`,
		data: { project: projectId },
		type: 'GET',
		dataType: 'json',
		headers: {
			'Authorization': `Bearer ${token}`,
			'Content-Type': 'application/json',
			'X-Taiga-Api-Url': apiUrl
		}
	}).done(function (memberships) {
		window.taigaCache.memberByProject[cacheKey] = memberships;
		window.taigaCache.memberFetchedAt[cacheKey] = Date.now();
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
			if (Array.isArray(memberships)) {
				memberships.forEach(m => {
					const name = m.full_name || m.user_email || 'Unknown';
					html += `<option value="${m.user}">${name}</option>`;
				});
			} else {
				console.warn('taigaPopulateBulkMembers: memberships is not an array', memberships);
			}
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

	if (totalId) {
		$('#' + totalId + '_simple').text(total);
		$('#' + totalId).text(total);
	}
	if (filteredId) {
		$('#' + filteredId + '_simple').text(filtered);
		$('#' + filteredId).text(filtered);
	}
	if (selectionId) {
		$('#' + selectionId).text(selected);
	}

	var $bulkBar = $('#bulkActionsBar');
	if ($bulkBar.length) {
		$bulkBar.toggleClass('has-selection', selected > 0);
	}

	$('#clearSelectionBtn').prop('disabled', selected === 0);
	$('#bulkActionsDropdown').prop('disabled', selected === 0);

	if (selected === 0) {
		$('#masterCheckbox').prop('checked', false);
	}
}

/**
 * Binds master checkboxes and individual item selection logic.
 * @param {string} itemCheckboxClass - CSS class of individual checkboxes
 * @param {Function} onSelectionChange - Callback receives (checkedCount)
 */
function taigaBindSelectionLogic(itemCheckboxClass, onSelectionChange) {
	$(document).off('change', '#masterCheckbox').on('change', '#masterCheckbox', function () {
		var isChecked = $(this).is(':checked');
		$('#masterCheckbox').prop('checked', isChecked);
		$('.' + itemCheckboxClass).prop('checked', isChecked).trigger('change.selection');
		var checkedCount = isChecked ? $('.' + itemCheckboxClass).length : 0;
		if (typeof onSelectionChange === 'function') {
			onSelectionChange(checkedCount);
		}
	});

	$(document).off('change.selection', '.' + itemCheckboxClass).on('change.selection', '.' + itemCheckboxClass, function () {
		$(this).closest('.card').toggleClass('taiga-selected', $(this).is(':checked'));
		var totalCount = $('.' + itemCheckboxClass).length;
		var checkedCount = $('.' + itemCheckboxClass + ':checked').length;
		var allChecked = totalCount > 0 && checkedCount === totalCount;
		$('#masterCheckbox').prop('checked', allChecked);
		if (typeof onSelectionChange === 'function') {
			onSelectionChange(checkedCount);
		}
	});

	$('#clearSelectionBtn').off('click').on('click', function () {
		$('#masterCheckbox').prop('checked', false);
		$('.' + itemCheckboxClass).prop('checked', false).trigger('change.selection');
		if (typeof onSelectionChange === 'function') {
			onSelectionChange(0);
		}
	});
}

function taigaReplaceUrlQuery(params) {
	params = params || {};
	const url = new URL(window.location.href);
	const search = new URLSearchParams();

	Object.keys(params).forEach(key => {
		const value = params[key];
		if (value === undefined || value === null) return;
		if (String(value).trim() === '') return;
		search.set(key, String(value));
	});

	const query = search.toString();
	url.search = query ? `?${query}` : '';
	window.history.replaceState(null, '', url.toString());
}

function taigaGetUrlQueryParams() {
	const urlParams = new URLSearchParams(window.location.search);
	const params = {};
	urlParams.forEach((value, key) => {
		params[key] = value;
	});
	return params;
}

function taigaApplyFiltersFromUrl() {
	const urlParams = new URLSearchParams(window.location.search);
	const page = parseInt(urlParams.get('page') || '1') || 1;
	const q = urlParams.get('q');
	const orderBy = urlParams.get('order_by');
	const projectIdParam = urlParams.get('project');
	const epicIdParam = urlParams.get('epic');
	const userStoryIdParam = urlParams.get('user_story');
	const statusIdParam = urlParams.get('status');
	const assignedToParam = urlParams.get('assigned_to');

	const apiGet = function (url, data) {
		return $.ajax({
			url: url,
			type: 'GET',
			data: data,
			headers: {
				'Authorization': 'Bearer ' + window.taigaToken,
				'Content-Type': 'application/json',
				'X-Taiga-Api-Url': window.apiUrl
			}
		});
	};

	const setSelectValue = function (selector, id, label) {
		if (!id) return;
		const $select = $(selector);
		if (!$select.length) return;
		const option = new Option(label || id, id, true, true);
		$select.append(option).trigger('change');
	};

	const applyProject = function (projectId) {
		if (!projectId) return $.Deferred().resolve().promise();
		if (!$('#projectSelect').length) return $.Deferred().resolve().promise();
		return apiGet('api.php/projects/' + encodeURIComponent(projectId)).then(function (project) {
			setSelectValue('#projectSelect', String(project.id), project.name || ('Project ' + project.id));
		}, function () {
			setSelectValue('#projectSelect', String(projectId), 'Project ' + projectId);
		});
	};

	const applyEpic = function (epicId) {
		if (!epicId) return $.Deferred().resolve(null).promise();
		if (!$('#epicSelect').length) return $.Deferred().resolve(null).promise();
		return apiGet('api.php/epics/' + encodeURIComponent(epicId)).then(function (epic) {
			setSelectValue('#epicSelect', String(epic.id), `#${epic.ref}: ${epic.subject || 'Untitled Epik'}`);
			return epic;
		}, function () {
			setSelectValue('#epicSelect', String(epicId), 'Epik ' + epicId);
			return null;
		});
	};

	const applyUserStory = function (usorId) {
		if (!usorId) return $.Deferred().resolve(null).promise();
		if (!$('#userStorySelect').length) return $.Deferred().resolve(null).promise();
		return apiGet('api.php/userstories/' + encodeURIComponent(usorId)).then(function (usor) {
			setSelectValue('#userStorySelect', String(usor.id), `#${usor.ref}: ${usor.subject || 'Untitled Usor'}`);
			return usor;
		}, function () {
			setSelectValue('#userStorySelect', String(usorId), 'Usor ' + usorId);
			return null;
		});
	};

	const applyStatusAndAssigned = function (projectId) {
		const jobs = [];

		if (statusIdParam && $('#statusSelect').length && projectId && $('#statusSelect').data('status-type')) {
			jobs.push(
				taigaFetchStatuses(window.apiUrl, window.taigaToken, projectId, $('#statusSelect').data('status-type'))
					.then(function (statuses) {
						const $status = $('#statusSelect');
						taigaPopulateStatusDropdown($status, statuses);
						$status.val(String(statusIdParam)).trigger('change');
					})
			);
		}

		if (assignedToParam && $('#assignedToSelect').length && projectId) {
			jobs.push(
				taigaFetchMembers(window.apiUrl, window.taigaToken, projectId)
					.then(function (memberships) {
						const $assigned = $('#assignedToSelect');
						let html = '<option value="">(Semua User)</option>';
						if (Array.isArray(memberships)) {
							memberships.forEach(m => {
								const name = m.full_name || m.user_email || 'Unknown';
								html += `<option value="${m.user}">${name}</option>`;
							});
						}
						$assigned.html(html);
						$assigned.val(String(assignedToParam)).trigger('change');
					})
			);
		}

		if (!jobs.length) return $.Deferred().resolve().promise();
		return $.when.apply($, jobs);
	};

	if (q && $('#searchInput').length) {
		$('#searchInput').val(q);
	}
	if (orderBy && $('#sortSelect').length) {
		$('#sortSelect').val(orderBy).trigger('change');
	}

	let chain = $.Deferred().resolve().promise();
	let resolvedProjectId = projectIdParam ? String(projectIdParam) : null;

	if (!resolvedProjectId && userStoryIdParam) {
		chain = chain
			.then(function () {
				return applyUserStory(userStoryIdParam);
			})
			.then(function (usor) {
				if (usor && usor.project && !resolvedProjectId) {
					resolvedProjectId = String(usor.project);
					return applyProject(resolvedProjectId);
				}
			});
	} else {
		if (resolvedProjectId) {
			chain = chain.then(function () {
				return applyProject(resolvedProjectId);
			});
		}
		if (userStoryIdParam) {
			chain = chain.then(function () {
				return applyUserStory(userStoryIdParam);
			});
		}
	}

	if (!resolvedProjectId && epicIdParam) {
		chain = chain
			.then(function () {
				return applyEpic(epicIdParam);
			})
			.then(function (epic) {
				if (epic && epic.project && !resolvedProjectId) {
					resolvedProjectId = String(epic.project);
					return applyProject(resolvedProjectId);
				}
			});
	} else if (epicIdParam) {
		chain = chain.then(function () {
			return applyEpic(epicIdParam);
		});
	}

	if (resolvedProjectId) {
		chain = chain.then(function () {
			return applyStatusAndAssigned(resolvedProjectId);
		});
	}

	return chain.then(function () {
		return page;
	});
}
