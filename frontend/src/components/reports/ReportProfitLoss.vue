<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/lib/api'
import { downloadFile, printPage } from '@/lib/download'

interface ProfitLoss {
  pendapatan: string
  hpp: string
  diskon: string
  ppn: string
  laba_kotor: string
}

const data = ref<ProfitLoss | null>(null)
const loading = ref(false)
const startDate = ref(new Date().toISOString().split('T')[0])
const endDate = ref(new Date().toISOString().split('T')[0])

async function fetchReport() {
  loading.value = true
  try {
    const response = await api.get('/reports/profit-loss', {
      params: { start_date: startDate.value, end_date: endDate.value }
    })
    data.value = response.data
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

onMounted(fetchReport)

async function exportCsv() {
  try {
    await downloadFile('/reports/profit-loss/export', {
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
  <div class="space-y-4 py-2">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-foreground">Laporan Laba Rugi</h1>
    </div>

    <!-- Filter -->
    <div class="mb-6 p-4 warm-card rounded-lg p-3">
      <div class="flex gap-4 items-end">
        <div>
          <label class="block text-sm font-medium text-muted-foreground mb-1">Tanggal Mulai</label>
          <input
            v-model="startDate"
            type="date"
            class="warm-input w-full px-3 py-2 rounded-md border border-border focus:outline-none focus:ring-2 focus:ring-primary"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-muted-foreground mb-1">Tanggal Akhir</label>
          <input
            v-model="endDate"
            type="date"
            class="warm-input w-full px-3 py-2 rounded-md border border-border focus:outline-none focus:ring-2 focus:ring-primary"
          />
        </div>
        <button
          @click="fetchReport"
          class="warm-primary px-4 py-2 rounded-md text-white font-medium hover:warm-primary/90"
        >
          Tampilkan
        </button>
        <div class="flex gap-2 ml-auto">
          <button
            @click="exportCsv"
            class="warm-card px-4 py-2 rounded-md text-primary font-medium hover:warm-primary/90"
          >
            Export CSV
          </button>
          <button
            @click="printPage"
            class="warm-card px-4 py-2 rounded-md text-muted-foreground hover:bg-muted"
          >
            Print / PDF
          </button>
        </div>
      </div>
    </div>

    <div v-if="loading" class="text-center py-8">Loading...</div>

    <div v-else-if="data" class="warm-card rounded-lg p-6">
      <div class="space-y-4">
        <!-- Pendapatan -->
        <div class="flex justify-between items-center p-4 warm-card rounded-lg">
          <span class="text-muted-foreground font-medium">Pendapatan</span>
          <span class="text-xl font-bold text-primary">{{ formatRupiah(data.pendapatan) }}</span>
        </div>

        <!-- HPP -->
        <div class="flex justify-between items-center p-4 warm-card rounded-lg bg-red-50">
          <span class="text-muted-foreground font-medium">Harga Pokok Penjualan (HPP)</span>
          <span class="text-xl font-bold text-red-600">-{{ formatRupiah(data.hpp) }}</span>
        </div>

        <!-- Diskon -->
        <div class="flex justify-between items-center p-4 warm-card rounded-lg bg-yellow-50">
          <span class="text-muted-foreground font-medium">Diskon</span>
          <span class="text-xl font-bold text-primary">-{{ formatRupiah(data.diskon) }}</span>
        </div>

        <!-- PPN -->
        <div class="flex justify-between items-center p-4 warm-card rounded-lg bg-primary">
          <span class="text-muted-foreground font-medium">PPN</span>
          <span class="text-xl font-bold text-primary-foreground">+{{ formatRupiah(data.ppn) }}</span>
        </div>

        <!-- Laba Kotor -->
        <div class="flex justify-between items-center p-4 warm-card rounded-lg border border-border">
          <span class="text-muted-foreground font-bold text-lg">Laba Kotor</span>
          <span
            :class="[
              'text-2xl font-bold',
              Number(data.laba_kotor) >= 0 ? 'text-primary' : 'text-destructive'
            ]"
          >
            {{ formatRupiah(data.laba_kotor) }}
          </span>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-8 text-muted-foreground">
      Tidak ada data
    </div>
  </div>
</template>
