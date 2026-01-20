<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import dayjs from 'dayjs';

// PrimeVue Komponenten explizit importieren
import ToggleSwitch from 'primevue/toggleswitch';
import Select from 'primevue/select';
import Chart from 'primevue/chart';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

const toast = useToast();
const page = usePage();

const currentUser = computed(() => page.props.auth.user);
const userRole = computed(() => currentUser.value.role_id || 4);

const selectedEmployee = ref(null);
const availableEmployees = ref([]);
const selectedYear = ref(new Date().getFullYear());
const selectedMonth = ref(null);
const loading = ref(true);
const showTeamOverview = ref(false);

const selectedDepartment = ref(null);
const availableDepartments = ref([]);
const isHR = computed(() => userRole.value <= 2);

const years = ref([
    new Date().getFullYear() - 2,
    new Date().getFullYear() - 1,
    new Date().getFullYear(),
    new Date().getFullYear() + 1
]);

const months = ref([
    { name: 'Alle Monate', value: null },
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
const teamVacationOverview = ref([]);
const allVacationRequests = ref([]);
const allEmployees = ref([]);

const homeofficeChartData = computed(() => {
    if (selectedMonth.value) {
        const daysInMonth = getDaysInMonth(selectedYear.value, selectedMonth.value);
        const labels = Array.from({ length: daysInMonth }, (_, i) => i + 1);

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
    } else {
        return {
            labels: months.value.filter(m => m.value !== null).map(m => m.name),
            datasets: [
                {
                    label: 'Homeoffice Tage',
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: 'rgb(59, 130, 246)',
                    data: statistics.value.homeoffice.monthly
                }
            ]
        };
    }
});

const absenceChartData = computed(() => {
    if (selectedMonth.value) {
        const daysInMonth = getDaysInMonth(selectedYear.value, selectedMonth.value);
        const labels = Array.from({ length: daysInMonth }, (_, i) => i + 1);

        return {
            labels,
            datasets: [
                {
                    label: 'Abwesenheitstage',
                    backgroundColor: 'rgba(245, 158, 11, 0.5)',
                    borderColor: 'rgb(245, 158, 11)',
                    data: statistics.value.absence.monthly
                }
            ]
        };
    } else {
        return {
            labels: months.value.filter(m => m.value !== null).map(m => m.name),
            datasets: [
                {
                    label: 'Abwesenheitstage',
                    backgroundColor: 'rgba(245, 158, 11, 0.5)',
                    borderColor: 'rgb(245, 158, 11)',
                    data: statistics.value.absence.monthly
                }
            ]
        };
    }
});

const fieldServiceChartData = computed(() => {
    if (selectedMonth.value) {
        const daysInMonth = getDaysInMonth(selectedYear.value, selectedMonth.value);
        const labels = Array.from({ length: daysInMonth }, (_, i) => i + 1);

        return {
            labels,
            datasets: [
                {
                    label: 'Außendiensttage',
                    backgroundColor: 'rgba(139, 92, 246, 0.5)',
                    borderColor: 'rgb(139, 92, 246)',
                    data: statistics.value.fieldService.monthly
                }
            ]
        };
    } else {
        return {
            labels: months.value.filter(m => m.value !== null).map(m => m.name),
            datasets: [
                {
                    label: 'Außendiensttage',
                    backgroundColor: 'rgba(139, 92, 246, 0.5)',
                    borderColor: 'rgb(139, 92, 246)',
                    data: statistics.value.fieldService.monthly
                }
            ]
        };
    }
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

function getMonthName(monthNumber) {
    if (monthNumber === null) return 'Alle Monate';
    const month = months.value.find(m => m.value === monthNumber);
    return month ? month.name : '';
}

function getDaysInMonth(year, month) {
    return new Date(year, month, 0).getDate();
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}.${month}.${year}`;
}

function getStatusSeverity(status) {
    switch (status) {
        case 'approved': return 'success';
        case 'pending': return 'warning';
        case 'rejected': return 'danger';
        default: return 'info';
    }
}

const getActualDays = (request) => {
    if (request.actualDays !== undefined) {
        return request.actualDays
    }

    if (request.dayType && request.dayType !== 'full_day') {
        const startDate = dayjs(request.start_date)
        const endDate = dayjs(request.end_date)
        if (startDate.isSame(endDate, 'day')) {
            return 0.5
        }
    }

    return request.days || 0
}

async function loadTeamVacationOverview() {
    try {
        if (userRole.value > 3) return;

        let employees = [...allEmployees.value];

        if (userRole.value === 3) {
            employees = employees.filter(emp =>
                emp.team_id === currentUser.value.current_team_id
            );
        } else if (isHR.value && selectedDepartment.value && selectedDepartment.value.id) {
            employees = employees.filter(emp =>
                emp.team_id === selectedDepartment.value.id
            );
        }

        const teamData = [];

        const allRequestsResponse = await axios.get('/api/vacation/all-requests');
        const allRequests = allRequestsResponse.data || [];

        const teamNames = {
            1: 'IT',
            2: 'Schaden',
            3: 'Vertrieb',
            4: 'Personal',
            5: 'Betrieb',
            6: 'Geschäftsleitung',
            7: 'Azubis'
        };

        for (const employee of employees) {
            const employeeRequests = allRequests.filter(req => {
                if (!req.start_date || !req.user_id) {
                    console.warn('Ungültiger Urlaubsantrag:', req);
                    return false;
                }

                const reqUserId = typeof req.user_id === 'string' ? parseInt(req.user_id, 10) : req.user_id;
                const empId = typeof employee.id === 'string' ? parseInt(employee.id, 10) : employee.id;

                let startYear;
                try {
                    startYear = new Date(req.start_date).getFullYear();
                } catch (e) {
                    console.warn('Ungültiges Startdatum:', req.start_date);
                    return false;
                }

                return reqUserId === empId && startYear === selectedYear.value;
            });

            const usedDays = employeeRequests
                .filter(req => req.status === 'approved')
                .reduce((sum, req) => {
                    return sum + getActualDays(req);
                }, 0);

            const totalEntitlement = 30;

            let teamName = 'Keine Abteilung';

            if (employee.team && employee.team.name) {
                teamName = employee.team.name;
            } else if (employee.team_id) {
                const department = availableDepartments.value.find(dept => dept.id === employee.team_id);
                if (department) {
                    teamName = department.name;
                } else {
                    teamName = teamNames[employee.team_id] || `Team ${employee.team_id}`;
                }
            }

            teamData.push({
                name: employee.name,
                abteilung: teamName,
                tage: usedDays,
                resttage: totalEntitlement - usedDays,
                tagelast: 0
            });
        }

        teamVacationOverview.value = teamData;
    } catch (error) {
        console.error('Fehler beim Laden der Team-Urlaubsdaten:', error);
        toast.add({ severity: 'error', summary: 'Fehler', detail: 'Fehler beim Laden der Team-Urlaubsdaten', life: 3000 });

        teamVacationOverview.value = [];
    }
}

async function loadDepartments() {
    try {
        const userTeams = page.props.auth?.user?.all_teams || [];

        if (userTeams && userTeams.length > 0) {
            availableDepartments.value = userTeams.map(team => ({
                id: team.id,
                name: team.name
            }));
        }
        createLocalDepartmentsFromEmployees();
        if (!availableDepartments.value.some(dept => dept.id === null)) {
            availableDepartments.value.unshift({ id: null, name: 'Alle Abteilungen' });
        }
    } catch (error) {
        console.error('Fehler beim Laden der Abteilungen:', error);
        createLocalDepartmentsFromEmployees();

        if (!availableDepartments.value.some(dept => dept.id === null)) {
            availableDepartments.value.unshift({ id: null, name: 'Alle Abteilungen' });
        }
    }
}

function createLocalDepartmentsFromEmployees() {
    const departments = new Map();
    const teamNames = {
        1: 'IT',
        2: 'Schaden',
        3: 'Vertrieb',
        4: 'Personal',
        5: 'Betrieb',
        6: 'Geschäftsleitung',
        7: 'Azubis'
    };

    Object.entries(teamNames).forEach(([id, name]) => {
        departments.set(parseInt(id), {
            id: parseInt(id),
            name: name
        });
    });

    if (allEmployees.value && allEmployees.value.length > 0) {
        allEmployees.value.forEach(employee => {
            let teamId = null;
            let teamName = null;

            if (employee.team) {
                if (typeof employee.team === 'object') {
                    teamId = employee.team.id;
                    teamName = employee.team.name;
                } else if (typeof employee.team === 'number' || typeof employee.team === 'string') {
                    teamId = employee.team;
                    teamName = teamNames[teamId] || `Team ${teamId}`;
                }
            }

            if (!teamId && employee.team_id) {
                teamId = employee.team_id;
                teamName = employee.department_name || teamNames[teamId] || `Team ${teamId}`;
            }

            if (!teamId && employee.department_id) {
                teamId = employee.department_id;
                teamName = employee.department_name || teamNames[teamId] || `Abteilung ${teamId}`;
            }

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

    if (departments.size === 0) {
        availableDepartments.value = [
            { id: 1, name: 'IT' },
            { id: 2, name: 'Schaden' },
            { id: 3, name: 'Vertrieb' },
            { id: 4, name: 'Personal' },
            { id: 5, name: 'Betrieb' },
            { id: 6, name: 'Geschäftsleitung' },
            { id: 7, name: 'Azubis' },
            { id: null, name: 'Alle Abteilungen' }
        ];
    } else {
        availableDepartments.value = Array.from(departments.values());
    }
}

async function initializeDashboard() {
    try {
        loading.value = true;

        if (isHR.value) {
            await loadDepartments();
            if (availableDepartments.value.length > 0) {
                selectedDepartment.value = availableDepartments.value[0];
            }
        }

        await loadAvailableEmployees();

        selectedEmployee.value = {
            id: currentUser.value.id,
            name: currentUser.value.name
        };

        await loadAllVacationRequests();

        await loadEmployeeData();

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
        let employees = [];

        if (userRole.value <= 2) {
            const response = await axios.get('/api/employees');
            allEmployees.value = response.data;
            employees = [...allEmployees.value];

            if (selectedDepartment.value && selectedDepartment.value.id !== null) {
                employees = employees.filter(emp => emp.team_id === selectedDepartment.value.id);
            }
        } else if (userRole.value === 3) {
            const response = await axios.get('/api/employees');
            allEmployees.value = response.data;
            employees = allEmployees.value.filter(emp =>
                emp.id === currentUser.value.id ||
                emp.team_id === currentUser.value.current_team_id
            );
        } else {
            employees = [{
                id: currentUser.value.id,
                name: currentUser.value.name
            }];
        }

        availableEmployees.value = employees;

        if (selectedEmployee.value && !employees.find(emp => emp.id === selectedEmployee.value.id)) {
            selectedEmployee.value = employees.length > 0 ? employees[0] : null;
        }
    } catch (error) {
        console.error('Fehler beim Laden der Mitarbeiterdaten:', error);
        toast.add({ severity: 'error', summary: 'Fehler', detail: 'Fehler beim Laden der Mitarbeiterdaten', life: 3000 });
    }
}

async function loadAllVacationRequests() {
    try {
        const response = await axios.get('/api/vacation/user');
        const requests = response.data.requests || [];
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

        await loadVacationData();

        await loadEventsData();
    } catch (error) {
        console.error('Fehler beim Laden der Daten:', error);
        toast.add({ severity: 'error', summary: 'Fehler', detail: 'Fehler beim Laden der Daten', life: 3000 });
    } finally {
        loading.value = false;
    }
}

async function loadVacationData() {
    try {
        const isCurrentUser = selectedEmployee.value.id === currentUser.value.id;
        let requests = [];

        if (isCurrentUser) {
            const response = await axios.get(`/api/vacation/yearly/${selectedYear.value}`);
            const vacationStats = response.data.stats;

            statistics.value.vacation.total = vacationStats.totalEntitlement;

            const userResponse = await axios.get('/api/vacation/user');
            requests = userResponse.data.requests || [];
        } else {
            const allRequestsResponse = await axios.get('/api/vacation/all-requests');
            const allRequests = allRequestsResponse.data || [];
            requests = allRequests.filter(req => {
                if (!req.start_date || !req.user_id) return false;

                const reqUserId = typeof req.user_id === 'string' ? parseInt(req.user_id, 10) : req.user_id;
                const empId = typeof selectedEmployee.value.id === 'string' ?
                    parseInt(selectedEmployee.value.id, 10) : selectedEmployee.value.id;

                let startYear;
                try {
                    startYear = new Date(req.start_date).getFullYear();
                } catch (e) {
                    return false;
                }

                return reqUserId === empId && startYear === selectedYear.value;
            });

            statistics.value.vacation.total = 30;
        }

        // Berechne genutzte Urlaubstage basierend auf Jahr und optional Monat
        let usedDays = 0;

        const approvedRequests = requests.filter(req => {
            const status = req.status;
            return status === 'approved';
        });

        approvedRequests.forEach(req => {
            const startDate = new Date(req.start_date || req.startDate);
            const endDate = new Date(req.end_date || req.endDate);

            let currentDate = new Date(startDate);
            while (currentDate <= endDate) {
                const dayOfWeek = currentDate.getDay();
                const currentYear = currentDate.getFullYear();
                const currentMonth = currentDate.getMonth() + 1;

                if (dayOfWeek !== 0 && dayOfWeek !== 6 && currentYear === selectedYear.value) {
                    // Wenn ein Monat ausgewählt ist, nur Tage dieses Monats zählen
                    if (selectedMonth.value === null || currentMonth === selectedMonth.value) {
                        usedDays++;
                    }
                }
                currentDate.setDate(currentDate.getDate() + 1);
            }
        });

        // Berücksichtige halbe Tage
        approvedRequests.forEach(req => {
            const dayType = req.dayType || req.day_type;
            if (dayType && dayType !== 'full_day') {
                const startDate = new Date(req.start_date || req.startDate);
                const endDate = new Date(req.end_date || req.endDate);

                if (startDate.getTime() === endDate.getTime()) {
                    const currentMonth = startDate.getMonth() + 1;
                    if (startDate.getFullYear() === selectedYear.value) {
                        if (selectedMonth.value === null || currentMonth === selectedMonth.value) {
                            usedDays -= 0.5; // Korrigiere für halben Tag
                        }
                    }
                }
            }
        });

        statistics.value.vacation.used = usedDays;

        // Vacation data für Tabelle
       // Vacation data für Tabelle - gefiltert nach Jahr und optional Monat
       vacationData.value = requests
           .filter(req => {
               const startDate = new Date(req.start_date || req.startDate);
               const endDate = new Date(req.end_date || req.endDate);

               // Prüfe ob der Antrag im ausgewählten Jahr liegt
               const startsInYear = startDate.getFullYear() === selectedYear.value;
               const endsInYear = endDate.getFullYear() === selectedYear.value;
               const spansYear = startDate.getFullYear() <= selectedYear.value && endDate.getFullYear() >= selectedYear.value;

               if (!startsInYear && !endsInYear && !spansYear) {
                   return false;
               }

               // Wenn ein Monat ausgewählt ist, prüfe ob der Antrag in diesem Monat liegt
               if (selectedMonth.value !== null) {
                   const startsInMonth = startDate.getFullYear() === selectedYear.value &&
                                         (startDate.getMonth() + 1) === selectedMonth.value;
                   const endsInMonth = endDate.getFullYear() === selectedYear.value &&
                                       (endDate.getMonth() + 1) === selectedMonth.value;
                   const spansMonth = startDate <= new Date(selectedYear.value, selectedMonth.value - 1, 1) &&
                                      endDate >= new Date(selectedYear.value, selectedMonth.value, 0);

                   return startsInMonth || endsInMonth || spansMonth;
               }

               return true;
           })
           .map(req => ({
               start_date: req.start_date || req.startDate,
               end_date: req.end_date || req.endDate,
               days: req.days,
               status: req.status,
               notes: req.notes || '',
               dayType: req.dayType || req.day_type
           }));

    } catch (error) {
        console.error('Fehler beim Laden der Urlaubsdaten:', error);
        toast.add({ severity: 'error', summary: 'Fehler', detail: 'Fehler beim Laden der Urlaubsdaten', life: 3000 });
    }
}

async function loadEventsData() {
    try {
        const eventTypesResponse = await axios.get('/api/event-types');
        const eventTypes = eventTypesResponse.data;

        const homeofficeTypeId = eventTypes.find(type => type.name === 'Homeoffice')?.id;
        const absenceTypeId = eventTypes.find(type => type.name === 'Krank')?.id;
        const fieldServiceTypeId = eventTypes.find(type => type.name === 'Außendienst')?.id;

        const params = {
            start_date: `${selectedYear.value}-01-01`,
            end_date: `${selectedYear.value}-12-31`
        };

        const eventsResponse = await axios.get('/api/events', { params });
        const events = eventsResponse.data;

        const userEvents = events.filter(event =>
            event.user_id === selectedEmployee.value.id
        );

        processHomeofficeData(userEvents, homeofficeTypeId);

        processAbsenceData(userEvents, absenceTypeId);

        processFieldServiceData(userEvents, fieldServiceTypeId);
    } catch (error) {
        console.error('Fehler beim Laden der Ereignisdaten:', error);
        toast.add({ severity: 'error', summary: 'Fehler', detail: 'Fehler beim Laden der Ereignisdaten', life: 3000 });
    }
}

function processHomeofficeData(events, homeofficeTypeId) {
    let total = 0;

    const homeofficeEvents = events.filter(event => event.event_type_id === homeofficeTypeId);

    if (selectedMonth.value) {
        const daysInMonth = getDaysInMonth(selectedYear.value, selectedMonth.value);
        const dailyData = Array(daysInMonth).fill(0);

        homeofficeEvents.forEach(event => {
            const startDate = new Date(event.start_date);
            const endDate = new Date(event.end_date);

            let currentDate = new Date(startDate);
            while (currentDate <= endDate) {
                if (currentDate.getFullYear() === selectedYear.value &&
                    currentDate.getMonth() + 1 === selectedMonth.value) {
                    const day = currentDate.getDate() - 1;
                    const dayOfWeek = currentDate.getDay();

                    if (dayOfWeek !== 0 && dayOfWeek !== 6 && dailyData[day] === 0) {
                        dailyData[day] = 1;
                        total++;
                    }
                }
                currentDate.setDate(currentDate.getDate() + 1);
            }
        });

        statistics.value.homeoffice.monthly = dailyData;
    } else {
        const monthlyData = Array(12).fill(0);

        homeofficeEvents.forEach(event => {
            const startDate = new Date(event.start_date);
            const endDate = new Date(event.end_date);

            let currentDate = new Date(startDate);
            while (currentDate <= endDate) {
                const dayOfWeek = currentDate.getDay();
                if (dayOfWeek !== 0 && dayOfWeek !== 6) {
                    const month = currentDate.getMonth();
                    monthlyData[month]++;
                    total++;
                }
                currentDate.setDate(currentDate.getDate() + 1);
            }
        });

        statistics.value.homeoffice.monthly = monthlyData;
    }

    statistics.value.homeoffice.total = total;
}

const vacationChartData = computed(() => {
    if (selectedMonth.value) {
        const daysInMonth = getDaysInMonth(selectedYear.value, selectedMonth.value);
        const dailyData = Array(daysInMonth).fill(0);
        const labels = Array.from({ length: daysInMonth }, (_, i) => i + 1);

        vacationData.value
            .filter(req => req.status === 'approved')
            .forEach(req => {
                const startDate = new Date(req.start_date);
                const endDate = new Date(req.end_date);

                let currentDate = new Date(startDate);
                while (currentDate <= endDate) {
                    const dayOfWeek = currentDate.getDay();
                    if (dayOfWeek !== 0 && dayOfWeek !== 6 &&
                        currentDate.getFullYear() === selectedYear.value &&
                        currentDate.getMonth() + 1 === selectedMonth.value) {
                        const day = currentDate.getDate() - 1;
                        if (dailyData[day] === 0) {
                            dailyData[day] = 1;
                        }
                    }
                    currentDate.setDate(currentDate.getDate() + 1);
                }
            });

        return {
            labels,
            datasets: [
                {
                    label: 'Urlaubstage',
                    backgroundColor: 'rgba(34, 197, 94, 0.5)',
                    borderColor: 'rgb(34, 197, 94)',
                    data: dailyData
                }
            ]
        };
    } else {
        const labels = months.value.filter(m => m.value !== null).map(m => m.name);
        const monthlyData = Array(12).fill(0);

        vacationData.value
            .filter(req => req.status === 'approved')
            .forEach(req => {
                const startDate = new Date(req.start_date);
                const endDate = new Date(req.end_date);

                let currentDate = new Date(startDate);
                while (currentDate <= endDate) {
                    const dayOfWeek = currentDate.getDay();
                    if (dayOfWeek !== 0 && dayOfWeek !== 6) {
                        if (currentDate.getFullYear() === selectedYear.value) {
                            const month = currentDate.getMonth();
                            monthlyData[month]++;
                        }
                    }
                    currentDate.setDate(currentDate.getDate() + 1);
                }
            });

        return {
            labels,
            datasets: [
                {
                    label: 'Urlaubstage',
                    backgroundColor: 'rgba(34, 197, 94, 0.5)',
                    borderColor: 'rgb(34, 197, 94)',
                    data: monthlyData
                }
            ]
        };
    }
});

function processAbsenceData(events, absenceTypeId) {
    let total = 0;

    const absenceEvents = events.filter(event => event.event_type_id === absenceTypeId);

    if (selectedMonth.value) {
        const daysInMonth = getDaysInMonth(selectedYear.value, selectedMonth.value);
        const dailyData = Array(daysInMonth).fill(0);

        absenceEvents.forEach(event => {
            const startDate = new Date(event.start_date);
            const endDate = new Date(event.end_date);

            let currentDate = new Date(startDate);
            while (currentDate <= endDate) {
                if (currentDate.getFullYear() === selectedYear.value &&
                    currentDate.getMonth() + 1 === selectedMonth.value) {
                    const day = currentDate.getDate() - 1;
                    const dayOfWeek = currentDate.getDay();

                    if (dayOfWeek !== 0 && dayOfWeek !== 6 && dailyData[day] === 0) {
                        dailyData[day] = 1;
                        total++;
                    }
                }
                currentDate.setDate(currentDate.getDate() + 1);
            }
        });

        statistics.value.absence.monthly = dailyData;
    } else {
        const monthlyData = Array(12).fill(0);

        absenceEvents.forEach(event => {
            const startDate = new Date(event.start_date);
            const endDate = new Date(event.end_date);

            let currentDate = new Date(startDate);
            while (currentDate <= endDate) {
                const dayOfWeek = currentDate.getDay();
                if (dayOfWeek !== 0 && dayOfWeek !== 6) {
                    const month = currentDate.getMonth();
                    monthlyData[month]++;
                    total++;
                }
                currentDate.setDate(currentDate.getDate() + 1);
            }
        });

        statistics.value.absence.monthly = monthlyData;
    }

    statistics.value.absence.total = total;
}

function processFieldServiceData(events, fieldServiceTypeId) {
    let total = 0;

    const fieldServiceEvents = events.filter(event => event.event_type_id === fieldServiceTypeId);

    if (selectedMonth.value) {
        const daysInMonth = getDaysInMonth(selectedYear.value, selectedMonth.value);
        const dailyData = Array(daysInMonth).fill(0);

        fieldServiceEvents.forEach(event => {
            const startDate = new Date(event.start_date);
            const endDate = new Date(event.end_date);

            let currentDate = new Date(startDate);
            while (currentDate <= endDate) {
                if (currentDate.getFullYear() === selectedYear.value &&
                    currentDate.getMonth() + 1 === selectedMonth.value) {
                    const day = currentDate.getDate() - 1;
                    const dayOfWeek = currentDate.getDay();

                    if (dayOfWeek !== 0 && dayOfWeek !== 6 && dailyData[day] === 0) {
                        dailyData[day] = 1;
                        total++;
                    }
                }
                currentDate.setDate(currentDate.getDate() + 1);
            }
        });

        statistics.value.fieldService.monthly = dailyData;
    } else {
        const monthlyData = Array(12).fill(0);

        fieldServiceEvents.forEach(event => {
            const startDate = new Date(event.start_date);
            const endDate = new Date(event.end_date);

            let currentDate = new Date(startDate);
            while (currentDate <= endDate) {
                const dayOfWeek = currentDate.getDay();
                if (dayOfWeek !== 0 && dayOfWeek !== 6) {
                    const month = currentDate.getMonth();
                    monthlyData[month]++;
                    total++;
                }
                currentDate.setDate(currentDate.getDate() + 1);
            }
        });

        statistics.value.fieldService.monthly = monthlyData;
    }

    statistics.value.fieldService.total = total;
}

function calculateWorkingDays(startDate, endDate) {
    let count = 0;
    let currentDate = new Date(startDate);

    while (currentDate <= endDate) {
        const dayOfWeek = currentDate.getDay();
        if (dayOfWeek !== 0 && dayOfWeek !== 6) {
            count++;
        }
        currentDate.setDate(currentDate.getDate() + 1);
    }

    return count;
}

function updateData() {
    loadEmployeeData();

    if (userRole.value <= 3 && showTeamOverview.value) {
        loadTeamVacationOverview();
    }
}

watch(selectedEmployee, (newValue) => {
    if (newValue) {
        loadEmployeeData();
    }
});

watch(showTeamOverview, (newValue) => {
    if (newValue && userRole.value <= 3) {
        loadTeamVacationOverview();
    }
});

watch(selectedDepartment, (newValue) => {
    if (isHR.value) {
        loadAvailableEmployees();

        if (showTeamOverview.value) {
            loadTeamVacationOverview();
        }
    }
});

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

        <div class="py-6">
            <div class="max-w-[100rem] mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-4">
                    <!-- Kompakte Filter in einer Reihe -->
                    <div class="mb-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-700 dark:to-gray-600 rounded-xl shadow-sm p-3">
                        <div class="flex flex-wrap items-end gap-3">
                            <!-- Jahr Filter -->
                            <div class="flex-shrink-0 w-32">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <i class="pi pi-calendar mr-1"></i>Jahr
                                </label>
                                <Select
                                    v-model="selectedYear"
                                    :options="years"
                                    placeholder="Jahr"
                                    class="w-full text-sm"
                                    @change="updateData"
                                />
                            </div>

                            <!-- Monat Filter -->
                            <div class="flex-shrink-0 w-36">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <i class="pi pi-calendar-plus mr-1"></i>Monat
                                </label>
                                <Select
                                    v-model="selectedMonth"
                                    :options="months"
                                    optionLabel="name"
                                    optionValue="value"
                                    placeholder="Monat"
                                    class="w-full text-sm"
                                    @change="updateData"
                                />
                            </div>

                            <!-- Abteilungsfilter (nur für HR) -->
                            <div v-if="isHR" class="flex-shrink-0 w-44">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <i class="pi pi-building mr-1"></i>Abteilung
                                </label>
                                <Select
                                    v-model="selectedDepartment"
                                    :options="availableDepartments"
                                    optionLabel="name"
                                    placeholder="Abteilung"
                                    class="w-full text-sm"
                                />
                            </div>

                            <!-- Mitarbeiter Selector (nur für Abteilungsleiter und HR) -->
                            <div v-if="userRole <= 3" class="flex-shrink-0 w-52">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <i class="pi pi-user mr-1"></i>Mitarbeiter
                                </label>
                                <Select
                                    v-model="selectedEmployee"
                                    :options="availableEmployees"
                                    optionLabel="name"
                                    placeholder="Mitarbeiter"
                                    class="w-full text-sm"
                                    @change="loadEmployeeData"
                                />
                            </div>

                            <!-- Toggle für Teamübersicht (nur für Abteilungsleiter und HR) -->
                            <div v-if="userRole <= 3" class="flex-shrink-0 ml-auto">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1 opacity-0">Toggle</label>
                                <div class="flex items-center bg-white dark:bg-gray-700 rounded-lg px-3 py-2 shadow-sm">
                                    <span class="text-xs text-gray-600 dark:text-gray-400 mr-2">Einzeln</span>
                                    <ToggleSwitch v-model="showTeamOverview" />
                                    <span class="text-xs text-gray-600 dark:text-gray-400 ml-2">Team</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Loading Indicator -->
                    <div v-if="loading" class="flex justify-center items-center py-12">
                        <i class="pi pi-spin pi-spinner text-4xl text-blue-500"></i>
                    </div>

                    <div v-else>
                        <!-- Kompaktere Statistics Cards mit weniger Padding -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
                            <!-- Homeoffice Card -->
                            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-md overflow-hidden transform transition-all hover:scale-105 hover:shadow-lg">
                                <div class="p-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-2">
                                                <i class="pi pi-home text-white text-xl"></i>
                                            </div>
                                            <div class="ml-3">
                                                <dt class="text-xs font-medium text-blue-100">Homeoffice</dt>
                                                <dd class="text-2xl font-bold text-white">{{ statistics.homeoffice.total }}</dd>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-black/10 px-3 py-1">
                                    <span class="text-xs font-medium text-white/90">
                                        {{ selectedMonth ? `${getMonthName(selectedMonth)} ${selectedYear}` : selectedYear }}
                                    </span>
                                </div>
                            </div>

                            <!-- Abwesenheit Card -->
                            <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl shadow-md overflow-hidden transform transition-all hover:scale-105 hover:shadow-lg">
                                <div class="p-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-2">
                                                <i class="pi pi-calendar-times text-white text-xl"></i>
                                            </div>
                                            <div class="ml-3">
                                                <dt class="text-xs font-medium text-orange-100">Abwesenheit</dt>
                                                <dd class="text-2xl font-bold text-white">{{ statistics.absence.total }}</dd>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-black/10 px-3 py-1">
                                    <span class="text-xs font-medium text-white/90">{{ selectedYear }}</span>
                                </div>
                            </div>

                            <!-- Außendienst Card -->
                            <div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl shadow-md overflow-hidden transform transition-all hover:scale-105 hover:shadow-lg">
                                <div class="p-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-2">
                                                <i class="pi pi-briefcase text-white text-xl"></i>
                                            </div>
                                            <div class="ml-3">
                                                <dt class="text-xs font-medium text-purple-100">Außendienst</dt>
                                                <dd class="text-2xl font-bold text-white">{{ statistics.fieldService.total }}</dd>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-black/10 px-3 py-1">
                                    <span class="text-xs font-medium text-white/90">{{ selectedYear }}</span>
                                </div>
                            </div>

                            <!-- Urlaub Card -->
                            <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl shadow-md overflow-hidden transform transition-all hover:scale-105 hover:shadow-lg">
                                <div class="p-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-2">
                                                <i class="pi pi-calendar-plus text-white text-xl"></i>
                                            </div>
                                            <div class="ml-3">
                                                <dt class="text-xs font-medium text-emerald-100">Urlaub</dt>
                                                <dd class="text-2xl font-bold text-white">
                                                    {{ statistics.vacation.used }} / {{ statistics.vacation.total }}
                                                </dd>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-black/10 px-3 py-1">
                                    <div class="w-full bg-white/20 rounded-full h-1.5">
                                        <div
                                            class="bg-white h-1.5 rounded-full transition-all duration-300"
                                            :style="{ width: `${(statistics.vacation.used / statistics.vacation.total) * 100}%` }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Kompaktere Charts Section mit reduzierter Höhe -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-3 mb-4">
                            <!-- Homeoffice Chart -->
                            <div class="bg-white dark:bg-gray-700 rounded-xl shadow-md p-3">
                                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">Homeoffice Übersicht</h2>
                                <Chart type="bar" :data="homeofficeChartData" :options="chartOptions" class="h-48" />
                            </div>

                            <!-- Abwesenheit Chart -->
                            <div class="bg-white dark:bg-gray-700 rounded-xl shadow-md p-3">
                                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">Abwesenheit Übersicht</h2>
                                <Chart type="bar" :data="absenceChartData" :options="chartOptions" class="h-48" />
                            </div>

                            <!-- Außendienst Chart -->
                            <div class="bg-white dark:bg-gray-700 rounded-xl shadow-md p-3">
                                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">Außendienst Übersicht</h2>
                                <Chart type="bar" :data="fieldServiceChartData" :options="chartOptions" class="h-48" />
                            </div>

                            <!-- Urlaub Chart -->
                            <div class="bg-white dark:bg-gray-700 rounded-xl shadow-md p-3">
                                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">Urlaub Übersicht</h2>
                                <Chart type="bar" :data="vacationChartData" :options="chartOptions" class="h-48" />
                            </div>
                        </div>

                        <!-- Kompaktere Urlaub Section -->
                        <div class="bg-white dark:bg-gray-700 rounded-xl shadow-md overflow-hidden">
                            <div class="px-4 py-3 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-600 dark:to-gray-700 border-b border-gray-200 dark:border-gray-600">
                                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Urlaubsübersicht {{ selectedYear }}</h2>
                            </div>

                            <!-- Einzelne Urlaubsanträge -->
                            <div v-if="!showTeamOverview || userRole > 3">
                                <DataTable
                                    :value="vacationData"
                                    :paginator="true"
                                    :rows="10"
                                    stripedRows
                                    responsiveLayout="scroll"
                                    class="p-datatable-sm text-sm"
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
                                    <Column field="days" header="Tage" :sortable="true">
                                        <template #body="slotProps">
                                            {{ getActualDays(slotProps.data) }}
                                        </template>
                                    </Column>
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
                                    :rows="15"
                                    stripedRows
                                    responsiveLayout="scroll"
                                    class="p-datatable-sm text-sm"
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
