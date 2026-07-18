<script setup>
import { useAuth } from '../store/auth'
const auth = useAuth()
</script>
<template>
  <div class="max-w-md mx-auto bg-white border rounded-xl p-6">
    <h1 class="text-xl font-semibold mb-4">Mi perfil</h1>
    <div class="space-y-2">
      <div><p class="text-xs text-slate-400">Nombre</p><p class="font-medium">{{ auth.user?.name }}</p></div>
      <div><p class="text-xs text-slate-400">Correo</p><p class="font-medium">{{ auth.user?.email }}</p></div>
    </div>
    <router-link to="/my-items" class="inline-block mt-5 text-brand text-sm">Ir a mi armario →</router-link>
  </div>
</template>
