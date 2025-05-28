<template>
  <div class="relative bg-gray-100 rounded-lg overflow-hidden">
    <div class="p-4 bg-white border-b">
      <h3 class="text-lg font-semibold">Parkplatz-Positionen bearbeiten</h3>
      <p class="text-sm text-gray-600">Klicken Sie auf das Bild, um Parkplätze zu positionieren</p>
    </div>
    
    <!-- Image Container -->
    <div class="relative" ref="imageContainer">
      <img 
        ref="parkingImage"
        :src="imageUrl" 
        alt="Parkplatz Übersicht"
        class="w-full h-auto max-h-[600px] object-cover cursor-crosshair"
        @click="onImageClick"
        @load="onImageLoad"
      />
      
      <!-- Existing Parking Spots -->
      <div 
        v-for="spot in parkingSpots" 
        :key="spot.id"
        class="absolute transform -translate-x-1/2 -translate-y-1/2 cursor-move"
        :style="{ 
          left: spot.position_x + '%', 
          top: spot.position_y + '%' 
        }"
        @mousedown="startDrag(spot, $event)"
        @click.stop
      >
        <div 
          class="w-8 h-8 rounded-full border-3 flex items-center justify-center shadow-lg"
          :class="spot.id === selectedSpot?.id ? 'bg-blue-500 border-blue-600' : 'bg-green-500 border-green-600'"
        >
          <span class="text-xs font-bold text-white">{{ spot.identifier }}</span>
        </div>
        
        <!-- Spot Label -->
        <div class="absolute top-10 left-1/2 transform -translate-x-1/2 bg-black bg-opacity-75 text-white text-xs px-2 py-1 rounded whitespace-nowrap">
          {{ spot.name || spot.identifier }}
        </div>
      </div>
      
      <!-- Preview Position for New Spot -->
      <div 
        v-if="previewPosition && !selectedSpot"
        class="absolute transform -translate-x-1/2 -translate-y-1/2 pointer-events-none"
        :style="{ 
          left: previewPosition.x + '%', 
          top: previewPosition.y + '%' 
        }"
      >
        <div class="w-8 h-8 rounded-full border-3 bg-yellow-500 border-yellow-600 flex items-center justify-center shadow-lg opacity-75">
          <span class="text-xs font-bold text-white">?</span>
        </div>
      </div>
    </div>
    
    <!-- Controls -->
    <div class="p-4 bg-white border-t">
      <div class="flex justify-between items-center mb-4">
        <div>
          <span class="text-sm font-medium">
            {{ selectedSpot ? `Bearbeite: ${selectedSpot.name || selectedSpot.identifier}` : 'Klicken Sie auf einen Parkplatz zum Bearbeiten' }}
          </span>
        </div>
        <div class="space-x-2">
          <button 
            @click="clearSelection"
            class="px-3 py-1 text-sm bg-gray-500 text-white rounded hover:bg-gray-600"
          >
            Auswahl aufheben
          </button>
          <button 
            @click="savePositions"
            :disabled="!hasChanges"
            class="px-3 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-600 disabled:opacity-50"
          >
            Positionen speichern
          </button>
        </div>
      </div>
      
      <!-- Position Info -->
      <div v-if="selectedSpot" class="grid grid-cols-2 gap-4 text-sm">
        <div>
          <label class="block text-gray-700 font-medium mb-1">X-Position (%)</label>
          <input 
            v-model.number="selectedSpot.position_x" 
            type="number" 
            step="0.1" 
            min="0" 
            max="100"
            class="w-full px-2 py-1 border rounded"
            @input="markAsChanged"
          />
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Y-Position (%)</label>
          <input 
            v-model.number="selectedSpot.position_y" 
            type="number" 
            step="0.1" 
            min="0" 
            max="100"
            class="w-full px-2 py-1 border rounded"
            @input="markAsChanged"
          />
        </div>
      </div>
      
      <!-- Instructions -->
      <div class="mt-4 text-xs text-gray-500">
        <p><strong>Anleitung:</strong></p>
        <ul class="list-disc list-inside space-y-1">
          <li>Klicken Sie auf einen Parkplatz, um ihn auszuwählen</li>
          <li>Ziehen Sie Parkplätze, um sie zu verschieben</li>
          <li>Verwenden Sie die Eingabefelder für präzise Positionierung</li>
          <li>Speichern Sie Ihre Änderungen mit dem "Speichern" Button</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, defineProps, defineEmits, nextTick } from 'vue'

const props = defineProps({
  parkingSpots: {
    type: Array,
    default: () => []
  },
  imageUrl: {
    type: String,
    default: '/images/park.jpg'
  }
})

const emit = defineEmits(['positions-updated'])

const imageContainer = ref(null)
const parkingImage = ref(null)
const selectedSpot = ref(null)
const previewPosition = ref(null)
const isDragging = ref(false)
const dragOffset = ref({ x: 0, y: 0 })
const hasChanges = ref(false)
const imageLoaded = ref(false)

const onImageLoad = () => {
  imageLoaded.value = true
}

const onImageClick = (event) => {
  if (!imageLoaded.value || isDragging.value) return
  
  const rect = parkingImage.value.getBoundingClientRect()
  const x = ((event.clientX - rect.left) / rect.width) * 100
  const y = ((event.clientY - rect.top) / rect.height) * 100
  
  previewPosition.value = { x, y }
  
  // If a spot is selected, move it to the clicked position
  if (selectedSpot.value) {
    selectedSpot.value.position_x = Math.round(x * 10) / 10
    selectedSpot.value.position_y = Math.round(y * 10) / 10
    markAsChanged()
  }
}

const startDrag = (spot, event) => {
  event.preventDefault()
  selectedSpot.value = spot
  isDragging.value = true
  
  const rect = parkingImage.value.getBoundingClientRect()
  const spotX = (spot.position_x / 100) * rect.width
  const spotY = (spot.position_y / 100) * rect.height
  
  dragOffset.value = {
    x: event.clientX - rect.left - spotX,
    y: event.clientY - rect.top - spotY
  }
  
  document.addEventListener('mousemove', onDrag)
  document.addEventListener('mouseup', stopDrag)
}

const onDrag = (event) => {
  if (!isDragging.value || !selectedSpot.value) return
  
  const rect = parkingImage.value.getBoundingClientRect()
  const x = ((event.clientX - rect.left - dragOffset.value.x) / rect.width) * 100
  const y = ((event.clientY - rect.top - dragOffset.value.y) / rect.height) * 100
  
  // Constrain to image bounds
  selectedSpot.value.position_x = Math.max(0, Math.min(100, Math.round(x * 10) / 10))
  selectedSpot.value.position_y = Math.max(0, Math.min(100, Math.round(y * 10) / 10))
  
  markAsChanged()
}

const stopDrag = () => {
  isDragging.value = false
  document.removeEventListener('mousemove', onDrag)
  document.removeEventListener('mouseup', stopDrag)
}

const clearSelection = () => {
  selectedSpot.value = null
  previewPosition.value = null
}

const markAsChanged = () => {
  hasChanges.value = true
}

const savePositions = async () => {
  try {
    const updates = props.parkingSpots.map(spot => ({
      id: spot.id,
      position_x: spot.position_x,
      position_y: spot.position_y
    }))
    
    emit('positions-updated', updates)
    hasChanges.value = false
  } catch (error) {
    console.error('Error saving positions:', error)
    alert('Fehler beim Speichern der Positionen')
  }
}
</script>

<style scoped>
.border-3 {
  border-width: 3px;
}
</style>
