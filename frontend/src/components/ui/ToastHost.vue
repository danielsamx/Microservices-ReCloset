<script setup>
import { useToasts } from '../../store/toasts'
import Icon from './Icon.vue'
const toasts = useToasts()
const iconName = { success: 'success', error: 'warning', info: 'info' }
const tint = { success: 'bg-gradient-to-br from-brand-400 to-brand-600', error: 'bg-gradient-to-br from-danger-400 to-danger-600', info: 'bg-gradient-to-br from-slate-600 to-slate-800' }
</script>
<template>
  <div class="fixed z-[100] top-3 right-3 left-3 sm:left-auto flex flex-col gap-2 items-stretch sm:items-end pointer-events-none">
    <div v-for="t in toasts.items" :key="t.id"
      role="status" aria-live="polite"
      class="pointer-events-auto flex items-center gap-3 card-glass shadow-card-hover rounded-xl pl-2 pr-3 py-2 min-w-[220px] max-w-sm"
      style="animation: toast-in .28s cubic-bezier(.34,1.56,.64,1) both">
      <span :class="tint[t.type]" class="w-7 h-7 shrink-0 grid place-items-center rounded-lg text-white shadow-soft">
        <Icon :name="iconName[t.type]" :size="16" />
      </span>
      <p class="text-sm text-soft flex-1">{{ t.message }}</p>
      <button @click="toasts.dismiss(t.id)" class="text-faint hover:text-muted grid place-items-center w-6 h-6" aria-label="Cerrar notificación">
        <Icon name="close" :size="16" />
      </button>
    </div>
  </div>
</template>
