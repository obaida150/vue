import fs from "fs"
import path from "path"

// Pfad zur alten SQL-Dump-Datei
// Stellen Sie sicher, dass diese Datei im selben Verzeichnis wie das Skript liegt oder passen Sie den Pfad an.
const OLD_SQL_DUMP_FILE = "scripts/Dump20250801%20Users_Alt-eXrZGwGuXL1e7Nrihh7U8960FAc4pY.sql"
const NEW_SQL_OUTPUT_FILE = "insert-users-new-schema.sql"

async function migrateUsers() {
    try {
        const oldSqlContent = fs.readFileSync(path.resolve(OLD_SQL_DUMP_FILE), "utf8")

        // KORRIGIERTE Regex: Passt jetzt genau auf "VALUES (" und das abschließende ");"
        // Der Fehler lag in den doppelten Dollarzeichen ($$) in der vorherigen Regex.
        const insertRegex = /INSERT INTO `users` VALUES $$([\s\S]+?)$$;/g
        let match
        const newInserts = []
        let maxId = 0

        // Hilfsfunktion zum Bereinigen und Parsen einzelner Werte
        const cleanValue = (val) => {
            if (val === null || val === undefined) return null
            val = val.trim()
            if (val.toUpperCase() === "NULL") return null
            // Entferne führende/nachfolgende Anführungszeichen, falls vorhanden
            if (val.startsWith("'") && val.endsWith("'")) return val.substring(1, val.length - 1)
            return val
        }

        // Hilfsfunktion zum Formatieren von Werten für PostgreSQL INSERT
        const formatSqlValue = (value) => {
            if (value === null) return "NULL"
            if (typeof value === "string") {
                // Escape einfache Anführungszeichen innerhalb des Strings
                const escapedValue = value.replace(/'/g, "''")
                return `'${escapedValue}'`
            }
            // Für Booleans (TRUE/FALSE) oder Zahlen, unverändert zurückgeben
            return value
        }

        while ((match = insertRegex.exec(oldSqlContent)) !== null) {
            const allValuesBlock = match[1] // Dies erfasst z.B. "1,'Dittmeier',...),(2,'Guth',..."
            console.log("Gefundener Werteblock (Anfang):", allValuesBlock.substring(0, 200) + "...") // Debugging

            // KORRIGIERTE Logik zum Teilen in einzelne Zeilen-Strings
            // Jedes Element nach dem Split muss wieder in Klammern gesetzt werden
            const rawRowStrings = allValuesBlock.split("),(").map((s) => `(${s})`)

            console.log("Anzahl der gefundenen Roh-Zeilen:", rawRowStrings.length) // Debugging

            for (const rawRowStr of rawRowStrings) {
                // Regex, um einzelne Werte aus einem einzelnen Zeilen-String zu extrahieren, unter Berücksichtigung von Anführungszeichen
                // Es passt:
                // 1. Einen einzelnen in Anführungszeichen gesetzten String: `'[^']*'`
                // 2. Den Literal-String 'NULL'
                // 3. Eine Zahl (Ganzzahl oder Gleitkommazahl): `\d+(?:\.\d+)?`
                // 4. Oder alles andere, was kein Komma oder Klammer ist (für unquoted strings/numbers ohne Anführungszeichen)
                const valueParts = rawRowStr.match(/'[^']*'|NULL|\d+(?:\.\d+)?|[^,()]+/g)

                if (!valueParts) {
                    console.warn("Konnte keine Werte aus Zeile extrahieren:", rawRowStr)
                    continue
                }

                // Bereinigen und Zuordnen der extrahierten Werte
                const cleanedValues = valueParts.map((val) => cleanValue(val))

                // Stellen Sie sicher, dass wir genau 24 Werte für das alte Schema haben
                if (cleanedValues.length !== 24) {
                    console.warn(
                        `Überspringe Zeile mit falscher Spaltenanzahl (erwartet 24, erhalten ${cleanedValues.length}):`,
                        rawRowStr,
                    )
                    continue
                }

                const oldUser = {
                    id: Number.parseInt(cleanedValues[0]),
                    name_old: cleanedValues[1], // Dies ist der Nachname
                    email: cleanedValues[2],
                    email_verified_at: cleanedValues[3],
                    password: cleanedValues[4],
                    two_factor_secret: cleanedValues[5],
                    two_factor_recovery_codes: cleanedValues[6],
                    remember_token: cleanedValues[7],
                    current_team_id: cleanedValues[8],
                    profile_photo_path: cleanedValues[9],
                    created_at: cleanedValues[10],
                    updated_at: cleanedValues[11],
                    vorname: cleanedValues[12], // Dies ist der Vorname
                    kuerzel: cleanedValues[13],
                    ist_aktiv: cleanedValues[14],
                    ist_personal: cleanedValues[15],
                    ist_admin: cleanedValues[16],
                    login_date: cleanedValues[17], // Wird ignoriert
                    urlaub_anzahl: cleanedValues[18],
                    geburtsdatum: cleanedValues[19],
                    diensteintritt: cleanedValues[20],
                    color: cleanedValues[21], // Wird ignoriert
                    pers_nr: cleanedValues[22],
                    is_ausbilder: cleanedValues[23],
                }

                // Aktualisiere maxId für die Sequenzeinstellung
                if (oldUser.id > maxId) {
                    maxId = oldUser.id
                }

                // Transformationslogik
                const newFirstName = oldUser.vorname
                const newLastName = oldUser.name_old
                const newFullName = `${newFirstName} ${newLastName}`.trim()

                let newRoleId = 4 // Standardrolle
                if (oldUser.ist_admin === "1") {
                    newRoleId = 1 // Admin-Rolle
                }

                const newIsActive = oldUser.ist_aktiv === "1" ? "TRUE" : "FALSE"
                const newIsAusbilder = oldUser.is_ausbilder === "1" ? "TRUE" : "FALSE"
                const newIsApprentice = "FALSE" // Standardmäßig false

                const newInsertRow = `(${formatSqlValue(oldUser.id)}, ${formatSqlValue(
                    newFirstName,
                )}, ${formatSqlValue(newLastName)}, ${formatSqlValue(newFullName)}, ${formatSqlValue(
                    oldUser.email,
                )}, ${formatSqlValue(oldUser.email_verified_at)}, ${formatSqlValue(
                    oldUser.geburtsdatum,
                )}, ${formatSqlValue(newRoleId)}, ${formatSqlValue(
                    oldUser.urlaub_anzahl,
                )}, ${formatSqlValue(oldUser.kuerzel)}, ${formatSqlValue(
                    oldUser.diensteintritt,
                )}, ${formatSqlValue(oldUser.pers_nr)}, ${newIsActive}, ${formatSqlValue(
                    oldUser.password,
                )}, ${formatSqlValue(oldUser.two_factor_secret)}, ${formatSqlValue(
                    oldUser.two_factor_recovery_codes,
                )}, NULL, ${formatSqlValue(oldUser.remember_token)}, ${formatSqlValue(
                    oldUser.current_team_id,
                )}, ${formatSqlValue(oldUser.profile_photo_path)}, ${formatSqlValue(
                    oldUser.created_at,
                )}, ${formatSqlValue(oldUser.updated_at)}, NULL, ${newIsApprentice}, ${newIsAusbilder}, NULL, NULL, NULL)`
                newInserts.push(newInsertRow)
            }
        }

        // Generiere die endgültige SQL-Datei
        let outputSql = `-- SQL-Inserts für die neue users-Tabelle (generiert am ${new Date().toISOString()})\n\n`
        outputSql += `INSERT INTO "users" ("id", "first_name", "last_name", "name", "email", "email_verified_at", "birth_date", "role_id", "vacation_days_per_year", "initials", "entry_date", "employee_number", "is_active", "password", "two_factor_secret", "two_factor_recovery_codes", "two_factor_confirmed_at", "remember_token", "current_team_id", "profile_photo_path", "created_at", "updated_at", "mentor_id", "is_apprentice", "is_ausbilder", "outlook_access_token", "outlook_refresh_token", "outlook_expires_at") VALUES\n`
        outputSql += newInserts.join(",\n") + ";\n\n"

        // Setze die Sequenz für PostgreSQL, um sicherzustellen, dass neue IDs korrekt vergeben werden
        outputSql += `-- Setze die ID-Sequenz für PostgreSQL nach dem Import\n`
        outputSql += `SELECT setval('users_id_seq', (SELECT MAX(id) FROM users));\n`

        fs.writeFileSync(NEW_SQL_OUTPUT_FILE, outputSql, "utf8")
        console.log(`Migration erfolgreich! Die neuen SQL-Inserts wurden in "${NEW_SQL_OUTPUT_FILE}" gespeichert.`)
        console.log(`Maximale ID im alten Dump: ${maxId}`)
    } catch (error) {
        console.error("Fehler bei der Migration:", error)
    }
}

migrateUsers()
