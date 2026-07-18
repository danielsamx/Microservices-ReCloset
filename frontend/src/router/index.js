import { createRouter, createWebHashHistory } from 'vue-router'
import { useAuth } from '../store/auth'

const routes = [
  { path: '/', component: () => import('../views/Home.vue') },
  { path: '/login', component: () => import('../views/Login.vue') },
  { path: '/register', component: () => import('../views/Register.vue') },
  { path: '/catalog', component: () => import('../views/Catalog.vue') },
  { path: '/items/:id', component: () => import('../views/ItemDetail.vue') },
  { path: '/my-items', component: () => import('../views/MyItems.vue'), meta: { auth: true } },
  { path: '/items/new', component: () => import('../views/ItemForm.vue'), meta: { auth: true } },
  { path: '/items/:id/edit', component: () => import('../views/ItemForm.vue'), meta: { auth: true } },
  { path: '/chat', component: () => import('../views/Chat.vue'), meta: { auth: true } },
  { path: '/chat/:id', component: () => import('../views/Chat.vue'), meta: { auth: true } },
  { path: '/notifications', component: () => import('../views/Notifications.vue'), meta: { auth: true } },
  { path: '/profile', component: () => import('../views/Profile.vue'), meta: { auth: true } },
]

const router = createRouter({ history: createWebHashHistory(), routes })
router.beforeEach((to) => {
  const auth = useAuth()
  if (to.meta.auth && !auth.isAuthed) return '/login'
})
export default router
