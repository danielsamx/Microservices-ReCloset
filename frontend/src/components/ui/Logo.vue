<script setup>
defineProps({
  size: {
    type: [Number, String],
    default: 32
  },
  showText: {
    type: Boolean,
    default: false
  }
})
</script>

<template>
  <div class="inline-flex items-center gap-2 select-none group">
    <!-- Beautiful Circular Fashion SVG Logo -->
    <svg 
      :width="size" 
      :height="size" 
      viewBox="0 0 100 100" 
      fill="none" 
      xmlns="http://www.w3.org/2000/svg"
      class="text-brand group-hover:rotate-12 transition-transform duration-500 ease-out"
    >
      <!-- Background Circle Gradient Accent -->
      <circle cx="50" cy="50" r="46" class="fill-brand-50 opacity-40 group-hover:opacity-60 transition-opacity" />

      <!-- Left Arrow Path (Recycling) -->
      <path 
        d="M 50,14 A 36,36 0 0,1 86,50 A 36,36 0 0,1 72,78" 
        stroke="currentColor" 
        stroke-width="7" 
        stroke-linecap="round" 
        class="text-brand-600"
      />
      <!-- Right Arrow Path (Recycling) -->
      <path 
        d="M 50,86 A 36,36 0 0,1 14,50 A 36,36 0 0,1 28,22" 
        stroke="currentColor" 
        stroke-width="7" 
        stroke-linecap="round"
        class="text-brand-400"
      />

      <!-- Arrow Heads -->
      <!-- Top-Right pointing arrow head (from left loop to right) -->
      <path 
        d="M 78,16 L 86,13 L 89,21" 
        stroke="currentColor" 
        stroke-width="7" 
        stroke-linecap="round" 
        stroke-linejoin="round"
        class="text-brand-600"
      />
      <!-- Bottom-Left pointing arrow head (from right loop to left) -->
      <path 
        d="M 22,84 L 14,87 L 11,79" 
        stroke="currentColor" 
        stroke-width="7" 
        stroke-linecap="round" 
        stroke-linejoin="round"
        class="text-brand-400"
      />

      <!-- Hanger inside the circle -->
      <!-- Hanger hook -->
      <path 
        d="M 50,38 C 50,29 59,29 59,34 C 59,39 50,40 50,45" 
        stroke="currentColor" 
        stroke-width="6.5" 
        stroke-linecap="round"
        class="text-brand-800"
      />
      <!-- Hanger triangle base -->
      <path 
        d="M 50,45 L 24,64 C 22,65.5 23,68 25.5,68 L 74.5,68 C 77,68 78,65.5 76,64 L 50,45 Z" 
        fill="currentColor" 
        fill-opacity="0.1" 
        stroke="currentColor" 
        stroke-width="6.5" 
        stroke-linecap="round" 
        stroke-linejoin="round"
        class="text-brand-800"
      />
    </svg>

    <!-- Optional App Title next to logo -->
    <span 
      v-if="showText" 
      class="font-display font-extrabold tracking-tight text-slate-800 text-lg sm:text-xl"
    >
      Re<span class="text-brand">Closet</span>
    </span>
  </div>
</template>
