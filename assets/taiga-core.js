window.TTTaiga = {
    API: {},
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
    Form: {},
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
