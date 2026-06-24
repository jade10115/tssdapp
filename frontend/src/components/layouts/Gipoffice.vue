<template>
  <div class="gip-office-wrapper">
    <div class="header">
      <div class="header-left">
        <div class="header-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
            <polyline points="9 22 9 12 15 12 15 22"/>
          </svg>
        </div>
        <div>
          <h1>GIP Office Assignment</h1>
          <p>Manage GIP allocation across offices</p>
        </div>
      </div>
      <button class="btn-add" @click="openAddOfficeModal">
        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
        Add Office
      </button>
    </div>

    <div class="top-row">
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon" style="background:#eff6ff; color:#2563eb;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
            </svg>
          </div>
          <div class="stat-info">
            <div class="stat-value">{{ yearlyStats.total }}</div>
            <div class="stat-label">Total GIPs</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:#dbeafe; color:#1d4ed8;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/>
            </svg>
          </div>
          <div class="stat-info">
            <div class="stat-value">{{ yearlyStats.male }}</div>
            <div class="stat-label">Male</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:#fce7f3; color:#be185d;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/>
            </svg>
          </div>
          <div class="stat-info">
            <div class="stat-value">{{ yearlyStats.female }}</div>
            <div class="stat-label">Female</div>
          </div>
        </div>
      </div>
      <div class="year-filter">
        <label>Select Year</label>
        <select v-model="selectedYear" @change="refreshAll" class="year-select">
          <option v-for="yr in availableYears" :key="yr" :value="yr">{{ yr }}</option>
        </select>
      </div>
    </div>

    <div class="table-card">
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Office Name</th>
              <th class="center">Total GIPs</th>
              <th class="center">Male</th>
              <th class="center">Female</th>
              <th class="actions">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading"><td colspan="5" class="empty">Loading...</td></tr>
            <tr v-else-if="!offices.length"><td colspan="5" class="empty">No offices available.</td></tr>
            <tr v-else v-for="office in offices" :key="office.id" class="table-row">
              <td>
                <div class="office-name">
                  <div class="office-avatar">{{ office.name.charAt(0).toUpperCase() }}</div>
                  <span>{{ office.name }}</span>
                </div>
              </td>
              <td class="center"><span class="count-badge">{{ office.gip_employees_count ?? office.employees_count ?? 0 }}</span></td>
              <td class="center">{{ office.male_count ?? 0 }}</td>
              <td class="center">{{ office.female_count ?? 0 }}</td>
              <td class="actions">
                <button class="btn-icon manage" @click="openManageModal(office)" title="Manage GIPs">
                  <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
                </button>
                <button class="btn-icon edit" @click="editOffice(office)" title="Edit">
                  <svg viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
                </button>
                <button class="btn-icon delete" @click="confirmDeleteOffice(office)" title="Delete">
                  <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showManageModal" class="modal-overlay" @click.self="closeManageModal">
          <div class="modal">
            <div class="modal-header">
              <h3>{{ selectedOffice?.name }} — GIPs ({{ selectedYear }})</h3>
              <button class="modal-close" @click="closeManageModal">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
              </button>
            </div>
            <div class="modal-body">
              <div class="gip-list">
                <div v-for="gip in selectedOfficeGips" :key="gip.id" class="gip-item">
                  <div class="gip-avatar" :class="gip.gender === 'Female' ? 'female' : 'male'">
                    {{ (gip.first_name?.[0] || '') + (gip.family_name?.[0] || '') }}
                  </div>
                  <div class="gip-info">
                    <strong>{{ gip.full_name ?? `${gip.first_name} ${gip.family_name}` }}</strong>
                    <span class="gip-status" :class="statusClass(gip.status)">{{ gip.status }}</span>
                  </div>
                  <button class="btn-unassign" @click="removeGip(gip)" title="Unassign">✕</button>
                </div>
                <div v-if="!selectedOfficeGips.length" class="empty">No GIPs assigned in {{ selectedYear }}.</div>
              </div>
              <button class="btn-assign" @click="openAssignModal(selectedOffice)">+ Assign GIP</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showAssignModal" class="modal-overlay" @click.self="closeAssignModal">
          <div class="modal">
            <div class="modal-header">
              <h3>Assign GIP to {{ selectedOffice?.name }}</h3>
              <button class="modal-close" @click="closeAssignModal">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
              </button>
            </div>
            <div class="modal-body">
              <div class="search-box">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                <input v-model="searchQuery" placeholder="Search unassigned GIP..." />
              </div>
              <div class="gip-list selectable">
                <div v-for="gip in filteredUnassigned" :key="gip.id" class="gip-item" @click="assignGip(gip)">
                  <div class="gip-avatar" :class="gip.gender === 'Female' ? 'female' : 'male'">
                    {{ (gip.first_name?.[0] || '') + (gip.family_name?.[0] || '') }}
                  </div>
                  <span>{{ gip.full_name ?? `${gip.first_name} ${gip.family_name}` }}</span>
                </div>
                <div v-if="!filteredUnassigned.length" class="empty">No unassigned GIPs found for {{ selectedYear }}.</div>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showOfficeModal" class="modal-overlay" @click.self="closeOfficeModal">
          <div class="modal">
            <div class="modal-header">
              <h3>{{ editingOffice ? 'Edit Office' : 'New Office' }}</h3>
              <button class="modal-close" @click="closeOfficeModal">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
              </button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Office Name *</label>
                <input v-model="officeForm.name" type="text" class="form-input" required />
              </div>
              <div class="form-group">
                <label>Code (optional)</label>
                <input v-model="officeForm.code" type="text" class="form-input" />
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn-secondary" @click="closeOfficeModal">Cancel</button>
              <button class="btn-primary" @click="saveOffice">{{ editingOffice ? 'Update' : 'Create' }}</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, inject } from 'vue'
import Swal from 'sweetalert2'
import axios from '../../axios.js'

// ✅ Injected parent contextual key base variable
const API_BASE = inject('API_BASE')

const offices = ref([])
const totalOffices = ref(0)
const availableYears = ref([])
const selectedYear = ref(new Date().getFullYear())
const loading = ref(false)
const yearlyStats = reactive({ total: 0, male: 0, female: 0 })

// Modals
const showManageModal = ref(false)
const showAssignModal = ref(false)
const showOfficeModal = ref(false)
const editingOffice = ref(false)
const selectedOffice = ref(null)
const selectedOfficeGips = ref([])
const searchQuery = ref('')
const unassignedGips = ref([])

const filteredUnassigned = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) return unassignedGips.value
  return unassignedGips.value.filter(g => 
    (g.full_name || `${g.first_name} ${g.family_name}`).toLowerCase().includes(q)
  )
})

const officeForm = ref({ name: '', code: '' })
const selectedOfficeForEdit = ref(null)

const statusClass = (status) => ({
  'Active': 'status-active',
  'Completed': 'status-completed',
  'Terminated': 'status-terminated',
  'Resigned': 'status-resigned'
}[status] || '')

const fetchAll = async () => {
  loading.value = true
  try {
    const [officesRes, statsRes, yearsRes] = await Promise.all([
      axios.get(`${API_BASE}/gip/offices`, { params: { year: selectedYear.value } }),
      axios.get(`${API_BASE}/gip/offices/stats`, { params: { year: selectedYear.value } }),
      axios.get(`${API_BASE}/gip/offices/available-years`)
    ])
    
    // Support handling structural response configurations array variants dynamically
    offices.value = officesRes.data.offices || officesRes.data || []
    totalOffices.value = officesRes.data.totalOffices || offices.value.length
    
    Object.assign(yearlyStats, statsRes.data)
    availableYears.value = yearsRes.data || []
    
    if (availableYears.value.length && !availableYears.value.includes(selectedYear.value)) {
      selectedYear.value = availableYears.value[0] || new Date().getFullYear()
    }
  } catch (e) {
    console.error(e)
    Swal.fire('Error', 'Failed to load metadata views.', 'error')
  } finally {
    loading.value = false
  }
}

const refreshAll = () => fetchAll()

const fetchUnassigned = async () => {
  try {
    const res = await axios.get(`${API_BASE}/gip/unassigned`, { params: { year: selectedYear.value } })
    unassignedGips.value = res.data || []
  } catch (e) { 
    console.error(e)
    Swal.fire('Error', 'Could not fetch unassigned GIP records.', 'error') 
  }
}

const openManageModal = async (office) => {
  selectedOffice.value = office
  try {
    const res = await axios.get(`${API_BASE}/gip/offices/${office.id}/gips`, { params: { year: selectedYear.value } })
    selectedOfficeGips.value = res.data || []
  } catch (e) { 
    console.error(e)
    Swal.fire('Error', 'Could not fetch assigned GIP listings.', 'error') 
  }
  showManageModal.value = true
}

const openAssignModal = (office) => {
  selectedOffice.value = office
  searchQuery.value = ''
  fetchUnassigned()
  showAssignModal.value = true
}

const assignGip = async (gip) => {
  try {
    await axios.post(`${API_BASE}/gip/assign`, { gip_employee_id: gip.id, office_id: selectedOffice.value.id })
    Swal.fire('Success', 'GIP allocated successfully!', 'success')
    await fetchAll()
    closeAssignModal()
    // Refresh manage window view state context seamlessly if stack nested
    if (showManageModal.value && selectedOffice.value) {
      const res = await axios.get(`${API_BASE}/gip/offices/${selectedOffice.value.id}/gips`, { params: { year: selectedYear.value } })
      selectedOfficeGips.value = res.data || []
    }
  } catch (e) { 
    console.error(e)
    Swal.fire('Error', 'Assignment failed transaction.', 'error') 
  }
}

const removeGip = async (gip) => {
  const result = await Swal.fire({
    title: 'Unassign GIP?',
    text: `Remove ${gip.full_name || `${gip.first_name} ${gip.family_name}`} from this office?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Yes, unassign',
    confirmButtonColor: '#ef4444',
  })
  if (!result.isConfirmed) return
  try {
    await axios.post(`${API_BASE}/gip/unassign`, { gip_employee_id: gip.id })
    Swal.fire('Success', 'GIP unassigned successfully.', 'success')
    await fetchAll()
    if (showManageModal.value) {
      const res = await axios.get(`${API_BASE}/gip/offices/${selectedOffice.value.id}/gips`, { params: { year: selectedYear.value } })
      selectedOfficeGips.value = res.data || []
    }
  } catch (e) { 
    console.error(e)
    Swal.fire('Error', 'Unassignment step failure encountered.', 'error') 
  }
}

const openAddOfficeModal = () => {
  editingOffice.value = false
  officeForm.value = { name: '', code: '' }
  showOfficeModal.value = true
}

const editOffice = (office) => {
  editingOffice.value = true
  selectedOfficeForEdit.value = office
  officeForm.value = { name: office.name, code: office.code || '' }
  showOfficeModal.value = true
}

const saveOffice = async () => {
  if (!officeForm.value.name.trim()) {
    Swal.fire('Validation', 'Office name field is required.', 'warning')
    return
  }
  try {
    if (editingOffice.value) {
      await axios.put(`${API_BASE}/offices/${selectedOfficeForEdit.value.id}`, officeForm.value)
      Swal.fire('Updated!', 'Office details changed.', 'success')
    } else {
      await axios.post(`${API_BASE}/offices`, officeForm.value)
      Swal.fire('Created!', 'Office profile added.', 'success')
    }
    closeOfficeModal()
    fetchAll()
  } catch (err) {
    console.error(err)
    Swal.fire('Error', err.response?.data?.message || 'Could not save office metadata configurations.', 'error')
  }
}

const confirmDeleteOffice = async (office) => {
  const result = await Swal.fire({
    title: 'Delete Office?',
    text: `Are you sure you want to drop "${office.name}" completely?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete',
    confirmButtonColor: '#dc2626',
  })
  if (!result.isConfirmed) return
  try {
    await axios.delete(`${API_BASE}/offices/${office.id}`)
    Swal.fire('Deleted!', 'Office node dropped.', 'success')
    fetchAll()
  } catch (e) {
    console.error(e)
    Swal.fire('Error', 'Action rejected. Verify references.', 'error')
  }
}

const closeManageModal = () => { showManageModal.value = false }
const closeAssignModal = () => { showAssignModal.value = false }
const closeOfficeModal = () => { showOfficeModal.value = false; selectedOfficeForEdit.value = null }

onMounted(refreshAll)
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

*{
  box-sizing:border-box;
}

.gip-office-wrapper{
  padding:1.5rem;
  max-width:1200px;
  margin:0 auto;
  font-family:'Inter',sans-serif;
  color:#1e293b;
}

/* =========================
   HEADER
========================= */
.header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:2rem;
  gap:1rem;
  flex-wrap:wrap;
}

.header-left{
  display:flex;
  align-items:center;
  gap:1rem;
}

.header-icon{
  width:48px;
  height:48px;
  border-radius:12px;
  background:linear-gradient(135deg,#003087,#2563eb);
  display:flex;
  align-items:center;
  justify-content:center;
  color:#fff;
}

.header-icon svg{
  width:24px;
  height:24px;
}

.header-left h1{
  margin:0;
  font-size:1.6rem;
  font-weight:700;
}

.header-left p{
  margin:0;
  font-size:.85rem;
  color:#64748b;
}

.btn-add{
  display:inline-flex;
  align-items:center;
  gap:.5rem;
  padding:.75rem 1.25rem;
  border:none;
  border-radius:10px;
  background:linear-gradient(135deg,#003087,#2563eb);
  color:#fff;
  font-weight:600;
  cursor:pointer;
  transition:.2s ease;
  box-shadow:0 4px 12px rgba(37,99,235,.25);
}

.btn-add:hover{
  transform:translateY(-2px);
}

.btn-add svg{
  width:18px;
  height:18px;
}

/* =========================
   TOP ROW
========================= */
.top-row{
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:1.5rem;
  margin-bottom:2rem;
  flex-wrap:wrap;
}

.stats-grid{
  display:flex;
  gap:1rem;
  flex-wrap:wrap;
}

.stat-card{
  background:#fff;
  border-radius:16px;
  padding:1rem 1.3rem;
  display:flex;
  align-items:center;
  gap:1rem;
  min-width:150px;
  box-shadow:0 4px 12px rgba(0,0,0,.05);
}

.stat-icon{
  width:44px;
  height:44px;
  border-radius:12px;
  display:flex;
  align-items:center;
  justify-content:center;
}

.stat-icon svg{
  width:20px;
  height:20px;
}

.stat-value{
  font-size:1.7rem;
  font-weight:700;
  line-height:1;
}

.stat-label{
  font-size:.8rem;
  color:#64748b;
  margin-top:.2rem;
}

.year-filter{
  display:flex;
  align-items:center;
  gap:.5rem;
}

.year-filter label{
  font-size:.9rem;
  font-weight:600;
}

.year-select{
  padding:.6rem 1rem;
  border:1px solid #dbe2ea;
  border-radius:10px;
  outline:none;
  background:#fff;
  font-size:.9rem;
}

.year-select:focus{
  border-color:#2563eb;
}

/* =========================
   TABLE
========================= */
.table-card{
  background:#fff;
  border-radius:18px;
  overflow:hidden;
  box-shadow:0 6px 20px rgba(0,0,0,.06);
}

.table-wrapper{
  overflow-x:auto;
}

table{
  width:100%;
  border-collapse:collapse;
  min-width:650px;
}

th{
  background:#f8fafc;
  padding:1rem 1.2rem;
  font-size:.75rem;
  text-transform:uppercase;
  letter-spacing:.05em;
  color:#64748b;
  font-weight:700;
  border-bottom:1px solid #e2e8f0;
}

td{
  padding:1rem 1.2rem;
  border-bottom:1px solid #f1f5f9;
  font-size:.9rem;
}

tr:hover td{
  background:#f8fafc;
}

.center{
  text-align:center;
}

.actions{
  text-align:right;
  white-space:nowrap;
}

.empty{
  text-align:center;
  padding:3rem;
  color:#94a3b8;
}

.office-name{
  display:flex;
  align-items:center;
  gap:.75rem;
  font-weight:600;
}

.office-avatar{
  width:36px;
  height:36px;
  border-radius:10px;
  background:#eff6ff;
  color:#1d4ed8;
  display:flex;
  align-items:center;
  justify-content:center;
  font-weight:700;
}

.count-badge{
  display:inline-block;
  padding:.25rem .75rem;
  border-radius:999px;
  background:#eef2ff;
  color:#4338ca;
  font-weight:600;
}

/* =========================
   ACTION BUTTONS
========================= */
.btn-icon{
  width:34px;
  height:34px;
  border-radius:10px;
  border:1px solid #e2e8f0;
  background:#fff;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  margin-left:.25rem;
  cursor:pointer;
  transition:.2s ease;
}

.btn-icon svg{
  width:16px;
  height:16px;
}

.btn-icon.manage{
  color:#2563eb;
}

.btn-icon.manage:hover{
  background:#eff6ff;
}

.btn-icon.edit{
  color:#059669;
}

.btn-icon.edit:hover{
  background:#ecfdf5;
}

.btn-icon.delete{
  color:#dc2626;
}

.btn-icon.delete:hover{
  background:#fef2f2;
}

/* =========================
   MODAL FIX
========================= */
.modal-overlay{
  position:fixed;
  inset:0;
  width:100vw;
  height:100vh;
  background:rgba(15,23,42,.55);
  z-index:9999;
  display:flex;
  align-items:center;
  justify-content:center;
  padding:20px;
  overflow-y:auto;
  backdrop-filter:blur(4px);
  -webkit-backdrop-filter:blur(4px);
  animation:fadeIn .2s ease;
}

.modal{
  position:relative;
  width:100%;
  max-width:540px;
  background:#fff;
  border-radius:20px;
  overflow:hidden;
  display:flex;
  flex-direction:column;
  max-height:90vh;
  box-shadow:
    0 20px 50px rgba(0,0,0,.25),
    0 2px 8px rgba(0,0,0,.08);
  animation:modalPop .22s ease;
}

.modal-header{
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:1.2rem 1.5rem;
  border-bottom:1px solid #e2e8f0;
  background:#fff;
  flex-shrink:0;
}

.modal-header h3{
  margin:0;
  font-size:1.05rem;
  font-weight:700;
  color:#0f172a;
}

.modal-close{
  width:36px;
  height:36px;
  border:none;
  border-radius:10px;
  background:#f8fafc;
  color:#64748b;
  display:flex;
  align-items:center;
  justify-content:center;
  cursor:pointer;
  transition:.2s ease;
}

.modal-close:hover{
  background:#e2e8f0;
  color:#0f172a;
}

.modal-close svg{
  width:18px;
  height:18px;
}

.modal-body{
  flex:1;
  overflow-y:auto;
  padding:1.5rem;
}

.modal-footer{
  display:flex;
  justify-content:flex-end;
  gap:.75rem;
  padding:1rem 1.5rem;
  border-top:1px solid #e2e8f0;
  background:#f8fafc;
  flex-shrink:0;
}

/* =========================
   GIP LIST
========================= */
.gip-list{
  display:flex;
  flex-direction:column;
  gap:.6rem;
}

.gip-item{
  display:flex;
  align-items:center;
  gap:.75rem;
  padding:.75rem;
  border-radius:12px;
  background:#f8fafc;
  transition:.2s ease;
}

.gip-item:hover{
  background:#eff6ff;
}

.selectable .gip-item{
  cursor:pointer;
}

.gip-avatar{
  width:38px;
  height:38px;
  border-radius:10px;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:.75rem;
  font-weight:700;
}

.gip-avatar.male{
  background:#dbeafe;
  color:#1d4ed8;
}

.gip-avatar.female{
  background:#fce7f3;
  color:#be185d;
}

.gip-info{
  flex:1;
  display:flex;
  align-items:center;
  gap:.5rem;
}

.gip-status{
  padding:.15rem .5rem;
  border-radius:999px;
  font-size:.7rem;
  font-weight:700;
}

.status-active{
  background:#dcfce7;
  color:#15803d;
}

.status-completed{
  background:#dbeafe;
  color:#1d4ed8;
}

.status-terminated{
  background:#fee2e2;
  color:#b91c1c;
}

.status-resigned{
  background:#fff7ed;
  color:#c2410c;
}

.btn-unassign{
  border:none;
  background:none;
  color:#ef4444;
  font-size:1rem;
  cursor:pointer;
}

.btn-assign{
  width:100%;
  margin-top:1rem;
  padding:.75rem;
  border-radius:12px;
  border:1px dashed #93c5fd;
  background:#eff6ff;
  color:#2563eb;
  font-weight:700;
  cursor:pointer;
  transition:.2s ease;
}

.btn-assign:hover{
  background:#dbeafe;
}

/* =========================
   SEARCH
========================= */
.search-box{
  position:relative;
  margin-bottom:1rem;
}

.search-box svg{
  position:absolute;
  left:.85rem;
  top:50%;
  transform:translateY(-50%);
  width:18px;
  height:18px;
  color:#94a3b8;
}

.search-box input{
  width:100%;
  padding:.75rem .9rem .75rem 2.6rem;
  border:1px solid #dbe2ea;
  border-radius:12px;
  outline:none;
  font-size:.9rem;
}

.search-box input:focus{
  border-color:#2563eb;
  box-shadow:0 0 0 4px rgba(37,99,235,.1);
}

/* =========================
   FORM
========================= */
.form-group{
  margin-bottom:1.2rem;
}

.form-group label{
  display:block;
  margin-bottom:.45rem;
  font-size:.9rem;
  font-weight:600;
  color:#334155;
}

.form-input{
  width:100%;
  padding:.75rem .9rem;
  border:1px solid #dbe2ea;
  border-radius:12px;
  outline:none;
  font-size:.9rem;
  transition:.2s ease;
}

.form-input:focus{
  border-color:#2563eb;
  box-shadow:0 0 0 4px rgba(37,99,235,.1);
}

.btn-primary{
  padding:.7rem 1.3rem;
  border:none;
  border-radius:10px;
  background:#2563eb;
  color:#fff;
  font-weight:700;
  cursor:pointer;
}

.btn-primary:hover{
  background:#1d4ed8;
}

.btn-secondary{
  padding:.7rem 1.3rem;
  border:1px solid #dbe2ea;
  border-radius:10px;
  background:#fff;
  color:#334155;
  font-weight:700;
  cursor:pointer;
}

.btn-secondary:hover{
  background:#f8fafc;
}

/* =========================
   ANIMATIONS
========================= */
.fade-enter-active,
.fade-leave-active{
  transition:opacity .2s ease;
}

.fade-enter-from,
.fade-leave-to{
  opacity:0;
}

@keyframes modalPop{
  from{
    opacity:0;
    transform:translateY(-20px) scale(.96);
  }
  to{
    opacity:1;
    transform:translateY(0) scale(1);
  }
}

@keyframes fadeIn{
  from{
    opacity:0;
  }
  to{
    opacity:1;
  }
}

/* =========================
   RESPONSIVE
========================= */
@media (max-width:768px){
  .gip-office-wrapper{ padding:1rem; }
  .header{ flex-direction:column; align-items:flex-start; }
  .top-row{ flex-direction:column; align-items:flex-start; }
  .stats-grid{ width:100%; }
  .stat-card{ flex:1; min-width:unset; }
  .modal{ max-width:100%; border-radius:16px; }
  .modal-header, .modal-body, .modal-footer{ padding:1rem; }
  .modal-footer{ flex-direction:column; }
  .btn-primary, .btn-secondary{ width:100%; }
}
</style>