<script setup>
import { ref, onMounted } from 'vue'
import api from '../lib/api'
import { mediaUrl, money } from '../lib/media'
const items = ref([]); const summary = ref({}); const loading = ref(true)
const label = { available: 'Disponible', reserved: 'Reservada', sold: 'Vendida' }
async function load() {
  loading.value = true
  const [{ data: mine }, { data: s }] = await Promise.all([api.get('/items/mine'), api.get('/wardrobe/summary')])
  items.value = mine.items; summary.value = s; loading.value = false
}
onMounted(load)
async function setStatus(item, status) { await api.patch(`/items/${item.id}/status`, { status }); item.status = status; load() }
async function remove(item) {
  if (!confirm(`¿Eliminar "${item.name}"? Esta acción no se puede deshacer.`)) return
  await api.delete(`/items/${item.id}`); load()
}
</script>
<template>
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Mi armario</h1>
    <router-link to="/items/new" class="bg-brand text-white px-4 py-2 rounded-lg hover:bg-brand-dark text-sm">+ Publicar prenda</router-link>
  </div>
  <div class="grid grid-cols-4 gap-3 mb-6">
    <div class="bg-white border rounded-xl p-3 text-center"><p class="text-2xl font-bold">{{ summary.total || 0 }}</p><p class="text-xs text-slate-400">Total</p></div>
    <div class="bg-white border rounded-xl p-3 text-center"><p class="text-2xl font-bold text-emerald-600">{{ summary.available || 0 }}</p><p class="text-xs text-slate-400">Disponibles</p></div>
    <div class="bg-white border rounded-xl p-3 text-center"><p class="text-2xl font-bold text-amber-600">{{ summary.reserved || 0 }}</p><p class="text-xs text-slate-400">Reservadas</p></div>
    <div class="bg-white border rounded-xl p-3 text-center"><p class="text-2xl font-bold text-slate-500">{{ summary.sold || 0 }}</p><p class="text-xs text-slate-400">Vendidas</p></div>
  </div>
  <div v-if="loading" class="text-slate-400">Cargando…</div>
  <div v-else-if="!items.length" class="text-center text-slate-400 py-16">Aún no has publicado prendas.</div>
  <div v-else class="space-y-3">
    <div v-for="it in items" :key="it.id" class="bg-white border rounded-xl p-3 flex items-center gap-4">
      <img v-if="it.media?.length" :src="mediaUrl(it.media[0].url)" class="w-16 h-16 object-cover rounded-lg" />
      <div v-else class="w-16 h-16 grid place-items-center bg-slate-100 rounded-lg text-2xl">👕</div>
      <div class="flex-1">
        <p class="font-medium">{{ it.name }}</p>
        <p class="text-sm text-brand">{{ money(it.price) }}</p>
      </div>
      <select :value="it.status" @change="setStatus(it, $event.target.value)" class="border rounded-lg px-2 py-1.5 text-sm">
        <option v-for="(l, k) in label" :key="k" :value="k">{{ l }}</option>
      </select>
      <router-link :to="`/items/${it.id}/edit`" class="text-sm text-slate-500 hover:text-brand">Editar</router-link>
      <button @click="remove(it)" class="text-sm text-rose-500 hover:underline">Eliminar</button>
    </div>
  </div>
</template>
