import { createRouter, createWebHistory } from 'vue-router'

import HomeView from '../views/HomeView.vue'
import TentangkamiView from '../views/TentangkamiView.vue'
import KontakView from '../views/KontakView.vue'

import LoginView from '../views/auth/LoginView.vue'
import RegisterView from '../views/auth/RegisterView.vue'

import ProductListView from '../views/products/ProductListView.vue'
import ProductDetailView from '../views/products/ProductDetailView.vue'
import CartView from '../views/CartView.vue'
import CheckoutView from '../views/CheckoutView.vue'
import OrderDetailView from '../views/OrderDetailView.vue'
import OrdersView from '../views/OrdersView.vue'
import ProfileView from '../views/ProfileView.vue'

import AdminDashboardView from '../views/admin/AdminDashboardView.vue'
import AdminProdukView from '../views/admin/AdminProdukView.vue'
import AdminKategoriView from '../views/admin/AdminKategoriView.vue'
import AdminOrderView from '../views/admin/AdminOrderView.vue'
import AdminOrderItemView from '../views/admin/AdminOrderItemView.vue'
import AdminContactView from '../views/admin/AdminContactView.vue'
import AdminPengaturanView from '../views/admin/AdminPengaturanView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),

  routes: [
    // =========================
    // PUBLIC
    // =========================
    {
      path: '/',
      name: 'home',
      component: HomeView
    },

    {
      path: '/tentang-kami',
      name: 'tentang-kami',
      component: TentangkamiView
    },

    {
      path: '/kontak',
      name: 'kontak',
      component: KontakView
    },

    // =========================
    // AUTH
    // =========================
    {
      path: '/login',
      name: 'login',
      component: LoginView
    },

    {
      path: '/register',
      name: 'register',
      component: RegisterView
    },

    // =========================
    // CUSTOMER
    // =========================
    {
      path: '/cart',
      name: 'cart',
      component: CartView
    },

    {
      path: '/checkout',
      name: 'checkout',
      component: CheckoutView
    },

    {
    path: '/orders',
    name: 'orders',
    component: OrdersView
    },

    {
      path: '/orders/:id',
      name: 'order-detail',
      component: OrderDetailView
    },

    {
      path: '/products',
      name: 'products',
      component: ProductListView
    },

    {
      path: '/products/:id',
      name: 'product-detail',
      component: ProductDetailView
    },

    {
      path: '/profile',
      name: 'profile',
      component: ProfileView
    },

    // =========================
    // ADMIN
    // =========================
    {
      path: '/admin',
      name: 'admin-dashboard',
      component: AdminDashboardView
    },

    {
      path: '/admin/produk',
      name: 'admin-produk',
      component: AdminProdukView
    },

    {
      path: '/admin/kategori',
      name: 'admin-kategori',
      component: AdminKategoriView
    },

    {
      path: '/admin/order',
      name: 'admin-order',
      component: AdminOrderView
    },

    {
      path: '/admin/order-item',
      name: 'admin-order-item',
      component: AdminOrderItemView
    },

    {
      path: '/admin/kontak',
      name: 'admin-kontak',
      component: AdminContactView
    },

    {
      path: '/admin/pengaturan',
      name: 'admin-pengaturan',
      component: AdminPengaturanView
    },
    {
      path: '/auth/callback',
      component: () => import('@/views/AuthCallback.vue')
    }
  ]
})

router.beforeEach((to, from, next) => {
  const role = localStorage.getItem('role') || localStorage.getItem('userRole') || 'user'

  if (to.path === '/profile' && role === 'admin') {
    next('/admin')
    return
  }

  next()
})

export default router