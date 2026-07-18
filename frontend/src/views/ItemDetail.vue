<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../lib/api'
import { mediaUrl, money } from '../lib/media'
import { useAuth } from '../store/auth'
const route = useRoute(); const router = useRouter(); const auth = useAuth()
const item = ref(null); const active = ref(0); const loading = ref(true); const starting = ref(false); const err = ref('')
const isMine = computed(() => auth.user && item.value && item.value.owner_id === auth.user.id)
onMounted(async () => {
  try { const { data } = await api.get(`/catalog/${route.params.id}`); item.value = data.item }
  catch (e) { err.value = 'No se encontró la prenda.' } finally { loading.value = false }
})
async function contact() {
  if (!auth.isAuthed) return router.push('/login')
  starting.value = true; err.value = ''
  try {
    const { data } = await api.post('/conversations', { item_id: item.value.id })
    router.push(`/chat/${data.conversation.id}`)
  } catch (e) { err.value = e.response?.data?.message || 'No se pudo iniciar el chat.' }
  finally { starting.value = false }
}
</script>
<template>
  <div v-if="loading" class="text-slate-400">Cargando…</div>
  <div v-else-if="!item" class="text-center text-slate-400 py-16">{{ err }}</div>
  <div v-else class="grid md:grid-cols-2 gap-8">
    <div>
      <div class="aspect-square bg-slate-100 rounded-xl overflow-hidden">
        <img v-if="item.media?.length" :src="mediaUrl(item.media[active].url)" class="w-full h-full object-cover" />
        <div v-else class="w-full h-full grid place-items-center text-6xl text-slate-300">👕</div>
      </div>
      <div v-if="item.media?.length > 1" class="flex gap-2 mt-3">
        <img v-for="(m, i) in item.media" :key="m.media_id" :src="mediaUrl(m.url)" @click="active = i"
          class="w-16 h-16 object-cover rounded-lg cursor-pointer border" :class="{ 'ring-2 ring-brand': i === active }" />
      </div>
    </div>
    <div>
      <h1 class="text-2xl font-bold">{{ item.name }}</h1>
      <p class="text-2xl text-brand font-semibold mt-1">{{ money(item.price) }}</p>
      <p class="text-sm text-slate-400 mt-1">Vendedor: {{ item.owner_name || 'Usuario' }}</p>
      <div class="flex gap-2 mt-3 text-sm">
        <span class="bg-slate-100 px-2 py-1 rounded">{{ item.category?.name }}</span>
        <span class="bg-slate-100 px-2 py-1 rounded">Talla {{ item.size?.label }}</span>
        <span class="bg-slate-100 px-2 py-1 rounded">{{ item.color?.name }}</span>
      </div>
      <p class="mt-4 text-slate-600 whitespace-pre-line">{{ item.description }}</p>
      <p v-if="err" class="text-rose-500 text-sm mt-3">{{ err }}</p>
      <div class="mt-6">
        <button v-if="!isMine" @click="contact" :disabled="starting"
          class="bg-brand text-white px-5 py-2.5 rounded-lg hover:bg-brand-dark disabled:opacity-50">
          {{ starting ? '…' : 'Contactar al vendedor' }}
        </button>
        <router-link v-else :to="`/items/${item.id}/edit`" class="border px-5 py-2.5 rounded-lg hover:bg-white">Editar prenda</router-link>
      </div>
    </div>
  </div>
</template>
