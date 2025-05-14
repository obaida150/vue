<template>
    <Dialog
        :visible="localVisible"
        @update:visible="updateVisible"
        :style="{ width: '450px' }"
        header="Urlaubsdetails"
        :modal="true"
        :closable="true"
        class="p-fluid"
    >
        <div v-if="vacation">
            <div class="mb-4">
                <h3 class="text-xl font-bold">{{ vacation.title }}</h3>
                <Tag
                    v-if="vacation.status"
                    :severity="getStatusSeverity(vacation.status)"
                    :value="getStatusLabel(vacation.status)"
                    class="mt-2"
                />
            </div>

            <div class="grid mb-4">
                <div class="col-4 font-bold">Zeitraum:</div>
                <div class="col-8">
                    {{ formatDate(vacation.startDate) }} - {{ formatDate(vacation.endDate) }}
                </div>
            </div>

            <div v-if="vacation.employee_name" class="grid mb-4">
                <div class="col-4 font-bold">Mitarbeiter:</div>
                <div class="col-8">{{ vacation.employee_name }}</div>
            </div>

            <div v-if="vacation.description" class="mb-4">
                <div class="font-bold mb-2">Beschreibung:</div>
                <div class="p-2 bg-gray-100 rounded">{{ vacation.description }}</div>
            </div>
        </div>

        <template #footer>
            <Button
                label="Schließen"
                icon="pi pi-times"
                class="p-button-text"
                @click="onClose"
            />
            <Button
                label="Zur Urlaubsverwaltung"
                icon="pi pi-external-link"
                class="p-button-primary"
                @click="onNavigate"
            />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const props = defineProps({
    visible: {
        type: Boolean,
        required: true
    },
    modelValue: {
        type: Boolean,
        default: false
    },
    vacation: {
        type: Object,
        default: null
    },
    getStatusLabel: {
        type: Function,
        required: true
    },
    getStatusSeverity: {
        type: Function,
        required: true
    },
    formatDate: {
        type: Function,
        required: true
    }
});

const emit = defineEmits(['update:visible', 'update:modelValue', 'close', 'navigate']);

// Lokale Variable für die Sichtbarkeit
const localVisible = ref(props.visible);

// Aktualisiere die lokale Variable, wenn sich die Prop ändert
watch(() => props.visible, (newValue) => {
    localVisible.value = newValue;
});

// Aktualisiere die Prop, wenn sich die lokale Variable ändert
const updateVisible = (value) => {
    localVisible.value = value;
    emit('update:visible', value);
    if (!value) {
        emit('close');
    }
};

const onClose = () => {
    updateVisible(false);
};

const onNavigate = () => {
    emit('navigate');
};
</script>
