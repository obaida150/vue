<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

// PrimeVue Komponenten explizit importieren
import InputSwitch from 'primevue/inputswitch';
import Dropdown from 'primevue/dropdown';
import Chart from 'primevue/chart';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

const toast = useToast();
const page = usePage();

// Benutzer aus Inertia-Props holen
const currentUser = computed(() => page.props.auth.user);
const userRole = computed(() => currentUser.value.role_id || 4); // Standard: Mitarbeiter

const selectedEmployee = ref(null);
const availableEmployees = ref([]);
const selectedYear = ref(new Date().getFullYear());
const selectedMonth = ref(null);
const loading = ref(true);
const showTeamOverview = ref(false); // Toggle für die Gesamtübersicht

// Neue Variablen für die Abteilungsfilterung
const selectedDepartment = ref(null);
const availableDepartments = ref([]);
const isHR = computed(() => userRole.value <= 2); // HR-Rolle (1 oder 2)

const years = ref([
    new Date().getFullYear() - 2,
    new Date().getFullYear() - 1,
    new Date().getFullYear(),
    new Date().getFullYear() + 1
]);

const months = ref([
    { name: 'Januar', value: 1 },
    { name: 'Februar', value: 2 },
    { name: 'März', value: 3 },
    { name: 'April', value: 4 },
    { name: 'Mai', value: 5 },
    { name: 'Juni', value: 6 },
    { name: 'Juli', value: 7 },
    { name: 'August', value: 8 },
    { name: 'September', value: 9 },
    { name: 'Oktober', value: 10 },
    { name: 'November', value: 11 },
    { name: 'Dezember', value: 12 }
]);

// Statistik-Daten
const statistics = ref({
    homeoffice: {
        total: 0,
        monthly: []
    },
    absence: {
        total: 0,
        monthly: []
    },
    fieldService: {
        total: 0,
        monthly: []
    },
    vacation: {
        used: 0,
        total: 30,
        entries: []
    }
});

const vacationData = ref([]);
const teamVacationOverview = ref([]); // Neue Daten für die Teamübersicht
const allVacationRequests = ref([]); // Alle Urlaubsanträge
const allEmployees = ref([]); // Alle Mitarbeiter (ungefiltert)

// Chart-Daten
const homeofficeChartData = computed(() => {
    const labels = selectedMonth.value
        ? Array.from({ length: getDaysInMonth(selectedYear.value, selectedMonth.value) }, (_, i) => i + 1)
        : months.value.map(m => m.name);

    return {
        labels,
        datasets: [
            {
                label: 'Homeoffice Tage',
                backgroundColor: 'rgba(59, 130, 246, 0.5)',
                borderColor: 'rgb(59, 130, 246)',
                data: statistics.value.homeoffice.monthly
            }
        ]
    };
});

const absenceChartData = computed(() => {
    return {
        labels: months.value.map(m => m.name),
        datasets: [
            {
                label: 'Abwesenheitstage',
                backgroundColor: 'rgba(245, 158, 11, 0.5)',
                borderColor: 'rgb(245, 158, 11)',
                data: statistics.value.absence.monthly
            }
        ]
    };
});

const fieldServiceChartData = computed(() => {
    return {
        labels: months.value.map(m => m.name),
        datasets: [
            {
                label: 'Außendiensttage',
                backgroundColor: 'rgba(139, 92, 246, 0.5)',
                borderColor: 'rgb(139, 92, 246)',
                data: statistics.value.fieldService.monthly
            }
        ]
    };
});

const chartOptions = {
    plugins: {
        legend: {
            position: 'bottom'
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                stepSize: 1
            }
        }
    },
    responsive: true,
    maintainAspectRatio: false
};

// Funktionen
function getMonthName(monthNumber) {
    const month = months.value.find(m => m.value === monthNumber);
    return month ? month.name : '';
}

function getDaysInMonth(year, month) {
    return new Date(year, month, 0).getDate();
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('de-DE');
}

function getStatusSeverity(status) {
    switch (status) {
        case 'approved': return 'success';
        case 'pending': return 'warning';
        case 'rejected': return 'danger';
        default: return 'info';
    }
}

// Verbessere die loadTeamVacationOverview-Funktion, um die Abteilungszuweisung zu korrigieren
async function loadTeamVacationOverview() {
    try {
        // Nur für Abteilungsleiter und HR
        if (userRole.value > 3) return;

        // Verwende die bereits geladenen Mitarbeiter
        let employees = [...allEmployees.value];

        // Filtere Mitarbeiter basierend auf der Rolle und ausgewählter Abteilung
        if (userRole.value === 3) { // Abteilungsleiter sieht nur sein Team
            employees = employees.filter(emp =>
                emp.team_id === currentUser.value.current_team_id
            );
        } else if (isHR.value && selectedDepartment.value && selectedDepartment.value.id) {
            // HR mit ausgewählter Abteilung
            employees = employees.filter(emp =>
                emp.team_id === selectedDepartment.value.id
            );
        }

        // Initialisiere die Teamübersicht
        const teamData = [];

        // Lade alle genehmigten Urlaubsanträge
        const allRequestsResponse = await axios.get('/api/vacation/all-requests');
        const allRequests = allRequestsResponse.data || [];

        // Definiere eine Map für Abteilungsnamen
        const teamNames = {
            1: 'IT',
            2: 'Schaden',
            3: 'Vertrieb',
            4: 'Personal',
            5: 'Betrieb',
            6: 'Geschäftsleitung'
        };

        // Verarbeite die Daten für jeden Mitarbeiter
        for (const employee of employees) {
            // Filtere Urlaubsanträge für diesen Mitarbeiter und das ausgewählte Jahr
            const employeeRequests = allRequests.filter(req => {
                // Prüfe, ob die Felder existieren
                if (!req.start_date || !req.user_id) {
                    console.warn('Ungültiger Urlaubsantrag:', req);
                    return false;
                }

                // Prüfe, ob die user_id als String oder Zahl vorliegt
                const reqUserId = typeof req.user_id === 'string' ? parseInt(req.user_id, 10) : req.user_id;
                const empId = typeof employee.id === 'string' ? parseInt(employee.id, 10) : employee.id;

                // Prüfe das Jahr
                let startYear;
                try {
                    startYear = new Date(req.start_date).getFullYear();
                } catch (e) {
                    console.warn('Ungültiges Startdatum:', req.start_date);
                    return false;
                }

                return reqUserId === empId && startYear === selectedYear.value;
            });

            // Berechne die Summe der genehmigten Urlaubstage
            const usedDays = employeeRequests
                .filter(req => req.status === 'approved')
                .reduce((sum, req) => {
                    // Prüfe, ob days eine Zahl ist
                    const days = typeof req.days === 'number' ? req.days : parseInt(req.days, 10);
                    return sum + (isNaN(days) ? 0 : days);
                }, 0);

            // Standard-Urlaubsanspruch (kann angepasst werden)
            const totalEntitlement = 30;

            // Hole den Abteilungsnamen
            let teamName = 'Keine Abteilung';

            // Versuche, den Abteilungsnamen aus verschiedenen Quellen zu bekommen
            if (employee.team && employee.team.name) {
                teamName = employee.team.name;
            } else if (employee.team_id) {
                // Suche die Abteilung in den verfügbaren Abteilungen
                const department = availableDepartments.value.find(dept => dept.id === employee.team_id);
                if (department) {
                    teamName = department.name;
                } else {
                    // Verwende den benutzerdefinierten Namen aus der Map, falls vorhanden
                    teamName = teamNames[employee.team_id] || `Team ${employee.team_id}`;
                }
            }

            // Füge den Mitarbeiter zur Teamübersicht hinzu - mit vereinfachten Feldnamen
            teamData.push({
                name: employee.name,
                abteilung: teamName,
                tage: usedDays,
                resttage: totalEntitlement - usedDays,
                tagelast: 0 // Standardwert, falls keine Daten verfügbar
            });
        }

        teamVacationOverview.value = teamData;
    } catch (error) {
        console.error('Fehler beim Laden der Team-Urlaubsdaten:', error);
        toast.add({ severity: 'error', summary: 'Fehler', detail: 'Fehler beim Laden der Team-Urlaubsdaten', life: 3000 });

        // Setze leere Daten im Fehlerfall
        teamVacationOverview.value = [];
    }
}

async function loadDepartments() {
    try {
        // Versuche die Teams aus den Inertia-Props zu lesen
        const userTeams = page.props.auth?.user?.all_teams || [];

        if (userTeams && userTeams.length > 0) {
            // Konvertiere die Teams in das richtige Format
            availableDepartments.value = userTeams.map(team => ({
                id: team.id,
                name: team.name
            }));
        }
        // Unabhängig davon, ob Teams aus Inertia-Props geladen wurden,
        // rufe immer createLocalDepartmentsFromEmployees auf, um sicherzustellen,
        // dass alle Abteilungen geladen werden
        createLocalDepartmentsFromEmployees();
        // Option für "Alle Abteilungen" hinzufügen, falls noch nicht vorhanden
        if (!availableDepartments.value.some(dept => dept.id === null)) {
            availableDepartments.value.unshift({ id: null, name: 'Alle Abteilungen' });
        }
    } catch (error) {
        console.error('Fehler beim Laden der Abteilungen:', error);
        // Fallback: Verwende Mitarbeiterdaten
        createLocalDepartmentsFromEmployees();

        // Option für "Alle Abteilungen" hinzufügen
        if (!availableDepartments.value.some(dept => dept.id === null)) {
            availableDepartments.value.unshift({ id: null, name: 'Alle Abteilungen' });
        }
    }
}

// Verbessere die createLocalDepartmentsFromEmployees-Funktion, um sicherzustellen, dass alle Abteilungen geladen werden
function createLocalDepartmentsFromEmployees() {
    const departments = new Map();
    const teamNames = {
        1: 'IT',
        2: 'Schaden',
        3: 'Vertrieb',
        4: 'Personal',
        5: 'Betrieb',
        6: 'Geschäftsleitung'
    };

    // Füge zuerst alle bekannten Abteilungen aus der teamNames-Map hinzu
    Object.entries(teamNames).forEach(([id, name]) => {
        departments.set(parseInt(id), {
            id: parseInt(id),
            name: name
        });
    });

    // Wenn Mitarbeiterdaten vorhanden sind, ergänze die Liste
    if (allEmployees.value && allEmployees.value.length > 0) {
        allEmployees.value.forEach(employee => {
            let teamId = null;
            let teamName = null;

            // Versuche, team_id und team-Name aus verschiedenen möglichen Strukturen zu extrahieren
            if (employee.team) {
                if (typeof employee.team === 'object') {
                    teamId = employee.team.id;
                    teamName = employee.team.name;
                } else if (typeof employee.team === 'number' || typeof employee.team === 'string') {
                    teamId = employee.team;
                    // Verwende den benutzerdefinierten Namen aus der Map, falls vorhanden
                    teamName = teamNames[teamId] || `Team ${employee.team}`;
                }
            }

            // Alternative: Direkte team_id und department_id/name Felder prüfen
            if (!teamId && employee.team_id) {
                teamId = employee.team_id;
                // Verwende den benutzerdefinierten Namen aus der Map, falls vorhanden
                teamName = employee.department_name || teamNames[teamId] || `Team ${teamId}`;
            }

            // Weitere mögliche Felder prüfen
            if (!teamId && employee.department_id) {
                teamId = employee.department_id;
                // Verwende den benutzerdefinierten Namen aus der Map, falls vorhanden
                teamName = employee.department_name || teamNames[teamId] || `Abteilung ${teamId}`;
            }

            // Wenn eine Abteilung gefunden wurde, zur Map hinzufügen
            if (teamId && !departments.has(teamId)) {
                departments.set(teamId, {
                    id: teamId,
                    name: teamName || teamNames[teamId] || `Abteilung ${teamId}`
                });
            }
        });
    } else {
        console.log('Keine Mitarbeiterdaten für Abteilungsextraktion verfügbar');
    }

    // Wenn keine Abteilungen gefunden wurden, verwende Standard-Abteilungen
    if (departments.size === 0) {
        availableDepartments.value = [
            { id: 1, name: 'IT' },
            { id: 2, name: 'Schaden' },
            { id: 3, name: 'Vertrieb' },
            { id: 4, name: 'Personal' },
            { id: 5, name: 'Betrieb' },
            { id: 6, name: 'Geschäftsleitung' }
        ];
    } else {
        availableDepartments.value = Array.from(departments.values());
    }
}

// Aktualisiere die initializeDashboard-Funktion, um die Debug-Funktion aufzurufen
async function initializeDashboard() {
    try {
        loading.value = true;

        // Lade verfügbare Mitarbeiter basierend auf der Rolle
        await loadAvailableEmployees();

        // Lade Abteilungen NACH dem Laden der Mitarbeiter (für HR)
        if (isHR.value) {
            await loadDepartments();
        }

        // Setze den aktuellen Benutzer als ausgewählten Mitarbeiter
        selectedEmployee.value = {
            id: currentUser.value.id,
            name: currentUser.value.name
        };

        // Lade alle Urlaubsanträge (für die Teamübersicht)
        await loadAllVacationRequests();

        // Lade Daten für den aktuellen Benutzer
        await loadEmployeeData();

        // Wenn Abteilungsleiter oder HR und Teamübersicht aktiviert, lade auch die Teamübersicht
        if (userRole.value <= 3 && showTeamOverview.value) {
            await loadTeamVacationOverview();
        }
    } catch (error) {
        console.error('Fehler beim Initialisieren des Dashboards:', error);
        toast.add({ severity: 'error', summary: 'Fehler', detail: 'Fehler beim Initialisieren des Dashboards', life: 3000 });
    } finally {
        loading.value = false;
    }
}

async function loadAvailableEmployees() {
    try {
        // Lade Mitarbeiter basierend auf der Rolle
        let employees = [];

        if (userRole.value <= 3) { // HR oder Abteilungsleiter
            const response = await axios.get('/api/employees');
            allEmployees.value = response.data; // Speichere alle Mitarbeiter
            employees = [...allEmployees.value]; // Kopie erstellen

            // Filtere Mitarbeiter basierend auf der Rolle und ausgewählter Abteilung
            if (userRole.value === 3) { // Abteilungsleiter sieht nur sein Team
                employees = employees.filter(emp =>
                    emp.team_id === currentUser.value.current_team_id
                );
            } else if (isHR.value && selectedDepartment.value && selectedDepartment.value.id) {
                // HR mit ausgewählter Abteilung
                employees = employees.filter(emp =>
                    emp.team_id === selectedDepartment.value.id
                );
            }
        } else {
            // Mitarbeiter sieht nur sich selbst
            employees = [{
                id: currentUser.value.id,
                name: currentUser.value.name
            }];
        }

        availableEmployees.value = employees;
    } catch (error) {
        console.error('Fehler beim Laden der Mitarbeiterdaten:', error);
        toast.add({ severity: 'error', summary: 'Fehler', detail: 'Fehler beim Laden der Mitarbeiterdaten', life: 3000 });
    }
}

// Lade alle Urlaubsanträge für die Teamübersicht
async function loadAllVacationRequests() {
    try {
        // Lade alle Urlaubsanträge
        const response = await axios.get('/api/vacation/user');
        const requests = response.data.requests || [];
        // Speichere alle Urlaubsanträge
        allVacationRequests.value = requests;
    } catch (error) {
        console.error('Fehler beim Laden aller Urlaubsanträge:', error);
        toast.add({ severity: 'error', summary: 'Fehler', detail: 'Fehler beim Laden aller Urlaubsanträge', life: 3000 });
    }
}

async function loadEmployeeData() {
    if (!selectedEmployee.value) return;

    try {
        loading.value = true;

        // Lade Urlaubsdaten
        await loadVacationData();

        // Lade Homeoffice- und Abwesenheitsdaten
        await loadEventsData();
    } catch (error) {
        console.error('Fehler beim Laden der Daten:', error);
        toast.add({ severity: 'error', summary: 'Fehler', detail: 'Fehler beim Laden der Daten', life: 3000 });
    } finally {
        loading.value = false;
    }
}

// Korrigierte Funktion zum Laden der Urlaubsdaten
async function loadVacationData() {
    try {
        // Prüfen, ob der ausgewählte Mitarbeiter der angemeldete Benutzer ist
        const isCurrentUser = selectedEmployee.value.id === currentUser.value.id;

        if (isCurrentUser) {
            // Für den angemeldeten Benutzer können wir den normalen Endpunkt verwenden
            const response = await axios.get(`/api/vacation/yearly/${selectedYear.value}`);
            const vacationStats = response.data.stats;

            // Aktualisiere die Urlaubsstatistik
            statistics.value.vacation.total = vacationStats.totalEntitlement;
            statistics.value.vacation.used = vacationStats.used;
        } else {
            // Standard-Urlaubsanspruch (kann angepasst werden)
            const totalEntitlement = 30;

            // Lade alle Urlaubsanträge
            const allRequestsResponse = await axios.get('/api/vacation/all-requests');
            const allRequests = allRequestsResponse.data || [];
            // Filtere Urlaubsanträge für den ausgewählten Mitarbeiter und das ausgewählte Jahr
            const employeeRequests = allRequests.filter(req => {
                // Prüfe, ob die Felder existieren
                if (!req.start_date || !req.user_id) {
                    console.warn('Ungültiger Urlaubsantrag:', req);
                    return false;
                }

                // Prüfe, ob die user_id als String oder Zahl vorliegt
                const reqUserId = typeof req.user_id === 'string' ? parseInt(req.user_id, 10) : req.user_id;
                const empId = typeof selectedEmployee.value.id === 'string' ?
                    parseInt(selectedEmployee.value.id, 10) : selectedEmployee.value.id;

                // Prüfe das Jahr
                let startYear;
                try {
                    startYear = new Date(req.start_date).getFullYear();
                } catch (e) {
                    console.warn('Ungültiges Startdatum:', req.start_date);
                    return false;
                }

                return reqUserId === empId && startYear === selectedYear.value;
            });

            // Berechne die Summe der genehmigten Urlaubstage
            const usedDays = employeeRequests
                .filter(req => req.status === 'approved')
                .reduce((sum, req) => {
                    // Prüfe, ob days eine Zahl ist
                    const days = typeof req.days === 'number' ? req.days : parseInt(req.days, 10);
                    return sum + (isNaN(days) ? 0 : days);
                }, 0);

            // Aktualisiere die Urlaubsstatistik
            statistics.value.vacation.total = totalEntitlement;
            statistics.value.vacation.used = usedDays;

            // Formatiere die Daten für die Tabelle
            vacationData.value = employeeRequests.map(req => ({
                start_date: req.start_date,
                end_date: req.end_date,
                days: req.days,
                status: req.status,
                notes: req.notes || ''
            }));

            return; // Wir haben die Daten bereits verarbeitet
        }

        // Lade Urlaubsanträge für den angemeldeten Benutzer
        const userResponse = await axios.get('/api/vacation/user');
        const requests = userResponse.data.requests;

        // Filtere Urlaubsanträge für den ausgewählten Benutzer und das ausgewählte Jahr
        const filteredRequests = requests.filter(req => {
            if (!req.startDate) return false;

            const startYear = new Date(req.startDate).getFullYear();
            return startYear === selectedYear.value;
        });

        // Formatiere die Daten für die Tabelle
        vacationData.value = filteredRequests.map(req => ({
            start_date: req.startDate,
            end_date: req.endDate,
            days: req.days,
            status: req.status,
            notes: req.notes || ''
        }));
    } catch (error) {
        console.error('Fehler beim Laden der Urlaubsdaten:', error);
        toast.add({ severity: 'error', summary: 'Fehler', detail: 'Fehler beim Laden der Urlaubsdaten', life: 3000 });
    }
}

async function loadEventsData() {
    try {
        // Lade Ereignistypen
        const eventTypesResponse = await axios.get('/api/event-types');
        const eventTypes = eventTypesResponse.data;

        // Finde die IDs für Homeoffice, Abwesend und Außendienst
        const homeofficeTypeId = eventTypes.find(type => type.name === 'Homeoffice')?.id;
        const absenceTypeId = eventTypes.find(type => type.name === 'Abwesend')?.id;
        const fieldServiceTypeId = eventTypes.find(type => type.name === 'Außendienst')?.id;

        // Parameter für die Ereignisabfrage
        const params = {
            start_date: `${selectedYear.value}-01-01`,
            end_date: `${selectedYear.value}-12-31`
        };

        // Lade Ereignisse
        const eventsResponse = await axios.get('/api/events', { params });
        const events = eventsResponse.data;

        // Filtere Ereignisse für den ausgewählten Benutzer
        const userEvents = events.filter(event =>
            event.user_id === selectedEmployee.value.id
        );

        // Verarbeite Homeoffice-Daten
        processHomeofficeData(userEvents, homeofficeTypeId);

        // Verarbeite Abwesenheitsdaten
        processAbsenceData(userEvents, absenceTypeId);

        // Verarbeite Außendienst-Daten
        processFieldServiceData(userEvents, fieldServiceTypeId);
    } catch (error) {
        console.error('Fehler beim Laden der Ereignisdaten:', error);
        toast.add({ severity: 'error', summary: 'Fehler', detail: 'Fehler beim Laden der Ereignisdaten', life: 3000 });
    }
}

function processHomeofficeData(events, homeofficeTypeId) {
    // Initialisiere monatliche Daten
    const monthlyData = Array(12).fill(0);
    let total = 0;

    // Filtere Homeoffice-Ereignisse
    const homeofficeEvents = events.filter(event => event.event_type_id === homeofficeTypeId);

    // Verarbeite jedes Homeoffice-Ereignis
    homeofficeEvents.forEach(event => {
        const startDate = new Date(event.start_date);
        const endDate = new Date(event.end_date);

        // Wenn ein Monat ausgewählt ist, filtere nur Ereignisse in diesem Monat
        if (selectedMonth.value && (startDate.getMonth() + 1 !== selectedMonth.value && endDate.getMonth() + 1 !== selectedMonth.value)) {
            return;
        }

        // Berechne die Anzahl der Tage
        const days = calculateWorkingDays(startDate, endDate);
        total += days;

        // Verteile die Tage auf die entsprechenden Monate
        if (selectedMonth.value) {
            // Wenn ein Monat ausgewählt ist, erstelle tägliche Daten
            const daysInMonth = getDaysInMonth(selectedYear.value, selectedMonth.value);
            const dailyData = Array(daysInMonth).fill(0);

            let currentDate = new Date(startDate);
            while (currentDate <= endDate) {
                if (currentDate.getMonth() + 1 === selectedMonth.value) {
                    const day = currentDate.getDate() - 1; // 0-basierter Index
                    dailyData[day] = 1;
                }
                currentDate.setDate(currentDate.getDate() + 1);
            }

            statistics.value.homeoffice.monthly = dailyData;
        } else {
            // Wenn kein Monat ausgewählt ist, verteile auf Monate
            let currentDate = new Date(startDate);
            while (currentDate <= endDate) {
                // Zähle nur Werktage (Mo-Fr)
                const dayOfWeek = currentDate.getDay();
                if (dayOfWeek !== 0 && dayOfWeek !== 6) { // Nicht Sonntag und nicht Samstag
                    const month = currentDate.getMonth();
                    monthlyData[month]++;
                }
                currentDate.setDate(currentDate.getDate() + 1);
            }

            statistics.value.homeoffice.monthly = monthlyData;
        }
    });

    statistics.value.homeoffice.total = total;
}

function processAbsenceData(events, absenceTypeId) {
    // Initialisiere monatliche Daten
    const monthlyData = Array(12).fill(0);
    let total = 0;

    // Filtere Abwesenheits-Ereignisse
    const absenceEvents = events.filter(event => event.event_type_id === absenceTypeId);

    // Verarbeite jedes Abwesenheits-Ereignis
    absenceEvents.forEach(event => {
        const startDate = new Date(event.start_date);
        const endDate = new Date(event.end_date);

        // Berechne die Anzahl der Tage
        const days = calculateWorkingDays(startDate, endDate);
        total += days;

        // Verteile die Tage auf die entsprechenden Monate
        let currentDate = new Date(startDate);
        while (currentDate <= endDate) {
            // Zähle nur Werktage (Mo-Fr)
            const dayOfWeek = currentDate.getDay();
            if (dayOfWeek !== 0 && dayOfWeek !== 6) { // Nicht Sonntag und nicht Samstag
                const month = currentDate.getMonth();
                monthlyData[month]++;
            }
            currentDate.setDate(currentDate.getDate() + 1);
        }
    });

    statistics.value.absence.monthly = monthlyData;
    statistics.value.absence.total = total;
}

function processFieldServiceData(events, fieldServiceTypeId) {
    // Initialisiere monatliche Daten
    const monthlyData = Array(12).fill(0);
    let total = 0;

    // Filtere Außendienst-Ereignisse
    const fieldServiceEvents = events.filter(event => event.event_type_id === fieldServiceTypeId);

    // Verarbeite jedes Außendienst-Ereignis
    fieldServiceEvents.forEach(event => {
        const startDate = new Date(event.start_date);
        const endDate = new Date(event.end_date);

        // Berechne die Anzahl der Tage
        const days = calculateWorkingDays(startDate, endDate);
        total += days;

        // Verteile die Tage auf die entsprechenden Monate
        let currentDate = new Date(startDate);
        while (currentDate <= endDate) {
            // Zähle nur Werktage (Mo-Fr)
            const dayOfWeek = currentDate.getDay();
            if (dayOfWeek !== 0 && dayOfWeek !== 6) { // Nicht Sonntag und nicht Samstag
                const month = currentDate.getMonth();
                monthlyData[month]++;
            }
            currentDate.setDate(currentDate.getDate() + 1);
        }
    });

    statistics.value.fieldService.monthly = monthlyData;
    statistics.value.fieldService.total = total;
}

function calculateWorkingDays(startDate, endDate) {
    let count = 0;
    let currentDate = new Date(startDate);

    while (currentDate <= endDate) {
        // Zähle nur Werktage (Mo-Fr)
        const dayOfWeek = currentDate.getDay();
        if (dayOfWeek !== 0 && dayOfWeek !== 6) { // Nicht Sonntag und nicht Samstag
            count++;
        }
        currentDate.setDate(currentDate.getDate() + 1);
    }

    return count;
}

function updateData() {
    loadEmployeeData();

    // Wenn Abteilungsleiter oder HR und Teamübersicht aktiviert, lade auch die Teamübersicht neu
    if (userRole.value <= 3 && showTeamOverview.value) {
        loadTeamVacationOverview();
    }
}

// Beobachte Änderungen am ausgewählten Mitarbeiter
watch(selectedEmployee, (newValue) => {
    if (newValue) {
        loadEmployeeData();
    }
});

// Beobachte Änderungen am Toggle für die Teamübersicht
watch(showTeamOverview, (newValue) => {
    if (newValue && userRole.value <= 3) {
        loadTeamVacationOverview();
    }
});

// Beobachte Änderungen an der ausgewählten Abteilung
watch(selectedDepartment, (newValue) => {
    if (isHR.value) {
        // Aktualisiere die Mitarbeiterliste basierend auf der ausgewählten Abteilung
        loadAvailableEmployees();

        // Wenn Teamübersicht aktiviert ist, aktualisiere auch diese
        if (showTeamOverview.value) {
            loadTeamVacationOverview();
        }
    }
});

// Initialisierung
onMounted(() => {
    initializeDashboard();
});
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Mitarbeiter Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-[90rem] mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <!-- Filter Controls -->
                    <div class="mb-6 flex flex-col md:flex-row gap-4">
                        <div class="bg-white dark:bg-gray-700 rounded-lg shadow p-4 flex-1">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Zeitraum</h2>
                            <div class="flex flex-col sm:flex-row gap-4">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jahr</label>
                                    <Dropdown
                                        v-model="selectedYear"
                                        :options="years"
                                        placeholder="Jahr auswählen"
                                        class="w-full"
                                        @change="updateData"
                                    />
                                </div>
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Monat</label>
                                    <Dropdown
                                        v-model="selectedMonth"
                                        :options="months"
                                        optionLabel="name"
                                        optionValue="value"
                                        placeholder="Monat auswählen"
                                        class="w-full"
                                        @change="updateData"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Abteilungsfilter (nur für HR) -->
                    <div v-if="isHR" class="mb-6">
                        <div class="bg-white dark:bg-gray-700 rounded-lg shadow p-4">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Abteilungsfilter</h2>
                            <div class="flex flex-col sm:flex-row gap-4">
                                <div class="flex-1">
                                    <Dropdown
                                        v-model="selectedDepartment"
                                        :options="availableDepartments"
                                        optionLabel="name"
                                        placeholder="Abteilung auswählen"
                                        class="w-full"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Selector (nur für Abteilungsleiter und HR) -->
                    <div v-if="userRole <= 3" class="mb-6">
                        <div class="bg-white dark:bg-gray-700 rounded-lg shadow p-4">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Mitarbeiter auswählen</h2>
                            <Dropdown
                                v-model="selectedEmployee"
                                :options="availableEmployees"
                                optionLabel="name"
                                placeholder="Mitarbeiter auswählen"
                                class="w-full md:w-80"
                                @change="loadEmployeeData"
                            />
                        </div>
                    </div>

                    <!-- Loading Indicator -->
                    <div v-if="loading" class="flex justify-center items-center py-12">
                        <i class="pi pi-spin pi-spinner text-4xl text-blue-500"></i>
                    </div>

                    <div v-else>
                        <!-- Statistics Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                            <!-- Homeoffice Card -->
                            <div class="bg-white dark:bg-gray-700 rounded-lg shadow overflow-hidden">
                                <div class="p-5">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 bg-blue-100 dark:bg-blue-900 rounded-md p-3">
                                            <i class="pi pi-home text-blue-600 dark:text-blue-300 text-xl"></i>
                                        </div>
                                        <div class="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Homeoffice Tage</dt>
                                                <dd>
                                                    <div class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ statistics.homeoffice.total }}</div>
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800 px-5 py-3">
                                    <div class="text-sm">
                    <span class="font-medium text-blue-700 dark:text-blue-300">
                      {{ selectedMonth ? `${getMonthName(selectedMonth)} ${selectedYear}` : selectedYear }}
                    </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Abwesenheit Card -->
                            <div class="bg-white dark:bg-gray-700 rounded-lg shadow overflow-hidden">
                                <div class="p-5">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 bg-yellow-100 dark:bg-yellow-900 rounded-md p-3">
                                            <i class="pi pi-calendar-times text-yellow-600 dark:text-yellow-300 text-xl"></i>
                                        </div>
                                        <div class="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Abwesenheitstage</dt>
                                                <dd>
                                                    <div class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ statistics.absence.total }}</div>
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800 px-5 py-3">
                                    <div class="text-sm">
                    <span class="font-medium text-yellow-700 dark:text-yellow-300">
                      {{ selectedYear }}
                    </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Außendienst Card -->
                            <div class="bg-white dark:bg-gray-700 rounded-lg shadow overflow-hidden">
                                <div class="p-5">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 bg-purple-100 dark:bg-purple-900 rounded-md p-3">
                                            <i class="pi pi-briefcase text-purple-600 dark:text-purple-300 text-xl"></i>
                                        </div>
                                        <div class="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Außendiensttage</dt>
                                                <dd>
                                                    <div class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ statistics.fieldService.total }}</div>
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800 px-5 py-3">
                                    <div class="text-sm">
                    <span class="font-medium text-purple-700 dark:text-purple-300">
                      {{ selectedYear }}
                    </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Urlaub Card -->
                            <div class="bg-white dark:bg-gray-700 rounded-lg shadow overflow-hidden">
                                <div class="p-5">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 bg-green-100 dark:bg-green-900 rounded-md p-3">
                                            <i class="pi pi-calendar-plus text-green-600 dark:text-green-300 text-xl"></i>
                                        </div>
                                        <div class="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Urlaubstage</dt>
                                                <dd>
                                                    <div class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                                        {{ statistics.vacation.used }} / {{ statistics.vacation.total }}
                                                    </div>
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800 px-5 py-3">
                                    <div class="text-sm">
                    <span class="font-medium text-green-700 dark:text-green-300">
                      {{ selectedYear }}
                    </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Charts Section -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                            <!-- Homeoffice Chart -->
                            <div class="bg-white dark:bg-gray-700 rounded-lg shadow p-6">
                                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Homeoffice Übersicht</h2>
                                <Chart type="bar" :data="homeofficeChartData" :options="chartOptions" class="h-80" />
                            </div>

                            <!-- Abwesenheit Chart -->
                            <div class="bg-white dark:bg-gray-700 rounded-lg shadow p-6">
                                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Abwesenheit Übersicht</h2>
                                <Chart type="bar" :data="absenceChartData" :options="chartOptions" class="h-80" />
                            </div>

                            <!-- Außendienst Chart -->
                            <div class="bg-white dark:bg-gray-700 rounded-lg shadow p-6">
                                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Außendienst Übersicht</h2>
                                <Chart type="bar" :data="fieldServiceChartData" :options="chartOptions" class="h-80" />
                            </div>
                        </div>

                        <!-- Urlaub Section -->
                        <div class="bg-white dark:bg-gray-700 rounded-lg shadow overflow-hidden">
                            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-600 flex justify-between items-center">
                                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Urlaubsübersicht</h2>

                                <!-- Toggle für Teamübersicht (nur für Abteilungsleiter und HR) -->
                                <div v-if="userRole <= 3" class="flex items-center">
                                    <span class="mr-2 text-sm text-gray-600 dark:text-gray-400">Einzelansicht</span>
                                    <InputSwitch v-model="showTeamOverview" />
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Teamübersicht</span>
                                </div>
                            </div>

                            <!-- Einzelne Urlaubsanträge -->
                            <div v-if="!showTeamOverview || userRole > 3">
                                <DataTable
                                    :value="vacationData"
                                    :paginator="true"
                                    :rows="5"
                                    stripedRows
                                    responsiveLayout="scroll"
                                    class="p-datatable-sm"
                                >
                                    <Column field="start_date" header="Von" :sortable="true">
                                        <template #body="slotProps">
                                            {{ formatDate(slotProps.data.start_date) }}
                                        </template>
                                    </Column>
                                    <Column field="end_date" header="Bis" :sortable="true">
                                        <template #body="slotProps">
                                            {{ formatDate(slotProps.data.end_date) }}
                                        </template>
                                    </Column>
                                    <Column field="days" header="Tage" :sortable="true"></Column>
                                    <Column field="status" header="Status" :sortable="true">
                                        <template #body="slotProps">
                                            <Tag
                                                :value="slotProps.data.status"
                                                :severity="getStatusSeverity(slotProps.data.status)"
                                            />
                                        </template>
                                    </Column>
                                    <Column field="notes" header="Notizen" :sortable="true"></Column>
                                </DataTable>
                            </div>

                            <!-- Teamübersicht (nur für Abteilungsleiter und HR) -->
                            <div v-else>
                                <DataTable
                                    :value="teamVacationOverview"
                                    :paginator="true"
                                    :rows="10"
                                    stripedRows
                                    responsiveLayout="scroll"
                                    class="p-datatable-sm"
                                >
                                    <Column field="name" header="Name" :sortable="true"></Column>
                                    <Column field="abteilung" header="Abteilung" :sortable="true" v-if="isHR"></Column>
                                    <Column field="tage" header="Tage" :sortable="true"></Column>
                                    <Column field="resttage" header="Resttage" :sortable="true"></Column>
                                    <Column field="tagelast" header="Tagelast" :sortable="true"></Column>
                                </DataTable>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
