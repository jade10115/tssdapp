<template>
  <div class="main-content">
    <div class="page-header">
      <div class="header-left">
        <h2>ADL Status</h2>
        <p class="sub">
          Clean totals, unique LGU computation, peso-formatted amounts, and improved table UI.
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
          <input
            v-model="search"
            type="text"
            placeholder="Search ADL or sponsor..."
          />
        </label>

        <div class="actions">
          <button class="btn btn-outline" @click="fetchAll" :disabled="loading">
            {{ loading ? "Loading..." : "Refresh" }}
          </button>

          <button class="btn btn-primary" @click="openCreateModal">
            + Add ADL
          </button>
        </div>
      </div>
    </div>

    <div class="cards-grid">
      <div class="stat-card">
        <div class="stat-icon neutral">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 21V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v14" />
            <path d="M9 21V9h6v12" />
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ filteredMasters.length }}</div>
          <div class="stat-label">Masters</div>
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
          <div class="stat-label">Allocated</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon spent">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 20V10" />
            <path d="M18 14l-6 6-6-6" />
            <path d="M20 4H4" />
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ formatMoney(totalSpent) }}</div>
          <div class="stat-label">Spent</div>
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
          <div class="stat-label">Balance</div>
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
          <div class="stat-label">Unique LGUs</div>
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
          <div class="stat-label">Beneficiaries</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon util">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ formatPercent(overallUtilization) }}</div>
          <div class="stat-label">Utilization</div>
        </div>
      </div>
    </div>

    <div class="table-section">
      <div class="table-head">
        <div>
          <h3>ADL Master List</h3>
          <p>
            {{ filteredMasters.length }} record<span v-if="filteredMasters.length !== 1">s</span>
            for {{ year }}
          </p>
        </div>

        <div class="table-summary">
          <span class="summary-chip">Spent: {{ formatMoney(totalSpent) }}</span>
          <span class="summary-chip">Balance: {{ formatMoney(totalBalance) }}</span>
        </div>
      </div>

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

          <tbody v-if="!loading && filteredMasters.length">
            <tr v-for="m in filteredMasters" :key="m.id">
              <td>
                <div class="adl-title">{{ m.adl || "—" }}</div>
                <div class="adl-sub">ID: {{ m.id }}</div>
              </td>

              <td>
                <div class="sponsor-text">{{ m.sponsor || "—" }}</div>
              </td>

              <td>{{ displayYear(m) }}</td>

              <td class="numeric">
                <span class="money-text">{{ formatMoney(masterAmount(m)) }}</span>
              </td>

              <td class="numeric">
                <span :class="['money-pill', masterBalance(m) <= 0 ? 'zero' : 'ok']">
                  {{ formatMoney(masterBalance(m)) }}
                </span>
              </td>

              <td class="numeric">
                <span
                  class="num-pill"
                  :title="(m._lgu_names || []).join(', ') || 'No LGU'"
                >
                  {{ formatNumber(masterLGU(m)) }}
                </span>
              </td>

              <td class="numeric">
                <span class="num-pill benpill">
                  {{ formatNumber(masterBeneficiaries(m)) }}
                </span>
              </td>

              <td class="numeric">
                <div class="util-cell">
                  <div class="util-bar" :title="formatPercent(masterUtilization(m))">
                    <div
                      :class="['util-fill', utilizationBarClass(masterUtilization(m))]"
                      :style="{ width: safePercent(masterUtilization(m)) + '%' }"
                    ></div>
                  </div>
                  <div class="util-text">{{ formatPercent(masterUtilization(m)) }}</div>
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
                  <svg
                    v-if="savingId === m.id"
                    class="spin"
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path d="M21 12a9 9 0 11-6.219-8.56"/>
                  </svg>
                  <svg
                    v-else
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
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
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                  </svg>
                </button>
              </td>
            </tr>
          </tbody>

          <tbody v-else-if="loading">
            <tr>
              <td colspan="10" class="empty">
                <div class="loading-content">
                  <div class="loading-spinner"></div>
                  <span>Loading ADL data...</span>
                </div>
              </td>
            </tr>
          </tbody>

          <tbody v-else>
            <tr>
              <td colspan="10" class="empty">No ADL masters found.</td>
            </tr>
          </tbody>

          <tfoot v-if="!loading && filteredMasters.length">
            <tr class="tfoot">
              <td colspan="3" class="tfoot-label">Totals</td>
              <td class="numeric">{{ formatMoney(totalAllocated) }}</td>
              <td class="numeric">{{ formatMoney(totalBalance) }}</td>
              <td class="numeric">{{ formatNumber(totalLGU) }}</td>
              <td class="numeric">{{ formatNumber(totalBeneficiaries) }}</td>
              <td class="numeric">{{ formatPercent(overallUtilization) }}</td>
              <td colspan="2"></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <Teleport to="body">
      <Transition name="modal" appear>
        <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
          <div class="modal-container" @click.stop>
            <div class="modal-header">
              <div class="modal-title">
                <svg
                  v-if="modalMode === 'create'"
                  width="20"
                  height="20"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path d="M12 5v14M5 12h14"/>
                </svg>
                <svg
                  v-else
                  width="20"
                  height="20"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
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
                    <input
                      type="text"
                      v-model="form.adl"
                      placeholder="e.g. ADL-2025-001"
                      class="field-input"
                      required
                    />
                  </div>

                  <div class="field-group">
                    <label class="field-label">Sponsor</label>
                    <input
                      type="text"
                      v-model="form.sponsor"
                      placeholder="e.g. DOLE / LGU / NGO"
                      class="field-input"
                    />
                  </div>

                  <div class="field-group">
                    <label class="field-label">
                      Amount <span class="required">*</span>
                    </label>
                    <input
                      type="text"
                      :value="amountInput"
                      @input="onAmountInput"
                      @blur="onAmountBlur"
                      inputmode="decimal"
                      placeholder="₱ 0"
                      class="field-input money-input"
                      required
                    />
                    <p class="field-hint">
                      Auto comma and peso sign enabled.
                    </p>
                  </div>

                  <div class="field-group">
                    <label class="field-label">Remaining Balance</label>
                    <input
                      type="text"
                      :value="formatMoney(form.remaining_amount)"
                      class="field-input readonly-money"
                      disabled
                      readonly
                    />
                    <p class="field-hint">
                      {{ modalMode === "create"
                        ? "On create, balance is the same as the total amount."
                        : "Computed balance based on actual spent amount." }}
                    </p>
                  </div>

                  <div class="field-group full-width">
                    <label class="field-label">Status</label>
                    <select v-model="form.status" class="field-input">
                      <option value="Open">Open</option>
                      <option value="Closed">Closed</option>
                      <option value="Pending">Pending</option>
                    </select>
                  </div>

                  <div class="field-group full-width info-box" v-if="modalMode === 'edit'">
                    <div class="info-row">
                      <span>Computation:</span>
                      <span>LGU count is unique per LGU name, beneficiaries are summed from breakdowns, and utilization uses actual spent amount first.</span>
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
                <svg
                  v-if="modalSaving"
                  class="spin"
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                >
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

const showModal = ref(false);
const modalMode = ref("create");
const modalSaving = ref(false);
const editingId = ref(null);
const amountInput = ref("₱ 0");

const form = ref({
  adl: "",
  sponsor: "",
  total_amount: 0,
  remaining_amount: 0,
  status: "Open",
});

const yearOptions = computed(() => {
  const now = new Date().getFullYear();
  return Array.from({ length: 8 }, (_, i) => now + 1 - i);
});

const currencyFormatter = new Intl.NumberFormat("en-PH", {
  style: "currency",
  currency: "PHP",
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
});

const numberFormatter = new Intl.NumberFormat("en-PH", {
  minimumFractionDigits: 0,
  maximumFractionDigits: 2,
});

const formatMoney = (value) => currencyFormatter.format(Number(value || 0));

const formatNumber = (value) => {
  const n = Number(value || 0);
  return Number.isFinite(n) ? Math.trunc(n).toLocaleString("en-PH") : "0";
};

const round2 = (value) =>
  Math.round((Number(value || 0) + Number.EPSILON) * 100) / 100;

const formatPercent = (value) =>
  `${round2(value).toLocaleString("en-PH", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  })}%`;

const safePercent = (value) => Math.max(0, Math.min(100, Number(value || 0)));

const sanitizeMoneyInput = (value = "") => {
  let cleaned = String(value).replace(/[^\d.]/g, "");

  const dotIndex = cleaned.indexOf(".");
  if (dotIndex !== -1) {
    cleaned =
      cleaned.slice(0, dotIndex + 1) +
      cleaned.slice(dotIndex + 1).replace(/\./g, "");
  }

  let [intPart = "", decPart = ""] = cleaned.split(".");
  intPart = intPart.replace(/^0+(?=\d)/, "");
  decPart = decPart.slice(0, 2);

  const safeInt = intPart === "" ? "0" : intPart;
  const formattedInt = Number(safeInt).toLocaleString("en-PH");
  const hasDot = dotIndex !== -1;

  const display = `₱ ${formattedInt}${hasDot ? `.${decPart}` : ""}`;
  const numeric = Number(`${safeInt}${hasDot ? `.${decPart}` : ""}`) || 0;

  return { display, numeric };
};

const setAmountDisplay = (value) => {
  const amount = Number(value || 0);
  form.value.total_amount = amount;
  amountInput.value = `₱ ${numberFormatter.format(amount)}`;

  if (modalMode.value === "create") {
    form.value.remaining_amount = amount;
  }
};

const onAmountInput = (event) => {
  const { display, numeric } = sanitizeMoneyInput(event.target.value);
  amountInput.value = display;
  form.value.total_amount = numeric;
  syncBalance();
};

const onAmountBlur = () => {
  amountInput.value = `₱ ${numberFormatter.format(Number(form.value.total_amount || 0))}`;
};

const normalizeStatus = (status) => {
  const value = String(status || "").trim().toLowerCase();

  if (["open", "active", "enabled"].includes(value)) return "Open";
  if (["closed", "inactive", "disabled"].includes(value)) return "Closed";
  return "Pending";
};

const statusClass = (status) => {
  const v = normalizeStatus(status).toLowerCase();
  if (v === "open") return "status-open";
  if (v === "closed") return "status-closed";
  return "status-pending";
};

const displayYear = (master) => {
  const ts = master.created_at || master.createdAt || master.created;
  if (!ts) return year.value;

  const d = new Date(ts);
  if (Number.isNaN(d.getTime())) return year.value;

  return d.getFullYear();
};

const masterAmount = (m) => Number(m.total_amount ?? 0);
const rawMasterBalance = (m) => Number(m.balance ?? m.remaining_amount ?? 0);
const masterSpent = (m) => Number(m._spent ?? Math.max(masterAmount(m) - rawMasterBalance(m), 0));
const masterBalance = (m) => Number(m.balance ?? Math.max(masterAmount(m) - masterSpent(m), 0));
const masterLGU = (m) => Number(m._total_lgu ?? 0);
const masterBeneficiaries = (m) => Number(m._beneficiaries ?? 0);

const masterUtilization = (m) => {
  if (m._utilization !== undefined && m._utilization !== null) {
    return Number(m._utilization);
  }

  const amount = masterAmount(m);
  const spent = masterSpent(m);

  if (amount <= 0) return 0;
  return round2((spent / amount) * 100);
};

const utilizationBarClass = (value) => {
  const v = safePercent(value);
  if (v >= 100) return "danger";
  if (v >= 75) return "warn";
  return "good";
};

const filteredMasters = computed(() => {
  const term = search.value.trim().toLowerCase();

  if (!term) return masters.value;

  return masters.value.filter((m) => {
    const adl = String(m.adl || "").toLowerCase();
    const sponsor = String(m.sponsor || "").toLowerCase();
    return adl.includes(term) || sponsor.includes(term);
  });
});

const totalAllocated = computed(() =>
  filteredMasters.value.reduce((sum, m) => sum + masterAmount(m), 0)
);

const totalSpent = computed(() =>
  filteredMasters.value.reduce((sum, m) => sum + masterSpent(m), 0)
);

const totalBalance = computed(() =>
  filteredMasters.value.reduce((sum, m) => sum + masterBalance(m), 0)
);

const totalLGU = computed(() =>
  filteredMasters.value.reduce((sum, m) => sum + masterLGU(m), 0)
);

const totalBeneficiaries = computed(() =>
  filteredMasters.value.reduce((sum, m) => sum + masterBeneficiaries(m), 0)
);

const overallUtilization = computed(() => {
  if (totalAllocated.value <= 0) return 0;
  return round2((totalSpent.value / totalAllocated.value) * 100);
});

const getErrorMessage = (err) => {
  const errors = err?.response?.data?.errors;
  if (errors && typeof errors === "object") {
    return Object.values(errors).flat().join("<br>");
  }

  return err?.response?.data?.message || err?.message || "Something went wrong.";
};

const fetchAll = async () => {
  loading.value = true;

  try {
    const { data } = await axios.get(`${API_BASE}/tupad_adl_masters`, {
      params: { year: year.value },
    });

    masters.value = Array.isArray(data) ? data : [];
  } catch (err) {
    console.error(err);
    Swal.fire({
      title: "Error",
      html: getErrorMessage(err),
      icon: "error",
    });
  } finally {
    loading.value = false;
  }
};

const openAdl = (m) => {
  const adlId = Number(m?.id || 0);
  if (!adlId) return;

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
  amountInput.value = "₱ 0";
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
  setAmountDisplay(0);
  showModal.value = true;
};

const openEditModal = (m) => {
  modalMode.value = "edit";
  editingId.value = m.id;

  form.value = {
    adl: m.adl || "",
    sponsor: m.sponsor || "",
    total_amount: masterAmount(m),
    remaining_amount: masterBalance(m),
    status: normalizeStatus(m.status),
  };

  setAmountDisplay(form.value.total_amount);
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
    status: normalizeStatus(form.value.status),
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
    Swal.fire({
      title: "Error",
      html: getErrorMessage(err),
      icon: "error",
    });
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
      adl: m.adl || "",
      sponsor: m.sponsor || null,
      total_amount: masterAmount(m),
      status: next,
    });

    Swal.fire("Success", "Status updated successfully.", "success");
    await fetchAll();
  } catch (err) {
    console.error(err);
    Swal.fire({
      title: "Error",
      html: getErrorMessage(err),
      icon: "error",
    });
  } finally {
    savingId.value = null;
  }
};

const deleteMaster = async (m) => {
  const confirm = await Swal.fire({
    title: "Delete ADL?",
    text: `This will permanently delete "${m.adl || "ADL"}".`,
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
    Swal.fire({
      title: "Error",
      html: getErrorMessage(err),
      icon: "error",
    });
  } finally {
    savingId.value = null;
  }
};

onMounted(fetchAll);
</script>

<style scoped>
.main-content {
  padding: 24px;
  max-width: 1600px;
  margin: 0 auto;
  min-height: 100vh;
  background:
    radial-gradient(circle at top right, rgba(37, 99, 235, 0.06), transparent 320px),
    #f8fafc;
  color: #0f172a;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
}

.page-header {
  background: rgba(255, 255, 255, 0.94);
  backdrop-filter: blur(10px);
  border: 1px solid #e7edf5;
  border-radius: 20px;
  box-shadow: 0 16px 40px rgba(15, 23, 42, 0.06);
  padding: 22px 24px;
  margin-bottom: 18px;
  display: flex;
  justify-content: space-between;
  align-items: end;
  gap: 18px;
  flex-wrap: wrap;
}

.header-left h2 {
  margin: 0;
  font-size: 30px;
  font-weight: 900;
  letter-spacing: -0.03em;
  color: #0f172a;
}

.sub {
  margin: 6px 0 0;
  color: #64748b;
  font-size: 13px;
  font-weight: 700;
}

.controls {
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
  align-items: end;
}

.control {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.control span {
  font-size: 12px;
  font-weight: 900;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.control select,
.control input {
  height: 44px;
  min-width: 140px;
  padding: 0 14px;
  border-radius: 14px;
  border: 1px solid #dbe4ef;
  background: #fff;
  outline: none;
  font-size: 14px;
  font-weight: 800;
  color: #0f172a;
  transition: 0.2s ease;
}

.control.search input {
  min-width: 280px;
}

.control select:focus,
.control input:focus {
  border-color: rgba(37, 99, 235, 0.6);
  box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
}

.actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.btn {
  height: 44px;
  padding: 0 16px;
  border-radius: 14px;
  border: none;
  cursor: pointer;
  font-size: 14px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: 0.2s ease;
}

.btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.btn-primary {
  color: #fff;
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  box-shadow: 0 12px 24px rgba(37, 99, 235, 0.22);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 16px 30px rgba(37, 99, 235, 0.24);
}

.btn-outline {
  background: #fff;
  border: 1px solid #dbe4ef;
  color: #334155;
}

.btn-outline:hover:not(:disabled) {
  background: #f8fafc;
}

.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
  gap: 14px;
  margin-bottom: 18px;
}

.stat-card {
  background: rgba(255, 255, 255, 0.96);
  border: 1px solid #e7edf5;
  border-radius: 18px;
  padding: 16px;
  display: flex;
  gap: 12px;
  align-items: center;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 16px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  border: 1px solid transparent;
}

.stat-icon svg {
  width: 22px;
  height: 22px;
}

.stat-icon.neutral {
  background: rgba(99, 102, 241, 0.10);
  color: #4f46e5;
  border-color: rgba(99, 102, 241, 0.14);
}

.stat-icon.amount {
  background: rgba(16, 185, 129, 0.10);
  color: #059669;
  border-color: rgba(16, 185, 129, 0.14);
}

.stat-icon.spent {
  background: rgba(249, 115, 22, 0.10);
  color: #ea580c;
  border-color: rgba(249, 115, 22, 0.14);
}

.stat-icon.balance {
  background: rgba(37, 99, 235, 0.10);
  color: #2563eb;
  border-color: rgba(37, 99, 235, 0.14);
}

.stat-icon.lgu {
  background: rgba(245, 158, 11, 0.12);
  color: #d97706;
  border-color: rgba(245, 158, 11, 0.16);
}

.stat-icon.ben {
  background: rgba(236, 72, 153, 0.10);
  color: #db2777;
  border-color: rgba(236, 72, 153, 0.14);
}

.stat-icon.util {
  background: rgba(239, 68, 68, 0.10);
  color: #dc2626;
  border-color: rgba(239, 68, 68, 0.14);
}

.stat-content {
  min-width: 0;
}

.stat-value {
  font-size: 20px;
  font-weight: 1000;
  color: #0f172a;
  line-height: 1.1;
  letter-spacing: -0.03em;
}

.stat-label {
  margin-top: 4px;
  font-size: 12px;
  font-weight: 800;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.table-section {
  background: rgba(255, 255, 255, 0.97);
  border: 1px solid #e7edf5;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
}

.table-head {
  padding: 18px 20px;
  border-bottom: 1px solid #eaf0f7;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  background: linear-gradient(180deg, #ffffff, #fbfdff);
}

.table-head h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 900;
  color: #0f172a;
}

.table-head p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
  font-weight: 700;
}

.table-summary {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.summary-chip {
  display: inline-flex;
  align-items: center;
  padding: 8px 12px;
  border-radius: 999px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  font-size: 12px;
  font-weight: 900;
  color: #334155;
}

.table-wrapper {
  overflow: auto;
  max-height: 72vh;
}

table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  min-width: 1280px;
}

thead th {
  position: sticky;
  top: 0;
  z-index: 2;
  background: linear-gradient(180deg, #fcfdff, #f4f7fb);
  color: #64748b;
  font-size: 12px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  padding: 14px 16px;
  text-align: left;
  border-bottom: 1px solid #e8eef5;
  white-space: nowrap;
}

tbody td,
tfoot td {
  padding: 14px 16px;
  font-size: 14px;
  border-bottom: 1px solid #eef3f8;
  vertical-align: middle;
}

tbody tr {
  background: #fff;
  transition: background 0.2s ease;
}

tbody tr:nth-child(even) {
  background: #fcfdff;
}

tbody tr:hover {
  background: #f8fbff;
}

.numeric {
  text-align: right;
  font-weight: 900;
  font-variant-numeric: tabular-nums;
}

.money-text {
  color: #0f172a;
  white-space: nowrap;
}

.adl-title {
  font-size: 14px;
  font-weight: 900;
  color: #0f172a;
}

.adl-sub {
  margin-top: 4px;
  font-size: 12px;
  color: #94a3b8;
  font-weight: 700;
}

.sponsor-text {
  color: #334155;
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
  width: 38px;
  height: 38px;
  border-radius: 12px;
  border: 1px solid #dbe4ef;
  background: #fff;
  color: #64748b;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: 0.2s ease;
  box-shadow: 0 8px 16px rgba(15, 23, 42, 0.04);
}

.icon-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  background: #f8fafc;
  color: #334155;
}

.icon-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.icon-btn.danger:hover:not(:disabled) {
  background: #fef2f2;
  color: #dc2626;
  border-color: rgba(239, 68, 68, 0.35);
}

.pill {
  display: inline-flex;
  align-items: center;
  padding: 6px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 900;
  border: 1px solid;
  white-space: nowrap;
}

.status-open {
  color: #047857;
  background: rgba(16, 185, 129, 0.10);
  border-color: rgba(16, 185, 129, 0.20);
}

.status-closed {
  color: #b91c1c;
  background: rgba(239, 68, 68, 0.10);
  border-color: rgba(239, 68, 68, 0.20);
}

.status-pending {
  color: #92400e;
  background: rgba(245, 158, 11, 0.12);
  border-color: rgba(245, 158, 11, 0.22);
}

.num-pill,
.money-pill {
  display: inline-flex;
  align-items: center;
  justify-content: flex-end;
  min-width: 78px;
  padding: 6px 10px;
  border-radius: 999px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  white-space: nowrap;
}

.benpill {
  color: #9d174d;
  background: rgba(236, 72, 153, 0.08);
  border-color: rgba(236, 72, 153, 0.15);
}

.money-pill {
  min-width: 145px;
  font-weight: 1000;
}

.money-pill.ok {
  color: #047857;
  background: rgba(16, 185, 129, 0.10);
  border-color: rgba(16, 185, 129, 0.20);
}

.money-pill.zero {
  color: #b91c1c;
  background: rgba(239, 68, 68, 0.10);
  border-color: rgba(239, 68, 68, 0.20);
}

.util-cell {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
}

.util-bar {
  width: 124px;
  height: 10px;
  border-radius: 999px;
  background: #edf2f7;
  border: 1px solid #e2e8f0;
  overflow: hidden;
}

.util-fill {
  height: 100%;
  width: 0;
  transition: width 0.3s ease;
}

.util-fill.good {
  background: linear-gradient(90deg, #22c55e, #16a34a);
}

.util-fill.warn {
  background: linear-gradient(90deg, #f59e0b, #d97706);
}

.util-fill.danger {
  background: linear-gradient(90deg, #ef4444, #dc2626);
}

.util-text {
  min-width: 54px;
  text-align: right;
  color: #334155;
  font-weight: 1000;
  font-variant-numeric: tabular-nums;
}

.tfoot td {
  position: sticky;
  bottom: 0;
  z-index: 1;
  background: #f8fafc;
  color: #0f172a;
  font-weight: 1000;
  border-top: 1px solid #e2e8f0;
}

.tfoot-label {
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #64748b;
  font-size: 12px;
}

.empty {
  text-align: center;
  padding: 52px 20px;
  color: #94a3b8;
  font-weight: 800;
}

.loading-content {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
}

.loading-spinner {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 2px solid #dbe4ef;
  border-top-color: #2563eb;
  animation: spin 1s linear infinite;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.72);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 18px;
  z-index: 1000;
}

.modal-container {
  width: 100%;
  max-width: 680px;
  max-height: 92vh;
  overflow: hidden;
  border-radius: 22px;
  background: #fff;
  border: 1px solid #e2e8f0;
  box-shadow: 0 32px 64px rgba(15, 23, 42, 0.24);
  display: flex;
  flex-direction: column;
}

.modal-header {
  padding: 20px 22px;
  border-bottom: 1px solid #e2e8f0;
  background: linear-gradient(180deg, #ffffff, #f8fafc);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
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
  width: 40px;
  height: 40px;
  border-radius: 12px;
  border: 1px solid #dbe4ef;
  background: #fff;
  color: #64748b;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: 0.2s ease;
}

.close-btn:hover {
  background: #f8fafc;
  color: #334155;
}

.modal-body {
  padding: 22px;
  overflow: auto;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.field-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.full-width {
  grid-column: 1 / -1;
}

.field-label {
  font-size: 13px;
  font-weight: 900;
  color: #334155;
}

.required {
  color: #ef4444;
}

.field-input {
  width: 100%;
  min-height: 46px;
  padding: 12px 14px;
  border-radius: 14px;
  border: 1px solid #dbe4ef;
  background: #fff;
  color: #0f172a;
  font-size: 14px;
  font-weight: 800;
  outline: none;
  transition: 0.2s ease;
}

.field-input:focus {
  border-color: rgba(37, 99, 235, 0.6);
  box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
}

.money-input {
  letter-spacing: 0.02em;
}

.readonly-money:disabled,
.readonly-money[readonly] {
  background: #f8fafc;
  color: #475569;
  cursor: not-allowed;
}

.field-hint {
  margin: 0;
  font-size: 12px;
  font-weight: 700;
  color: #64748b;
}

.info-box {
  padding: 12px 14px;
  border-radius: 14px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
}

.info-row {
  display: flex;
  gap: 8px;
  font-size: 12px;
  font-weight: 800;
  color: #475569;
  line-height: 1.5;
}

.modal-footer {
  padding: 18px 22px;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.spin {
  animation: spin 1s linear infinite;
}

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.22s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .modal-container,
.modal-leave-active .modal-container {
  transition: transform 0.22s ease, opacity 0.22s ease;
}

.modal-enter-from .modal-container,
.modal-leave-to .modal-container {
  opacity: 0;
  transform: translateY(-14px) scale(0.98);
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 992px) {
  .main-content {
    padding: 16px;
  }

  .page-header {
    align-items: stretch;
  }

  .controls {
    width: 100%;
  }
}

@media (max-width: 768px) {
  .controls,
  .actions {
    width: 100%;
  }

  .control,
  .control.search,
  .control.search input,
  .actions .btn {
    width: 100%;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .table-head {
    align-items: flex-start;
    flex-direction: column;
  }

  .actions-cell {
    min-width: 180px;
  }
}
</style>