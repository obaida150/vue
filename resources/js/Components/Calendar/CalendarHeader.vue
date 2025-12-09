<template>
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div class="flex items-center gap-4">
            <Button icon="pi pi pi-chevron-left" @click="$emit('previous')" class="p-button-rounded p-button-text" />
            <h2 class="text-2xl font-semibold capitalize m-0">
                <span v-if="calendarView === 'day'">{{ formatDate(currentDate) }}</span>
                <span v-else-if="calendarView === 'week'">KW {{ currentWeekNumber }} ({{ formatDateRange(weekStart, weekEnd) }})</span>
                <span v-else-if="calendarView === 'month'">{{ currentMonthName }} {{ currentYear }}</span>
                <span v-else>{{ currentYear }}</span>
            </h2>
            <Button icon="pi pi pi-chevron-right" @click="$emit('next')" class="p-button-rounded p-button-text" />
        </div>
        <div class="flex items-center gap-4 w-full md:w-auto">
            <!-- Toggle-Button nur für role_id 1, 2, 3 - nicht für role_id 4 -->
            <div v-if="shouldShowToggle" class="mr-2">
                <ToggleButton
                    v-model="localShowOnlyOwnEvents"
                    :onLabel="'Nur eigene'"
                    :offLabel="toggleOffLabel"
                    onIcon="pi pi-user"
                    :offIcon="'pi pi-users'"
                    class="p-button-sm"
                    @change="$emit('toggle-event-filter')"
                />
            </div>

            <div class="flex gap-1">
                <Button
                    :class="{ 'p-button-primary': calendarView === 'month', 'p-button-outlined': calendarView !== 'month' }"
                    label="Monat"
                    @click="setView('month')"
                    class="p-button-rounded"
                />
                <Button
                    :class="{ 'p-button-primary': calendarView === 'year', 'p-button-outlined': calendarView !== 'year' }"
                    label="Jahr"
                    @click="setView('year')"
                    class="p-button-rounded"
                />
            </div>

            <div v-if="calendarView === 'year'" class="flex gap-1">
                <Button
                    :class="{ 'p-button-primary': yearLayout === '6x2', 'p-button-outlined': yearLayout !== '6x2' }"
                    label="6x2"
                    @click="$emit('set-layout', '6x2')"
                    class="p-button-rounded"
                />
                <Button
                    :class="{ 'p-button-primary': yearLayout === '4x3', 'p-button-outlined': yearLayout !== '4x3' }"
                    label="4x3"
                    @click="$emit('set-layout', '4x3')"
                    class="p-button-rounded"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import dayjs from 'dayjs';
import Button from 'primevue/button';
import ToggleButton from 'primevue/togglebutton';

const props = defineProps({
    calendarView: {
        type: String,
        required: true
    },
    currentDate: {
        type: Object,
        required: true
    },
    yearLayout: {
        type: String,
        default: '6x2'
    },
    isTeamManager: {
        type: Boolean,
        default: false
    },
    isHrUser: {
        type: Boolean,
        default: false
    },
    showOnlyOwnEvents: {
        type: Boolean,
        default: false
    },
    currentUserRoleId: {
        type: Number,
        default: null
    }
});

const emit = defineEmits(['previous', 'next', 'set-view', 'set-layout', 'toggle-event-filter']);

// Lokale Kopie des showOnlyOwnEvents-Werts
const localShowOnlyOwnEvents = ref(props.showOnlyOwnEvents);

// Aktualisiere die lokale Variable, wenn sich die Prop ändert
watch(() => props.showOnlyOwnEvents, (newValue) => {
    localShowOnlyOwnEvents.value = newValue;
});

const shouldShowToggle = computed(() => {
    // Debugging
    // console.log('CalendarHeader - currentUserRoleId:', props.currentUserRoleId);
    // console.log('CalendarHeader - isTeamManager:', props.isTeamManager);
    // console.log('CalendarHeader - isHrUser:', props.isHrUser);

    // Wenn role_id explizit 4 ist, niemals Toggle zeigen
    if (props.currentUserRoleId === 4) {
        // console.log('CalendarHeader - Toggle versteckt (role_id 4)');
        return false;
    }

    // Für Admin (1), HR (2), Abteilungsleiter (3) den Toggle zeigen
    if (props.currentUserRoleId === 1 || props.currentUserRoleId === 2 || props.currentUserRoleId === 3) {
        // console.log('CalendarHeader - Toggle angezeigt (role_id 1, 2, oder 3)');
        return true;
    }

    // Fallback: Wenn role_id noch null ist, prüfe die anderen Flags
    const show = props.isHrUser || props.isTeamManager;
    // console.log('CalendarHeader - Toggle Fallback:', show);
    return show;
});

const toggleOffLabel = computed(() => {
    return props.isHrUser ? 'Alle User' : 'Team';
});

// Computed Properties
const currentYear = computed(() => props.currentDate.year());
const currentMonthName = computed(() => props.currentDate.format('MMMM'));
const currentWeekNumber = computed(() => props.currentDate.week());

// Wochenstart und -ende berechnen
const weekStart = computed(() => {
    const day = props.currentDate.day();
    const diff = day === 0 ? 6 : day - 1;
    return props.currentDate.subtract(diff, 'day');
});

const weekEnd = computed(() => {
    return weekStart.value.add(6, 'day');
});

// Formatierungsfunktionen
const formatDate = (date) => {
    return date.format('dddd, DD. MMMM YYYY');
};

const formatDateRange = (start, end) => {
    return `${start.format('DD.MM.')} - ${end.format('DD.MM.YYYY')}`;
};

// Ansicht ändern
const setView = (view) => {
    emit('set-view', view);
};
</script>
