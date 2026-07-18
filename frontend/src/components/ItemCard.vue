<script setup>
import { mediaUrl, money } from '../lib/media'
defineProps({ item: Object })
const badge = { available: 'bg-emerald-100 text-emerald-700', reserved: 'bg-amber-100 text-amber-700', sold: 'bg-slate-200 text-slate-600' }
const label = { available: 'Disponible', reserved: 'Reservada', sold: 'Vendida' }
</script>
<template>
  <router-link :to="`/items/${item.id}`" class="bg-white rounded-xl border overflow-hidden hover:shadow-md transition block">
    <div class="aspect-square bg-slate-100 overflow-hidden">
      <img v-if="item.media?.length" :src="mediaUrl(item.media[0].url)" class="w-full h-full object-cover" :alt="item.name" />
      <div v-else class="w-full h-full grid place-items-center text-slate-300 text-4xl">👕</div>
    </div>
    <div class="p-3">
      <div class="flex items-center justify-between gap-2">
        <h3 class="font-medium truncate">{{ item.name }}</h3>
        <span :class="badge[item.status]" class="text-[11px] px-2 py-0.5 rounded-full whitespace-nowrap">{{ label[item.status] }}</span>
      </div>
      <p class="text-brand font-semibold mt-1">{{ money(item.price) }}</p>
      <p class="text-xs text-slate-400 mt-1">
        {{ item.category?.name }} · {{ item.size?.label }} · {{ item.color?.name }}
      </p>
    </div>
  </router-link>
</template>
