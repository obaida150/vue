"use client"

import { ref } from "vue"
import dayjs from "dayjs"
import axios from "axios"
import { useToast } from "primevue/usetoast"

export function useDialogs(
  isHrUser,
  isTeamManager,
  currentUserId,
  hasVacations,
  isHoliday,
  getHolidayName,
  fetchEvents,
  userTeamId,
  employees,
  currentDate,
) {
  const toast = useToast()

  // Event Dialog
  const newEvent = ref({
    title: "",
    type: null,
    startDate: null,
    endDate: null,
    isAllDay: true,
    description: "",
    user_id: null,
  })
  const saving = ref(false)
  const isEditMode = ref(false)
  const eventDialogVisible = ref(false)

  // Event Details
  const selectedEvent = ref(null)
  const eventDetailsDialogVisible = ref(false)

  // Vacation Details
  const selectedVacation = ref(null)
  const vacationDetailsDialogVisible = ref(false)

  // Delete Confirmation
  const deleteConfirmationVisible = ref(false)
  const deleting = ref(false)

  // Week Plan
  const selectedWeekNumber = ref(null)
  const weekDays = ref([])
  const savingWeekPlan = ref(false)
  const weekPlanDialogVisible = ref(false)
  const weekPlanShowOnlyOwnEvents = ref(true)

  // Holiday Info
  const holidayInfoVisible = ref(false)
  const selectedHolidayName = ref("")

  // Hilfsfunktionen
  const truncateText = (text, maxLength) => {
    if (!text) return ""
    if (text.length > maxLength) {
      return text.substring(0, maxLength) + "..."
    }
    return text
  }

  const formatDate = (date) => {
    return dayjs(date).format("DD.MM.YYYY")
  }

  const getStatusLabel = (status) => {
    switch (status) {
      case "pending":
        return "Ausstehend"
      case "approved":
        return "Genehmigt"
      case "rejected":
        return "Abgelehnt"
      default:
        return "Unbekannt"
    }
  }

  const getStatusSeverity = (status) => {
    switch (status) {
      case "pending":
        return "warning"
      case "approved":
        return "success"
      case "rejected":
        return "danger"
      default:
        return "info"
    }
  }

  // Event Dialog Funktionen
  const openEventDialog = (date) => {
    if (isHoliday(dayjs(date))) {
      showHolidayInfo(date)
      return
    }

    if (hasVacations(date)) {
      toast.add({
        severity: "info",
        summary: "Hinweis",
        detail: "An diesem Tag ist bereits Urlaub eingetragen. Bitte wählen Sie einen anderen Tag.",
        life: 3000,
      })
      return
    }

    isEditMode.value = false
    newEvent.value = {
      title: "",
      type: null,
      startDate: date,
      endDate: date,
      isAllDay: true,
      description: "",
      user_id: currentUserId.value,
    }

    eventDialogVisible.value = true
  }

  const closeEventDialog = () => {
    eventDialogVisible.value = false
  }

  const saveEvent = async () => {
    if (!newEvent.value.title || !newEvent.value.type) {
      toast.add({
        severity: "error",
        summary: "Validierungsfehler",
        detail: "Bitte füllen Sie alle erforderlichen Felder aus.",
        life: 3000,
      })
      return
    }

    // Prüfen, ob ein Benutzer ausgewählt wurde (für HR bei Krankheit oder Abwesend)
    if (
      isHrUser.value &&
      newEvent.value.type &&
      (newEvent.value.type.name === "Krankheit" || newEvent.value.type.name === "Abwesend") &&
      !newEvent.value.user_id
    ) {
      toast.add({
        severity: "error",
        summary: "Validierungsfehler",
        detail: "Bitte wählen Sie einen Mitarbeiter aus.",
        life: 3000,
      })
      return
    }

    // Prüfen, ob Start- oder Enddatum ein Feiertag ist
    const startDate = dayjs(newEvent.value.startDate)
    const endDate = dayjs(newEvent.value.endDate)

    if (isHoliday(startDate) || isHoliday(endDate)) {
      toast.add({
        severity: "error",
        summary: "Validierungsfehler",
        detail: "Ereignisse können nicht an Feiertagen eingetragen werden.",
        life: 3000,
      })
      return
    }

    // Prüfen, ob ein Feiertag zwischen Start- und Enddatum liegt
    let date = startDate.clone()
    while (date.isBefore(endDate) || date.isSame(endDate, "day")) {
      if (isHoliday(date)) {
        toast.add({
          severity: "error",
          summary: "Validierungsfehler",
          detail: `Ereignisse können nicht an Feiertagen eingetragen werden. ${getHolidayName(date)} (${date.format("DD.MM.YYYY")}) liegt im gewählten Zeitraum.`,
          life: 5000,
        })
        return
      }
      date = date.add(1, "day")
    }

    // Prüfen, ob im gewählten Zeitraum bereits Urlaub eingetragen ist
    date = startDate.clone()
    while (date.isBefore(endDate) || date.isSame(endDate, "day")) {
      if (hasVacations(date.toDate())) {
        toast.add({
          severity: "error",
          summary: "Validierungsfehler",
          detail: `Ereignisse können nicht an Urlaubstagen eingetragen werden. Am ${date.format("DD.MM.YYYY")} ist bereits Urlaub eingetragen.`,
          life: 5000,
        })
        return
      }
      date = date.add(1, "day")
    }

    // Vereinfachte Berechtigungsprüfung für Bearbeitung
    if (isEditMode.value && newEvent.value.id) {
      let canEdit = false

      // HR-Benutzer dürfen alles bearbeiten
      if (isHrUser.value) {
        canEdit = true
      }
      // Eigene Ereignisse darf jeder bearbeiten
      else if (newEvent.value.user_id === currentUserId.value) {
        canEdit = true
      }
      // Abteilungsleiter dürfen Ereignisse ihrer Teammitglieder bearbeiten
      else if (isTeamManager.value) {
        // Für Abteilungsleiter erlauben wir die Bearbeitung, auch wenn team_id nicht übereinstimmt
        canEdit = true
      }

      if (!canEdit) {
        toast.add({
          severity: "error",
          summary: "Keine Berechtigung",
          detail: "Sie sind nicht berechtigt, dieses Ereignis zu bearbeiten.",
          life: 5000,
        })
        saving.value = false
        return
      }
    }

    saving.value = true
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

      const eventData = {
        title: newEvent.value.title,
        event_type_id: newEvent.value.type.id,
        event_type: newEvent.value.type.name,
        start_date: dayjs(newEvent.value.startDate).format("YYYY-MM-DD"),
        end_date: dayjs(newEvent.value.endDate).format("YYYY-MM-DD"),
        is_all_day: newEvent.value.isAllDay,
        description: newEvent.value.description,
        user_id: newEvent.value.user_id,
      }

      if (isEditMode.value && newEvent.value.id) {
        if (selectedEvent.value && selectedEvent.value.source === "vacation") {
          toast.add({
            severity: "info",
            summary: "Hinweis",
            detail: "Urlaubseinträge können nicht direkt bearbeitet werden. Bitte nutzen Sie die Urlaubsverwaltung.",
            life: 5000,
          })
          return
        }

        try {
          // Laravel-Standard für Updates
          const postData = { ...eventData, _method: "PUT" }
          const response = await axios.post(`/api/events/${newEvent.value.id}`, postData, config)

          if (response.status === 200) {
            toast.add({
              severity: "success",
              summary: "Erfolgreich",
              detail: "Ereignis wurde aktualisiert.",
              life: 3000,
            })
          }
        } catch (postError) {
          // Fallback: direkter PUT-Request
          const response = await axios.put(`/api/events/${newEvent.value.id}`, eventData, config)

          if (response.status === 200) {
            toast.add({
              severity: "success",
              summary: "Erfolgreich",
              detail: "Ereignis wurde aktualisiert.",
              life: 3000,
            })
          }
        }
      } else {
        const response = await axios.post("/api/events", eventData, config)

        if (response.status === 201) {
          toast.add({
            severity: "success",
            summary: "Erfolgreich",
            detail: "Ereignis wurde gespeichert.",
            life: 3000,
          })
        }
      }

      setTimeout(() => {
        fetchEvents()
      }, 1000)
    } catch (error) {
      console.error("Fehler beim Speichern des Ereignisses:", error)

      if (error.response && error.response.status === 401) {
        toast.add({
          severity: "error",
          summary: "Fehler",
          detail: "Sie sind nicht berechtigt, dieses Ereignis zu speichern. Bitte melden Sie sich erneut an.",
          life: 5000,
        })
      } else {
        toast.add({
          severity: "error",
          summary: "Fehler",
          detail:
            "Es gab ein Problem beim Speichern des Ereignisses: " + (error.response?.data?.error || error.message),
          life: 3000,
        })
      }
    } finally {
      saving.value = false
      closeEventDialog()
      closeEventDetailsDialog()
    }
  }

  // Event Details Dialog Funktionen
  const openEventDetailsDialog = (event) => {
    selectedEvent.value = event
    eventDetailsDialogVisible.value = true
  }

  const closeEventDetailsDialog = () => {
    eventDetailsDialogVisible.value = false
  }

  const editEvent = (findEventType) => {
    if (!selectedEvent.value) return

    if (selectedEvent.value.source === "vacation") {
      toast.add({
        severity: "info",
        summary: "Hinweis",
        detail: "Urlaubseinträge können nicht direkt bearbeitet werden. Bitte nutzen Sie die Urlaubsverwaltung.",
        life: 5000,
      })
      return
    }

    // Vereinfachte Berechtigungsprüfung
    let canEdit = false

    // HR-Benutzer dürfen alles bearbeiten
    if (isHrUser.value) {
      canEdit = true
    }
    // Eigene Ereignisse darf jeder bearbeiten
    else if (selectedEvent.value.user_id === currentUserId.value) {
      canEdit = true
    }
    // Abteilungsleiter dürfen Ereignisse ihrer Teammitglieder bearbeiten
    else if (isTeamManager.value) {
      // Für Abteilungsleiter erlauben wir die Bearbeitung, auch wenn team_id nicht übereinstimmt
      canEdit = true
    }

    if (!canEdit) {
      toast.add({
        severity: "error",
        summary: "Keine Berechtigung",
        detail: "Sie sind nicht berechtigt, dieses Ereignis zu bearbeiten.",
        life: 5000,
      })
      return
    }

    // Prüfen, ob es sich um einen Krankheitseintrag oder Abwesenheitseintrag handelt und der Benutzer kein HR-Mitarbeiter ist
    if (
      selectedEvent.value.type &&
      (selectedEvent.value.type.name === "Krankheit" || selectedEvent.value.type.name === "Abwesend") &&
      !isHrUser.value
    ) {
      toast.add({
        severity: "info",
        summary: "Hinweis",
        detail: "Krankheits- und Abwesenheitseinträge können nur von HR-Mitarbeitern bearbeitet werden.",
        life: 5000,
      })
      return
    }

    const eventType = findEventType(selectedEvent.value.type)

    isEditMode.value = true
    newEvent.value = {
      id: selectedEvent.value.id,
      title: selectedEvent.value.title,
      type: eventType,
      startDate: new Date(selectedEvent.value.startDate),
      endDate: new Date(selectedEvent.value.endDate),
      isAllDay: selectedEvent.value.isAllDay,
      description: selectedEvent.value.description,
      user_id: selectedEvent.value.user_id || currentUserId.value,
      team_id: selectedEvent.value.team_id,
    }

    closeEventDetailsDialog()
    eventDialogVisible.value = true
  }

  // Vacation Details Dialog Funktionen
  const openVacationDetailsDialog = (vacation) => {
    selectedVacation.value = vacation
    vacationDetailsDialogVisible.value = true
  }

  const closeVacationDetailsDialog = () => {
    vacationDetailsDialogVisible.value = false
  }

  const navigateToVacationManagement = () => {
    if (typeof window !== "undefined") {
      window.location.href = "/vacation/management"
    }
  }

  // Delete Confirmation Dialog Funktionen
  const confirmDeleteEvent = (event) => {
    if (!event) return

    // Prüfen, ob es sich um einen Krankheitseintrag oder Abwesenheitseintrag handelt und der Benutzer kein HR-Mitarbeiter ist
    if (event.type && (event.type.name === "Krankheit" || event.type.name === "Abwesend") && !isHrUser.value) {
      toast.add({
        severity: "info",
        summary: "Hinweis",
        detail: "Krankheits- und Abwesenheitseinträge können nur von HR-Mitarbeitern gelöscht werden.",
        life: 5000,
      })
      return
    }

    // Vereinfachte Berechtigungsprüfung
    let canDelete = false

    // HR-Benutzer dürfen alles löschen
    if (isHrUser.value) {
      canDelete = true
    }
    // Eigene Ereignisse darf jeder löschen
    else if (event.user_id === currentUserId.value) {
      canDelete = true
    }
    // Abteilungsleiter dürfen Ereignisse ihrer Teammitglieder löschen
    else if (isTeamManager.value) {
      // Für Abteilungsleiter erlauben wir das Löschen, auch wenn team_id nicht übereinstimmt
      canDelete = true
    }

    if (!canDelete) {
      toast.add({
        severity: "error",
        summary: "Keine Berechtigung",
        detail: "Sie sind nicht berechtigt, dieses Ereignis zu löschen.",
        life: 5000,
      })
      return
    }

    selectedEvent.value = event
    deleteConfirmationVisible.value = true
  }

  const cancelDeleteEvent = () => {
    deleteConfirmationVisible.value = false
  }

  const deleteEvent = async () => {
    if (!selectedEvent.value) return

    deleting.value = true

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

      try {
        // Versuche zuerst mit DELETE-Methode
        const response = await axios.delete(`/api/events/${selectedEvent.value.id}`, config)

        if (response.status === 200) {
          toast.add({
            severity: "success",
            summary: "Erfolgreich",
            detail: "Ereignis wurde gelöscht.",
            life: 3000,
          })
        }
      } catch (deleteError) {
        // Fallback: POST mit _method=DELETE
        const response = await axios.post(`/api/events/${selectedEvent.value.id}`, { _method: "DELETE" }, config)

        if (response.status === 200) {
          toast.add({
            severity: "success",
            summary: "Erfolgreich",
            detail: "Ereignis wurde gelöscht.",
            life: 3000,
          })
        }
      }

      setTimeout(() => {
        fetchEvents()
      }, 1000)
    } catch (error) {
      console.error("Fehler beim Löschen des Ereignisses:", error)

      toast.add({
        severity: "error",
        summary: "Fehler",
        detail: "Das Ereignis konnte nicht gelöscht werden: " + (error.response?.data?.error || error.message),
        life: 3000,
      })
    } finally {
      deleting.value = false
      deleteConfirmationVisible.value = false
    }
  }

  // Week Plan Dialog Funktionen
  const openWeekPlanDialog = (weekNumber, days, getEventsForDay) => {
    selectedWeekNumber.value = weekNumber

    // Tage für die Wochenplanung vorbereiten
    weekDays.value = days.map((day) => {
      const existingEvents = getEventsForDay(day.date)

      // Filter Ereignisse basierend auf weekPlanShowOnlyOwnEvents
      let existingEvent = null

      if (weekPlanShowOnlyOwnEvents.value) {
        // Nur eigene Ereignisse anzeigen
        existingEvent = existingEvents.find((event) => event.user_id === currentUserId.value)
      } else {
        // Alle Ereignisse anzeigen, die der Benutzer bearbeiten darf
        existingEvent = existingEvents.find(
          (event) =>
            event.user_id === currentUserId.value ||
            isHrUser.value ||
            (isTeamManager.value && event.team_id === userTeamId.value),
        )
      }

      return {
        date: day.date,
        selectedType: existingEvent ? existingEvent.type : null,
        notes: existingEvent ? existingEvent.description : "",
        eventId: existingEvent ? existingEvent.id : null,
        isEdited: false,
        toDelete: false,
        user_id: existingEvent ? existingEvent.user_id : currentUserId.value,
        selectedEmployee:
          existingEvent && existingEvent.user_id !== currentUserId.value
            ? employees.value.find((emp) => emp.id === existingEvent.user_id)
            : null,
      }
    })

    weekPlanDialogVisible.value = true
  }

  const closeWeekPlanDialog = () => {
    weekPlanDialogVisible.value = false
  }

  const toggleWeekPlanFilter = (getEventsForDay) => {
    weekPlanShowOnlyOwnEvents.value = !weekPlanShowOnlyOwnEvents.value

    // Aktualisiere die Tage basierend auf dem neuen Filter
    if (selectedWeekNumber.value && weekDays.value.length > 0) {
      const days = weekDays.value.map((day) => ({
        date: day.date,
        dayNumber: dayjs(day.date).date(),
        currentMonth: dayjs(day.date).month() === currentDate.value.month(),
        isToday: dayjs(day.date).isToday(),
        isWeekend: dayjs(day.date).day() === 0 || dayjs(day.date).day() === 6,
      }))

      // Rufe openWeekPlanDialog erneut auf, um die Tage neu zu laden
      openWeekPlanDialog(selectedWeekNumber.value, days, getEventsForDay)
    }
  }

  const removeWeekDayEvent = (index) => {
    if (index >= 0 && index < weekDays.value.length) {
      const day = weekDays.value[index]

      if (day.eventId) {
        day.toDelete = true
        day.selectedType = null
        day.notes = ""
      } else {
        day.selectedType = null
        day.notes = ""
      }
    }
  }

  const saveWeekPlan = async () => {
    savingWeekPlan.value = true
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

      const toCreate = []
      const toUpdate = []
      const toDelete = []

      weekDays.value.forEach((day) => {
        if (isHoliday(dayjs(day.date)) || hasVacations(day.date)) {
          return
        }

        // Prüfen, ob für Krankheit oder Abwesend ein Benutzer ausgewählt wurde
        if (
          day.selectedType &&
          (day.selectedType.name === "Krankheit" || day.selectedType.name === "Abwesend") &&
          !day.selectedEmployee &&
          isHrUser.value
        ) {
          toast.add({
            severity: "error",
            summary: "Validierungsfehler",
            detail: `Bitte wählen Sie einen Mitarbeiter für den ${day.selectedType.name}-Eintrag am ${dayjs(day.date).format("DD.MM.YYYY")} aus.`,
            life: 5000,
          })
          return
        }

        if (day.eventId && day.toDelete) {
          toDelete.push(day.eventId)
        } else if (day.eventId && day.isEdited && day.selectedType) {
          toUpdate.push({
            id: day.eventId,
            title: day.selectedType.name,
            description: day.notes || "",
            start_date: dayjs(day.date).format("YYYY-MM-DD"),
            end_date: dayjs(day.date).format("YYYY-MM-DD"),
            event_type_id: day.selectedType.id,
            is_all_day: true,
            user_id: day.user_id || (day.selectedEmployee ? day.selectedEmployee.id : currentUserId.value),
          })
        } else if (!day.eventId && day.selectedType) {
          toCreate.push({
            title: day.selectedType.name,
            description: day.notes || "",
            start_date: dayjs(day.date).format("YYYY-MM-DD"),
            end_date: dayjs(day.date).format("YYYY-MM-DD"),
            event_type_id: day.selectedType.id,
            is_all_day: true,
            user_id: day.user_id || (day.selectedEmployee ? day.selectedEmployee.id : currentUserId.value),
          })
        }
      })

      let successCount = 0
      const totalOperations = toCreate.length + toUpdate.length + toDelete.length

      // Löschoperationen
      for (const eventId of toDelete) {
        try {
          try {
            const response = await axios.delete(`/api/events/${eventId}`, config)
            if (response.status === 200) successCount++
          } catch (deleteError) {
            const response = await axios.post(`/api/events/${eventId}`, { _method: "DELETE" }, config)
            if (response.status === 200) successCount++
          }
        } catch (error) {
          console.error(`Fehler beim Löschen des Ereignisses ${eventId}:`, error)
        }
      }

      // Aktualisierungsoperationen
      for (const event of toUpdate) {
        try {
          try {
            const postData = { ...event, _method: "PUT" }
            const response = await axios.post(`/api/events/${event.id}`, postData, config)
            if (response.status === 200) successCount++
          } catch (postError) {
            const response = await axios.put(`/api/events/${event.id}`, event, config)
            if (response.status === 200) successCount++
          }
        } catch (error) {
          console.error(`Fehler beim Aktualisieren des Ereignisses ${event.id}:`, error)
        }
      }

      // Erstellungsoperationen
      for (const event of toCreate) {
        try {
          const response = await axios.post("/api/events", event, config)
          if (response.status === 201) successCount++
        } catch (error) {
          console.error("Fehler beim Erstellen eines Ereignisses:", error)
        }
      }

      if (successCount > 0) {
        toast.add({
          severity: "success",
          summary: successCount === totalOperations ? "Erfolg" : "Teilweise erfolgreich",
          detail:
            successCount === totalOperations
              ? "Wochenplanung wurde gespeichert."
              : `${successCount} von ${totalOperations} Änderungen wurden gespeichert.`,
          life: 3000,
        })
        setTimeout(() => fetchEvents(), 1000)
      } else if (totalOperations === 0) {
        toast.add({
          severity: "info",
          summary: "Hinweis",
          detail: "Keine Änderungen vorgenommen.",
          life: 3000,
        })
      } else {
        toast.add({
          severity: "error",
          summary: "Fehler",
          detail: "Die Wochenplanung konnte nicht gespeichert werden.",
          life: 3000,
        })
      }
    } catch (error) {
      console.error("Fehler beim Speichern der Wochenplanung:", error)
      toast.add({
        severity: "error",
        summary: "Fehler",
        detail: "Die Wochenplanung konnte nicht gespeichert werden.",
        life: 3000,
      })
    } finally {
      savingWeekPlan.value = false
      closeWeekPlanDialog()
    }
  }

  // Holiday Info Dialog Funktionen
  const showHolidayInfo = (date) => {
    const holidayName = getHolidayName(dayjs(date))
    if (holidayName) {
      selectedHolidayName.value = holidayName
      holidayInfoVisible.value = true
    }
  }

  return {
    // Event Dialog
    newEvent,
    saving,
    isEditMode,
    eventDialogVisible,
    openEventDialog,
    closeEventDialog,
    saveEvent,

    // Event Details
    selectedEvent,
    eventDetailsDialogVisible,
    openEventDetailsDialog,
    closeEventDetailsDialog,
    editEvent,

    // Vacation Details
    selectedVacation,
    vacationDetailsDialogVisible,
    openVacationDetailsDialog,
    closeVacationDetailsDialog,
    navigateToVacationManagement,

    // Delete Confirmation
    deleteConfirmationVisible,
    deleting,
    confirmDeleteEvent,
    cancelDeleteEvent,
    deleteEvent,

    // Week Plan
    selectedWeekNumber,
    weekDays,
    savingWeekPlan,
    weekPlanDialogVisible,
    weekPlanShowOnlyOwnEvents,
    openWeekPlanDialog,
    closeWeekPlanDialog,
    toggleWeekPlanFilter,
    removeWeekDayEvent,
    saveWeekPlan,

    // Holiday Info
    holidayInfoVisible,
    selectedHolidayName,
    showHolidayInfo,

    // Hilfsfunktionen
    truncateText,
    formatDate,
    getStatusLabel,
    getStatusSeverity,
  }
}
