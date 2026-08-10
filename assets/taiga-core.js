window.TTTaiga = {
    API: {},
    UI: {},
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
