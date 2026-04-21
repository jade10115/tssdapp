<template>
  <div class="main-content">
    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <h2>ADL Status</h2>
        <p class="sub">
          Manage ADL master list status per year (filtered by <b>created_at</b>).
        </p>
      </div>

      <div class="controls">
        <label class="control">
          <span>Year</span>
          <select v-model="year" @change="fetchAll">
            <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
          </select>
        </label>

        <label class="control search">
          <span>Search</span>
          <input v-model="search" placeholder="Search ADL / sponsor..." />
        </label>

        
      </div>
    </div>

    <!-- Reporting Cards -->
    <div class="cards-grid">
      <div class="stat-card">
        <div class="stat-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 21V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v14" />
            <path d="M9 21V9h6v12" />
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ filteredMasters.length }}</div>
          <div class="stat-label">Masters (filtered)</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon amount">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="1" x2="12" y2="23"></line>
            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ formatMoney(totalAllocated) }}</div>
          <div class="stat-label">Total Allocated</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon balance">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="1" y="4" width="22" height="16" rx="2" ry="2" />
            <line x1="1" y1="10" x2="23" y2="10" />
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ formatMoney(totalBalance) }}</div>
          <div class="stat-label">Remaining Balance</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon lgu">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 21h18" />
            <path d="M6 21V8l6-4 6 4v13" />
            <path d="M10 21v-7h4v7" />
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ formatNumber(totalLGU) }}</div>
          <div class="stat-label">Total LGU (rows)</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon ben">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ formatNumber(totalBeneficiaries) }}</div>
          <div class="stat-label">Total Beneficiaries</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon util">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ overallUtilization }}%</div>
          <div class="stat-label">Overall Utilization</div>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="table-section">
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>ADL</th>
              <th>Sponsor</th>
              <th>Year</th>
              <th class="numeric">Amount</th>
              <th class="numeric">Balance</th>
              <th class="numeric">LGU</th>
              <th class="numeric">Beneficiaries</th>
              <th class="numeric">Utilization</th>
              <th>Status</th>
              <th class="actions-head">Actions</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="m in filteredMasters" :key="m.id">
              <td>
                <div class="adl-title">{{ m.adl || m.name || m.adl_name || "—" }}</div>
                <div class="adl-sub">ID: {{ m.id }}</div>
              </td>

              <td>{{ m.sponsor || "—" }}</td>

              <td>{{ displayYear(m) }}</td>

              <td class="numeric">{{ formatMoney(masterAmount(m)) }}</td>
              <td class="numeric">
                <span :class="['money-pill', masterBalance(m) <= 0 ? 'zero' : 'ok']">
                  {{ formatMoney(masterBalance(m)) }}
                </span>
              </td>

              <td class="numeric">
                <span class="num-pill">{{ formatNumber(m._total_lgu || 0) }}</span>
              </td>

              <td class="numeric">
                <span class="num-pill benpill">{{ formatNumber(m._beneficiaries || 0) }}</span>
              </td>

              <td class="numeric">
                <div class="util-cell">
                  <div class="util-bar" :title="`${m._utilization || 0}%`">
                    <div class="util-fill" :style="{ width: (m._utilization || 0) + '%' }"></div>
                  </div>
                  <div class="util-text">{{ m._utilization || 0 }}%</div>
                </div>
              </td>

              <td>
                <span :class="['pill', statusClass(m.status)]">
                  {{ normalizeStatus(m.status) }}
                </span>
              </td>

              <td class="actions-cell">
                <button class="icon-btn" @click="openAdl(m)" title="Open ADL details">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                </button>

                <button class="icon-btn" @click="openEditModal(m)" title="Edit">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                  </svg>
                </button>

                <button
                  class="icon-btn"
                  @click="toggleStatus(m)"
                  :disabled="savingId === m.id"
                  title="Toggle status"
                >
                  <svg v-if="savingId === m.id" class="spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 12a9 9 0 11-6.219-8.56"/>
                  </svg>
                  <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 4 23 10 17 10"/>
                    <path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/>
                  </svg>
                </button>

                <button
                  class="icon-btn danger"
                  @click="deleteMaster(m)"
                  :disabled="savingId === m.id"
                  title="Delete"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                  </svg>
                </button>
              </td>
            </tr>

            <tr v-if="!loading && filteredMasters.length === 0">
              <td colspan="10" class="empty">No ADL masters found.</td>
            </tr>

            <tr v-if="loading">
              <td colspan="10" class="empty">
                <div class="loading-content">
                  <div class="loading-spinner"></div>
                  <span>Loading...</span>
                </div>
              </td>
            </tr>
          </tbody>

          <tfoot v-if="!loading && filteredMasters.length">
            <tr class="tfoot">
              <td colspan="3" class="tfoot-label">Totals (filtered)</td>
              <td class="numeric">{{ formatMoney(totalAllocated) }}</td>
              <td class="numeric">{{ formatMoney(totalBalance) }}</td>
              <td class="numeric">{{ formatNumber(totalLGU) }}</td>
              <td class="numeric">{{ formatNumber(totalBeneficiaries) }}</td>
              <td class="numeric">{{ overallUtilization }}%</td>
              <td colspan="2"></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- MODAL -->
    <Teleport to="body">
      <Transition name="modal" appear>
        <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
          <div class="modal-container" @click.stop>
            <div class="modal-header">
              <div class="modal-title">
                <svg v-if="modalMode === 'create'" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 5v14M5 12h14"/>
                </svg>
                <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                  <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                {{ modalMode === "create" ? "Add ADL" : "Edit ADL" }}
              </div>
              <button class="close-btn" @click="closeModal">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="18" y1="6" x2="6" y2="18"/>
                  <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </div>

            <div class="modal-body">
              <form @submit.prevent="saveMaster">
                <div class="form-grid">
                  <div class="field-group">
                    <label class="field-label">
                      ADL Number <span class="required">*</span>
                    </label>
                    <div class="input-wrapper">
                      <input
                        type="text"
                        v-model="form.adl"
                        placeholder="e.g. ADL-2025-001"
                        class="field-input"
                        required
                      />
                    </div>
                  </div>

                  <div class="field-group">
                    <label class="field-label">Sponsor</label>
                    <div class="input-wrapper">
                      <input
                        type="text"
                        v-model="form.sponsor"
                        placeholder="e.g. DOLE / LGU / NGO"
                        class="field-input"
                      />
                    </div>
                  </div>

                  <div class="field-group">
                    <label class="field-label">
                      Amount (PHP) <span class="required">*</span>
                    </label>
                    <div class="input-wrapper">
                      <input
                        type="number"
                        v-model.number="form.total_amount"
                        min="0"
                        step="0.01"
                        placeholder="0.00"
                        class="field-input"
                        @input="syncBalance"
                        required
                      />
                    </div>
                    <p class="field-hint">Balance will be set equal to Amount on create</p>
                  </div>

                  <div class="field-group">
                    <label class="field-label">Balance (PHP)</label>
                    <div class="input-wrapper">
                      <input
                        type="number"
                        v-model.number="form.remaining_amount"
                        min="0"
                        step="0.01"
                        placeholder="0.00"
                        class="field-input"
                        :disabled="modalMode === 'create'"
                        :readonly="modalMode === 'create'"
                      />
                    </div>
                    <p class="field-hint">
                      {{ modalMode === "create" ? "Auto (same as Amount for new ADL)" : "Current remaining balance" }}
                    </p>
                  </div>

                  <div class="field-group full-width">
                    <label class="field-label">Status</label>
                    <div class="select-wrapper">
                      <select v-model="form.status" class="field-input">
                        <option value="Open">Open</option>
                        <option value="Closed">Closed</option>
                        <option value="Pending">Pending</option>
                      </select>
                    </div>
                  </div>

                  <div class="field-group full-width info-box" v-if="modalMode === 'edit' && editingId">
                    <div class="info-row">
                      <span>Note:</span>
                      <span>LGU/Beneficiaries are computed from breakdowns (reporting only).</span>
                    </div>
                  </div>
                </div>
              </form>
            </div>

            <div class="modal-footer">
              <button class="btn btn-outline" @click="closeModal" :disabled="modalSaving">
                Cancel
              </button>
              <button class="btn btn-primary" @click="saveMaster" :disabled="modalSaving">
                <svg v-if="modalSaving" class="spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M21 12a9 9 0 11-6.219-8.56"/>
                </svg>
                {{ modalSaving ? "Saving..." : "Save Changes" }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import axios from "axios";
import { useRouter } from "vue-router";
import Swal from "sweetalert2";
import { inject } from "vue";

const API_BASE = inject("API_BASE");
const router = useRouter();

const masters = ref([]);
const loading = ref(false);
const savingId = ref(null);

const year = ref(new Date().getFullYear());
const search = ref("");

// reporting sources
const adlDetails = ref([]);     // all details for selected year
const breakdowns = ref([]);     // all breakdown rows (we filter/map in JS)

/** Modal state */
const showModal = ref(false);
const modalMode = ref("create");
const modalSaving = ref(false);
const editingId = ref(null);

const form = ref({
  adl: "",
  sponsor: "",
  total_amount: 0,
  remaining_amount: 0,
  status: "Open",
});

const yearOptions = computed(() => {
  const now = new Date().getFullYear();
  const yrs = [];
  for (let y = now + 1; y >= now - 6; y--) yrs.push(y);
  return yrs;
});

const formatMoney = (value) =>
  new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(Number(value || 0));

const formatNumber = (n) => {
  const x = Number(n || 0);
  return Number.isFinite(x) ? Math.trunc(x).toLocaleString() : "0";
};

const normalizeStatus = (s) => {
  const v = String(s || "").toLowerCase().trim();
  if (!v) return "Pending";
  if (["open", "active", "enabled"].includes(v)) return "Open";
  if (["closed", "inactive", "disabled"].includes(v)) return "Closed";
  if (["pending", "draft"].includes(v)) return "Pending";
  return v.charAt(0).toUpperCase() + v.slice(1);
};

const statusClass = (s) => {
  const v = normalizeStatus(s).toLowerCase();
  if (v === "open") return "status-open";
  if (v === "closed") return "status-closed";
  return "status-pending";
};

const displayYear = (m) => {
  const ts = m.created_at || m.createdAt || m.created;
  if (!ts) return m.year || year.value;
  const d = new Date(ts);
  if (Number.isNaN(d.getTime())) return m.year || year.value;
  return d.getFullYear();
};

// Safe getters for amount/balance fields
const masterAmount = (m) => Number(m.total_amount ?? m.amount ?? 0);
const masterBalance = (m) => Number(m.remaining_amount ?? m.balance ?? (m.total_amount ?? 0));

const utilizationPct = (amount, balance) => {
  const a = Number(amount || 0);
  const b = Number(balance || 0);
  if (a <= 0) return 0;
  if (b <= 0) return 100;
  const pct = Math.round(((a - b) / a) * 100);
  return Math.max(0, Math.min(100, pct));
};

const filteredMasters = computed(() => {
  const s = search.value.trim().toLowerCase();
  const list = masters.value;
  if (!s) return list;

  return list.filter((m) => {
    const adl = (m.adl || m.name || m.adl_name || "").toLowerCase();
    const sponsor = (m.sponsor || "").toLowerCase();
    return adl.includes(s) || sponsor.includes(s);
  });
});

/**
 * Reporting totals (FILTERED)
 */
const totalAllocated = computed(() =>
  filteredMasters.value.reduce((sum, m) => sum + masterAmount(m), 0)
);

const totalBalance = computed(() =>
  filteredMasters.value.reduce((sum, m) => sum + masterBalance(m), 0)
);

const totalLGU = computed(() =>
  filteredMasters.value.reduce((sum, m) => sum + Number(m._total_lgu || 0), 0)
);

const totalBeneficiaries = computed(() =>
  filteredMasters.value.reduce((sum, m) => sum + Number(m._beneficiaries || 0), 0)
);

const overallUtilization = computed(() => utilizationPct(totalAllocated.value, totalBalance.value));

/**
 * Fetch masters + details + breakdowns, then compute reporting fields per master:
 * - _total_lgu
 * - _beneficiaries
 * - _utilization
 */
const fetchAll = async () => {
  loading.value = true;
  try {
    const [mastersRes, detailsRes, breakdownRes] = await Promise.all([
      axios.get(`${API_BASE}/tupad_adl_masters`, { params: { year: year.value } }),
      axios.get(`${API_BASE}/tupad_adl_details`, { params: { year: year.value } }),
      axios.get(`${API_BASE}/tupad_adl_breakdown`),
    ]);

    masters.value = Array.isArray(mastersRes.data) ? mastersRes.data : [];
    adlDetails.value = Array.isArray(detailsRes.data) ? detailsRes.data : [];
    breakdowns.value = Array.isArray(breakdownRes.data) ? breakdownRes.data : [];

    computeReporting();
  } catch (err) {
    console.error(err);
    Swal.fire("Error", err.response?.data?.message || "Failed to load ADL reporting.", "error");
  } finally {
    loading.value = false;
  }
};

const computeReporting = () => {
  // detailId -> masterId map
  const detailToMaster = {};
  adlDetails.value.forEach((d) => {
    const did = Number(d.id || 0);
    const mid = Number(d.adl_master_id || 0);
    if (did && mid) detailToMaster[did] = mid;
  });

  // master aggregates
  const benByMaster = {};
  const lguByMaster = {};

  // count LGU rows and sum beneficiaries from breakdown rows
  breakdowns.value.forEach((b) => {
    const did = Number(b.adl_detail_id || 0); // ✅ correct FK
    const mid = detailToMaster[did];
    if (!mid) return;

    // LGU = number of breakdown rows (as per your previous logic)
    lguByMaster[mid] = (lguByMaster[mid] || 0) + 1;

    const ben = Number(b.beneficiaries || 0) || 0;
    benByMaster[mid] = (benByMaster[mid] || 0) + ben;
  });

  // attach computed fields to master list
  masters.value = masters.value.map((m) => {
    const mid = Number(m.id);
    const amt = masterAmount(m);
    const bal = masterBalance(m);
    return {
      ...m,
      _total_lgu: lguByMaster[mid] || 0,
      _beneficiaries: benByMaster[mid] || 0,
      _utilization: utilizationPct(amt, bal),
    };
  });
};

const openAdl = (m) => {
  const adlId = Number(m?.id || 0);
  if (!adlId) return;

  // ✅ Use MASTER query param
  router.push({
    name: "Adlbreakdown",
    query: { adl_id: adlId },
  });
};

const resetForm = () => {
  form.value = {
    adl: "",
    sponsor: "",
    total_amount: 0,
    remaining_amount: 0,
    status: "Open",
  };
};

const syncBalance = () => {
  if (modalMode.value === "create") {
    form.value.remaining_amount = Number(form.value.total_amount || 0);
  }
};

const openCreateModal = () => {
  modalMode.value = "create";
  editingId.value = null;
  resetForm();
  form.value.status = "Open";
  syncBalance();
  showModal.value = true;
};

const openEditModal = (m) => {
  modalMode.value = "edit";
  editingId.value = m.id;

  form.value = {
    adl: m.adl || m.name || m.adl_name || "",
    sponsor: m.sponsor || "",
    total_amount: masterAmount(m),
    remaining_amount: masterBalance(m),
    status: normalizeStatus(m.status),
  };

  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  modalSaving.value = false;
};

const saveMaster = async () => {
  const payload = {
    adl: String(form.value.adl || "").trim(),
    sponsor: String(form.value.sponsor || "").trim() || null,
    total_amount: Number(form.value.total_amount || 0),
    status: form.value.status,
  };

  if (!payload.adl) {
    Swal.fire("Required", "ADL Number is required.", "warning");
    return;
  }

  if (payload.total_amount < 0) {
    Swal.fire("Invalid", "Amount must be 0 or greater.", "warning");
    return;
  }

  modalSaving.value = true;
  try {
    if (modalMode.value === "create") {
      await axios.post(`${API_BASE}/tupad_adl_masters`, payload);
      Swal.fire("Success", "ADL created successfully.", "success");
    } else {
      await axios.put(`${API_BASE}/tupad_adl_masters/${editingId.value}`, payload);
      Swal.fire("Success", "ADL updated successfully.", "success");
    }

    closeModal();
    await fetchAll();
  } catch (err) {
    console.error(err);
    Swal.fire("Error", err.response?.data?.message || err.message, "error");
  } finally {
    modalSaving.value = false;
  }
};

const toggleStatus = async (m) => {
  const current = normalizeStatus(m.status).toLowerCase();
  const next = current === "open" ? "Closed" : "Open";

  const confirm = await Swal.fire({
    title: "Update Status?",
    text: `Set this ADL to "${next}"?`,
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Yes, update",
  });

  if (!confirm.isConfirmed) return;

  savingId.value = m.id;
  try {
    await axios.put(`${API_BASE}/tupad_adl_masters/${m.id}`, {
      adl: m.adl || m.name || m.adl_name || "",
      sponsor: m.sponsor || null,
      total_amount: masterAmount(m),
      status: next,
    });

    Swal.fire("Success", "Status updated successfully.", "success");
    await fetchAll();
  } catch (err) {
    console.error(err);
    Swal.fire("Error", err.response?.data?.message || err.message, "error");
  } finally {
    savingId.value = null;
  }
};

const deleteMaster = async (m) => {
  const confirm = await Swal.fire({
    title: "Delete ADL?",
    text: `This will permanently delete "${m.adl || m.name || "ADL"}".`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, delete",
    confirmButtonColor: "#ef4444",
  });

  if (!confirm.isConfirmed) return;

  savingId.value = m.id;
  try {
    await axios.delete(`${API_BASE}/tupad_adl_masters/${m.id}`);
    Swal.fire("Success", "ADL deleted successfully.", "success");
    await fetchAll();
  } catch (err) {
    console.error(err);
    Swal.fire("Error", err.response?.data?.message || err.message, "error");
  } finally {
    savingId.value = null;
  }
};

onMounted(fetchAll);
</script>

<style scoped>
/* Base Styles */
.main-content {
  padding: 24px;
  max-width: 1500px;
  margin: 0 auto;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  color: #1e293b;
  background: #f8fafc;
  min-height: 100vh;
}

/* Header */
.page-header {
  background: white;
  padding: 22px;
  border-radius: 16px;
  box-shadow: 0 10px 26px rgba(2, 6, 23, 0.06);
  margin-bottom: 16px;
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 18px;
  flex-wrap: wrap;
  border: 1px solid #eef2f7;
}

.header-left h2 {
  margin: 0;
  font-size: 26px;
  font-weight: 900;
  color: #0f172a;
  letter-spacing: -0.02em;
}

.sub {
  margin: 6px 0 0;
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
}

.controls {
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
  align-items: flex-end;
}

.control {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.control span {
  font-size: 12px;
  font-weight: 800;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.control select,
.control input {
  padding: 10px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: white;
  font-size: 14px;
  font-weight: 700;
  transition: all 0.2s ease;
  outline: none;
}

.control select:focus,
.control input:focus {
  border-color: rgba(37, 99, 235, 0.55);
  box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
}

.control.search input {
  min-width: 280px;
}

.actions {
  display: flex;
  gap: 10px;
  align-items: flex-end;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border: none;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 900;
  cursor: pointer;
  transition: all 0.2s ease;
  outline: none;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: white;
  box-shadow: 0 10px 22px rgba(37, 99, 235, 0.18);
}

.btn-primary:not(:disabled):hover {
  transform: translateY(-1px);
  box-shadow: 0 14px 28px rgba(37, 99, 235, 0.24);
}

.btn-outline {
  background: white;
  border: 1px solid #e2e8f0;
  color: #475569;
}

.btn-outline:not(:disabled):hover {
  background: #f8fafc;
}

/* Cards */
.cards-grid {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 12px;
  margin: 14px 0 18px;
}

.stat-card {
  background: #fff;
  border: 1px solid #eef2f7;
  border-radius: 16px;
  padding: 14px;
  display: flex;
  gap: 12px;
  align-items: center;
  box-shadow: 0 10px 26px rgba(2, 6, 23, 0.05);
}

.stat-icon {
  width: 44px;
  height: 44px;
  border-radius: 14px;
  display: grid;
  place-items: center;
  background: rgba(37, 99, 235, 0.10);
  color: #1d4ed8;
  flex-shrink: 0;
  border: 1px solid rgba(37, 99, 235, 0.12);
}

.stat-icon svg {
  width: 22px;
  height: 22px;
}

.stat-icon.amount { background: rgba(16, 185, 129, 0.10); color: #10b981; border-color: rgba(16,185,129,0.16); }
.stat-icon.balance { background: rgba(59, 130, 246, 0.10); color: #3b82f6; border-color: rgba(59,130,246,0.16); }
.stat-icon.lgu { background: rgba(245, 158, 11, 0.12); color: #f59e0b; border-color: rgba(245,158,11,0.18); }
.stat-icon.ben { background: rgba(236, 72, 153, 0.10); color: #ec4899; border-color: rgba(236,72,153,0.16); }
.stat-icon.util { background: rgba(239, 68, 68, 0.10); color: #ef4444; border-color: rgba(239,68,68,0.16); }

.stat-content {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.stat-value {
  font-size: 18px;
  font-weight: 1000;
  color: #0f172a;
  letter-spacing: -0.02em;
}

.stat-label {
  font-size: 12px;
  font-weight: 800;
  color: #64748b;
}

/* Table */
.table-section {
  background: white;
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(2, 6, 23, 0.05);
  overflow: hidden;
  border: 1px solid #eef2f7;
}

.table-wrapper {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  min-width: 1220px;
}

thead th {
  background: linear-gradient(180deg, #fcfdff, #f2f6ff);
  padding: 14px 16px;
  text-align: left;
  font-size: 12px;
  font-weight: 900;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  border-bottom: 1px solid #eef2f7;
  white-space: nowrap;
}

tbody td {
  padding: 14px 16px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 14px;
  vertical-align: middle;
}

tbody tr:hover {
  background: #fbfdff;
}

.numeric {
  text-align: right;
  font-weight: 900;
  font-variant-numeric: tabular-nums;
}

.adl-title {
  font-weight: 900;
  color: #0f172a;
}

.adl-sub {
  margin-top: 4px;
  font-size: 12px;
  color: #94a3b8;
  font-weight: 700;
}

.actions-head {
  width: 220px;
  text-align: center;
}

.actions-cell {
  display: flex;
  gap: 8px;
  justify-content: center;
  align-items: center;
}

.icon-btn {
  width: 36px;
  height: 36px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: white;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  color: #64748b;
  box-shadow: 0 6px 14px rgba(2, 6, 23, 0.04);
}

.icon-btn:hover {
  background: #f8fafc;
  color: #334155;
  transform: translateY(-1px);
}

.icon-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.icon-btn.danger:hover {
  background: #fef2f2;
  border-color: rgba(239, 68, 68, 0.35);
  color: #dc2626;
}

.pill {
  display: inline-flex;
  align-items: center;
  padding: 5px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 900;
  border: 1px solid;
}

.status-open {
  background: rgba(16, 185, 129, 0.10);
  border-color: rgba(16, 185, 129, 0.22);
  color: #047857;
}

.status-closed {
  background: rgba(239, 68, 68, 0.10);
  border-color: rgba(239, 68, 68, 0.22);
  color: #b91c1c;
}

.status-pending {
  background: rgba(245, 158, 11, 0.12);
  border-color: rgba(245, 158, 11, 0.25);
  color: #92400e;
}

.num-pill {
  display: inline-flex;
  justify-content: flex-end;
  padding: 6px 10px;
  border-radius: 999px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  min-width: 70px;
}

.benpill {
  background: rgba(236, 72, 153, 0.08);
  border-color: rgba(236, 72, 153, 0.16);
  color: #9d174d;
}

.money-pill {
  display: inline-flex;
  justify-content: flex-end;
  padding: 6px 10px;
  border-radius: 999px;
  font-weight: 1000;
  border: 1px solid;
  min-width: 140px;
}

.money-pill.ok {
  background: rgba(16, 185, 129, 0.10);
  border-color: rgba(16, 185, 129, 0.25);
  color: #047857;
}

.money-pill.zero {
  background: rgba(239, 68, 68, 0.10);
  border-color: rgba(239, 68, 68, 0.25);
  color: #b91c1c;
}

/* Util cell */
.util-cell {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
}

.util-bar {
  width: 120px;
  height: 10px;
  border-radius: 999px;
  background: #eef2f7;
  border: 1px solid #e8eef7;
  overflow: hidden;
}

.util-fill {
  height: 100%;
  background: linear-gradient(90deg, #2563eb, #1d4ed8);
  width: 0%;
  transition: width 0.35s ease;
}

.util-text {
  width: 46px;
  text-align: right;
  font-weight: 1000;
  color: #334155;
}

/* Footer totals row */
.tfoot {
  background: #f8fafc;
  border-top: 2px solid #e2e8f0;
}

.tfoot td {
  padding: 14px 16px;
  font-weight: 1000;
  color: #0f172a;
}

.tfoot-label {
  text-transform: uppercase;
  letter-spacing: 0.06em;
  font-size: 12px;
  color: #64748b;
}

/* Loading */
.empty {
  text-align: center;
  padding: 48px;
  color: #94a3b8;
  font-weight: 800;
}

.loading-content {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 24px;
}

.loading-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid #e2e8f0;
  border-top: 2px solid #2563eb;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.72);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  z-index: 1000;
  overflow-y: auto;
}

.modal-container {
  background: white;
  border-radius: 18px;
  box-shadow: 0 30px 50px rgba(2, 6, 23, 0.22);
  width: 100%;
  max-width: 620px;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  border: 1px solid rgba(226, 232, 240, 0.9);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
}

.modal-title {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 18px;
  font-weight: 1000;
  color: #0f172a;
}

.close-btn {
  width: 38px;
  height: 38px;
  border: 1px solid #e2e8f0;
  background: white;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  color: #64748b;
}

.close-btn:hover {
  background: #f1f5f9;
  color: #334155;
}

.modal-body {
  padding: 20px;
  overflow-y: auto;
  flex: 1;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.field-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.field-group.full-width {
  grid-column: 1 / -1;
}

.field-label {
  font-size: 13px;
  font-weight: 900;
  color: #334155;
}

.required { color: #ef4444; }

.field-input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 800;
  outline: none;
  background: white;
  transition: all 0.2s ease;
}

.field-input:focus {
  border-color: rgba(37, 99, 235, 0.55);
  box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
}

.field-input:disabled,
.field-input:read-only {
  background: #f8fafc;
  color: #94a3b8;
  cursor: not-allowed;
}

.field-hint {
  font-size: 12px;
  color: #64748b;
  margin: 0;
  font-weight: 700;
}

.info-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 10px 12px;
}

.info-row {
  display: flex;
  gap: 8px;
  font-size: 12px;
  font-weight: 800;
  color: #64748b;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 18px 20px;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
}

.spin {
  animation: spin 1s linear infinite;
}

/* Modal Transitions */
.modal-enter-active,
.modal-leave-active {
  transition: all 0.25s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .modal-container,
.modal-leave-active .modal-container {
  transition: all 0.25s ease;
}

.modal-enter-from .modal-container,
.modal-leave-to .modal-container {
  transform: scale(0.97) translateY(-18px);
  opacity: 0;
}

/* Responsive */
@media (max-width: 1200px) {
  .cards-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 768px) {
  .main-content { padding: 16px; }
  .page-header { flex-direction: column; align-items: stretch; }
  .controls { flex-direction: column; align-items: stretch; }
  .control.search input { min-width: 100%; }
  .form-grid { grid-template-columns: 1fr; }
  .cards-grid { grid-template-columns: 1fr; }
}
</style>
