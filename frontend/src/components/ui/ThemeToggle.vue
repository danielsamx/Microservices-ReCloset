<script setup>
import { useTheme } from '../../store/theme'
import Icon from './Icon.vue'
const theme = useTheme()
</script>
<template>
  <button
    @click="theme.toggle()"
    class="theme-toggle"
    :aria-label="theme.isDark ? 'Activar modo claro' : 'Activar modo oscuro'"
    :title="theme.isDark ? 'Modo claro' : 'Modo oscuro'">
    <Transition name="theme-swap" mode="out-in">
      <Icon v-if="theme.isDark" key="sun" name="sun" :size="19" />
      <Icon v-else key="moon" name="moon" :size="19" />
    </Transition>
  </button>
</template>
<style scoped>
.theme-toggle {
  position: relative;
  width: 2.5rem; height: 2.5rem;
  display: grid; place-items: center;
  border-radius: .8rem;
  color: var(--text-muted);
  transition: background .18s ease, color .18s ease, transform .18s ease;
}
.theme-toggle:hover { background: rgba(16,185,129,.12); color: var(--brand-strong); transform: rotate(-8deg); }
:global(.dark) .theme-toggle:hover { color: var(--mint); }
.theme-swap-enter-active, .theme-swap-leave-active { transition: opacity .2s ease, transform .3s cubic-bezier(.34,1.56,.64,1); }
.theme-swap-enter-from { opacity: 0; transform: rotate(-90deg) scale(.5); }
.theme-swap-leave-to { opacity: 0; transform: rotate(90deg) scale(.5); }
</style>
