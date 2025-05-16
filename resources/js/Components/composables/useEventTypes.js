import { ref, computed } from "vue"
import axios from "axios"

export function useEventTypes(currentUserRoleId) {
  const eventTypes = ref([])

  // Gefilterte Event-Typen basierend auf Benutzerrolle
  const filteredEventTypes = computed(() => {
    if (!eventTypes.value) return []

    return eventTypes.value.filter((type) => {
      // Wenn der Event-Typ "Krankheit" oder "Abwesend" ist und der Benutzer nicht die HR-Rolle hat,
      // dann diesen Typ nicht anzeigen
      if ((type.name === "Krankheit" || type.name === "Abwesend") && currentUserRoleId.value !== 2) {
        return false
      }
      return true
    })
  })

  const fetchEventTypes = async () => {
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

      const response = await axios.get("/api/event-types", config)
      eventTypes.value = response.data.map((type) => ({
        id: type.id,
        name: type.name,
        value: type.name.toLowerCase(),
        color: type.color,
        requires_approval: type.requires_approval,
      }))

      // Prüfen, ob "Abwesend" Ereignistyp bereits existiert
      const hasAbsentType = eventTypes.value.some((type) => type.name === "Abwesend")
      if (!hasAbsentType) {
        // Füge "Abwesend" hinzu, wenn es nicht existiert
        eventTypes.value.push({
          id: 99, // Temporäre ID
          name: "Abwesend",
          value: "absent",
          color: "#795548",
          requires_approval: true,
        })
      }
    } catch (error) {
      console.error("Fehler beim Laden der Ereignistypen:", error)
      // Fallback-Werte
      eventTypes.value = [
        { id: 1, name: "Homeoffice", value: "homeoffice", color: "#4CAF50", requires_approval: true },
        { id: 2, name: "Büro", value: "office", color: "#2196F3", requires_approval: false },
        { id: 3, name: "Außendienst", value: "field", color: "#FF9800", requires_approval: true },
        { id: 4, name: "Krank", value: "sick", color: "#F44336", requires_approval: false },
        { id: 5, name: "Urlaub", value: "vacation", color: "#9C27B0", requires_approval: true },
        { id: 6, name: "Sonstiges", value: "other", color: "#607D8B", requires_approval: true },
        { id: 7, name: "Abwesend", value: "absent", color: "#795548", requires_approval: true },
      ]
    }
  }

  const findEventType = (typeInfo) => {
    if (!typeInfo) return null

    let eventType = null

    // Nach ID suchen
    if (typeInfo.id) {
      eventType = eventTypes.value.find((type) => type.id === typeInfo.id)
    }

    // Nach Name suchen
    if (!eventType && typeInfo.name) {
      eventType = eventTypes.value.find(
        (type) => type.name === typeInfo.name || type.name.toLowerCase() === typeInfo.name.toLowerCase(),
      )
    }

    // Nach Wert suchen
    if (!eventType && typeInfo.value) {
      eventType = eventTypes.value.find(
        (type) => type.value === typeInfo.value || type.value.toLowerCase() === typeInfo.value.toLowerCase(),
      )
    }

    // Fallback
    if (!eventType && eventTypes.value.length > 0) {
      eventType = eventTypes.value.find((type) => type.name === "Sonstiges") || eventTypes.value[0]
    }

    return eventType
  }

  return {
    eventTypes,
    filteredEventTypes,
    fetchEventTypes,
    findEventType,
  }
}
