<script setup>
import { ref, onMounted } from 'vue'
import api from '../lib/api'
import ItemCard from '../components/ItemCard.vue'
import ProductCardSkeleton from '../components/ui/ProductCardSkeleton.vue'
import EmptyState from '../components/ui/EmptyState.vue'
import Icon from '../components/ui/Icon.vue'
import { useAuth } from '../store/auth'
import logotipoUrl from '../public/logotipo - recloset.png'
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
  <section class="hero-section overflow-hidden rounded-4xl shadow-glow mb-8">
    <!-- Fondo con gradiente continuo blanco → verde -->
    <div class="hero-bg absolute inset-0 pointer-events-none"></div>

    <!-- Blobs flotantes (solo en la zona verde inferior) -->
    <div class="absolute right-0 bottom-0 w-80 h-80 rounded-full bg-lime-400/15 blur-3xl animate-float pointer-events-none"></div>
    <div class="absolute -left-10 bottom-10 w-56 h-56 rounded-full bg-mint/12 blur-2xl animate-float-slow pointer-events-none"></div>

    <!-- Grid decorativo sutil (cubre todo pero se nota más en la zona blanca) -->
    <div class="absolute inset-0 opacity-[0.025] pointer-events-none" style="background-image: linear-gradient(#10b981 1px, transparent 1px), linear-gradient(90deg, #10b981 1px, transparent 1px); background-size: 36px 36px;"></div>

    <!-- Contenido -->
    <div class="relative z-10 flex flex-col items-center text-center px-6 sm:px-12 pt-10 sm:pt-14 pb-12 sm:pb-16">
      <!-- Logotipo con animación sweep -->
      <div class="logo-sweep-container mb-6 sm:mb-8">
        <img :src="logotipoUrl" alt="ReCloset Logotipo" class="logo-sweep-image h-28 sm:h-40 object-contain" />
      </div>

      <!-- Título y contenido (ya en la zona que transiciona a verde) -->
      <div class="max-w-2xl flex flex-col items-center">
        <h1 class="font-display text-3xl sm:text-5xl font-extrabold leading-[1.1] tracking-tight hero-title">
          Dale una <span class="relative inline-block">segunda vida
            <svg class="absolute -bottom-1 left-0 w-full" height="8" viewBox="0 0 200 10" preserveAspectRatio="none"><path d="M2 7 Q 100 -2 198 6" stroke="#a3e635" stroke-width="4" fill="none" stroke-linecap="round"/></svg>
          </span> a tu ropa
        </h1>
        <p class="hero-subtitle mt-4 text-sm sm:text-lg max-w-xl">
          Publica, descubre y conversa en tiempo real con personas interesadas en tus prendas.
        </p>
        <div class="mt-7 flex flex-wrap justify-center gap-3">
          <router-link to="/catalog" class="btn btn-lg bg-white !text-brand-700 hover:bg-brand-50 shadow-xl hover:scale-[1.03] transition-transform">
            <Icon name="compass" :size="18" /> Explorar catálogo
          </router-link>
          <router-link v-if="!auth.isAuthed" to="/register" class="btn btn-lg glass !bg-white/15 !border-white/30 text-white hover:!bg-white/25 hover:scale-[1.03] transition-transform">Crear cuenta</router-link>
          <router-link v-else to="/items/new" class="btn btn-lg glass !bg-white/15 !border-white/30 text-white hover:!bg-white/25 hover:scale-[1.03] transition-transform">
            <Icon name="plus" :size="18" /> Publicar prenda
          </router-link>
        </div>
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

<style scoped>
.hero-section {
  position: relative;
  border: 1px solid rgba(16, 185, 129, 0.12);
}

.hero-bg {
  background: linear-gradient(
    180deg,
    #ffffff 0%,
    #f0fdf8 18%,
    #d1fae5 32%,
    #6ee7b7 48%,
    #10b981 62%,
    #047857 80%,
    #065f46 100%
  );
}

:global(.dark) .hero-bg {
  background: linear-gradient(
    180deg,
    #0a0a0a 0%,
    #071210 18%,
    #052e21 32%,
    #064e3b 48%,
    #047857 62%,
    #065f46 80%,
    #022c22 100%
  );
}

.hero-title {
  color: #fff;
  text-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
}

.hero-subtitle {
  color: rgba(255, 255, 255, 0.92);
}

/* Sweep animation */
.logo-sweep-container {
  position: relative;
  display: inline-block;
  overflow: hidden;
}

.logo-sweep-container::after {
  content: '';
  position: absolute;
  top: 0;
  left: -150%;
  width: 60%;
  height: 100%;
  background: linear-gradient(
    90deg,
    transparent 0%,
    rgba(16, 185, 129, 0.12) 30%,
    rgba(16, 185, 129, 0.28) 50%,
    rgba(16, 185, 129, 0.12) 70%,
    transparent 100%
  );
  transform: skewX(-25deg);
  animation: logo-sweep-shine 6s ease-in-out infinite;
  pointer-events: none;
}

.logo-sweep-image {
  animation: logo-sweep-reveal 6s cubic-bezier(0.4, 0, 0.2, 1) infinite;
}

@keyframes logo-sweep-reveal {
  0% {
    clip-path: inset(0 100% 0 0);
    opacity: 0;
  }
  4% {
    opacity: 1;
  }
  20% {
    clip-path: inset(0 0 0 0);
  }
  80% {
    clip-path: inset(0 0 0 0);
    opacity: 1;
  }
  96% {
    opacity: 1;
  }
  100% {
    clip-path: inset(0 0 0 100%);
    opacity: 0;
  }
}

@keyframes logo-sweep-shine {
  0%, 20% {
    left: -150%;
  }
  50%, 100% {
    left: 150%;
  }
}
</style>
