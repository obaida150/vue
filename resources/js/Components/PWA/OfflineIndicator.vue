<template>
  <div v-if="!isOnline" class="fixed top-0 left-0 right-0 bg-yellow-500 text-white text-center py-2 z-50">
    <div class="flex items-center justify-center space-x-2">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
      </svg>
      <span>Sie sind offline. Einige Funktionen sind möglicherweise nicht verfügbar.</span>
    </div>
  </div>
</template>

<script>
export default {
  name: 'OfflineIndicator',
  data() {
    return {
      isOnline: navigator.onLine
    };
  },
  mounted() {
    window.addEventListener('online', this.updateOnlineStatus);
    window.addEventListener('offline', this.updateOnlineStatus);
  },
  beforeUnmount() {
    window.removeEventListener('online', this.updateOnlineStatus);
    window.removeEventListener('offline', this.updateOnlineStatus);
  },
  methods: {
    updateOnlineStatus() {
      this.isOnline = navigator.onLine;
    }
  }
};
</script>