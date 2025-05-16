import { ref } from "vue"
import HolidayService from "@/Services/holiday-service"
import dayjs from "dayjs"

export function useHolidays() {
  const holidays = ref([])
  const isLoadingHolidays = ref(false)
  const disabledDates = ref([])
  const selectedHolidayName = ref("")
  const holidayInfoVisible = ref(false)

  const updateDisabledDates = () => {
    disabledDates.value = holidays.value.map((holiday) => holiday.date.toDate())
  }

  const fetchHolidays = async (year) => {
    isLoadingHolidays.value = true
    try {
      holidays.value = await HolidayService.getHolidays(year)
      updateDisabledDates()
    } catch (error) {
      console.error("Fehler beim Laden der Feiertage:", error)
    } finally {
      isLoadingHolidays.value = false
    }
  }

  const isHoliday = (date) => {
    return HolidayService.isHoliday(date, holidays.value)
  }

  const getHolidayName = (date) => {
    return HolidayService.getHolidayName(date, holidays.value)
  }

  const showHolidayInfo = (date) => {
    const holidayName = getHolidayName(dayjs(date))
    if (holidayName) {
      selectedHolidayName.value = holidayName
      holidayInfoVisible.value = true
    }
  }

  return {
    holidays,
    isLoadingHolidays,
    disabledDates,
    selectedHolidayName,
    holidayInfoVisible,
    updateDisabledDates,
    fetchHolidays,
    isHoliday,
    getHolidayName,
    showHolidayInfo,
  }
}
