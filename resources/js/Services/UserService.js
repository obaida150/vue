import axios from "axios"

class UserService {
    /**
     * Benutzer abrufen
     */
    getUsers() {
        return axios.get("/api/users")
    }

    /**
     * Einzelnen Benutzer abrufen
     */
    getUser(id) {
        return axios.get(`/api/users/${id}`)
    }

    /**
     * Neuen Benutzer erstellen
     */
    createUser(userData) {
        return axios.post("/api/users", userData)
    }

    /**
     * Benutzer aktualisieren
     */
    updateUser(id, userData) {
        return axios.put(`/api/users/${id}`, userData)
    }

    /**
     * Benutzer löschen
     */
    deleteUser(id) {
        return axios.delete(`/api/users/${id}`)
    }

    /**
     * Abteilungen abrufen
     */
    getDepartments() {
        return axios.get("/api/departments")
    }

    /**
     * Rollen abrufen
     */
    getRoles() {
        return axios.get("/api/roles")
    }
}

export default new UserService()
