<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const emit = defineEmits(['logout'])

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const menuItems = [
  {
    name: 'Dashboard',
    icon: '▦',
    path: '/admin'
  },
  {
    name: 'Produk',
    icon: '📦',
    path: '/admin/produk'
  },
  {
    name: 'Kategori',
    icon: '🗂️',
    path: '/admin/kategori'
  },
  {
    name: 'Order',
    icon: '🛒',
    path: '/admin/order'
  },
  {
    name: 'Order Item',
    icon: '📋',
    path: '/admin/order-item'
  },
    {
        name: 'Kontak',
        icon: '📞',
        path: '/admin/kontak'
    },
  {
    name: 'Pengaturan',
    icon: '⚙️',
    path: '/admin/pengaturan'
  }
]

const adminName = computed(() => {
  return auth.user?.name || 'Admin'
})

const adminInitial = computed(() => {
  return adminName.value.charAt(0).toUpperCase()
})

const isActive = (path) => {
  if (path === '/admin') {
    return route.path === '/admin'
  }

  return route.path.startsWith(path)
}

const goTo = (path) => {
  router.push(path)
}

const handleLogout = () => {
  emit('logout')
}
</script>

<template>
  <aside class="admin-sidebar">

    <!-- LOGO -->
    <div class="sidebar-logo">

      <div class="logo-icon">
        🍿
      </div>

      <div class="logo-text">
        <strong>CEMILKU</strong>
        <span>ADMIN PANEL</span>
      </div>

    </div>


    <!-- MENU -->
    <nav class="sidebar-nav">

      <div class="menu-title">
        MENU UTAMA
      </div>

      <button
        v-for="item in menuItems"
        :key="item.path"
        type="button"
        class="menu-item"
        :class="{ active: isActive(item.path) }"
        @click="goTo(item.path)"
      >

        <span class="menu-icon">
          {{ item.icon }}
        </span>

        <span class="menu-name">
          {{ item.name }}
        </span>

        <span
          v-if="isActive(item.path)"
          class="active-indicator"
        ></span>

      </button>

    </nav>


    <!-- BOTTOM -->
    <div class="sidebar-bottom">

      <!-- ADMIN -->
      <div class="admin-card">

        <div class="admin-avatar">
          {{ adminInitial }}
        </div>

        <div class="admin-info">

          <strong>
            {{ adminName }}
          </strong>

          <span>
            Administrator
          </span>

        </div>

      </div>


      <!-- LOGOUT -->
      <button
        type="button"
        class="logout-button"
        @click="handleLogout"
      >

        <span class="logout-icon">
          ⇥
        </span>

        <span>
          Keluar
        </span>

      </button>

    </div>

  </aside>
</template>


<style scoped>
/* =====================================================
   SIDEBAR
===================================================== */

.admin-sidebar {
  position: fixed;
  top: 0;
  left: 0;
  z-index: 100;

  width: 260px;
  height: 100vh;

  display: flex;
  flex-direction: column;

  padding: 22px 16px;

  box-sizing: border-box;

  background:
    linear-gradient(
      180deg,
      rgba(255, 255, 255, 0.97),
      rgba(248, 250, 252, 0.97)
    );

  border-right: 1px solid rgba(15, 23, 42, 0.08);

  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
}


/* =====================================================
   LOGO
===================================================== */

.sidebar-logo {
  display: flex;
  align-items: center;

  gap: 11px;

  padding: 5px 8px 25px;

  border-bottom: 1px solid rgba(15, 23, 42, 0.07);
}

.logo-icon {
  width: 42px;
  height: 42px;

  display: grid;
  place-items: center;

  border-radius: 13px;

  background:
    linear-gradient(
      135deg,
      #7c3aed,
      #a855f7
    );

  color: white;

  font-size: 20px;

  box-shadow:
    0 8px 20px rgba(124, 58, 237, 0.2);
}

.logo-text {
  display: flex;
  flex-direction: column;
}

.logo-text strong {
  color: #172033;

  font-size: 17px;
  font-weight: 900;

  letter-spacing: 1px;
}

.logo-text span {
  margin-top: 2px;

  color: #94a3b8;

  font-size: 8px;
  font-weight: 800;

  letter-spacing: 1.4px;
}


/* =====================================================
   NAVIGATION
===================================================== */

.sidebar-nav {
  flex: 1;

  padding-top: 25px;

  overflow-y: auto;
}

.menu-title {
  padding: 0 12px;

  margin-bottom: 9px;

  color: #94a3b8;

  font-size: 9px;
  font-weight: 800;

  letter-spacing: 1.2px;
}

.menu-item {
  position: relative;

  width: 100%;
  height: 46px;

  display: flex;
  align-items: center;

  gap: 12px;

  padding: 0 13px;
  margin-bottom: 5px;

  border: 0;
  border-radius: 11px;

  background: transparent;

  color: #64748b;

  text-align: left;
  font-family: inherit;

  cursor: pointer;

  transition:
    background 0.2s ease,
    color 0.2s ease,
    transform 0.2s ease;
}

.menu-item:hover {
  background: rgba(124, 58, 237, 0.06);

  color: #7c3aed;

  transform: translateX(2px);
}

.menu-item.active {
  background:
    linear-gradient(
      90deg,
      rgba(124, 58, 237, 0.12),
      rgba(124, 58, 237, 0.05)
    );

  color: #7c3aed;

  font-weight: 750;
}

.menu-icon {
  width: 22px;

  display: grid;
  place-items: center;

  font-size: 16px;
}

.menu-name {
  flex: 1;

  font-size: 12px;
}

.active-indicator {
  width: 4px;
  height: 20px;

  border-radius: 5px;

  background: #7c3aed;
}


/* =====================================================
   BOTTOM
===================================================== */

.sidebar-bottom {
  padding-top: 15px;

  border-top: 1px solid rgba(15, 23, 42, 0.07);
}


/* =====================================================
   ADMIN CARD
===================================================== */

.admin-card {
  display: flex;
  align-items: center;

  gap: 9px;

  padding: 10px;
  margin-bottom: 9px;

  border-radius: 12px;

  background: rgba(124, 58, 237, 0.05);
}

.admin-avatar {
  width: 35px;
  height: 35px;

  flex-shrink: 0;

  display: grid;
  place-items: center;

  border-radius: 10px;

  background:
    linear-gradient(
      135deg,
      #7c3aed,
      #a855f7
    );

  color: white;

  font-size: 12px;
  font-weight: 850;
}

.admin-info {
  min-width: 0;

  display: flex;
  flex-direction: column;
}

.admin-info strong {
  overflow: hidden;

  color: #172033;

  font-size: 11px;

  white-space: nowrap;
  text-overflow: ellipsis;
}

.admin-info span {
  margin-top: 2px;

  color: #94a3b8;

  font-size: 9px;
}


/* =====================================================
   LOGOUT
===================================================== */

.logout-button {
  width: 100%;
  height: 40px;

  display: flex;
  align-items: center;

  gap: 10px;

  padding: 0 12px;

  border: 1px solid rgba(239, 68, 68, 0.1);
  border-radius: 10px;

  background: rgba(239, 68, 68, 0.04);

  color: #ef4444;

  font-family: inherit;

  font-size: 11px;
  font-weight: 700;

  cursor: pointer;

  transition: 0.2s ease;
}

.logout-button:hover {
  background: rgba(239, 68, 68, 0.09);

  border-color: rgba(239, 68, 68, 0.2);
}

.logout-icon {
  font-size: 17px;
}


/* =====================================================
   DARK MODE
===================================================== */

:global([data-theme="dark"]) .admin-sidebar {
  background:
    linear-gradient(
      180deg,
      rgba(15, 23, 42, 0.98),
      rgba(15, 23, 42, 0.96)
    );

  border-right-color: rgba(255, 255, 255, 0.08);
}

:global([data-theme="dark"]) .sidebar-logo {
  border-bottom-color: rgba(255, 255, 255, 0.07);
}

:global([data-theme="dark"]) .logo-text strong {
  color: #f8fafc;
}

:global([data-theme="dark"]) .menu-item {
  color: #94a3b8;
}

:global([data-theme="dark"]) .menu-item:hover {
  color: #a78bfa;

  background: rgba(167, 139, 250, 0.07);
}

:global([data-theme="dark"]) .menu-item.active {
  color: #a78bfa;

  background: rgba(167, 139, 250, 0.1);
}

:global([data-theme="dark"]) .active-indicator {
  background: #a78bfa;
}

:global([data-theme="dark"]) .sidebar-bottom {
  border-top-color: rgba(255, 255, 255, 0.07);
}

:global([data-theme="dark"]) .admin-card {
  background: rgba(167, 139, 250, 0.07);
}

:global([data-theme="dark"]) .admin-info strong {
  color: #f8fafc;
}

:global([data-theme="dark"]) .logout-button {
  background: rgba(239, 68, 68, 0.06);
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 900px) {

  .admin-sidebar {
    width: 76px;

    padding: 18px 10px;
  }

  .sidebar-logo {
    justify-content: center;

    padding: 5px 0 20px;
  }

  .logo-text {
    display: none;
  }

  .menu-title {
    display: none;
  }

  .menu-item {
    justify-content: center;

    padding: 0;
  }

  .menu-name {
    display: none;
  }

  .active-indicator {
    position: absolute;

    right: 2px;
  }

  .admin-card {
    justify-content: center;

    padding: 7px;
  }

  .admin-info {
    display: none;
  }

  .logout-button {
    justify-content: center;

    padding: 0;
  }

  .logout-button span:last-child {
    display: none;
  }
}
</style>