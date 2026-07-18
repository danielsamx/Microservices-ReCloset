<script setup>
import { watch, onBeforeUnmount } from 'vue'
const props = defineProps({ open: Boolean, title: String })
const emit = defineEmits(['close'])
function onKey(e) { if (e.key === 'Escape') emit('close') }
watch(() => props.open, (v) => {
  document.removeEventListener('keydown', onKey)
  if (v) document.addEventListener('keydown', onKey)
})
onBeforeUnmount(() => document.removeEventListener('keydown', onKey))
</script>
<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="open" class="fixed inset-0 z-[90] grid place-items-center p-4"
        role="dialog" aria-modal="true" @click.self="emit('close')">
        <div class="absolute inset-0 bg-slate-900/45 backdrop-blur-[2px]"></div>
        <div class="relative card w-full max-w-md p-5 modal-panel-enter-active">
          <h3 v-if="title" class="font-display font-semibold text-lg text-slate-800 mb-1">{{ title }}</h3>
          <slot />
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
