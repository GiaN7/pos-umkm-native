<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/lib/api'

interface Sale {
  id: number
  no_transaksi: string
  created_at: string
  subtotal: string
  diskon: string
  ppn: string
  total: string
  bayar: string
  kembali: string
  payment_method: string
  status: string
  user: {
    name: string
  }
  customer: {
    name: string
  } | null
  items: Array<{
    product: {
      name: string
    }
    qty: number
    harga: string
    diskon: number
    subtotal: string
  }>
}

const sales = ref<Sale[]>([])
const loading = ref(false)
const selectedSale = ref<Sale | null>(null)
const showDetail = ref(false)
const message = ref('')

async function fetchSales() {
  loading.value = true
  try {
    const response = await api.get('/sales')
    sales.value = response.data.data
  } catch (err: any) {
    message.value = err.response?.data?.message || 'Failed to fetch sales'
  } finally {
    loading.value = false
  }
}

function viewDetail(sale: Sale) {
  selectedSale.value = sale
  showDetail.value = true
}

function closeDetail() {
  showDetail.value = false
  selectedSale.value = null
}

async function handleRetur(sale: Sale) {
  if (!confirm(`Anda yakin ingin melakukan retur pada transaksi ${sale.no_transaksi}?`)) return

  try {
    await api.put(`/sales/${sale.id}/status`, { status: 'retur' })
    message.value = `Transaksi ${sale.no_transaksi} berhasil di-retur`
    showDetail.value = false
    fetchSales()
  } catch (err: any) {
    message.value = err.response?.data?.message || 'Retur gagal'
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
  return new Date(date).toLocaleString('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

onMounted(fetchSales)
</script>

<template>
  <div class="space-y-4 py-2">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-foreground">Riwayat Transaksi</h1>
      <button
        @click="fetchSales"
        class="warm-primary px-4 py-2 rounded-md text-white font-medium hover:warm-primary/90"
      >
        Refresh
      </button>
    </div>

    <div v-if="message" class="mb-4 p-3 warm-card rounded-lg p-3">
      {{ message }}
    </div>

    <!-- Sales List -->
    <div v-if="loading" class="text-center py-8">Loading...</div>

    <div v-else class="overflow-hidden rounded-2xl border border-border bg-white shadow-sm">
      <p class="px-4 pt-3 text-xs text-muted-foreground sm:hidden">Geser tabel ke samping untuk melihat semua kolom.</p>
      <div
        class="max-w-full overflow-x-auto"
        role="region"
        aria-label="Riwayat transaksi"
        tabindex="0"
      >
        <table class="min-w-[760px] divide-y divide-border">
          <thead class="bg-card">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">No Transaksi</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Tanggal</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Kasir</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Total</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Payment</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="sale in sales" :key="sale.id">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-foreground">{{ sale.no_transaksi }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ formatDate(sale.created_at) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ sale.user.name }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-foreground">{{ formatRupiah(sale.total) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground uppercase">{{ sale.payment_method }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'px-2 py-1 text-xs font-medium rounded-full',
                    sale.status === 'selesai' ? 'warm-card bg-emerald-50 text-emerald-700' : 'warm-card bg-rose-50 text-rose-700'
                  ]"
                >
                  {{ sale.status }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <button
                  @click="viewDetail(sale)"
                  class="text-blue-600 hover:text-blue-900"
                >
                  Detail
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Sale Detail Modal -->
    <div v-if="showDetail && selectedSale" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-4 border-b flex justify-between items-center">
          <h2 class="text-lg font-bold text-foreground">Detail Transaksi</h2>
          <button @click="closeDetail" class="icon-button flex h-10 w-10 items-center justify-center rounded-full text-muted-foreground">✕</button>
        </div>

        <div class="p-4 space-y-4">
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
              <p class="text-muted-foreground">No Transaksi</p>
              <p class="font-medium">{{ selectedSale.no_transaksi }}</p>
            </div>
            <div>
              <p class="text-muted-foreground">Tanggal</p>
              <p class="font-medium">{{ formatDate(selectedSale.created_at) }}</p>
            </div>
            <div>
              <p class="text-muted-foreground">Kasir</p>
              <p class="font-medium">{{ selectedSale.user.name }}</p>
            </div>
            <div>
              <p class="text-muted-foreground">Pelanggan</p>
              <p class="font-medium">{{ selectedSale.customer?.name || 'Umum' }}</p>
            </div>
            <div>
              <p class="text-muted-foreground">Payment</p>
              <p class="font-medium uppercase">{{ selectedSale.payment_method }}</p>
            </div>
            <div>
              <p class="text-muted-foreground">Status</p>
              <p class="font-medium">{{ selectedSale.status }}</p>
            </div>
          </div>

          <div class="border-t pt-4">
            <h3 class="font-medium text-foreground mb-2">Items</h3>
            <div class="space-y-2">
              <div
                v-for="item in selectedSale.items"
                :key="item.product.name"
                class="flex justify-between text-sm"
              >
                <div>
                  <p class="font-medium">{{ item.product.name }}</p>
                  <p class="text-muted-foreground">{{ item.qty }} x {{ formatRupiah(item.harga) }}</p>
                </div>
                <p class="font-medium">{{ formatRupiah(item.subtotal) }}</p>
              </div>
            </div>
          </div>

          <div class="border-t pt-4 space-y-1 text-sm">
            <div class="flex justify-between">
              <span>Subtotal</span>
              <span>{{ formatRupiah(selectedSale.subtotal) }}</span>
            </div>
            <div v-if="Number(selectedSale.diskon) > 0" class="flex justify-between text-red-600">
              <span>Diskon</span>
              <span>-{{ formatRupiah(selectedSale.diskon) }}</span>
            </div>
            <div v-if="Number(selectedSale.ppn) > 0" class="flex justify-between">
              <span>PPN</span>
              <span>{{ formatRupiah(selectedSale.ppn) }}</span>
            </div>
            <div class="flex justify-between font-bold text-base pt-1 border-t">
              <span>Total</span>
              <span>{{ formatRupiah(selectedSale.total) }}</span>
            </div>
            <div class="flex justify-between">
              <span>Bayar</span>
              <span>{{ formatRupiah(selectedSale.bayar) }}</span>
            </div>
            <div class="flex justify-between">
              <span>Kembali</span>
              <span>{{ formatRupiah(selectedSale.kembali) }}</span>
            </div>
          </div>
        </div>

        <div class="p-4 border-t flex gap-2">
          <button
            @click="closeDetail"
            class="flex-1 py-2 warm-card rounded-lg font-medium text-muted-foreground hover:warm/5"
          >
            Tutup
          </button>
          <button
            v-if="selectedSale.status === 'selesai'"
            @click="handleRetur(selectedSale)"
            class="flex-1 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700"
          >
            Retur
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
