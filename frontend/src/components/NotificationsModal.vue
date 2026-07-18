<script setup>
import { watch, onBeforeUnmount } from 'vue'
import { lockScroll, unlockScroll } from '../lib/scrollLock'
import NotificationsPanel from './NotificationsPanel.vue'
import Icon from './ui/Icon.vue'
import { useNotifications } from '../store/notifications'

const props = defineProps({ open: Boolean })
const emit = defineEmits(['close'])
const notif = useNotifications()

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
        class="fixed inset-0 z-[80] flex items-end justify-center sm:items-start sm:justify-end p-0 sm:p-4 sm:pt-20"
        role="dialog" aria-modal="true" aria-label="Notificaciones" @click.self="emit('close')">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-[2px]"></div>

        <div class="relative card w-full sm:max-w-md flex flex-col modal-panel-enter-active
                    max-h-[85dvh] sm:max-h-[70vh] rounded-b-none sm:rounded-2xl">
          <header class="flex items-center gap-3 p-4 border-b border-slate-100 shrink-0">
            <span class="w-9 h-9 rounded-xl bg-brand-50 text-brand-700 grid place-items-center shrink-0">
              <Icon name="bell" :size="18" />
            </span>
            <div class="min-w-0 flex-1">
              <h2 class="font-display font-bold text-base leading-tight">Notificaciones</h2>
              <p class="text-xs text-slate-400 truncate">{{ notif.unread ? `${notif.unread} sin leer` : 'Sin pendientes' }}</p>
            </div>
            <button @click="emit('close')" type="button"
              class="w-10 h-10 grid place-items-center rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition shrink-0"
              aria-label="Cerrar notificaciones">
              <Icon name="close" :size="20" />
            </button>
          </header>

          <div class="p-3 overflow-hidden flex-1 flex flex-col min-h-0 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
            <NotificationsPanel @navigate="emit('close')" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
