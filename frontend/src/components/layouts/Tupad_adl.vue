<template>
  <div class="main-content">
    <!-- Header + mini summary -->
    <div class="summary-section">
      <div class="section-header">
        <div class="title-wrap">
          <h2>TUPAD ADLs</h2>
          <span class="division-name">
            {{ divisionDetails.division || divisionDetails.name || "Division" }} · {{ selectedYear }}
          </span>
        </div>

        <div class="header-right">
          <div class="mini-metrics">
            <div class="mini">
              <span class="mini-label">Records</span>
              <span class="mini-value">{{ adlDetails.length }}</span>
            </div>
            <div class="mini">
              <span class="mini-label">Spent</span>
              <span class="mini-value">{{ formatMoney(totalAmount - remainingBalance) }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="cards-grid">
        <div class="stat-card total-beneficiaries">
          <div class="stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
          </div>
          <div class="stat-content">
            <h3>{{ totalBeneficiaries.toLocaleString() }}</h3>
            <p>Total Beneficiaries</p>
          </div>
        </div>

        <div class="stat-card total-amount">
          <div class="stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="1" x2="12" y2="23"></line>
              <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
          </div>
          <div class="stat-content">
            <h3>{{ formatMoney(totalAmount) }}</h3>
            <p>Total Amount</p>
          </div>
        </div>

        <div class="stat-card remaining-balance">
          <div class="stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
              <line x1="1" y1="10" x2="23" y2="10"></line>
            </svg>
          </div>
          <div class="stat-content">
            <h3>{{ formatMoney(remainingBalance) }}</h3>
            <p>Remaining Balance</p>
          </div>
        </div>

        <div class="stat-card completion-rate">
          <div class="stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
            </svg>
          </div>
          <div class="stat-content">
            <h3>{{ completionRate }}%</h3>
            <p>Utilization</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="table-section">
      <div class="table-header">
        <div class="table-title">
          <h2>ADL Details</h2>
          <p class="table-sub">ADLs allocated to this division for the selected year.</p>
        </div>

        <div class="header-actions">
          <button class="btn btn-primary" @click="openAddFromMasterModal">
            <span class="btn-icon">+</span>
            Add ADL (from Master)
          </button>
        </div>
      </div>

      <div class="table-wrapper">
        <table class="adl-table">
          <thead>
            <tr>
              <th>ADL</th>
              <th>Sponsor</th>
              <th class="numeric">Total LGU</th>
              <th class="numeric">Beneficiaries</th>
              <th class="numeric">Amount</th>
              <th class="numeric">Balance</th>
              <th>Progress</th>
              <th class="actions-head">Actions</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="adl in adlDetails" :key="adl.id" class="adl-row">
              <td class="adl-name">
                <div class="adl-cell">
                  <div class="adl-badge">{{ (adl.adl || "A").slice(0, 1).toUpperCase() }}</div>
                  <div class="adl-meta">
                    <div class="adl-title">{{ adl.adl }}</div>
                    <div class="adl-sub">{{ divisionDetails.division || divisionDetails.name || "Division" }}</div>
                  </div>
                </div>
              </td>

              <td class="sponsor-name">{{ adl.sponsor || "—" }}</td>

              <td class="numeric">
                <span class="num-pill">{{ formatNumber(adl.total_lgu || 0) }}</span>
              </td>

              <td class="numeric beneficiaries-cell">
                <div class="beneficiary-value">{{ formatNumber(adl._beneficiaries || 0) }}</div>
              </td>

              <td class="numeric">{{ formatMoney(adl.amount) }}</td>

              <td class="numeric">
                <span :class="['balance-pill', Number(adl.balance) <= 0 ? 'zero' : 'ok']">
                  {{ formatMoney(adl.balance) }}
                </span>
              </td>

              <td class="progress-cell">
                <div class="progress-container">
                  <div class="progress-bar" :title="`${adl.percentage || 0}%`">
                    <div class="progress-fill" :style="{ width: (adl.percentage || 0) + '%' }"></div>
                  </div>
                  <span class="progress-text">{{ adl.percentage || 0 }}%</span>
                </div>
              </td>

              <td class="actions-cell">
                <!-- View -->
                <button class="btn-icon-btn" @click="viewBreakdown(adl)" title="View Breakdown">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                  </svg>
                </button>

                <!-- Edit -->
                <button class="btn-icon-btn" @click="openEditAdlModal(adl)" title="Edit Allocation">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 20h9"></path>
                    <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                  </svg>
                </button>

                <!-- Delete -->
                <button class="btn-icon-btn btn-delete" @click="deleteAdl(adl)" title="Delete">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                  </svg>
                </button>
              </td>
            </tr>

            <tr v-if="adlDetails.length === 0">
              <td colspan="8" class="empty-state">
                <div class="empty-content">
                  <p>No ADL records found for {{ selectedYear }}.</p>
                  <button class="btn btn-primary" @click="openAddFromMasterModal">Add ADL from Master</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
// ⚠️ All script code remains exactly as provided by the user – unchanged
import { ref, onMounted, computed } from "vue";
import axios from "axios";
import { useRoute, useRouter } from "vue-router";
import Swal from "sweetalert2";

import { inject } from "vue";

const API_BASE = inject("API_BASE");
const route = useRoute();
const router = useRouter();

const divisionDetails = ref({});
const adlDetails = ref([]);
const masters = ref([]);

const selectedYear = computed(() => Number(route.query.year) || new Date().getFullYear());
const divisionId = computed(() => route.query.division_id);

const formatNumber = (n) => {
  const x = Number(n || 0);
  return Number.isFinite(x) ? Math.trunc(x).toLocaleString() : "0";
};

const formatMoney = (value) =>
  new Intl.NumberFormat("en-PH", { style: "currency", currency: "PHP" }).format(Number(value || 0));

const utilizationRate = (amount, balance) => {
  const a = Number(amount || 0);
  const b = Number(balance || 0);
  if (a <= 0) return 0;
  if (b <= 0) return 100;
  return Math.max(0, Math.min(100, Math.round(((a - b) / a) * 100)));
};

const totalBeneficiaries = computed(() =>
  adlDetails.value.reduce((sum, adl) => sum + Number(adl._beneficiaries || 0), 0)
);

const totalAmount = computed(() => adlDetails.value.reduce((sum, adl) => sum + Number(adl.amount || 0), 0));

const remainingBalance = computed(() =>
  adlDetails.value.reduce((sum, adl) => sum + Number(adl.balance || 0), 0)
);

const completionRate = computed(() => utilizationRate(totalAmount.value, remainingBalance.value));

const fetchDivisionAndAdls = async () => {
  if (!divisionId.value) return;

  try {
    const year = selectedYear.value;

    const [divisionResp, adlResp, masterResp] = await Promise.all([
      axios.get(`${API_BASE}/divisions/${divisionId.value}?year=${year}`),
      axios.get(`${API_BASE}/tupad_adl_details?division_id=${divisionId.value}&year=${year}`),
      axios.get(`${API_BASE}/tupad_adl_masters`),
    ]);

    divisionDetails.value = divisionResp.data || {};
    masters.value = Array.isArray(masterResp.data) ? masterResp.data : [];

    const adlList = Array.isArray(adlResp.data) ? adlResp.data : [];
    const adlIds = adlList.map((a) => Number(a.id)).filter(Boolean);

    // breakdowns -> map beneficiaries and LGU count by adl_detail_id
    const breakdownResp = await axios.get(`${API_BASE}/tupad_adl_breakdown`);
    const allBreakdowns = Array.isArray(breakdownResp.data) ? breakdownResp.data : [];

    const breakdowns = allBreakdowns.filter((b) => adlIds.includes(Number(b.adl_detail_id)));

    const benMap = {};
    const lguCountMap = {};

    breakdowns.forEach((b) => {
      const did = Number(b.adl_detail_id || 0);
      if (!did) return;

      const ben = Number(b.beneficiaries || 0) || 0;
      benMap[did] = (benMap[did] || 0) + ben;

      lguCountMap[did] = (lguCountMap[did] || 0) + 1;
    });

    adlDetails.value = adlList.map((adl) => {
      const id = Number(adl.id);
      const beneficiaries = benMap[id] || 0;
      const lguCount = lguCountMap[id] || 0;
      const pct = utilizationRate(adl.amount, adl.balance);

      return { ...adl, _beneficiaries: beneficiaries, total_lgu: lguCount, percentage: pct };
    });
  } catch (err) {
    console.error(err);
    Swal.fire("Error", err.response?.data?.message || "Failed to load Division/ADL data.", "error");
  }
};

const swalBase = {
  title: "",
  showCancelButton: true,
  confirmButtonText: "Save",
  cancelButtonText: "Cancel",
  buttonsStyling: false,
  customClass: {
    popup: "swal-popup",
    confirmButton: "swal-btn swal-btn-primary",
    cancelButton: "swal-btn swal-btn-ghost",
  },
  focusConfirm: false,
};

/** ----------- CREATE (Add ADL from Master) ----------- */
const openAddFromMasterModal = async () => {
  const existingMasterIds = new Set(adlDetails.value.map(a => Number(a.adl_master_id)));
const availableMasters = masters.value.filter(m => !existingMasterIds.has(Number(m.id)));

if (!availableMasters.length) {
  Swal.fire("Info", "All Master ADLs are already added for this Division/Year.", "info");
  return;
}
  if (!masters.value.length) {
    Swal.fire("Info", "Master list is empty. Add masters first.", "info");
    return;
    
  }

  const optionsHtml = masters.value
    .map((m) => {
      const name = m.adl || m.name || `ADL ${m.id}`;
      const sponsor = m.sponsor ? ` — ${m.sponsor}` : "";
      const avail = Number(m.balance ?? m.total_amount ?? 0);
      return `<option value="${m.id}">${escapeHtml(name)}${escapeHtml(sponsor)} (Avail: ${formatMoney(avail)})</option>`;
    })
    .join("");

  const html = `
    <div class="swal-wrap">
      <div class="swal-top">
        <div class="swal-title">Add ADL (from Master)</div>
        <div class="swal-sub">Creates an ADL Detail for this Division & Year.</div>
      </div>

      <div class="swal-grid">
        <div class="swal-field full">
          <label class="swal-label">Master ADL <span class="req">*</span></label>
          <select id="master_id" class="swal-input">${optionsHtml}</select>
          <div class="swal-hint">Choose which master fund to allocate from.</div>
        </div>

        <div class="swal-field full">
          <label class="swal-label">Amount to Allocate <span class="req">*</span></label>
          <input id="alloc_amount" type="number" class="swal-input" min="0.01" step="0.01" placeholder="e.g., 50000" />
          <div class="swal-note">
            <b>Security:</b> Amount cannot exceed Master available balance.
          </div>
        </div>
      </div>
    </div>
  `;

  const res = await Swal.fire({
    ...swalBase,
    confirmButtonText: "Save",
    html,
    preConfirm: () => {
      const masterId = Number(document.getElementById("master_id")?.value || 0);
      const amountNum = Number(document.getElementById("alloc_amount")?.value || 0);

      if (!masterId) return Swal.showValidationMessage("Please select a master ADL.");
      if (!Number.isFinite(amountNum) || amountNum <= 0) return Swal.showValidationMessage("Amount must be greater than 0.");

      const master = masters.value.find((x) => Number(x.id) === Number(masterId));
      const available = Number(master?.balance ?? master?.total_amount ?? 0);

      if (amountNum > available)
        return Swal.showValidationMessage(`Amount exceeds master available balance (${formatMoney(available)}).`);

      return { masterId, amountNum };
    },
  });

  if (!res.isConfirmed) return;

  const { masterId, amountNum } = res.value;
  const year = selectedYear.value;

  try {
    await axios.post(`${API_BASE}/tupad_adl_details`, {
      division_id: Number(divisionId.value),
      adl_master_id: Number(masterId),
      amount: Number(amountNum),
      balance: Number(amountNum),
      total_lgu: 0,
      percentage: 0,
      year,
    });

    Swal.fire("Success", "ADL added to this division.", "success");
    await fetchDivisionAndAdls();
  } catch (err) {
    console.error(err);
    Swal.fire("Error", err.response?.data?.message || err.message, "error");
  }
};

/** ----------- UPDATE (Edit ADL Allocation) ----------- */
const openEditAdlModal = async (adl) => {
  // find master for this detail
  const master = masters.value.find((m) => Number(m.id) === Number(adl.adl_master_id));
  const masterAvailable = Number(master?.balance ?? master?.total_amount ?? 0);

  const oldAmount = Number(adl.amount || 0);
  const oldBalance = Number(adl.balance || 0);
  const alreadySpent = Math.max(0, oldAmount - oldBalance);

  // maximum allowed new amount:
  // you can set higher, but the extra must be <= masterAvailable (backend will enforce)
  const maxAllowed = oldAmount + masterAvailable;

  const html = `
    <div class="swal-wrap">
      <div class="swal-top">
        <div class="swal-title">Edit ADL Allocation</div>
        <div class="swal-sub">
          Already spent: <b>${formatMoney(alreadySpent)}</b> · Master avail: <b>${formatMoney(masterAvailable)}</b>
        </div>
      </div>

      <div class="swal-grid">
        <div class="swal-field">
          <label class="swal-label">ADL</label>
          <input id="adl_name" class="swal-input" value="${escapeHtml(adl.adl || "")}" placeholder="ADL name" />
        </div>

        <div class="swal-field">
          <label class="swal-label">Sponsor</label>
          <input id="adl_sponsor" class="swal-input" value="${escapeHtml(adl.sponsor || "")}" placeholder="Sponsor" />
        </div>

        <div class="swal-field full">
          <label class="swal-label">New Amount <span class="req">*</span></label>
          <input id="new_amount" type="number" class="swal-input" min="0.01" step="0.01" value="${Number(oldAmount)}" />
          <div class="swal-hint">
            Min: ${formatMoney(alreadySpent)} (cannot be lower than spent) · Max: ${formatMoney(maxAllowed)}
          </div>
          <div class="swal-note">
            <b>Rule:</b> If you increase allocation, the additional amount will be deducted from Master balance.
            If you decrease, unspent will be returned to Master.
          </div>
        </div>
      </div>
    </div>
  `;

  const res = await Swal.fire({
    ...swalBase,
    confirmButtonText: "Update",
    html,
    preConfirm: () => {
      const adlName = document.getElementById("adl_name")?.value?.trim() || "";
      const sponsor = document.getElementById("adl_sponsor")?.value?.trim() || "";
      const newAmount = Number(document.getElementById("new_amount")?.value || 0);

      if (!Number.isFinite(newAmount) || newAmount <= 0) return Swal.showValidationMessage("Amount must be greater than 0.");

      if (newAmount < alreadySpent)
        return Swal.showValidationMessage(`New amount cannot be lower than already spent (${formatMoney(alreadySpent)}).`);

      if (newAmount > maxAllowed)
        return Swal.showValidationMessage(`New amount cannot be greater than allowed (${formatMoney(maxAllowed)}).`);

      return { adlName, sponsor, newAmount };
    },
  });

  if (!res.isConfirmed) return;

  try {
    await axios.put(`${API_BASE}/tupad_adl_details/${adl.id}`, {
      adl: res.value.adlName,
      sponsor: res.value.sponsor,
      amount: Number(res.value.newAmount),
      total_lgu: Number(adl.total_lgu || 0),
    });

    Swal.fire("Updated", "ADL allocation updated.", "success");
    await fetchDivisionAndAdls();
  } catch (err) {
    console.error(err);
    Swal.fire("Error", err.response?.data?.message || err.message, "error");
  }
};

/** ----------- DELETE ----------- */
const deleteAdl = (adlObj) => {
  Swal.fire({
    title: "Delete this ADL?",
    text: "This cannot be undone.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, delete",
    confirmButtonColor: "#dc3545",
  }).then(async (r) => {
    if (!r.isConfirmed) return;

    try {
      await axios.delete(`${API_BASE}/tupad_adl_details/${adlObj.id}`);
      Swal.fire("Deleted", "ADL deleted.", "success");
      await fetchDivisionAndAdls();
    } catch (err) {
      console.error(err);
      Swal.fire("Error", err.response?.data?.message || err.message, "error");
    }
  });
};

/** ----------- NAV ----------- */
const viewBreakdown = (adlObj) => {
  router.push({ path: "/breakdown", query: { adl_detail_id: adlObj.id } });
};

function escapeHtml(str) {
  return String(str || "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

onMounted(fetchDivisionAndAdls);
</script>

<style scoped>
/* =========================
   Base layout
   ========================= */
.main-content {
  padding: 24px;
  max-width: 1400px;
  margin: 0 auto;
  font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
  color: #0f172a;
  background: #f9fafb;
  border-radius: 20px;
}

/* =========================
   Summary header
   ========================= */
.summary-section {
  margin-bottom: 28px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.title-wrap {
  display: flex;
  align-items: baseline;
  gap: 12px;
  flex-wrap: wrap;
}

.section-header h2 {
  font-size: 28px;
  margin: 0;
  color: #0f172a;
  font-weight: 800;
  letter-spacing: -0.02em;
  background: linear-gradient(135deg, #1e293b, #0f172a);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
}

.division-name {
  color: #475569;
  font-size: 13px;
  font-weight: 600;
  padding: 5px 12px;
  border-radius: 40px;
  background: #fff;
  border: 1px solid #e2e8f0;
  backdrop-filter: blur(4px);
  box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}

.header-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.mini-metrics {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.mini {
  display: flex;
  align-items: baseline;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 40px;
  background: #ffffffcc;
  backdrop-filter: blur(4px);
  border: 1px solid #eef2f7;
  font-size: 13px;
}

.mini-label {
  color: #64748b;
  font-weight: 600;
}

.mini-value {
  color: #0f172a;
  font-weight: 800;
}

/* =========================
   Cards
   ========================= */
.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 18px;
}

.stat-card {
  background: #fff;
  border-radius: 24px;
  padding: 20px;
  display: flex;
  gap: 16px;
  align-items: center;
  border: 1px solid #eef2f7;
  box-shadow: 0 8px 20px rgba(2,6,23,0.04);
  transition: all 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 16px 28px rgba(2,6,23,0.08);
  border-color: #e2e8f0;
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: 20px;
  display: grid;
  place-items: center;
  background: #f1f5f9;
  color: #3b82f6;
  flex-shrink: 0;
  transition: all 0.2s;
}

.stat-card:hover .stat-icon {
  transform: scale(1.02);
}

.stat-icon svg {
  width: 28px;
  height: 28px;
  stroke-width: 1.8;
}

.stat-content h3 {
  margin: 0;
  font-size: 28px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.1;
  letter-spacing: -0.01em;
}

.stat-content p {
  margin: 6px 0 0;
  color: #64748b;
  font-weight: 600;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.total-beneficiaries .stat-icon { background: #fff7ed; color: #f59e0b; }
.total-amount .stat-icon { background: #f0fdf4; color: #10b981; }
.remaining-balance .stat-icon { background: #eff6ff; color: #3b82f6; }
.completion-rate .stat-icon { background: #fef2f2; color: #ef4444; }

/* =========================
   Table container
   ========================= */
.table-section {
  background: #fff;
  border-radius: 24px;
  border: 1px solid #eef2f7;
  box-shadow: 0 12px 32px rgba(2,6,23,0.04);
  overflow: hidden;
  backdrop-filter: blur(2px);
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #eef2f7;
  gap: 16px;
  flex-wrap: wrap;
  background: #fefefe;
}

.table-title h2 {
  font-size: 20px;
  margin: 0;
  color: #0f172a;
  font-weight: 800;
  letter-spacing: -0.01em;
}

.table-sub {
  margin: 6px 0 0;
  color: #64748b;
  font-weight: 500;
  font-size: 13px;
}

.header-actions {
  display: flex;
  gap: 12px;
  align-items: center;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border-radius: 60px;
  border: none;
  cursor: pointer;
  font-weight: 700;
  font-size: 14px;
  letter-spacing: 0.2px;
  transition: all 0.2s ease;
  background: #fff;
  color: #1e293b;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}

.btn-primary {
  background: linear-gradient(105deg, #2563eb, #1d4ed8);
  color: white;
  border: none;
  box-shadow: 0 8px 20px rgba(37,99,235,0.2);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 28px rgba(37,99,235,0.25);
  background: linear-gradient(105deg, #2c6ef0, #1e4fd0);
}

.btn-icon {
  font-size: 18px;
  font-weight: 700;
  line-height: 1;
}

/* Table */
.table-wrapper {
  overflow-x: auto;
}

.adl-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 1000px;
}

.adl-table thead th {
  position: sticky;
  top: 0;
  z-index: 1;
  background: #fcfdff;
  color: #475569;
  padding: 16px 20px;
  text-align: left;
  font-weight: 700;
  font-size: 13px;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  border-bottom: 1px solid #eef2f7;
  backdrop-filter: blur(4px);
}

.adl-table tbody td {
  padding: 16px 20px;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
  color: #1e293b;
  background: #fff;
  transition: background 0.1s ease;
}

.adl-row:hover td {
  background: #fafcff;
}

.numeric {
  text-align: right;
  font-weight: 600;
  font-feature-settings: "tnum";
}

.actions-head {
  text-align: center;
  width: 160px;
}

.adl-cell {
  display: flex;
  align-items: center;
  gap: 14px;
}

.adl-badge {
  width: 40px;
  height: 40px;
  border-radius: 14px;
  display: grid;
  place-items: center;
  font-weight: 800;
  font-size: 18px;
  color: #2563eb;
  background: #eff6ff;
  border: 1px solid #d9e8ff;
  transition: all 0.15s;
}

.adl-meta {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.adl-title {
  font-weight: 800;
  color: #0f172a;
  line-height: 1.2;
}

.adl-sub {
  font-size: 12px;
  color: #64748b;
  font-weight: 500;
}

.sponsor-name {
  color: #475569;
  font-weight: 600;
}

.num-pill {
  display: inline-flex;
  align-items: center;
  justify-content: flex-end;
  padding: 5px 10px;
  border-radius: 40px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  min-width: 70px;
  font-weight: 700;
  font-size: 13px;
}

.balance-pill {
  display: inline-flex;
  justify-content: flex-end;
  padding: 5px 12px;
  border-radius: 40px;
  font-weight: 800;
  border: 1px solid;
  min-width: 140px;
  font-size: 13px;
}

.balance-pill.ok {
  background: rgba(16,185,129,0.08);
  border-color: rgba(16,185,129,0.25);
  color: #0a7c4b;
}

.balance-pill.zero {
  background: rgba(239,68,68,0.08);
  border-color: rgba(239,68,68,0.25);
  color: #b91c1c;
}

/* Progress */
.progress-cell {
  min-width: 210px;
}

.progress-container {
  display: flex;
  align-items: center;
  gap: 12px;
}

.progress-bar {
  flex: 1;
  height: 8px;
  background: #eef2f7;
  border-radius: 40px;
  overflow: hidden;
  border: 1px solid #e4e9f2;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #2563eb, #3b82f6);
  width: 0%;
  transition: width 0.4s cubic-bezier(0.2, 0.9, 0.4, 1.1);
  border-radius: 40px;
}

.progress-text {
  min-width: 48px;
  text-align: right;
  font-weight: 700;
  color: #334155;
  font-size: 13px;
}

/* Actions */
.actions-cell {
  display: flex;
  gap: 8px;
  justify-content: center;
  align-items: center;
}

.btn-icon-btn {
  width: 40px;
  height: 40px;
  border-radius: 14px;
  border: 1px solid #eef2f7;
  background: #fff;
  display: grid;
  place-items: center;
  cursor: pointer;
  transition: all 0.15s ease;
  box-shadow: 0 2px 6px rgba(0,0,0,0.02);
}

.btn-icon-btn:hover {
  transform: translateY(-2px);
  background: #f8fafc;
  border-color: #d1dbe9;
  box-shadow: 0 6px 12px rgba(2,6,23,0.06);
}

.btn-icon-btn svg {
  width: 18px;
  height: 18px;
  color: #4b5565;
  transition: color 0.15s;
}

.btn-delete:hover {
  background: rgba(239,68,68,0.08);
  border-color: rgba(239,68,68,0.3);
}
.btn-delete:hover svg {
  color: #dc2626;
}

.empty-state {
  text-align: center;
  padding: 56px 20px;
}

.empty-content {
  max-width: 400px;
  margin: 0 auto;
}

.empty-content p {
  color: #64748b;
  margin-bottom: 20px;
  font-weight: 500;
}

/* SweetAlert2 custom modal UI (enhanced) */
:global(.swal-popup) {
  width: 620px !important;
  border-radius: 32px !important;
  padding: 24px !important;
  font-family: 'Inter', system-ui !important;
  backdrop-filter: blur(2px);
}

:global(.swal2-html-container) {
  margin: 0 !important;
  padding: 0 !important;
}

:global(.swal2-actions) {
  margin-top: 24px !important;
  gap: 12px !important;
}

:global(.swal2-validation-message) {
  border-radius: 60px !important;
  font-weight: 600 !important;
  background: #fef2f2 !important;
  color: #b91c1c !important;
}

:global(.swal-btn) {
  border-radius: 60px !important;
  padding: 12px 24px !important;
  font-weight: 700 !important;
  font-size: 14px !important;
  border: 1px solid transparent !important;
  cursor: pointer !important;
  transition: all 0.15s ease !important;
}

:global(.swal-btn-primary) {
  background: linear-gradient(105deg, #2563eb, #1d4ed8) !important;
  color: #fff !important;
  box-shadow: 0 8px 20px rgba(37,99,235,0.2) !important;
}
:global(.swal-btn-primary):hover {
  transform: translateY(-2px) !important;
  box-shadow: 0 12px 24px rgba(37,99,235,0.25) !important;
}

:global(.swal-btn-ghost) {
  background: #fff !important;
  color: #1e293b !important;
  border-color: #e2e8f0 !important;
}
:global(.swal-btn-ghost):hover {
  background: #f8fafc !important;
  border-color: #cbd5e1 !important;
}

:global(.swal-wrap) {
  text-align: left;
}

:global(.swal-top) {
  padding: 0 0 12px;
  margin-bottom: 12px;
}

:global(.swal-title) {
  font-size: 26px;
  font-weight: 800;
  letter-spacing: -0.02em;
  color: #0f172a;
  margin: 0;
}

:global(.swal-sub) {
  margin-top: 8px;
  font-size: 14px;
  font-weight: 500;
  color: #64748b;
}

:global(.swal-grid) {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  padding: 4px;
}

:global(.swal-field.full) {
  grid-column: 1 / -1;
}

:global(.swal-label) {
  display: block;
  font-size: 13px;
  font-weight: 700;
  color: #334155;
  margin-bottom: 8px;
}

:global(.req) {
  color: #ef4444;
  margin-left: 2px;
}

:global(.swal-input) {
  width: 100%;
  height: 50px;
  border-radius: 20px;
  border: 1px solid #e2e8f0;
  padding: 12px 16px;
  font-weight: 500;
  font-size: 14px;
  outline: none;
  background: #fff;
  transition: all 0.15s;
  box-sizing: border-box;
}

:global(.swal-input:focus) {
  border-color: #3b82f6;
  box-shadow: 0 0 0 4px rgba(59,130,246,0.15);
}

:global(.swal-hint) {
  margin-top: 8px;
  font-size: 12px;
  font-weight: 500;
  color: #64748b;
}

:global(.swal-note) {
  margin-top: 12px;
  font-size: 12px;
  font-weight: 500;
  color: #475569;
  background: #f8fafc;
  border: 1px solid #eef2f7;
  border-radius: 20px;
  padding: 12px 16px;
}

/* Responsive */
@media (max-width: 768px) {
  .main-content {
    padding: 16px;
  }
  .section-header h2 {
    font-size: 22px;
  }
  .cards-grid {
    grid-template-columns: 1fr;
  }
  .stat-card {
    padding: 16px;
  }
  .stat-content h3 {
    font-size: 24px;
  }
  .table-header {
    flex-direction: column;
    align-items: stretch;
    text-align: left;
  }
  .header-actions {
    justify-content: flex-start;
  }
  .btn {
    padding: 8px 14px;
    font-size: 13px;
  }
  .adl-table thead th,
  .adl-table tbody td {
    padding: 12px 14px;
  }
  .balance-pill {
    min-width: 100px;
  }
  :global(.swal-popup) {
    width: 90% !important;
    padding: 20px !important;
  }
}
</style>