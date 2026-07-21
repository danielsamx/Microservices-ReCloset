<script setup>
import { ref, onMounted, watch, nextTick, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../lib/api'
import { getEcho } from '../lib/echo'
import { mediaUrl } from '../lib/media'
import { timeAgo, clockTime, dayLabel, dayKey } from '../lib/time'
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

const mine = (m) => m.sender_id === auth.user.id
function showDay(i) {
  if (i === 0) return true
  return dayKey(messages.value[i].created_at) !== dayKey(messages.value[i - 1].created_at)
}
function endsGroup(i) {
  const cur = messages.value[i], next = messages.value[i + 1]
  if (!next) return true
  if (next.sender_id !== cur.sender_id) return true
  if (dayKey(next.created_at) !== dayKey(cur.created_at)) return true
  return (new Date(next.created_at) - new Date(cur.created_at)) > 5 * 60 * 1000
}

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
  <div class="flex items-center justify-between mb-3">
    <h1 class="font-display font-bold text-xl sm:text-2xl">Mensajes</h1>
    <router-link to="/catalog" class="btn btn-ghost btn-sm hidden sm:inline-flex"><Icon name="compass" :size="15" /> Explorar</router-link>
  </div>

  <div class="grid md:grid-cols-[330px_1fr] gap-3 h-[calc(100dvh-14.5rem)] md:h-[calc(100dvh-11.5rem)] min-h-[380px]">
    <aside class="card overflow-hidden flex flex-col" :class="{ 'hidden md:flex': active }">
      <div class="px-3 py-2.5 border-b border-slate-100 shrink-0 flex items-center gap-2">
        <p class="font-semibold text-slate-700 text-sm">Conversaciones</p>
        <span v-if="conversations.length" class="badge bg-slate-100 text-slate-500 ml-auto">{{ conversations.length }}</span>
      </div>
      <div class="overflow-y-auto flex-1">
        <div v-if="loadingList" class="p-3 space-y-3">
          <div v-for="i in 5" :key="i" class="flex gap-3 items-center">
            <Skeleton h="2.6rem" w="2.6rem" rounded="0.7rem" />
            <div class="flex-1 space-y-2"><Skeleton h=".8rem" w="60%" /><Skeleton h=".7rem" w="85%" /></div>
          </div>
        </div>
        <EmptyState v-else-if="!conversations.length" icon="message" title="Sin conversaciones"
          subtitle="Contacta a un vendedor desde una publicación para empezar a chatear." />
        <button v-for="c in conversations" :key="c.id" @click="router.push(`/chat/${c.id}`)"
          class="w-full text-left px-3 py-2.5 border-b border-slate-50 hover:bg-slate-50 flex gap-3 items-center transition"
          :class="{ 'bg-brand-50/70': active?.id === c.id }">
          <img v-if="c.item.thumb" :src="mediaUrl(c.item.thumb)" class="w-11 h-11 rounded-xl object-cover shrink-0" alt="" />
          <div v-else class="w-11 h-11 rounded-xl bg-slate-100 grid place-items-center shrink-0 text-slate-300"><Icon name="shirt" :size="20" /></div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <p class="text-sm font-semibold text-slate-800 truncate">{{ c.other.name || 'Usuario' }}</p>
              <span class="ml-auto text-[11px] text-slate-400 shrink-0">{{ timeAgo(c.last_message_at) }}</span>
            </div>
            <p class="text-[11px] text-slate-400 truncate">{{ c.item.title }}</p>
            <div class="flex items-center gap-2">
              <p class="text-xs truncate" :class="c.unread ? 'text-slate-800 font-medium' : 'text-slate-500'">{{ c.last_message || 'Sin mensajes' }}</p>
              <span v-if="c.unread" class="ml-auto bg-brand text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] px-1 grid place-items-center shrink-0">{{ c.unread }}</span>
            </div>
          </div>
        </button>
      </div>
    </aside>

    <section class="card flex flex-col overflow-hidden" :class="{ 'hidden md:flex': !active }">
      <div v-if="!active && !loadingThread" class="flex-1 grid place-items-center">
        <EmptyState icon="messages" title="Selecciona una conversación" subtitle="Elige un chat de la lista para ver los mensajes." />
      </div>
      <template v-else>
        <header class="px-3 py-2.5 border-b border-slate-100 flex items-center gap-2.5 shrink-0">
          <button @click="router.push('/chat')" class="md:hidden w-9 h-9 grid place-items-center rounded-lg hover:bg-slate-100 shrink-0" aria-label="Volver a conversaciones"><Icon name="back" :size="18" /></button>
          <template v-if="active">
            <span class="w-9 h-9 rounded-lg bg-brand-100 text-brand-800 grid place-items-center font-bold uppercase shrink-0">{{ active.other.name?.[0] || 'U' }}</span>
            <div class="min-w-0 flex-1">
              <p class="font-semibold text-slate-800 truncate leading-tight text-sm">{{ active.other.name || 'Usuario' }}</p>
              <router-link :to="`/items/${active.item.id}`" class="text-xs text-brand-700 hover:underline truncate block">{{ active.item.title }}</router-link>
            </div>
            <router-link :to="`/items/${active.item.id}`" class="btn btn-soft btn-sm hidden sm:inline-flex">Ver publicación</router-link>
            <button @click="askRemove(active.id)" class="w-9 h-9 grid place-items-center rounded-xl text-slate-400 hover:text-danger-600 hover:bg-danger-50 transition shrink-0" aria-label="Eliminar conversación"><Icon name="trash" :size="17" /></button>
          </template>
        </header>

        <div ref="scroller" class="flex-1 overflow-y-auto px-3 py-3 bg-slate-50/60">
          <div v-if="loadingThread" class="flex justify-center py-8"><Spinner :size="24" /></div>
          <template v-else>
            <div v-if="!messages.length" class="text-center py-10">
              <div class="w-12 h-12 rounded-2xl bg-white border border-slate-100 grid place-items-center mx-auto text-brand-600 mb-2"><Icon name="send" :size="20" /></div>
              <p class="text-sm text-slate-500">Envía el primer mensaje</p>
              <p class="text-xs text-slate-400 mt-0.5">Pregunta por tallas, estado o disponibilidad.</p>
            </div>

            <template v-for="(m, i) in messages" :key="m.id">
              <div v-if="showDay(i)" class="flex justify-center my-3">
                <span class="text-[11px] font-medium text-slate-500 bg-white border border-slate-200 rounded-full px-2.5 py-0.5">{{ dayLabel(m.created_at) }}</span>
              </div>
              <div class="flex animate-fade-up" :class="[mine(m) ? 'justify-end' : 'justify-start', endsGroup(i) ? 'mb-2.5' : 'mb-0.5']">
                <div class="max-w-[80%] sm:max-w-[70%]">
                  <div class="px-3.5 py-2 text-sm leading-relaxed break-words shadow-soft rounded-2xl"
                    :class="[
                      mine(m) ? 'bg-brand text-white' : 'bg-white text-slate-700 border border-slate-100',
                      endsGroup(i) ? (mine(m) ? 'rounded-br-md' : 'rounded-bl-md') : ''
                    ]">
                    {{ m.body }}
                  </div>
                  <p v-if="endsGroup(i)" class="text-[10.5px] text-slate-400 mt-0.5 px-1" :class="mine(m) ? 'text-right' : 'text-left'">
                    {{ clockTime(m.created_at) }}
                  </p>
                </div>
              </div>
            </template>
          </template>
        </div>

        <form @submit.prevent="send" class="p-2.5 border-t border-slate-100 flex items-end gap-2 shrink-0 bg-white">
          <input v-model="draft" placeholder="Escribe un mensaje…" class="input rounded-2xl flex-1 min-w-0" aria-label="Escribe un mensaje" autocomplete="off" />
          <button class="btn btn-primary rounded-2xl !px-3.5 shrink-0" :disabled="sending || !draft.trim()" aria-label="Enviar mensaje">
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
