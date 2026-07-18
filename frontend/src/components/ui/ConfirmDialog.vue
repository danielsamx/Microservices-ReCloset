<script setup>
import Modal from './Modal.vue'
import Spinner from './Spinner.vue'
defineProps({
  open: Boolean, title: String, message: String,
  confirmText: { type: String, default: 'Confirmar' },
  cancelText: { type: String, default: 'Cancelar' },
  danger: Boolean, loading: Boolean,
})
const emit = defineEmits(['confirm', 'cancel'])
</script>
<template>
  <Modal :open="open" :title="title" @close="emit('cancel')">
    <p class="text-sm text-slate-500 leading-relaxed">{{ message }}</p>
    <div class="flex justify-end gap-2 mt-5">
      <button class="btn btn-ghost" @click="emit('cancel')" :disabled="loading">{{ cancelText }}</button>
      <button class="btn" :class="danger ? 'btn-primary !bg-rose-600 hover:!bg-rose-700' : 'btn-primary'"
        @click="emit('confirm')" :disabled="loading">
        <Spinner v-if="loading" :size="16" light /> {{ confirmText }}
      </button>
    </div>
  </Modal>
</template>
