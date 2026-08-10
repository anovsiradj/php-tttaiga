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

    TTTaiga.Issues = {
        load: function (page = 1) {
            taigaReplaceUrlQuery({ ...taigaGetFilterParams(), page: page });
            const params = { ...taigaGetFilterParams(), page: page };

            $('#issuesContent').html(`
                <div class="loading-spinner text-center p-3">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            `);

            $.ajax({
                url: 'api.php/issues',
                type: 'GET',
                data: params,
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Content-Type': 'application/json',
                    'X-Taiga-Api-Url': apiUrl
                },
                success: function (issues, status, xhr) {
                    this.render(issues, xhr);
                    taigaRenderPagination(xhr, '#issuesPagination', (page) => this.load(page));
                }.bind(this),
                error: function (xhr) {
                    $('#issuesContent').html(`<div class="alert alert-danger">Unable to load issues. Please try again.</div>`);
                    $('#issuesPagination').empty();
                }
            });
        },
        render: function (issues, xhr) {
            taigaUpdateListCounts(xhr, issues.length, 'totalIssues', 'filteredIssues', 'selectionCount');
            if (issues.length === 0) {
                $('#issuesContent').html(`<div class="text-muted italic p-3 text-center"><em>(kosong)</em></div>`);
                return;
            }
            let html = '<div class="row taiga-list-grid">';
            issues.forEach(issue => {
                const statusInfo = taigaGetStatusInfo(issue);
                const statusBadge = taigaRenderStatusBadge(statusInfo);
                const assignedTo = issue.assigned_to_extra ? issue.assigned_to_extra.full_name_display : (issue.assigned_to ? 'User ID: ' + issue.assigned_to : 'Unassigned');
                const owner = issue.owner_extra ? issue.owner_extra.full_name_display : 'Unknown';

                html += `
                <div class="col-md-6 col-lg-4">
                    <div class="card taiga-list-card issue-card h-100" data-issue-id="${issue.id}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="form-check">
                                    <input class="form-check-input issue-checkbox" type="checkbox" value="${issue.id}" data-version="${issue.version}" id="issue-${issue.id}">
                                </div>
                                ${statusBadge}
                            </div>
                            <h6 class="card-title text-truncate">${issue.subject || 'Untitled Isu'}</h6>
                            <p class="card-text text-muted taiga-card-description small mb-0">${issue.description || ''}</p>
                            <div class="taiga-card-meta">
                                <small class="text-muted d-block text-truncate">Assigned: <strong>${assignedTo}</strong></small>
                            </div>
                        </div>
                        <div class="card-footer taiga-card-actions">
                            <a href="isu.php?id=${issue.id}" class="btn btn-sm btn-outline-primary">View</a>
                            <button class="btn btn-sm btn-outline-secondary edit-isu" data-isu-id="${issue.id}" data-bs-toggle="modal" data-bs-target="#singleIsuModal">Edit</button>
                        </div>
                    </div>
                </div>`;
            });
            html += '</div>';
            $('#issuesContent').html(html);
            $('.issue-checkbox').off('change').on('change', this.updateSelectionCount);
        },
        updateSelectionCount: function() {
            $('#selectionCount').text($('#issuesContent input.issue-checkbox:checked').length);
        },
        populateBulkCreateDropdowns: function() {
            const projectId = $('#projectSelect').val();
            taigaPopulateProjectSelect($('#bulkCreateIssueProject'), projectId);
            
            $('#bulkCreateIssueProject').off('change').on('change', function() {
                const pid = $(this).val();
                taigaPopulateBulkStatuses('issue', $('#bulkCreateIssueStatus'), pid);
                taigaPopulateBulkMembers($('#bulkCreateIssueAssignee'), pid);
            });
            if(projectId) $('#bulkCreateIssueProject').val(projectId).trigger('change.select2');
        },
        submitBulkCreate: function() {
            const text = $('#bulkCreateIssueText').val().trim();
            if (!text) { TTTaiga.UI.notify('Please enter some isus', 'warning'); return; }

            const projectId = $('#bulkCreateIssueProject').val();
            if (!projectId) { TTTaiga.UI.notify('Please select a project', 'warning'); return; }

            const $btn = $('#submitBulkCreateIssues');
            $btn.prop('disabled', true).text('Creating...');
            $('.filter-toolbar, .btn, .dropdown-item').addClass('disabled');

            const items = taigaParseBulkLines(text);
            let createdCount = 0; let errorCount = 0;

            items.forEach(item => {
                $.ajax({
                    url: 'api.php/issues',
                    type: 'POST',
                    headers: { 'Authorization': 'Bearer ' + window.taigaToken, 'Content-Type': 'application/json', 'X-Taiga-Api-Url': window.apiUrl },
                    data: JSON.stringify({
                        project: parseInt(projectId),
                        subject: item.subject,
                        description: item.description || '',
                        status: $('#bulkCreateIssueStatus').val() ? parseInt($('#bulkCreateIssueStatus').val()) : undefined,
                        assigned_to: $('#bulkCreateIssueAssignee').val() ? parseInt($('#bulkCreateIssueAssignee').val()) : undefined
                    }),
                    success: () => { createdCount++; checkFinished(); },
                    error: () => { errorCount++; checkFinished(); }
                });
            });

            function checkFinished() {
                if (createdCount + errorCount === items.length) {
                    $btn.prop('disabled', false).text('Create Isus');
                    $('.filter-toolbar, .btn, .dropdown-item').removeClass('disabled');
                    TTTaiga.UI.notify(`Created ${createdCount} isus, ${errorCount} failed.`, errorCount === 0 ? 'success' : 'danger');
                    if (errorCount === 0) {
                        $('#issueBulkCreateModal').modal('hide');
                        TTTaiga.Issues.load();
                    }
                }
            }
        },
        populateBulkUpdateDropdowns: function() {
            const projectId = $('#projectSelect').val();
            taigaPopulateBulkStatuses('issue', $('#bulkUpdateIssueStatus'), projectId, 'No Change');
            taigaPopulateBulkMembers($('#bulkUpdateIssueAssignee'), projectId, 'No Change');

            const selectedIds = $('#issuesContent input.issue-checkbox:checked').map(function(){ return $(this).val(); }).get();
            taigaLoadBulkItems('/issues', $('#bulkUpdateIssueList'), item => `
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="${item.id}" data-version="${item.version}" id="bulk-issue-${item.id}" ${selectedIds.includes(String(item.id)) ? 'checked' : ''}>
                    <label class="form-check-label" for="bulk-issue-${item.id}">#${item.ref}: ${item.subject}</label>
                </div>
            `);
        },
        submitBulkUpdate: function() {
            const selectedIssues = [];
            $('#bulkUpdateIssueList input:checked').each(function () {
                selectedIssues.push({ id: $(this).val(), version: $(this).data('version') });
            });

            if (selectedIssues.length === 0) { TTTaiga.UI.notify('Please select at least one isu', 'warning'); return; }

            const updateData = {};
            if ($('#bulkUpdateIssueStatus').val()) updateData.status = parseInt($('#bulkUpdateIssueStatus').val());
            if ($('#bulkUpdateIssueAssignee').val()) updateData.assigned_to = parseInt($('#bulkUpdateIssueAssignee').val());

            if (Object.keys(updateData).length === 0) { TTTaiga.UI.notify('No fields to update', 'warning'); return; }

            const $btn = $('#submitBulkIssueUpdate');
            $btn.prop('disabled', true).text('Updating...');
            $('.filter-toolbar, .btn, .dropdown-item').addClass('disabled');

            taigaExecuteBulk('/issues/', selectedIssues, 'PATCH', updateData, (successCount, errorCount) => {
                $btn.prop('disabled', false).text('Update Isus');
                $('.filter-toolbar, .btn, .dropdown-item').removeClass('disabled');
                TTTaiga.UI.notify(`Updated ${successCount} isus, ${errorCount} failed.`, errorCount === 0 ? 'success' : 'danger');
                if (errorCount === 0) { $('#issueBulkUpdateModal').modal('hide'); TTTaiga.Issues.load(); }
            });
        },
        deleteBulk: function() {
            const selectedIssues = [];
            $('#issuesContent input.issue-checkbox:checked').each(function () {
                selectedIssues.push({ id: $(this).val(), version: $(this).data('version') });
            });

            if (selectedIssues.length === 0) { TTTaiga.UI.notify('Please select at least one isu', 'warning'); return; }

            const $btn = $('#confirmBulkDeleteIssues');
            $btn.prop('disabled', true).text('Deleting...');
            $('.filter-toolbar, .btn, .dropdown-item').addClass('disabled');

            taigaExecuteBulk('/issues/', selectedIssues, 'DELETE', null, (successCount, errorCount) => {
                $btn.prop('disabled', false).text('Delete Isus');
                $('.filter-toolbar, .btn, .dropdown-item').removeClass('disabled');
                TTTaiga.UI.notify(`Deleted ${successCount} isus, ${errorCount} failed.`, errorCount === 0 ? 'success' : 'danger');
                if (errorCount === 0) { $('#issueBulkDeleteModal').modal('hide'); TTTaiga.Issues.load(); }
            });
        }
    };

    $('#issueBulkCreateModal').on('show.bs.modal', () => TTTaiga.Issues.populateBulkCreateDropdowns());
    $('#submitBulkCreateIssues').on('click', () => TTTaiga.Issues.submitBulkCreate());
    $('#bulkUpdateBtn').on('click', function(e) { e.preventDefault(); TTTaiga.Issues.populateBulkUpdateDropdowns(); $('#issueBulkUpdateModal').modal('show'); });
    $('#submitBulkIssueUpdate').on('click', () => TTTaiga.Issues.submitBulkUpdate());
    $('#bulkDeleteBtn').on('click', function(e) { e.preventDefault(); $('#issueBulkDeleteModal').modal('show'); });
    $('#confirmBulkDeleteIssues').on('click', () => TTTaiga.Issues.deleteBulk());

    taigaBindFilters((page) => TTTaiga.Issues.load(page));

    taigaApplyFiltersFromUrl()
        .then(page => TTTaiga.Issues.load(page))
        .catch(() => TTTaiga.Issues.load(1));
});
