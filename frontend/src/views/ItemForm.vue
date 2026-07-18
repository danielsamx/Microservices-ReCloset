<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../lib/api'
import { mediaUrl } from '../lib/media'
const route = useRoute(); const router = useRouter()
const editing = computed(() => !!route.params.id)
const meta = ref({ categories: [], sizes: [], colors: [] })
const form = ref({ name: '', description: '', category_id: '', size_id: '', color_id: '', price: '' })
const files = ref([]); const existing = ref([]); const errors = ref({}); const saving = ref(false); const gErr = ref('')

onMounted(async () => {
  const { data } = await api.get('/meta'); meta.value = data
  if (editing.value) {
    const { data: d } = await api.get(`/catalog/${route.params.id}`)
    const it = d.item
    form.value = { name: it.name, description: it.description, category_id: it.category_id, size_id: it.size_id, color_id: it.color_id, price: it.price }
    existing.value = it.media || []
  }
})
function onFiles(e) { files.value = Array.from(e.target.files) }

async function submit() {
  errors.value = {}; gErr.value = ''; saving.value = true
  try {
    if (editing.value) {
      await api.patch(`/items/${route.params.id}`, form.value)
      router.push('/my-items')
    } else {
      const fd = new FormData()
      Object.entries(form.value).forEach(([k, v]) => fd.append(k, v))
      files.value.forEach((f) => fd.append('files[]', f))
      await api.post('/items', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
      router.push('/my-items')
    }
  } catch (e) {
    errors.value = e.response?.data?.errors || {}
    gErr.value = e.response?.data?.message || 'No se pudo guardar.'
  } finally { saving.value = false }
}
</script>
<template>
  <div class="max-w-xl mx-auto bg-white border rounded-xl p-6">
    <h1 class="text-xl font-semibold mb-4">{{ editing ? 'Editar prenda' : 'Publicar prenda' }}</h1>
    <p v-if="gErr" class="bg-rose-50 text-rose-600 text-sm p-2 rounded mb-3">{{ gErr }}</p>
    <form @submit.prevent="submit" class="space-y-3">
      <div>
        <input v-model="form.name" required placeholder="Nombre" class="w-full border rounded-lg px-3 py-2" />
        <p v-if="errors.name" class="text-xs text-rose-500">{{ errors.name[0] }}</p>
      </div>
      <textarea v-model="form.description" placeholder="Descripción" rows="3" class="w-full border rounded-lg px-3 py-2"></textarea>
      <div class="grid grid-cols-3 gap-2">
        <select v-model="form.category_id" required class="border rounded-lg px-2 py-2 text-sm">
          <option value="">Categoría</option><option v-for="c in meta.categories" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
        <select v-model="form.size_id" required class="border rounded-lg px-2 py-2 text-sm">
          <option value="">Talla</option><option v-for="s in meta.sizes" :key="s.id" :value="s.id">{{ s.label }}</option>
        </select>
        <select v-model="form.color_id" required class="border rounded-lg px-2 py-2 text-sm">
          <option value="">Color</option><option v-for="c in meta.colors" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>
      <input v-model="form.price" type="number" step="0.01" min="0" required placeholder="Precio" class="w-full border rounded-lg px-3 py-2" />

      <div v-if="!editing">
        <label class="text-sm text-slate-500">Fotos / videos (1 a 8)</label>
        <input type="file" multiple accept="image/*,video/mp4,video/webm" @change="onFiles" class="w-full text-sm mt-1" />
        <p v-if="errors['files']" class="text-xs text-rose-500">{{ errors['files'][0] }}</p>
      </div>
      <div v-else-if="existing.length" class="flex gap-2 flex-wrap">
        <img v-for="m in existing" :key="m.media_id" :src="mediaUrl(m.url)" class="w-16 h-16 object-cover rounded-lg" />
        <p class="text-xs text-slate-400 w-full">La edición de archivos multimedia se gestiona recreando la prenda.</p>
      </div>

      <button :disabled="saving" class="w-full bg-brand text-white py-2 rounded-lg hover:bg-brand-dark disabled:opacity-50">
        {{ saving ? 'Guardando…' : (editing ? 'Guardar cambios' : 'Publicar') }}
      </button>
    </form>
  </div>
</template>
