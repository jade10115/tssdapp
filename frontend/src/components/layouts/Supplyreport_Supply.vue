<template>
  <div class="container">

    <!-- HEADER -->
    <div class="header gov-header">
      <div class="logos">
        <img src="/img/dole.png" alt="DOLE Logo" />
        <img src="/img/bagongphlogo.png" alt="Bagong Pilipinas Logo" />
      </div>

      <div class="header-titles">
        <div class="gov-line">Republic of the Philippines</div>
        <div class="gov-line sub">Department of Labor and Employment</div>
        <h2>Product Inventory Report</h2>
        <p class="header-sub">
          Inventory monitoring, reporting, and monthly stock snapshot generation.
        </p>
      </div>
    </div>

    <!-- TOP CONTROLS -->
    <div class="top-controls gov-card">

      <!-- SEARCH -->
      <div class="search-block">
        <label class="block-label">Search</label>
        <div class="search-bar">
          <i class="bi bi-search"></i>
          <input v-model="searchQuery" type="text" placeholder="Search product..." />
          <button class="btn btn-primary" @click="searchData">
            <i class="bi bi-search"></i> Search
          </button>
        </div>
      </div>

      <!-- REPORT CONTROLS -->
      <div class="report-block">
        <label class="block-label">Reports</label>

        <div class="annual-report-controls">
          <div class="year-wrap">
            <span class="control-label">Year</span>
            <select v-model="selectedYear" class="year-select">
              <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
          </div>

          <button class="btn btn-outline" @click="generateAnnualPdf">
            <i class="bi bi-file-pdf"></i> Annual PDF
          </button>

          <button class="btn btn-outline" @click="generateAnnualExcel">
            <i class="bi bi-file-earmark-excel"></i> Annual Excel
          </button>

          <button class="btn btn-outline" @click="generateAllItemsReport">
            <i class="bi bi-grid-3x3-gap"></i> All Items Report
          </button>

          <!-- ✅ NEW BUTTON -->
          <button class="btn btn-accent" @click="confirmGenerateMonthlyReports">
            <i class="bi bi-arrow-repeat"></i> Generate / Update Monthly Reports
          </button>
        </div>

        <p class="note">
          This button will generate or update the monthly snapshot report for <b>all products</b>
          (based on current stock). Use this when updating monthly reports manually.
        </p>
      </div>

    </div>

    <!-- PRODUCT TABLE -->
    <div class="table-wrapper gov-card">
      <div class="table-top">
        <div class="table-title">
          <h3>Products</h3>
          <p class="muted">Showing inventory status and quick actions.</p>
        </div>

        <div class="table-meta">
          <span class="meta-pill">
            <i class="bi bi-box-seam"></i> Total: {{ products.length }}
          </span>
          <span class="meta-pill">
            <i class="bi bi-filter"></i> Filtered: {{ filteredProducts.length }}
          </span>
        </div>
      </div>

      <table>
        <thead>
          <tr>
            <th>Image</th>
            <th>Product Name</th>
            <th>Current Stock</th>
            <th>Current Price</th>
            <th>Unit</th>
            <th>Status</th>
            <th style="width:260px;">Actions</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="product in paginatedProducts" :key="product.id">

            <!-- IMAGE -->
            <td>
              <img v-if="product.image" :src="productImageUrl(product.image)" class="product-img" />
              <span v-else class="no-image">No Image</span>
            </td>

            <!-- NAME -->
            <td>
              <div class="product-info">
                <span class="product-name">{{ product.product_name }}</span>
                <span class="muted tiny">ID: {{ product.id }}</span>
              </div>
            </td>

            <!-- STOCK -->
            <td>
              <span :class="['stock-pill', stockClass(product.current_stock)]">
                {{ product.current_stock }}
              </span>
            </td>

            <!-- PRICE -->
            <td>
              <div class="price-info">
                <span class="current-price">₱{{ formatMoney(product.price) }}</span>
                <button @click="viewPriceHistory(product)" class="btn btn-sm btn-outline-secondary">
                  <i class="bi bi-clock-history"></i> History
                </button>
              </div>
            </td>

            <!-- UNIT -->
            <td>{{ product.unit }}</td>

            <!-- STATUS -->
            <td>
              <span :class="['status-pill', statusClass(product.current_stock)]">
                {{ statusLabel(product.current_stock) }}
              </span>
            </td>

            <!-- ACTION BUTTONS -->
            <td class="actions">
              <button class="btn btn-info btn-sm" @click="openProductReport(product)">
                <i class="bi bi-graph-up"></i> View Report
              </button>
              <button class="btn btn-warning btn-sm" @click="openEdit(product)">
                <i class="bi bi-pencil"></i> Update
              </button>
              <button class="btn btn-danger btn-sm" @click="deleteProduct(product.id)">
                <i class="bi bi-trash"></i> Delete
              </button>
            </td>
          </tr>

          <tr v-if="paginatedProducts.length === 0">
            <td colspan="7" class="empty">
              <i class="bi bi-inbox"></i>
              <div>
                <div class="empty-title">No products found</div>
                <div class="muted">Try adjusting your search or filters.</div>
              </div>
            </td>
          </tr>

        </tbody>
      </table>
    </div>

    <!-- PAGINATION -->
    <div class="pagination">
      <button :disabled="currentPage === 1" @click="currentPage--" class="btn btn-outline-primary">
        <i class="bi bi-chevron-left"></i> Prev
      </button>
      <span class="page-info">Page {{ currentPage }} of {{ totalPages }}</span>
      <button :disabled="currentPage === totalPages" @click="currentPage++" class="btn btn-outline-primary">
        Next <i class="bi bi-chevron-right"></i>
      </button>
    </div>

    <!-- PRODUCT REPORT MODAL -->
    <div v-if="showReportModal" class="modal-overlay" @click.self="closeReport">
      <div class="modal-content">
        <div class="modal-header">
          <h3>
            <i class="bi bi-graph-up"></i>
            Monthly Report — {{ reportProductName }}
          </h3>
          <p class="text-muted">Year: {{ selectedYear }}</p>
        </div>

        <div class="modal-body">
          <div class="reports-section">
            <h5 class="section-title">
              <i class="bi bi-calendar-month"></i> Monthly Reports
            </h5>

            <div class="table-responsive">
              <table class="modal-table">
                <thead>
                  <tr>
                    <th>Month</th>
                    <th>Starting</th>
                    <th>Added</th>
                    <th>Released</th>
                    <th>Remaining</th>
                    <th>Price</th>
                    <th>Total Cost</th>
                  </tr>
                </thead>

                <tbody>
                  <tr v-for="r in productReports" :key="r.month + r.year">
                    <td>{{ monthName(r.month) }}</td>
                    <td>{{ r.starting_qty }}</td>
                    <td>{{ r.added_qty }}</td>
                    <td>{{ r.released_qty }}</td>
                    <td>{{ r.remaining_qty }}</td>
                    <td>₱{{ formatMoney(r.price) }}</td>
                    <td>₱{{ formatMoney(r.total_cost) }}</td>
                  </tr>
                </tbody>

                <tfoot>
                  <tr class="summary-row">
                    <td><strong>Total</strong></td>
                    <td>{{ annualSummary.opening_stock }}</td>
                    <td>{{ annualSummary.total_added }}</td>
                    <td>{{ annualSummary.total_released }}</td>
                    <td>{{ annualSummary.closing_stock }}</td>
                    <td>₱{{ formatMoney(annualSummary.avg_price) }}</td>
                    <td>—</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>

        <div class="modal-actions">
          <button class="btn btn-primary" @click="downloadStockCard(reportProductId)">
            <i class="bi bi-download"></i> Download Stock Card
          </button>
          <button class="btn btn-secondary" @click="closeReport">
            <i class="bi bi-x-circle"></i> Close
          </button>
        </div>
      </div>
    </div>

    <!-- UPDATE PRODUCT MODAL -->
    <div v-if="showEditModal" class="modal-overlay" @click.self="showEditModal = false">
      <div class="modal-content edit-modal">

        <div class="modal-header">
          <h3>
            <i class="bi bi-pencil"></i>
            Update Product
          </h3>
        </div>

        <div class="modal-body">
          <div class="edit-form">
            <div class="form-group">
              <label>Product Name</label>
              <input type="text" v-model="editProduct.product_name" class="form-control" />
            </div>

            <div class="form-group">
              <label>Unit</label>
              <input type="text" v-model="editProduct.unit" class="form-control" />
            </div>

            <div class="form-group">
              <label>Price</label>
              <div class="input-group">
                <span class="input-group-text">₱</span>
                <input type="number" v-model="editProduct.price" step="0.01" class="form-control" />
              </div>
            </div>

            <div class="form-group">
              <label>Current Stock</label>
              <input type="number" v-model="editProduct.current_stock" class="form-control" />
            </div>

            <div class="form-group">
              <label>Image</label>
              <input type="file" @change="handleImageChange" class="form-control" />
              <div class="image-preview">
                <img :src="previewImage" alt="Preview" />
              </div>
            </div>
          </div>
        </div>

        <div class="modal-actions">
          <button class="btn btn-primary" @click="updateProduct">
            <i class="bi bi-save"></i> Save Changes
          </button>
          <button class="btn btn-secondary" @click="showEditModal = false">
            <i class="bi bi-x-circle"></i> Cancel
          </button>
        </div>

      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import axios from "axios";
import Swal from "sweetalert2";

import { inject } from "vue";

const API_BASE = inject("API_BASE");


const products = ref([]);
const searchQuery = ref("");
const currentPage = ref(1);
const itemsPerPage = 5;

const showReportModal = ref(false);
const productReports = ref([]);
const reportProductId = ref(null);
const reportProductName = ref("");

const annualSummary = ref({
  opening_stock: 0,
  total_added: 0,
  total_released: 0,
  closing_stock: 0,
  avg_price: 0,
});

const selectedYear = ref(new Date().getFullYear());
const years = ref([]);
for (let y = selectedYear.value; y >= selectedYear.value - 5; y--) years.value.push(y);

const fetchProducts = async () => {
  const res = await axios.get(`${API_BASE}/products`);
  products.value = Array.isArray(res.data) ? res.data : [];
};
onMounted(fetchProducts);

const filteredProducts = computed(() =>
  products.value.filter(p =>
    (p.product_name || "").toLowerCase().includes(searchQuery.value.toLowerCase())
  )
);

const totalPages = computed(() => Math.max(1, Math.ceil(filteredProducts.value.length / itemsPerPage)));

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  return filteredProducts.value.slice(start, start + itemsPerPage);
});

const searchData = () => (currentPage.value = 1);

const formatMoney = v => Number(v || 0).toFixed(2);

const productImageUrl = (path) => {
  if (!path) return "/img/no-image.png";
  return `http://127.0.0.1:8000/product-images/${path}`;
};

const monthName = m =>
  ["", "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"][m];

/* STOCK INDICATOR */
const stockClass = qty => {
  const q = Number(qty || 0);
  if (q <= 0) return "stock-red";
  if (q <= 10) return "stock-yellow";
  return "stock-green";
};

const statusLabel = qty => {
  const q = Number(qty || 0);
  if (q <= 0) return "Not Available";
  if (q <= 10) return "Low Stock";
  return "Available";
};

const statusClass = qty => {
  const q = Number(qty || 0);
  if (q <= 0) return "status-red";
  if (q <= 10) return "status-yellow";
  return "status-green";
};

/* OPEN REPORT */
const openProductReport = async product => {
  reportProductId.value = product.id;
  reportProductName.value = product.product_name;

  const res = await axios.get(`${API_BASE}/product-report/${product.id}/${selectedYear.value}`);
  productReports.value = res.data.months || [];
  annualSummary.value = res.data.summary ?? {
    opening_stock: 0,
    total_added: 0,
    total_released: 0,
    closing_stock: 0,
    avg_price: 0,
  };

  showReportModal.value = true;
};

const closeReport = () => (showReportModal.value = false);

/* DELETE PRODUCT */
const deleteProduct = id => {
  Swal.fire({
    title: "Delete product?",
    text: "This cannot be undone.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Delete",
    cancelButtonText: "Cancel",
    confirmButtonColor: "#b42318",
  }).then(async res => {
    if (res.isConfirmed) {
      await axios.delete(`${API_BASE}/products/${id}`);
      await fetchProducts();
      Swal.fire("Deleted", "Product removed.", "success");
    }
  });
};

/* UPDATE PRODUCT MODAL */
const showEditModal = ref(false);
const editProduct = ref({
  id: null,
  product_name: "",
  unit: "",
  price: "",
  current_stock: "",
  image: null,
});
const previewImage = ref(null);

const openEdit = product => {
  editProduct.value = { ...product };
  previewImage.value = product.image ? productImageUrl(product.image) : "/img/no-image.png";
  showEditModal.value = true;
};

const handleImageChange = e => {
  const file = e.target.files?.[0];
  if (file) {
    editProduct.value.image = file;
    previewImage.value = URL.createObjectURL(file);
  }
};

const updateProduct = async () => {
  const formData = new FormData();
  formData.append("product_name", editProduct.value.product_name);
  formData.append("unit", editProduct.value.unit);
  formData.append("price", editProduct.value.price);
  formData.append("current_stock", editProduct.value.current_stock);

  if (editProduct.value.image instanceof File) {
    formData.append("image", editProduct.value.image);
  }

  await axios.post(`${API_BASE}/products/${editProduct.value.id}?_method=PUT`, formData, {
    headers: { "Content-Type": "multipart/form-data" }
  });

  Swal.fire("Updated", "Product updated successfully.", "success");
  showEditModal.value = false;
  await fetchProducts();
};

/* ANNUAL REPORTS */
const generateAnnualPdf = async () => {
  const res = await axios.get(`${API_BASE}/reports/annual/pdf/${selectedYear.value}`, { responseType: "blob" });
  const a = document.createElement("a");
  a.href = window.URL.createObjectURL(new Blob([res.data]));
  a.download = `ANNUAL_REPORT_${selectedYear.value}.pdf`;
  a.click();
};

const generateAnnualExcel = async () => {
  const res = await axios.get(`${API_BASE}/reports/annual/excel/${selectedYear.value}`, { responseType: "blob" });
  const a = document.createElement("a");
  a.href = window.URL.createObjectURL(new Blob([res.data]));
  a.download = `ANNUAL_REPORT_${selectedYear.value}.xlsx`;
  a.click();
};

const generateAllItemsReport = async () => {
  Swal.fire({
    title: "Generating Report...",
    text: "Please wait while we compile all items data.",
    icon: "info",
    showConfirmButton: false,
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading()
  });

  try {
    const res = await axios.get(`${API_BASE}/reports/all-items/${selectedYear.value}`, { responseType: "blob" });
    Swal.close();

    const a = document.createElement("a");
    a.href = window.URL.createObjectURL(new Blob([res.data]));
    a.download = `ALL_ITEMS_REPORT_${selectedYear.value}.xlsx`;
    a.click();

    Swal.fire("Success", "All items report generated successfully.", "success");
  } catch (e) {
    Swal.close();
    Swal.fire("Error", "Failed to generate report.", "error");
  }
};

const downloadStockCard = async (productId) => {
  const res = await axios.get(`${API_BASE}/reports/stock-card/${productId}`, { responseType: "blob" });
  const a = document.createElement("a");
  a.href = window.URL.createObjectURL(new Blob([res.data]));
  a.download = `STOCK_CARD_${reportProductName.value}.pdf`;
  a.click();
};

/* ✅ NEW: Generate/Update monthly reports for ALL products */
const confirmGenerateMonthlyReports = () => {
  Swal.fire({
    title: "Generate / Update Monthly Reports?",
    html: `
      <div style="text-align:left">
        <p>This will generate or update the <b>monthly stock snapshot</b> for <b>all products</b>.</p>
        <ul style="margin:8px 0 0 18px">
          <li>Uses current stock as the snapshot basis</li>
          <li>Updates existing month/year records if already created</li>
          <li>Recommended for month-end or manual correction</li>
        </ul>
      </div>
    `,
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Proceed",
    cancelButtonText: "Cancel",
    confirmButtonColor: "#0b4a6f",
  }).then(async (r) => {
    if (r.isConfirmed) await generateMonthlyReports();
  });
};

const generateMonthlyReports = async () => {
  Swal.fire({
    title: "Generating Monthly Reports...",
    text: "Please wait. Do not close this window.",
    allowOutsideClick: false,
    showConfirmButton: false,
    didOpen: () => Swal.showLoading(),
  });

  try {
    // backend should run command and return summary JSON
    const res = await axios.post(`${API_BASE}/reports/generate-monthly`, {
      // optional: allow backend to use these if you implement it
      year: selectedYear.value,
      force: true
    });

    Swal.close();

    const data = res.data || {};
    const monthLabel = data.month_label || "Selected month";
    const generated = data.generated ?? 0;
    const updated = data.updated ?? 0;
    const skipped = data.skipped ?? 0;
    const errors = Array.isArray(data.errors) ? data.errors : [];

    Swal.fire({
      title: "Monthly Reports Updated",
      icon: errors.length ? "warning" : "success",
      html: `
        <div style="text-align:left">
          <p><b>Coverage:</b> ${monthLabel}</p>
          <div style="margin-top:10px">
            <div><b>Generated:</b> ${generated}</div>
            <div><b>Updated:</b> ${updated}</div>
            <div><b>Skipped:</b> ${skipped}</div>
            <div><b>Errors:</b> ${errors.length}</div>
          </div>
          ${errors.length ? `
            <hr style="margin:12px 0" />
            <div style="max-height:120px; overflow:auto; font-size:12px; color:#7a271a">
              ${errors.map(e => `<div>• ${String(e).replaceAll('<','&lt;')}</div>`).join("")}
            </div>
          ` : ""}
        </div>
      `,
      confirmButtonColor: "#0b4a6f",
    });

    // Refresh products after report run (optional, safe)
    await fetchProducts();

  } catch (e) {
    Swal.close();
    Swal.fire("Error", "Failed to generate/update monthly reports.", "error");
  }
};
</script>

<style scoped>
/* Government-appropriate: clean, restrained, readable */
* { box-sizing: border-box; }

.container {
  max-width: 1400px;
  margin: 18px auto;
  padding: 0 18px;
  font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
  color: #1f2937;
  background: #f6f7fb;
}

.gov-card {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  box-shadow: 0 10px 24px rgba(17, 24, 39, 0.06);
}

.gov-header {
  background: linear-gradient(180deg, #0b4a6f 0%, #083a57 100%);
  color: #fff;
  border-radius: 16px;
  padding: 18px 18px;
  margin-bottom: 18px;
  border: 1px solid rgba(255,255,255,0.15);
}

.logos {
  display: flex;
  justify-content: center;
  gap: 12px;
  margin-bottom: 10px;
}

.logos img {
  width: 74px;
  height: auto;
  background: #fff;
  border-radius: 10px;
  padding: 6px;
  border: 1px solid rgba(0,0,0,0.08);
}

.header-titles { text-align: center; }
.gov-line { font-weight: 700; letter-spacing: 0.02em; font-size: 12px; opacity: 0.95; }
.gov-line.sub { font-weight: 600; opacity: 0.9; }
.header-titles h2 { margin: 6px 0 4px; font-size: 22px; font-weight: 800; }
.header-sub { margin: 0; font-size: 12px; opacity: 0.9; }

.top-controls {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 16px;
  padding: 16px;
  margin-bottom: 18px;
}

.block-label {
  display: block;
  font-size: 12px;
  font-weight: 800;
  color: #0b4a6f;
  margin-bottom: 8px;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.search-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  position: relative;
}

.search-bar i {
  position: absolute;
  left: 12px;
  color: #64748b;
}

.search-bar input {
  width: 100%;
  padding: 12px 12px 12px 38px;
  border: 1px solid #d1d5db;
  border-radius: 12px;
  outline: none;
  transition: 0.15s ease;
  background: #fbfdff;
}

.search-bar input:focus {
  border-color: #0b4a6f;
  box-shadow: 0 0 0 4px rgba(11, 74, 111, 0.14);
}

.annual-report-controls {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
}

.year-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  background: #f9fafb;
}

.control-label { font-weight: 800; color: #334155; font-size: 12px; }
.year-select {
  border: 1px solid #d1d5db;
  border-radius: 10px;
  padding: 8px 10px;
  background: #fff;
  font-weight: 700;
}

.note {
  margin: 10px 0 0;
  font-size: 12px;
  color: #475569;
}

/* Buttons */
.btn {
  border: none;
  border-radius: 12px;
  padding: 10px 14px;
  cursor: pointer;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: transform 0.12s ease, box-shadow 0.12s ease, background 0.12s ease;
}

.btn:hover { transform: translateY(-1px); }

.btn-primary {
  background: #0b4a6f;
  color: #fff;
  box-shadow: 0 10px 18px rgba(11, 74, 111, 0.18);
}
.btn-primary:hover { background: #093e5e; }

.btn-outline {
  background: #ffffff;
  color: #0b4a6f;
  border: 1px solid rgba(11, 74, 111, 0.35);
}
.btn-outline:hover {
  background: rgba(11, 74, 111, 0.06);
}

.btn-accent {
  background: linear-gradient(90deg, #11679a 0%, #0b4a6f 100%);
  color: #fff;
  box-shadow: 0 10px 18px rgba(17, 103, 154, 0.18);
}
.btn-accent:hover { filter: brightness(0.98); }

.btn-info { background: #1677a3; color: #fff; }
.btn-warning { background: #f0b429; color: #1f2937; }
.btn-danger { background: #b42318; color: #fff; }
.btn-secondary { background: #6b7280; color: #fff; }
.btn-outline-primary { background: transparent; border: 1px solid #0b4a6f; color: #0b4a6f; }

.btn-sm { padding: 8px 10px; border-radius: 10px; font-weight: 800; font-size: 12px; }
.btn-outline-secondary { background: transparent; border: 1px solid #cbd5e1; color: #334155; }

/* Table */
.table-wrapper { padding: 10px 0 0; overflow: hidden; }
.table-top {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  padding: 14px 16px 8px;
  align-items: flex-end;
}
.table-title h3 { margin: 0; font-size: 16px; font-weight: 900; color: #0b4a6f; }
.muted { color: #64748b; }
.tiny { font-size: 12px; }

.table-meta { display: flex; gap: 8px; flex-wrap: wrap; }
.meta-pill {
  display: inline-flex;
  gap: 8px;
  align-items: center;
  padding: 8px 10px;
  border-radius: 999px;
  border: 1px solid #e5e7eb;
  background: #f9fafb;
  font-weight: 800;
  font-size: 12px;
  color: #334155;
}

table {
  width: 100%;
  border-collapse: collapse;
}

thead th {
  background: #0b4a6f;
  color: #fff;
  padding: 12px 14px;
  text-align: left;
  font-size: 12px;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

tbody td {
  padding: 12px 14px;
  border-bottom: 1px solid #eef2f7;
  vertical-align: middle;
  background: #fff;
}

tbody tr:hover td { background: #f7fbff; }

.product-img {
  width: 46px;
  height: 46px;
  object-fit: cover;
  border-radius: 10px;
  border: 1px solid #e5e7eb;
}

.no-image {
  display: inline-block;
  padding: 8px 10px;
  border-radius: 10px;
  background: #f1f5f9;
  border: 1px solid #e5e7eb;
  color: #64748b;
  font-weight: 700;
  font-size: 12px;
}

.product-name { font-weight: 900; color: #111827; }

.actions { display: flex; gap: 8px; flex-wrap: wrap; }

/* Pills */
.stock-pill {
  padding: 8px 12px;
  border-radius: 999px;
  font-weight: 900;
  color: #fff;
  display: inline-block;
  min-width: 60px;
  text-align: center;
}
.stock-red { background: #b42318; }
.stock-yellow { background: #f0b429; color: #111827; }
.stock-green { background: #1a7f37; }

.status-pill {
  padding: 8px 12px;
  border-radius: 999px;
  font-weight: 900;
  font-size: 12px;
  display: inline-block;
  min-width: 120px;
  text-align: center;
}
.status-red { background: rgba(180,35,24,0.12); border: 1px solid rgba(180,35,24,0.25); color: #7a271a; }
.status-yellow { background: rgba(240,180,41,0.16); border: 1px solid rgba(240,180,41,0.32); color: #7a4b00; }
.status-green { background: rgba(26,127,55,0.12); border: 1px solid rgba(26,127,55,0.25); color: #14532d; }

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 12px;
  margin: 16px 0 6px;
}
.page-info { font-weight: 900; color: #334155; }

/* Modals */
.modal-overlay {
  position: fixed; inset: 0;
  background: rgba(15, 23, 42, 0.6);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  padding: 14px;
}

.modal-content {
  width: 100%;
  max-width: 1000px;
  background: #fff;
  border-radius: 16px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 24px 80px rgba(2, 6, 23, 0.35);
  overflow: hidden;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  padding: 16px 18px;
  background: #0b4a6f;
  color: #fff;
}
.modal-header h3 { margin: 0; font-weight: 900; }
.modal-body { padding: 18px; }
.modal-actions {
  padding: 14px 18px;
  border-top: 1px solid #eef2f7;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.table-responsive { overflow-x: auto; }
.modal-table {
  width: 100%;
  border-collapse: collapse;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
}
.modal-table th {
  background: #f1f5f9;
  color: #334155;
  padding: 10px;
  font-weight: 900;
}
.modal-table td { padding: 10px; border-top: 1px solid #eef2f7; }
.summary-row { background: #f8fafc; font-weight: 900; }

.edit-modal { max-width: 640px; }
.edit-form { display: grid; gap: 12px; }
.form-group label { font-weight: 900; color: #334155; font-size: 12px; }
.form-control {
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 12px;
  outline: none;
}
.form-control:focus { border-color: #0b4a6f; box-shadow: 0 0 0 4px rgba(11, 74, 111, 0.12); }

.input-group { display: flex; }
.input-group-text {
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-right: none;
  background: #f8fafc;
  border-radius: 12px 0 0 12px;
  font-weight: 900;
}
.input-group .form-control { border-left: none; border-radius: 0 12px 12px 0; }

.image-preview { margin-top: 8px; display: grid; place-items: center; }
.image-preview img { width: 120px; height: 120px; object-fit: cover; border-radius: 12px; border: 1px solid #e5e7eb; }

.empty {
  text-align: center;
  padding: 22px;
  color: #64748b;
  font-weight: 800;
}
.empty i { font-size: 26px; margin-bottom: 8px; display: block; color: #94a3b8; }
.empty-title { color: #334155; font-weight: 900; }

@media (max-width: 900px) {
  .top-controls { grid-template-columns: 1fr; }
  .actions { flex-direction: column; align-items: flex-start; }
}
</style>
