<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import api from '@/lib/api'
import { downloadFile, printPage } from '@/lib/download'
import { CalendarDays, Download, Printer, ReceiptText, TrendingUp } from 'lucide-vue-next'

interface Summary {
  total_transaksi: number
  total_subtotal: string
  total_diskon: string
  total_ppn: string
  total_penjualan: string
  total_bayar: string
}

interface Daily {
  tanggal: string
  jumlah_transaksi: number
  total: string
}

interface PaymentMethod {
  payment_method: string
  jumlah: number
  total: string
}

interface TopProduct {
  id: number
  name: string
  total_qty: string
  total_subtotal: string
}

const summary = ref<Summary | null>(null)
const daily = ref<Daily[]>([])
const paymentMethods = ref<PaymentMethod[]>([])
const topProducts = ref<TopProduct[]>([])
const loading = ref(false)
const startDate = ref(new Date().toISOString().split('T')[0])
const endDate = ref(new Date().toISOString().split('T')[0])
const maxDailySales = computed(() =>
  Math.max(...daily.value.map((item) => Number(item.total)), 0)
)
const maxProductQuantity = computed(() =>
  Math.max(...topProducts.value.map((product) => Number(product.total_qty)), 0)
)

async function fetchReport() {
  loading.value = true
  try {
    const response = await api.get('/reports/sales', {
      params: { start_date: startDate.value, end_date: endDate.value }
    })
    summary.value = response.data.summary
    daily.value = response.data.daily
    paymentMethods.value = response.data.payment_methods
    topProducts.value = response.data.top_products
  } catch (err: any) {
    console.error('Failed to fetch report', err)
  } finally {
    loading.value = false
  }
}

function formatRupiah(value: string | number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(Number(value))
}

function formatDate(date: string): string {
  return new Date(date).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

function dailyBarWidth(value: string): string {
  if (!maxDailySales.value) return '0%'
  return `${Math.max((Number(value) / maxDailySales.value) * 100, 4)}%`
}

function productBarWidth(value: string): string {
  if (!maxProductQuantity.value) return '0%'
  return `${Math.max((Number(value) / maxProductQuantity.value) * 100, 4)}%`
}

onMounted(fetchReport)

async function exportCsv() {
  try {
    await downloadFile('/reports/sales/export', {
      start_date: startDate.value,
      end_date: endDate.value,
    })
  } catch (err: any) {
    console.error('Failed to export', err)
    alert('Export gagal. Coba lagi.')
  }
}
</script>

<template>
  <div class="mx-auto max-w-[720px] space-y-4 pb-2">
    <header class="px-1">
      <p class="text-xs font-semibold text-muted-foreground">Ringkasan tokomu</p>
      <h1 class="mt-1 text-2xl font-bold tracking-tight text-foreground">Laporan penjualan</h1>
      <p class="mt-1 text-sm text-muted-foreground">Pantau pemasukan, transaksi, dan produk terlaris.</p>
    </header>

    <section class="warm-card rounded-[20px] p-4 sm:p-5">
      <div class="mb-4 flex items-center gap-2 text-sm font-bold">
        <CalendarDays class="h-4 w-4 text-secondary-foreground" />
        Periode laporan
      </div>
      <div class="grid gap-3 sm:grid-cols-2">
        <div class="min-w-0">
          <label for="sales-start-date" class="mb-1.5 block text-xs font-semibold text-muted-foreground">Tanggal mulai</label>
          <input
            id="sales-start-date"
            v-model="startDate"
            type="date"
            class="h-11 w-full rounded-xl border border-border bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          />
        </div>
        <div class="min-w-0">
          <label for="sales-end-date" class="mb-1.5 block text-xs font-semibold text-muted-foreground">Tanggal akhir</label>
          <input
            id="sales-end-date"
            v-model="endDate"
            type="date"
            class="h-11 w-full rounded-xl border border-border bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          />
        </div>
      </div>
      <div class="mt-4 flex flex-col gap-2 border-t border-border pt-4 sm:flex-row sm:items-center sm:justify-between">
        <button
          type="button"
          :disabled="loading"
          class="flex min-h-11 items-center justify-center gap-2 rounded-xl warm-primary px-4 text-sm font-semibold text-primary-foreground transition hover:warm-primary/90 disabled:cursor-wait disabled:opacity-60"
          @click="fetchReport"
        >
          <TrendingUp class="h-4 w-4" />
          {{ loading ? 'Memuat laporan…' : 'Tampilkan laporan' }}
        </button>
        <div class="grid grid-cols-2 gap-2">
          <button
            type="button"
            class="flex min-h-11 items-center justify-center gap-2 rounded-xl border border-border bg-white px-3 text-sm font-semibold transition hover:warm-card"
            @click="exportCsv"
          >
            <Download class="h-4 w-4" />
            Export CSV
          </button>
          <button
            type="button"
            class="flex min-h-11 items-center justify-center gap-2 rounded-xl border border-border bg-white px-3 text-sm font-semibold transition hover:warm-card"
            @click="printPage"
          >
            <Printer class="h-4 w-4" />
            Cetak / PDF
          </button>
        </div>
      </div>
    </section>

    <div v-if="loading" class="warm-card rounded-[20px] p-6 text-center text-sm text-muted-foreground">
      Menyiapkan laporan penjualan…
    </div>

    <template v-else>
      <section class="report-hero-card overflow-hidden rounded-[24px] p-5 text-white sm:p-6">
        <div class="flex items-start justify-between gap-4">
          <div class="min-w-0">
            <div class="mb-4 flex items-center gap-2 text-sm text-white/75">
              <TrendingUp class="h-4 w-4" />
              Total penjualan
            </div>
            <p class="break-words text-[clamp(1.8rem,8vw,2.5rem)] font-bold leading-tight tabular-nums">
              {{ formatRupiah(summary?.total_penjualan || 0) }}
            </p>
          </div>
          <div class="report-hero-icon flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl">
            <ReceiptText class="h-5 w-5" />
          </div>
        </div>
        <div class="mt-5 flex items-center gap-2 border-t border-white/15 pt-4 text-sm text-white/75">
          <ReceiptText class="h-4 w-4" />
          <span>{{ summary?.total_transaksi || 0 }} transaksi pada periode ini</span>
        </div>
      </section>

      <section class="grid grid-cols-2 gap-3">
        <div class="warm-card min-w-0 rounded-[20px] p-4">
          <p class="text-xs font-medium text-muted-foreground">Subtotal</p>
          <p class="mt-2 break-words text-lg font-bold tabular-nums sm:text-xl">
            {{ formatRupiah(summary?.total_subtotal || 0) }}
          </p>
          <p class="mt-1 text-xs text-muted-foreground">Sebelum diskon</p>
        </div>
        <div class="warm-card min-w-0 rounded-[20px] p-4">
          <p class="text-xs font-medium text-muted-foreground">Total diskon</p>
          <p class="mt-2 break-words text-lg font-bold tabular-nums text-destructive sm:text-xl">
            {{ formatRupiah(summary?.total_diskon || 0) }}
          </p>
          <p class="mt-1 text-xs text-muted-foreground">Potongan diberikan</p>
        </div>
      </section>

      <section class="warm-card rounded-[20px] p-4 sm:p-5">
        <div class="mb-4">
          <h2 class="font-bold">Penjualan harian</h2>
          <p class="mt-1 text-xs text-muted-foreground">Rincian omzet per hari</p>
        </div>
        <div v-if="daily.length === 0" class="py-5 text-center text-sm text-muted-foreground">
          Belum ada data penjualan pada periode ini
        </div>
        <div v-else class="space-y-4">
          <div
            v-for="item in daily"
            :key="item.tanggal"
            class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-3 gap-y-2"
          >
            <span class="truncate text-sm font-semibold text-muted-foreground">{{ formatDate(item.tanggal) }}</span>
            <span class="text-right text-sm font-bold tabular-nums">{{ formatRupiah(item.total) }}</span>
            <div class="h-2 overflow-hidden rounded-full bg-surface-container-low">
              <div class="h-full rounded-full bg-primary" :style="{ width: dailyBarWidth(item.total) }"></div>
            </div>
            <span class="text-right text-xs text-muted-foreground">{{ item.jumlah_transaksi }} transaksi</span>
          </div>
        </div>
      </section>

      <section class="warm-card rounded-[20px] p-4 sm:p-5">
        <div class="mb-4">
          <h2 class="font-bold">Metode pembayaran</h2>
          <p class="mt-1 text-xs text-muted-foreground">Transaksi berdasarkan cara bayar</p>
        </div>
        <div v-if="paymentMethods.length === 0" class="py-5 text-center text-sm text-muted-foreground">
          Belum ada data pembayaran pada periode ini
        </div>
        <div v-else class="divide-y divide-border">
          <div
            v-for="item in paymentMethods"
            :key="item.payment_method"
            class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
          >
            <div class="flex min-w-0 items-center gap-3">
              <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-surface-container-low text-primary">
                <ReceiptText class="h-4 w-4" />
              </span>
              <div class="min-w-0">
                <p class="truncate text-sm font-semibold">{{ item.payment_method.replaceAll('_', ' ').toUpperCase() }}</p>
                <p class="mt-0.5 text-xs text-muted-foreground">{{ item.jumlah }} transaksi</p>
              </div>
            </div>
            <span class="shrink-0 text-right text-sm font-bold tabular-nums">{{ formatRupiah(item.total) }}</span>
          </div>
        </div>
      </section>

      <section class="warm-card rounded-[20px] p-4 sm:p-5">
        <div class="mb-4">
          <h2 class="font-bold">Produk terlaris</h2>
          <p class="mt-1 text-xs text-muted-foreground">Top 10 berdasarkan jumlah terjual</p>
        </div>
        <div v-if="topProducts.length === 0" class="py-5 text-center text-sm text-muted-foreground">
          Belum ada produk terjual pada periode ini
        </div>
        <div v-else class="divide-y divide-border">
          <div
            v-for="(product, index) in topProducts"
            :key="product.id"
            class="grid grid-cols-[minmax(0,1fr)_auto] gap-x-3 gap-y-2 py-3 first:pt-0 last:pb-0"
          >
            <div class="flex min-w-0 items-center gap-3">
              <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-surface-container-low text-sm font-bold text-primary">
                {{ index + 1 }}
              </span>
              <span class="truncate text-sm font-semibold">{{ product.name }}</span>
            </div>
            <span class="self-center text-right text-sm font-bold tabular-nums">{{ formatRupiah(product.total_subtotal) }}</span>
            <div class="ml-12 h-1.5 overflow-hidden rounded-full bg-surface-container-low">
              <div class="h-full rounded-full bg-primary" :style="{ width: productBarWidth(product.total_qty) }"></div>
            </div>
            <span class="text-right text-xs text-muted-foreground">{{ product.total_qty }} terjual</span>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<style scoped>
.report-hero-card {
  background: var(--primary);
}

.report-hero-icon {
  color: #fff;
  background: rgba(255, 255, 255, 0.15);
}
</style>
