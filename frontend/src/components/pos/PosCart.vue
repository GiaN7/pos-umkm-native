<script setup lang="ts">
import { ref } from 'vue'
import { Package, XIcon } from 'lucide-vue-next'

interface Product {
  id: number
  name: string
  sku: string
  harga_jual: string
  stok: number
}

interface CartItem {
  product: Product
  qty: number
  diskon: number
}

const props = defineProps<{
  items: CartItem[]
  total: number
  count: number
}>()

const emit = defineEmits<{
  updateQty: [index: number, qty: number]
  updateDiskon: [index: number, diskon: number]
  remove: [index: number]
  clear: []
  pay: []
}>()

const showDiskonInput = ref<number | null>(null)
const diskonInput = ref(0)

function formatRupiah(value: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value)
}

function itemSubtotal(item: CartItem): string {
  return formatRupiah(Number(item.product.harga_jual) * item.qty - item.diskon)
}

function openDiskonInput(index: number) {
  showDiskonInput.value = index
  diskonInput.value = props.items[index].diskon
}

function saveDiskon(index: number) {
  emit('updateDiskon', index, diskonInput.value)
  showDiskonInput.value = null
}

function cancelDiskon() {
  showDiskonInput.value = null
}
</script>

<template>
  <div class="flex flex-col h-full">
    <!-- Header -->
    <div class="flex-shrink-0 border-b border-border p-4 sm:p-5">
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-lg font-bold text-foreground">Keranjang</h2>
          <p class="mt-1 text-xs text-muted-foreground">{{ count }} item dipilih</p>
        </div>
        <button
          v-if="items.length > 0"
          type="button"
          @click="emit('clear')"
          class="min-h-11 rounded-full px-3 text-sm font-semibold transition hover:brightness-110 disabled:cursor-wait disabled:opacity-60"
        >
          Kosongkan
        </button>
      </div>
    </div>

    <!-- Cart Items (scrollable) -->
    <div class="flex-1 overflow-y-auto p-4">
      <div v-if="items.length === 0" class="rounded-xl p-8 text-center">
        <Package class="h-12 w-12 mx-auto mb-3 text-muted-foreground" />
        <p class="text-sm text-muted-foreground">Keranjang masih kosong</p>
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="(item, index) in items"
          :key="item.product.id"
          class="rounded-xl border border-border bg-card p-3"
        >
          <div class="flex justify-between items-start mb-2">
            <div>
              <h4 class="font-medium text-foreground text-sm">{{ item.product.name }}</h4>
              <p class="text-xs text-muted-foreground">{{ formatRupiah(Number(item.product.harga_jual)) }} x {{ item.qty }}</p>
            </div>
            <button
              type="button"
              :aria-label="`Hapus ${item.product.name} dari keranjang`"
              @click="emit('remove', index)"
              class="flex h-9 w-9 items-center justify-center rounded-full text-destructive transition duration-150"
            >
              <XIcon class="h-4 w-4" />
            </button>
          </div>
          <div class="flex justify-between items-center">
            <div class="flex items-center gap-2">
              <button
                type="button"
                @click="emit('updateQty', index, item.qty - 1)"
                :disabled="item.qty <= 1"
                class="flex h-9 w-9 items-center justify-center rounded-full bg-muted text-lg font-semibold transition duration-150"
              >
                -
              </button>
              <span class="w-8 text-center font-medium">{{ item.qty }}</span>
              <button
                type="button"
                @click="emit('updateQty', index, item.qty + 1)"
                :disabled="item.qty >= item.product.stok"
                class="flex h-9 w-9 items-center justify-center rounded-full bg-muted text-lg font-semibold transition duration-150 disabled:opacity-50 disabled:pointer-events-none"
              >
                +
              </button>
            </div>
            <span class="font-bold text-foreground">{{ itemSubtotal(item) }}</span>
          </div>

          <!-- Diskon per item -->
          <div class="mt-2 pt-2 border-t border-border">
            <div class="flex justify-between items-center">
              <span class="text-xs text-muted-foreground">Diskon</span>
              <button
                type="button"
                @click="openDiskonInput(index)"
                class="min-h-10 rounded-full px-3 text-xs font-semibold text-primary hover:bg-primary/10"
              >
                {{ item.diskon > 0 ? formatRupiah(item.diskon) : 'Tambah Diskon' }}
              </button>
            </div>

            <!-- Diskon Input -->
            <div v-if="showDiskonInput === index" class="mt-2 flex gap-2">
              <input
                v-model.number="diskonInput"
                type="number"
                class="warm-input min-h-11 flex-1 rounded-xl px-3 text-sm"
                placeholder="0"
              />
              <button
                type="button"
                @click="saveDiskon(index)"
                class="min-h-11 rounded-xl bg-primary px-3 text-sm font-semibold text-white hover:bg-primary/90"
              >
                Simpan
              </button>
              <button
                type="button"
                @click="cancelDiskon"
                class="min-h-11 rounded-xl bg-muted px-3 text-sm font-semibold text-foreground hover:bg-muted/80"
              >
                Batal
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer (fixed) -->
    <div class="flex-shrink-0 border-t border-border p-4 sm:p-5">
      <div class="mb-4 flex items-end justify-between gap-3">
        <span class="text-muted-foreground">Total</span>
        <span class="text-right text-xl font-bold tabular-nums">{{ formatRupiah(total) }}</span>
      </div>
      <button
        type="button"
        @click="emit('pay')"
        :disabled="items.length === 0"
        class="min-h-[52px] w-full rounded-2xl bg-primary px-4 font-semibold text-white transition duration-150 hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50 disabled:pointer-events-none"
      >
        Lanjut ke pembayaran
      </button>
    </div>
  </div>
</template>
