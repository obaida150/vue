<script setup>
import { ref, reactive, nextTick } from 'vue'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import InputGroup from 'primevue/inputgroup'
import InputGroupAddon from 'primevue/inputgroupaddon'
import Message from 'primevue/message'
import axios from 'axios'

const emit = defineEmits(['confirmed'])

defineProps({
    title: {
        type: String,
        default: 'Passwort bestätigen',
    },
    content: {
        type: String,
        default: 'Bitte bestätigen Sie Ihr Passwort zur Sicherheit.',
    },
    button: {
        type: String,
        default: 'Bestätigen',
    },
})

const confirmingPassword = ref(false)

const form = reactive({
    password: '',
    error: '',
    processing: false,
})

const passwordInput = ref(null)

const startConfirmingPassword = () => {
    axios.get(route('password.confirmation')).then(response => {
        if (response.data.confirmed) {
            emit('confirmed')
        } else {
            confirmingPassword.value = true
            setTimeout(() => passwordInput.value?.$el.focus?.(), 250)
        }
    })
}

const confirmPassword = () => {
    form.processing = true

    axios
        .post(route('password.confirm'), {
            password: form.password,
        })
        .then(() => {
            form.processing = false
            closeModal()
            nextTick().then(() => emit('confirmed'))
        })
        .catch(error => {
            form.processing = false
            form.error = error.response?.data?.errors?.password?.[0] || 'Ein Fehler ist aufgetreten'
            passwordInput.value?.$el.focus?.()
        })
}

const closeModal = () => {
    confirmingPassword.value = false
    form.password = ''
    form.error = ''
}
</script>

<template>
    <span>
        <span @click="startConfirmingPassword">
            <slot />
        </span>

        <Dialog
            v-model:visible="confirmingPassword"
            :header="title"
            modal
            @hide="closeModal"
            :style="{ width: '100%', maxWidth: '450px' }"
            class="modern-dialog"
        >
            <!-- Content -->
            <div class="space-y-4">
                <p class="text-slate-600 text-sm leading-relaxed">
                    {{ content }}
                </p>

                <!-- Error Message -->
                <Message v-if="form.error" severity="error" :text="form.error" class="w-full" />

                <!-- Password Field -->
                <div class="space-y-2">
                    <label for="confirm-password" class="block text-sm font-semibold text-slate-700">
                        Passwort
                    </label>
                    <InputGroup>
                        <InputGroupAddon class="bg-slate-100 border-slate-300">
                            <svg class="w-5 h-5 text-slate-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                            </svg>
                        </InputGroupAddon>
                        <Password
                            id="confirm-password"
                            ref="passwordInput"
                            v-model="form.password"
                            placeholder="Ihr Passwort eingeben"
                            toggle-mask
                            class="bg-white border-slate-300 text-slate-900 w-full"
                            input-class="bg-white border-slate-300 text-slate-900 placeholder:text-slate-400 w-full"
                            autocomplete="current-password"
                            @keyup.enter="confirmPassword"
                        />
                    </InputGroup>
                </div>
            </div>

            <!-- Footer -->
            <template #footer>
                <div class="flex justify-end gap-3 pt-4">
                    <Button
                        label="Abbrechen"
                        severity="secondary"
                        @click="closeModal"
                        class="px-6"
                    />
                    <Button
                        :label="button"
                        :loading="form.processing"
                        :disabled="form.processing || !form.password"
                        @click="confirmPassword"
                        class="px-6 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 border-0"
                    />
                </div>
            </template>
        </Dialog>
    </span>
</template>

<style scoped>
:deep(.p-dialog) {
    border-radius: 1rem;
    border: 1px solid rgb(226 232 240);
    background-color: white;
}

:deep(.p-dialog .p-dialog-header) {
    background-color: rgb(248 250 252);
    border-bottom: 1px solid rgb(226 232 240);
    padding: 1.5rem;
}

:deep(.p-dialog .p-dialog-header .p-dialog-title) {
    font-size: 1.25rem;
    font-weight: 700;
    color: rgb(15 23 42);
}

:deep(.p-dialog .p-dialog-content) {
    padding: 1.5rem;
}

:deep(.p-dialog .p-dialog-footer) {
    background-color: transparent;
    border-top: 1px solid rgb(226 232 240);
    padding: 1rem 1.5rem;
}

:deep(.p-password .p-inputtext) {
    background-color: white;
    border-color: rgb(203 213 225);
    color: rgb(15 23 42);
    font-size: 0.875rem;
}

:deep(.p-password .p-inputtext:focus) {
    border-color: rgb(59 130 246);
    box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
}

:deep(.p-message) {
    background-color: rgb(254 242 242);
    border: 1px solid rgb(254 226 226);
    border-radius: 0.75rem;
}

:deep(.p-message.p-message-error .p-message-icon) {
    color: rgb(239 68 68);
}

:deep(.p-message.p-message-error .p-message-text) {
    color: rgb(239 68 68);
}
</style>
