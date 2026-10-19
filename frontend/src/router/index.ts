import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    redirect: '/pos',
  },
  {
    path: '/pos',
    name: 'pos',
    component: () => import('@/components/pos/PosInterface.vue'),
  },
  {
    path: '/products',
    name: 'products',
    component: () => import('@/components/products/ProductList.vue'),
  },
  {
    path: '/categories',
    name: 'categories',
    component: () => import('@/components/categories/CategoryList.vue'),
  },
  {
    path: '/customers',
    name: 'customers',
    component: () => import('@/components/customers/CustomerList.vue'),
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
