<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import Swal from 'sweetalert2'

import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

// =====================================================
// API
// =====================================================

const fallbackImage =
  'https://cdn-icons-png.flaticon.com/512/2553/2553691.png'

// =====================================================
// AUTH
// =====================================================

const currentUser = computed(() => {
  if (auth.user) {
    return auth.user
  }

  try {
    return JSON.parse(localStorage.getItem('user')) || null
  } catch {
    return null
  }
})

const isLoggedIn = computed(() => {
  return !!auth.token || !!localStorage.getItem('token')
})

const username = computed(() => {
  return (
    currentUser.value?.name ||
    currentUser.value?.username ||
    currentUser.value?.email ||
    'Pengguna'
  )
})

const userRole = computed(() => {
  return currentUser.value?.role || 'user'
})

// =====================================================
// LOGO
// =====================================================

const logoCemilku = ref('/images/cemilku-logo.png')

const handleLogoError = (event) => {
  if (event.target.dataset.fallbackApplied) {
    return
  }

  event.target.dataset.fallbackApplied = 'true'
  event.target.src = fallbackImage
}

// =====================================================
// DARK MODE
// =====================================================

const isDarkMode = ref(false)

const toggleDarkMode = () => {
  isDarkMode.value = !isDarkMode.value

  localStorage.setItem(
    'theme',
    isDarkMode.value ? 'dark' : 'light'
  )

  window.dispatchEvent(
    new CustomEvent('cemilku-theme-change', {
      detail: {
        dark: isDarkMode.value
      }
    })
  )
}

// =====================================================
// CART
// =====================================================

const cartItemCount = ref(0)
const loadingCart = ref(false)

const fetchCartCount = async () => {
  if (!isLoggedIn.value) {
    cartItemCount.value = 0
    return
  }

  loadingCart.value = true

  try {
    const response = await api.get('/cart')

    const items = response.data?.items || []

    cartItemCount.value = items.reduce(
      (total, item) => {
        return total + Number(item.quantity || 0)
      },
      0
    )
  } catch (error) {
    console.error(
      'Gagal mengambil jumlah keranjang:',
      error
    )

    cartItemCount.value = 0
  } finally {
    loadingCart.value = false
  }
}

const goToCart = () => {
  router.push('/cart')
}

// =====================================================
// NAVIGATION
// =====================================================

const goHome = () => {
  router.push('/')
}

const goToProducts = () => {
  router.push('/products')
}

const goToAbout = () => {
  router.push('/tentang-kami')
}

const goToContact = () => {
  router.push('/kontak')
}

const goToLogin = () => {
  router.push('/login')
}

const goToRegister = () => {
  router.push('/register')
}

const goToProfile = () => {
  router.push('/profile')
}

const goToAdmin = () => {
  router.push('/admin')
}

// =====================================================
// ACTIVE MENU
// =====================================================

const isActive = (path) => {
  if (path === '/') {
    return route.path === '/'
  }

  return route.path.startsWith(path)
}

// =====================================================
// LOGOUT
// =====================================================

const handleLogout = async () => {
  const result = await Swal.fire({
    title: 'Konfirmasi Keluar',
    text: 'Apakah Anda yakin ingin keluar dari akun ini?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#2563eb',
    cancelButtonColor: '#94a3b8',
    confirmButtonText: 'Ya, Keluar',
    cancelButtonText: 'Batal',
    background: isDarkMode.value
      ? '#1e293b'
      : '#ffffff',
    color: isDarkMode.value
      ? '#f8fafc'
      : '#1e293b'
  })

  if (!result.isConfirmed) {
    return
  }

  try {
    await auth.logout()

    cartItemCount.value = 0

    await Swal.fire({
      icon: 'success',
      title: 'Berhasil Keluar 👋',
      text: 'Anda telah keluar dari akun.',
      timer: 1800,
      showConfirmButton: false,
      timerProgressBar: true,
      background: isDarkMode.value
        ? '#1e293b'
        : '#ffffff',
      color: isDarkMode.value
        ? '#f8fafc'
        : '#1e293b'
    })

    router.push('/')
  } catch (error) {
    console.error(
      'Logout gagal:',
      error
    )

    localStorage.removeItem('token')
    localStorage.removeItem('user')

    cartItemCount.value = 0

    router.push('/')
  }
}

// =====================================================
// MOBILE MENU
// =====================================================

const mobileMenuOpen = ref(false)

const closeMobileMenu = () => {
  mobileMenuOpen.value = false
}

const navigateMobile = (callback) => {
  closeMobileMenu()
  callback()
}

// =====================================================
// THEME EVENT
// =====================================================

const handleThemeChange = (event) => {
  if (
    typeof event.detail?.dark === 'boolean'
  ) {
    isDarkMode.value =
      event.detail.dark
  }
}

// =====================================================
// CART EVENT
// =====================================================

const handleCartUpdate = () => {
  fetchCartCount()
}

// =====================================================
// MOUNT
// =====================================================

onMounted(() => {
  const savedTheme =
    localStorage.getItem('theme')

  isDarkMode.value =
    savedTheme === 'dark'

  window.addEventListener(
    'cemilku-theme-change',
    handleThemeChange
  )

  window.addEventListener(
    'cemilku-cart-update',
    handleCartUpdate
  )

  fetchCartCount()
})

// =====================================================
// UNMOUNT
// =====================================================

onUnmounted(() => {
  window.removeEventListener(
    'cemilku-theme-change',
    handleThemeChange
  )

  window.removeEventListener(
    'cemilku-cart-update',
    handleCartUpdate
  )
})
</script>


<template>
  <header
    class="navbar"
    :class="{
      'dark-navbar': isDarkMode
    }"
  >

    <div class="container nav-content">

      <!-- BRAND -->

      <div
        class="brand-logo"
        @click="goHome"
      >

        <div class="brand-mark">

          <img
            :src="logoCemilku"
            alt="Logo Cemilku"
            class="cemilku-logo-img"
            draggable="false"
            @error="handleLogoError"
          />

        </div>

        <div class="brand-info">

          <span class="brand-text">
            Cemilku
          </span>

          <small>
            SNACK STORE
          </small>

        </div>

      </div>


      <!-- DESKTOP NAVIGATION -->

      <nav class="nav-links">

        <button
          class="nav-btn"
          :class="{
            active: isActive('/')
          }"
          @click="goHome"
        >
          Beranda
        </button>

        <button
          class="nav-btn"
          :class="{
            active: isActive('/products')
          }"
          @click="goToProducts"
        >
          Produk
        </button>

        <button
          class="nav-btn"
          :class="{
            active: isActive('/tentang-kami')
          }"
          @click="goToAbout"
        >
          Tentang Kami
        </button>

        <button
          class="nav-btn"
          :class="{
            active: isActive('/kontak')
          }"
          @click="goToContact"
        >
          Kontak
        </button>

        <button
          v-if="userRole === 'admin'"
          class="nav-btn btn-dashboard"
          @click="goToAdmin"
        >
          Dashboard
        </button>

      </nav>


      <!-- AUTH -->

      <div class="nav-auth">

        <!-- THEME -->

        <button
          class="btn-theme-toggle"
          @click="toggleDarkMode"
          :title="
            isDarkMode
              ? 'Mode Terang'
              : 'Mode Gelap'
          "
        >

          <span v-if="isDarkMode">
            ☀️
          </span>

          <span v-else>
            🌙
          </span>

        </button>


        <!-- CART -->

        <button
          v-if="isLoggedIn"
          class="btn-cart"
          :class="{
            active: isActive('/cart')
          }"
          @click="goToCart"
          title="Keranjang"
        >

          <span class="cart-icon">
            🛒
          </span>
          

          <span class="cart-text">
            Keranjang
          </span>
      
          <span
            v-if="cartItemCount > 0"
            class="cart-badge"
          >
            {{
              cartItemCount > 99
                ? '99+'
                : cartItemCount
            }}
          </span>

        </button>
        <button
            class="cart-icon"
            @click="router.push('/orders')"
            >
            📦 Pesanan Saya
         </button>


        <!-- LOGGED IN -->

        <template v-if="isLoggedIn">

          <div class="user-greeting">

            <span class="greet-label">
              Selamat datang,
            </span>

            <strong class="user-email">
              {{ username }}
            </strong>

          </div>


          <button
            v-if="userRole !== 'admin'"
            class="btn-profile"
            @click="goToProfile"
          >
            👤 Profil
          </button>

          <button
            class="btn-logout"
            @click="handleLogout"
          >
            Keluar
          </button>

        </template>


        <!-- GUEST -->

        <template v-else>

          <button
            class="btn-outline"
            @click="goToLogin"
          >
            Masuk
          </button>

          <button
            class="btn-primary"
            @click="goToRegister"
          >
            Daftar
          </button>

        </template>


        <!-- MOBILE BUTTON -->

        <button
          class="mobile-menu-button"
          @click="
            mobileMenuOpen =
              !mobileMenuOpen
          "
        >
          ☰
        </button>

      </div>

    </div>


    <!-- MOBILE MENU -->

    <div
      v-if="mobileMenuOpen"
      class="mobile-menu"
    >

      <button
        :class="{
          active: isActive('/')
        }"
        @click="
          navigateMobile(goHome)
        "
      >
        🏠 Beranda
      </button>


      <button
        :class="{
          active:
            isActive('/products')
        }"
        @click="
          navigateMobile(
            goToProducts
          )
        "
      >
        🛍️ Produk
      </button>


      <button
        :class="{
          active:
            isActive('/tentang-kami')
        }"
        @click="
          navigateMobile(goToAbout)
        "
      >
        ℹ️ Tentang Kami
      </button>


      <button
        :class="{
          active:
            isActive('/kontak')
        }"
        @click="
          navigateMobile(goToContact)
        "
      >
        📞 Kontak
      </button>


      <!-- MOBILE CART -->

      <button
        v-if="isLoggedIn"
        class="mobile-cart"
        :class="{
          active: isActive('/cart')
        }"
        @click="
          navigateMobile(goToCart)
        "
      >
        🛒 Keranjang

        <span
          v-if="cartItemCount > 0"
          class="mobile-cart-badge"
        >
          {{ cartItemCount }}
        </span>
      </button>


      <button
        v-if="userRole === 'admin'"
        class="mobile-dashboard"
        @click="
          navigateMobile(goToAdmin)
        "
      >
        📊 Dashboard
      </button>

    </div>

  </header>
</template>


<style scoped>
/* =====================================================
   NAVBAR
===================================================== */

.navbar {
  position: sticky;
  top: 0;
  z-index: 1000;

  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);

  background: rgba(255, 255, 255, 0.82);

  border-bottom:
    1px solid rgba(226, 232, 240, 0.8);

  transition:
    background 0.3s ease,
    border-color 0.3s ease;
}

.container {
  max-width: 1200px;
  margin: 0 auto;

  padding: 0 24px;

  width: 100%;
  box-sizing: border-box;
}

.nav-content {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 20px;

  min-height: 76px;
}


/* =====================================================
   BRAND
===================================================== */

.brand-logo {
  display: flex;
  align-items: center;

  gap: 12px;

  cursor: pointer;

  transition:
    transform 0.3s ease;
}

.brand-logo:hover {
  transform: translateY(-2px);
}

.brand-mark {
  width: 42px;
  height: 42px;

  border-radius: 14px;

  background:
    linear-gradient(
      135deg,
      #2563eb,
      #3b82f6
    );

  display: flex;
  align-items: center;
  justify-content: center;

  overflow: hidden;

  padding: 6px;

  box-shadow:
    0 8px 16px -4px
    rgba(37, 99, 235, 0.3);
}

.cemilku-logo-img {
  width: 100%;
  height: 100%;

  object-fit: contain;

  display: block;
}

.brand-text {
  color: #0f172a;

  font-size: 1.35rem;
  font-weight: 800;

  letter-spacing: -0.03em;

  line-height: 1;
}

.brand-info small {
  display: block;

  margin-top: 3px;

  letter-spacing: 0.15em;
  text-transform: uppercase;

  color: #2563eb;

  font-size: 0.6rem;
  font-weight: 800;
}


/* =====================================================
   NAVIGATION
===================================================== */

.nav-links {
  display: flex;
  align-items: center;

  gap: 4px;

  background:
    rgba(241, 245, 249, 0.7);

  padding: 5px;

  border-radius: 14px;

  border:
    1px solid #e2e8f0;
}

.nav-btn {
  background: transparent;

  border: none;

  color: #64748b;

  padding: 8px 18px;

  font-size: 0.85rem;
  font-weight: 600;

  border-radius: 10px;

  cursor: pointer;

  transition:
    all 0.25s ease;
}

.nav-btn:hover {
  color: #2563eb;

  background:
    rgba(255, 255, 255, 0.6);
}

.nav-btn.active {
  background: #ffffff;

  color: #2563eb;

  box-shadow:
    0 2px 8px
    rgba(0, 0, 0, 0.04);
}

.btn-dashboard {
  background: #2563eb !important;
  color: #ffffff !important;
}

.btn-dashboard:hover {
  background: #1d4ed8 !important;
}


/* =====================================================
   AUTH
===================================================== */

.nav-auth {
  display: flex;
  align-items: center;

  gap: 12px;
}


/* =====================================================
   THEME
===================================================== */

.btn-theme-toggle {
  width: 38px;
  height: 38px;

  background:
    rgba(241, 245, 249, 0.8);

  border:
    1px solid #e2e8f0;

  border-radius: 12px;

  display: flex;
  align-items: center;
  justify-content: center;

  cursor: pointer;

  font-size: 1.1rem;

  transition:
    all 0.25s ease;
}

.btn-theme-toggle:hover {
  transform: translateY(-1px);

  border-color: #bfdbfe;
}


/* =====================================================
   CART
===================================================== */

.btn-cart {
  position: relative;

  display: flex;
  align-items: center;

  gap: 7px;

  min-height: 38px;

  padding: 8px 13px;

  border:
    1px solid #bfdbfe;

  border-radius: 12px;

  background: #eff6ff;

  color: #2563eb;

  font-size: 0.82rem;
  font-weight: 700;

  cursor: pointer;

  transition:
    all 0.25s ease;
}

.btn-cart:hover,
.btn-cart.active {
  background: #2563eb;
  color: #ffffff;

  border-color: #2563eb;

  transform: translateY(-1px);

  box-shadow:
    0 7px 18px
    rgba(37, 99, 235, 0.2);
}

.cart-icon {
  font-size: 1rem;
}

.cart-text {
  white-space: nowrap;
}

.cart-badge {
  position: absolute;

  top: -7px;
  right: -7px;

  min-width: 20px;
  height: 20px;

  padding: 0 5px;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 20px;

  background: #dc2626;

  border: 2px solid #ffffff;

  color: #ffffff;

  font-size: 10px;
  font-weight: 800;

  line-height: 1;
}


/* =====================================================
   USER
===================================================== */

.user-greeting {
  display: flex;
  flex-direction: column;

  text-align: right;

  line-height: 1.2;
}

.greet-label {
  font-size: 0.65rem;
  color: #64748b;
}

.user-email {
  font-size: 0.85rem;

  color: #1e293b;

  font-weight: 700;
}

.btn-profile,
.btn-outline,
.btn-primary,
.btn-logout {
  padding: 8px 18px;

  border-radius: 12px;

  font-size: 0.85rem;
  font-weight: 700;

  cursor: pointer;

  transition:
    all 0.25s ease;
}

.btn-profile {
  background: #eff6ff;

  border:
    1px solid #bfdbfe;

  color: #2563eb;
}

.btn-profile:hover {
  background: #2563eb;
  color: #ffffff;
}

.btn-logout {
  background: #fef2f2;

  border:
    1px solid #fecaca;

  color: #dc2626;
}

.btn-logout:hover {
  background: #dc2626;
  color: #ffffff;
}

.btn-outline {
  background: #ffffff;

  border:
    1px solid #cbd5e1;

  color: #334155;
}

.btn-outline:hover {
  border-color: #3b82f6;

  color: #2563eb;

  background: #f0f7ff;
}

.btn-primary {
  background:
    linear-gradient(
      135deg,
      #2563eb,
      #3b82f6
    );

  border: none;

  color: #ffffff;

  box-shadow:
    0 4px 14px
    rgba(37, 99, 235, 0.25);
}

.btn-primary:hover {
  box-shadow:
    0 8px 20px
    rgba(37, 99, 235, 0.35);

  transform: translateY(-1.5px);
}


/* =====================================================
   MOBILE
===================================================== */

.mobile-menu-button {
  display: none;

  width: 38px;
  height: 38px;

  border-radius: 12px;

  border:
    1px solid #e2e8f0;

  background: #f8fafc;

  color: #2563eb;

  cursor: pointer;

  font-size: 1.1rem;
}

.mobile-menu {
  display: none;
}


/* =====================================================
   DARK MODE
===================================================== */

.dark-navbar {
  background:
    rgba(15, 23, 42, 0.88);

  border-bottom-color:
    #334155;
}

.dark-navbar .brand-text {
  color: #f8fafc;
}

.dark-navbar .nav-links {
  background:
    rgba(30, 41, 59, 0.8);

  border-color: #334155;
}

.dark-navbar .nav-btn {
  color: #94a3b8;
}

.dark-navbar .nav-btn:hover {
  color: #60a5fa;
}

.dark-navbar .nav-btn.active {
  background: #1e293b;
  color: #60a5fa;
}

.dark-navbar .btn-theme-toggle {
  background: #1e293b;
  border-color: #334155;
}

.dark-navbar .btn-cart {
  background: #1e293b;
  border-color: #3b82f6;
  color: #60a5fa;
}

.dark-navbar .btn-cart:hover,
.dark-navbar .btn-cart.active {
  background: #2563eb;
  color: #ffffff;
}

.dark-navbar .cart-badge {
  border-color: #0f172a;
}

.dark-navbar .user-email {
  color: #f8fafc;
}

.dark-navbar .greet-label {
  color: #94a3b8;
}

.dark-navbar .btn-outline {
  background: #1e293b;
  border-color: #475569;
  color: #e2e8f0;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 1100px) {

  .nav-content {
    gap: 12px;
  }

  .nav-btn {
    padding: 8px 12px;
  }

  .user-greeting {
    display: none;
  }

  .cart-text {
    display: none;
  }

  .btn-cart {
    width: 38px;
    padding: 8px;

    justify-content: center;
  }
}


@media (max-width: 900px) {

  .nav-links {
    display: none;
  }

  .mobile-menu-button {
    display: flex;

    align-items: center;
    justify-content: center;
  }

  .mobile-menu {
    display: flex;

    flex-direction: column;

    gap: 6px;

    padding:
      12px 16px 16px;

    background:
      rgba(255, 255, 255, 0.96);

    border-top:
      1px solid #e2e8f0;
  }

  .mobile-menu button {
    border: none;

    background: transparent;

    text-align: left;

    padding: 12px 14px;

    border-radius: 10px;

    color: #475569;

    font-size: 0.9rem;
    font-weight: 700;

    cursor: pointer;
  }

  .mobile-menu button:hover,
  .mobile-menu button.active {
    background: #eff6ff;
    color: #2563eb;
  }

  .mobile-cart {
    display: flex;

    align-items: center;
    justify-content: space-between;

    width: 100%;
  }

  .mobile-cart-badge {
    min-width: 22px;
    height: 22px;

    padding: 0 6px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 20px;

    background: #dc2626;

    color: #ffffff;

    font-size: 10px;
    font-weight: 800;
  }

  .mobile-dashboard {
    background: #2563eb !important;
    color: #ffffff !important;
  }

  .dark-navbar .mobile-menu {
    background: #0f172a;
    border-color: #334155;
  }

  .dark-navbar .mobile-menu button {
    color: #cbd5e1;
  }

  .dark-navbar .mobile-menu button:hover,
  .dark-navbar .mobile-menu button.active {
    background: #1e293b;
    color: #60a5fa;
  }

  .dark-navbar .mobile-cart {
    color: #60a5fa !important;
  }

  .dark-navbar .mobile-cart:hover,
  .dark-navbar .mobile-cart.active {
    background: #1e293b !important;
    color: #60a5fa !important;
  }

  .dark-navbar .mobile-dashboard {
    background: #2563eb !important;
    color: #ffffff !important;
  }
}


@media (max-width: 650px) {

  .container {
    padding: 0 16px;
  }

  .brand-info {
    display: none;
  }

  .nav-auth {
    gap: 6px;
  }

  .btn-theme-toggle {
    width: 34px;
    height: 34px;
  }

  .btn-cart {
    width: 34px;
    height: 34px;

    min-height: 34px;

    padding: 6px;
  }

  .btn-profile,
  .btn-logout,
  .btn-outline,
  .btn-primary {
    padding: 8px 12px;

    font-size: 0.75rem;
  }
}


@media (max-width: 480px) {

  .btn-profile {
    display: none;
  }

  .btn-logout {
    padding: 8px 10px;
  }

  .btn-outline,
  .btn-primary {
    padding: 8px 10px;
  }
}
</style>