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
  { k: 'total',     l: 'Total',       grad: 'from-slate-500 to-slate-700',    wash: 'from-slate-400/15',   icon: 'bag' },
  { k: 'available', l: 'Disponibles', grad: 'from-brand-400 to-brand-600',    wash: 'from-brand-400/20',   icon: 'check' },
  { k: 'reserved',  l: 'Reservadas',  grad: 'from-amber-400 to-amber-600',    wash: 'from-amber-400/20',   icon: 'tag' },
  { k: 'sold',      l: 'Vendidas',    grad: 'from-lime-400 to-brand-500',     wash: 'from-lime-400/20',    icon: 'sold' },
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
    <div class="min-w-0">
      <h1 class="font-display font-bold text-2xl sm:text-3xl leading-tight truncate">Mis publicaciones</h1>
      <p class="text-sm text-faint mt-0.5">Gestiona tus prendas publicadas</p>
    </div>
    <router-link to="/items/new" class="btn btn-primary btn-sm shrink-0">
      <Icon name="plus" :size="16" /><span class="hidden sm:inline">Publicar</span>
    </router-link>
  </div>

  <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
    <div v-for="(s, idx) in stats" :key="s.k"
      class="card relative overflow-hidden p-4 sm:p-5 hover:shadow-card-hover hover:-translate-y-1 transition-all animate-fade-up"
      :style="{ animationDelay: idx * 60 + 'ms' }">
      <!-- lavado de color en gradiente (esquina) -->
      <div class="pointer-events-none absolute -right-6 -top-6 w-24 h-24 rounded-full bg-gradient-to-br to-transparent" :class="s.wash"></div>

      <!-- icono grande con gradiente -->
      <span class="relative w-12 h-12 sm:w-14 sm:h-14 rounded-2xl grid place-items-center text-white shrink-0 bg-gradient-to-br shadow-soft" :class="s.grad">
        <Icon :name="s.icon" :size="26" />
      </span>

      <!-- número grande -->
      <p class="relative font-display font-extrabold text-4xl sm:text-5xl leading-none mt-4 text-body tabular-nums">
        <template v-if="loading"><span class="text-faint">—</span></template>
        <template v-else>{{ summary[s.k] ?? 0 }}</template>
      </p>
      <p class="relative text-xs sm:text-sm font-medium text-faint mt-1.5">{{ s.l }}</p>
    </div>
  </div>

  <div v-if="loading" class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3">
    <div v-for="i in 6" :key="i" class="card p-3">
      <div class="flex gap-3">
        <Skeleton h="4.5rem" w="4.5rem" /><div class="flex-1 space-y-2 pt-1"><Skeleton h="1rem" w="70%" /><Skeleton h=".9rem" w="35%" /><Skeleton h=".8rem" w="55%" /></div>
      </div>
      <Skeleton h="2.2rem" w="100%" rounded=".7rem" />
    </div>
  </div>

  <EmptyState v-else-if="error" icon="warning" title="No se pudieron cargar tus publicaciones" subtitle="Inténtalo de nuevo.">
    <button class="btn btn-primary" @click="load"><Icon name="retry" :size="16" /> Reintentar</button>
  </EmptyState>

  <EmptyState v-else-if="!items.length" icon="shirt" title="Todavía no has publicado nada"
    subtitle="Publica tu primera prenda y empieza a vender.">
    <router-link to="/items/new" class="btn btn-primary">Publicar prenda</router-link>
  </EmptyState>

  <div v-else class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3">
    <article v-for="(it, idx) in items" :key="it.id"
      class="card p-3 flex flex-col hover:shadow-card-hover hover:-translate-y-0.5 transition-all animate-fade-up"
      :style="{ animationDelay: Math.min(idx, 9) * 40 + 'ms' }">
      <!-- cabecera: imagen + datos -->
      <div class="flex items-start gap-3">
        <router-link :to="`/items/${it.id}`" class="shrink-0">
          <img v-if="it.media?.length" :src="mediaUrl(it.media[0].url)"
            class="w-[4.5rem] h-[4.5rem] object-cover rounded-xl" :class="{ 'grayscale opacity-80': it.status==='sold' }" alt="" />
          <div v-else class="w-[4.5rem] h-[4.5rem] grid place-items-center bg-brand-500/10 rounded-xl text-brand-500/40"><Icon name="shirt" :size="26" /></div>
        </router-link>

        <div class="flex-1 min-w-0">
          <div class="flex items-start gap-2">
            <router-link :to="`/items/${it.id}`" class="font-semibold text-body line-clamp-2 hover:text-brand-700 dark:hover:text-brand-300 text-sm flex-1 min-w-0 transition-colors">{{ it.name }}</router-link>
            <StatusBadge :status="it.status" class="shrink-0" />
          </div>
          <p class="text-brand-700 dark:text-brand-300 font-bold mt-0.5">{{ money(it.price) }}</p>
          <div class="mt-1.5"><SizeList :item="it" :max="6" small /></div>
        </div>
      </div>

      <!-- acciones: pegadas al fondo de la tarjeta -->
      <div class="flex items-center gap-2 mt-3 pt-3 border-t mt-auto" style="border-color: var(--border-soft);">
        <select :value="it.status" @change="setStatus(it, $event.target.value)"
          class="input !py-1.5 text-sm flex-1 min-w-0" aria-label="Cambiar estado de la publicación">
          <option value="available">Disponible</option>
          <option value="reserved">Reservada</option>
          <option value="sold">Vendida</option>
        </select>
        <router-link :to="`/items/${it.id}/edit`"
          class="w-9 h-9 grid place-items-center rounded-xl btn-soft shrink-0" :aria-label="`Editar ${it.name}`">
          <Icon name="edit" :size="16" />
        </router-link>
        <button @click="askRemove(it)"
          class="w-9 h-9 grid place-items-center rounded-xl text-faint hover:text-danger-600 hover:bg-danger-500/10 transition shrink-0"
          :aria-label="`Eliminar ${it.name}`"><Icon name="trash" :size="16" /></button>
      </div>
    </article>
  </div>

  <ConfirmDialog :open="confirm.open" danger :loading="confirm.loading"
    title="Eliminar publicación"
    :message="`¿Seguro que quieres eliminar “${confirm.item?.name}”? Esta acción no se puede deshacer y también se borrarán sus fotos.`"
    confirmText="Eliminar" @confirm="doRemove" @cancel="confirm.open = false" />
</template>
