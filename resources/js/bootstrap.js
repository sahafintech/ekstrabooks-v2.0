import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

axios.defaults.withCredentials = true;

axios.interceptors.request.use(function (config) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    if (!csrfToken) {
        const cookies = document.cookie.split(';');
        for (let cookie of cookies) {
            const [name, value] = cookie.trim().split('=');
            if (name === 'XSRF-TOKEN') {
                config.headers['X-XSRF-TOKEN'] = decodeURIComponent(value);
                break;
            }
        }
    } else {
        config.headers['X-CSRF-TOKEN'] = csrfToken;
    }
    
    return config;
}, function (error) {
    return Promise.reject(error);
});
