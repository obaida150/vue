import { ref } from "vue"
import VacationService from "@/Services/VacationService"

export function useVacations() {
  const vacations = ref([])

  const fetchVacationData = async () => {
    try {
      // Verwende VacationService statt direktem API-Aufruf
      const response = await VacationService.getUserVacationData()

      if (response && response.data && response.data.requests) {
        const vacationRequests = response.data.requests.filter((req) => req.status === "approved")

        const vacationEntries = vacationRequests.map((req) => ({
          id: `vacation-${req.id}`,
          title: "Urlaub",
          description: req.notes || "Genehmigter Urlaub",
          startDate: new Date(req.startDate),
          endDate: new Date(req.endDate),
          isAllDay: true,
          status: "approved",
          type: {
            name: "Urlaub",
            value: "vacation",
            color: "#9C27B0",
          },
          color: "#9C27B0",
          source: "vacation",
          user_id: req.user_id,
          employee_name: req.employee_name || "",
        }))

        vacations.value = [...vacations.value, ...vacationEntries]
      }
    } catch (error) {
      console.error("Fehler beim Laden der Urlaubsdaten aus VacationService:", error)
    }
  }

  return {
    vacations,
    fetchVacationData,
  }
}
