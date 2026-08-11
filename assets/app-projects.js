$(document).ready(function () {
    const token = localStorage.getItem('taiga_token');
    const userData = localStorage.getItem('taiga_user');
    if (!token || !userData) { window.location.href = 'login.php'; return; }

    const config = window.taigaConfig || {};
    const apiUrl = localStorage.getItem('taiga_api_url') || config.servers?.default?.api_url;
    window.apiUrl = apiUrl; window.taigaToken = token;

    TTTaiga.Projects = {
        load: function (page = 1) {
            taigaReplaceUrlQuery({ ...taigaGetFilterParams(), page: page });
            const params = { ...taigaGetFilterParams(), page: page, member: window.taigaModel?.id };
            if (!params.member) delete params.member;

            $('#projectsContent').html(`<div class="loading-spinner text-center p-3"><div class="spinner-border text-primary" role="status"></div></div>`);

            $.ajax({
                url: 'api.php/projects',
                type: 'GET',
                data: params,
                headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json', 'X-Taiga-Api-Url': apiUrl },
                success: function (projects, status, xhr) {
                    this.render(projects, xhr);
                    taigaRenderPagination(xhr, '#projectsPagination', (page) => this.load(page));
                }.bind(this),
                error: function (xhr) {
                    $('#projectsContent').html(`<div class="alert alert-danger">Failed to load projects.</div>`);
                }
            });
        },
        render: function (projects, xhr) {
            taigaUpdateListCounts(xhr, projects.length, 'totalProjects', 'filteredProjects', 'selectedProjectsCount');
            if (projects.length === 0) {
                $('#projectsContent').html(`<div class="text-muted italic p-3 text-center"><em>(kosong)</em></div>`);
                return;
            }
            let html = '<div class="row taiga-list-grid">';
            projects.forEach(project => {
                html += `
                <div class="col-md-6 col-lg-4">
                    <div class="card taiga-list-card project-card h-100" data-project-id="${project.id}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="form-check">
                                    <input class="form-check-input project-checkbox" type="checkbox" value="${project.id}" data-version="${project.version}" data-name="${project.name}">
                                </div>
                                <span class="badge bg-${project.is_private ? 'secondary' : 'primary'}">${project.is_private ? 'Private' : 'Public'}</span>
                            </div>
                            <h6 class="card-title text-truncate">${project.name}</h6>
                            <p class="card-text text-muted small mb-0">${project.description || ''}</p>
                        </div>
                        <div class="card-footer taiga-card-actions">
                            <button class="btn btn-sm btn-outline-primary view-project" data-project-id="${project.id}">View</button>
                            <button class="btn btn-sm btn-outline-secondary edit-project" data-project-id="${project.id}" data-bs-toggle="modal" data-bs-target="#singleProjectModal">Edit</button>
                        </div>
                    </div>
                </div>`;
            });
            html += '</div>';
            $('#projectsContent').html(html);
            $('.project-checkbox').off('change').on('change', this.updateSelectionCount);
            $('.view-project').off('click').on('click', function() { window.location.href = `project.php?id=${$(this).data('project-id')}`; });
        },
        updateSelectionCount: function() {
            $('#selectedProjectsCount').text($('#projectsContent input.project-checkbox:checked').length);
            $('#selectedProjectsCountLabel').text($('#projectsContent input.project-checkbox:checked').length);
        },
        submitBulkCreate: function() {
            const text = $('#bulkCreateProjectText').val().trim();
            if (!text) { TTTaiga.UI.notify('Please enter some projects', 'warning'); return; }

            const $btn = $('#submitBulkCreateProjects');
            $btn.prop('disabled', true).text('Creating...');
            $('.filter-toolbar, .btn, .dropdown-item').addClass('disabled');

            const projects = taigaParseBulkLines(text);
            let createdCount = 0; let errorCount = 0;

            projects.forEach(p => {
                $.ajax({
                    url: 'api.php/projects',
                    type: 'POST',
                    headers: { 'Authorization': 'Bearer ' + window.taigaToken, 'Content-Type': 'application/json', 'X-Taiga-Api-Url': window.apiUrl },
                    data: JSON.stringify({
                        name: p.name,
                        description: p.description || '',
                    }),
                    success: () => { createdCount++; checkFinished(); },
                    error: () => { errorCount++; checkFinished(); }
                });
            });

            function checkFinished() {
                if (createdCount + errorCount === projects.length) {
                    $btn.prop('disabled', false).text('Create Projects');
                    $('.filter-toolbar, .btn, .dropdown-item').removeClass('disabled');
                    TTTaiga.UI.notify(`Created ${createdCount} projects, ${errorCount} failed.`, errorCount === 0 ? 'success' : 'danger');
                    if (errorCount === 0) { $('#bulkCreateProjectModal').modal('hide'); TTTaiga.Projects.load(); }
                }
            }
        },
        submitBulkUpdate: function() {
            const prefix = $('#projectPrefixInput').val().trim();
            if (!prefix) { TTTaiga.UI.notify('Please enter a prefix', 'warning'); return; }

            const selectedProjects = [];
            $('#projectsContent input.project-checkbox:checked').each(function () {
                selectedProjects.push({ id: $(this).val(), version: $(this).data('version'), name: $(this).data('name') });
            });

            if (selectedProjects.length === 0) { TTTaiga.UI.notify('Please select at least one project', 'warning'); return; }

            const $btn = $('#submitBulkProjectUpdate');
            $btn.prop('disabled', true).text('Applying...');
            $('.filter-toolbar, .btn, .dropdown-item').addClass('disabled');

            taigaExecuteBulk('/projects/', selectedProjects, 'PATCH', (item) => {
                const cleanName = item.name.replace(/^\[.*?\]\s*/, '');
                return { name: `[${prefix}] ${cleanName}` };
            }, (successCount, errorCount) => {
                $btn.prop('disabled', false).text('Apply Prefix');
                $('.filter-toolbar, .btn, .dropdown-item').removeClass('disabled');
                TTTaiga.UI.notify(`Updated ${successCount} projects, ${errorCount} failed.`, errorCount === 0 ? 'success' : 'danger');
                if (errorCount === 0) {
                    $('#bulkUpdateProjectModal').modal('hide');
                    TTTaiga.Projects.load();
                }
            });
        },
        deleteBulk: function() {
            const selectedProjects = [];
            $('#projectsContent input.project-checkbox:checked').each(function () {
                selectedProjects.push({ id: $(this).val(), version: $(this).data('version') });
            });

            if (selectedProjects.length === 0) { TTTaiga.UI.notify('Please select at least one project', 'warning'); return; }

            const $btn = $('#confirmBulkDeleteProjects');
            $btn.prop('disabled', true).text('Deleting...');
            $('.filter-toolbar, .btn, .dropdown-item').addClass('disabled');

            taigaExecuteBulk('/projects/', selectedProjects, 'DELETE', null, (successCount, errorCount) => {
                $btn.prop('disabled', false).text('Delete Projects');
                $('.filter-toolbar, .btn, .dropdown-item').removeClass('disabled');
                TTTaiga.UI.notify(`Deleted ${successCount} projects, ${errorCount} failed.`, errorCount === 0 ? 'success' : 'danger');
                if (errorCount === 0) {
                    $('#bulkDeleteProjectModal').modal('hide');
                    TTTaiga.Projects.load();
                }
            });
        }
    };

    $('#submitBulkProjectUpdate').on('click', () => TTTaiga.Projects.submitBulkUpdate());
    $('#confirmBulkDeleteProjects').on('click', () => TTTaiga.Projects.deleteBulk());
    $('#submitBulkCreateProjects').on('click', () => TTTaiga.Projects.submitBulkCreate());
    $('#previewBulkCreateProjects').on('click', function() {
        const text = $('#bulkCreateProjectText').val().trim();
        const $preview = $('#bulkCreateProjectPreview');
        if (!text) { $preview.addClass('d-none'); return; }
        const items = taigaParseBulkLines(text);
        $preview.html('<strong>Preview (' + items.length + ' projects):</strong><ul class="mb-0 mt-1">' + items.map(i => '<li>' + i.name + (i.description ? ' | ' + i.description : '') + '</li>').join('') + '</ul>').removeClass('d-none');
    });

    taigaBindFilters((page) => TTTaiga.Projects.load(page));
    taigaApplyFiltersFromUrl()
        .then(page => TTTaiga.Projects.load(page))
        .catch(() => TTTaiga.Projects.load(1));
});
