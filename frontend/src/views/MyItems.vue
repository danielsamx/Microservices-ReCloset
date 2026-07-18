<script setup>
import { ref, onMounted } from 'vue'
import api from '../lib/api'
import { mediaUrl, money } from '../lib/media'
import { useToasts } from '../store/toasts'
import StatusBadge from '../components/ui/StatusBadge.vue'
import SizeList from '../components/ui/SizeList.vue'
import EmptyState from '../components/ui/EmptyState.vue'
import ConfirmDialog from '../components/ui/ConfirmDialog.vue'
import Skeleton from '../components/ui/Skeleton.vue'
import Icon from '../components/ui/Icon.vue'
const toasts = useToasts()
const items = ref([]); const summary = ref({}); const loading = ref(true); const error = ref(false)
const confirm = ref({ open: false, item: null, loading: false })
const stats = [
  { k: 'total', l: 'Total', c: 'text-slate-800', icon: 'bag' },
  { k: 'available', l: 'Disponibles', c: 'text-emerald-600', icon: 'check' },
  { k: 'reserved', l: 'Reservadas', c: 'text-amber-600', icon: 'tag' },
  { k: 'sold', l: 'Vendidas', c: 'text-slate-500', icon: 'sold' },
]
async function load() {
  loading.value = true; error.value = false
  try {
    const [{ data: mine }, { data: s }] = await Promise.all([api.get('/items/mine'), api.get('/wardrobe/summary')])
    items.value = mine.items; summary.value = s
  } catch (e) { error.value = true } finally { loading.value = false }
}
onMounted(load)
async function setStatus(item, status) {
  const prev = item.status; item.status = status
  try { await api.patch(`/items/${item.id}/status`, { status }); toasts.success('Estado actualizado'); load() }
  catch (e) { item.status = prev; toasts.error('No se pudo cambiar el estado') }
}
function askRemove(item) { confirm.value = { open: true, item, loading: false } }
async function doRemove() {
  confirm.value.loading = true
  try {
    await api.delete(`/items/${confirm.value.item.id}`)
    toasts.success('Publicación eliminada'); confirm.value.open = false; load()
  } catch (e) { toasts.error('No se pudo eliminar'); confirm.value.loading = false }
}
</script>
<template>
  <div class="flex items-center justify-between gap-3 mb-4">
    <div>
      <h1 class="font-display font-bold text-2xl leading-tight">Mis publicaciones</h1>
      <p class="text-sm text-slate-400">Gestiona tus prendas publicadas</p>
    </div>
    <router-link to="/items/new" class="btn btn-primary btn-sm"><Icon name="plus" :size="16" /> Publicar</router-link>
  </div>

  <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 mb-4">
    <div v-for="s in stats" :key="s.k" class="card px-3 py-2.5">
      <div class="flex items-center justify-between">
        <p class="text-xl font-display font-extrabold" :class="s.c">
          <template v-if="loading">—</template><template v-else>{{ summary[s.k] || 0 }}</template>
        </p>
        <span class="text-slate-300"><Icon :name="s.icon" :size="18" /></span>
      </div>
      <p class="text-[11px] text-slate-400 mt-0.5">{{ s.l }}</p>
    </div>
  </div>

  <div v-if="loading" class="space-y-2.5">
    <div v-for="i in 3" :key="i" class="card p-2.5 flex items-center gap-3">
      <Skeleton h="3.5rem" w="3.5rem" /><div class="flex-1 space-y-2"><Skeleton h="1rem" w="40%" /><Skeleton h=".9rem" w="20%" /></div>
    </div>
  </div>

  <EmptyState v-else-if="error" icon="warning" title="No se pudieron cargar tus publicaciones" subtitle="Inténtalo de nuevo.">
    <button class="btn btn-primary" @click="load"><Icon name="retry" :size="16" /> Reintentar</button>
  </EmptyState>

  <EmptyState v-else-if="!items.length" icon="shirt" title="Todavía no has publicado nada"
    subtitle="Publica tu primera prenda y empieza a vender.">
    <router-link to="/items/new" class="btn btn-primary">Publicar prenda</router-link>
  </EmptyState>

  <div v-else class="space-y-2.5">
    <div v-for="it in items" :key="it.id" class="card p-2.5 flex items-center gap-3 hover:shadow-card-hover transition">
      <router-link :to="`/items/${it.id}`" class="shrink-0">
        <img v-if="it.media?.length" :src="mediaUrl(it.media[0].url)" class="w-14 h-14 object-cover rounded-xl" :class="{ 'grayscale opacity-80': it.status==='sold' }" alt="" />
        <div v-else class="w-14 h-14 grid place-items-center bg-slate-100 rounded-xl text-slate-300"><Icon name="shirt" :size="24" /></div>
      </router-link>
      <div class="flex-1 min-w-0">
        <router-link :to="`/items/${it.id}`" class="font-medium text-slate-800 truncate block hover:text-brand-700 text-sm">{{ it.name }}</router-link>
        <p class="text-brand-700 font-bold text-sm">{{ money(it.price) }}</p>
        <div class="flex items-center gap-2 mt-1">
          <SizeList :item="it" :max="4" small />
          <span class="sm:hidden"><StatusBadge :status="it.status" /></span>
        </div>
      </div>
      <div class="hidden sm:block shrink-0"><StatusBadge :status="it.status" /></div>
      <select :value="it.status" @change="setStatus(it, $event.target.value)" class="input !py-1.5 !w-auto text-sm shrink-0" aria-label="Cambiar estado">
        <option value="available">Disponible</option>
        <option value="reserved">Reservada</option>
        <option value="sold">Vendida</option>
      </select>
      <router-link :to="`/items/${it.id}/edit`" class="btn btn-soft btn-sm hidden sm:inline-flex shrink-0"><Icon name="edit" :size="15" /> Editar</router-link>
      <button @click="askRemove(it)" class="w-9 h-9 grid place-items-center rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition shrink-0" aria-label="Eliminar publicación"><Icon name="trash" :size="17" /></button>
    </div>
  </div>

  <ConfirmDialog :open="confirm.open" danger :loading="confirm.loading"
    title="Eliminar publicación"
    :message="`¿Seguro que quieres eliminar “${confirm.item?.name}”? Esta acción no se puede deshacer y también se borrarán sus fotos.`"
    confirmText="Eliminar" @confirm="doRemove" @cancel="confirm.open = false" />
</template>
