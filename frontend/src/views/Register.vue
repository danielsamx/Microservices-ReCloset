<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../store/auth'
const auth = useAuth(); const router = useRouter()
const form = ref({ name: '', email: '', password: '', password_confirmation: '' })
const errors = ref({}); const loading = ref(false)
async function submit() {
  errors.value = {}; loading.value = true
  try { await auth.register(form.value); router.push('/catalog') }
  catch (e) { errors.value = e.response?.data?.errors || { email: ['No se pudo registrar.'] } }
  finally { loading.value = false }
}
</script>
<template>
  <div class="max-w-sm mx-auto bg-white p-6 rounded-xl border mt-6">
    <h1 class="text-xl font-semibold mb-4">Crear cuenta</h1>
    <form @submit.prevent="submit" class="space-y-3">
      <div>
        <input v-model="form.name" required placeholder="Nombre" class="w-full border rounded-lg px-3 py-2" />
        <p v-if="errors.name" class="text-xs text-rose-500 mt-1">{{ errors.name[0] }}</p>
      </div>
      <div>
        <input v-model="form.email" type="email" required placeholder="Correo" class="w-full border rounded-lg px-3 py-2" />
        <p v-if="errors.email" class="text-xs text-rose-500 mt-1">{{ errors.email[0] }}</p>
      </div>
      <div>
        <input v-model="form.password" type="password" required placeholder="Contraseña (8+, mayús, número)" class="w-full border rounded-lg px-3 py-2" />
        <p v-if="errors.password" class="text-xs text-rose-500 mt-1">{{ errors.password[0] }}</p>
      </div>
      <input v-model="form.password_confirmation" type="password" required placeholder="Confirmar contraseña" class="w-full border rounded-lg px-3 py-2" />
      <button :disabled="loading" class="w-full bg-brand text-white py-2 rounded-lg hover:bg-brand-dark disabled:opacity-50">{{ loading ? '…' : 'Registrarme' }}</button>
    </form>
  </div>
</template>
