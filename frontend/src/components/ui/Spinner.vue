<script setup>
defineProps({ size: { type: Number, default: 18 }, light: Boolean })
</script>
<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" class="animate-spin" role="status" aria-label="Cargando">
    <circle cx="12" cy="12" r="9" :stroke="light ? 'rgba(255,255,255,.3)' : 'rgba(16,185,129,.2)'" stroke-width="3" />
    <path d="M21 12a9 9 0 0 0-9-9" :stroke="light ? '#fff' : '#10b981'" stroke-width="3" stroke-linecap="round" />
  </svg>
</template>
