// composables/useCalendarUtils.js
import dayjs from "dayjs"

export function useCalendarUtils() {
    // Konstanten
    const weekdays = ["Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag", "Sonntag"]
    const weekdaysShort = ["Mo", "Di", "Mi", "Do", "Fr", "Sa"]

    // Hilfsfunktion: Ersten Tag einer Woche finden
    const getFirstDayOfWeek = (date) => {
        const day = date.day()
        const diff = day === 0 ? 6 : day - 1
        return date.subtract(diff, "day")
    }

    // Hilfsfunktion: Letzten Tag einer Woche finden
    const getLastDayOfWeek = (date) => {
        return getFirstDayOfWeek(date).add(6, "day")
    }

    // Alle Wochen im aktuellen Monat generieren
    const getWeeksInMonth = (currentDate) => {
        const startOfMonth = currentDate.startOf("month")
        const endOfMonth = currentDate.endOf("month")

        let currentWeekStart = dayjs(startOfMonth).day(1)
        if (currentWeekStart.isAfter(startOfMonth)) {
            currentWeekStart = currentWeekStart.subtract(1, "week")
        }

        const weeks = []
        const targetDate = dayjs().year(currentDate.year()).month(currentDate.month())
        while (currentWeekStart.isBefore(endOfMonth) || currentWeekStart.isSame(endOfMonth, "day")) {
            const weekNumber = currentWeekStart.week()
            const days = []
            for (let i = 0; i < 7; i++) {
                const date = currentWeekStart.clone().add(i, "day")
                days.push({
                    date: date.toDate(),
                    dayNumber: date.format("D"),
                    currentMonth: date.isSame(targetDate, "month"),
                    isToday: date.isToday(),
                    isWeekend: date.day() === 0 || date.day() === 6,
                })
            }
            weeks.push({ weekNumber, days })
            currentWeekStart = currentWeekStart.add(1, "week")
        }
        return weeks
    }

    // Wochen für Mini-Kalender in Jahresansicht generieren
    const getWeeksInMonthForMini = (month, currentDate) => {
        // Prüfe, ob currentDate definiert ist
        if (!currentDate) {
            console.error('currentDate ist undefined in getWeeksInMonthForMini');
            return []; // Leeres Array zurückgeben, um Fehler zu vermeiden
        }

        const targetDate = dayjs().month(month).year(currentDate.year())
        const startOfMonth = targetDate.startOf("month")
        const endOfMonth = targetDate.endOf("month")

        let currentWeekStart = dayjs(startOfMonth).day(1)
        if (currentWeekStart.isAfter(startOfMonth)) {
            currentWeekStart = currentWeekStart.subtract(1, "week")
        }

        const weeks = []
        while (currentWeekStart.isBefore(endOfMonth) || currentWeekStart.isSame(endOfMonth, "day")) {
            const weekNumber = currentWeekStart.week()
            const days = []
            for (let i = 0; i < 7; i++) {
                const date = currentWeekStart.clone().add(i, "day")
                days.push({
                    date: date.toDate(),
                    dayNumber: date.format("D"),
                    currentMonth: date.isSame(targetDate, "month"),
                    isToday: date.isToday(),
                    isWeekend: date.day() === 0 || date.day() === 6,
                })
            }
            weeks.push({ weekNumber, days })
            currentWeekStart = currentWeekStart.add(1, "week")
        }
        return weeks
    }

    // Monatsname formatieren
    const getMonthName = (month) => {
        return dayjs().month(month).format("MMMM")
    }

    return {
        weekdays,
        weekdaysShort,
        getFirstDayOfWeek,
        getLastDayOfWeek,
        getWeeksInMonth,
        getWeeksInMonthForMini,
        getMonthName,
    }
}
