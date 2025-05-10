<template>
    <Dialog
        :visible="visible"
        @update:visible="$emit('update:visible', $event)"
        :style="{ width: '90vw', maxWidth: '500px' }"
        header="Ereignis hinzufügen"
        :modal="true"
        class="event-dialog"
    >
        <div class="flex flex-col gap-3 sm:gap-4">
            <div>
                <label for="event-title" class="block mb-1 sm:mb-2 font-medium">Titel</label>
                <InputText id="event-title" v-model="event.title" class="w-full" />
            </div>

            <div>
                <label for="event-type" class="block mb-1 sm:mb-2 font-medium">Ereignistyp</label>
                <Select
                    id="event-type"
                    v-model="event.type"
                    :options="eventTypes"
                    optionLabel="name"
                    placeholder="Typ auswählen"
                    class="w-full"
                />
            </div>

            <div>
                <label class="block mb-1 sm:mb-2 font-medium">Zeitraum</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <DatePicker
                        v-model="event.startDate"
                        dateFormat="dd.mm.yy"
                        placeholder="Startdatum"
                        class="w-full"
                        :disabledDates="disabledDates"
                        :locale="locale"
                    />
                    <DatePicker
                        v-model="event.endDate"
                        dateFormat="dd.mm.yy"
                        placeholder="Enddatum"
                        class="w-full"
                        :disabledDates="disabledDates"
                        :locale="locale"
                    />
                </div>
            </div>

            <div>
                <div class="flex items-center gap-2 mb-1 sm:mb-2">
                    <Checkbox v-model="event.isAllDay" :binary="true" inputId="is-all-day" />
                    <label for="is-all-day" class="font-medium">Ganztägig</label>
                </div>
            </div>

            <div>
                <label for="event-description" class="block mb-1 sm:mb-2 font-medium">Beschreibung</label>
                <Textarea
                    id="event-description"
                    v-model="event.description"
                    rows="3"
                    class="w-full"
                />
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Abbrechen" icon="pi pi-times" @click="$emit('close')" class="p-button-text p-button-sm sm:p-button-md" />
                <Button label="Speichern" icon="pi pi-check" @click="$emit('save')" :loading="saving" autofocus class="p-button-sm sm:p-button-md" />
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';
import DatePicker from 'primevue/datepicker';

const props = defineProps({
    visible: {
        type: Boolean,
        required: true
    },
    event: {
        type: Object,
        required: true
    },
    eventTypes: {
        type: Array,
        required: true
    },
    disabledDates: {
        type: Array,
        required: true
    },
    locale: {
        type: Object,
        required: true
    },
    saving: {
        type: Boolean,
        required: true
    }
});

defineEmits(['update:visible', 'close', 'save']);
</script>

<style scoped>
.event-dialog .p-dialog-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--surface-d);
}

.event-dialog .p-dialog-content {
    padding: 1.5rem;
}

.event-dialog .p-dialog-footer {
    padding: 1.5rem;
    border-top: 1px solid var(--surface-d);
}
</style>
