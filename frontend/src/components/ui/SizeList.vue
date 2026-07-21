<script setup>
import { computed } from 'vue'
const props = defineProps({
  item: Object,
  max: { type: Number, default: 0 },   // 0 = mostrar todas
  small: Boolean,
})
/** Soporta múltiples tallas y hace fallback a la talla única (compatibilidad). */
const all = computed(() => {
  if (props.item?.sizes?.length) return props.item.sizes
  return props.item?.size ? [props.item.size] : []
})
const shown = computed(() => (props.max ? all.value.slice(0, props.max) : all.value))
const rest = computed(() => Math.max(0, all.value.length - shown.value.length))
</script>
<template>
  <div v-if="all.length" class="flex flex-wrap items-center gap-1">
    <span v-for="s in shown" :key="s.id" class="size-pill" :class="{ 'size-sm': small }">{{ s.label }}</span>
    <span v-if="rest" class="size-pill size-more" :class="{ 'size-sm': small }">+{{ rest }}</span>
  </div>
</template>
<style scoped>
.size-pill { display: inline-flex; align-items: center; justify-content: center; min-width: 1.65rem;
  padding: .12rem .4rem; border-radius: .4rem; font-size: .7rem; font-weight: 700;
  background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; line-height: 1.35; }
.size-sm { font-size: .65rem; min-width: 1.4rem; }
.size-more { background: #f3f7ee; color: #386641; border-color: #e4eecd; }
</style>
