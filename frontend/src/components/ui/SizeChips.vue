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
      <button type="button" @click="selectAll" class="text-xs text-brand-700 hover:underline">Seleccionar todas</button>
      <button v-if="modelValue.length" type="button" @click="clear" class="text-xs text-slate-400 hover:text-slate-600">Limpiar</button>
      <span class="text-xs ml-auto" :class="error ? 'text-danger-500' : 'text-slate-400'">
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
.chip-off { background: #fff; border-color: #e2e8f0; color: #475569; }
.chip-off:hover { border-color: #cfe0a8; background: #f3f7ee; color: #386641; }
.chip-on { background: #386641; border-color: #386641; color: #fff; box-shadow: 0 4px 12px -4px rgba(56,102,65,.5); }
</style>
