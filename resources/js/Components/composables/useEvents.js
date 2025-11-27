"use client"

import { ref } from "vue"
import dayjs from "dayjs"
import axios from "axios"
import { useToast } from "primevue/usetoast"

export function useEvents(
    calendarView,
    currentDate,
    eventTypes,
    currentUserId,
    isHrUser,
    isTeamManager,
    userTeamId,
    showOnlyOwnEvents,
    findEventType,
) {
    const events = ref([])
    const vacations = ref([])
    const loading = ref(false)
    const toast = useToast()

    const fetchEvents = async () => {
        loading.value = true

        try {
            // Datumsbereich basierend auf der aktuellen Ansicht berechnen
            let startDate, endDate

            if (calendarView.value === "month") {
                const firstDay = currentDate.value.startOf("month").subtract(7, "day")
                const lastDay = currentDate.value.endOf("month").add(7, "day")
                startDate = firstDay.format("YYYY-MM-DD")
                endDate = lastDay.format("YYYY-MM-DD")
            } else {
                startDate = currentDate.value.startOf("year").format("YYYY-MM-DD")
                endDate = currentDate.value.endOf("year").format("YYYY-MM-DD")
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content")
            const config = {
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
                withCredentials: true,
                params: { start_date: startDate, end_date: endDate },
            }

            let allEvents = []
            try {
                const eventsResponse = await axios.get("/api/events", config)

                allEvents = eventsResponse.data.map((event) => {
                    const eventTypeName = event.event_type || event.title
                    let eventType = null

                    if (event.event_type_id) {
                        eventType = eventTypes.value.find((type) => type.id === event.event_type_id)
                    }

                    if (!eventType && typeof eventTypeName === "string") {
                        eventType = eventTypes.value.find(
                            (type) => type.name === eventTypeName || type.name.toLowerCase() === eventTypeName.toLowerCase(),
                        )
                    }

                    if (!eventType) {
                        eventType = eventTypes.value.find((type) => type.name === "Sonstiges") || {
                            id: 6,
                            name: "Sonstiges",
                            value: "other",
                            color: "#607D8B",
                            requires_approval: true,
                        }
                    }

                    const isVacation =
                        (eventType && eventType.name.toLowerCase() === "urlaub") ||
                        (eventTypeName && typeof eventTypeName === "string" && eventTypeName.toLowerCase().includes("urlaub")) ||
                        (event.title && typeof event.title === "string" && event.title.toLowerCase().includes("urlaub")) ||
                        (event.event_type &&
                            typeof event.event_type === "string" &&
                            event.event_type.toLowerCase().includes("urlaub"))

                    return {
                        id: event.id,
                        title: event.title,
                        description: event.description,
                        startDate: new Date(event.start_date),
                        endDate: new Date(event.end_date),
                        isAllDay: event.is_all_day,
                        status: event.status,
                        type: eventType,
                        color: eventType.color,
                        source: isVacation ? "vacation" : "event",
                        event_type_id: event.event_type_id,
                        user_id: event.user_id,
                        team_id: event.team_id,
                        employee_name: event.employee_name || "",
                        sync_with_outlook: event.sync_with_outlook,
                        outlook_event_id: event.outlook_event_id,
                        outlook_change_key: event.outlook_change_key,
                        start_time: event.start_time,
                        end_time: event.end_time,
                        is_all_day: event.is_all_day, // Zusätzlich mit Unterstrich für Konsistenz
                    }
                })
            } catch (error) {
                console.error("Fehler beim Laden der Ereignisse:", error)
            }

            events.value = allEvents.filter((event) => event.source !== "vacation")

            const vacationEvents = allEvents.filter((event) => event.source === "vacation")
            vacations.value = [
                ...vacations.value.filter((v) => !vacationEvents.some((ve) => ve.id === v.id)),
                ...vacationEvents,
            ]
        } catch (error) {
            console.error("Fehler beim Laden der Ereignisse:", error)
            toast.add({
                severity: "error",
                summary: "Fehler",
                detail: "Die Ereignisse konnten nicht vollständig geladen werden.",
                life: 3000,
            })
        } finally {
            loading.value = false
        }
    }

    const hasEvents = (date) => {
        if (!date) return false
        const dateStr = dayjs(date).format("YYYY-MM-DD")

        // In der Jahresansicht oder wenn showOnlyOwnEvents aktiviert ist, nur eigene Ereignisse berücksichtigen
        if (calendarView.value === "year" || showOnlyOwnEvents.value) {
            return events.value.some((event) => {
                const eventStartDate = dayjs(event.startDate).format("YYYY-MM-DD")
                const eventEndDate = dayjs(event.endDate).format("YYYY-MM-DD")
                return dateStr >= eventStartDate && dateStr <= eventEndDate && event.user_id === currentUserId.value
            })
        }

        // Wenn der Benutzer HR ist, alle Ereignisse berücksichtigen
        if (isHrUser.value) {
            return events.value.some((event) => {
                const eventStartDate = dayjs(event.startDate).format("YYYY-MM-DD")
                const eventEndDate = dayjs(event.endDate).format("YYYY-MM-DD")
                return dateStr >= eventStartDate && dateStr <= eventEndDate
            })
        }

        // Wenn der Benutzer Abteilungsleiter ist, nur Ereignisse des Teams berücksichtigen
        if (isTeamManager.value) {
            return events.value.some((event) => {
                const eventStartDate = dayjs(event.startDate).format("YYYY-MM-DD")
                const eventEndDate = dayjs(event.endDate).format("YYYY-MM-DD")
                return (
                    dateStr >= eventStartDate &&
                    dateStr <= eventEndDate &&
                    (event.user_id === currentUserId.value || event.team_id === userTeamId.value)
                )
            })
        }

        // Für normale Benutzer nur eigene Ereignisse berücksichtigen
        return events.value.some((event) => {
            const eventStartDate = dayjs(event.startDate).format("YYYY-MM-DD")
            const eventEndDate = dayjs(event.endDate).format("YYYY-MM-DD")
            return dateStr >= eventStartDate && dateStr <= eventEndDate && event.user_id === currentUserId.value
        })
    }

    const getEventsForDay = (date) => {
        if (!date) return []
        const dateStr = dayjs(date).format("YYYY-MM-DD")

        // Wenn showOnlyOwnEvents aktiviert ist, nur eigene Ereignisse zurückgeben
        if (showOnlyOwnEvents.value) {
            return events.value.filter((event) => {
                const eventStartDate = dayjs(event.startDate).format("YYYY-MM-DD")
                const eventEndDate = dayjs(event.endDate).format("YYYY-MM-DD")
                return dateStr >= eventStartDate && dateStr <= eventEndDate && event.user_id === currentUserId.value
            })
        }

        // Wenn der Benutzer HR ist und showOnlyOwnEvents deaktiviert ist, alle Ereignisse zurückgeben
        if (isHrUser.value) {
            return events.value.filter((event) => {
                const eventStartDate = dayjs(event.startDate).format("YYYY-MM-DD")
                const eventEndDate = dayjs(event.endDate).format("YYYY-MM-DD")
                return dateStr >= eventStartDate && dateStr <= eventEndDate
            })
        }

        // Wenn der Benutzer Abteilungsleiter ist und showOnlyOwnEvents deaktiviert ist,
        // nur Ereignisse des Teams zurückgeben
        if (isTeamManager.value) {
            return events.value.filter((event) => {
                const eventStartDate = dayjs(event.startDate).format("YYYY-MM-DD")
                const eventEndDate = dayjs(event.endDate).format("YYYY-MM-DD")
                return (
                    dateStr >= eventStartDate &&
                    dateStr <= eventEndDate &&
                    (event.user_id === currentUserId.value || event.team_id === userTeamId.value)
                )
            })
        }

        // Für normale Benutzer nur eigene Ereignisse zurückgeben
        return events.value.filter((event) => {
            const eventStartDate = dayjs(event.startDate).format("YYYY-MM-DD")
            const eventEndDate = dayjs(event.endDate).format("YYYY-MM-DD")
            return dateStr >= eventStartDate && dateStr <= eventEndDate && event.user_id === currentUserId.value
        })
    }

    const getEventColorForDay = (date) => {
        if (!date) return null
        const dateStr = dayjs(date).format("YYYY-MM-DD")

        // In der Jahresansicht oder wenn showOnlyOwnEvents aktiviert ist, nur eigene Ereignisse berücksichtigen
        if (calendarView.value === "year" || showOnlyOwnEvents.value) {
            const event = events.value.find((event) => {
                const eventStartDate = dayjs(event.startDate).format("YYYY-MM-DD")
                const eventEndDate = dayjs(event.endDate).format("YYYY-MM-DD")
                return dateStr >= eventStartDate && dateStr <= eventEndDate && event.user_id === currentUserId.value
            })
            return event ? event.color : null
        }

        // Wenn der Benutzer HR ist, alle Ereignisse berücksichtigen
        if (isHrUser.value) {
            const event = events.value.find((event) => {
                const eventStartDate = dayjs(event.startDate).format("YYYY-MM-DD")
                const eventEndDate = dayjs(event.endDate).format("YYYY-MM-DD")
                return dateStr >= eventStartDate && dateStr <= eventEndDate
            })
            return event ? event.color : null
        }

        // Wenn der Benutzer Abteilungsleiter ist, nur Ereignisse des Teams berücksichtigen
        if (isTeamManager.value) {
            const event = events.value.find((event) => {
                const eventStartDate = dayjs(event.startDate).format("YYYY-MM-DD")
                const eventEndDate = dayjs(event.endDate).format("YYYY-MM-DD")
                return (
                    dateStr >= eventStartDate &&
                    dateStr <= eventEndDate &&
                    (event.user_id === currentUserId.value || event.team_id === userTeamId.value)
                )
            })
            return event ? event.color : null
        }

        // Für normale Benutzer nur eigene Ereignisse berücksichtigen
        const event = events.value.find((event) => {
            const eventStartDate = dayjs(event.startDate).format("YYYY-MM-DD")
            const eventEndDate = dayjs(event.endDate).format("YYYY-MM-DD")
            return dateStr >= eventStartDate && dateStr <= eventEndDate && event.user_id === currentUserId.value
        })
        return event ? event.color : null
    }

    const hasVacations = (date) => {
        if (!date) return false
        const dateStr = dayjs(date).format("YYYY-MM-DD")

        return vacations.value.some((vacation) => {
            const vacationStartDate = dayjs(vacation.startDate).format("YYYY-MM-DD")
            const vacationEndDate = dayjs(vacation.endDate).format("YYYY-MM-DD")
            return dateStr >= vacationStartDate && dateStr <= vacationEndDate
        })
    }

    const getVacationsForDay = (date) => {
        if (!date) return []
        const dateStr = dayjs(date).format("YYYY-MM-DD")
        return vacations.value.filter((vacation) => {
            const vacationStartDate = dayjs(vacation.startDate).format("YYYY-MM-DD")
            const vacationEndDate = dayjs(vacation.endDate).format("YYYY-MM-DD")
            return dateStr >= vacationStartDate && dateStr <= vacationEndDate
        })
    }

    return {
        events,
        vacations,
        loading,
        fetchEvents,
        hasEvents,
        getEventsForDay,
        getEventColorForDay,
        hasVacations,
        getVacationsForDay,
    }
}
