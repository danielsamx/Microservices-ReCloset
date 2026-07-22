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
  <section class="relative overflow-hidden rounded-4xl text-white px-6 sm:px-12 py-12 sm:py-20 shadow-glow"
    style="background: linear-gradient(120deg, #065f46 0%, #047857 30%, #10b981 65%, #34d399 100%); background-size: 200% 200%;"
    :class="'animate-gradient-shift'">
    <!-- Blobs flotantes -->
    <div class="absolute -right-20 -top-24 w-72 h-72 rounded-full bg-lime-400/25 blur-2xl animate-float"></div>
    <div class="absolute right-24 top-16 w-40 h-40 rounded-full bg-white/10 blur-xl animate-float-slow"></div>
    <div class="absolute -left-16 -bottom-24 w-64 h-64 rounded-full bg-mint/20 blur-2xl animate-float-slow"></div>
    <!-- Grid decorativo -->
    <div class="absolute inset-0 opacity-[0.07]" style="background-image: linear-gradient(white 1px, transparent 1px), linear-gradient(90deg, white 1px, transparent 1px); background-size: 44px 44px;"></div>

    <div class="relative max-w-2xl animate-fade-up">
      <span class="inline-flex items-center gap-1.5 text-xs font-semibold glass !bg-white/15 !border-white/25 rounded-full px-3.5 py-1.5 mb-5">
        <Icon name="leaf" :size="14" /> Moda circular · Sostenible
      </span>
      <h1 class="font-display text-4xl sm:text-6xl font-extrabold leading-[1.05] tracking-tight">
        Dale una <span class="relative inline-block">segunda vida
          <svg class="absolute -bottom-1 left-0 w-full" height="10" viewBox="0 0 200 10" preserveAspectRatio="none"><path d="M2 7 Q 100 -2 198 6" stroke="#a3e635" stroke-width="4" fill="none" stroke-linecap="round"/></svg>
        </span> a tu ropa
      </h1>
      <p class="text-white/90 mt-5 text-base sm:text-xl max-w-xl">Publica, descubre y conversa en tiempo real con personas interesadas en tus prendas.</p>
      <div class="mt-8 flex flex-wrap gap-3">
        <router-link to="/catalog" class="btn btn-lg bg-white !text-brand-700 hover:bg-brand-50 shadow-xl hover:scale-[1.03]">
          <Icon name="compass" :size="18" /> Explorar catálogo
        </router-link>
        <router-link v-if="!auth.isAuthed" to="/register" class="btn btn-lg glass !bg-white/15 !border-white/30 text-white hover:!bg-white/25">Crear cuenta</router-link>
        <router-link v-else to="/items/new" class="btn btn-lg glass !bg-white/15 !border-white/30 text-white hover:!bg-white/25">
          <Icon name="plus" :size="18" /> Publicar prenda
        </router-link>
      </div>
    </div>
  </section>

  <section class="mt-8">
    <div class="flex items-center justify-between mb-4">
      <h2 class="font-display font-bold text-xl sm:text-2xl">Recién publicadas</h2>
      <router-link to="/catalog" class="text-sm text-brand-700 dark:text-brand-300 font-semibold hover:gap-2 inline-flex items-center gap-1 transition-all">
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
