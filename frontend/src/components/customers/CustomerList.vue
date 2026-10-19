<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/lib/api'

interface Customer {
  id: number
  name: string
  phone: string | null
  email: string | null
  address: string | null
  poin: number
  status: boolean
}

const customers = ref<Customer[]>([])
const loading = ref(false)
const showForm = ref(false)
const editingCustomer = ref<Customer | null>(null)
const form = ref({
  name: '',
  phone: '',
  email: '',
  address: '',
  poin: 0,
  status: true,
})
const message = ref('')

async function fetchCustomers() {
  loading.value = true
  try {
    const response = await api.get('/customers')
    customers.value = response.data.data
  } catch (err: any) {
    message.value = err.response?.data?.message || 'Failed to fetch customers'
  } finally {
    loading.value = false
  }
}

function openAddForm() {
  editingCustomer.value = null
  form.value = { name: '', phone: '', email: '', address: '', poin: 0, status: true }
  showForm.value = true
}

function openEditForm(customer: Customer) {
  editingCustomer.value = customer
  form.value = {
    name: customer.name,
    phone: customer.phone || '',
    email: customer.email || '',
    address: customer.address || '',
    poin: customer.poin,
    status: customer.status,
  }
  showForm.value = true
}

async function saveCustomer() {
  try {
    if (editingCustomer.value) {
      await api.put(`/customers/${editingCustomer.value.id}`, form.value)
      message.value = 'Customer updated successfully'
    } else {
      await api.post('/customers', form.value)
      message.value = 'Customer created successfully'
    }
    showForm.value = false
    fetchCustomers()
  } catch (err: any) {
    message.value = err.response?.data?.message || 'Failed to save customer'
  }
}

async function deleteCustomer(id: number) {
  if (!confirm('Are you sure you want to delete this customer?')) return

  try {
    await api.delete(`/customers/${id}`)
    message.value = 'Customer deleted successfully'
    fetchCustomers()
  } catch (err: any) {
    message.value = err.response?.data?.message || 'Failed to delete customer'
  }
}

onMounted(fetchCustomers)
</script>

<template>
  <div class="space-y-4 py-2">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-foreground">Customers</h1>
      <button
        @click="openAddForm"
        class="warm-primary px-4 py-2 rounded-md text-white font-medium hover:warm-primary/90"
      >
        Add Customer
      </button>
    </div>

    <div v-if="message" class="mb-4 p-3 warm-card rounded-lg p-3">
      {{ message }}
    </div>

    <!-- Form -->
    <div v-if="showForm" class="mb-6 p-4 warm-card rounded-lg shadow-sm">
      <h2 class="text-lg font-semibold mb-4">
        {{ editingCustomer ? 'Edit Customer' : 'Add Customer' }}
      </h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-muted-foreground mb-1">Name</label>
          <input
            v-model="form.name"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Customer name"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-muted-foreground mb-1">Phone</label>
          <input
            v-model="form.phone"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Phone number"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-muted-foreground mb-1">Email</label>
          <input
            v-model="form.email"
            type="email"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Email"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-muted-foreground mb-1">Poin</label>
          <input
            v-model.number="form.poin"
            type="number"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="0"
          />
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-muted-foreground mb-1">Address</label>
          <textarea
            v-model="form.address"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Address"
            rows="2"
          ></textarea>
        </div>
        <div class="flex items-center">
          <input
            v-model="form.status"
            type="checkbox"
            id="status"
            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
          />
          <label for="status" class="ml-2 block text-muted-foreground">Active</label>
        </div>
      </div>
      <div class="flex gap-2 mt-4">
        <button
          @click="saveCustomer"
          class="px-4 py-2 warm-primary rounded-md text-white font-medium hover:warm-primary/90"
        >
          Save
        </button>
        <button
          @click="showForm = false"
          class="px-4 py-2 warm-card rounded-md text-foreground hover:bg-muted"
        >
          Cancel
        </button>
      </div>
    </div>

    <!-- List -->
    <div v-if="loading" class="text-center py-8">Loading...</div>

    <div v-else class="bg-white rounded-lg shadow-md overflow-hidden">
      <table class="min-w-full divide-y divide-border">
        <thead class="bg-card">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Phone</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Email</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Poin</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-card divide-y divide-muted-foreground/20">
          <tr v-for="customer in customers" :key="customer.id">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-foreground">{{ customer.name }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ customer.phone || '-' }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ customer.email || '-' }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-foreground">{{ customer.poin }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                :class="[
                  'px-2 py-1 text-xs font-medium rounded-full',
                  customer.status ? 'warm-card bg-emerald-50 text-emerald-700' : 'warm-card bg-rose-50 text-rose-700'
                ]"
              >
                {{ customer.status ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
              <button
                @click="openEditForm(customer)"
                class="text-primary hover:text-primary-foreground mr-3"
              >
                Edit
              </button>
              <button
                @click="deleteCustomer(customer.id)"
                class="text-destructive hover:text-destructive-800"
              >
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
