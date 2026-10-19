<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Package } from 'lucide-vue-next'
import api from '@/lib/api'
import ProductImage from '@/components/products/ProductImage.vue'

interface Product {
  id: number
  category_id: number | null
  category_name: string | null
  name: string
  sku: string
  barcode: string | null
  harga_beli: string
  harga_jual: string
  stok: number
  stok_minimal: number
  status: boolean
  image: string | null
}

interface Category {
  id: number
  name: string
}

const props = defineProps<{
  products: Product[]
  loading: boolean
}>()

const emit = defineEmits<{
  addToCart: [product: Product]
}>()

const categories = ref<Category[]>([])
const selectedCategory = ref<number | null>(null)

const filteredProducts = computed(() => {
  if (!selectedCategory.value) return props.products
  return props.products.filter(p => p.category_id === selectedCategory.value)
})

async function fetchCategories() {
  try {
    const response = await api.get('/categories')
    categories.value = response.data.data
  } catch (err: any) {
    console.error('Failed to fetch categories', err)
  }
}

function formatRupiah(value: string | number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(Number(value))
}

onMounted(fetchCategories)
</script>

<template>
  <div>
    <!-- Category Filter -->
    <div class="mb-4 flex gap-2 flex-wrap">
      <button
        type="button"
        @click="selectedCategory = null"
        :class="[
          'min-h-11 rounded-full border px-4 text-sm font-semibold transition-colors',
          selectedCategory === null
            ? 'warm-primary warm-primary-foreground border-primary'
            : 'warm-card border border-border'
        ]"
      >
        Semua
      </button>
      <button
        v-for="cat in categories"
        :key="cat.id"
        type="button"
        @click="selectedCategory = cat.id"
        :class="[
          'min-h-11 rounded-full border px-4 text-sm font-semibold transition-colors',
          selectedCategory === cat.id
            ? 'warm-primary warm-primary-foreground border-primary'
            : 'warm-card border border-border hover:warm/5'
        ]"
      >
        {{ cat.name }}
      </button>
    </div>

    <!-- Product Grid -->
    <div v-if="loading" class="text-center py-8">Loading...</div>

    <div v-else-if="filteredProducts.length === 0" class="rounded-xl p-8 text-center">
      <Package class="h-12 w-12 mx-auto mb-3 text-muted-foreground" />
      <p class="text-sm text-muted-foreground">Produk belum tersedia</p>
    </div>

    <div v-else class="grid grid-cols-2 gap-3">
      <button
        v-for="product in filteredProducts"
        :key="product.id"
        type="button"
        :disabled="product.stok <= 0"
        @click="emit('addToCart', product)"
        :class="[
          'product-card min-w-0 rounded-2xl border border-border bg-white p-3 text-left transition hover:-translate-y-0.5 hover:shadow-md sm:p-4',
          product.stok <= 0 ? 'cursor-not-allowed opacity-50' : ''
        ]"
      >
        <ProductImage
          :image="product.image"
          :name="product.name"
          class="product-art mb-3 aspect-[1.25/1] rounded-xl"
        />
        <h3 class="truncate text-sm font-semibold text-foreground">{{ product.name }}</h3>
        <p class="mt-1 truncate text-xs text-muted-foreground">{{ product.sku }}</p>
        <p v-if="product.category_name" class="mt-1 truncate text-xs text-blue-600">{{ product.category_name }}</p>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
          <span class="text-sm font-bold text-blue-600 tabular-nums">{{ formatRupiah(product.harga_jual) }}</span>
          <span
            :class="[
              'rounded-full px-2 py-1 text-[10px] font-semibold',
              product.stok <= product.stok_minimal
                ? 'bg-rose-50 text-rose-700'
                : 'bg-emerald-50 text-emerald-700'
            ]"
          >
            Stok: {{ product.stok }}
          </span>
        </div>
      </button>
    </div>
  </div>
</template>

<style scoped>
.product-card:focus-visible {
  outline: 2px solid #3a1e14;
  outline-offset: 2px;
}
</style>
