<script setup>
import { ref, onMounted, watch, nextTick, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../lib/api'
import { getEcho } from '../lib/echo'
import { mediaUrl } from '../lib/media'
import { useAuth } from '../store/auth'
import { useToasts } from '../store/toasts'
import EmptyState from '../components/ui/EmptyState.vue'
import ConfirmDialog from '../components/ui/ConfirmDialog.vue'
import Skeleton from '../components/ui/Skeleton.vue'
import Spinner from '../components/ui/Spinner.vue'
import Icon from '../components/ui/Icon.vue'

const route = useRoute(); const router = useRouter(); const auth = useAuth(); const toasts = useToasts()
const conversations = ref([]); const active = ref(null); const messages = ref([])
const draft = ref(''); const loadingList = ref(true); const loadingThread = ref(false); const sending = ref(false)
const channel = ref(null); const scroller = ref(null)
const confirm = ref({ open: false, id: null, loading: false })

async function loadList() {
  loadingList.value = true
  try { const { data } = await api.get('/conversations'); conversations.value = data.conversations }
  catch (e) {} finally { loadingList.value = false }
}
async function openThread(id) {
  loadingThread.value = true; active.value = active.value?.id === id ? active.value : null
  try {
    const { data } = await api.get(`/conversations/${id}`)
    active.value = data.conversation; messages.value = data.messages
    subscribe(id)
    const c = conversations.value.find((x) => x.id === id); if (c) c.unread = 0
    await scrollDown()
  } catch (e) { toasts.error('No se pudo abrir la conversación') }
  finally { loadingThread.value = false }
}
function subscribe(id) {
  if (channel.value) getEcho().leave(`conversation.${channel.value}`)
  channel.value = id
  getEcho().private(`conversation.${id}`).listen('.message.sent', async (e) => {
    if (!messages.value.some((m) => m.id === e.message.id)) { messages.value.push(e.message); await scrollDown() }
  })
}
async function send() {
  const body = draft.value.trim(); if (!body || !active.value || sending.value) return
  sending.value = true; draft.value = ''
  try {
    const { data } = await api.post(`/conversations/${active.value.id}/messages`, { body })
    if (!messages.value.some((m) => m.id === data.message.id)) messages.value.push(data.message)
    await scrollDown(); loadList()
  } catch (e) { draft.value = body; toasts.error('No se pudo enviar el mensaje') }
  finally { sending.value = false }
}
function askRemove(id) { confirm.value = { open: true, id, loading: false } }
async function doRemove() {
  confirm.value.loading = true
  try {
    await api.delete(`/conversations/${confirm.value.id}`)
    if (active.value?.id === confirm.value.id) { active.value = null; messages.value = []; router.push('/chat') }
    confirm.value.open = false; toasts.success('Conversación eliminada'); loadList()
  } catch (e) { toasts.error('No se pudo eliminar'); confirm.value.loading = false }
}
async function scrollDown() { await nextTick(); if (scroller.value) scroller.value.scrollTop = scroller.value.scrollHeight }
onMounted(async () => { await loadList(); if (route.params.id) openThread(Number(route.params.id)) })
watch(() => route.params.id, (id) => { if (id) openThread(Number(id)); else active.value = null })
onBeforeUnmount(() => { if (channel.value) getEcho().leave(`conversation.${channel.value}`) })
</script>
<template>
  <h1 class="font-display font-bold text-2xl mb-4">Mensajes</h1>
  <div class="grid md:grid-cols-[340px_1fr] gap-4 md:h-[72vh]">
    <aside class="card overflow-hidden flex flex-col" :class="{ 'hidden md:flex': active }">
      <div class="p-3 border-b border-slate-100 shrink-0">
        <p class="font-semibold text-slate-700 text-sm">Conversaciones</p>
      </div>
      <div class="overflow-y-auto flex-1">
        <div v-if="loadingList" class="p-3 space-y-3">
          <div v-for="i in 4" :key="i" class="flex gap-3 items-center">
            <Skeleton h="2.5rem" w="2.5rem" rounded="0.6rem" /><div class="flex-1 space-y-2"><Skeleton h=".8rem" w="60%" /><Skeleton h=".7rem" w="80%" /></div>
          </div>
        </div>
        <EmptyState v-else-if="!conversations.length" icon="message" title="Sin conversaciones"
          subtitle="Contacta a un vendedor desde una prenda para empezar a chatear." />
        <button v-for="c in conversations" :key="c.id" @click="router.push(`/chat/${c.id}`)"
          class="w-full text-left p-3 border-b border-slate-50 hover:bg-slate-50 flex gap-3 items-center transition"
          :class="{ 'bg-brand-50/60': active?.id === c.id }">
          <img v-if="c.item.thumb" :src="mediaUrl(c.item.thumb)" class="w-11 h-11 rounded-xl object-cover shrink-0" alt="" />
          <div v-else class="w-11 h-11 rounded-xl bg-slate-100 grid place-items-center shrink-0 text-slate-300"><Icon name="shirt" :size="20" /></div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-slate-800 truncate">{{ c.other.name || 'Usuario' }}</p>
            <p class="text-xs text-slate-400 truncate">{{ c.item.title }}</p>
            <p class="text-xs text-slate-500 truncate mt-0.5">{{ c.last_message || 'Sin mensajes' }}</p>
          </div>
          <span v-if="c.unread" class="bg-brand text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] px-1 grid place-items-center shrink-0">{{ c.unread }}</span>
        </button>
      </div>
    </aside>

    <section class="card flex flex-col overflow-hidden h-[72vh] md:h-auto" :class="{ 'hidden md:flex': !active }">
      <div v-if="!active && !loadingThread" class="flex-1 grid place-items-center">
        <EmptyState icon="messages" title="Selecciona una conversación" subtitle="Elige un chat de la lista para ver los mensajes." />
      </div>
      <template v-else>
        <header class="p-3 border-b border-slate-100 flex items-center gap-3 shrink-0">
          <button @click="router.push('/chat')" class="md:hidden w-9 h-9 grid place-items-center rounded-lg hover:bg-slate-100" aria-label="Volver"><Icon name="back" :size="18" /></button>
          <template v-if="active">
            <span class="w-9 h-9 rounded-lg bg-brand-100 text-brand-800 grid place-items-center font-bold uppercase shrink-0">{{ active.other.name?.[0] || 'U' }}</span>
            <div class="min-w-0">
              <p class="font-semibold text-slate-800 truncate leading-tight">{{ active.other.name || 'Usuario' }}</p>
              <router-link :to="`/items/${active.item.id}`" class="text-xs text-brand-700 hover:underline truncate block">{{ active.item.title }}</router-link>
            </div>
            <button @click="askRemove(active.id)" class="ml-auto w-9 h-9 grid place-items-center rounded-xl text-rose-500 hover:bg-rose-50 transition shrink-0" aria-label="Eliminar conversación"><Icon name="trash" :size="18" /></button>
          </template>
        </header>

        <div ref="scroller" class="flex-1 overflow-y-auto p-4 space-y-2 bg-slate-50/50">
          <div v-if="loadingThread" class="flex justify-center py-6"><Spinner :size="24" /></div>
          <template v-else>
            <div v-if="!messages.length" class="text-center text-sm text-slate-400 py-8">Envía el primer mensaje 👋</div>
            <div v-for="m in messages" :key="m.id" class="flex" :class="m.sender_id === auth.user.id ? 'justify-end' : 'justify-start'">
              <div class="max-w-[78%] px-3.5 py-2 rounded-2xl text-sm shadow-soft animate-fade-up"
                :class="m.sender_id === auth.user.id ? 'bg-brand text-white rounded-br-md' : 'bg-white text-slate-700 rounded-bl-md'">
                {{ m.body }}
              </div>
            </div>
          </template>
        </div>

        <form @submit.prevent="send" class="p-3 border-t border-slate-100 flex gap-2 shrink-0">
          <input v-model="draft" placeholder="Escribe un mensaje…" class="input rounded-full" aria-label="Escribe un mensaje" />
          <button class="btn btn-primary rounded-full !px-4" :disabled="sending || !draft.trim()" aria-label="Enviar mensaje">
            <Spinner v-if="sending" :size="18" light /><Icon v-else name="send" :size="18" />
          </button>
        </form>
      </template>
    </section>
  </div>

  <ConfirmDialog :open="confirm.open" danger :loading="confirm.loading"
    title="Eliminar conversación"
    message="¿Eliminar esta conversación y todos sus mensajes? Esta acción no se puede deshacer."
    confirmText="Eliminar" @confirm="doRemove" @cancel="confirm.open = false" />
</template>
