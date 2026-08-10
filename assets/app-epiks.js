$(document).ready(function () {
    const token = localStorage.getItem('taiga_token');
    const userData = localStorage.getItem('taiga_user');

    if (!token || !userData) {
        window.location.href = 'login.php';
        return;
    }

    const config = window.taigaConfig || {};
    const apiUrl = localStorage.getItem('taiga_api_url') || config.servers?.default?.api_url;

    window.apiUrl = apiUrl;
    window.taigaToken = token;

    let allProjects = [];

    TTTaiga.Epiks = {
        load: function (page = 1) {
            taigaReplaceUrlQuery({ ...taigaGetFilterParams(), page: page });
            const params = { ...taigaGetFilterParams(), page: page };

            $('#epicsContent').html(`
                <div class="loading-spinner text-center p-3">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            `);

            $.ajax({
                url: 'api.php/epics',
                type: 'GET',
                data: params,
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Content-Type': 'application/json',
                    'X-Taiga-Api-Url': apiUrl
                },
                success: function (epics, status, xhr) {
                    this.render(epics, xhr);
                    taigaRenderPagination(xhr, '#epicsPagination', (page) => this.load(page));
                }.bind(this),
                error: function (xhr) {
                    $('#epicsContent').html(`<div class="alert alert-danger">Unable to load epiks. Please try again.</div>`);
                    $('#epicsPagination').empty();
                }
            });
        },
        render: function (epics, xhr) {
            taigaUpdateListCounts(xhr, epics.length, 'totalEpics', 'filteredEpics', 'selectedEpicsCount');
            if (epics.length === 0) {
                $('#epicsContent').html(`<div class="text-muted italic p-3 text-center"><em>(kosong)</em></div>`);
                return;
            }
            let html = '<div class="row taiga-list-grid">';
            epics.forEach(epic => {
                const project = allProjects.find(p => p.id === epic.project) || {};
                const statusInfo = taigaGetStatusInfo(epic);
                const statusBadge = taigaRenderStatusBadge(statusInfo);
                const assignedTo = epic.assigned_to_extra ? epic.assigned_to_extra.full_name_display : (epic.assigned_to ? 'User ID: ' + epic.assigned_to : 'Unassigned');
                const owner = epic.owner_extra ? epic.owner_extra.full_name_display : 'Unknown';

                html += `
                <div class="col-md-6 col-lg-4">
                    <div class="card taiga-list-card epic-card h-100" data-epic-id="${epic.id}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="form-check">
                                    <input class="form-check-input epic-checkbox" type="checkbox" value="${epic.id}" data-version="${epic.version}" id="epic-${epic.id}">
                                </div>
                                ${statusBadge}
                            </div>
                            <h6 class="card-title text-truncate">${epic.subject || 'Untitled Epik'}</h6>
                            <p class="card-text text-muted taiga-card-description small mb-0">${epic.description || ''}</p>
                            <div class="taiga-card-meta">
                                <small class="text-muted d-block text-truncate">Assigned: <strong>${assignedTo}</strong></small>
                            </div>
                        </div>
                        <div class="card-footer taiga-card-actions">
                            <button class="btn btn-sm btn-outline-primary view-epic" data-epic-id="${epic.id}">View</button>
                            <button class="btn btn-sm btn-outline-secondary edit-epic" data-epic-id="${epic.id}" data-bs-toggle="modal" data-bs-target="#singleEpicModal">Edit</button>
                        </div>
                    </div>
                </div>`;
            });
            html += '</div>';
            $('#epicsContent').html(html);
            $('.epic-checkbox').off('change').on('change', this.updateSelectionCount);
            $('.view-epic').off('click').on('click', function() { window.location.href = `epik.php?id=${$(this).data('epic-id')}`; });
        },
        updateSelectionCount: function() {
            $('#selectedEpicsCount').text($('#epicsContent input.epic-checkbox:checked').length);
        },
        populateBulkCreateDropdowns: function() {
            const projectId = $('#projectSelect').val();
            taigaPopulateProjectSelect($('#bulkCreateEpicProject'), projectId);
            $('#bulkCreateEpicProject').off('change').on('change', function() {
                const pid = $(this).val();
                taigaPopulateBulkStatuses('epic', $('#bulkCreateEpicStatus'), pid);
                taigaPopulateBulkMembers($('#bulkCreateEpicAssignee'), pid);
            });
            if(projectId) $('#bulkCreateEpicProject').trigger('change');
        },
        submitBulkCreate: function() {
            const text = $('#bulkCreateEpicText').val().trim();
            if (!text) { TTTaiga.UI.notify('Please enter some epiks', 'warning'); return; }

            const projectId = $('#bulkCreateEpicProject').val();
            if (!projectId) { TTTaiga.UI.notify('Please select a project', 'warning'); return; }

            const $btn = $('#submitBulkCreateEpic');
            $btn.prop('disabled', true).text('Creating...');
            $('.filter-toolbar, .btn, .dropdown-item').addClass('disabled');

            const epics = taigaParseBulkLines(text);
            let createdCount = 0; let errorCount = 0;

            epics.forEach(epic => {
                $.ajax({
                    url: 'api.php/epics',
                    type: 'POST',
                    headers: { 'Authorization': 'Bearer ' + window.taigaToken, 'Content-Type': 'application/json', 'X-Taiga-Api-Url': window.apiUrl },
                    data: JSON.stringify({
                        project: parseInt(projectId),
                        subject: epic.subject,
                        description: epic.description || '',
                        status: $('#bulkCreateEpicStatus').val() ? parseInt($('#bulkCreateEpicStatus').val()) : undefined,
                        assigned_to: $('#bulkCreateEpicAssignee').val() ? parseInt($('#bulkCreateEpicAssignee').val()) : undefined
                    }),
                    success: () => { createdCount++; checkFinished(); },
                    error: () => { errorCount++; checkFinished(); }
                });
            });

            function checkFinished() {
                if (createdCount + errorCount === epics.length) {
                    $btn.prop('disabled', false).text('Create Epiks');
                    $('.filter-toolbar, .btn, .dropdown-item').removeClass('disabled');
                    TTTaiga.UI.notify(`Created ${createdCount} epiks, ${errorCount} failed.`, errorCount === 0 ? 'success' : 'danger');
                    if (errorCount === 0) {
                        $('#bulkCreateEpicModal').modal('hide');
                        TTTaiga.Epiks.load();
                    }
                }
            }
        },
        populateBulkUpdateDropdowns: function() {
            const projectId = $('#projectSelect').val();
            taigaPopulateBulkStatuses('epic', $('#bulkUpdateEpicStatus'), projectId, 'No Change');
            taigaPopulateBulkMembers($('#bulkUpdateEpicAssignee'), projectId, 'No Change');

            const selectedIds = $('#epicsContent input.epic-checkbox:checked').map(function(){ return $(this).val(); }).get();
            taigaLoadBulkItems('/epics', $('#bulkUpdateEpics'), item => `
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="${item.id}" data-version="${item.version}" id="bulk-epic-${item.id}" ${selectedIds.includes(String(item.id)) ? 'checked' : ''}>
                    <label class="form-check-label" for="bulk-epic-${item.id}">#${item.ref}: ${item.subject || 'Untitled Epik'}</label>
                </div>
            `);
        },
        submitBulkUpdate: function() {
            const selectedEpics = [];
            $('#bulkUpdateEpics input:checked').each(function () {
                selectedEpics.push({ id: $(this).val(), version: $(this).data('version') });
            });

            if (selectedEpics.length === 0) { TTTaiga.UI.notify('Please select at least one epik', 'warning'); return; }

            const updateData = {};
            if ($('#bulkUpdateEpicStatus').val()) updateData.status = parseInt($('#bulkUpdateEpicStatus').val());
            if ($('#bulkUpdateEpicAssignee').val()) updateData.assigned_to = parseInt($('#bulkUpdateEpicAssignee').val());

            if (Object.keys(updateData).length === 0) { TTTaiga.UI.notify('No fields to update', 'warning'); return; }

            const $btn = $('#submitBulkUpdateEpic');
            $btn.prop('disabled', true).text('Updating...');
            $('.filter-toolbar, .btn, .dropdown-item').addClass('disabled');

            taigaExecuteBulk('/epics/', selectedEpics, 'PATCH', updateData, (successCount, errorCount) => {
                $btn.prop('disabled', false).text('Update Epiks');
                $('.filter-toolbar, .btn, .dropdown-item').removeClass('disabled');
                TTTaiga.UI.notify(`Updated ${successCount} epiks, ${errorCount} failed.`, errorCount === 0 ? 'success' : 'danger');
                if (errorCount === 0) { $('#bulkUpdateEpicModal').modal('hide'); TTTaiga.Epiks.load(); }
            });
        },
        deleteBulk: function() {
            const selectedEpics = [];
            $('#epicsContent input.epic-checkbox:checked').each(function () {
                selectedEpics.push({ id: $(this).val(), version: $(this).data('version') });
            });

            if (selectedEpics.length === 0) { TTTaiga.UI.notify('Please select at least one epik', 'warning'); return; }

            const $btn = $('#confirmBulkDeleteEpics');
            $btn.prop('disabled', true).text('Deleting...');
            $('.filter-toolbar, .btn, .dropdown-item').addClass('disabled');

            taigaExecuteBulk('/epics/', selectedEpics, 'DELETE', null, (successCount, errorCount) => {
                $btn.prop('disabled', false).text('Delete Epiks');
                $('.filter-toolbar, .btn, .dropdown-item').removeClass('disabled');
                TTTaiga.UI.notify(`Deleted ${successCount} epiks, ${errorCount} failed.`, errorCount === 0 ? 'success' : 'danger');
                if (errorCount === 0) { $('#bulkDeleteEpicModal').modal('hide'); TTTaiga.Epiks.load(); }
            });
        }
    };

    // Load projects first
    $.ajax({
        url: 'api.php/projects',
        type: 'GET',
        headers: {
            'Authorization': 'Bearer ' + token,
            'Content-Type': 'application/json',
            'X-Taiga-Api-Url': apiUrl
        },
        success: function (projects) {
            allProjects = projects;
            
            $('#bulkCreateEpicModal').on('show.bs.modal', function() { 
                TTTaiga.Epiks.populateBulkCreateDropdowns(); 
                const state = TTTaiga.State.getState();
                if(state.projectSelect) $('#bulkCreateEpicProject').val(state.projectSelect).trigger('change.select2');
            });
            $('#submitBulkCreateEpic').on('click', () => TTTaiga.Epiks.submitBulkCreate());
            $('#bulkUpdateEpicModal').on('show.bs.modal', () => TTTaiga.Epiks.populateBulkUpdateDropdowns());
            $('#submitBulkUpdateEpic').on('click', () => TTTaiga.Epiks.submitBulkUpdate());
            $('#confirmBulkDeleteEpics').on('click', () => TTTaiga.Epiks.deleteBulk());

            taigaBindFilters((page) => TTTaiga.Epiks.load(page));

            taigaApplyFiltersFromUrl()
                .then(page => TTTaiga.Epiks.load(page))
                .catch(() => TTTaiga.Epiks.load(1));
        }
    });
});
