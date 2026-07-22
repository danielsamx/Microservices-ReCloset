<script setup>
import { computed } from 'vue'
import { mediaUrl, money } from '../lib/media'
import StatusBadge from './ui/StatusBadge.vue'
import SizeList from './ui/SizeList.vue'
import Icon from './ui/Icon.vue'
const props = defineProps({ item: Object })
const isSold = computed(() => props.item.status === 'sold')
</script>
<template>
  <router-link :to="`/items/${item.id}`"
    class="group card overflow-hidden hover:shadow-card-hover hover:-translate-y-1.5 transition-all duration-300 h-full flex flex-col">
    <div class="relative aspect-[4/5] bg-brand-500/5 overflow-hidden">
      <img v-if="item.media?.length" :src="mediaUrl(item.media[0].url)" loading="lazy"
        class="w-full h-full object-cover transition-transform duration-[600ms] ease-out"
        :class="isSold ? 'grayscale opacity-75' : 'group-hover:scale-[1.08]'" :alt="item.name" />
      <div v-else class="w-full h-full grid place-items-center text-brand-500/30">
        <Icon name="shirt" :size="46" :stroke="1.25" />
      </div>
      <div class="pointer-events-none absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-black/25 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

      <div v-if="isSold" class="absolute inset-0 grid place-items-center">
        <span class="bg-slate-900/80 text-white text-xs font-semibold tracking-wide px-3 py-1.5 rounded-full flex items-center gap-1.5 shadow">
          <Icon name="sold" :size="14" /> Vendido
        </span>
      </div>
      <div v-else class="absolute top-2 left-2">
        <StatusBadge :status="item.status" />
      </div>

      <div v-if="item.media?.length > 1"
        class="absolute top-2 right-2 bg-slate-900/60 text-white text-[10px] font-semibold px-1.5 py-0.5 rounded-md">
        1/{{ item.media.length }}
      </div>

      <div class="absolute bottom-2 left-2">
        <span class="inline-flex items-center glass px-2.5 py-1 rounded-lg font-display font-extrabold text-sm shadow-soft"
          :class="isSold ? 'text-faint line-through' : 'text-brand-700 dark:text-brand-300'">
          {{ money(item.price) }}
        </span>
      </div>
    </div>

    <div class="p-3 flex flex-col gap-1.5 flex-1">
      <h3 class="font-semibold text-body text-sm leading-snug line-clamp-2 group-hover:text-brand-700 dark:group-hover:text-brand-300 transition-colors">{{ item.name }}</h3>
      <SizeList :item="item" :max="4" small />
      <div class="flex items-center gap-1.5 mt-auto pt-2 border-t text-[11px] text-faint" style="border-color: var(--border-soft);">
        <span class="w-4 h-4 rounded bg-gradient-to-br from-brand-300 to-brand-600 text-white grid place-items-center text-[9px] font-bold uppercase shrink-0">
          {{ item.owner_name?.[0] || 'U' }}
        </span>
        <span class="truncate">{{ item.owner_name || 'Usuario' }}</span>
        <span class="ml-auto truncate">{{ item.category?.name }}</span>
      </div>
    </div>
  </router-link>
</template>
