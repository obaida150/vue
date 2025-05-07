import axios from "axios"
import dayjs from "dayjs"

// Cache für Feiertage nach Jahren
const holidayCache = {}

// Liste der zu ignorierenden Feiertage
const ignoredHolidays = ["Augsburger Friedensfest", "Buß- und Bettag"]

/**
 * Service für die Verwaltung von Feiertagen
 */
export default {
    /**
     * Lädt Feiertage für ein bestimmtes Jahr
     * @param {number} year - Das Jahr für das die Feiertage geladen werden sollen
     * @returns {Promise<Array>} - Ein Promise mit den Feiertagen
     */
    async getHolidays(year) {
        // Wenn die Feiertage bereits im Cache sind, verwende diese
        if (holidayCache[year]) {
            return holidayCache[year]
        }

        try {
            // Versuche zuerst, die Feiertage über einen CORS-Proxy zu laden
            const corsProxy = "https://corsproxy.io/?"
            const apiUrl = `${corsProxy}https://feiertage-api.de/api/?jahr=${year}&nur_land=BY`

            const response = await axios.get(apiUrl, {
                headers: {
                    // Entferne den x-requested-with Header, der CORS-Probleme verursacht
                    "X-Requested-With": null,
                },
            })

            // Umwandeln des API-Antwortformats in unser Format und ignoriere bestimmte Feiertage
            const holidaysData = []
            for (const [name, data] of Object.entries(response.data)) {
                // Ignoriere bestimmte Feiertage
                if (!ignoredHolidays.includes(name)) {
                    holidaysData.push({
                        date: dayjs(data.datum),
                        name: name,
                    })
                }
            }

            // Speichere im Cache
            holidayCache[year] = holidaysData

            console.log(`Feiertage für ${year} über API geladen:`, holidaysData)
            return holidaysData
        } catch (error) {
            console.warn("Fehler beim Laden der Feiertage über API, verwende lokale Berechnung:", error)

            // Fallback: Lokale Berechnung der Feiertage
            const calculatedHolidays = this.calculateBavarianHolidays(year)

            // Speichere im Cache
            holidayCache[year] = calculatedHolidays

            console.log(`Feiertage für ${year} lokal berechnet:`, calculatedHolidays)
            return calculatedHolidays
        }
    },

    /**
     * Berechnet bayerische Feiertage lokal
     * @param {number} year - Das Jahr für das die Feiertage berechnet werden sollen
     * @returns {Array} - Die berechneten Feiertage
     */
    calculateBavarianHolidays(year) {
        // Funktion zur Berechnung des Ostersonntags (Gaußsche Osterformel)
        const calculateEaster = (year) => {
            const a = year % 19
            const b = Math.floor(year / 100)
            const c = year % 100
            const d = Math.floor(b / 4)
            const e = b % 4
            const f = Math.floor((b + 8) / 25)
            const g = Math.floor((b - f + 1) / 3)
            const h = (19 * a + b - d - g + 15) % 30
            const i = Math.floor(c / 4)
            const k = c % 4
            const l = (32 + 2 * e + 2 * i - h - k) % 7
            const m = Math.floor((a + 11 * h + 22 * l) / 451)
            const month = Math.floor((h + l - 7 * m + 114) / 31)
            const day = ((h + l - 7 * m + 114) % 31) + 1

            return dayjs(`${year}-${month}-${day}`)
        }

        // Berechnung der beweglichen Feiertage basierend auf Ostern
        const easterSunday = calculateEaster(year)
        const goodFriday = easterSunday.subtract(2, "day")
        const easterMonday = easterSunday.add(1, "day")
        const ascensionDay = easterSunday.add(39, "day")
        const whitMonday = easterSunday.add(50, "day")
        const corpusChristi = easterSunday.add(60, "day")

        // Berechnung des Buß- und Bettags (Mittwoch vor dem letzten Sonntag im Kirchenjahr)
        // Der letzte Sonntag im Kirchenjahr ist der Sonntag vor dem 1. Advent
        // Der 1. Advent ist der Sonntag zwischen dem 27. November und dem 3. Dezember
        const firstAdvent = (() => {
            const dec4 = dayjs(`${year}-12-04`)
            const dayOfWeek = dec4.day() // 0 = Sonntag, 1 = Montag, ...
            return dec4.subtract(dayOfWeek, "day").subtract(7, "day")
        })()
        const lastSundayOfChurchYear = firstAdvent.subtract(7, "day")
        const repentanceDay = lastSundayOfChurchYear.subtract(4, "day") // Mittwoch vor dem letzten Sonntag

        // Feste Feiertage in Bayern
        const holidays = [
            { date: dayjs(`${year}-01-01`), name: "Neujahr" },
            { date: dayjs(`${year}-01-06`), name: "Heilige Drei Könige" },
            { date: dayjs(`${year}-05-01`), name: "Tag der Arbeit" },
            { date: dayjs(`${year}-08-15`), name: "Mariä Himmelfahrt" },
            { date: dayjs(`${year}-10-03`), name: "Tag der Deutschen Einheit" },
            { date: dayjs(`${year}-11-01`), name: "Allerheiligen" },
            { date: dayjs(`${year}-12-25`), name: "Erster Weihnachtstag" },
            { date: dayjs(`${year}-12-26`), name: "Zweiter Weihnachtstag" },
            { date: goodFriday, name: "Karfreitag" },
            { date: easterMonday, name: "Ostermontag" },
            { date: ascensionDay, name: "Christi Himmelfahrt" },
            { date: whitMonday, name: "Pfingstmontag" },
            { date: corpusChristi, name: "Fronleichnam" },
            // Augsburger Friedensfest (8. August) und Buß- und Bettag werden nicht hinzugefügt
        ]

        // Filtere die zu ignorierenden Feiertage heraus
        return holidays.filter((holiday) => !ignoredHolidays.includes(holiday.name))
    },

    /**
     * Prüft, ob ein Datum ein Feiertag ist
     * @param {dayjs.Dayjs} date - Das zu prüfende Datum
     * @param {Array} holidays - Die Liste der Feiertage
     * @returns {boolean} - true, wenn das Datum ein Feiertag ist
     */
    isHoliday(date, holidays) {
        return holidays.some((holiday) => holiday.date.format("YYYY-MM-DD") === date.format("YYYY-MM-DD"))
    },

    /**
     * Gibt den Namen eines Feiertags zurück
     * @param {dayjs.Dayjs} date - Das Datum des Feiertags
     * @param {Array} holidays - Die Liste der Feiertage
     * @returns {string} - Der Name des Feiertags oder ein leerer String
     */
    getHolidayName(date, holidays) {
        const holiday = holidays.find((holiday) => holiday.date.format("YYYY-MM-DD") === date.format("YYYY-MM-DD"))
        return holiday ? holiday.name : ""
    },
}
