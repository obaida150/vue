import axios from "axios"

export default {
    // Fetch user's vacation data
    getUserVacationData() {
        return axios.get("/api/vacation/user")
    },

    // Fetch yearly vacation data
    getYearlyVacationData(year) {
        return axios.get(`/api/vacation/yearly/${year}`)
    },

    // Fetch all vacation requests for management
    getVacationRequests() {
        return axios.get("/api/vacation/requests")
    },

    // Fetch company calendar data
    getCompanyCalendarData() {
        return axios.get("/api/calendar/company")
    },

    // Submit a vacation request
    submitVacationRequest(data) {
        return axios.post("/api/vacation/submit", data)
    },

    // Approve a vacation request
    approveVacationRequest(id, notes) {
        return axios.post(`/api/vacation/approve/${id}`, { notes })
    },

    // Reject a vacation request
    rejectVacationRequest(id, reason) {
        return axios.post(`/api/vacation/reject/${id}`, { reason })
    },

    cancelVacationRequest(id) {
        return axios.post(`/api/vacation/cancel/${id}`)
    },

    // Hilfsfunktion zum Filtern von Urlaubstagen nach Benutzer-ID
    filterVacationsByUserId(vacations, userId) {
        if (!vacations || !Array.isArray(vacations)) return []
        return vacations.filter((vacation) => vacation.userId === userId)
    },
}
