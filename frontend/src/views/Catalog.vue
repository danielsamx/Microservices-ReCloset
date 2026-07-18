<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import api from '../lib/api'
import ItemCard from '../components/ItemCard.vue'
import ProductCardSkeleton from '../components/ui/ProductCardSkeleton.vue'
import EmptyState from '../components/ui/EmptyState.vue'
import Icon from '../components/ui/Icon.vue'

const items = ref([]); const meta = ref({ categories: [], sizes: [], colors: [] })
const filters = ref({ category_id: '', size_id: '', color_id: '', search: '' })
const loading = ref(true); const error = ref(false); const showFilters = ref(false)
const activeCount = computed(() => Object.values(filters.value).filter(Boolean).length)

async function load() {
  loading.value = true; error.value = false
  const params = Object.fromEntries(Object.entries(filters.value).filter(([, v]) => v))
  try { const { data } = await api.get('/catalog', { params }); items.value = data.data }
  catch (e) { error.value = true } finally { loading.value = false }
}
onMounted(async () => {
  try { const { data } = await api.get('/meta'); meta.value = data } catch (e) {}
  load()
})
watch(filters, load, { deep: true })
function clear() { filters.value = { category_id: '', size_id: '', color_id: '', search: '' } }
</script>
<template>
  <div class="flex items-end justify-between gap-3 mb-5">
    <div>
      <h1 class="font-display font-bold text-2xl">Catálogo</h1>
      <p class="text-sm text-slate-400">Descubre prendas disponibles de la comunidad</p>
    </div>
    <button 
      class="btn btn-ghost btn-sm md:hidden relative" 
      @click="showFilters = !showFilters"
      aria-label="Abrir filtros"
    >
      <Icon name="filters" :size="16" /> Filtros
      <span v-if="activeCount" class="badge badge-available ml-1">{{ activeCount }}</span>
    </button>
  </div>

  <!-- Mobile filters backdrop drawer overlay -->
  <div 
    v-if="showFilters" 
    class="fixed inset-0 bg-slate-900/40 backdrop-blur-[1.5px] z-40 md:hidden animate-fade-in"
    @click="showFilters = false"
  ></div>

  <div class="flex flex-col md:flex-row gap-6 items-start mt-2 w-full">
    <!-- Filters Column (Sticky Sidebar on PC, slide-over drawer on Mobile) -->
    <aside 
      class="shrink-0 transition-all duration-300 w-full"
      :class="[
        showFilters 
          ? 'fixed right-0 top-0 h-full w-[295px] bg-white shadow-2xl p-5 overflow-y-auto z-50 md:z-10 md:w-[260px] md:relative md:h-auto md:shadow-none md:p-0 md:overflow-visible md:top-0 md:block' 
          : 'hidden md:block md:w-[260px] md:relative md:top-0 md:overflow-visible'
      ]"
    >
      <div class="card card-pad space-y-6 border-slate-100/80 shadow-soft">
        <!-- Filters Header (Mobile only has Close, PC shows Title) -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <span class="font-display font-bold text-slate-800 text-base flex items-center gap-1.5">
            <Icon name="filters" :size="16" class="text-brand-600" /> Filtros
          </span>
          <button 
            v-if="activeCount" 
            @click="clear" 
            class="text-[11px] text-rose-600 hover:text-rose-700 font-semibold flex items-center gap-0.5 transition"
          >
            <Icon name="close" :size="12" /> Limpiar ({{ activeCount }})
          </button>
          <button 
            @click="showFilters = false" 
            class="md:hidden w-7 h-7 rounded-lg hover:bg-slate-100 grid place-items-center text-slate-400 hover:text-slate-600" 
            aria-label="Cerrar filtros"
          >
            <Icon name="close" :size="15" />
          </button>
        </div>

        <!-- Search Input -->
        <div class="space-y-1.5">
          <label class="field-label !mb-0 text-slate-700 text-xs">Buscar prendas</label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
              <Icon name="search" :size="16" />
            </span>
            <input 
              v-model="filters.search" 
              placeholder="Ej. Chaqueta, vestido…" 
              class="input pl-9 text-xs py-2 bg-slate-50/50 hover:bg-slate-50 transition border-slate-200/80 focus:bg-white" 
              aria-label="Buscar prendas" 
            />
          </div>
        </div>

        <!-- Categories List -->
        <div class="space-y-1.5">
          <label class="field-label !mb-0 text-slate-700 text-xs">Categoría</label>
          <div class="space-y-1 max-h-[160px] overflow-y-auto pr-1">
            <button 
              @click="filters.category_id = ''" 
              class="w-full text-left text-xs px-2.5 py-1.5 rounded-xl transition-all duration-200 flex items-center justify-between"
              :class="filters.category_id === '' ? 'bg-brand-50 text-brand-700 font-bold shadow-sm' : 'text-slate-600 hover:bg-slate-50'"
            >
              <span>Todas las categorías</span>
              <span v-if="filters.category_id === ''" class="w-1.5 h-1.5 rounded-full bg-brand-600"></span>
            </button>
            <button 
              v-for="c in meta.categories" 
              :key="c.id"
              @click="filters.category_id = c.id" 
              class="w-full text-left text-xs px-2.5 py-1.5 rounded-xl transition-all duration-200 flex items-center justify-between"
              :class="filters.category_id === c.id ? 'bg-brand-50 text-brand-700 font-bold shadow-sm' : 'text-slate-600 hover:bg-slate-50'"
            >
              <span>{{ c.name }}</span>
              <span v-if="filters.category_id === c.id" class="w-1.5 h-1.5 rounded-full bg-brand-600"></span>
            </button>
          </div>
        </div>

        <!-- Sizes Grid -->
        <div class="space-y-1.5">
          <label class="field-label !mb-0 text-slate-700 text-xs">Talla</label>
          <div class="grid grid-cols-4 gap-1.5">
            <button 
              @click="filters.size_id = ''"
              class="border text-center text-[10px] py-1.5 rounded-lg transition font-semibold"
              :class="filters.size_id === '' ? 'border-brand bg-brand text-white shadow-soft' : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
            >
              Todas
            </button>
            <button 
              v-for="s in meta.sizes" 
              :key="s.id"
              @click="filters.size_id = s.id"
              class="border text-center text-[10px] py-1.5 rounded-lg transition font-semibold uppercase"
              :class="filters.size_id === s.id ? 'border-brand bg-brand text-white shadow-soft' : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
            >
              {{ s.label }}
            </button>
          </div>
        </div>

        <!-- Colors Row (Swatches) -->
        <div class="space-y-1.5">
          <label class="field-label !mb-0 text-slate-700 text-xs">Color</label>
          <div class="flex flex-wrap gap-1.5">
            <button 
              @click="filters.color_id = ''"
              class="w-7 h-7 rounded-full border grid place-items-center text-slate-500 transition-all hover:scale-105"
              :class="filters.color_id === '' ? 'border-slate-800 bg-slate-100 ring-2 ring-slate-300 shadow-sm' : 'border-slate-200 hover:border-slate-300 bg-white'"
              title="Todos los colores"
            >
              <Icon name="close" :size="12" />
            </button>
            <button 
              v-for="c in meta.colors" 
              :key="c.id"
              @click="filters.color_id = c.id"
              class="w-7 h-7 rounded-full border transition-all relative group/color flex items-center justify-center"
              :style="{ backgroundColor: c.hex }"
              :class="[
                filters.color_id === c.id ? 'ring-2 ring-brand ring-offset-2 scale-105 border-slate-600 shadow-sm' : 'border-slate-200 hover:scale-105',
                c.hex.toLowerCase() === '#ffffff' ? 'border-slate-300' : ''
              ]"
              :title="c.name"
            >
              <span 
                v-if="filters.color_id === c.id" 
                class="absolute inset-0 m-auto w-3 h-3 rounded-full flex items-center justify-center font-bold text-[8px]"
                :class="c.hex.toLowerCase() === '#ffffff' ? 'text-slate-800' : 'text-white'"
              >
                ✓
              </span>
              <span class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 px-1.5 py-0.5 bg-slate-800 text-[9px] text-white rounded opacity-0 group-hover/color:opacity-100 pointer-events-none transition-opacity duration-200 shadow-md whitespace-nowrap z-50">
                {{ c.name }}
              </span>
            </button>
          </div>
        </div>
      </div>
    </aside>

    <!-- Catalog Columns -->
    <div class="flex-1 w-full">
      <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <ProductCardSkeleton v-for="i in 8" :key="i" />
      </div>

      <EmptyState v-else-if="error" icon="warning" title="No pudimos cargar las prendas"
        subtitle="Comprueba tu conexión e inténtalo de nuevo.">
        <button class="btn btn-primary" @click="load"><Icon name="retry" :size="16" /> Reintentar</button>
      </EmptyState>

      <EmptyState v-else-if="!items.length" icon="search" title="Sin resultados"
        subtitle="No hay prendas que coincidan con tu búsqueda. Prueba a cambiar los filtros.">
        <button v-if="activeCount" class="btn btn-primary" @click="clear">Quitar filtros</button>
      </EmptyState>

      <div v-else class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <div v-for="(i, idx) in items" :key="i.id" class="animate-fade-up" :style="{ animationDelay: Math.min(idx, 8) * 35 + 'ms' }">
          <ItemCard :item="i" />
        </div>
      </div>
    </div>
  </div>
</template>
