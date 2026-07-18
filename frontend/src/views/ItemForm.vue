<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../lib/api'
import { mediaUrl } from '../lib/media'
import { useToasts } from '../store/toasts'
import Spinner from '../components/ui/Spinner.vue'
import Icon from '../components/ui/Icon.vue'
import SizeChips from '../components/ui/SizeChips.vue'

const route = useRoute(); const router = useRouter(); const toasts = useToasts()
const editing = computed(() => !!route.params.id)
const meta = ref({ categories: [], sizes: [], colors: [] })
const form = ref({ name: '', description: '', category_id: '', size_ids: [], color_id: '', price: '' })
const files = ref([]); const previews = ref([]); const existing = ref([])
const errors = ref({}); const saving = ref(false); const gErr = ref('')

onMounted(async () => {
  try { const { data } = await api.get('/meta'); meta.value = data } catch (e) {}
  if (editing.value) {
    try {
      const { data: d } = await api.get(`/catalog/${route.params.id}`)
      const it = d.item
      form.value = {
        name: it.name,
        description: it.description || '',
        category_id: it.category_id,
        size_ids: it.sizes?.length ? it.sizes.map((s) => s.id) : (it.size_id ? [it.size_id] : []),
        color_id: it.color_id,
        price: it.price,
      }
      existing.value = it.media || []
    } catch (e) { gErr.value = 'No se pudo cargar la publicación.' }
  }
})

function onFiles(e) {
  files.value = Array.from(e.target.files)
  previews.value = files.value.map((f) => ({ url: URL.createObjectURL(f), video: f.type.startsWith('video') }))
}
function removePreview(i) { files.value.splice(i, 1); previews.value.splice(i, 1) }

async function submit() {
  if (saving.value) return
  errors.value = {}; gErr.value = ''
  if (!form.value.size_ids.length) {
    errors.value = { size_ids: ['Selecciona al menos una talla.'] }
    gErr.value = 'Revisa los campos marcados.'
    return
  }
  saving.value = true
  try {
    if (editing.value) {
      await api.patch(`/items/${route.params.id}`, form.value)
      toasts.success('Publicación actualizada')
      router.push('/my-items')
    } else {
      const fd = new FormData()
      Object.entries(form.value).forEach(([k, v]) => { if (k !== 'size_ids') fd.append(k, v) })
      form.value.size_ids.forEach((id) => fd.append('size_ids[]', id))
      files.value.forEach((f) => fd.append('files[]', f))
      await api.post('/items', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
      toasts.success('¡Publicación creada!')
      router.push('/my-items')
    }
  } catch (e) {
    errors.value = e.response?.data?.errors || {}
    gErr.value = e.response?.data?.message || (e.response ? 'Revisa los campos marcados.' : 'Error de conexión.')
  } finally { saving.value = false }
}
</script>

<template>
  <div class="max-w-2xl mx-auto">
    <button @click="router.back()" class="text-sm text-slate-500 hover:text-slate-700 mb-3 inline-flex items-center gap-1">
      <Icon name="back" :size="16" /> Volver
    </button>

    <div class="card card-pad animate-fade-up">
      <h1 class="font-display font-bold text-lg sm:text-xl mb-0.5">{{ editing ? 'Editar publicación' : 'Publicar prenda' }}</h1>
      <p class="text-sm text-slate-400 mb-5">{{ editing ? 'Actualiza los datos de tu publicación' : 'Comparte una prenda con la comunidad' }}</p>

      <div v-if="gErr" class="flex items-center gap-2 bg-rose-50 text-rose-600 text-sm p-3 rounded-xl mb-4">
        <Icon name="warning" :size="17" /> {{ gErr }}
      </div>

      <form @submit.prevent="submit" class="space-y-4" novalidate>
        <div class="grid sm:grid-cols-2 gap-4">
          <div class="sm:col-span-2">
            <label class="field-label">Nombre <span class="field-req">*</span></label>
            <input v-model="form.name" required placeholder="Ej. Chaqueta de mezclilla" class="input" :class="{ 'input-error': errors.name }" />
            <p v-if="errors.name" class="field-err"><Icon name="warning" :size="13" /> {{ errors.name[0] }}</p>
          </div>
          <div class="sm:col-span-2">
            <label class="field-label">Descripción</label>
            <textarea v-model="form.description" rows="3" placeholder="Estado, material, medidas…" class="input resize-none"></textarea>
          </div>
          <div>
            <label class="field-label">Categoría <span class="field-req">*</span></label>
            <select v-model="form.category_id" required class="input" :class="{ 'input-error': errors.category_id }">
              <option value="">Elegir…</option>
              <option v-for="c in meta.categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div>
            <label class="field-label">Color <span class="field-req">*</span></label>
            <select v-model="form.color_id" required class="input" :class="{ 'input-error': errors.color_id }">
              <option value="">Elegir…</option>
              <option v-for="c in meta.colors" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div class="sm:col-span-2">
            <label class="field-label">Precio (USD) <span class="field-req">*</span></label>
            <div class="relative max-w-[200px]">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-medium">$</span>
              <input v-model="form.price" type="number" step="0.01" min="0" required placeholder="0.00" class="input pl-7" :class="{ 'input-error': errors.price }" />
            </div>
            <p v-if="errors.price" class="field-err"><Icon name="warning" :size="13" /> {{ errors.price[0] }}</p>
          </div>
        </div>

        <div class="border-t border-slate-100 pt-4">
          <label class="field-label">Tallas disponibles <span class="field-req">*</span>
            <span class="font-normal text-slate-400">— puedes elegir varias</span>
          </label>
          <SizeChips v-model="form.size_ids" :sizes="meta.sizes" :error="!!errors.size_ids" />
          <p v-if="errors.size_ids" class="field-err"><Icon name="warning" :size="13" /> {{ errors.size_ids[0] }}</p>
        </div>

        <div v-if="!editing" class="border-t border-slate-100 pt-4">
          <label class="field-label">Fotos / videos <span class="field-req">*</span> <span class="font-normal text-slate-400">(1 a 8)</span></label>
          <label class="flex flex-col items-center border-2 border-dashed rounded-xl p-5 text-center cursor-pointer transition hover:border-brand hover:bg-brand-50/40"
            :class="errors['files'] ? 'border-rose-300' : 'border-slate-200'">
            <input type="file" multiple accept="image/*,video/mp4,video/webm" @change="onFiles" class="hidden" />
            <Icon name="upload" :size="26" class="text-brand-600" />
            <p class="text-sm text-slate-500 mt-1">Toca para seleccionar archivos</p>
          </label>
          <p v-if="errors['files']" class="field-err"><Icon name="warning" :size="13" /> {{ errors['files'][0] }}</p>
          <div v-if="previews.length" class="flex gap-2 flex-wrap mt-3">
            <div v-for="(p, i) in previews" :key="i" class="relative w-16 h-16 rounded-xl overflow-hidden border bg-slate-100 group">
              <video v-if="p.video" :src="p.url" class="w-full h-full object-cover" />
              <img v-else :src="p.url" class="w-full h-full object-cover" alt="" />
              <button type="button" @click="removePreview(i)"
                class="absolute top-0.5 right-0.5 w-5 h-5 rounded-md bg-slate-900/70 text-white grid place-items-center opacity-0 group-hover:opacity-100 transition"
                aria-label="Quitar archivo"><Icon name="close" :size="12" /></button>
            </div>
          </div>
        </div>
        <div v-else-if="existing.length" class="border-t border-slate-100 pt-4">
          <label class="field-label">Archivos actuales</label>
          <div class="flex gap-2 flex-wrap">
            <img v-for="m in existing" :key="m.media_id" :src="mediaUrl(m.url)" class="w-16 h-16 object-cover rounded-xl border" alt="" />
          </div>
          <p class="field-help">Para cambiar los archivos, vuelve a publicar la prenda.</p>
        </div>

        <div class="flex gap-2 pt-2 border-t border-slate-100">
          <button type="button" @click="router.back()" class="btn btn-ghost">Cancelar</button>
          <button :disabled="saving" class="btn btn-primary flex-1">
            <Spinner v-if="saving" :size="18" light /> {{ saving ? 'Guardando…' : (editing ? 'Guardar cambios' : 'Publicar prenda') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
