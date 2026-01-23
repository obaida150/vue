<template>
    <div class="flex items-center justify-between p-2 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-lg transition-colors">
        <span class="text-xs font-medium text-slate-700 dark:text-slate-300 flex-1">{{ label }}</span>
        <div 
            class="w-6 h-6 rounded-full flex items-center justify-center text-white font-bold text-xs shadow-md transition-all duration-300"
            :style="{ backgroundColor: color || '#94a3b8' }"
            :title="state"
        >
            {{ state === 'on' ? '•' : '○' }}
        </div>
    </div>
</template>

<script>
import { defineComponent } from 'vue'

export default defineComponent({
    name: 'RoomStatus',
    props: {
        label: {
            type: String,
            required: true
        },
        color: {
            type: String,
            default: '#94a3b8'
        },
        state: {
            type: String,
            default: 'unknown'
        }
    }
})
</script>
