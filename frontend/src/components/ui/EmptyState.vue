<script setup>
import Icon from './Icon.vue'
defineProps({ icon: { type: String, default: 'inbox' }, title: String, subtitle: String })
</script>
<template>
  <div class="text-center py-14 px-4 animate-fade-in">
    <div class="mx-auto w-16 h-16 rounded-2xl bg-brand-50 text-brand-600 grid place-items-center mb-4 ring-1 ring-brand-100">
      <Icon :name="icon" :size="30" :stroke="1.75" />
    </div>
    <h3 class="font-display font-semibold text-slate-700">{{ title }}</h3>
    <p v-if="subtitle" class="text-sm text-slate-400 mt-1 max-w-sm mx-auto leading-relaxed">{{ subtitle }}</p>
    <div class="mt-4 flex justify-center"><slot /></div>
  </div>
</template>
