<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Swal from 'sweetalert2'

import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
import api from '../services/api'

const route = useRoute()
const router = useRouter()

// ==========================================
// STATE
// ==========================================

const order = ref(null)
const loading = ref(true)
const error = ref('')

const proofFile = ref(null)
const uploadingProof = ref(false)

const fileInput = ref(null)

// ==========================================
// FORMAT RUPIAH
// ==========================================

const formatRupiah = (value) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(Number(value || 0))
}

// ==========================================
// STATUS
// ==========================================

const statusInfo = computed(() => {
  const status = order.value?.status

  const statuses = {
    pending: {
      label: 'Menunggu Pembayaran',
      icon: '⏳',
      class: 'status-pending'
    },

    processing: {
      label: 'Sedang Diproses',
      icon: '📦',
      class: 'status-processing'
    },

    shipped: {
      label: 'Sedang Dikirim',
      icon: '🚚',
      class: 'status-shipped'
    },

    completed: {
      label: 'Selesai',
      icon: '✅',
      class: 'status-completed'
    },

    cancelled: {
      label: 'Dibatalkan',
      icon: '❌',
      class: 'status-cancelled'
    }
  }

  return statuses[status] || {
    label: status || 'Menunggu',
    icon: '📋',
    class: 'status-pending'
  }
})

// ==========================================
// PAYMENT STATUS
// ==========================================

const paymentStatusInfo = computed(() => {
  const status = order.value?.payment?.status

  const statuses = {
    pending: {
      label: 'Menunggu Pembayaran',
      class: 'payment-pending'
    },

    paid: {
      label: 'Pembayaran Diterima',
      class: 'payment-paid'
    },

    verified: {
      label: 'Pembayaran Terverifikasi',
      class: 'payment-paid'
    },

    rejected: {
      label: 'Pembayaran Ditolak',
      class: 'payment-rejected'
    }
  }

  return statuses[status] || {
    label: status || 'Menunggu',
    class: 'payment-pending'
  }
})

// ==========================================
// FETCH ORDER
// ==========================================

const fetchOrder = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get(`/orders/${route.params.id}`)

    order.value = response.data

    console.log('Detail order:', response.data)
  } catch (err) {
    console.error('Gagal mengambil detail order:', err)

    if (err.response?.status === 401) {
      await Swal.fire({
        icon: 'warning',
        title: 'Belum Login',
        text: 'Silakan login terlebih dahulu.'
      })

      router.push('/login')
      return
    }

    if (err.response?.status === 403) {
      error.value = 'Kamu tidak memiliki akses ke pesanan ini.'
      return
    }

    if (err.response?.status === 404) {
      error.value = 'Pesanan tidak ditemukan.'
      return
    }

    error.value = 'Gagal mengambil detail pesanan.'
  } finally {
    loading.value = false
  }
}

// ==========================================
// TANGGAL
// ==========================================

const formatDate = (date) => {
  if (!date) {
    return '-'
  }

  return new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'long',
    timeStyle: 'short'
  }).format(new Date(date))
}

// ==========================================
// FILE PROOF
// ==========================================

const selectProofFile = (event) => {
  const file = event.target.files?.[0]

  if (!file) {
    return
  }

  if (!file.type.startsWith('image/')) {
    Swal.fire({
      icon: 'warning',
      title: 'File Tidak Valid',
      text: 'Bukti pembayaran harus berupa gambar.'
    })

    event.target.value = ''
    return
  }

  if (file.size > 2 * 1024 * 1024) {
    Swal.fire({
      icon: 'warning',
      title: 'File Terlalu Besar',
      text: 'Ukuran bukti pembayaran maksimal 2 MB.'
    })

    event.target.value = ''
    return
  }

  proofFile.value = file
}

// ==========================================
// UPLOAD BUKTI PEMBAYARAN
// ==========================================

const uploadProof = async () => {
  if (!proofFile.value) {
    await Swal.fire({
      icon: 'warning',
      title: 'Pilih Bukti Pembayaran',
      text: 'Silakan pilih gambar bukti transfer terlebih dahulu.'
    })

    return
  }

  if (!order.value?.payment?.id) {
    await Swal.fire({
      icon: 'error',
      title: 'Pembayaran Tidak Ditemukan',
      text: 'Data pembayaran untuk pesanan ini tidak ditemukan.'
    })

    return
  }

  uploadingProof.value = true

  try {
    const formData = new FormData()

    formData.append(
      'proof_image',
      proofFile.value
    )

    const response = await api.post(
      `/payments/${order.value.payment.id}/proof`,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      }
    )

    console.log('Payment:', response.data)

    // Update payment dari response
    order.value.payment = response.data

    proofFile.value = null

    if (fileInput.value) {
      fileInput.value.value = ''
    }

    await Swal.fire({
      icon: 'success',
      title: 'Bukti Berhasil Diupload',
      text: 'Bukti pembayaran berhasil dikirim dan sedang menunggu verifikasi.',
      confirmButtonColor: '#2563eb'
    })

    await fetchOrder()
  } catch (err) {
    console.error('Gagal upload bukti:', err)

    let message = 'Gagal mengupload bukti pembayaran.'

    if (err.response?.data?.message) {
      message = err.response.data.message
    }

    if (err.response?.data?.errors?.proof_image?.[0]) {
      message = err.response.data.errors.proof_image[0]
    }

    await Swal.fire({
      icon: 'error',
      title: 'Upload Gagal',
      text: message
    })
  } finally {
    uploadingProof.value = false
  }
}

// ==========================================
// IMAGE URL
// ==========================================

const getImageUrl = (product) => {
  if (!product) {
    return ''
  }

  if (product.image_url) {
    return product.image_url
  }

  if (product.image) {
    if (product.image.startsWith('http')) {
      return product.image
    }

    return `http://127.0.0.1:8000/storage/${product.image}`
  }

  return ''
}

// ==========================================
// PAYMENT PROOF URL
// ==========================================

const getProofUrl = () => {
  const proof = order.value?.payment?.proof_image

  if (!proof) {
    return ''
  }

  if (proof.startsWith('http')) {
    return proof
  }

  return `http://127.0.0.1:8000/storage/${proof}`
}

// ==========================================
// BACK
// ==========================================

const goToHome = () => {
  router.push('/')
}

const goToProducts = () => {
  router.push('/products')
}

// ==========================================
// MOUNTED
// ==========================================

onMounted(() => {
  fetchOrder()
})
</script>

<template>
  <div class="order-page">

    <main class="order-main">
      <div class="order-container">

        <!-- ==================================
             LOADING
        =================================== -->

        <div
          v-if="loading"
          class="loading-box"
        >
          <div class="spinner"></div>

          <h3>
            Memuat Pesanan...
          </h3>

          <p>
            Tunggu sebentar, kami mengambil detail pesanan kamu.
          </p>
        </div>

        <!-- ==================================
             ERROR
        =================================== -->

        <div
          v-else-if="error"
          class="error-box"
        >
          <div class="error-icon">
            ⚠️
          </div>

          <h2>
            Pesanan Tidak Dapat Dibuka
          </h2>

          <p>
            {{ error }}
          </p>

          <button
            class="primary-button"
            @click="goToHome"
          >
            Kembali ke Beranda
          </button>
        </div>

        <!-- ==================================
             ORDER
        =================================== -->

        <template v-else-if="order">

          <!-- HEADER -->
          <div class="order-header">

            <div>

              <button
                class="back-button"
                @click="goToHome"
              >
                ← Kembali ke Beranda
              </button>

              <div class="title-row">

                <div>
                  <h1>
                    Detail Pesanan
                  </h1>

                  <p>
                    Pesanan kamu berhasil dibuat.
                  </p>
                </div>

                <div
                  class="order-status"
                  :class="statusInfo.class"
                >
                  <span>
                    {{ statusInfo.icon }}
                  </span>

                  {{ statusInfo.label }}
                </div>

              </div>

            </div>

          </div>

          <!-- ORDER NUMBER -->
          <section class="order-number-card">

            <div class="order-number-icon">
              📦
            </div>

            <div>
              <span>
                Nomor Pesanan
              </span>

              <strong>
                {{ order.order_number }}
              </strong>

              <small>
                Dibuat {{ formatDate(order.created_at) }}
              </small>
            </div>

          </section>

          <!-- MAIN GRID -->
          <div class="order-grid">

            <!-- LEFT -->
            <div class="order-left">

              <!-- ==================================
                   STATUS
              =================================== -->

              <section class="detail-card">

                <div class="card-title">
                  <div class="card-icon">
                    📋
                  </div>

                  <div>
                    <h2>
                      Status Pesanan
                    </h2>

                    <p>
                      Status pesanan kamu saat ini.
                    </p>
                  </div>
                </div>

                <div class="status-timeline">

                  <div
                    class="timeline-item active"
                  >
                    <div class="timeline-dot">
                      ✓
                    </div>

                    <div>
                      <strong>
                        Pesanan Dibuat
                      </strong>

                      <span>
                        Pesanan berhasil dibuat.
                      </span>
                    </div>
                  </div>

                  <div
                    class="timeline-line"
                  ></div>

                  <div
                    class="timeline-item"
                    :class="{
                      active:
                        order.status !== 'pending'
                    }"
                  >
                    <div class="timeline-dot">
                      <span v-if="order.status !== 'pending'">
                        ✓
                      </span>

                      <span v-else>
                        2
                      </span>
                    </div>

                    <div>
                      <strong>
                        Pembayaran
                      </strong>

                      <span>
                        {{
                          paymentStatusInfo.label
                        }}
                      </span>
                    </div>
                  </div>

                  <div
                    class="timeline-line"
                  ></div>

                  <div
                    class="timeline-item"
                    :class="{
                      active:
                        [
                          'processing',
                          'shipped',
                          'completed'
                        ].includes(order.status)
                    }"
                  >
                    <div class="timeline-dot">
                      <span
                        v-if="
                          [
                            'processing',
                            'shipped',
                            'completed'
                          ].includes(order.status)
                        "
                      >
                        ✓
                      </span>

                      <span v-else>
                        3
                      </span>
                    </div>

                    <div>
                      <strong>
                        Pesanan Diproses
                      </strong>

                      <span>
                        Pesanan akan diproses oleh penjual.
                      </span>
                    </div>
                  </div>

                  <div
                    class="timeline-line"
                  ></div>

                  <div
                    class="timeline-item"
                    :class="{
                      active:
                        [
                          'shipped',
                          'completed'
                        ].includes(order.status)
                    }"
                  >
                    <div class="timeline-dot">
                      <span
                        v-if="
                          [
                            'shipped',
                            'completed'
                          ].includes(order.status)
                        "
                      >
                        ✓
                      </span>

                      <span v-else>
                        4
                      </span>
                    </div>

                    <div>
                      <strong>
                        Dikirim
                      </strong>

                      <span>
                        Pesanan sedang menuju alamat kamu.
                      </span>
                    </div>
                  </div>

                  <div
                    class="timeline-line"
                  ></div>

                  <div
                    class="timeline-item"
                    :class="{
                      active:
                        order.status === 'completed'
                    }"
                  >
                    <div class="timeline-dot">
                      <span
                        v-if="
                          order.status === 'completed'
                        "
                      >
                        ✓
                      </span>

                      <span v-else>
                        5
                      </span>
                    </div>

                    <div>
                      <strong>
                        Selesai
                      </strong>

                      <span>
                        Pesanan telah selesai.
                      </span>
                    </div>
                  </div>

                </div>

              </section>

              <!-- ==================================
                   ADDRESS
              =================================== -->

              <section class="detail-card">

                <div class="card-title">

                  <div class="card-icon">
                    📍
                  </div>

                  <div>
                    <h2>
                      Alamat Pengiriman
                    </h2>

                    <p>
                      Alamat tujuan pesanan.
                    </p>
                  </div>

                </div>

                <div
                  v-if="order.address"
                  class="address-box"
                >

                  <div class="address-name">
                    <strong>
                      {{ order.address.recipient_name }}
                    </strong>

                    <span
                      v-if="order.address.label"
                      class="address-label"
                    >
                      {{ order.address.label }}
                    </span>
                  </div>

                  <p>
                    📱 {{ order.address.phone }}
                  </p>

                  <p>
                    {{ order.address.full_address }}
                  </p>

                  <span>
                    {{ order.address.city }},
                    {{ order.address.postal_code }}
                  </span>

                </div>

                <div
                  v-else
                  class="no-data"
                >
                  Alamat tidak tersedia.
                </div>

              </section>

              <!-- ==================================
                   PRODUCTS
              =================================== -->

              <section class="detail-card">

                <div class="card-title">

                  <div class="card-icon">
                    🛍️
                  </div>

                  <div>
                    <h2>
                      Produk Pesanan
                    </h2>

                    <p>
                      Produk yang kamu pesan.
                    </p>
                  </div>

                </div>

                <div class="order-products">

                  <div
                    v-for="item in order.items"
                    :key="item.id"
                    class="order-product"
                  >

                    <div class="product-image">

                      <img
                        v-if="getImageUrl(item.product)"
                        :src="getImageUrl(item.product)"
                        :alt="item.product_name"
                      />

                      <div
                        v-else
                        class="image-placeholder"
                      >
                        🍪
                      </div>

                    </div>

                    <div class="product-info">

                      <strong>
                        {{ item.product_name }}
                      </strong>

                      <span>
                        {{ item.quantity }}
                        ×
                        {{ formatRupiah(item.price) }}
                      </span>

                    </div>

                    <strong class="product-subtotal">
                      {{ formatRupiah(item.subtotal) }}
                    </strong>

                  </div>

                </div>

              </section>

              <!-- ==================================
                   PAYMENT
              =================================== -->

              <section class="detail-card payment-card">

                <div class="card-title">

                  <div class="card-icon">
                    💳
                  </div>

                  <div>
                    <h2>
                      Pembayaran
                    </h2>

                    <p>
                      Lakukan pembayaran sesuai total pesanan.
                    </p>
                  </div>

                </div>

                <div
                  v-if="order.payment"
                  class="payment-content"
                >

                  <!-- PAYMENT STATUS -->

                  <div class="payment-status-row">

                    <div>
                      <span>
                        Status Pembayaran
                      </span>

                      <strong>
                        {{ paymentStatusInfo.label }}
                      </strong>
                    </div>

                    <span
                      class="payment-badge"
                      :class="paymentStatusInfo.class"
                    >
                      {{ paymentStatusInfo.label }}
                    </span>

                  </div>

                  <!-- BANK INFO -->

                  <div class="bank-box">

                    <div class="bank-header">
                      <span>
                        🏦
                      </span>

                      <strong>
                        Transfer Bank
                      </strong>
                    </div>

                    <div class="bank-detail">

                      <span>
                        Bank
                      </span>

                      <strong>
                        BCA
                      </strong>

                    </div>

                    <div class="bank-detail">

                      <span>
                        Nomor Rekening
                      </span>

                      <strong>
                        1234567890
                      </strong>

                    </div>

                    <div class="bank-detail">

                      <span>
                        Atas Nama
                      </span>

                      <strong>
                        CEMILKU
                      </strong>

                    </div>

                    <div class="bank-detail total">

                      <span>
                        Total Transfer
                      </span>

                      <strong>
                        {{ formatRupiah(order.payment.amount) }}
                      </strong>

                    </div>

                  </div>

                  <!-- UPLOAD PROOF -->

                  <div
                    v-if="
                      order.payment.status === 'pending'
                    "
                    class="upload-section"
                  >

                    <div class="upload-title">

                      <strong>
                        Upload Bukti Transfer
                      </strong>

                      <span>
                        JPG, PNG, JPEG maksimal 2 MB
                      </span>

                    </div>

                    <label class="upload-box">

                      <input
                        ref="fileInput"
                        type="file"
                        accept="image/*"
                        @change="selectProofFile"
                      />

                      <div class="upload-icon">
                        📤
                      </div>

                      <strong>
                        {{
                          proofFile
                            ? proofFile.name
                            : 'Pilih bukti transfer'
                        }}
                      </strong>

                      <span>
                        Klik untuk memilih file
                      </span>

                    </label>

                    <button
                      class="upload-button"
                      :disabled="
                        uploadingProof ||
                        !proofFile
                      "
                      @click="uploadProof"
                    >

                      <span v-if="uploadingProof">
                        Mengupload...
                      </span>

                      <span v-else>
                        Upload Bukti Pembayaran
                      </span>

                    </button>

                  </div>

                  <!-- EXISTING PROOF -->

                  <div
                    v-if="order.payment.proof_image"
                    class="proof-success"
                  >

                    <div class="proof-header">

                      <div>
                        <strong>
                          ✓ Bukti Pembayaran
                        </strong>

                        <span>
                          Bukti transfer sudah dikirim.
                        </span>
                      </div>

                    </div>

                    <a
                      :href="getProofUrl()"
                      target="_blank"
                      class="proof-link"
                    >
                      Lihat Bukti Pembayaran
                    </a>

                  </div>

                </div>

                <div
                  v-else
                  class="no-data"
                >
                  Data pembayaran belum tersedia.
                </div>

              </section>

            </div>

            <!-- RIGHT -->
            <aside class="order-right">

              <!-- SUMMARY -->

              <section class="summary-card">

                <h2>
                  Ringkasan Pembayaran
                </h2>

                <div class="summary-row">

                  <span>
                    Subtotal
                  </span>

                  <strong>
                    {{ formatRupiah(order.subtotal) }}
                  </strong>

                </div>

                <div class="summary-row">

                  <span>
                    Pengiriman
                  </span>

                  <strong>
                    {{ formatRupiah(order.shipping_cost) }}
                  </strong>

                </div>

                <div class="summary-divider"></div>

                <div class="summary-total">

                  <span>
                    Total
                  </span>

                  <strong>
                    {{ formatRupiah(order.total) }}
                  </strong>

                </div>

              </section>

              <!-- PAYMENT MINI -->

              <section
                v-if="order.payment"
                class="mini-payment-card"
              >

                <div class="mini-payment-icon">
                  💳
                </div>

                <div>

                  <span>
                    Metode Pembayaran
                  </span>

                  <strong>
                    Transfer Bank
                  </strong>

                </div>

              </section>

              <!-- HELP -->

              <section class="help-card">

                <div class="help-icon">
                  💬
                </div>

                <div>

                  <strong>
                    Butuh Bantuan?
                  </strong>

                  <p>
                    Hubungi kami jika ada masalah
                    dengan pesanan kamu.
                  </p>

                  <button
                    @click="goToProducts"
                  >
                    Belanja Lagi →
                  </button>

                </div>

              </section>

            </aside>

          </div>

        </template>

      </div>
    </main>

  </div>
</template>

<style scoped>
* {
  box-sizing: border-box;
}

.order-page {
  min-height: 100vh;
  background: #f6f8fb;
  color: #0f172a;
}

.order-main {
  min-height: calc(100vh - 150px);
  padding: 40px 20px 70px;
}

.order-container {
  max-width: 1200px;
  margin: 0 auto;
}

/* ==========================================
   HEADER
========================================== */

.order-header {
  margin-bottom: 20px;
}

.back-button {
  border: none;
  padding: 0;
  margin-bottom: 18px;
  background: transparent;
  color: #2563eb;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
}

.back-button:hover {
  color: #1d4ed8;
}

.title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
}

.title-row h1 {
  margin: 0 0 6px;
  font-size: 32px;
  font-weight: 900;
}

.title-row p {
  margin: 0;
  color: #64748b;
  font-size: 14px;
}

.order-status {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 10px 14px;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 800;
  white-space: nowrap;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-processing {
  background: #dbeafe;
  color: #1d4ed8;
}

.status-shipped {
  background: #e0e7ff;
  color: #4338ca;
}

.status-completed {
  background: #dcfce7;
  color: #166534;
}

.status-cancelled {
  background: #fee2e2;
  color: #991b1b;
}

/* ==========================================
   ORDER NUMBER
========================================== */

.order-number-card {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 22px;
  padding: 18px 20px;
  background: #ffffff;
  border: 1px solid #dbeafe;
  border-radius: 15px;
}

.order-number-icon {
  width: 46px;
  height: 46px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #eff6ff;
  border-radius: 12px;
  font-size: 22px;
}

.order-number-card > div:last-child {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.order-number-card span {
  color: #64748b;
  font-size: 11px;
}

.order-number-card strong {
  color: #2563eb;
  font-size: 16px;
}

.order-number-card small {
  color: #94a3b8;
  font-size: 10px;
}

/* ==========================================
   GRID
========================================== */

.order-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 350px;
  gap: 22px;
  align-items: start;
}

.order-left {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.order-right {
  display: flex;
  flex-direction: column;
  gap: 15px;
  position: sticky;
  top: 90px;
}

/* ==========================================
   CARDS
========================================== */

.detail-card,
.summary-card,
.mini-payment-card,
.help-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 17px;
  box-shadow: 0 8px 30px rgba(15, 23, 42, 0.04);
}

.detail-card {
  padding: 23px;
}

.summary-card {
  padding: 22px;
}

.mini-payment-card,
.help-card {
  padding: 17px;
}

/* ==========================================
   CARD TITLE
========================================== */

.card-title {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 22px;
}

.card-icon {
  width: 38px;
  height: 38px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #eff6ff;
  border-radius: 10px;
  font-size: 18px;
}

.card-title h2 {
  margin: 0 0 4px;
  font-size: 17px;
  font-weight: 800;
}

.card-title p {
  margin: 0;
  color: #64748b;
  font-size: 12px;
}

/* ==========================================
   TIMELINE
========================================== */

.status-timeline {
  padding-left: 3px;
}

.timeline-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  opacity: 0.45;
}

.timeline-item.active {
  opacity: 1;
}

.timeline-dot {
  width: 28px;
  height: 28px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #cbd5e1;
  border-radius: 50%;
  color: #64748b;
  font-size: 11px;
  font-weight: 800;
}

.timeline-item.active .timeline-dot {
  border-color: #2563eb;
  background: #eff6ff;
  color: #2563eb;
}

.timeline-item > div:last-child {
  display: flex;
  flex-direction: column;
  gap: 3px;
  padding-top: 2px;
}

.timeline-item strong {
  font-size: 13px;
}

.timeline-item span {
  color: #64748b;
  font-size: 11px;
}

.timeline-line {
  width: 2px;
  height: 25px;
  margin-left: 13px;
  background: #e2e8f0;
}

/* ==========================================
   ADDRESS
========================================== */

.address-box {
  padding: 16px;
  background: #f8fbff;
  border: 1px solid #dbeafe;
  border-radius: 12px;
}

.address-name {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 7px;
}

.address-name strong {
  font-size: 14px;
}

.address-label {
  padding: 3px 7px;
  background: #dbeafe;
  color: #2563eb;
  border-radius: 5px;
  font-size: 9px;
  font-weight: 800;
}

.address-box p {
  margin: 4px 0;
  color: #475569;
  font-size: 12px;
  line-height: 1.5;
}

.address-box > span {
  display: block;
  margin-top: 6px;
  color: #64748b;
  font-size: 11px;
}

/* ==========================================
   PRODUCTS
========================================== */

.order-products {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.order-product {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 13px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
}

.product-image {
  width: 60px;
  height: 60px;
  flex-shrink: 0;
  overflow: hidden;
  background: #eff6ff;
  border-radius: 10px;
}

.product-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}

.product-info {
  display: flex;
  flex-direction: column;
  gap: 5px;
  min-width: 0;
  flex: 1;
}

.product-info strong {
  overflow: hidden;
  font-size: 13px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.product-info span {
  color: #64748b;
  font-size: 11px;
}

.product-subtotal {
  color: #2563eb;
  font-size: 13px;
  white-space: nowrap;
}

/* ==========================================
   PAYMENT
========================================== */

.payment-status-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  margin-bottom: 18px;
}

.payment-status-row > div {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.payment-status-row span {
  color: #64748b;
  font-size: 11px;
}

.payment-status-row strong {
  font-size: 14px;
}

.payment-badge {
  padding: 6px 9px;
  border-radius: 7px;
  font-size: 10px !important;
  font-weight: 800;
}

.payment-pending {
  background: #fef3c7;
  color: #92400e;
}

.payment-paid {
  background: #dcfce7;
  color: #166534;
}

.payment-rejected {
  background: #fee2e2;
  color: #991b1b;
}

.bank-box {
  padding: 17px;
  background: #f8fbff;
  border: 1px solid #dbeafe;
  border-radius: 12px;
}

.bank-header {
  display: flex;
  align-items: center;
  gap: 9px;
  padding-bottom: 13px;
  margin-bottom: 4px;
  border-bottom: 1px solid #dbeafe;
}

.bank-header span {
  font-size: 20px;
}

.bank-header strong {
  font-size: 14px;
}

.bank-detail {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  padding: 9px 0;
}

.bank-detail span {
  color: #64748b;
  font-size: 11px;
}

.bank-detail strong {
  color: #334155;
  font-size: 12px;
}

.bank-detail.total {
  margin-top: 5px;
  padding-top: 13px;
  border-top: 1px dashed #cbd5e1;
}

.bank-detail.total strong {
  color: #2563eb;
  font-size: 16px;
}

/* ==========================================
   UPLOAD
========================================== */

.upload-section {
  margin-top: 18px;
}

.upload-title {
  display: flex;
  flex-direction: column;
  gap: 3px;
  margin-bottom: 10px;
}

.upload-title strong {
  font-size: 13px;
}

.upload-title span {
  color: #94a3b8;
  font-size: 10px;
}

.upload-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 145px;
  padding: 20px;
  border: 2px dashed #bfdbfe;
  background: #f8fbff;
  border-radius: 12px;
  text-align: center;
  cursor: pointer;
  transition: 0.2s;
}

.upload-box:hover {
  border-color: #2563eb;
  background: #eff6ff;
}

.upload-box input {
  display: none;
}

.upload-icon {
  margin-bottom: 8px;
  font-size: 26px;
}

.upload-box strong {
  max-width: 100%;
  overflow: hidden;
  color: #2563eb;
  font-size: 12px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.upload-box span {
  margin-top: 4px;
  color: #94a3b8;
  font-size: 10px;
}

.upload-button {
  width: 100%;
  margin-top: 11px;
  padding: 12px;
  border: none;
  border-radius: 9px;
  background: #2563eb;
  color: #ffffff;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
}

.upload-button:hover:not(:disabled) {
  background: #1d4ed8;
}

.upload-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.proof-success {
  margin-top: 17px;
  padding: 14px;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 11px;
}

.proof-header {
  margin-bottom: 10px;
}

.proof-header strong {
  display: block;
  margin-bottom: 3px;
  color: #166534;
  font-size: 13px;
}

.proof-header span {
  color: #64748b;
  font-size: 11px;
}

.proof-link {
  display: inline-block;
  color: #2563eb;
  font-size: 11px;
  font-weight: 700;
  text-decoration: none;
}

.proof-link:hover {
  text-decoration: underline;
}

/* ==========================================
   SUMMARY
========================================== */

.summary-card h2 {
  margin: 0 0 20px;
  font-size: 17px;
  font-weight: 800;
}

.summary-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
  color: #64748b;
  font-size: 13px;
}

.summary-row strong {
  color: #334155;
}

.summary-divider {
  height: 1px;
  margin: 17px 0;
  background: #e2e8f0;
}

.summary-total {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.summary-total span {
  font-size: 15px;
  font-weight: 800;
}

.summary-total strong {
  color: #2563eb;
  font-size: 19px;
  font-weight: 900;
}

/* ==========================================
   MINI PAYMENT
========================================== */

.mini-payment-card {
  display: flex;
  align-items: center;
  gap: 11px;
}

.mini-payment-icon {
  width: 38px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #eff6ff;
  border-radius: 9px;
  font-size: 18px;
}

.mini-payment-card > div:last-child {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.mini-payment-card span {
  color: #94a3b8;
  font-size: 10px;
}

.mini-payment-card strong {
  font-size: 12px;
}

/* ==========================================
   HELP
========================================== */

.help-card {
  display: flex;
  align-items: flex-start;
  gap: 11px;
  background: #eff6ff;
  border-color: #dbeafe;
}

.help-icon {
  font-size: 20px;
}

.help-card strong {
  display: block;
  margin-bottom: 4px;
  font-size: 13px;
}

.help-card p {
  margin: 0 0 9px;
  color: #64748b;
  font-size: 11px;
  line-height: 1.5;
}

.help-card button {
  border: none;
  padding: 0;
  background: transparent;
  color: #2563eb;
  font-size: 11px;
  font-weight: 800;
  cursor: pointer;
}

/* ==========================================
   EMPTY / ERROR
========================================== */

.no-data {
  padding: 20px;
  background: #f8fafc;
  border-radius: 10px;
  color: #64748b;
  text-align: center;
  font-size: 12px;
}

.loading-box,
.error-box {
  min-height: 500px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.loading-box h3,
.error-box h2 {
  margin: 15px 0 5px;
}

.loading-box p,
.error-box p {
  margin: 0 0 20px;
  color: #64748b;
  font-size: 13px;
}

.spinner {
  width: 42px;
  height: 42px;
  border: 3px solid #dbeafe;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.error-icon {
  font-size: 45px;
}

.primary-button {
  border: none;
  padding: 11px 17px;
  background: #2563eb;
  color: #ffffff;
  border-radius: 9px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

.primary-button:hover {
  background: #1d4ed8;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* ==========================================
   RESPONSIVE
========================================== */

@media (max-width: 900px) {
  .order-grid {
    grid-template-columns: 1fr;
  }

  .order-right {
    position: static;
  }
}

@media (max-width: 600px) {
  .order-main {
    padding: 25px 14px 50px;
  }

  .title-row {
    flex-direction: column;
    align-items: flex-start;
  }

  .title-row h1 {
    font-size: 27px;
  }

  .order-status {
    width: 100%;
    justify-content: center;
  }

  .order-number-card {
    padding: 15px;
  }

  .detail-card,
  .summary-card {
    padding: 18px;
    border-radius: 14px;
  }

  .order-product {
    align-items: flex-start;
  }

  .product-image {
    width: 50px;
    height: 50px;
  }

  .product-subtotal {
    font-size: 11px;
  }

  .payment-status-row {
    align-items: flex-start;
    flex-direction: column;
  }

  .bank-detail {
    align-items: flex-start;
    flex-direction: column;
    gap: 3px;
  }
}
</style>