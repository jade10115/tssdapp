<template>
  <div class="inv-page">
    <!-- Header -->
    <div class="gov-header">
      <div class="gov-title">
        <div class="seal" aria-hidden="true">🏛️</div>
        <div>
          <div class="h-title">Supply & Inventory</div>
          <div class="h-sub">Modern government-friendly UI • responsive • accessible</div>
        </div>
      </div>

      <div class="gov-actions">
        <!-- Add Product button removed (kept as per your template) -->

        <div class="search">
          <i class="bi bi-search"></i>
          <input v-model="searchQuery" type="text" placeholder="Search products…" />
          <button v-if="searchQuery" class="search-clear" @click="searchQuery = ''" aria-label="Clear search">
            <i class="bi bi-x-circle"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="toolbar">
      <div class="seg" role="tablist" aria-label="Status filter">
        <button @click="statusFilter = 'All'" :class="segClass('All')" role="tab">
          <span class="dot all"></span> All
          <span class="seg-count">{{ products.length }}</span>
        </button>
        <button @click="statusFilter = 'Available'" :class="segClass('Available')" role="tab">
          <span class="dot ok"></span> Available
          <span class="seg-count">{{ countsByStatus.Available }}</span>
        </button>
        <button @click="statusFilter = 'Low Qty'" :class="segClass('Low Qty')" role="tab">
          <span class="dot warn"></span> Low Qty
          <span class="seg-count">{{ countsByStatus["Low Qty"] }}</span>
        </button>
        <button @click="statusFilter = 'Not Available'" :class="segClass('Not Available')" role="tab">
          <span class="dot bad"></span> Not Available
          <span class="seg-count">{{ countsByStatus["Not Available"] }}</span>
        </button>
      </div>

      <div class="quick">
        <div class="qcard">
          <div class="qk">Showing</div>
          <div class="qv">{{ filteredProducts.length }}</div>
        </div>
        <div class="qcard">
          <div class="qk">In Cart</div>
          <div class="qv">{{ cartCount }}</div>
        </div>
        <div class="qcard">
          <div class="qk">Total</div>
          <div class="qv money">₱{{ formatMoneyNoSymbol(cartTotal) }}</div>
        </div>
      </div>
    </div>

    <!-- Low Stock Notice -->
    <div class="notice warn" v-if="lowStock.length">
      <div class="nicon"><i class="bi bi-exclamation-triangle"></i></div>
      <div class="nbody">
        <div class="ntitle">Items Running Low</div>
        <div class="ntext">
          <span v-for="l in lowStock" :key="l.id" class="pill-warn">
            {{ l.product_name }} • {{ l.current_stock }} {{ pluralizeUnit(l.unit, l.current_stock) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Main Grid -->
    <div class="grid">
      <!-- Cart -->
      <aside class="cart" aria-label="Cart panel">
        <div class="cart-head">
          <div class="cart-title">
            <i class="bi bi-cart3"></i>
            <span>Cart</span>
          </div>

          <div class="cart-actions">
            <span class="cart-badge" :title="`${cartCount} items`">{{ cartCount }}</span>
            <button class="mini-btn" @click="clearCart" :disabled="!cart.length" title="Clear cart">
              <i class="bi bi-trash3"></i>
            </button>
          </div>
        </div>

        <div class="cart-body">
          <div v-if="cart.length" class="cart-list">
            <div v-for="item in cart" :key="item.id" class="cart-item">
              <div class="ci-top">
                <div class="ci-left">
                  <div class="ci-name">{{ item.product_name }}</div>
                  <div class="ci-sub">
                    ₱{{ item.price }} • {{ item.unit }}
                    <span class="sep">•</span>
                    Stock: {{ item.current_stock }}
                  </div>
                </div>

                <button class="mini-btn danger" @click="removeFromCart(item.id)" title="Remove item">
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>

              <div class="ci-bottom">
                <div class="qty qty-sm">
                  <button class="qty-btn" type="button" @click="decCart(item.id)" :disabled="item.quantity <= 1">−</button>
                  <input
                    type="number"
                    min="1"
                    :max="item.current_stock"
                    :value="item.quantity"
                    @input="setCartQty(item.id, $event.target.value)"
                  />
                  <button
                    class="qty-btn"
                    type="button"
                    @click="incCart(item.id)"
                    :disabled="item.quantity >= item.current_stock"
                  >
                    +
                  </button>
                </div>

                <div class="ci-sum">
                  <div class="ci-line">Line Total</div>
                  <div class="ci-total">₱{{ formatMoneyNoSymbol(item.price * item.quantity) }}</div>
                </div>
              </div>
            </div>

            <div class="cart-summary">
              <div class="sum-row">
                <span>Items</span>
                <b>{{ cartCount }}</b>
              </div>
              <div class="sum-row">
                <span>Total Amount</span>
                <b class="sum-money">₱{{ formatMoneyNoSymbol(cartTotal) }}</b>
              </div>
            </div>
          </div>

          <div v-else class="empty-cart">
            <i class="bi bi-inbox"></i>
            <div>
              <div class="ec-title">No items in cart</div>
              <div class="ec-sub">Add products from the list to request supplies.</div>
            </div>
          </div>

          <div class="reqby">
            <label class="lbl">Requested By</label>
            <select v-model="selectedRequestedBy" class="sel">
              <option value="">-- Select Profile --</option>
              <option v-for="p in profiles" :key="p.id" :value="p.user_id">
                {{ p.first_name }} {{ p.last_name }} ({{ p.position }})
              </option>
            </select>
            <div class="hint" v-if="!selectedRequestedBy">
              Required to checkout.
            </div>
          </div>

          <button class="gbtn gbtn-primary full" @click="checkout" :disabled="checkoutDisabled">
            <span v-if="checkoutLoading" class="spinner"></span>
            <i v-else class="bi bi-check-circle"></i>
            <span>{{ checkoutLoading ? "Processing…" : "Checkout" }}</span>
          </button>
        </div>
      </aside>

      <!-- Products -->
      <main class="products" aria-label="Products list">
        <div class="phead">
          <div class="pmeta">
            <div class="ptitle">Products</div>
            <div class="psub">
              Sorted by status • Tap “Add to Cart” to include items
            </div>
          </div>

          <div class="phead-actions">
            <button class="gbtn gbtn-ghost" @click="loadProducts" :disabled="loadingProducts">
              <span v-if="loadingProducts" class="spinner"></span>
              <i v-else class="bi bi-arrow-clockwise"></i>
              Refresh
            </button>
          </div>
        </div>

        <!-- Loading skeleton -->
        <div v-if="loadingProducts" class="cards">
          <div v-for="n in 8" :key="n" class="pcard skel">
            <div class="skel-img"></div>
            <div class="skel-body">
              <div class="skel-line w80"></div>
              <div class="skel-line w60"></div>
              <div class="skel-box"></div>
              <div class="skel-line w70"></div>
              <div class="skel-btn"></div>
            </div>
          </div>
        </div>

        <div v-else>
          <div v-if="!filteredProducts.length" class="empty-products">
            <div class="empty-ico"><i class="bi bi-search"></i></div>
            <div class="empty-title">No products found</div>
            <div class="empty-sub">
              Try changing the filter or search terms.
            </div>
            <button class="gbtn gbtn-ghost" @click="resetFilters">
              <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
            </button>
          </div>

          <div v-else class="cards">
            <div v-for="product in paginatedProducts" :key="product.id" class="pcard" :class="statusClass(product.status)">
              <div class="pimg">
                <img :src="getImageUrl(product.image)" alt="Product image" />
                <div class="tag" :class="tagClass(product.status)">
                  <span class="tag-dot"></span>{{ product.status }}
                </div>
              </div>

              <div class="pbody">
                <div class="pname-row">
                  <div class="pname" :title="product.product_name">{{ product.product_name }}</div>
                  <div class="price">₱{{ product.price }}</div>
                </div>

                <div class="pinfo">
                  <div class="info-row">
                    <span class="k">Stock</span>
                    <span class="v">{{ product.current_stock }} {{ pluralizeUnit(product.unit, product.current_stock) }}</span>
                  </div>
                  <div class="info-row">
                    <span class="k">Unit</span>
                    <span class="v unit">{{ product.unit }}</span>
                  </div>
                </div>

                <div class="qty-row">
                  <label class="qty-lbl">Qty</label>
                  <div class="qty">
                    <button class="qty-btn" type="button"
                            :disabled="product.current_stock <= 0 || getQuantity(product.id) <= 1"
                            @click="updateQuantity(product.id, getQuantity(product.id) - 1)">−</button>

                    <input
                      type="number"
                      min="1"
                      :max="product.current_stock"
                      :disabled="product.current_stock <= 0"
                      :value="getQuantity(product.id)"
                      @input="updateQuantity(product.id, $event.target.value)"
                    />

                    <button class="qty-btn" type="button"
                            :disabled="product.current_stock <= 0 || getQuantity(product.id) >= product.current_stock"
                            @click="updateQuantity(product.id, getQuantity(product.id) + 1)">+</button>
                  </div>
                </div>

                <button
                  @click="addToCart(product)"
                  class="gbtn gbtn-warn full"
                  :disabled="product.current_stock <= 0 || product.status === 'Not Available'"
                >
                  <i class="bi bi-cart-plus"></i>
                  Add to Cart
                </button>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div class="pager" v-if="filteredProducts.length">
            <button class="gbtn gbtn-ghost" @click="prevPage" :disabled="currentPage === 1">
              <i class="bi bi-chevron-left"></i> Prev
            </button>

            <div class="page-ind">
              Page <b>{{ currentPage }}</b> of <b>{{ totalPages || 1 }}</b>
            </div>

            <button class="gbtn gbtn-ghost" @click="nextPage" :disabled="currentPage === totalPages || totalPages === 0">
              Next <i class="bi bi-chevron-right"></i>
            </button>
          </div>
        </div>
      </main>
    </div>

    <!-- Mobile sticky checkout bar -->
    <div class="mobile-bar" v-if="isMobile">
      <div class="mb-left">
        <div class="mb-items">
          <i class="bi bi-cart3"></i>
          <span><b>{{ cartCount }}</b> item(s)</span>
        </div>
        <div class="mb-total">₱{{ formatMoneyNoSymbol(cartTotal) }}</div>
      </div>
      <button class="gbtn gbtn-primary" @click="scrollToCart" :disabled="!cart.length">
        Checkout
      </button>
    </div>

    <!-- ➕ ADD PRODUCT MODAL -->
    <div v-if="showAddModal" class="modal-overlay" @click.self="closeAddModal">
      <div class="modal-shell" role="dialog" aria-modal="true">
        <div class="mhead">
          <div class="mtitle">
            <div class="micon"><i class="bi bi-plus-circle-fill"></i></div>
            <div>
              <div class="mtt">Add New Product</div>
              <div class="mts">Upload an image or take a photo using your camera.</div>
            </div>
          </div>

          <button class="mclose" type="button" @click="closeAddModal" aria-label="Close">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <div class="mbody">
          <div class="mgrid">
            <div class="field full">
              <label class="flbl">Product Name <span class="req">*</span></label>
              <input class="fin" type="text" v-model="newProduct.product_name" placeholder="Enter product name" />
            </div>

            <div class="field">
              <label class="flbl">Unit <span class="req">*</span></label>
              <select class="fin" v-model="newProduct.unit">
                <option value="">Select unit</option>
                <option value="pc">pc (piece)</option>
                <option value="rem">rem (ream)</option>
                <option value="box">box</option>
                <option value="qty">qty (quantity)</option>
              </select>
            </div>

            <div class="field">
              <label class="flbl">Price <span class="req">*</span></label>
              <div class="money">
                <span class="peso">₱</span>
                <input class="fin" type="number" v-model="newProduct.price" placeholder="0.00" />
              </div>
            </div>

            <div class="field full">
              <label class="flbl">Starting Stock <span class="req">*</span></label>
              <input class="fin" type="number" v-model="newProduct.current_stock" placeholder="Enter quantity" />
            </div>

            <div class="field full">
              <label class="flbl">Image (optional)</label>

              <div class="img-actions">
                <label class="filebtn">
                  <i class="bi bi-upload"></i>
                  <span>Upload File</span>
                  <input type="file" accept="image/*" @change="handleAddImage" />
                </label>

                <button class="cambtn" type="button" @click="openCamera">
                  <i class="bi bi-camera"></i>
                  <span>Use Camera</span>
                </button>

                <button class="ghostbtn" type="button" @click="clearImage" :disabled="!newProduct.image">
                  <i class="bi bi-trash3"></i>
                  <span>Remove</span>
                </button>
              </div>

              <div class="preview">
                <img :src="addPreviewImage" alt="Preview" />
              </div>

              <div v-if="camera.open" class="camera">
                <div class="camera-top">
                  <div class="cam-title"><i class="bi bi-record-circle"></i> Camera Preview</div>
                  <button class="mmini" type="button" @click="closeCamera">
                    <i class="bi bi-x"></i> Close
                  </button>
                </div>

                <video ref="videoRef" class="vid" playsinline autoplay muted></video>

                <div class="camera-actions">
                  <button class="gbtn gbtn-primary" type="button" @click="capturePhoto" :disabled="camera.loading">
                    <i class="bi bi-camera-fill"></i> Capture
                  </button>
                  <button class="gbtn gbtn-ghost" type="button" @click="switchCamera" :disabled="camera.loading">
                    <i class="bi bi-arrow-repeat"></i> Switch
                  </button>
                </div>

                <canvas ref="canvasRef" class="hidden-canvas"></canvas>

                <div v-if="camera.error" class="cam-error">
                  <i class="bi bi-exclamation-triangle"></i> {{ camera.error }}
                </div>

                <div class="cam-note">Allow camera permission when prompted (best on mobile).</div>
              </div>
            </div>
          </div>
        </div>

        <div class="mfoot">
          <button class="gbtn gbtn-ghost" type="button" @click="closeAddModal">
            <i class="bi bi-x-circle"></i> Close
          </button>

          <button class="gbtn gbtn-primary" type="button" @click="saveNewProduct" :disabled="savingProduct">
            <span v-if="savingProduct" class="spinner"></span>
            <i v-else class="bi bi-check-circle"></i>
            <span>{{ savingProduct ? "Saving…" : "Save Product" }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, onBeforeUnmount, nextTick, inject } from "vue";
import axios from "axios";
import Swal from "sweetalert2";

// ========== GLOBAL API BASE (injected from main.js) ==========
const API_BASE = inject("API_BASE");
// Derive base URL for images (strip trailing "/api")
const BASE_URL = API_BASE.replace(/\/api$/, "");

/* ===== State ===== */
const products = ref([]);
const lowStock = ref([]);
const cart = ref([]);
const searchQuery = ref("");
const statusFilter = ref("All");
const quantities = ref({});
const profiles = ref([]);
const selectedRequestedBy = ref("");

const loadingProducts = ref(false);
const checkoutLoading = ref(false);

const currentPage = ref(1);
const itemsPerPage = 12;

/* ===== Helpers ===== */
const getImageUrl = (image) =>
  image ? `${BASE_URL}/product-images/${image}` : "/img/no-image.png";

const pluralizeUnit = (unit, qty) => {
  if (qty > 1) {
    if (unit === "pc") return "pcs";
    if (unit === "rem") return "rems";
    if (unit === "box") return "boxes";
    if (unit === "qty") return "qtys";
  }
  return unit;
};

const formatMoneyNoSymbol = (n) => {
  const x = Number(n || 0);
  return new Intl.NumberFormat("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(x);
};

const segClass = (val) => ["segbtn", statusFilter.value === val ? "active" : ""];
const statusClass = (s) => ({ "is-low": s === "Low Qty", "is-none": s === "Not Available" });
const tagClass = (s) => ({ ok: s === "Available", warn: s === "Low Qty", bad: s === "Not Available" });

/* ===== Counts ===== */
const countsByStatus = computed(() => {
  const c = { Available: 0, "Low Qty": 0, "Not Available": 0 };
  for (const p of products.value) {
    if (c[p.status] !== undefined) c[p.status] += 1;
  }
  return c;
});

/* ===== Filtering + Sorting ===== */
const filteredProducts = computed(() =>
  products.value
    .filter((p) => p.product_name.toLowerCase().includes(searchQuery.value.toLowerCase()))
    .filter((p) => (statusFilter.value === "All" ? true : p.status === statusFilter.value))
    .sort((a, b) => {
      const rank = { Available: 1, "Low Qty": 2, "Not Available": 3 };
      return rank[a.status] - rank[b.status];
    })
);

/* ===== Pagination ===== */
const totalPages = computed(() => Math.ceil(filteredProducts.value.length / itemsPerPage));
const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  return filteredProducts.value.slice(start, start + itemsPerPage);
});
const prevPage = () => (currentPage.value = Math.max(1, currentPage.value - 1));
const nextPage = () => (currentPage.value = Math.min(totalPages.value || 1, currentPage.value + 1));

watch([searchQuery, statusFilter], () => (currentPage.value = 1));

/* ===== Quantity (Products list) ===== */
const getQuantity = (productId) => quantities.value[productId] || 1;
const updateQuantity = (productId, value) => {
  const num = typeof value === "number" ? value : parseInt(value) || 1;
  quantities.value = { ...quantities.value, [productId]: Math.max(1, num) };
};

/* ===== Cart UX ===== */
const cartCount = computed(() => cart.value.reduce((s, i) => s + Number(i.quantity || 0), 0));
const cartTotal = computed(() => cart.value.reduce((s, i) => s + Number(i.price || 0) * Number(i.quantity || 0), 0));
const checkoutDisabled = computed(() => !cart.value.length || !selectedRequestedBy.value || checkoutLoading.value);

const normalizeQty = (q) => {
  const n = parseInt(q);
  return Number.isFinite(n) ? n : 1;
};

const addToCart = (product) => {
  const qtyToAdd = getQuantity(product.id);
  const existing = cart.value.find((i) => i.id === product.id);
  const already = existing ? existing.quantity : 0;

  if (qtyToAdd + already > product.current_stock) {
    return Swal.fire("Not enough stock!", "Requested quantity exceeds available stock.", "error");
  }

  if (existing) existing.quantity += qtyToAdd;
  else cart.value.push({ ...product, quantity: qtyToAdd });

  updateQuantity(product.id, 1);
  Swal.fire({ icon: "success", title: "Added to cart", timer: 900, showConfirmButton: false });
};

const removeFromCart = (id) => {
  cart.value = cart.value.filter((i) => i.id !== id);
};

const clearCart = async () => {
  if (!cart.value.length) return;
  const res = await Swal.fire({
    icon: "warning",
    title: "Clear cart?",
    text: "This will remove all items from the cart.",
    showCancelButton: true,
    confirmButtonText: "Yes, clear",
    cancelButtonText: "Cancel",
  });
  if (!res.isConfirmed) return;
  cart.value = [];
};

const setCartQty = (id, value) => {
  const item = cart.value.find((i) => i.id === id);
  if (!item) return;
  const qty = Math.max(1, normalizeQty(value));
  item.quantity = Math.min(qty, item.current_stock);
};

const incCart = (id) => {
  const item = cart.value.find((i) => i.id === id);
  if (!item) return;
  if (item.quantity + 1 > item.current_stock) return;
  item.quantity += 1;
};

const decCart = (id) => {
  const item = cart.value.find((i) => i.id === id);
  if (!item) return;
  item.quantity = Math.max(1, item.quantity - 1);
};

/* ===== Checkout ===== */
const checkout = async () => {
  if (checkoutDisabled.value) return;

  try {
    checkoutLoading.value = true;
    const token = localStorage.getItem("auth_token");
    await axios.post(
      `${API_BASE}/store`,
      {
        user_id: selectedRequestedBy.value,
        items: cart.value.map((i) => ({
          product_id: i.id,
          quantity: i.quantity,
          price: i.price,
          unit: i.unit,
        })),
      },
      { headers: { Authorization: `Bearer ${token}` } }
    );

    Swal.fire("Success!", "Request submitted.", "success");
    cart.value = [];
    await loadProducts();
    await loadLowStock();
  } catch (e) {
    console.error(e);
    Swal.fire("Error", e?.response?.data?.message || "Checkout failed.", "error");
  } finally {
    checkoutLoading.value = false;
  }
};

/* ===== Loaders ===== */
const loadLowStock = async () => {
  lowStock.value = (await axios.get(`${API_BASE}/products/low`)).data;
};
const loadProfiles = async (division) => {
  profiles.value = (await axios.get(`${API_BASE}/profiles/by-division/${division}`)).data;
};
const loadProducts = async () => {
  try {
    loadingProducts.value = true;
    const res = await axios.get(`${API_BASE}/products`);
    products.value = res.data;

    const newQuantities = {};
    res.data.forEach((p) => (newQuantities[p.id] = quantities.value[p.id] || 1));
    quantities.value = newQuantities;
  } finally {
    loadingProducts.value = false;
  }
};

onMounted(async () => {
  await loadProducts();
  const session = await axios.get(`${API_BASE}/session`);
  if (session.data.logged_in) loadProfiles(session.data.user.division);
  loadLowStock();
});

/* ===== Add Product Modal + Camera ===== */
const showAddModal = ref(false);
const savingProduct = ref(false);
const addPreviewImage = ref("/img/no-image.png");
const newProduct = ref({
  product_name: "",
  unit: "",
  price: "",
  current_stock: "",
  image: null,
});

const openAddProduct = () => (showAddModal.value = true);

const closeAddModal = async () => {
  showAddModal.value = false;
  await closeCamera();
};

const handleAddImage = (e) => {
  const file = e.target.files?.[0];
  if (file) {
    newProduct.value.image = file;
    addPreviewImage.value = URL.createObjectURL(file);
  }
};

const clearImage = () => {
  newProduct.value.image = null;
  addPreviewImage.value = "/img/no-image.png";
};

const validateNewProduct = () => {
  const p = newProduct.value;
  if (!String(p.product_name || "").trim()) return "Product name is required.";
  if (!String(p.unit || "").trim()) return "Unit is required.";
  const price = Number(p.price);
  if (!Number.isFinite(price) || price <= 0) return "Price must be greater than 0.";
  const stock = Number(p.current_stock);
  if (!Number.isFinite(stock) || stock < 0) return "Starting stock must be 0 or greater.";
  return null;
};

const saveNewProduct = async () => {
  const err = validateNewProduct();
  if (err) return Swal.fire("Validation", err, "warning");

  try {
    savingProduct.value = true;

    const fd = new FormData();
    Object.keys(newProduct.value).forEach((key) => fd.append(key, newProduct.value[key]));

    await axios.post(`${API_BASE}/products`, fd, {
      headers: { "Content-Type": "multipart/form-data" },
    });

    Swal.fire("Product Added!", "", "success");
    showAddModal.value = false;
    await closeCamera();

    await loadProducts();
    await loadLowStock();

    newProduct.value = { product_name: "", unit: "", price: "", current_stock: "", image: null };
    addPreviewImage.value = "/img/no-image.png";
  } catch (e) {
    console.error(e);
    Swal.fire("Error", e?.response?.data?.message || "Failed to add product.", "error");
  } finally {
    savingProduct.value = false;
  }
};

/* Camera support */
const videoRef = ref(null);
const canvasRef = ref(null);

const camera = ref({
  open: false,
  loading: false,
  error: "",
  facingMode: "environment",
  stream: null,
});

const stopStream = () => {
  if (camera.value.stream) {
    camera.value.stream.getTracks().forEach((t) => t.stop());
    camera.value.stream = null;
  }
};

const openCamera = async () => {
  camera.value.error = "";
  camera.value.loading = true;

  try {
    camera.value.open = true;
    await nextTick();
    stopStream();

    const stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: camera.value.facingMode },
      audio: false,
    });

    camera.value.stream = stream;
    if (videoRef.value) {
      videoRef.value.srcObject = stream;
      await videoRef.value.play();
    }
  } catch (err) {
    console.error(err);
    camera.value.error =
      err?.name === "NotAllowedError"
        ? "Camera permission denied."
        : "Unable to access camera on this device/browser.";
    camera.value.open = false;
  } finally {
    camera.value.loading = false;
  }
};

const closeCamera = async () => {
  stopStream();
  camera.value.open = false;
  camera.value.error = "";
};

const switchCamera = async () => {
  camera.value.facingMode = camera.value.facingMode === "environment" ? "user" : "environment";
  await openCamera();
};

const capturePhoto = async () => {
  camera.value.error = "";
  if (!videoRef.value || !canvasRef.value) return;

  try {
    const video = videoRef.value;
    const canvas = canvasRef.value;

    const w = video.videoWidth || 1280;
    const h = video.videoHeight || 720;

    canvas.width = w;
    canvas.height = h;

    const ctx = canvas.getContext("2d");
    ctx.drawImage(video, 0, 0, w, h);

    const blob = await new Promise((resolve) => canvas.toBlob(resolve, "image/jpeg", 0.92));
    if (!blob) throw new Error("Failed to capture photo.");

    const file = new File([blob], `camera_${Date.now()}.jpg`, { type: "image/jpeg" });
    newProduct.value.image = file;
    addPreviewImage.value = URL.createObjectURL(file);

    await closeCamera();
  } catch (err) {
    console.error(err);
    camera.value.error = "Failed to capture. Try again.";
  }
};

onBeforeUnmount(() => stopStream());

/* Keep quantities updated when products change */
watch(
  products,
  (newProducts) => {
    const newQuantities = { ...quantities.value };
    let needsUpdate = false;
    newProducts.forEach((p) => {
      if (!newQuantities[p.id]) {
        newQuantities[p.id] = 1;
        needsUpdate = true;
      }
    });
    if (needsUpdate) quantities.value = newQuantities;
  },
  { deep: true }
);

/* UX helpers */
const resetFilters = () => {
  statusFilter.value = "All";
  searchQuery.value = "";
  currentPage.value = 1;
};

/* Mobile sticky bar */
const isMobile = ref(false);
const updateIsMobile = () => (isMobile.value = window.matchMedia("(max-width: 900px)").matches);
const scrollToCart = () => {
  const el = document.querySelector(".cart");
  if (el) el.scrollIntoView({ behavior: "smooth", block: "start" });
};
onMounted(() => {
  updateIsMobile();
  window.addEventListener("resize", updateIsMobile);
});
onBeforeUnmount(() => window.removeEventListener("resize", updateIsMobile));
</script>

<style scoped>
/* Your existing styles remain unchanged – they are identical to the original */
/* ... (all styles exactly as you had them) ... */
</style>