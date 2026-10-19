<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { reactiveOmit } from '@vueuse/core'
import { cn } from '@/lib/utils'
import { Package } from 'lucide-vue-next'
import TableCell from './TableCell.vue'
import TableRow from './TableRow.vue'

const props = withDefaults(defineProps<{
  class?: HTMLAttributes['class']
  colspan?: number
}>(), {
  colspan: 1,
})

const delegatedProps = reactiveOmit(props, 'class')
</script>

<template>
  <TableRow>
    <TableCell
      :class="
        cn(
          'p-4 whitespace-nowrap align-middle text-sm text-foreground',
          props.class,
        )
      "
      v-bind="delegatedProps"
    >
      <div class="flex flex-col items-center justify-center py-16">
        <Package class="h-12 w-12 mx-2 text-muted-foreground mb-3" />
        <p class="text-center text-sm text-muted-foreground" v-if="!props.noTitle">
          Tidak ada data
        </p>
        <p class="text-center text-sm text-muted-foreground" v-if="props.noTitle">
          Tidak ada data yang tampil.
        </p>
        <slot />
      </div>
    </TableCell>
  </TableRow>
</template>
