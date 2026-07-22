<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../lib/api'
import { mediaUrl, money } from '../lib/media'
import { useAuth } from '../store/auth'
import { useToasts } from '../store/toasts'
import StatusBadge from '../components/ui/StatusBadge.vue'
import EmptyState from '../components/ui/EmptyState.vue'
import Skeleton from '../components/ui/Skeleton.vue'
import Spinner from '../components/ui/Spinner.vue'
import Icon from '../components/ui/Icon.vue'

const route = useRoute(); const router = useRouter(); const auth = useAuth(); const toasts = useToasts()
const item = ref(null); const active = ref(0); const loading = ref(true); const starting = ref(false); const notFound = ref(false)
const isMine = computed(() => auth.user && item.value && item.value.owner_id === auth.user.id)
const sizes = computed(() => item.value?.sizes?.length ? item.value.sizes : (item.value?.size ? [item.value.size] : []))

async function load() {
  loading.value = true; notFound.value = false
  try { const { data } = await api.get(`/catalog/${route.params.id}`); item.value = data.item }
  catch (e) { notFound.value = true } finally { loading.value = false }
}
onMounted(load)

async function contact() {
  if (!auth.isAuthed) return router.push('/login')
  starting.value = true
  try {
    const { data } = await api.post('/conversations', { item_id: item.value.id })
    router.push(`/chat/${data.conversation.id}`)
  } catch (e) { toasts.error(e.response?.data?.message || 'No se pudo iniciar el chat.') }
  finally { starting.value = false }
}
</script>
<template>
  <nav class="flex items-center gap-1.5 text-sm text-faint mb-3">
    <router-link to="/catalog" class="hover:text-brand-700 dark:hover:text-brand-300 transition-colors">Catálogo</router-link>
    <Icon name="chevronRight" :size="14" />
    <span class="text-muted truncate max-w-[50vw]">{{ item?.name || 'Publicación' }}</span>
  </nav>

  <div v-if="loading" class="grid lg:grid-cols-[1.05fr_1fr] gap-6 lg:gap-8 items-start">
    <Skeleton h="clamp(300px,46vw,520px)" rounded="1.125rem" />
    <div class="card p-5 space-y-3">
      <Skeleton h="1.4rem" w="35%" /><Skeleton h="2rem" w="70%" /><Skeleton h="2rem" w="45%" />
      <Skeleton h="1rem" w="55%" /><Skeleton h="4rem" /><Skeleton h="3rem" w="70%" />
    </div>
  </div>

  <EmptyState v-else-if="notFound" icon="search" title="Publicación no encontrada"
    subtitle="Puede que se haya vendido o eliminado.">
    <router-link to="/catalog" class="btn btn-primary">Volver al catálogo</router-link>
  </EmptyState>

  <div v-else class="grid lg:grid-cols-[1.05fr_1fr] gap-6 lg:gap-8 items-start animate-fade-in">
    <!-- Galería (sticky en escritorio para acompañar el scroll de los detalles) -->
    <div class="lg:sticky lg:top-20 self-start">
      <div class="card overflow-hidden aspect-square bg-brand-500/5 relative">
        <img v-if="item.media?.length" :src="mediaUrl(item.media[active].url)"
          class="w-full h-full object-cover animate-fade-in" :class="{ 'grayscale opacity-80': item.status === 'sold' }" :key="active" :alt="item.name" />
        <div v-else class="w-full h-full grid place-items-center text-brand-500/30"><Icon name="shirt" :size="96" :stroke="1.2" /></div>
        <div v-if="item.status === 'sold'" class="absolute inset-0 grid place-items-center pointer-events-none">
          <span class="bg-slate-900/80 text-white text-sm font-semibold px-4 py-2 rounded-full flex items-center gap-2"><Icon name="sold" :size="16" /> Vendido</span>
        </div>
      </div>
      <div v-if="item.media?.length > 1" class="flex gap-2 mt-3 overflow-x-auto pb-1">
        <button v-for="(m, i) in item.media" :key="m.media_id" @click="active = i"
          class="w-16 h-16 sm:w-[4.5rem] sm:h-[4.5rem] rounded-xl overflow-hidden shrink-0 border-2 transition"
          :class="i === active ? 'border-brand shadow-glow' : 'border-transparent opacity-70 hover:opacity-100'" :aria-label="`Imagen ${i+1}`">
          <img :src="mediaUrl(m.url)" class="w-full h-full object-cover" alt="" />
        </button>
      </div>
    </div>

    <!-- Detalles (columna que llena el alto de la imagen) -->
    <div class="space-y-4">
      <div class="card p-5 sm:p-6">
        <div class="flex items-center justify-between gap-3 mb-2">
          <StatusBadge :status="item.status" />
          <span class="text-xs text-faint">{{ item.category?.name }}</span>
        </div>
        <h1 class="font-display font-extrabold text-2xl sm:text-3xl leading-tight">{{ item.name }}</h1>
        <p class="font-display font-extrabold text-3xl sm:text-4xl mt-2" :class="item.status === 'sold' ? 'text-faint line-through' : 'text-gradient'">{{ money(item.price) }}</p>

        <div class="my-4 border-t" style="border-color: var(--border-soft);"></div>

        <div v-if="sizes.length" class="mb-4">
          <h3 class="field-label mb-1.5">Tallas disponibles</h3>
          <div class="flex flex-wrap gap-1.5">
            <span v-for="s in sizes" :key="s.id"
              class="min-w-[2.5rem] text-center px-2.5 py-1.5 rounded-lg border text-sm font-semibold text-soft" style="border-color: var(--border); background: rgb(var(--surface) / .6);">
              {{ s.label }}
            </span>
          </div>
        </div>

        <div class="flex flex-wrap gap-x-6 gap-y-3 mb-4">
          <div>
            <p class="field-label mb-1">Color</p>
            <span class="inline-flex items-center gap-2 text-sm font-medium text-soft">
              <span class="w-4 h-4 rounded-full border" style="border-color: var(--border);" :style="{ background: item.color?.hex }"></span>{{ item.color?.name }}
            </span>
          </div>
          <div>
            <p class="field-label mb-1">Categoría</p>
            <span class="text-sm font-medium text-soft">{{ item.category?.name }}</span>
          </div>
        </div>

        <!-- Vendedor -->
        <div class="flex items-center gap-3 rounded-xl p-3 mb-4" style="background: rgb(var(--surface-2) / 1); border: 1px solid var(--border-soft);">
          <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-300 to-brand-600 text-white grid place-items-center font-bold uppercase shadow-soft shrink-0">{{ item.owner_name?.[0] || 'U' }}</span>
          <div class="min-w-0">
            <p class="text-xs text-faint">Publicado por</p>
            <p class="font-semibold text-body truncate">{{ item.owner_name || 'Usuario' }}</p>
          </div>
        </div>

        <button v-if="!isMine" @click="contact" :disabled="starting || item.status === 'sold'" class="btn btn-primary btn-lg btn-block">
          <Spinner v-if="starting" :size="18" light /><Icon v-else name="message" :size="18" />
          {{ item.status === 'sold' ? 'No disponible' : 'Contactar al vendedor' }}
        </button>
        <div v-else class="flex flex-col sm:flex-row gap-2.5">
          <router-link :to="`/items/${item.id}/edit`" class="btn btn-primary btn-lg flex-1"><Icon name="edit" :size="18" /> Editar publicación</router-link>
          <router-link to="/my-items" class="btn btn-ghost btn-lg sm:w-auto"><Icon name="bag" :size="18" /> Mis publicaciones</router-link>
        </div>
      </div>

      <!-- Descripción: llena la columna en lugar de dejar hueco -->
      <div v-if="item.description" class="card p-5 sm:p-6">
        <h3 class="font-display font-bold text-lg mb-2">Descripción</h3>
        <p class="text-muted whitespace-pre-line leading-relaxed text-[15px]">{{ item.description }}</p>
      </div>
    </div>
  </div>
</template>
