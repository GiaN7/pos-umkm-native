<script setup lang="ts">
import { ref, watch } from 'vue'
import { Image as ImageIcon } from 'lucide-vue-next'

const props = defineProps<{
  image: string | null
  name: string
}>()

function resolveImage(): string | null {
  if (!props.image) return null
  if (/^(https?:|data:)/i.test(props.image) || props.image.startsWith('/')) {
    return props.image
  }
  return `/storage/${props.image.replace(/^\/+/, '')}`
}

const source = ref(resolveImage())

watch(() => props.image, () => {
  source.value = resolveImage()
})

function useFallback() {
  source.value = null
}
</script>

<template>
  <div class="relative flex w-full min-w-0 items-center justify-center overflow-hidden bg-gradient-to-br from-[#fbf2ec] to-[#f3e3d7]">
    <img
      v-if="source"
      :src="source"
      :alt="name"
      class="absolute inset-0 h-full w-full object-contain p-1"
      loading="lazy"
      decoding="async"
      @error="useFallback"
    />
    <div v-else class="flex min-h-20 w-full flex-col items-center justify-center gap-1 px-2 text-secondary-foreground">
      <ImageIcon class="h-7 w-7 opacity-70" aria-hidden="true" />
      <span class="text-center text-[10px] font-medium">Belum ada foto</span>
    </div>
  </div>
</template>
