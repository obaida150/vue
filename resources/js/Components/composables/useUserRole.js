import { ref, computed } from "vue"
import axios from "axios"

export function useUserRole() {
  const currentUserRoleId = ref(null)
  const currentUserId = ref(null)
  const isTeamManager = ref(false)
  const userTeamId = ref(null)
  const employees = ref([])

  // Computed Properties
  const isHrUser = computed(() => currentUserRoleId.value === 2)

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
      currentUserRoleId.value = response.data.role_id
      currentUserId.value = response.data.user_id
      isTeamManager.value = response.data.is_team_manager || false
      userTeamId.value = response.data.team_id || null

      // Wenn der Benutzer ein Abteilungsleiter ist, lade die Teammitglieder
      if (isTeamManager.value && response.data.team_members) {
        employees.value = response.data.team_members
      }
    } catch (error) {
      console.error("Fehler beim Laden der Benutzerrolle:", error)
      currentUserRoleId.value = null
      currentUserId.value = null
      isTeamManager.value = false
      userTeamId.value = null
    }
  }

  const fetchEmployees = async () => {
    // Wenn bereits Teammitglieder geladen wurden und der Benutzer ein Abteilungsleiter ist, nicht erneut laden
    if (isTeamManager.value && employees.value.length > 0) return

    // Nur für HR-Benutzer oder Abteilungsleiter laden
    if (!isHrUser.value && !isTeamManager.value) return

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

      const response = await axios.get("/api/employees", config)
      employees.value = response.data
    } catch (error) {
      console.error("Fehler beim Laden der Mitarbeiterliste:", error)
      employees.value = []
    }
  }

  return {
    currentUserRoleId,
    currentUserId,
    isTeamManager,
    userTeamId,
    employees,
    isHrUser,
    canEditEvent,
    fetchUserRole,
    fetchEmployees,
  }
}
