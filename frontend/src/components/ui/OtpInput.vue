<script setup>
import { ref, watch, nextTick, onMounted } from 'vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  length: { type: Number, default: 6 },
  autofocus: { type: Boolean, default: true },
})
const emit = defineEmits(['update:modelValue', 'complete'])

const digits = ref(Array(props.length).fill(''))
const inputs = ref([])

watch(() => props.modelValue, (v) => {
  const chars = (v || '').slice(0, props.length).split('')
  digits.value = Array.from({ length: props.length }, (_, i) => chars[i] || '')
})

function emitValue() {
  const val = digits.value.join('')
  emit('update:modelValue', val)
  if (val.length === props.length && !digits.value.includes('')) emit('complete', val)
}

function onInput(i, e) {
  const raw = e.target.value.replace(/\D/g, '')
  if (!raw) { digits.value[i] = ''; emitValue(); return }
  // Si pegan varios dígitos, repártelos.
  const chars = raw.split('')
  for (let k = 0; k < chars.length && i + k < props.length; k++) {
    digits.value[i + k] = chars[k]
  }
  const next = Math.min(i + chars.length, props.length - 1)
  nextTick(() => inputs.value[next]?.focus())
  emitValue()
}

function onKeydown(i, e) {
  if (e.key === 'Backspace' && !digits.value[i] && i > 0) {
    inputs.value[i - 1]?.focus()
  } else if (e.key === 'ArrowLeft' && i > 0) {
    inputs.value[i - 1]?.focus()
  } else if (e.key === 'ArrowRight' && i < props.length - 1) {
    inputs.value[i + 1]?.focus()
  }
}

function onPaste(e) {
  e.preventDefault()
  const text = (e.clipboardData?.getData('text') || '').replace(/\D/g, '').slice(0, props.length)
  if (!text) return
  digits.value = Array.from({ length: props.length }, (_, i) => text[i] || '')
  nextTick(() => inputs.value[Math.min(text.length, props.length - 1)]?.focus())
  emitValue()
}

onMounted(() => { if (props.autofocus) nextTick(() => inputs.value[0]?.focus()) })
defineExpose({ focus: () => inputs.value[0]?.focus() })
</script>

<template>
  <div class="flex gap-2 justify-center" @paste="onPaste">
    <input
      v-for="(d, i) in digits" :key="i"
      ref="inputs"
      :value="d"
      @input="onInput(i, $event)"
      @keydown="onKeydown(i, $event)"
      type="text" inputmode="numeric" maxlength="1" autocomplete="one-time-code"
      class="otp-cell"
      :aria-label="`Dígito ${i + 1}`" />
  </div>
</template>

<style scoped>
.otp-cell {
  width: 3rem; height: 3.5rem;
  text-align: center;
  font-size: 1.5rem; font-weight: 700;
  color: var(--text);
  background: rgb(var(--surface) / .85);
  border: 1.5px solid var(--border);
  border-radius: .85rem;
  transition: border .16s, box-shadow .16s, transform .12s;
}
.otp-cell:focus {
  outline: none;
  border-color: var(--brand);
  box-shadow: 0 0 0 3px var(--ring);
  transform: translateY(-1px);
}
@media (max-width: 380px) { .otp-cell { width: 2.5rem; height: 3rem; font-size: 1.25rem; } }
</style>
