<script setup lang="ts">
import { ref, computed, watch } from 'vue'

const props = defineProps<{
  total: number
  show: boolean
}>()

const emit = defineEmits<{
  close: []
  pay: [paymentMethod: string, bayar: number]
}>()

const paymentMethod = ref<string>('tunai')
const bayar = ref<number>(0)

const kembali = computed(() => {
  return Math.max(0, bayar.value - props.total)
})

const canPay = computed(() => {
  return bayar.value >= props.total
})

watch(paymentMethod, (method) => {
  if (method === 'qris') {
    bayar.value = props.total
  }
})

const paymentMethods = [
  { value: 'tunai', label: 'Tunai', icon: '💵' },
  { value: 'qris', label: 'QRIS / Transfer', icon: '📱' },
]

function formatRupiah(value: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value)
}

function setBayar(amount: number) {
  bayar.value = amount
}

function handlePay() {
  if (!canPay.value) return
  emit('pay', paymentMethod.value, bayar.value)
}

function handleClose() {
  emit('close')
}
</script>

<template>
  <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
      <!-- Header -->
      <div class="p-4 border-b">
        <div class="flex justify-between items-center">
          <h2 class="text-lg font-bold text-foreground">Pembayaran</h2>
          <button
            @click="handleClose"
            class="icon-button flex h-10 w-10 items-center justify-center rounded-full text-muted-foreground"
          >
            ✕
          </button>
        </div>
      </div>

      <!-- Body -->
      <div class="p-4 space-y-4">
        <!-- Total -->
        <div class="text-center py-4 warm-card rounded-lg">
          <p class="text-sm text-muted-foreground">Total Bayar</p>
          <p class="text-3xl font-bold text-foreground">{{ formatRupiah(total) }}</p>
        </div>

        <!-- Payment Method -->
        <div>
          <label class="block text-sm font-medium text-muted-foreground mb-2">Metode Pembayaran</label>
          <div class="grid grid-cols-2 gap-2">
            <button
              v-for="method in paymentMethods"
              :key="method.value"
              @click="paymentMethod = method.value"
              :class="[
                'p-3 rounded-lg border text-center transition-colors',
                paymentMethod === method.value
                  ? 'warm-primary text-white border-primary'
                  : 'warm-card border-border text-muted-foreground hover:warm/5'
              ]"
            >
              <div class="text-2xl mb-1">{{ method.icon }}</div>
              <div class="text-sm font-medium">{{ method.label }}</div>
            </button>
          </div>
        </div>

        <!-- Bayar -->
        <div>
          <label class="block text-sm font-medium text-muted-foreground mb-1">Jumlah Bayar</label>
          <input
            v-model.number="bayar"
            type="number"
            class="w-full px-3 py-2 border border-border rounded-md focus:outline-none focus:ring-2 focus:ring-primary text-lg"
            placeholder="0"
          />
          <div class="flex gap-2 mt-2">
            <button
              @click="setBayar(total)"
              class="flex-1 py-2 warm-card rounded-md px-2 text-sm text-muted-foreground hover:warm/5"
            >
              Uang Pas
            </button>
            <button
              @click="setBayar(Math.ceil(total / 10000) * 10000)"
              class="flex-1 py-2 warm-card rounded-md px-2 text-sm text-muted-foreground hover:warm/5"
            >
              {{ formatRupiah(Math.ceil(total / 10000) * 10000) }}
            </button>
            <button
              @click="setBayar(Math.ceil(total / 50000) * 50000)"
              class="flex-1 py-2 warm-card rounded-md px-2 text-sm text-muted-foreground hover:warm/5"
            >
              {{ formatRupiah(Math.ceil(total / 50000) * 50000) }}
            </button>
          </div>
        </div>

        <!-- Kembali -->
        <div class="flex justify-between items-center p-3 warm-card rounded-lg">
          <span class="text-muted-foreground">Kembali</span>
          <span
            :class="[
              'text-xl font-bold',
              kembali > 0 ? 'text-primary' : 'text-muted-foreground'
            ]"
          >
            {{ formatRupiah(kembali) }}
          </span>
        </div>
      </div>

      <!-- Footer -->
      <div class="p-4 border-t flex gap-2">
        <button
          @click="handleClose"
          class="flex-1 py-3 warm-card rounded-lg font-medium text-foreground hover:warm/5"
        >
          Batal
        </button>
        <button
          @click="handlePay"
          :disabled="!canPay"
          class="flex-1 py-3 warm-primary rounded-lg font-medium text-white hover:warm-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
        >
          Bayar
        </button>
      </div>
    </div>
  </div>
</template>
