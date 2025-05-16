import { ref, computed } from "vue"
import dayjs from "dayjs"

export function useCalendarState() {
  // Zustands-Variablen
  const currentDate = ref(dayjs())
  const calendarView = ref("month")
  const yearLayout = ref("6x2")
  const events = ref([])
  const vacations = ref([])
  const loading = ref(false)
  const showOnlyOwnEvents = ref(false)

  // Computed Properties
  const currentMonthName = computed(() => currentDate.value.format("MMMM"))
  const currentYear = computed(() => currentDate.value.year())

  // Navigation
  const previousPeriod = () => {
    if (calendarView.value === "month") {
      currentDate.value = currentDate.value.subtract(1, "month")
    } else {
      currentDate.value = currentDate.value.subtract(1, "year")
    }
  }

  const nextPeriod = () => {
    if (calendarView.value === "month") {
      currentDate.value = currentDate.value.add(1, "month")
    } else {
      currentDate.value = currentDate.value.add(1, "year")
    }
  }

  const setCalendarView = (view) => {
    calendarView.value = view
  }

  const toggleEventFilter = () => {
    showOnlyOwnEvents.value = !showOnlyOwnEvents.value
    // Speichern der Einstellung im localStorage
    localStorage.setItem("showOnlyOwnEvents", showOnlyOwnEvents.value ? "true" : "false")
  }

  const goToMonth = (month) => {
    currentDate.value = currentDate.value.month(month)
    calendarView.value = "month"
  }

  return {
    currentDate,
    calendarView,
    yearLayout,
    events,
    vacations,
    loading,
    showOnlyOwnEvents,
    currentMonthName,
    currentYear,
    previousPeriod,
    nextPeriod,
    setCalendarView,
    toggleEventFilter,
    goToMonth,
  }
}
