<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import api from '../lib/api'
import ItemCard from '../components/ItemCard.vue'
import ProductCardSkeleton from '../components/ui/ProductCardSkeleton.vue'
import EmptyState from '../components/ui/EmptyState.vue'
import Icon from '../components/ui/Icon.vue'

const items = ref([]); const total = ref(0)
const meta = ref({ categories: [], sizes: [], colors: [] })
const filters = ref({ category_id: '', size_id: '', color_id: '', search: '' })
const loading = ref(true); const error = ref(false); const showFilters = ref(false)
const activeCount = computed(() => Object.values(filters.value).filter(Boolean).length)

async function load() {
  loading.value = true; error.value = false
  const params = Object.fromEntries(Object.entries(filters.value).filter(([, v]) => v))
  try {
    const { data } = await api.get('/catalog', { params })
    items.value = data.data; total.value = data.total ?? data.data.length
  } catch (e) { error.value = true } finally { loading.value = false }
}
onMounted(async () => {
  try { const { data } = await api.get('/meta'); meta.value = data } catch (e) {}
  load()
})
watch(filters, load, { deep: true })
function clear() { filters.value = { category_id: '', size_id: '', color_id: '', search: '' } }
function setSize(id) { filters.value.size_id = filters.value.size_id === id ? '' : id }
</script>

<template>
  <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-3">
    <div class="min-w-0">
      <h1 class="font-display font-bold text-xl sm:text-2xl leading-tight">Catálogo</h1>
      <p class="text-sm text-slate-400">
        <template v-if="loading">Buscando prendas…</template>
        <template v-else>{{ total }} publicacion{{ total === 1 ? '' : 'es' }} disponible{{ total === 1 ? '' : 's' }}</template>
      </p>
    </div>
    <div class="relative sm:ml-auto sm:w-72">
      <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"><Icon name="search" :size="17" /></span>
      <input v-model="filters.search" placeholder="Buscar prendas…" class="input pl-10" aria-label="Buscar prendas" />
    </div>
    <button class="btn btn-ghost btn-sm sm:hidden self-start" @click="showFilters = !showFilters">
      <Icon name="filters" :size="16" /> Filtros
      <span v-if="activeCount" class="badge badge-available ml-1">{{ activeCount }}</span>
    </button>
  </div>

  <div class="card p-3 mb-4" :class="{ 'hidden sm:block': !showFilters }">
    <div class="flex flex-wrap items-center gap-2">
      <select v-model="filters.category_id" class="input !py-1.5 text-sm flex-1 min-w-[9rem] sm:flex-none sm:!w-auto" aria-label="Categoría">
        <option value="">Todas las categorías</option>
        <option v-for="c in meta.categories" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
      <select v-model="filters.color_id" class="input !py-1.5 text-sm flex-1 min-w-[9rem] sm:flex-none sm:!w-auto" aria-label="Color">
        <option value="">Todos los colores</option>
        <option v-for="c in meta.colors" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>

      <div class="w-px h-6 bg-slate-200 mx-1 hidden sm:block"></div>
      <span class="text-xs text-slate-400 hidden sm:block">Talla:</span>
      <div class="flex flex-wrap gap-1.5">
        <button v-for="s in meta.sizes" :key="s.id" type="button" @click="setSize(s.id)"
          class="size-filter" :class="{ 'size-filter-on': filters.size_id === s.id }"
          :aria-pressed="filters.size_id === s.id">{{ s.label }}</button>
      </div>

      <button v-if="activeCount" @click="clear" class="btn btn-soft btn-sm ml-auto">
        <Icon name="close" :size="15" /> Limpiar
      </button>
    </div>
  </div>

  <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
    <ProductCardSkeleton v-for="i in 10" :key="i" />
  </div>

  <EmptyState v-else-if="error" icon="warning" title="No pudimos cargar las prendas"
    subtitle="Comprueba tu conexión e inténtalo de nuevo.">
    <button class="btn btn-primary" @click="load"><Icon name="retry" :size="16" /> Reintentar</button>
  </EmptyState>

  <EmptyState v-else-if="!items.length" icon="search" title="Sin resultados"
    subtitle="No hay publicaciones que coincidan con tu búsqueda. Prueba a cambiar los filtros.">
    <button v-if="activeCount" class="btn btn-primary" @click="clear">Quitar filtros</button>
  </EmptyState>

  <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
    <div v-for="(i, idx) in items" :key="i.id" class="animate-fade-up" :style="{ animationDelay: Math.min(idx, 10) * 30 + 'ms' }">
      <ItemCard :item="i" />
    </div>
  </div>
</template>

<style scoped>
.size-filter { min-width: 2.2rem; padding: .3rem .55rem; border-radius: .55rem; font-size: .78rem; font-weight: 600;
  border: 1.5px solid #e2e8f0; background: #fff; color: #475569; transition: all .15s; }
.size-filter:hover { border-color: #cfe0a8; color: #386641; }
.size-filter-on { background: #386641; border-color: #386641; color: #fff; }
</style>
