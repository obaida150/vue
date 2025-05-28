<template>
  <div v-if="show" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-[500px] shadow-lg rounded-md bg-white">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold text-gray-900">
          Wiederkehrende Reservierung
        </h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
      
      <div class="mb-4 p-3 bg-blue-50 rounded-lg">
        <div class="flex items-center">
          <div class="w-4 h-4 bg-green-500 rounded-full mr-3"></div>
          <div>
            <p class="font-medium text-gray-900">{{ selectedSpot?.name || selectedSpot?.identifier }}</p>
            <p class="text-sm text-gray-600">{{ selectedSpot?.parking_location?.name }}</p>
            <p class="text-xs text-gray-500">{{ getTypeLabel(selectedSpot?.type) }}</p>
          </div>
        </div>
      </div>
      
      <form @submit.prevent="createRecurringReservation">
        <!-- Start Date -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Startdatum</label>
          <input v-model="form.start_date" 
                 type="date" 
                 :min="today"
                 required
                 class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        </div>

        <!-- Recurrence Pattern -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Wiederholung</label>
          <select v-model="form.recurrence_type" 
                  required
                  class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            <option value="daily">Täglich</option>
            <option value="weekly">Wöchentlich</option>
            <option value="monthly">Monatlich</option>
          </select>
        </div>

        <!-- Recurrence Interval -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">
            Alle {{ form.recurrence_type === 'daily' ? 'Tage' : form.recurrence_type === 'weekly' ? 'Wochen' : 'Monate' }}
          </label>
          <input v-model.number="form.recurrence_interval" 
                 type="number" 
                 min="1" 
                 max="12"
                 required
                 class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        </div>

        <!-- End Condition -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Endet</label>
          <div class="space-y-2">
            <label class="flex items-center">
              <input v-model="form.end_type" 
                     type="radio" 
                     value="date"
                     class="mr-2">
              <span>Am Datum</span>
            </label>
            <input v-if="form.end_type === 'date'"
                   v-model="form.end_date" 
                   type="date" 
                   :min="form.start_date"
                   class="ml-6 shadow appearance-none border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            
            <label class="flex items-center">
              <input v-model="form.end_type" 
                     type="radio" 
                     value="count"
                     class="mr-2">
              <span>Nach Anzahl Wiederholungen</span>
            </label>
            <input v-if="form.end_type === 'count'"
                   v-model.number="form.end_count" 
                   type="number" 
                   min="1" 
                   max="365"
                   class="ml-6 shadow appearance-none border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
          </div>
        </div>

        <!-- Time -->
        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-gray-700 text-sm font-bold mb-2">Von</label>
            <input v-model="form.start_time" 
                   type="time" 
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
          </div>
          <div>
            <label class="block text-gray-700 text-sm font-bold mb-2">Bis</label>
            <input v-model="form.end_time" 
                   type="time" 
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
          </div>
        </div>

        <!-- Vehicle Info -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Fahrzeug-Info (optional)</label>
          <input v-model="form.vehicle_info" 
                 type="text" 
                 placeholder="z.B. BMW X3, Kennzeichen ABC-123"
                 class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        </div>

        <!-- Notes -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Notizen (optional)</label>
          <textarea v-model="form.notes" 
                    rows="3"
                    placeholder="Zusätzliche Informationen..."
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
        </div>

        <!-- Preview -->
        <div v-if="previewDates.length > 0" class="mb-4 p-3 bg-gray-50 rounded">
          <h4 class="text-sm font-medium text-gray-900 mb-2">Vorschau (erste 5 Termine):</h4>
          <div class="space-y-1">
            <div v-for="date in previewDates.slice(0, 5)" :key="date" class="text-sm text-gray-600">
              {{ formatPreviewDate(date) }}
            </div>
            <div v-if="previewDates.length > 5" class="text-sm text-gray-500 italic">
              ... und {{ previewDates.length - 5 }} weitere Termine
            </div>
          </div>
        </div>

        <div class="flex justify-end space-x-2">
          <button @click="$emit('close')" 
                  type="button"
                  class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
            Abbrechen
          </button>
          <button type="submit"
                  :disabled="previewDates.length === 0"
                  class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50">
            {{ previewDates.length }} Reservierungen erstellen
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  show: Boolean,
  selectedSpot: Object
})

const emit = defineEmits(['close', 'create-recurring'])

const today = computed(() => {
  return new Date().toISOString().split('T')[0]
})

const form = ref({
  start_date: today.value,
  recurrence_type: 'weekly',
  recurrence_interval: 1,
  end_type: 'count',
  end_date: '',
  end_count: 4,
  start_time: '06:00',
  end_time: '18:00',
  vehicle_info: '',
  notes: ''
})

const previewDates = computed(() => {
  if (!form.value.start_date) return []
  
  const dates = []
  const startDate = new Date(form.value.start_date)
  let currentDate = new Date(startDate)
  
  const maxDates = form.value.end_type === 'count' ? form.value.end_count : 100
  const endDate = form.value.end_type === 'date' ? new Date(form.value.end_date) : null
  
  for (let i = 0; i < maxDates; i++) {
    if (endDate && currentDate > endDate) break
    
    dates.push(new Date(currentDate))
    
    // Calculate next date based on recurrence
    switch (form.value.recurrence_type) {
      case 'daily':
        currentDate.setDate(currentDate.getDate() + form.value.recurrence_interval)
        break
      case 'weekly':
        currentDate.setDate(currentDate.getDate() + (7 * form.value.recurrence_interval))
        break
      case 'monthly':
        currentDate.setMonth(currentDate.getMonth() + form.value.recurrence_interval)
        break
    }
  }
  
  return dates
})

const getTypeLabel = (type) => {
  const labels = {
    regular: 'Standard',
    lift_top: 'Hebebühne Oben',
    lift_bottom: 'Hebebühne Unten',
    external: 'Extern'
  }
  return labels[type] || type
}

const formatPreviewDate = (date) => {
  return date.toLocaleDateString('de-DE', {
    weekday: 'short',
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const createRecurringReservation = () => {
  const reservations = previewDates.value.map(date => ({
    parking_spot_id: props.selectedSpot.id,
    reservation_date: date.toISOString().split('T')[0],
    start_time: form.value.start_time,
    end_time: form.value.end_time,
    vehicle_info: form.value.vehicle_info,
    notes: form.value.notes
  }))
  
  emit('create-recurring', reservations)
}

// Reset form when dialog opens
watch(() => props.show, (newShow) => {
  if (newShow) {
    form.value = {
      start_date: today.value,
      recurrence_type: 'weekly',
      recurrence_interval: 1,
      end_type: 'count',
      end_date: '',
      end_count: 4,
      start_time: '06:00',
      end_time: '18:00',
      vehicle_info: '',
      notes: ''
    }
  }
})
</script>
