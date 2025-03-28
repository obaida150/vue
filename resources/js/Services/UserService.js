import axios from "axios"

// Konfiguriere Axios für CSRF-Schutz und Authentifizierung
axios.defaults.withCredentials = true
axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest"

// CSRF-Token aus dem Meta-Tag holen
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content")
if (csrfToken) {
    axios.defaults.headers.common["X-CSRF-TOKEN"] = csrfToken
}

export default {
    // Fetch all users
    getUsers() {
        return axios.get("/api/users")
    },

    // Get a specific user
    getUser(id) {
        return axios.get(`/api/users/${id}`)
    },

    // Create a new user
    createUser(userData) {
        return axios.post("/api/users", userData)
    },

    // Update a user
    updateUser(id, userData) {
        return axios.put(`/api/users/${id}`, userData)
    },

    // Delete a user
    deleteUser(id) {
        return axios.delete(`/api/users/${id}`)
    },

    // Get departments
    getDepartments() {
        return axios.get("/api/departments")
    },

    // Get roles
    getRoles() {
        return axios.get("/api/roles")
    },
}

