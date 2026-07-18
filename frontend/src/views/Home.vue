<script setup>
import { ref, onMounted } from 'vue'
import api from '../lib/api'
import ItemCard from '../components/ItemCard.vue'
import ProductCardSkeleton from '../components/ui/ProductCardSkeleton.vue'
import EmptyState from '../components/ui/EmptyState.vue'
import Icon from '../components/ui/Icon.vue'
import { useAuth } from '../store/auth'
const auth = useAuth()
const items = ref([]); const loading = ref(true); const error = ref(false)
async function load() {
  loading.value = true; error.value = false
  try { const { data } = await api.get('/catalog', { params: { per_page: 10 } }); items.value = data.data }
  catch (e) { error.value = true } finally { loading.value = false }
}
onMounted(load)
</script>
<template>
  <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-700 via-brand-600 to-teal-500 text-white px-6 sm:px-10 py-10 sm:py-14 shadow-card">
    <div class="absolute -right-16 -top-16 w-64 h-64 rounded-full bg-white/10"></div>
    <div class="absolute -left-10 -bottom-20 w-56 h-56 rounded-full bg-white/10"></div>
    <div class="relative max-w-xl animate-fade-up">
      <span class="inline-flex items-center gap-1.5 text-xs font-semibold bg-white/15 rounded-full px-3 py-1 mb-4">
        <Icon name="recycle" :size="14" /> Moda circular
      </span>
      <h1 class="font-display text-3xl sm:text-5xl font-extrabold leading-tight">Dale una segunda vida a tu ropa</h1>
      <p class="text-white/85 mt-3 sm:text-lg">Publica, descubre y conversa en tiempo real con personas interesadas en tus prendas.</p>
      <div class="mt-6 flex flex-wrap gap-3">
        <router-link to="/catalog" class="btn btn-lg bg-white text-brand-700 hover:bg-brand-50">
          <Icon name="compass" :size="18" /> Explorar catálogo
        </router-link>
        <router-link v-if="!auth.isAuthed" to="/register" class="btn btn-lg bg-white/15 text-white hover:bg-white/25 border-white/20">Crear cuenta</router-link>
        <router-link v-else to="/items/new" class="btn btn-lg bg-white/15 text-white hover:bg-white/25 border-white/20">
          <Icon name="plus" :size="18" /> Publicar prenda
        </router-link>
      </div>
    </div>
  </section>

  <section class="mt-8">
    <div class="flex items-center justify-between mb-4">
      <h2 class="font-display font-bold text-xl">Recién publicadas</h2>
      <router-link to="/catalog" class="text-sm text-brand-700 font-medium hover:underline inline-flex items-center gap-1">
        Ver todo <Icon name="chevronRight" :size="15" />
      </router-link>
    </div>

    <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
      <ProductCardSkeleton v-for="i in 10" :key="i" />
    </div>

    <EmptyState v-else-if="error" icon="warning" title="No pudimos cargar el catálogo"
      subtitle="Revisa tu conexión e inténtalo otra vez.">
      <button class="btn btn-primary" @click="load"><Icon name="retry" :size="16" /> Reintentar</button>
    </EmptyState>

    <EmptyState v-else-if="!items.length" icon="bag" title="Aún no hay prendas publicadas"
      subtitle="Sé la primera persona en publicar algo increíble.">
      <router-link v-if="auth.isAuthed" to="/items/new" class="btn btn-primary">Publicar prenda</router-link>
      <router-link v-else to="/register" class="btn btn-primary">Crear cuenta</router-link>
    </EmptyState>

    <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
      <div v-for="(i, idx) in items" :key="i.id" class="animate-fade-up" :style="{ animationDelay: idx * 40 + 'ms' }">
        <ItemCard :item="i" />
      </div>
    </div>
  </section>
</template>
