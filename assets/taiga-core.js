// --- Session Init ---

(() => {
    const session = globalThis.TTTaigaSession || {};
    let model = session.user || localStorage.getItem('taiga_user');
    if (typeof model === 'string' && model) {
        try { model = JSON.parse(model); } catch (e) { model = null; }
    }
    globalThis.taigaModel = model;
    if (!globalThis.taigaToken) globalThis.taigaToken = session.authenticated ? 'session' : localStorage.getItem('taiga_token');
    if (!globalThis.apiUrl) globalThis.apiUrl = session.apiUrl || localStorage.getItem('taiga_api_url');
})();

window.TTTaiga = {
    API: {
        _clearAuth: function () {
            localStorage.removeItem('taiga_token');
            localStorage.removeItem('taiga_user');
            localStorage.removeItem('taiga_api_url');
        },
        _headers: function () {
            return {
                'Authorization': 'Bearer ' + (window.taigaToken || ''),
                'Content-Type': 'application/json',
                'X-Taiga-Api-Url': window.apiUrl || ''
            };
        },
        _handleError: function (xhr, url) {
            if ((xhr.status === 401 || xhr.status === 403) && url.indexOf('api.php') !== -1 && !window.location.pathname.endsWith('login.php')) {
                this._clearAuth();
                window.location.href = 'login.php';
            }
            return xhr;
        },
        request: function (method, endpoint, data, opts = {}) {
            const settings = {
                url: endpoint,
                type: method,
                headers: this._headers(),
                dataType: 'json',
                error: function (xhr) {
                    this._handleError(xhr, endpoint);
                    if (typeof opts.onError === 'function') opts.onError(xhr);
                }.bind(this)
            };
            if (data && method !== 'GET') settings.data = JSON.stringify(data);
            if (data && method === 'GET') settings.data = data;
            if (opts.success) settings.success = opts.success;
            if (opts.complete) settings.complete = opts.complete;
            return $.ajax(settings);
        },
        get: function (endpoint, params = {}, opts = {}) {
            return this.request('GET', endpoint, params, opts);
        },
        post: function (endpoint, data = {}, opts = {}) {
            return this.request('POST', endpoint, data, opts);
        },
        patch: function (endpoint, data = {}, opts = {}) {
            return this.request('PATCH', endpoint, data, opts);
        },
        _delete: function (endpoint, opts = {}) {
            return this.request('DELETE', endpoint, null, opts);
        }
    },
    UI: {
        notify: function(message, type = 'primary') {
            const containerId = 'taigaToastContainer';
            let $container = $('#' + containerId);
            if (!$container.length) {
                $('body').append(`<div id="${containerId}" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;"></div>`);
                $container = $('#' + containerId);
            }

            const toastId = 'toast-' + Date.now();
            const html = `
                <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header">
                        <strong class="me-auto text-${type}">Notification</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">${message}</div>
                </div>`;

            $container.append(html);
            const toastEl = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
            toast.show();

            toastEl.addEventListener('hidden.bs.toast', () => {
                $(toastEl).remove();
            });
        }
    },
    Form: {
        _getFormData: function (formId) {
            const data = {};
            const prefix = formId.replace('Form', '');
            $('#' + formId).find('input, select, textarea').each(function () {
                const $el = $(this);
                const id = $el.attr('id') || '';
                if (!id || $el.is(':disabled')) return;
                const field = id.replace(prefix, '');
                if (!field || field === 'Id' || field === 'Version') return;
                const val = $el.val();
                if ($el.is(':checkbox')) {
                    data[field.charAt(0).toLowerCase() + field.slice(1)] = $el.is(':checked');
                } else {
                    data[field.charAt(0).toLowerCase() + field.slice(1)] = val;
                }
            });
            return data;
        },
        saveModal: function (opts) {
            const { endpoint, formId, modalId, listFn } = opts;
            const $form = $('#' + formId);
            const id = $('#' + formId + 'Id').val();
            const version = $('#' + formId + 'Version').val();
            const formData = opts.data || this._getFormData(formId);
            const method = id ? 'PATCH' : 'POST';
            const url = id ? endpoint + '/' + id : endpoint;
            const payload = id && version ? { ...formData, version: parseInt(version) } : formData;

            const $btn = $form.closest('.modal').find('[type="submit"], .btn-primary').last();
            $btn.prop('disabled', true).text('Saving...');

            TTTaiga.API.request(method, url, payload, {
                success: function () {
                    $btn.prop('disabled', false).text('Save');
                    $('#' + modalId).modal('hide');
                    TTTaiga.UI.notify(id ? 'Updated successfully' : 'Created successfully', 'success');
                    $form[0].reset();
                    $('#' + formId + 'Id').val('');
                    $('#' + formId + 'Version').val('');
                    if (typeof listFn === 'function') listFn();
                },
                onError: function (xhr) {
                    $btn.prop('disabled', false).text('Save');
                    const msg = xhr.responseJSON?.error || xhr.responseJSON?._error_message || 'Failed to save';
                    TTTaiga.UI.notify(msg, 'danger');
                }
            });
        },
        populate: function (formId, data) {
            const prefix = formId.replace('Form', '');
            Object.keys(data).forEach(key => {
                const field = key.charAt(0).toUpperCase() + key.slice(1);
                const $el = $('#' + prefix + field);
                if (!$el.length) return;
                if ($el.is(':checkbox')) {
                    $el.prop('checked', !!data[key]);
                } else {
                    $el.val(data[key]);
                }
            });
        }
    },
    Filter: {},
    State: {
        subscribers: [],
        data: JSON.parse(localStorage.getItem('taiga_state')) || {},
        subscribe: function(callback) {
            this.subscribers.push(callback);
        },
        setState: function(newState) {
            this.data = { ...this.data, ...newState };
            localStorage.setItem('taiga_state', JSON.stringify(this.data));
            this.subscribers.forEach(callback => callback(this.data));
        },
        getState: function() {
            return this.data;
        }
    }
};

// --- Shared Filter Logic (extracted from app.js) ---

const TTTTAIGA_SHARED_FILTER_KEYS = ['q', 'project', 'status', 'epic', 'user_story', 'assigned_to', 'milestone', 'order_by'];
const TTTTAIGA_SHARED_FILTER_MODULES = new Set(['projects.php', 'sprints.php', 'epiks.php', 'usors.php', 'tasks.php', 'isus.php']);
const TTTTAIGA_SHARED_FILTER_STORAGE_KEY = 'tttaiga_shared_filters';

function tttaigaCurrentPageName() {
    const page = window.location.pathname.split('/').pop();
    return page || 'index.php';
}

function tttaigaIsSharedFilterPage(page) {
    return TTTTAIGA_SHARED_FILTER_MODULES.has(page || tttaigaCurrentPageName());
}

function tttaigaBuildSharedFilterParams(params) {
    const shared = new URLSearchParams();
    TTTTAIGA_SHARED_FILTER_KEYS.forEach(key => {
        const value = params.get(key);
        if (value !== null && String(value).trim() !== '') {
            shared.set(key, value);
        }
    });
    return shared;
}

function tttaigaReadStoredSharedFilters() {
    try {
        const raw = sessionStorage.getItem(TTTTAIGA_SHARED_FILTER_STORAGE_KEY);
        if (!raw) return new URLSearchParams();
        const parsed = JSON.parse(raw);
        const params = new URLSearchParams();
        TTTTAIGA_SHARED_FILTER_KEYS.forEach(key => {
            if (parsed && parsed[key] !== undefined && String(parsed[key]).trim() !== '') {
                params.set(key, String(parsed[key]));
            }
        });
        return params;
    } catch (e) {
        return new URLSearchParams();
    }
}

function tttaigaStoreSharedFilters(params) {
    const shared = params instanceof URLSearchParams
        ? tttaigaBuildSharedFilterParams(params)
        : tttaigaBuildSharedFilterParams(new URLSearchParams(params || {}));
    const payload = {};
    shared.forEach((value, key) => { payload[key] = value; });
    try {
        if (Object.keys(payload).length) {
            sessionStorage.setItem(TTTTAIGA_SHARED_FILTER_STORAGE_KEY, JSON.stringify(payload));
        } else {
            sessionStorage.removeItem(TTTTAIGA_SHARED_FILTER_STORAGE_KEY);
        }
    } catch (e) {}
    return shared;
}

function tttaigaSharedFilterParams() {
    const current = tttaigaBuildSharedFilterParams(new URLSearchParams(window.location.search));
    if ([...current.keys()].length > 0) return current;
    return tttaigaReadStoredSharedFilters();
}

function tttaigaPersistSharedFilters(params) {
    if (!tttaigaIsSharedFilterPage()) return new URLSearchParams();
    const source = params instanceof URLSearchParams ? params : new URLSearchParams(params || {});
    const shared = tttaigaStoreSharedFilters(source);
    tttaigaBindSharedFilterNavigation();
    return shared;
}

function tttaigaEnsureSharedFiltersInUrl() {
    if (!tttaigaIsSharedFilterPage()) return;
    const url = new URL(window.location.href);
    const current = tttaigaBuildSharedFilterParams(url.searchParams);
    if ([...current.keys()].length > 0) {
        tttaigaStoreSharedFilters(url.searchParams);
        return;
    }
    const stored = tttaigaReadStoredSharedFilters();
    if ([...stored.keys()].length === 0) return;
    stored.forEach((value, key) => { url.searchParams.set(key, value); });
    window.history.replaceState(null, '', url.toString());
}

function tttaigaUrlWithSharedFilters(href) {
    const shared = tttaigaSharedFilterParams();
    const url = new URL(href, window.location.href);
    TTTTAIGA_SHARED_FILTER_KEYS.forEach(key => { url.searchParams.delete(key); });
    shared.forEach((value, key) => { url.searchParams.set(key, value); });
    return url.pathname.split('/').pop() + (url.search ? url.search : '') + (url.hash || '');
}

function tttaigaBindSharedFilterNavigation() {
    $('a[href]').each(function () {
        const rawHref = $(this).attr('href');
        if (!rawHref || rawHref.startsWith('#') || rawHref.includes('://')) return;
        const page = rawHref.split('?')[0].split('#')[0];
        if (!tttaigaIsSharedFilterPage(page)) return;
        $(this).attr('href', tttaigaUrlWithSharedFilters(rawHref));
    });
    $(document).off('click.sharedFilters', 'a[href]').on('click.sharedFilters', 'a[href]', function () {
        const rawHref = $(this).attr('href');
        if (!rawHref || rawHref.startsWith('#') || rawHref.includes('://')) return;
        const page = rawHref.split('?')[0].split('#')[0];
        if (!tttaigaIsSharedFilterPage(page)) return;
        $(this).attr('href', tttaigaUrlWithSharedFilters(rawHref));
    });
}

// --- HTML Sanitizer ---

function tttaigaSanitizeHtml(html) {
    const template = document.createElement('template');
    template.innerHTML = String(html ?? '');
    const blockedTags = new Set(['SCRIPT', 'STYLE', 'IFRAME', 'OBJECT', 'EMBED', 'LINK', 'META']);
    template.content.querySelectorAll('*').forEach(node => {
        if (blockedTags.has(node.tagName)) { node.remove(); return; }
        Array.from(node.attributes).forEach(attr => {
            const name = attr.name.toLowerCase();
            const value = String(attr.value || '').trim().toLowerCase();
            if (name.startsWith('on')) { node.removeAttribute(attr.name); return; }
            if ((name === 'href' || name === 'src' || name === 'xlink:href') && value.startsWith('javascript:')) { node.removeAttribute(attr.name); return; }
            if (name === 'style' && /expression\s*\(|javascript:|url\s*\(/i.test(attr.value)) { node.removeAttribute(attr.name); }
        });
    });
    return template.innerHTML;
}

function tttaigaInstallHtmlSanitizer() {
    if (!window.jQuery || $.fn.__tttaigaHtmlSanitized) return;
    const originalHtml = $.fn.html;
    $.fn.html = function (value) {
        if (typeof value === 'string') return originalHtml.call(this, tttaigaSanitizeHtml(value));
        if (typeof value === 'function') return originalHtml.call(this, function (index, oldHtml) {
            const nextValue = value.call(this, index, oldHtml);
            return typeof nextValue === 'string' ? tttaigaSanitizeHtml(nextValue) : nextValue;
        });
        return originalHtml.apply(this, arguments);
    };
    $.fn.__tttaigaHtmlSanitized = true;
}

// --- Auto-init ---

tttaigaEnsureSharedFiltersInUrl();

$(document).ready(function () {
    tttaigaInstallHtmlSanitizer();
    tttaigaBindSharedFilterNavigation();

    $(document).ajaxError(function (_event, xhr, settings) {
        const url = settings && settings.url ? String(settings.url) : '';
        if ((xhr.status === 401 || xhr.status === 403) && url.indexOf('api.php') !== -1 && !window.location.pathname.endsWith('login.php')) {
            tttaigaClearAuthState();
            window.location.href = 'login.php';
        }
    });

    $('#logoutBtn').on('click', function () {
        tttaigaClearAuthState();
        $.post('login.php', { action: 'logout' }).always(function () {
            window.location.href = 'login.php';
        });
    });
});

function tttaigaClearAuthState() {
    localStorage.removeItem('taiga_token');
    localStorage.removeItem('taiga_user');
    localStorage.removeItem('taiga_api_url');
}
