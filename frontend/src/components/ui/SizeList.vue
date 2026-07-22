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
  padding: .12rem .45rem; border-radius: .45rem; font-size: .7rem; font-weight: 700;
  background: rgb(var(--surface-2) / 1); color: var(--text-muted); border: 1px solid var(--border); line-height: 1.35; }
:global(.dark) .size-pill { background: rgba(255,255,255,.05); }
.size-sm { font-size: .65rem; min-width: 1.4rem; }
.size-more { background: rgba(16,185,129,.12); color: var(--brand-strong); border-color: rgba(16,185,129,.25); }
:global(.dark) .size-more { color: var(--mint); }
</style>
