<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import InputGroup from 'primevue/inputgroup'
import InputGroupAddon from 'primevue/inputgroupaddon'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Checkbox from 'primevue/checkbox'
import Button from 'primevue/button'
import Message from 'primevue/message'

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const passwordRef = ref(null)

const submit = () => {
    form
        .transform(data => ({
            ...data,
            remember: form.remember ? 'on' : '',
        }))
        .post(route('login'), {
            onFinish: () => form.reset('password'),
        })
}

defineProps({
    canResetPassword: Boolean,
    status: String,
})
</script>

<template>
    <Head title="Login" />

    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center px-4 py-12">
        <!-- Decorative elements -->
        <div class="absolute top-0 right-0 w-96 h-96 bg-blue-200 rounded-full opacity-40 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-blue-300 rounded-full opacity-30 blur-3xl"></div>

        <div class="relative w-full max-w-md">
            <!-- Card -->
            <div class="bg-white border border-slate-200 rounded-2xl shadow-xl p-8">
                <!-- Logo and Title -->
                <div class="text-center mb-8">
                    <img src="/images/logo.png" alt="Dittmeier Mitarbeiterportal" style="margin-top: -5em;border-radius:25px; box-shadow: 1px 2px 41px 2px rgba(4,111,71,0.2); margin-bottom: 2em; ">
                    <h1 class="text-3xl font-bold text-slate-900 mb-2">Willkommen bei</h1>
                    <p class="text-slate-600">Mitarbeiterportal 2.0</p>
                </div>

                <!-- Status Message -->
                <Message v-if="status" severity="success" :text="status" class="mb-6 w-full" />

                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">Email-Adresse</label>
                        <InputGroup>
                            <InputGroupAddon class="bg-slate-100 border-slate-300">
                                <svg class="w-5 h-5 text-slate-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                                </svg>
                            </InputGroupAddon>
                            <InputText
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="name@example.com"
                                class="bg-white border-slate-300 text-slate-900 placeholder:text-slate-400"
                                required
                                autofocus
                                autocomplete="username"
                            />
                        </InputGroup>
                        <small v-if="form.errors.email" class="text-red-600 block mt-1">{{ form.errors.email }}</small>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">Passwort</label>
                        <InputGroup>
                            <InputGroupAddon class="bg-slate-100 border-slate-300">
                                <svg class="w-5 h-5 text-slate-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                </svg>
                            </InputGroupAddon>
                            <Password
                                id="password"
                                ref="passwordRef"
                                v-model="form.password"
                                placeholder="Ihr Passwort"
                                toggle-mask
                                class="bg-white border-slate-300 text-slate-900"
                                input-class="bg-white border-slate-300 text-slate-900 placeholder:text-slate-400"
                                required
                                autocomplete="current-password"
                            />
                        </InputGroup>
                        <small v-if="form.errors.password" class="text-red-600 block mt-1">{{ form.errors.password }}</small>
                    </div>

                    <!-- Remember Checkbox -->
                    <div class="flex items-center gap-2">
                        <Checkbox v-model="form.remember" input-id="remember" binary />
                        <label for="remember" class="text-sm text-slate-600 cursor-pointer">Anmeldedaten speichern</label>
                    </div>

                    <!-- Submit Button -->
                    <Button
                        type="submit"
                        label="Login"
                        class="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 border-0 shadow-lg font-semibold py-3"
                        :loading="form.processing"
                    />
                </form>

                <!-- Links -->
                <div class="mt-6 pt-6 border-t border-slate-200">
                    <div v-if="canResetPassword" class="text-center">
                        <Link :href="route('password.request')" class="text-sm text-blue-600 hover:text-blue-700 transition-colors">
                            Passwort vergessen?
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Footer Text -->
            <p class="text-center text-slate-600 text-sm mt-6">
                Sichere Anmeldung für Mitarbeiter
            </p>
        </div>
    </div>
</template>

<style scoped>
:deep(.p-inputgroup .p-inputtext) {
    background-color: white;
    border-color: rgb(203 213 225);
    color: rgb(15 23 42);
    padding: 0.75rem;
}

:deep(.p-inputgroup .p-inputtext:focus) {
    border-color: rgb(59 130 246);
    box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
}

:deep(.p-password .p-inputtext) {
    background-color: white;
    border-color: rgb(203 213 225);
    color: rgb(15 23 42);
}

:deep(.p-password .p-inputtext:focus) {
    border-color: rgb(59 130 246);
    box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
}

:deep(.p-password .p-icon-password-hide,
.p-password .p-icon-password-show) {
    color: rgb(100 116 139);
}

:deep(.p-checkbox .p-checkbox-box) {
    background-color: white;
    border-color: rgb(203 213 225);
}

:deep(.p-checkbox .p-checkbox-box.p-highlight) {
    background-color: rgb(59 130 246);
    border-color: rgb(59 130 246);
}

:deep(.p-message) {
    background-color: rgb(240 253 250);
    border: 1px solid rgb(134 239 172);
    border-radius: 0.75rem;
}

:deep(.p-message.p-message-success .p-message-icon) {
    color: rgb(34 197 94);
}

:deep(.p-message.p-message-success .p-message-text) {
    color: rgb(34 197 94);
}
</style>
