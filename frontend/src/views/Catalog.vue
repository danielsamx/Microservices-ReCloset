<script setup>
import { ref, onMounted, watch } from 'vue'
import api from '../lib/api'
import ItemCard from '../components/ItemCard.vue'
const items = ref([]); const meta = ref({ categories: [], sizes: [], colors: [] })
const filters = ref({ category_id: '', size_id: '', color_id: '', search: '' })
const loading = ref(true)
async function load() {
  loading.value = true
  const params = Object.fromEntries(Object.entries(filters.value).filter(([, v]) => v))
  try { const { data } = await api.get('/catalog', { params }); items.value = data.data } finally { loading.value = false }
}
onMounted(async () => { const { data } = await api.get('/meta'); meta.value = data; load() })
watch(filters, load, { deep: true })
function clear() { filters.value = { category_id: '', size_id: '', color_id: '', search: '' } }
</script>
<template>
  <h1 class="text-2xl font-bold mb-4">Catálogo</h1>
  <div class="flex flex-wrap gap-2 mb-5 bg-white p-3 rounded-xl border">
    <input v-model="filters.search" placeholder="Buscar…" class="border rounded-lg px-3 py-1.5 text-sm flex-1 min-w-[160px]" />
    <select v-model="filters.category_id" class="border rounded-lg px-2 py-1.5 text-sm">
      <option value="">Categoría</option><option v-for="c in meta.categories" :key="c.id" :value="c.id">{{ c.name }}</option>
    </select>
    <select v-model="filters.size_id" class="border rounded-lg px-2 py-1.5 text-sm">
      <option value="">Talla</option><option v-for="s in meta.sizes" :key="s.id" :value="s.id">{{ s.label }}</option>
    </select>
    <select v-model="filters.color_id" class="border rounded-lg px-2 py-1.5 text-sm">
      <option value="">Color</option><option v-for="c in meta.colors" :key="c.id" :value="c.id">{{ c.name }}</option>
    </select>
    <button @click="clear" class="text-sm text-slate-500 px-2">Limpiar</button>
  </div>
  <div v-if="loading" class="text-slate-400">Cargando…</div>
  <div v-else-if="!items.length" class="text-center text-slate-400 py-16">No hay prendas que coincidan con los filtros.</div>
  <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <ItemCard v-for="i in items" :key="i.id" :item="i" />
  </div>
</template>
