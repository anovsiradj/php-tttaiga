$(document).ready(function () {
    const token = localStorage.getItem('taiga_token');
    const userData = localStorage.getItem('taiga_user');

    if (!token || !userData) {
        window.location.href = 'login.php';
        return;
    }

    const config = window.taigaConfig || {};
    window.apiUrl = localStorage.getItem('taiga_api_url') || config.servers?.default?.api_url;
    window.taigaToken = token;

    TTTaiga.Tasks = {
        load: function (page = 1) {
            taigaReplaceUrlQuery({ ...taigaGetFilterParams(), page: page });
            const params = { ...taigaGetFilterParams(), page: page };

            $('#tasksContent').html(`
                <div class="loading-spinner text-center p-3">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            `);

            TTTaiga.API.get('api.php/tasks', params, {
                success: function (tasks, status, xhr) {
                    this.render(tasks, xhr);
                    taigaRenderPagination(xhr, '#tasksPagination', (page) => this.load(page));
                }.bind(this),
                onError: function (xhr) {
                    $('#tasksContent').html(`<div class="alert alert-danger">Unable to load tasks. Please try again.</div>`);
                    $('#tasksPagination').empty();
                }
            });
        },
        render: function (tasks, xhr) {
            taigaUpdateListCounts(xhr, tasks.length, 'totalTasks', 'filteredTasks', 'selectedTasksCount');
            if (tasks.length === 0) {
                $('#tasksContent').html(`<div class="text-muted italic p-3 text-center"><em>(kosong)</em></div>`);
                return;
            }
            let html = '<div class="row taiga-list-grid">';
            tasks.forEach(task => {
                const statusInfo = taigaGetStatusInfo(task);
                const statusBadge = taigaRenderStatusBadge(statusInfo);
                const assignedTo = task.assigned_to_extra_info ? task.assigned_to_extra_info.full_name_display : (task.assigned_to ? 'User ID: ' + task.assigned_to : 'Unassigned');
                const owner = task.owner_extra_info ? task.owner_extra_info.full_name_display : 'Unknown';

                const usorInfo = task.user_story_extra_info ? task.user_story_extra_info.subject : (task.user_story ? `Usor #${task.user_story}` : '');
                const usorHtml = usorInfo ? `<small class="text-muted d-block text-truncate">In Usor: <strong>${usorInfo}</strong></small>` : '';

                const refHtml = task.ref ? `<small class="text-muted mt-1">#${task.ref}</small>` : '';

                html += `
                <div class="col-md-6 col-lg-4">
                    <div class="card taiga-list-card task-card h-100" data-task-id="${task.id}" data-task-ref="${task.ref}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="form-check">
                                    <input class="form-check-input task-checkbox" type="checkbox" value="${task.id}" data-version="${task.version}" id="task-${task.id}">
                                </div>
                                <div class="d-flex flex-column align-items-end">
                                    ${statusBadge}
                                    ${refHtml}
                                </div>
                            </div>
                            <h6 class="card-title text-truncate">${task.subject || 'Untitled Task'}</h6>
                            <p class="card-text taiga-card-description task-description text-muted small mb-0">${task.description || ''}</p>
                            <div class="taiga-card-meta">
                                <small class="text-muted d-block text-truncate">Assigned: <strong>${assignedTo}</strong></small>
                                ${usorHtml}
                            </div>
                        </div>
                        <div class="card-footer taiga-card-actions">
                            <a href="task.php?id=${task.id}" class="btn btn-sm btn-outline-secondary">View</a>
                            <button class="btn btn-outline-primary btn-sm edit-task" data-task-id="${task.id}" data-bs-toggle="modal" data-bs-target="#singleTaskModal">Edit</button>
                        </div>
                    </div>
                </div>`;
            });
            html += '</div>';
            $('#tasksContent').html(html);
            taigaBindSelectionLogic('task-checkbox', taigaBulkSelectionCallback);
        },
        populateBulkCreateDropdowns: function() {
            $('#bulkTaskProject').closest('.col-md-6').show();
            $('#bulkTaskUserStory').closest('.mb-3').show();

            TTTaiga.Form.populateDropdowns({
                projectSel: '#bulkTaskProject',
                statusSel: '#bulkTaskStatus',
                statusType: 'task',
                assigneeSel: '#bulkTaskAssignee',
                usorSel: '#bulkTaskUserStory'
            });

            const currentSearch = $('#searchInput').val();
            if (currentSearch) {
                $('#activeTaskSearchQuery').text(currentSearch);
                $('#bulkTaskSearchContext').removeClass('d-none');
            } else {
                $('#bulkTaskSearchContext').addClass('d-none');
            }
            $('#bulkTaskTitles').attr('placeholder', 'Enter task titles, one per line');
        },
        submitBulkCreate: function() {
            const titles = $('#bulkTaskTitles').val().trim().split('\n').filter(t => t.trim());
            if (titles.length === 0) {
                TTTaiga.UI.notify('Please enter at least one task title', 'warning');
                return;
            }

            const projectId = $('#bulkTaskProject').val() || $('#projectSelect').val();
            const userStoryId = $('#bulkTaskUserStory').val() || $('#userStorySelect').val();
            const statusId = $('#bulkTaskStatus').val();
            const assigneeId = $('#bulkTaskAssignee').val();
            const commonDescription = $('#bulkTaskDescription').val().trim();

            if (!projectId) {
                TTTaiga.UI.notify('Please select a project', 'warning');
                return;
            }

            const $btn = $('#submitBulkTaskCreate');
            $btn.prop('disabled', true).text('Creating...');
            $('.filter-toolbar, .btn, .dropdown-item').addClass('disabled');

            let createdCount = 0;
            let errorCount = 0;

            const currentSearch = $('#searchInput').val();
            const prependSearch = $('#prependTaskSearchCheck').is(':checked');

            titles.forEach(title => {
                let finalSubject = title;
                if (currentSearch && prependSearch) {
                    finalSubject = `[${currentSearch}] ${finalSubject}`;
                }

                const taskData = {
                    subject: finalSubject,
                    project: parseInt(projectId),
                    description: commonDescription
                };

                if (userStoryId) taskData.user_story = parseInt(userStoryId);
                if (statusId) taskData.status = parseInt(statusId);
                if (assigneeId) taskData.assigned_to = parseInt(assigneeId);

                TTTaiga.API.post('api.php/tasks', taskData, {
                    success: function () {
                        createdCount++;
                        if (createdCount + errorCount === titles.length) {
                            $btn.prop('disabled', false).text('Create Tasks');
                            $('.filter-toolbar, .btn, .dropdown-item').removeClass('disabled');
                            TTTaiga.UI.notify(`Successfully created ${createdCount} tasks!`, 'success');
                            $('#bulkCreateTaskModal').modal('hide');
                            TTTaiga.Tasks.load();
                        }
                    },
                    onError: function (xhr) {
                        console.error('Failed to create task:', title, xhr);
                        errorCount++;
                        if (createdCount + errorCount === titles.length) {
                            $btn.prop('disabled', false).text('Create Tasks');
                            $('.filter-toolbar, .btn, .dropdown-item').removeClass('disabled');
                            TTTaiga.UI.notify(`Created ${createdCount} tasks, but ${errorCount} failed.`, 'danger');
                        }
                    }
                });
            });
        },
        populateBulkUpdateDropdowns: function() {
            const filterParams = taigaGetFilterParams();
            const projectId = filterParams.project;

            const refreshUpdateInputs = function (selectedProjectId) {
                taigaPopulateBulkStatuses('task', $('#bulkUpdateTaskStatus'), selectedProjectId, 'No Change');
                taigaPopulateBulkMembers($('#bulkUpdateTaskAssignee'), selectedProjectId, 'No Change');

                $('#bulkUpdateTaskUsor, #bulkUpdateTaskSprint').each(function () {
                    const $select = $(this);
                    if ($select.data('select2')) {
                        $select.select2('destroy');
                    }
                    $select.empty().append(new Option(selectedProjectId ? 'No Change' : 'Select project first', '', false, false));
                    $select.prop('disabled', !selectedProjectId);
                });

                taigaInitRemoteSelect2('#bulkUpdateTaskUsor', '/userstories', {
                    placeholder: selectedProjectId ? 'No Change' : 'Select project first',
                    formatText: (item) => `#${item.ref}: ${item.subject}`,
                    additionalParams: () => selectedProjectId ? { project: selectedProjectId } : {}
                });

                taigaInitRemoteSelect2('#bulkUpdateTaskSprint', '/milestones', {
                    placeholder: selectedProjectId ? 'No Change' : 'Select project first',
                    additionalParams: () => selectedProjectId ? { project: selectedProjectId } : {}
                });
                $('#bulkUpdateTaskUsor, #bulkUpdateTaskSprint').prop('disabled', !selectedProjectId);
            };

            $('#bulkUpdateTaskProject').off('change.bulkTaskShared').on('change.bulkTaskShared', function () {
                refreshUpdateInputs($(this).val());
            });

            taigaPopulateProjectSelect($('#bulkUpdateTaskProject'), projectId).done(function () {
                if (projectId) {
                    $('#bulkUpdateTaskProject').val(String(projectId)).trigger('change.select2');
                }
                refreshUpdateInputs(projectId);
            });

            taigaLoadBulkItems('/tasks', $('#bulkUpdateTaskList'), item => {
                return `
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="${item.id}" data-version="${item.version}" id="bulk-task-${item.id}">
                        <label class="form-check-label" for="bulk-task-${item.id}">
                            #${item.ref}: ${item.subject}
                        </label>
                    </div>
                `;
            });
        },
        submitBulkUpdate: function() {
            const selectedTasks = [];
            $('#bulkUpdateTaskList input:checked').each(function () {
                selectedTasks.push({ id: $(this).val(), version: $(this).data('version') });
            });

            if (selectedTasks.length === 0) {
                TTTaiga.UI.notify('Please select at least one task to update', 'warning');
                return;
            }

            const updateData = {};
            const status = $('#bulkUpdateTaskStatus').val();
            if (status) updateData.status = parseInt(status);
            const assignee = $('#bulkUpdateTaskAssignee').val();
            if (assignee) updateData.assigned_to = parseInt(assignee);
            const usor = $('#bulkUpdateTaskUsor').val();
            if (usor) updateData.user_story = usor === 'null' ? null : parseInt(usor);
            const sprint = $('#bulkUpdateTaskSprint').val();
            if (sprint) updateData.milestone = sprint === 'null' ? null : parseInt(sprint);

            if (Object.keys(updateData).length === 0) {
                TTTaiga.UI.notify('Please select at least one field to update', 'warning');
                return;
            }

            const $btn = $('#submitBulkTaskUpdate');
            $btn.prop('disabled', true).text('Updating...');
            $('.filter-toolbar, .btn, .dropdown-item').addClass('disabled');

            taigaExecuteBulk('/tasks/', selectedTasks, 'PATCH', updateData, (successCount, errorCount) => {
                $btn.prop('disabled', false).text('Update Tasks');
                $('.filter-toolbar, .btn, .dropdown-item').removeClass('disabled');
                
                if (errorCount === 0) {
                    TTTaiga.UI.notify(`Successfully updated ${successCount} tasks!`, 'success');
                    $('#bulkUpdateTaskModal').modal('hide');
                    TTTaiga.Tasks.load();
                } else {
                    TTTaiga.UI.notify(`Updated ${successCount} tasks, but ${errorCount} failed.`, 'danger');
                }
            });
        },
        deleteBulk: function() {
            const selectedTasks = [];
            $('.task-checkbox:checked').each(function () {
                selectedTasks.push({ id: $(this).val(), version: $(this).data('version') });
            });

            if (selectedTasks.length === 0) {
                TTTaiga.UI.notify('Please select at least one task to delete', 'warning');
                return;
            }

            const $btn = $('#confirmBulkDeleteTasks');
            $btn.prop('disabled', true).text('Deleting...');
            $('.filter-toolbar, .btn, .dropdown-item').addClass('disabled');

            taigaExecuteBulk('/tasks/', selectedTasks, 'DELETE', null, (successCount, errorCount) => {
                $btn.prop('disabled', false).text('Delete Tasks');
                $('.filter-toolbar, .btn, .dropdown-item').removeClass('disabled');
                if (errorCount === 0) {
                    TTTaiga.UI.notify(`Successfully deleted ${successCount} tasks!`, 'success');
                    $('#bulkDeleteTaskModal').modal('hide');
                    TTTaiga.Tasks.load();
                } else {
                    TTTaiga.UI.notify(`Deleted ${successCount} tasks, but ${errorCount} failed.`, 'danger');
                }
            });
        }
    };

    // Bind event handlers
    $('#submitSingleTask').on('click', function () {
        TTTaiga.Form.saveModal({
            endpoint: 'api.php/tasks',
            formId: 'singleTaskForm',
            modalId: 'singleTaskModal',
            listFn: () => TTTaiga.Tasks.load(),
            data: {
                project: parseInt($('#singleTaskProject').val()),
                subject: $('#singleTaskSubject').val(),
                description: $('#singleTaskDescription').val(),
                user_story: $('#singleTaskUsor').val() ? parseInt($('#singleTaskUsor').val()) : undefined,
                milestone: $('#singleTaskSprint').val() ? parseInt($('#singleTaskSprint').val()) : undefined,
                status: $('#singleTaskStatus').val() ? parseInt($('#singleTaskStatus').val()) : undefined,
                assigned_to: $('#singleTaskAssignee').val() ? parseInt($('#singleTaskAssignee').val()) : undefined
            }
        });
    });
    $('#singleTaskModal').on('show.bs.modal', function (e) {
        const id = $(e.relatedTarget).data('task-id');
        if (!id || Number(id) <= 0) {
            $('#singleTaskModalLabel').text('Create Task');
            $('#singleTaskId').val('');
            $('#singleTaskVersion').val('');
            $('#singleTaskForm')[0].reset();
            TTTaiga.Form.populateDropdowns({
                projectSel: '#singleTaskProject',
                statusSel: '#singleTaskStatus',
                statusType: 'task',
                assigneeSel: '#singleTaskAssignee',
                usorSel: '#singleTaskUsor',
                sprintSel: '#singleTaskSprint'
            });
            return;
        }
        $('#singleTaskModalLabel').text('Edit Task');
        TTTaiga.API.get('api.php/tasks/' + id, {}, {
            success: function (task) {
                $('#singleTaskId').val(task.id);
                $('#singleTaskVersion').val(task.version);
                $('#singleTaskSubject').val(task.subject);
                $('#singleTaskDescription').val(task.description);
                TTTaiga.Form.populateDropdowns({
                    projectSel: '#singleTaskProject',
                    statusSel: '#singleTaskStatus',
                    statusType: 'task',
                    assigneeSel: '#singleTaskAssignee',
                    usorSel: '#singleTaskUsor',
                    sprintSel: '#singleTaskSprint'
                }, {
                    project: task.project,
                    status: task.status,
                    assigned_to: task.assigned_to,
                    user_story: task.user_story,
                    milestone: task.milestone,
                    project_label: task.project_extra_info ? task.project_extra_info.name : null,
                    user_story_label: task.user_story_extra_info ? '#' + task.user_story_extra_info.ref + ': ' + task.user_story_extra_info.subject : null,
                    milestone_label: task.milestone_extra_info ? task.milestone_extra_info.name : null
                });
            }
        });
    });
    $('#bulkCreateTaskModal').on('show.bs.modal', function() { 
        TTTaiga.Tasks.populateBulkCreateDropdowns(); 
    });
    $('#submitBulkTaskCreate').on('click', function() { TTTaiga.Tasks.submitBulkCreate(); });
    $('#bulkUpdateTaskModal').on('show.bs.modal', function() { TTTaiga.Tasks.populateBulkUpdateDropdowns(); });
    $('#submitBulkTaskUpdate').on('click', function() { TTTaiga.Tasks.submitBulkUpdate(); });
    $('#confirmBulkDeleteTasks').on('click', function() { TTTaiga.Tasks.deleteBulk(); });

    taigaBindFilters((page) => TTTaiga.Tasks.load(page));

    taigaApplyFiltersFromUrl()
        .then(page => TTTaiga.Tasks.load(page))
        .catch(() => TTTaiga.Tasks.load(1));
});
