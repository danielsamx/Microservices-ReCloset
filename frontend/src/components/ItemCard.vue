<script setup>
import { computed } from 'vue'
import { mediaUrl, money } from '../lib/media'
import StatusBadge from './ui/StatusBadge.vue'
import Icon from './ui/Icon.vue'
const props = defineProps({ item: Object })
const isSold = computed(() => props.item.status === 'sold')
</script>
<template>
  <router-link :to="`/items/${item.id}`"
    class="group card overflow-hidden border-slate-100/70 hover:border-brand-200/80 hover:shadow-card-hover hover:-translate-y-1.5 transition-all duration-300 ease-out block focus-visible:ring">
    <div class="relative aspect-square bg-slate-100 overflow-hidden">
      <img v-if="item.media?.length" :src="mediaUrl(item.media[0].url)" loading="lazy"
        class="w-full h-full object-cover transition-transform duration-500 ease-out"
        :class="isSold ? 'grayscale opacity-70' : 'group-hover:scale-108'" :alt="item.name" />
      <div v-else class="w-full h-full grid place-items-center text-slate-300">
        <Icon name="shirt" :size="52" :stroke="1.25" />
      </div>

      <!-- Sold overlay: clear but tasteful -->
      <div v-if="isSold" class="absolute inset-0 grid place-items-center">
        <span class="bg-slate-900/80 text-white text-xs font-semibold tracking-wide px-3 py-1.5 rounded-full flex items-center gap-1.5 shadow">
          <Icon name="sold" :size="14" /> Vendido
        </span>
      </div>
      <div v-else class="absolute top-2 left-2">
        <StatusBadge :status="item.status" />
      </div>
    </div>

    <div class="p-3">
      <h3 class="font-medium text-slate-800 truncate">{{ item.name }}</h3>
      <p class="font-display font-bold text-lg mt-0.5" :class="isSold ? 'text-slate-400 line-through' : 'text-brand-700'">{{ money(item.price) }}</p>
      <div class="flex flex-wrap gap-1 mt-2">
        <span class="text-[11px] text-slate-500 bg-slate-100 rounded-md px-1.5 py-0.5">{{ item.category?.name }}</span>
        <span class="text-[11px] text-slate-500 bg-slate-100 rounded-md px-1.5 py-0.5">Talla {{ item.size?.label }}</span>
      </div>
    </div>
  </router-link>
</template>
