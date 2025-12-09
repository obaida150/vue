import { ref, computed } from "vue"
import axios from "axios"

export function useUserRole() {
    const currentUserRoleId = ref(null)
    const currentUserId = ref(null)
    const isTeamManager = ref(false)
    const userTeamId = ref(null)
    const employees = ref([])
    const hasHrPermissions = ref(false)

    // Computed Properties
    const isHrUser = computed(
        () => currentUserRoleId.value === 1 || currentUserRoleId.value === 2 || hasHrPermissions.value === true,
    )

    const canEditEvent = computed(() => {
        return (event) => {
            // HR-Benutzer dürfen alle Ereignisse bearbeiten
            if (isHrUser.value) return true

            // Eigene Ereignisse darf jeder bearbeiten
            if (event.user_id === currentUserId.value) return true

            // Abteilungsleiter dürfen Ereignisse ihrer Teammitglieder bearbeiten
            if (isTeamManager.value && userTeamId.value) {
                // Prüfen, ob das Ereignis zum Team des Abteilungsleiters gehört
                return event.team_id === userTeamId.value
            }

            return false
        }
    })

    const fetchUserRole = async () => {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content")
            const config = {
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
                withCredentials: true,
            }

            const response = await axios.get("/api/user/role", config)

            // console.log("Backend response from /api/user/role:", response.data)
            // console.log("role_id from backend:", response.data.role_id)
            // console.log("user_id from backend:", response.data.user_id)
            // console.log("has_hr_permissions from backend:", response.data.has_hr_permissions)

            currentUserRoleId.value = response.data.role_id
            currentUserId.value = response.data.user_id
            isTeamManager.value = response.data.is_team_manager || false
            userTeamId.value = response.data.team_id || null
            hasHrPermissions.value = !!response.data.has_hr_permissions

            // console.log("After setting - currentUserRoleId:", currentUserRoleId.value)
            // console.log("After setting - hasHrPermissions:", hasHrPermissions.value)
            // console.log("After setting - isHrUser:", isHrUser.value)

            // Wenn der Benutzer ein Abteilungsleiter ist, lade die Teammitglieder
            if (isTeamManager.value && response.data.team_members) {
                employees.value = response.data.team_members
            }
        } catch (error) {
            // console.error("Fehler beim Laden der Benutzerrolle:", error)
            // console.error("Error response:", error.response?.data)
            // console.error("Error status:", error.response?.status)
            currentUserRoleId.value = null
            currentUserId.value = null
            isTeamManager.value = false
            userTeamId.value = null
            hasHrPermissions.value = false
        }
    }

    const fetchEmployees = async () => {
        if (currentUserRoleId.value === null) {
            // console.log("Waiting for user role to be loaded...")
            await fetchUserRole()
        }

        console.log(
            "  fetchEmployees called - role_id:",
            currentUserRoleId.value,
            "hasHrPermissions:",
            hasHrPermissions.value,
            "isHr:",
            isHrUser.value,
        )

        if (currentUserRoleId.value === 1 || currentUserRoleId.value === 2 || hasHrPermissions.value) {
            try {
                // console.log("Admin/HR User - Fetching all employees from /api/users...")

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content")
                const config = {
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    withCredentials: true,
                }

                const response = await axios.get("/api/users", config)

                // console.log("All employees loaded for HR:", response.data.length)
                employees.value = response.data
                return
            } catch (error) {
                // console.error("Fehler beim Laden der Mitarbeiterliste:", error)
                employees.value = []
                return
            }
        }

        // Für Abteilungsleiter: Verwende Teammitglieder, wenn bereits geladen
        if (isTeamManager.value && employees.value.length > 0) return

        // Nur für Abteilungsleiter laden (falls noch nicht geladen)
        if (!isTeamManager.value) return

        try {
            // console.log("Team Manager - Fetching employees from /api/users...")

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content")
            const config = {
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
                withCredentials: true,
            }

            const response = await axios.get("/api/users", config)

            // console.log("Employees loaded for Team Manager:", response.data.length)
            employees.value = response.data
        } catch (error) {
            // console.error("Fehler beim Laden der Mitarbeiterliste:", error)
            // console.error("Error response:", error.response?.data)
            // console.error("Error status:", error.response?.status)
            employees.value = []
        }
    }

    return {
        currentUserRoleId,
        currentUserId,
        isTeamManager,
        userTeamId,
        employees,
        hasHrPermissions,
        isHrUser,
        canEditEvent,
        fetchUserRole,
        fetchEmployees,
    }
}
