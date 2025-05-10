<template>
    <Dialog
        :visible="visible"
        @update:visible="$emit('update:visible', $event)"
        :style="{ width: '450px' }"
        header="Ereignis löschen"
        :modal="true"
        :closable="false"
        class="delete-confirmation-dialog"
    >
        <div class="flex flex-col gap-4">
            <div class="flex items-start gap-3">
                <i class="pi pi-exclamation-triangle text-yellow-500 text-2xl mt-1"></i>
                <div>
                    <h3 class="text-lg font-semibold mb-2">Sind Sie sicher?</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Möchten Sie dieses Ereignis wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.
                    </p>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button
                    label="Abbrechen"
                    icon="pi pi-times"
                    @click="$emit('cancel')"
                    class="p-button-text p-button-sm sm:p-button-md"
                />
                <Button
                    label="Löschen"
                    icon="pi pi-trash"
                    @click="$emit('delete')"
                    :loading="deleting"
                    class="p-button-danger p-button-sm sm:p-button-md"
                />
            </div>
        </template>
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
    deleting: {
        type: Boolean,
        required: true
    }
});

defineEmits(['update:visible', 'cancel', 'delete']);
</script>

<style scoped>
.delete-confirmation-dialog .p-dialog-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--surface-d);
}

.delete-confirmation-dialog .p-dialog-content {
    padding: 1.5rem;
}

.delete-confirmation-dialog .p-dialog-footer {
    padding: 1.5rem;
    border-top: 1px solid var(--surface-d);
}
</style>
