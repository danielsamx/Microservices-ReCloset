<script setup>
import Icon from './Icon.vue'
const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  sizes: { type: Array, default: () => [] },
  error: Boolean,
})
const emit = defineEmits(['update:modelValue'])

const isOn = (id) => props.modelValue.includes(id)
function toggle(id) {
  const next = isOn(id) ? props.modelValue.filter((x) => x !== id) : [...props.modelValue, id]
  emit('update:modelValue', next)
}
function selectAll() { emit('update:modelValue', props.sizes.map((s) => s.id)) }
function clear() { emit('update:modelValue', []) }
</script>

<template>
  <div>
    <div class="flex flex-wrap gap-2" role="group" aria-label="Tallas disponibles">
      <button v-for="s in sizes" :key="s.id" type="button" @click="toggle(s.id)"
        class="chip" :class="isOn(s.id) ? 'chip-on' : 'chip-off'"
        :aria-pressed="isOn(s.id)">
        <Icon v-if="isOn(s.id)" name="check" :size="14" :stroke="3" />
        {{ s.label }}
      </button>
    </div>
    <div class="flex items-center gap-3 mt-2">
      <button type="button" @click="selectAll" class="text-xs text-brand-700 dark:text-brand-300 hover:underline">Seleccionar todas</button>
      <button v-if="modelValue.length" type="button" @click="clear" class="text-xs text-faint hover:text-muted">Limpiar</button>
      <span class="text-xs ml-auto" :class="error ? 'text-danger-500' : 'text-faint'">
        {{ modelValue.length ? `${modelValue.length} seleccionada${modelValue.length > 1 ? 's' : ''}` : 'Ninguna seleccionada' }}
      </span>
    </div>
  </div>
</template>

<style scoped>
.chip { display: inline-flex; align-items: center; gap: .3rem; min-width: 3rem; justify-content: center;
  padding: .45rem .8rem; border-radius: .7rem; font-size: .85rem; font-weight: 600;
  border: 1.5px solid; cursor: pointer; transition: all .15s ease; }
.chip:active { transform: scale(.96); }
.chip-off { background: rgb(var(--surface) / .7); border-color: var(--border); color: var(--text-muted); }
.chip-off:hover { border-color: var(--brand); background: rgba(16,185,129,.08); color: var(--brand-strong); }
:global(.dark) .chip-off:hover { color: var(--mint); }
.chip-on { background: linear-gradient(135deg, var(--brand), var(--brand-strong)); border-color: transparent; color: #fff; box-shadow: 0 4px 14px -4px rgba(16,185,129,.6); }
</style>
