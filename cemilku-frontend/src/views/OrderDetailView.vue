<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Swal from 'sweetalert2'

import api from '../services/api'

import {
  getOrderStatusInfo,
  getPaymentStatusInfo,
  isPending,
  isProcessing,
  isShipped,
  isCompleted
} from '../utils/orderStatus'

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

const payingMidtrans = ref(false)
const snapLoaded = ref(false)

let paymentPollingTimer = null

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
// STATUS ORDER
// ==========================================

const statusInfo = computed(() => {
  return getOrderStatusInfo(order.value?.status)
})

// ==========================================
// STATUS PAYMENT
// ==========================================

const paymentStatusInfo = computed(() => {
  return getPaymentStatusInfo(
    order.value?.payment?.status
  )
})

// ==========================================
// FETCH ORDER
// ==========================================

const fetchOrder = async (showLoading = true) => {
  if (showLoading) {
    loading.value = true
  }

  error.value = ''

  try {
    const response = await api.get(
      `/orders/${route.params.id}`
    )

    order.value = response.data

    console.log(
      'Detail order:',
      response.data
    )
  } catch (err) {
    console.error(
      'Gagal mengambil detail order:',
      err
    )

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
      error.value =
        'Kamu tidak memiliki akses ke pesanan ini.'
      return
    }

    if (err.response?.status === 404) {
      error.value =
        'Pesanan tidak ditemukan.'
      return
    }

    error.value =
      'Gagal mengambil detail pesanan.'
  } finally {
    if (showLoading) {
      loading.value = false
    }
  }
}

// ==========================================
// FORMAT DATE
// ==========================================

const formatDate = (date) => {
  if (!date) {
    return '-'
  }

  return new Intl.DateTimeFormat(
    'id-ID',
    {
      dateStyle: 'long',
      timeStyle: 'short'
    }
  ).format(
    new Date(date)
  )
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
      text:
        'Bukti pembayaran harus berupa gambar.'
    })

    event.target.value = ''
    return
  }

  if (file.size > 2 * 1024 * 1024) {
    Swal.fire({
      icon: 'warning',
      title: 'File Terlalu Besar',
      text:
        'Ukuran bukti pembayaran maksimal 2 MB.'
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
      text:
        'Silakan pilih gambar bukti transfer terlebih dahulu.'
    })

    return
  }

  if (!order.value?.payment?.id) {
    await Swal.fire({
      icon: 'error',
      title: 'Pembayaran Tidak Ditemukan',
      text:
        'Data pembayaran untuk pesanan ini tidak ditemukan.'
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
          'Content-Type':
            'multipart/form-data'
        }
      }
    )

    console.log(
      'Payment:',
      response.data
    )

    order.value.payment =
      response.data?.payment ||
      response.data

    proofFile.value = null

    if (fileInput.value) {
      fileInput.value.value = ''
    }

    await Swal.fire({
      icon: 'success',
      title:
        'Bukti Berhasil Diupload',
      text:
        'Bukti pembayaran berhasil dikirim dan sedang menunggu verifikasi.',
      confirmButtonColor:
        '#2563eb'
    })

    await fetchOrder()
  } catch (err) {
    console.error(
      'Gagal upload bukti:',
      err
    )

    let message =
      'Gagal mengupload bukti pembayaran.'

    if (err.response?.data?.message) {
      message =
        err.response.data.message
    }

    if (
      err.response?.data?.errors
        ?.proof_image?.[0]
    ) {
      message =
        err.response.data.errors
          .proof_image[0]
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
// BACKEND URL
// ==========================================

const backendBaseUrl = computed(() => {
  const base =
    api.defaults.baseURL || ''

  return base
    .replace(/\/api\/?$/, '')
    .replace(/\/$/, '')
})

// ==========================================
// STORAGE URL
// ==========================================

const resolveStorageUrl = (path) => {
  if (!path) {
    return ''
  }

  if (path.startsWith('http')) {
    return path
  }

  return `${backendBaseUrl.value}/storage/${path}`
}

// ==========================================
// PRODUCT IMAGE
// ==========================================

const getImageUrl = (product) => {
  if (!product) {
    return ''
  }

  return resolveStorageUrl(
    product.image_url ||
    product.image
  )
}

// ==========================================
// PAYMENT PROOF URL
// ==========================================

const getProofUrl = () => {
  return resolveStorageUrl(
    order.value?.payment?.proof_image
  )
}

// ==========================================
// NAVIGATION
// ==========================================

const goToHome = () => {
  router.push('/')
}

const goToProducts = () => {
  router.push('/products')
}

// ==========================================
// LOAD MIDTRANS SNAP.JS
// ==========================================

const loadSnapScript = (
  clientKey,
  isProduction = false
) => {
  return new Promise(
    (resolve, reject) => {
      if (!clientKey) {
        reject(
          new Error(
            'Client Key Midtrans tidak ditemukan.'
          )
        )
        return
      }

      if (
        window.snap &&
        typeof window.snap.pay ===
          'function'
      ) {
        snapLoaded.value = true
        resolve(window.snap)
        return
      }

      const existingScript =
        document.getElementById(
          'midtrans-snap-js'
        )

      if (existingScript) {
        existingScript.addEventListener(
          'load',
          () => {
            if (
              window.snap &&
              typeof window.snap.pay ===
                'function'
            ) {
              snapLoaded.value = true
              resolve(
                window.snap
              )
            } else {
              reject(
                new Error(
                  'Snap.js dimuat tetapi tidak tersedia.'
                )
              )
            }
          },
          {
            once: true
          }
        )

        existingScript.addEventListener(
          'error',
          () => {
            reject(
              new Error(
                'Gagal memuat Snap.js Midtrans.'
              )
            )
          },
          {
            once: true
          }
        )

        return
      }

      const script =
        document.createElement(
          'script'
        )

      script.id =
        'midtrans-snap-js'

      script.src =
        isProduction
          ? 'https://app.midtrans.com/snap/snap.js'
          : 'https://app.sandbox.midtrans.com/snap/snap.js'

      script.setAttribute(
        'data-client-key',
        clientKey
      )

      script.async = true

      script.onload = () => {
        if (
          window.snap &&
          typeof window.snap.pay ===
            'function'
        ) {
          snapLoaded.value = true

          resolve(
            window.snap
          )
        } else {
          reject(
            new Error(
              'Snap.js berhasil dimuat tetapi window.snap tidak tersedia.'
            )
          )
        }
      }

      script.onerror = () => {
        snapLoaded.value = false

        reject(
          new Error(
            'Gagal memuat Snap.js Midtrans.'
          )
        )
      }

      document.head.appendChild(
        script
      )
    }
  )
}

// ==========================================
// STOP POLLING
// ==========================================

const stopPaymentPolling = () => {
  if (paymentPollingTimer) {
    clearInterval(
      paymentPollingTimer
    )

    paymentPollingTimer = null
  }
}

// ==========================================
// WAIT WEBHOOK
// ==========================================

const waitForPaymentConfirmation =
  async (orderId) => {
    const maxAttempts = 15
    const delay = 1500

    for (
      let attempt = 0;
      attempt < maxAttempts;
      attempt++
    ) {
      try {
        const response =
          await api.get(
            `/orders/${orderId}`
          )

        const latestOrder =
          response.data

        order.value =
          latestOrder

        console.log(
          `Cek pembayaran ${attempt + 1}/${maxAttempts}:`,
          {
            payment:
              latestOrder?.payment?.status,

            order:
              latestOrder?.status
          }
        )

        if (
          latestOrder?.payment
            ?.status === 'paid'
          ||
          latestOrder?.status ===
            'processing'
        ) {
          return latestOrder
        }

        if (
          latestOrder?.payment
            ?.status === 'failed'
          ||
          latestOrder?.status ===
            'cancelled'
        ) {
          return latestOrder
        }
      } catch (error) {
        console.error(
          'Gagal mengecek status payment:',
          error
        )
      }

      await new Promise(
        resolve =>
          setTimeout(
            resolve,
            delay
          )
      )
    }

    return null
  }

// ==========================================
// BAYAR MIDTRANS
// ==========================================

const payMidtrans = async () => {
  if (!order.value?.id) {
    return
  }

  if (
    order.value?.payment?.status ===
    'paid'
  ) {
    await Swal.fire({
      icon: 'info',
      title:
        'Pesanan Sudah Dibayar',
      text:
        'Pesanan ini sudah berhasil dibayar.',
      confirmButtonColor:
        '#2563eb'
    })

    return
  }

  payingMidtrans.value = true

  stopPaymentPolling()

  try {
    // ======================================
    // 1. AMBIL SNAP TOKEN DARI BACKEND
    // ======================================

    const response =
      await api.post(
        `/orders/${order.value.id}/snap-token`
      )

    console.log(
      'Response Snap Token:',
      response.data
    )

    const snapToken =
      response.data?.snap_token

    const clientKey =
      response.data?.client_key

    const isProduction =
      Boolean(
        response.data?.is_production
      )

    if (!snapToken) {
      throw new Error(
        response.data?.message ||
        'Snap Token tidak ditemukan dari server.'
      )
    }

    if (!clientKey) {
      throw new Error(
        'Client Key Midtrans tidak ditemukan.'
      )
    }

    // ======================================
    // 2. LOAD SNAP.JS
    // ======================================

    const snap =
      await loadSnapScript(
        clientKey,
        isProduction
      )

    if (
      !snap ||
      typeof snap.pay !==
        'function'
    ) {
      throw new Error(
        'Midtrans Snap tidak siap digunakan.'
      )
    }

    // ======================================
    // 3. BUKA POPUP MIDTRANS
    // ======================================

    snap.pay(
      snapToken,
      {
        language: 'id',

        // ==================================
        // SUCCESS
        // ==================================

        onSuccess:
          async (result) => {
            console.log(
              'Midtrans SUCCESS:',
              result
            )

            /*
             * Tunggu webhook masuk.
             */
            const latestOrder =
              await waitForPaymentConfirmation(
                order.value.id
              )

            if (
              latestOrder?.payment
                ?.status === 'paid'
              ||
              latestOrder?.status ===
                'processing'
            ) {
              order.value =
                latestOrder

              await Swal.fire({
                icon: 'success',
                title:
                  'Pembayaran Berhasil!',
                text:
                  'Pembayaran berhasil dikonfirmasi dan pesanan sedang diproses.',
                confirmButtonText:
                  'OK',
                confirmButtonColor:
                  '#2563eb'
              })

              await fetchOrder(
                false
              )

              return
            }

            /*
             * Callback sukses, tetapi webhook
             * belum terlihat.
             */
            await fetchOrder(
              false
            )

            await Swal.fire({
              icon: 'success',
              title:
                'Pembayaran Berhasil!',
              text:
                'Pembayaran sudah diterima Midtrans. Status pesanan sedang diperbarui oleh sistem.',
              confirmButtonText:
                'OK',
              confirmButtonColor:
                '#2563eb'
            })
          },

        // ==================================
        // PENDING
        // ==================================

        onPending:
          async (result) => {
            console.log(
              'Midtrans PENDING:',
              result
            )

            await fetchOrder(
              false
            )

            await Swal.fire({
              icon: 'info',
              title:
                'Menunggu Pembayaran',
              text:
                'Transaksi belum selesai. Silakan selesaikan pembayaran sesuai instruksi Midtrans.',
              confirmButtonText:
                'OK',
              confirmButtonColor:
                '#2563eb'
            })
          },

        // ==================================
        // ERROR
        // ==================================

        onError:
          (result) => {
            console.error(
              'Midtrans ERROR:',
              result
            )

            Swal.fire({
              icon: 'error',
              title:
                'Pembayaran Gagal',
              text:
                'Midtrans melaporkan pembayaran gagal. Silakan coba lagi.',
              confirmButtonColor:
                '#2563eb'
            })
          },

        // ==================================
        // CLOSE
        // ==================================

        onClose:
          async () => {
            console.log(
              'Popup Midtrans ditutup.'
            )

            /*
             * Close bukan berarti paid.
             */
            await fetchOrder(
              false
            )
          }
      }
    )
  } catch (err) {
    console.error(
      'Gagal memulai pembayaran Midtrans:',
      err
    )

    let message =
      'Gagal membuat transaksi Midtrans.'

    if (
      err.response?.data?.message
    ) {
      message =
        err.response.data.message
    }

    if (
      err.response?.data?.error
        ?.error_messages?.[0]
    ) {
      message =
        err.response.data.error
          .error_messages[0]
    }

    if (
      err.message &&
      !err.response
    ) {
      message =
        err.message
    }

    await Swal.fire({
      icon: 'error',
      title:
        'Pembayaran Gagal',
      text: message,
      confirmButtonColor:
        '#2563eb'
    })
  } finally {
    payingMidtrans.value =
      false
  }
}

// ==========================================
// MOUNTED
// ==========================================

onMounted(async () => {
  await fetchOrder()
})

// ==========================================
// UNMOUNTED
// ==========================================

onUnmounted(() => {
  stopPaymentPolling()
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
            Tunggu sebentar, kami mengambil
            detail pesanan kamu.
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
            type="button"
            @click="goToHome"
          >
            Kembali ke Beranda
          </button>
        </div>

        <!-- ==================================
             ORDER
        =================================== -->

        <template
          v-else-if="order"
        >

          <!-- HEADER -->
          <div class="order-header">

            <button
              class="back-button"
              type="button"
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
                :class="
                  statusInfo.class
                "
              >

                <span>
                  {{ statusInfo.icon }}
                </span>

                {{
                  statusInfo.label
                }}

              </div>

            </div>

          </div>

          <!-- ORDER NUMBER -->
          <section
            class="order-number-card"
          >

            <div
              class="order-number-icon"
            >
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
                Dibuat
                {{
                  formatDate(
                    order.created_at
                  )
                }}
              </small>

            </div>

          </section>

          <!-- GRID -->
          <div class="order-grid">

            <!-- ==================================
                 LEFT
            =================================== -->

            <div class="order-left">

              <!-- STATUS -->
              <section
                class="detail-card"
              >

                <div
                  class="card-title"
                >

                  <div class="card-icon">
                    📋
                  </div>

                  <div>
                    <h2>
                      Status Pesanan
                    </h2>

                    <p>
                      Status pesanan kamu
                      saat ini.
                    </p>
                  </div>

                </div>

                <div
                  class="status-timeline"
                >

                  <!-- STEP 1 -->
                  <div
                    class="
                      timeline-item
                      active
                    "
                  >

                    <div
                      class="timeline-dot"
                    >
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

                  <!-- STEP 2 -->
                  <div
                    class="timeline-item"
                    :class="{
                      active:
                        !isPending(
                          order.status
                        )
                    }"
                  >

                    <div
                      class="timeline-dot"
                    >

                      <span
                        v-if="
                          !isPending(
                            order.status
                          )
                        "
                      >
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

                  <!-- STEP 3 -->
                  <div
                    class="timeline-item"
                    :class="{
                      active:
                        isProcessing(
                          order.status
                        )
                    }"
                  >

                    <div
                      class="timeline-dot"
                    >

                      <span
                        v-if="
                          isProcessing(
                            order.status
                          )
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
                        Pesanan akan diproses
                        oleh penjual.
                      </span>

                    </div>

                  </div>

                  <div
                    class="timeline-line"
                  ></div>

                  <!-- STEP 4 -->
                  <div
                    class="timeline-item"
                    :class="{
                      active:
                        isShipped(
                          order.status
                        )
                    }"
                  >

                    <div
                      class="timeline-dot"
                    >

                      <span
                        v-if="
                          isShipped(
                            order.status
                          )
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
                        Pesanan sedang menuju
                        alamat kamu.
                      </span>

                    </div>

                  </div>

                  <div
                    class="timeline-line"
                  ></div>

                  <!-- STEP 5 -->
                  <div
                    class="timeline-item"
                    :class="{
                      active:
                        isCompleted(
                          order.status
                        )
                    }"
                  >

                    <div
                      class="timeline-dot"
                    >

                      <span
                        v-if="
                          isCompleted(
                            order.status
                          )
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

              <!-- ADDRESS -->
              <section
                class="detail-card"
              >

                <div
                  class="card-title"
                >

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
                  v-if="
                    order.address
                  "
                  class="address-box"
                >

                  <div
                    class="address-name"
                  >

                    <strong>
                      {{
                        order.address
                          .recipient_name
                      }}
                    </strong>

                    <span
                      v-if="
                        order.address.label
                      "
                      class="
                        address-label
                      "
                    >
                      {{
                        order.address.label
                      }}
                    </span>

                  </div>

                  <p>
                    📱
                    {{
                      order.address.phone
                    }}
                  </p>

                  <p>
                    {{
                      order.address
                        .full_address
                    }}
                  </p>

                  <span>
                    {{
                      order.address.city
                    }},
                    {{
                      order.address
                        .postal_code
                    }}
                  </span>

                </div>

                <div
                  v-else
                  class="no-data"
                >
                  Alamat tidak tersedia.
                </div>

              </section>

              <!-- PRODUCTS -->
              <section
                class="detail-card"
              >

                <div
                  class="card-title"
                >

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

                <div
                  class="order-products"
                >

                  <div
                    v-for="
                      item in order.items
                    "
                    :key="item.id"
                    class="order-product"
                  >

                    <div
                      class="product-image"
                    >

                      <img
                        v-if="
                          getImageUrl(
                            item.product
                          )
                        "
                        :src="
                          getImageUrl(
                            item.product
                          )
                        "
                        :alt="
                          item.product_name
                        "
                      />

                      <div
                        v-else
                        class="
                          image-placeholder
                        "
                      >
                        🍪
                      </div>

                    </div>

                    <div
                      class="product-info"
                    >

                      <strong>
                        {{
                          item.product_name
                        }}
                      </strong>

                      <span>
                        {{
                          item.quantity
                        }}
                        ×
                        {{
                          formatRupiah(
                            item.price
                          )
                        }}
                      </span>

                    </div>

                    <strong
                      class="
                        product-subtotal
                      "
                    >
                      {{
                        formatRupiah(
                          item.subtotal
                        )
                      }}
                    </strong>

                  </div>

                </div>

              </section>

              <!-- PAYMENT -->
              <section
                class="
                  detail-card
                  payment-card
                "
              >

                <div
                  class="card-title"
                >

                  <div class="card-icon">
                    💳
                  </div>

                  <div>
                    <h2>
                      Pembayaran
                    </h2>

                    <p>
                      Lakukan pembayaran sesuai
                      total pesanan.
                    </p>
                  </div>

                </div>

                <div
                  v-if="
                    order.payment
                  "
                  class="
                    payment-content
                  "
                >

                  <!-- PAYMENT STATUS -->
                  <div
                    class="
                      payment-status-row
                    "
                  >

                    <div>

                      <span>
                        Status Pembayaran
                      </span>

                      <strong>
                        {{
                          paymentStatusInfo.label
                        }}
                      </strong>

                    </div>

                    <span
                      class="
                        payment-badge
                      "
                      :class="
                        paymentStatusInfo.class
                      "
                    >
                      {{
                        paymentStatusInfo.label
                      }}
                    </span>

                  </div>

                  <!-- MIDTRANS BUTTON -->
                  <div
                    v-if="
                      order.payment
                        .status ===
                      'pending'
                    "
                    class="
                      midtrans-section
                    "
                  >

                    <button
                      class="
                        midtrans-button
                      "
                      type="button"
                      :disabled="
                        payingMidtrans
                      "
                      @click="
                        payMidtrans
                      "
                    >

                      <span
                        v-if="
                          payingMidtrans
                        "
                      >
                        Membuka Midtrans...
                      </span>

                      <span v-else>
                        💳 Bayar Sekarang
                        dengan Midtrans
                      </span>

                    </button>

                    <p
                      class="
                        midtrans-info
                      "
                    >
                      Pembayaran akan diproses
                      melalui Midtrans Sandbox.
                    </p>

                  </div>

                  <!-- SUCCESS -->
                  <div
                    v-if="
                      order.payment
                        .status ===
                      'paid'
                    "
                    class="
                      payment-success-box
                    "
                  >

                    <div
                      class="
                        payment-success-icon
                      "
                    >
                      ✓
                    </div>

                    <div>

                      <strong>
                        Pembayaran Berhasil
                      </strong>

                      <span>
                        Pembayaran telah
                        dikonfirmasi oleh sistem.
                      </span>

                    </div>

                  </div>

                  <!-- PROOF -->
                  <div
                    v-if="
                      order.payment
                        .proof_image
                    "
                    class="proof-success"
                  >

                    <div
                      class="
                        proof-header
                      "
                    >

                      <div>

                        <strong>
                          ✓ Bukti Pembayaran
                        </strong>

                        <span>
                          Bukti transfer sudah
                          dikirim.
                        </span>

                      </div>

                    </div>

                    <a
                      :href="
                        getProofUrl()
                      "
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
                  Data pembayaran belum
                  tersedia.
                </div>

              </section>

            </div>

            <!-- ==================================
                 RIGHT
            =================================== -->

            <aside
              class="order-right"
            >

              <!-- SUMMARY -->
              <section
                class="summary-card"
              >

                <h2>
                  Ringkasan Pembayaran
                </h2>

                <div
                  class="summary-row"
                >

                  <span>
                    Subtotal
                  </span>

                  <strong>
                    {{
                      formatRupiah(
                        order.subtotal
                      )
                    }}
                  </strong>

                </div>

                <div
                  class="summary-row"
                >

                  <span>
                    Pengiriman
                  </span>

                  <strong>
                    {{
                      formatRupiah(
                        order.shipping_cost
                      )
                    }}
                  </strong>

                </div>

                <div
                  class="summary-divider"
                ></div>

                <div
                  class="
                    summary-total
                  "
                >

                  <span>
                    Total
                  </span>

                  <strong>
                    {{
                      formatRupiah(
                        order.total
                      )
                    }}
                  </strong>

                </div>

              </section>

              <!-- HELP -->
              <section
                class="help-card"
              >

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
                    type="button"
                    @click="
                      goToProducts
                    "
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
  grid-template-columns:
    minmax(0, 1fr)
    350px;
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
   CARD
========================================== */

.detail-card,
.summary-card,
.help-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 17px;
  box-shadow:
    0 8px 30px
    rgba(15, 23, 42, 0.04);
}

.detail-card {
  padding: 23px;
}

.summary-card {
  padding: 22px;
}

.help-card {
  padding: 17px;
}

/* ==========================================
   SUMMARY
========================================== */

.summary-card h2 {
  margin: 0 0 18px;
  font-size: 20px;
  font-weight: 800;
}

.summary-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
}

.summary-row span {
  color: #64748b;
  font-size: 14px;
}

.summary-row strong {
  color: #0f172a;
  font-size: 15px;
}

.summary-divider {
  height: 1px;
  margin: 14px 0;
  background: #e2e8f0;
}

.summary-total {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.summary-total span {
  font-size: 16px;
  font-weight: 700;
}

.summary-total strong {
  color: #2563eb;
  font-size: 24px;
  font-weight: 900;
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
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 5px;
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

.payment-status-row > div > span {
  color: #64748b;
  font-size: 11px;
}

.payment-status-row strong {
  font-size: 14px;
}

.payment-badge {
  padding: 6px 9px;
  border-radius: 7px;
  font-size: 10px;
  font-weight: 800;
}

.midtrans-section {
  margin-bottom: 18px;
}

.midtrans-button {
  width: 100%;
  border: none;
  border-radius: 10px;
  padding: 14px;
  background: #2563eb;
  color: #ffffff;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: 0.2s;
}

.midtrans-button:hover:not(:disabled) {
  background: #1d4ed8;
  transform: translateY(-1px);
}

.midtrans-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.midtrans-info {
  margin: 8px 0 0;
  text-align: center;
  color: #94a3b8;
  font-size: 11px;
}

.payment-success-box {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 18px;
  padding: 14px;
  border: 1px solid #bbf7d0;
  background: #f0fdf4;
  border-radius: 11px;
}

.payment-success-icon {
  width: 35px;
  height: 35px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: #dcfce7;
  color: #16a34a;
  font-size: 18px;
  font-weight: 900;
}

.payment-success-box > div:last-child {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.payment-success-box strong {
  color: #166534;
  font-size: 13px;
}

.payment-success-box span {
  color: #4b5563;
  font-size: 11px;
}

.proof-success {
  padding: 15px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 11px;
}

.proof-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
}

.proof-header > div {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.proof-header strong {
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
  font-size: 12px;
  font-weight: 700;
  text-decoration: none;
}

.proof-link:hover {
  text-decoration: underline;
}

/* ==========================================
   HELP
========================================== */

.help-card {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.help-icon {
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

.help-card strong {
  font-size: 13px;
}

.help-card p {
  margin: 5px 0 0;
  color: #64748b;
  font-size: 11px;
  line-height: 1.5;
}

.help-card button {
  margin-top: 10px;
  border: none;
  padding: 9px 12px;
  background: #2563eb;
  color: #ffffff;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
}

.help-card button:hover {
  background: #1d4ed8;
}

/* ==========================================
   EMPTY
========================================== */

.no-data {
  padding: 18px;
  text-align: center;
  color: #64748b;
  background: #f8fafc;
  border-radius: 10px;
  font-size: 12px;
}

/* ==========================================
   LOADING
========================================== */

.loading-box {
  min-height: 450px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: #64748b;
  text-align: center;
}

.loading-box h3 {
  margin: 8px 0 0;
  color: #0f172a;
  font-size: 17px;
}

.loading-box p {
  margin: 0;
  font-size: 12px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #dbeafe;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* ==========================================
   ERROR
========================================== */

.error-box {
  max-width: 600px;
  margin: 70px auto;
  padding: 40px 25px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 17px;
  text-align: center;
}

.error-icon {
  font-size: 45px;
}

.error-box h2 {
  margin: 15px 0 8px;
}

.error-box p {
  margin: 0 0 20px;
  color: #64748b;
  font-size: 13px;
}

.primary-button {
  border: none;
  padding: 11px 18px;
  border-radius: 9px;
  background: #2563eb;
  color: #ffffff;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

.primary-button:hover {
  background: #1d4ed8;
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
    align-items: flex-start;
    flex-direction: column;
  }

  .title-row h1 {
    font-size: 27px;
  }

  .order-status {
    width: 100%;
    justify-content: center;
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
}
</style>