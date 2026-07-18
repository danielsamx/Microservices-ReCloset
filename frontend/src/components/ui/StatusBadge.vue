<script setup>
import Icon from './Icon.vue'
defineProps({ status: String, size: { type: Number, default: 12 } })
const label = { available: 'Disponible', reserved: 'Reservada', sold: 'Vendida' }
const icon = { available: 'check', reserved: 'tag', sold: 'sold' }
</script>
<template>
  <span class="badge" :class="`badge-${status}`">
    <Icon :name="icon[status] || 'info'" :size="size" :stroke="2.4" />
    {{ label[status] || status }}
  </span>
</template>
