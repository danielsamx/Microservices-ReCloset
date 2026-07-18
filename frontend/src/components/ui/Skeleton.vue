<script setup>
defineProps({ h: { type: String, default: '1rem' }, w: { type: String, default: '100%' }, rounded: { type: String, default: '.6rem' } })
</script>
<template>
  <div class="skeleton" :style="{ height: h, width: w, borderRadius: rounded }"></div>
</template>
