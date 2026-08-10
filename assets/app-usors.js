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

    TTTaiga.Usors = {
        load: function (page = 1) {
            taigaReplaceUrlQuery({ ...taigaGetFilterParams(), page: page });
            const params = { ...taigaGetFilterParams(), page: page };

            $('#usorsContent').html(`
                <div class="loading-spinner text-center p-3">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            `);

            $.ajax({
                url: 'api.php/userstories',
                type: 'GET',
                data: params,
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Content-Type': 'application/json',
                    'X-Taiga-Api-Url': apiUrl
                },
                success: function (usors, status, xhr) {
                    this.render(usors, xhr);
                    taigaRenderPagination(xhr, '#usorsPagination', (page) => this.load(page));
                }.bind(this),
                error: function (xhr) {
                    $('#usorsContent').html(`<div class="alert alert-danger">Unable to load usors. Please try again.</div>`);
                    $('#usorsPagination').empty();
                }
            });
        },
        render: function (usors, xhr) {
            taigaUpdateListCounts(xhr, usors.length, 'totalUsors', 'filteredUsors', 'selectedUsorsCount');
            if (usors.length === 0) {
                $('#usorsContent').html(`<div class="text-muted italic p-3 text-center"><em>(kosong)</em></div>`);
                return;
            }
            let html = '<div class="row taiga-list-grid">';
            usors.forEach(usor => {
                const statusInfo = taigaGetStatusInfo(usor);
                const statusBadge = taigaRenderStatusBadge(statusInfo);
                const assignedTo = usor.assigned_to_extra ? usor.assigned_to_extra.full_name_display : (usor.assigned_to ? 'User ID: ' + usor.assigned_to : 'Unassigned');
                const owner = usor.owner_extra ? usor.owner_extra.full_name_display : 'Unknown';

                html += `
                <div class="col-md-6 col-lg-4">
                    <div class="card taiga-list-card usor-card h-100" data-us-id="${usor.id}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="form-check">
                                    <input class="form-check-input story-checkbox" type="checkbox" value="${usor.id}" data-version="${usor.version}" id="us-${usor.id}">
                                </div>
                                <div class="d-flex flex-column align-items-end">
                                    ${statusBadge}
                                    <small class="text-muted mt-1">#${usor.ref}</small>
                                </div>
                            </div>
                            <h6 class="card-title text-truncate">${usor.subject || 'Untitled Usor'}</h6>
                            <p class="card-text taiga-card-description text-muted small mb-0">${usor.description || ''}</p>
                            <div class="taiga-card-meta">
                                <small class="text-muted d-block text-truncate">Assigned: <strong>${assignedTo}</strong></small>
                            </div>
                        </div>
                        <div class="card-footer taiga-card-actions">
                            <a href="usor.php?id=${usor.id}" class="btn btn-sm btn-outline-primary">View</a>
                            <button class="btn btn-sm btn-outline-secondary edit-usor" data-usor-id="${usor.id}" data-bs-toggle="modal" data-bs-target="#singleUsorModal">Edit</button>
                        </div>
                    </div>
                </div>`;
            });
            html += '</div>';
            $('#usorsContent').html(html);
            $('.story-checkbox').off('change').on('change', this.updateSelectionCount);
        },
        updateSelectionCount: function() {
            $('#selectedUsorsCount').text($('#usorsContent input:checked').length);
        },
        populateBulkCreateDropdowns: function() {
            const currentProjectId = $('#projectSelect').val();
            
            taigaPopulateProjectSelect($('#bulkCreateProject'), currentProjectId).done(function() {
                if(currentProjectId) $('#bulkCreateProject').val(currentProjectId).trigger('change.select2');
            });
            
            $('#bulkCreateProject').off('change').on('change', function() {
                const projectId = $(this).val();
                taigaPopulateBulkStatuses('us', $('#bulkCreateStatus'), projectId);
                taigaPopulateBulkMembers($('#bulkCreateAssignee'), projectId);
            });
        },
        submitBulkCreate: function() {
            const text = $('#bulkCreateText').val().trim();
            if (!text) {
                TTTaiga.UI.notify('Please enter some usors to create', 'warning');
                return;
            }

            const projectId = $('#bulkCreateProject').val();
            if (!projectId) {
                TTTaiga.UI.notify('Please select a project', 'warning');
                return;
            }

            const $btn = $('#submitBulkCreate');
            $btn.prop('disabled', true).text('Creating...');
            $('.filter-toolbar, .btn, .dropdown-item').addClass('disabled');

            const stories = text.split('\n').filter(line => line.trim()).map(line => {
                const parts = line.split('|');
                return { 
                    subject: parts[0]?.trim() || 'Untitled Usor', 
                    description: parts[1]?.trim() || '', 
                    status: parts[2]?.trim() || $('#bulkCreateStatus').val() 
                };
            });

            let createdCount = 0;
            let errorCount = 0;

            stories.forEach(story => {
                $.ajax({
                    url: 'api.php/userstories',
                    type: 'POST',
                    headers: { 'Authorization': 'Bearer ' + window.taigaToken, 'Content-Type': 'application/json', 'X-Taiga-Api-Url': window.apiUrl },
                    data: JSON.stringify({
                        subject: story.subject,
                        description: story.description,
                        project: parseInt(projectId),
                        status: story.status
                    }),
                    success: () => { createdCount++; checkFinished(); },
                    error: () => { errorCount++; checkFinished(); }
                });
            });

            function checkFinished() {
                if (createdCount + errorCount === stories.length) {
                    $btn.prop('disabled', false).text('Create Stories');
                    $('.filter-toolbar, .btn, .dropdown-item').removeClass('disabled');
                    TTTaiga.UI.notify(`Created ${createdCount} usors, ${errorCount} failed.`, errorCount === 0 ? 'success' : 'danger');
                    if (errorCount === 0) {
                        $('#bulkCreateModal').modal('hide');
                        TTTaiga.Usors.load();
                    }
                }
            }
        },
        populateBulkUpdateDropdowns: function() {
            const filterParams = taigaGetFilterParams();
            const projectId = filterParams.project;
            taigaPopulateProjectSelect($('#bulkUpdateProjectOptions'), projectId).done(function() {
                if(projectId) $('#bulkUpdateProjectOptions').val(projectId).trigger('change.select2');
            });
            taigaPopulateBulkStatuses('us', $('#bulkUpdateStatus'), projectId, 'No Change');
            taigaPopulateBulkMembers($('#bulkUpdateAssignee'), projectId, 'No Change');

            taigaLoadBulkItems('/userstories', $('#bulkUpdateUsors'), item => `
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="${item.id}" data-version="${item.version}" id="bulk-usor-${item.id}">
                    <label class="form-check-label" for="bulk-usor-${item.id}">#${item.ref}: ${item.subject || 'Untitled Usor'}</label>
                </div>
            `);
        },
        submitBulkUpdate: function() {
            const selectedUsors = [];
            $('#bulkUpdateUsors input:checked').each(function () {
                selectedUsors.push({ id: $(this).val(), version: $(this).data('version') });
            });

            if (selectedUsors.length === 0) {
                TTTaiga.UI.notify('Please select at least one usor to update', 'warning');
                return;
            }

            const updateData = {};
            const status = $('#bulkUpdateStatus').val();
            if (status) updateData.status = parseInt(status);
            
            const $btn = $('#submitBulkUpdate');
            $btn.prop('disabled', true).text('Updating...');
            $('.filter-toolbar, .btn, .dropdown-item').addClass('disabled');

            taigaExecuteBulk('/userstories/', selectedUsors, 'PATCH', updateData, (successCount, errorCount) => {
                $btn.prop('disabled', false).text('Update Usors');
                $('.filter-toolbar, .btn, .dropdown-item').removeClass('disabled');
                TTTaiga.UI.notify(`Updated ${successCount} usors, ${errorCount} failed.`, errorCount === 0 ? 'success' : 'danger');
                if (errorCount === 0) {
                    $('#bulkUpdateModal').modal('hide');
                    TTTaiga.Usors.load();
                }
            });
        },
        deleteBulk: function() {
            const selectedUsors = [];
            $('#usorsContent input.story-checkbox:checked').each(function () {
                selectedUsors.push({ id: $(this).val(), version: $(this).data('version') });
            });

            if (selectedUsors.length === 0) {
                TTTaiga.UI.notify('Please select at least one usor to delete', 'warning');
                return;
            }

            const $btn = $('#confirmBulkDelete');
            $btn.prop('disabled', true).text('Deleting...');
            $('.filter-toolbar, .btn, .dropdown-item').addClass('disabled');

            taigaExecuteBulk('/userstories/', selectedUsors, 'DELETE', null, (successCount, errorCount) => {
                $btn.prop('disabled', false).text('Delete Usors');
                $('.filter-toolbar, .btn, .dropdown-item').removeClass('disabled');
                TTTaiga.UI.notify(`Deleted ${successCount} usors, ${errorCount} failed.`, errorCount === 0 ? 'success' : 'danger');
                if (errorCount === 0) {
                    $('#bulkDeleteModal').modal('hide');
                    TTTaiga.Usors.load();
                }
            });
        }
    };

    $('#bulkCreateModal').on('show.bs.modal', function() { 
        TTTaiga.Usors.populateBulkCreateDropdowns(); 
        const state = TTTaiga.State.getState();
        if(state.projectSelect) $('#bulkCreateProject').val(state.projectSelect).trigger('change.select2');
    });
    $('#submitBulkCreate').on('click', function() { TTTaiga.Usors.submitBulkCreate(); });
    $('#bulkUpdateModal').on('show.bs.modal', function() { TTTaiga.Usors.populateBulkUpdateDropdowns(); });
    $('#submitBulkUpdate').on('click', function() { TTTaiga.Usors.submitBulkUpdate(); });
    $('#confirmBulkDelete').on('click', function() { TTTaiga.Usors.deleteBulk(); });

    taigaBindFilters((page) => TTTaiga.Usors.load(page));

    taigaApplyFiltersFromUrl()
        .then(page => TTTaiga.Usors.load(page))
        .catch(() => TTTaiga.Usors.load(1));
});
