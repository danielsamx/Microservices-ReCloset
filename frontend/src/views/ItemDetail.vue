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
  <nav class="flex items-center gap-1.5 text-sm text-slate-400 mb-3">
    <router-link to="/catalog" class="hover:text-brand-700">Catálogo</router-link>
    <Icon name="chevronRight" :size="14" />
    <span class="text-slate-600 truncate max-w-[50vw]">{{ item?.name || 'Publicación' }}</span>
  </nav>

  <div v-if="loading" class="grid md:grid-cols-2 gap-6">
    <Skeleton h="clamp(280px,48vw,440px)" rounded="1.125rem" />
    <div class="space-y-3">
      <Skeleton h="2rem" w="70%" /><Skeleton h="1.8rem" w="40%" />
      <Skeleton h="1rem" w="55%" /><Skeleton h="5rem" /><Skeleton h="3rem" w="60%" />
    </div>
  </div>

  <EmptyState v-else-if="notFound" icon="search" title="Publicación no encontrada"
    subtitle="Puede que se haya vendido o eliminado.">
    <router-link to="/catalog" class="btn btn-primary">Volver al catálogo</router-link>
  </EmptyState>

  <div v-else class="grid md:grid-cols-2 gap-5 lg:gap-8 animate-fade-in">
    <div>
      <div class="card overflow-hidden aspect-square bg-slate-100 relative">
        <img v-if="item.media?.length" :src="mediaUrl(item.media[active].url)"
          class="w-full h-full object-cover animate-fade-in" :class="{ 'grayscale opacity-80': item.status === 'sold' }" :key="active" :alt="item.name" />
        <div v-else class="w-full h-full grid place-items-center text-slate-300"><Icon name="shirt" :size="72" :stroke="1.2" /></div>
        <div v-if="item.status === 'sold'" class="absolute inset-0 grid place-items-center pointer-events-none">
          <span class="bg-slate-900/80 text-white text-sm font-semibold px-4 py-2 rounded-full flex items-center gap-2"><Icon name="sold" :size="16" /> Vendido</span>
        </div>
      </div>
      <div v-if="item.media?.length > 1" class="flex gap-2 mt-2.5 overflow-x-auto pb-1">
        <button v-for="(m, i) in item.media" :key="m.media_id" @click="active = i"
          class="w-16 h-16 rounded-xl overflow-hidden shrink-0 border-2 transition"
          :class="i === active ? 'border-brand' : 'border-transparent opacity-70 hover:opacity-100'" :aria-label="`Imagen ${i+1}`">
          <img :src="mediaUrl(m.url)" class="w-full h-full object-cover" alt="" />
        </button>
      </div>
    </div>

    <div>
      <div class="flex items-center gap-2 mb-1.5"><StatusBadge :status="item.status" /></div>
      <h1 class="font-display font-extrabold text-2xl sm:text-3xl leading-tight">{{ item.name }}</h1>
      <p class="font-display font-extrabold text-3xl mt-1.5" :class="item.status === 'sold' ? 'text-slate-400 line-through' : 'text-brand-700'">{{ money(item.price) }}</p>

      <div v-if="sizes.length" class="mt-4">
        <h3 class="field-label mb-1.5">Tallas disponibles</h3>
        <div class="flex flex-wrap gap-1.5">
          <span v-for="s in sizes" :key="s.id"
            class="min-w-[2.5rem] text-center px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-sm font-semibold text-slate-700">
            {{ s.label }}
          </span>
        </div>
      </div>

      <div class="flex flex-wrap gap-2 mt-4">
        <span class="badge bg-slate-100 text-slate-600">{{ item.category?.name }}</span>
        <span class="badge bg-slate-100 text-slate-600">
          <span class="w-3 h-3 rounded-full border border-slate-300" :style="{ background: item.color?.hex }"></span>{{ item.color?.name }}
        </span>
      </div>

      <div v-if="item.description" class="mt-4">
        <h3 class="field-label mb-1">Descripción</h3>
        <p class="text-slate-600 whitespace-pre-line leading-relaxed text-[15px]">{{ item.description }}</p>
      </div>

      <div class="card p-3 mt-4 flex items-center gap-3">
        <span class="w-10 h-10 rounded-xl bg-brand-100 text-brand-800 grid place-items-center font-bold uppercase">{{ item.owner_name?.[0] || 'U' }}</span>
        <div class="min-w-0">
          <p class="text-xs text-slate-400">Publicado por</p>
          <p class="font-semibold text-slate-800 truncate">{{ item.owner_name || 'Usuario' }}</p>
        </div>
      </div>

      <div class="mt-5 flex gap-3">
        <button v-if="!isMine" @click="contact" :disabled="starting || item.status === 'sold'" class="btn btn-primary btn-lg flex-1 sm:flex-none">
          <Spinner v-if="starting" :size="18" light /><Icon v-else name="message" :size="18" />
          {{ item.status === 'sold' ? 'No disponible' : 'Contactar al vendedor' }}
        </button>
        <router-link v-else :to="`/items/${item.id}/edit`" class="btn btn-ghost btn-lg"><Icon name="edit" :size="18" /> Editar publicación</router-link>
      </div>
    </div>
  </div>
</template>
