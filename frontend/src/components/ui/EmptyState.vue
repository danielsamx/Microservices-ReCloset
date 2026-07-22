<script setup>
import Icon from './Icon.vue'
defineProps({ icon: { type: String, default: 'inbox' }, title: String, subtitle: String })
</script>
<template>
  <div class="text-center py-14 px-4 animate-fade-in">
    <div class="mx-auto w-20 h-20 rounded-3xl bg-gradient-to-br from-brand-500/15 to-lime-400/15 text-brand-600 dark:text-brand-300 grid place-items-center mb-4 ring-1 ring-brand-500/20 animate-floaty">
      <Icon :name="icon" :size="34" :stroke="1.75" />
    </div>
    <h3 class="font-display font-semibold text-lg text-soft">{{ title }}</h3>
    <p v-if="subtitle" class="text-sm text-faint mt-1.5 max-w-sm mx-auto leading-relaxed">{{ subtitle }}</p>
    <div class="mt-4 flex justify-center"><slot /></div>
  </div>
</template>
