import axios from "axios"

export default {
    // Geburtstags-Benachrichtigungen abrufen
    getBirthdayNotifications() {
        return axios.get("/api/notifications/birthdays")
    },
}

