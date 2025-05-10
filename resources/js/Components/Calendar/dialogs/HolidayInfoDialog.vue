<template>
    <Dialog
        :visible="visible"
        @update:visible="$emit('update:visible', $event)"
        :style="{ width: '450px' }"
        :header="holidayName"
        :modal="true"
        class="holiday-info-dialog"
    >
        <div class="p-4">
            <p class="text-center mb-4">
                An diesem Tag können keine Ereignisse eingetragen werden, da es sich um einen Feiertag handelt.
            </p>
            <div class="flex justify-center">
                <Button label="Schließen" icon="pi pi-times" @click="$emit('update:visible', false)" class="p-button-sm sm:p-button-md" />
            </div>
        </div>
    </Dialog>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';

const props = defineProps({
    visible: {
        type: Boolean,
        required: true
    },
    holidayName: {
        type: String,
        default: 'Feiertag'
    }
});

defineEmits(['update:visible']);
</script>

<style scoped>
.holiday-info-dialog .p-dialog-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--surface-d);
}

.holiday-info-dialog .p-dialog-content {
    padding: 1.5rem;
}

.holiday-info-dialog .p-dialog-footer {
    padding: 1.5rem;
    border-top: 1px solid var(--surface-d);
}
</style>
