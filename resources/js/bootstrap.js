import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// CSRF-Token für alle Axios-Requests setzen
let token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

// Axios-Interceptor für PWA-Offline-Handling
window.axios.interceptors.response.use(
    response => response,
    error => {
        if (!navigator.onLine) {
            console.log('Offline - Request wird für später gespeichert');
            // Hier könnten Sie die Anfrage für später speichern
        }
        return Promise.reject(error);
    }
);