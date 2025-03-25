import axios from "axios"

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

