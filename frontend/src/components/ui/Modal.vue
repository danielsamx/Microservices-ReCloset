<script setup>
import { watch, onBeforeUnmount } from 'vue'
import { lockScroll, unlockScroll } from '../../lib/scrollLock'
import Icon from './Icon.vue'
const props = defineProps({
  open: Boolean,
  title: String,
  /** en móvil se muestra como hoja inferior (más cómodo con el pulgar) */
  sheet: { type: Boolean, default: true },
})
const emit = defineEmits(['close'])
function onKey(e) { if (e.key === 'Escape') emit('close') }
watch(() => props.open, (v) => {
  document.removeEventListener('keydown', onKey)
  v ? lockScroll() : unlockScroll()
  if (v) document.addEventListener('keydown', onKey)
})
onBeforeUnmount(() => { document.removeEventListener('keydown', onKey); unlockScroll() })
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="open"
        class="fixed inset-0 z-[90] flex p-0 sm:p-4"
        :class="sheet ? 'items-end sm:items-center justify-center' : 'items-center justify-center'"
        role="dialog" aria-modal="true" @click.self="emit('close')">
        <div class="absolute inset-0 bg-brand-900/50 dark:bg-black/60 backdrop-blur-[3px]"></div>

        <div class="relative card-glass w-full sm:max-w-md max-h-[90dvh] flex flex-col modal-panel-enter-active shadow-card-hover"
          :class="sheet ? 'rounded-b-none sm:rounded-2xl pb-[env(safe-area-inset-bottom)]' : ''">
          <header v-if="title" class="flex items-center gap-3 px-4 pt-4 pb-3 shrink-0">
            <h3 class="font-display font-semibold text-lg text-body flex-1 min-w-0 truncate">{{ title }}</h3>
            <button @click="emit('close')" type="button"
              class="w-9 h-9 grid place-items-center rounded-xl text-faint hover:text-brand-600 hover:bg-brand-500/10 transition shrink-0"
              aria-label="Cerrar">
              <Icon name="close" :size="20" />
            </button>
          </header>
          <div class="px-4 pb-4 overflow-y-auto" :class="title ? '' : 'pt-4'">
            <slot />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
