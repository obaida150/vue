<template>
  <div v-if="showInstallPrompt" class="fixed bottom-4 left-4 right-4 bg-blue-600 text-white p-4 rounded-lg shadow-lg z-50 md:left-auto md:right-4 md:w-96">
    <div class="flex items-start justify-between">
      <div class="flex-1">
        <h3 class="font-semibold mb-1">App installieren</h3>
        <p class="text-sm opacity-90">Installieren Sie das Mitarbeiterportal für schnelleren Zugriff und bessere Performance.</p>
      </div>
      <button @click="dismiss" class="ml-4 text-white/70 hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    </div>
    <div class="mt-3 flex space-x-2">
      <button @click="install" class="bg-white text-blue-600 px-4 py-2 rounded font-medium text-sm hover:bg-gray-100 transition-colors">
        Installieren
      </button>
      <button @click="dismiss" class="border border-white/30 px-4 py-2 rounded text-sm hover:bg-white/10 transition-colors">
        Später
      </button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'InstallPrompt',
  data() {
    return {
      showInstallPrompt: false,
      deferredPrompt: null
    };
  },
  mounted() {
    window.addEventListener('beforeinstallprompt', this.handleInstallPrompt);
    window.addEventListener('appinstalled', this.handleAppInstalled);
  },
  beforeUnmount() {
    window.removeEventListener('beforeinstallprompt', this.handleInstallPrompt);
    window.removeEventListener('appinstalled', this.handleAppInstalled);
  },
  methods: {
    handleInstallPrompt(e) {
      e.preventDefault();
      this.deferredPrompt = e;
      this.showInstallPrompt = true;
    },
    handleAppInstalled() {
      this.showInstallPrompt = false;
      this.deferredPrompt = null;
      console.log('PWA wurde installiert');
    },
    async install() {
      if (this.deferredPrompt) {
        this.deferredPrompt.prompt();
        const { outcome } = await this.deferredPrompt.userChoice;
        console.log(`User response to the install prompt: ${outcome}`);
        this.deferredPrompt = null;
        this.showInstallPrompt = false;
      }
    },
    dismiss() {
      this.showInstallPrompt = false;
      this.deferredPrompt = null;
    }
  }
};
</script>