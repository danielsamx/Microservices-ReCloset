<script setup>
import { ref, onMounted } from 'vue'
import api from '../lib/api'
import ItemCard from '../components/ItemCard.vue'
const items = ref([]); const loading = ref(true)
onMounted(async () => {
  try { const { data } = await api.get('/catalog', { params: { per_page: 8 } }); items.value = data.data } finally { loading.value = false }
})
</script>
<template>
  <section class="text-center py-12">
    <h1 class="text-4xl font-bold">Dale una segunda vida a tu ropa</h1>
    <p class="text-slate-500 mt-3 max-w-xl mx-auto">Publica, explora y conversa en tiempo real con otras personas interesadas en tus prendas.</p>
    <div class="mt-6 flex justify-center gap-3">
      <router-link to="/catalog" class="bg-brand text-white px-5 py-2.5 rounded-lg hover:bg-brand-dark">Explorar catálogo</router-link>
      <router-link to="/register" class="border px-5 py-2.5 rounded-lg hover:bg-white">Crear cuenta</router-link>
    </div>
  </section>
  <section>
    <h2 class="font-semibold mb-3">Recién publicadas</h2>
    <div v-if="loading" class="text-slate-400">Cargando…</div>
    <div v-else-if="!items.length" class="text-slate-400">Aún no hay prendas publicadas.</div>
    <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <ItemCard v-for="i in items" :key="i.id" :item="i" />
    </div>
  </section>
</template>
