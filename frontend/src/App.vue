<script setup>
import { onMounted, watch } from 'vue'
import Navbar from './components/Navbar.vue'
import MobileNav from './components/MobileNav.vue'
import ToastHost from './components/ui/ToastHost.vue'
import NotificationsModal from './components/NotificationsModal.vue'
import { useAuth } from './store/auth'
import { useNotifications } from './store/notifications'

const auth = useAuth()
const notif = useNotifications()

function boot() {
  if (auth.isAuthed) { notif.load().catch(() => {}); notif.subscribe() }
}
onMounted(boot)
watch(() => auth.token, boot)
</script>

<template>
  <Navbar />
  <main class="max-w-6xl mx-auto px-4 sm:px-5 py-5 sm:py-7 pb-24 md:pb-10 min-h-[70vh]">
    <router-view v-slot="{ Component, route }">
      <transition name="route" mode="out-in">
        <div :key="route.path">
          <component :is="Component" />
        </div>
      </transition>
    </router-view>
  </main>
  <MobileNav />
  <ToastHost />
  <NotificationsModal />
</template>
