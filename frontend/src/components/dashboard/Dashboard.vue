<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Package, RefreshCw, ShoppingBag, TrendingUp, Wallet } from 'lucide-vue-next'
import api from '@/lib/api'

interface Product {
  id: number
  name: string
  stok: number
  stok_minimal: number
}

interface TopProduct {
  id: number
  name: string
  total_qty: number
}

interface Penjualan7Hari {
  tanggal: string
  total: string
}

const penjualanHariIni = ref(0)
const transaksiHariIni = ref(0)
const stokMenipis = ref<Product[]>([])
const topProduk = ref<TopProduct[]>([])
const penjualan7Hari = ref<Penjualan7Hari[]>([])
const loading = ref(false)
const maxPenjualan = computed(() =>
  Math.max(...penjualan7Hari.value.map((item) => Number(item.total)), 0)
)

const tanggalHariIni = new Intl.DateTimeFormat('id-ID', {
  weekday: 'long',
  day: 'numeric',
  month: 'long',
}).format(new Date())

async function fetchDashboard() {
  loading.value = true
  try {
    const response = await api.get('/dashboard')
    penjualanHariIni.value = response.data.penjualan_hari_ini
    transaksiHariIni.value = response.data.transaksi_hari_ini
    stokMenipis.value = response.data.stok_menipis
    topProduk.value = response.data.top_produk
    penjualan7Hari.value = response.data.penjualan_7_hari
  } catch (err: any) {
    console.error('Failed to fetch dashboard', err)
  } finally {
    loading.value = false
  }
}

function formatRupiah(value: number | string): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(Number(value))
}

function formatDate(date: string): string {
  return new Date(date).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
  })
}

function barWidth(value: string): string {
  if (!maxPenjualan.value) return '0%'
  return `${Math.max((Number(value) / maxPenjualan.value) * 100, 4)}%`
}

onMounted(fetchDashboard)
</script>

<template>
  <div class="mx-auto max-w-[720px] space-y-4 pb-2">
    <div class="flex items-center justify-between gap-3 px-1">
      <div>
        <p class="text-xs font-semibold capitalize text-muted-foreground">{{ tanggalHariIni }}</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight">Ringkasan toko</h1>
      </div>
      <button
        type="button"
        aria-label="Muat ulang ringkasan"
        class="icon-button flex h-11 w-11 shrink-0 items-center justify-center rounded-full"
        :disabled="loading"
        @click="fetchDashboard"
      >
        <RefreshCw class="h-4 w-4" :class="loading ? 'animate-spin' : ''" />
      </button>
    </div>

    <div v-if="loading" class="warm-card rounded-[24px] p-6 text-center text-sm text-muted-foreground">
      Menyiapkan ringkasan tokomu…
    </div>

    <template v-else>
      <section class="hero-card overflow-hidden rounded-[24px] p-5 text-white sm:p-6">
        <div class="flex items-start justify-between gap-4">
          <div>
            <div class="mb-5 flex items-center gap-2 text-sm text-white/70">
              <Wallet class="h-4 w-4" />
              Penjualan hari ini
            </div>
            <p class="break-words text-[clamp(1.7rem,8vw,2.35rem)] font-bold leading-tight tabular-nums">
              {{ formatRupiah(penjualanHariIni) }}
            </p>
          </div>
          <div class="hero-icon flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl">
            <TrendingUp class="h-5 w-5" />
          </div>
        </div>
        <div class="mt-6 flex items-center gap-2 border-t border-white/15 pt-4 text-sm text-white/75">
          <ShoppingBag class="h-4 w-4" />
          <span>{{ transaksiHariIni }} transaksi tercatat hari ini</span>
        </div>
      </section>

      <section class="grid grid-cols-2 gap-3">
        <div class="warm-card rounded-[20px] p-4">
          <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-green-50 text-green-700">
            <ShoppingBag class="h-5 w-5" />
          </div>
          <p class="text-xs font-medium text-muted-foreground">Transaksi hari ini</p>
          <p class="mt-1 text-2xl font-bold tabular-nums">{{ transaksiHariIni }}</p>
          <p class="mt-1 text-xs text-muted-foreground">struk penjualan</p>
        </div>
        <div class="warm-card rounded-[20px] p-4">
          <div
            class="mb-4 flex h-10 w-10 items-center justify-center rounded-full"
            :class="stokMenipis.length ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700'"
          >
            <Package class="h-5 w-5" />
          </div>
          <p class="text-xs font-medium text-muted-foreground">Stok perlu dicek</p>
          <p
            class="mt-1 text-2xl font-bold tabular-nums"
            :class="stokMenipis.length ? 'text-red-700' : 'text-green-700'"
          >
            {{ stokMenipis.length }}
          </p>
          <p class="mt-1 text-xs text-muted-foreground">produk menipis</p>
        </div>
      </section>

      <section v-if="stokMenipis.length" class="rounded-[20px] border border-red-200 bg-red-50 p-4">
        <div class="mb-3 flex items-center gap-2 text-sm font-bold text-red-800">
          <Package class="h-4 w-4" />
          Perlu restok
        </div>
        <div class="space-y-2">
          <div
            v-for="product in stokMenipis"
            :key="product.id"
            class="flex items-center justify-between gap-3 rounded-xl bg-white/80 px-3 py-2.5"
          >
            <span class="min-w-0 truncate text-sm font-semibold">{{ product.name }}</span>
            <span class="shrink-0 text-xs font-semibold tabular-nums text-red-700">
              {{ product.stok }} tersisa
            </span>
          </div>
        </div>
      </section>

      <section class="warm-card rounded-[20px] p-4 sm:p-5">
        <div class="mb-4 flex items-center justify-between gap-3">
          <div>
            <h2 class="font-bold">Penjualan 7 hari</h2>
            <p class="mt-1 text-xs text-muted-foreground">Lihat ritme tokomu minggu ini</p>
          </div>
          <div class="flex h-10 w-10 items-center justify-center rounded-full bg-surface-container-low text-foreground">
            <TrendingUp class="h-5 w-5" />
          </div>
        </div>
        <div v-if="penjualan7Hari.length === 0" class="py-5 text-center text-sm text-muted-foreground">
          Belum ada data penjualan
        </div>
        <div v-else class="space-y-3">
          <div
            v-for="item in penjualan7Hari"
            :key="item.tanggal"
            class="grid grid-cols-[52px_minmax(0,1fr)_auto] items-center gap-3"
          >
            <span class="text-xs font-medium text-muted-foreground">{{ formatDate(item.tanggal) }}</span>
            <div class="h-2 overflow-hidden rounded-full bg-surface-container">
              <div class="h-full rounded-full bg-secondary" :style="{ width: barWidth(item.total) }"></div>
            </div>
            <span class="text-right text-xs font-semibold tabular-nums">{{ formatRupiah(item.total) }}</span>
          </div>
        </div>
      </section>

      <section class="warm-card rounded-[20px] p-4 sm:p-5">
        <div class="mb-4">
          <h2 class="font-bold">Produk paling laris</h2>
          <p class="mt-1 text-xs text-muted-foreground">Yang paling sering masuk keranjang</p>
        </div>
        <div v-if="topProduk.length === 0" class="py-5 text-center text-sm text-muted-foreground">
          Belum ada data penjualan
        </div>
        <div v-else class="divide-y divide-border">
          <div
            v-for="(product, index) in topProduk"
            :key="product.id"
            class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
          >
            <div class="flex min-w-0 items-center gap-3">
              <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-surface-container-low text-sm font-bold text-foreground">
                {{ index + 1 }}
              </span>
              <span class="truncate text-sm font-semibold">{{ product.name }}</span>
            </div>
            <span class="shrink-0 text-xs font-medium text-muted-foreground">{{ product.total_qty }} terjual</span>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<style scoped>
.hero-card {
  background: var(--primary);
}

.hero-icon {
  color: #fff;
  background: rgba(255, 255, 255, 0.15);
}
</style>
