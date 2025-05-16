import { ref, computed } from "vue"
import dayjs from "dayjs"
import VacationService from "@/Services/VacationService"

export function useVacations() {
    const vacations = ref([])
    const loading = ref(false)
    const error = ref(null)
    const currentUserId = ref(null)

    // Lade alle Urlaubsdaten
    const fetchVacations = async () => {
        loading.value = true
        error.value = null
        try {
            const response = await VacationService.getUserVacationData()
            vacations.value = response.data.bookedDates || []
            currentUserId.value = response.data.userId
            return response.data
        } catch (err) {
            console.error("Fehler beim Laden der Urlaubsdaten:", err)
            error.value = "Die Urlaubsdaten konnten nicht geladen werden."
            return null
        } finally {
            loading.value = false
        }
    }

    // Filtere Urlaubstage nach Benutzer-ID
    const filterVacationsByUserId = (userId) => {
        return vacations.value.filter((vacation) => vacation.userId === userId)
    }

    // Eigene Urlaubstage
    const myVacations = computed(() => {
        return filterVacationsByUserId(currentUserId.value)
    })

    // Urlaubstage von anderen Teammitgliedern
    const teamVacations = computed(() => {
        return vacations.value.filter((vacation) => vacation.userId !== currentUserId.value)
    })

    // Prüfe, ob ein bestimmtes Datum in den Urlaubstagen enthalten ist
    const isDateInVacation = (date, userId = null) => {
        const targetDate = dayjs(date)

        // Wenn keine userId angegeben ist, prüfe alle Urlaubstage
        const vacationsToCheck = userId ? filterVacationsByUserId(userId) : vacations.value

        return vacationsToCheck.some((vacation) => {
            const startDate = dayjs(vacation.start)
            const endDate = dayjs(vacation.end)
            return (
                (targetDate.isAfter(startDate, "day") || targetDate.isSame(startDate, "day")) &&
                (targetDate.isBefore(endDate, "day") || targetDate.isSame(endDate, "day"))
            )
        })
    }

    // Prüfe, ob ein bestimmtes Datum in den eigenen Urlaubstagen enthalten ist
    const isDateInMyVacation = (date) => {
        return isDateInVacation(date, currentUserId.value)
    }

    // Prüfe, ob ein bestimmtes Datum in den Urlaubstagen von Teammitgliedern enthalten ist
    const isDateInTeamVacation = (date) => {
        return vacations.value.some((vacation) => {
            if (vacation.userId === currentUserId.value) return false

            const targetDate = dayjs(date)
            const startDate = dayjs(vacation.start)
            const endDate = dayjs(vacation.end)

            return (
                (targetDate.isAfter(startDate, "day") || targetDate.isSame(startDate, "day")) &&
                (targetDate.isBefore(endDate, "day") || targetDate.isSame(endDate, "day"))
            )
        })
    }

    return {
        vacations,
        loading,
        error,
        currentUserId,
        fetchVacations,
        filterVacationsByUserId,
        myVacations,
        teamVacations,
        isDateInVacation,
        isDateInMyVacation,
        isDateInTeamVacation,
    }
}
