<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import Button from 'primevue/button'
import InputGroup from 'primevue/inputgroup'
import InputGroupAddon from 'primevue/inputgroupaddon'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'

defineProps({
    status: String,
})

const form = useForm({
    email: '',
})

const submit = () => {
    form.post(route('password.email'))
}
</script>

<template>
    <Head title="Passwort zurücksetzen" />

    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center px-4 py-12">
        <!-- Decorative elements -->
        <div class="absolute top-0 right-0 w-96 h-96 bg-blue-200 rounded-full opacity-40 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-blue-300 rounded-full opacity-30 blur-3xl"></div>

        <div class="relative w-full max-w-md">
            <!-- Card -->
            <div class="bg-white border border-slate-200 rounded-2xl shadow-xl p-8">
                <!-- Logo and Title -->
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-14 h-14 bg-blue-100 rounded-full mb-4">
                        <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-slate-900 mb-2">Passwort zurücksetzen</h1>
                    <p class="text-slate-600">Geben Sie Ihre E-Mail-Adresse ein</p>
                </div>

                <!-- Description -->
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-sm text-slate-700">
                        Haben Sie Ihr Passwort vergessen? Kein Problem. Geben Sie einfach Ihre E-Mail-Adresse ein und wir senden Ihnen einen Link zum Zurücksetzen des Passworts.
                    </p>
                </div>

                <!-- Status Message -->
                <Message v-if="status" severity="success" :text="status" class="mb-6 w-full" />

                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-6">
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
                        <small v-if="form.errors.email" class="text-red-600 block mt-2">{{ form.errors.email }}</small>
                    </div>

                    <!-- Submit Button -->
                    <Button
                        type="submit"
                        label="Reset Link senden"
                        class="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 border-0 shadow-lg font-semibold py-3"
                        :loading="form.processing"
                        :disabled="form.processing"
                    />
                </form>

                <!-- Back Link -->
                <div class="mt-6 pt-6 border-t border-slate-200 text-center">
                    <p class="text-sm text-slate-600">
                        Erinnern Sie sich an Ihr Passwort?
                        <Link :href="route('login')" class="text-blue-600 hover:text-blue-700 font-semibold transition-colors">
                            Zum Login
                        </Link>
                    </p>
                </div>
            </div>

            <!-- Footer Text -->
            <p class="text-center text-slate-600 text-sm mt-6">
                Sichere Passwort-Wiederherstellung
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
