<template>
    <Teleport to="body">
        <Transition name="modal-fade">
            <div v-if="isOpen" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <Transition name="modal-scale">
                    <div v-if="isOpen" class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-5xl max-h-[85vh] flex flex-col">
                        <!-- Header -->
                        <div class="px-8 py-6 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-t-2xl">
                            <div>
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Sanitäranlagen</h2>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Live-Verfügbarkeit</p>
                            </div>
                            <button @click="close" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors p-2 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 overflow-y-auto">
                            <div v-if="!roomStatus" class="flex items-center justify-center h-64">
                                <div class="text-center">
                                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
                                    <p class="text-slate-600 dark:text-slate-400">Wird aktualisiert...</p>
                                </div>
                            </div>

                            <div v-else class="px-8 py-8">
                                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                                    <!-- KS14 - 1. OG -->
                                    <div class="bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-700 rounded-xl p-4 border border-slate-200 dark:border-slate-600">
                                        <h3 class="text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wide mb-3">KS14 • 1. OG</h3>
                                        <div class="space-y-2">
                                            <RoomStatus label="Herren Vorne." :color="roomStatus.results14[0]?.color" :state="roomStatus.results14[0]?.state" />
                                            <RoomStatus label="Herren Hinten." :color="roomStatus.results14[5]?.color" :state="roomStatus.results14[5]?.state" />
                                            <RoomStatus label="Damen A" :color="roomStatus.results14[1]?.color" :state="roomStatus.results14[1]?.state" />
                                            <RoomStatus label="Damen B" :color="roomStatus.results14[2]?.color" :state="roomStatus.results14[2]?.state" />
                                        </div>
                                    </div>

                                    <!-- KS14 - 2. OG -->
                                    <div class="bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-700 rounded-xl p-4 border border-slate-200 dark:border-slate-600">
                                        <h3 class="text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wide mb-3">KS14 • 2. OG</h3>
                                        <div class="space-y-2">
                                            <RoomStatus label="Damen" :color="roomStatus.results14[3]?.color" :state="roomStatus.results14[3]?.state" />
                                            <RoomStatus label="Herren" :color="roomStatus.results14[4]?.color" :state="roomStatus.results14[4]?.state" />
                                        </div>
                                    </div>

                                    <!-- KS23 - Empfang -->
                                    <div class="bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-700 rounded-xl p-4 border border-slate-200 dark:border-slate-600">
                                        <h3 class="text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wide mb-3">KS23 • Empf.</h3>
                                        <div class="space-y-2">
                                            <RoomStatus label="Damen" :color="roomStatus.results23[0]?.color" :state="roomStatus.results23[0]?.state" />
                                            <RoomStatus label="Herren" :color="roomStatus.results23[1]?.color" :state="roomStatus.results23[1]?.state" />
                                        </div>
                                    </div>

                                    <!-- KS23 - 3. OG -->
                                    <div class="bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-700 rounded-xl p-4 border border-slate-200 dark:border-slate-600">
                                        <h3 class="text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wide mb-3">KS23 • 3. OG</h3>
                                        <div class="space-y-2">
                                            <RoomStatus label="Damen" :color="roomStatus.results23[2]?.color" :state="roomStatus.results23[2]?.state" />
                                            <RoomStatus label="Herren" :color="roomStatus.results23[3]?.color" :state="roomStatus.results23[3]?.state" />
                                        </div>
                                    </div>

                                    <!-- KS23 - Lounge -->
                                    <div class="bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-700 rounded-xl p-4 border border-slate-200 dark:border-slate-600">
                                        <h3 class="text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wide mb-3">KS23 • Lounge</h3>
                                        <div class="space-y-2">
                                            <RoomStatus label="Damen" :color="roomStatus.results23[4]?.color" :state="roomStatus.results23[4]?.state" />
                                            <RoomStatus label="Herren" :color="roomStatus.results23[5]?.color" :state="roomStatus.results23[5]?.state" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Legend -->
                                <div class="flex items-center justify-center gap-6 mt-8 pt-6 border-t border-slate-200 dark:border-slate-700">
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 rounded-full bg-emerald-500 shadow-md"></div>
                                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Frei</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 rounded-full bg-red-500 shadow-md"></div>
                                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Belegt</span>
                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 ml-auto">
                                        Aktualisiert alle 2 Sekunden
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script>
import { defineComponent } from 'vue'
import RoomStatus from './RoomStatus.vue'

export default defineComponent({
    name: 'RoomStatusModal',
    components: {
        RoomStatus,
    },
    data() {
        return {
            isOpen: false,
            roomStatus: null,
            pollInterval: null,
            failureCount: 0,
            MAX_FAILURES: 5,
        }
    },
    methods: {
        open() {
            this.isOpen = true
            this.failureCount = 0
            this.fetchStatus()
            this.startPolling()
        },
        close() {
            this.isOpen = false
            this.stopPolling()
        },
        startPolling() {
            this.pollInterval = setInterval(() => {
                if (this.isOpen) {
                    this.fetchStatus()
                }
            }, 2000)
        },
        stopPolling() {
            if (this.pollInterval) {
                clearInterval(this.pollInterval)
                this.pollInterval = null
            }
        },
        async fetchStatus() {
            try {
                const response = await fetch('/api/room-status', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    cache: 'no-store'
                })

                if (!response.ok) {
                    this.failureCount++
                    if (this.failureCount >= this.MAX_FAILURES) {
                        this.stopPolling()
                    }
                    return
                }

                this.failureCount = 0
                const data = await response.json()
                this.roomStatus = data
            } catch (error) {
                console.error('[Room Status Modal] Fetch Error:', error)
                this.failureCount++
                if (this.failureCount >= this.MAX_FAILURES) {
                    this.stopPolling()
                }
            }
        }
    },
    beforeUnmount() {
        this.stopPolling()
    }
})
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

.modal-scale-enter-active,
.modal-scale-leave-active {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal-scale-enter-from,
.modal-scale-leave-to {
    transform: scale(0.95) translateY(-20px);
}
</style>
