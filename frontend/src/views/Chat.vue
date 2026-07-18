<script setup>
import { ref, onMounted, watch, nextTick, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../lib/api'
import { getEcho } from '../lib/echo'
import { mediaUrl } from '../lib/media'
import { useAuth } from '../store/auth'

const route = useRoute(); const router = useRouter(); const auth = useAuth()
const conversations = ref([]); const active = ref(null); const messages = ref([])
const draft = ref(''); const loadingThread = ref(false); const channel = ref(null); const scroller = ref(null)

async function loadList() { const { data } = await api.get('/conversations'); conversations.value = data.conversations }

async function openThread(id) {
  loadingThread.value = true
  try {
    const { data } = await api.get(`/conversations/${id}`)
    active.value = data.conversation; messages.value = data.messages
    subscribe(id)
    const c = conversations.value.find((x) => x.id === id); if (c) c.unread = 0
    await scrollDown()
  } finally { loadingThread.value = false }
}

function subscribe(id) {
  if (channel.value) getEcho().leave(`conversation.${channel.value}`)
  channel.value = id
  getEcho().private(`conversation.${id}`).listen('.message.sent', async (e) => {
    if (!messages.value.some((m) => m.id === e.message.id)) { messages.value.push(e.message); await scrollDown() }
  })
}

async function send() {
  const body = draft.value.trim(); if (!body || !active.value) return
  draft.value = ''
  const { data } = await api.post(`/conversations/${active.value.id}/messages`, { body })
  if (!messages.value.some((m) => m.id === data.message.id)) messages.value.push(data.message)
  await scrollDown(); loadList()
}

async function removeConv(id) {
  if (!confirm('¿Eliminar esta conversación?')) return
  await api.delete(`/conversations/${id}`)
  if (active.value?.id === id) { active.value = null; messages.value = [] }
  loadList()
}

async function scrollDown() { await nextTick(); if (scroller.value) scroller.value.scrollTop = scroller.value.scrollHeight }

onMounted(async () => { await loadList(); if (route.params.id) openThread(Number(route.params.id)) })
watch(() => route.params.id, (id) => { if (id) openThread(Number(id)) })
onBeforeUnmount(() => { if (channel.value) getEcho().leave(`conversation.${channel.value}`) })
</script>
<template>
  <div class="grid md:grid-cols-[320px_1fr] gap-4 h-[70vh]">
    <aside class="bg-white border rounded-xl overflow-y-auto">
      <h2 class="font-semibold p-3 border-b">Conversaciones</h2>
      <p v-if="!conversations.length" class="text-sm text-slate-400 p-4">No tienes conversaciones.</p>
      <button v-for="c in conversations" :key="c.id" @click="router.push(`/chat/${c.id}`)"
        class="w-full text-left p-3 border-b hover:bg-slate-50 flex gap-3 items-center"
        :class="{ 'bg-teal-50': active?.id === c.id }">
        <img v-if="c.item.thumb" :src="mediaUrl(c.item.thumb)" class="w-10 h-10 rounded object-cover" />
        <div v-else class="w-10 h-10 rounded bg-slate-100 grid place-items-center">👕</div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium truncate">{{ c.other.name || 'Usuario' }}</p>
          <p class="text-xs text-slate-400 truncate">{{ c.item.title }} · {{ c.last_message || '—' }}</p>
        </div>
        <span v-if="c.unread" class="bg-brand text-white text-[10px] rounded-full px-1.5">{{ c.unread }}</span>
      </button>
    </aside>

    <section class="bg-white border rounded-xl flex flex-col">
      <div v-if="!active" class="flex-1 grid place-items-center text-slate-400">Selecciona una conversación</div>
      <template v-else>
        <header class="p-3 border-b flex items-center justify-between">
          <div>
            <p class="font-medium">{{ active.other.name || 'Usuario' }}</p>
            <router-link :to="`/items/${active.item.id}`" class="text-xs text-brand">{{ active.item.title }}</router-link>
          </div>
          <button @click="removeConv(active.id)" class="text-xs text-rose-500 hover:underline">Eliminar</button>
        </header>
        <div ref="scroller" class="flex-1 overflow-y-auto p-4 space-y-2">
          <div v-for="m in messages" :key="m.id" class="flex" :class="m.sender_id === auth.user.id ? 'justify-end' : 'justify-start'">
            <div class="max-w-[70%] px-3 py-2 rounded-2xl text-sm"
              :class="m.sender_id === auth.user.id ? 'bg-brand text-white' : 'bg-slate-100'">
              {{ m.body }}
            </div>
          </div>
        </div>
        <form @submit.prevent="send" class="p-3 border-t flex gap-2">
          <input v-model="draft" placeholder="Escribe un mensaje…" class="flex-1 border rounded-full px-4 py-2 text-sm" />
          <button class="bg-brand text-white px-4 rounded-full hover:bg-brand-dark">Enviar</button>
        </form>
      </template>
    </section>
  </div>
</template>
