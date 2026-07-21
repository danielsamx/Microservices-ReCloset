<script setup>
import { computed } from 'vue'
import Icon from './Icon.vue'
const props = defineProps({
  message: String,
  errors: { type: Object, default: () => ({}) },
})
/** Aplana los errores del backend en una lista legible y sin duplicados. */
const list = computed(() => {
  const out = []
  Object.values(props.errors || {}).forEach((msgs) => {
    (Array.isArray(msgs) ? msgs : [msgs]).forEach((m) => { if (m && !out.includes(m)) out.push(m) })
  })
  return out
})
</script>
<template>
  <div v-if="message || list.length"
    class="bg-danger-50 border border-danger-100 text-danger-700 rounded-xl p-3 mb-4 animate-pop" role="alert" aria-live="assertive">
    <div class="flex items-start gap-2">
      <Icon name="warning" :size="18" class="shrink-0 mt-0.5" />
      <div class="min-w-0 flex-1">
        <p class="text-sm font-semibold">{{ message || 'Revisa los datos del formulario.' }}</p>
        <ul v-if="list.length" class="mt-1 space-y-0.5">
          <li v-for="(e, i) in list" :key="i" class="text-sm text-danger-600 flex gap-1.5">
            <span class="shrink-0">•</span><span>{{ e }}</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>
