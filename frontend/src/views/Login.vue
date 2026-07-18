<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../store/auth'
const auth = useAuth(); const router = useRouter()
const form = ref({ email: '', password: '' }); const error = ref(''); const loading = ref(false)
async function submit() {
  error.value = ''; loading.value = true
  try { await auth.login(form.value); router.push('/catalog') }
  catch (e) { error.value = e.response?.data?.errors?.email?.[0] || 'No se pudo iniciar sesión.' }
  finally { loading.value = false }
}
</script>
<template>
  <div class="max-w-sm mx-auto bg-white p-6 rounded-xl border mt-6">
    <h1 class="text-xl font-semibold mb-4">Iniciar sesión</h1>
    <p v-if="error" class="bg-rose-50 text-rose-600 text-sm p-2 rounded mb-3">{{ error }}</p>
    <form @submit.prevent="submit" class="space-y-3">
      <input v-model="form.email" type="email" required placeholder="Correo" class="w-full border rounded-lg px-3 py-2" />
      <input v-model="form.password" type="password" required placeholder="Contraseña" class="w-full border rounded-lg px-3 py-2" />
      <button :disabled="loading" class="w-full bg-brand text-white py-2 rounded-lg hover:bg-brand-dark disabled:opacity-50">{{ loading ? '…' : 'Entrar' }}</button>
    </form>
    <p class="text-sm text-slate-500 mt-3">¿No tienes cuenta? <router-link to="/register" class="text-brand">Regístrate</router-link></p>
  </div>
</template>
