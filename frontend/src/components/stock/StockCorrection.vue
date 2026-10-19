<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/lib/api'

interface Product {
  id: number
  name: string
  sku: string
  stok: number
  stok_minimal: number
}

interface StockCorrection {
  id: number
  product_id: number
  stok_sebelum: number
  stok_sesudah: number
  selisih: number
  tipe: string
  keterangan: string | null
  created_at: string
  product: {
    name: string
  }
  user: {
    name: string
  }
}

const products = ref<Product[]>([])
const corrections = ref<StockCorrection[]>([])
const loading = ref(false)
const showForm = ref(false)
const form = ref({
  product_id: null as number | null,
  stok_sesudah: 0,
  tipe: 'penyesuaian',
  keterangan: '',
})
const message = ref('')

async function fetchProducts() {
  try {
    const response = await api.get('/products')
    products.value = response.data.data
  } catch (err: any) {
    console.error('Failed to fetch products', err)
  }
}

async function fetchCorrections() {
  loading.value = true
  try {
    const response = await api.get('/stock-corrections')
    corrections.value = response.data.data
  } catch (err: any) {
    message.value = err.response?.data?.message || 'Failed to fetch corrections'
  } finally {
    loading.value = false
  }
}

function openForm() {
  form.value = {
    product_id: null,
    stok_sesudah: 0,
    tipe: 'penyesuaian',
    keterangan: '',
  }
  showForm.value = true
}

async function saveCorrection() {
  if (!form.value.product_id) {
    message.value = 'Pilih produk terlebih dahulu'
    return
  }

  try {
    await api.post('/stock-corrections', form.value)
    message.value = 'Koreksi stok berhasil'
    showForm.value = false
    fetchCorrections()
    fetchProducts()
  } catch (err: any) {
    message.value = err.response?.data?.message || 'Koreksi stok gagal'
  }
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

function getTipeLabel(tipe: string): string {
  const labels: Record<string, string> = {
    masuk: 'Masuk',
    keluar: 'Keluar',
    penyesuaian: 'Penyesuaian',
  }
  return labels[tipe] || tipe
}

function getTipeColor(tipe: string): string {
  const colors: Record<string, string> = {
    masuk: 'warm-card bg-emerald-50 text-emerald-700',
    keluar: 'warm-card bg-rose-50 text-rose-700',
    penyesuaian: 'warm-card bg-primary/10 text-primary',
  }
  return colors[tipe] || 'warm-card text-muted-foreground'
}

onMounted(() => {
  fetchProducts()
  fetchCorrections()
})
</script>

<template>
  <div class="space-y-4 py-2">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-foreground">Koreksi Stok</h1>
      <button
        @click="openForm"
        class="warm-primary px-4 py-2 rounded-md text-white font-medium hover:warm-primary/90"
      >
        Koreksi Stok
      </button>
    </div>

    <div v-if="message" class="mb-4 p-3 warm-card rounded-lg p-3">
      {{ message }}
    </div>

    <!-- Form -->
    <div v-if="showForm" class="mb-6 warm-card rounded-lg p-4">
      <h2 class="text-lg font-semibold mb-4">Koreksi Stok Baru</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-muted-foreground mb-1">Produk</label>
          <select
            v-model="form.product_id"
            class="warm-input w-full px-3 py-2 rounded-md border border-border focus:outline-none focus:ring-2 focus:ring-primary"
          >
            <option :value="null">Pilih Produk</option>
            <option v-for="product in products" :key="product.id" :value="product.id">
              {{ product.name }} (Stok: {{ product.stok }})
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-muted-foreground mb-1">Tipe</label>
          <select
            v-model="form.tipe"
            class="warm-input w-full px-3 py-2 rounded-md border border-border focus:outline-none focus:ring-2 focus:ring-primary"
          >
            <option value="masuk">Masuk</option>
            <option value="keluar">Keluar</option>
            <option value="penyesuaian">Penyesuaian</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-muted-foreground mb-1">Stok Sesudah</label>
          <input
            v-model.number="form.stok_sesudah"
            type="number"
            class="warm-input w-full px-3 py-2 rounded-md border border-border focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="0"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-muted-foreground mb-1">Keterangan</label>
          <input
            v-model="form.keterangan"
            type="text"
            class="warm-input w-full px-3 py-2 rounded-md border border-border focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="Keterangan"
          />
        </div>
      </div>
      <div class="flex gap-2 mt-4">
        <button
          @click="saveCorrection"
          class="warm-primary px-4 py-2 rounded-md text-white font-medium hover:warm-primary/90"
        >
          Simpan
        </button>
        <button
          @click="showForm = false"
          class="warm-card px-4 py-2 rounded-md text-muted-foreground hover:bg-muted"
        >
          Batal
        </button>
      </div>
    </div>

    <!-- Corrections List -->
    <div v-if="loading" class="text-center py-8">Loading...</div>

    <div v-else class="warm-card rounded-lg shadow-md overflow-hidden">
      <table class="min-w-full divide-y divide-border">
        <thead class="bg-card">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Tanggal</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Produk</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Tipe</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Stok Sebelum</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Stok Sesudah</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Selisih</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Keterangan</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">User</th>
          </tr>
        </thead>
        <tbody class="bg-card divide-y divide-muted-foreground/20">
          <tr v-for="correction in corrections" :key="correction.id">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ formatDate(correction.created_at) }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-foreground">{{ correction.product.name }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                :class="[
                  'px-2 py-1 text-xs font-medium rounded-full',
                  getTipeColor(correction.tipe)
                ]"
              >
                {{ getTipeLabel(correction.tipe) }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-foreground">{{ correction.stok_sebelum }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-foreground">{{ correction.stok_sesudah }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                :class="[
                  'text-sm font-medium',
                  correction.selisih > 0 ? 'text-primary' : correction.selisih < 0 ? 'text-destructive' : 'text-muted-foreground'
                ]"
              >
                {{ correction.selisih > 0 ? '+' : '' }}{{ correction.selisih }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ correction.keterangan || '-' }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ correction.user.name }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
