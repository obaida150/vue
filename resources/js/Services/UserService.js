import axios from "axios"

export default {
    // Alle Benutzer abrufen
    getUsers() {
        return axios.get("/api/users")
    },

    // Einen bestimmten Benutzer abrufen
    getUser(id) {
        return axios.get(`/api/users/${id}`)
    },

    // Einen neuen Benutzer erstellen
    createUser(userData) {
        return axios.post("/api/users", userData)
    },

    // Einen bestehenden Benutzer aktualisieren
    updateUser(id, userData) {
        return axios.put(`/api/users/${id}`, userData)
    },

    // Einen Benutzer löschen (deaktivieren)
    deleteUser(id) {
        return axios.delete(`/api/users/${id}`)
    },

    // Alle Abteilungen abrufen
    getDepartments() {
        return axios.get("/api/departments")
    },

    // Alle Rollen abrufen
    getRoles() {
        return axios.get("/api/roles")
    },
}

