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

    TTTaiga.Tasks = {
        load: function (page = 1) {
            taigaReplaceUrlQuery({ ...taigaGetFilterParams(), page: page });
            const params = { ...taigaGetFilterParams(), page: page };

            $('#tasksContent').html(`
                <div class="loading-spinner text-center p-3">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            `);

            $.ajax({
                url: 'api.php/tasks',
                type: 'GET',
                data: params,
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Content-Type': 'application/json',
                    'X-Taiga-Api-Url': apiUrl
                },
                success: function (tasks, status, xhr) {
                    this.render(tasks, xhr);
                    taigaRenderPagination(xhr, '#tasksPagination', (page) => this.load(page));
                }.bind(this),
                error: function (xhr) {
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
                const assignedTo = task.assigned_to_extra ? task.assigned_to_extra.full_name_display : (task.assigned_to ? 'User ID: ' + task.assigned_to : 'Unassigned');
                const owner = task.owner_extra ? task.owner_extra.full_name_display : 'Unknown';

                html += `
                <div class="col-md-6 col-lg-4">
                    <div class="card taiga-list-card task-card h-100" data-task-id="${task.id}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="form-check">
                                    <input class="form-check-input task-checkbox" type="checkbox" value="${task.id}" id="task-${task.id}">
                                </div>
                                ${statusBadge}
                            </div>
                            <h6 class="card-title text-truncate">${task.subject || 'Untitled Task'}</h6>
                            <p class="card-text taiga-card-description task-description text-muted small mb-0">${task.description || ''}</p>
                            <div class="taiga-card-meta">
                                <small class="text-muted d-block text-truncate">Assigned: <strong>${assignedTo}</strong></small>
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
            $('.task-checkbox').off('change').on('change', this.updateSelectionCount);
        },
        updateSelectionCount: function() {
            $('#selectedTasksCount').text($('#tasksContent input:checked').length);
        },
        populateBulkCreateDropdowns: function() {
            const currentProjectId = $('#projectSelect').val();
            const currentUserStoryId = $('#userStorySelect').val();
            const usText = $('#userStorySelect option:selected').text();

            $('#bulkTaskProject').closest('.col-md-6').show();
            $('#bulkTaskUserStory').closest('.mb-3').show();

            const refreshProjectInputs = function (projectId, selectedUserStoryId, selectedUserStoryText) {
                taigaPopulateBulkStatuses('task', $('#bulkTaskStatus'), projectId);
                taigaPopulateBulkMembers($('#bulkTaskAssignee'), projectId);

                const $userStory = $('#bulkTaskUserStory');
                if ($userStory.data('select2')) {
                    $userStory.select2('destroy');
                }
                $userStory.empty().append(new Option(projectId ? 'Select Usor' : 'Select project first', '', false, false));
                $userStory.prop('disabled', !projectId);
                taigaInitRemoteSelect2('#bulkTaskUserStory', '/userstories', {
                    placeholder: projectId ? 'Select Usor' : 'Select project first',
                    formatText: (item) => `#${item.ref}: ${item.subject}`,
                    additionalParams: () => projectId ? { project: projectId } : {}
                });
                $userStory.prop('disabled', !projectId);
                if (selectedUserStoryId) {
                    $userStory.append(new Option(selectedUserStoryText || ('Usor ' + selectedUserStoryId), selectedUserStoryId, true, true)).trigger('change');
                }
            };

            $('#bulkTaskProject').off('change.bulkTaskShared').on('change.bulkTaskShared', function () {
                refreshProjectInputs($(this).val(), null, null);
            });

            taigaPopulateProjectSelect($('#bulkTaskProject'), currentProjectId).done(function () {
                if (currentProjectId) {
                    $('#bulkTaskProject').val(String(currentProjectId)).trigger('change.select2');
                }
                refreshProjectInputs(currentProjectId, currentUserStoryId, usText);
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
                alert('Please enter at least one task title');
                return;
            }

            const projectId = $('#bulkTaskProject').val() || $('#projectSelect').val();
            const userStoryId = $('#bulkTaskUserStory').val() || $('#userStorySelect').val();
            const statusId = $('#bulkTaskStatus').val();
            const assigneeId = $('#bulkTaskAssignee').val();
            const commonDescription = $('#bulkTaskDescription').val().trim();

            if (!projectId) {
                alert('Please select a project');
                return;
            }

            const $btn = $('#submitBulkTaskCreate');
            $btn.prop('disabled', true).text('Creating...');

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

                $.ajax({
                    url: 'api.php/tasks',
                    type: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + window.taigaToken,
                        'Content-Type': 'application/json',
                        'X-Taiga-Api-Url': window.apiUrl
                    },
                    data: JSON.stringify(taskData),
                    success: function () {
                        createdCount++;
                        if (createdCount + errorCount === titles.length) {
                            $btn.prop('disabled', false).text('Create Tasks');
                            alert(`Successfully created ${createdCount} tasks!`);
                            $('#bulkCreateTaskModal').modal('hide');
                            TTTaiga.Tasks.load();
                        }
                    },
                    error: function (xhr) {
                        console.error('Failed to create task:', title, xhr);
                        errorCount++;
                        if (createdCount + errorCount === titles.length) {
                            $btn.prop('disabled', false).text('Create Tasks');
                            alert(`Created ${createdCount} tasks, but ${errorCount} failed.`);
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
                selectedTasks.push({
                    id: $(this).val(),
                    version: $(this).data('version')
                });
            });

            if (selectedTasks.length === 0) {
                alert('Please select at least one task to update');
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
                alert('Please select at least one field to update');
                return;
            }

            const $btn = $('#submitBulkTaskUpdate');
            $btn.prop('disabled', true).text('Updating...');

            taigaExecuteBulk('/tasks/', selectedTasks, 'PATCH', updateData, (successCount, errorCount) => {
                $btn.prop('disabled', false).text('Update Tasks');
                if (errorCount === 0) {
                    alert(`Successfully updated ${successCount} tasks!`);
                    $('#bulkUpdateTaskModal').modal('hide');
                    TTTaiga.Tasks.load();
                } else {
                    alert(`Updated ${successCount} tasks, but ${errorCount} failed.`);
                }
            });
        },
        deleteBulk: function() {
            const selectedTasks = [];
            $('.task-checkbox:checked').each(function () {
                selectedTasks.push({
                    id: $(this).val(),
                    version: $(this).data('version')
                });
            });

            if (selectedTasks.length === 0) {
                alert('Please select at least one task to delete');
                return;
            }

            const $btn = $('#confirmBulkDeleteTasks');
            $btn.prop('disabled', true).text('Deleting...');

            taigaExecuteBulk('/tasks/', selectedTasks, 'DELETE', null, (successCount, errorCount) => {
                $btn.prop('disabled', false).text('Delete Tasks');
                if (errorCount === 0) {
                    alert(`Successfully deleted ${successCount} tasks!`);
                    $('#bulkDeleteTaskModal').modal('hide');
                    TTTaiga.Tasks.load();
                } else {
                    alert(`Deleted ${successCount} tasks, but ${errorCount} failed.`);
                }
            });
        }
    };

    // Bind event handlers
    $('#bulkCreateTaskModal').on('show.bs.modal', function() { 
        TTTaiga.Tasks.populateBulkCreateDropdowns(); 
        
        // Autofill from shared state
        const state = TTTaiga.State.getState();
        if(state.projectSelect) $('#bulkTaskProject').val(state.projectSelect).trigger('change.select2');
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
