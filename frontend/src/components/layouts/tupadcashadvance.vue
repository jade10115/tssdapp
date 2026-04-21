<template>
  <div class="cash-page">
    <!-- Hero Section -->
    <div class="hero-section">
      <div class="hero-content">
        <div class="hero-badge">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
          <span>Financial Management</span>
        </div>
        <h1 class="hero-title">Cash Advance</h1>
        <p class="hero-description">Streamline employee cash advances with intelligent ADL selection and automatic calculations</p>
      </div>

      <div class="hero-actions">
        <div class="search-wrapper">
          <div class="search-container">
            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"></circle>
              <path d="m21 21-4.35-4.35"></path>
            </svg>
            <input v-model="searchQuery" type="text" placeholder="Search by employee, ADL, or ID..." class="search-input" />
          </div>
        </div>
        <button class="btn-primary-modern" @click="openCreateModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12h14"/>
          </svg>
          New Cash Advance
        </button>
      </div>
    </div>

    <!-- Stats Dashboard -->
    <div class="stats-grid">
      <div class="stat-card" style="--stat-color: #3b82f6">
        <div class="stat-icon-wrapper">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 21V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v14"/>
            <path d="M9 21V9h6v12"/>
          </svg>
        </div>
        <div class="stat-info">
          <div class="stat-value">{{ filteredRecords.length }}</div>
          <div class="stat-label">Total Records</div>
        </div>
      </div>
      <div class="stat-card" style="--stat-color: #10b981">
        <div class="stat-icon-wrapper">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="1" x2="12" y2="23"></line>
            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
          </svg>
        </div>
        <div class="stat-info">
          <div class="stat-value">{{ formatMoney(totalAmount) }}</div>
          <div class="stat-label">Total Amount</div>
        </div>
      </div>
      <div class="stat-card" style="--stat-color: #f59e0b">
        <div class="stat-icon-wrapper">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
        </div>
        <div class="stat-info">
          <div class="stat-value">{{ formatNumber(totalBeneficiaries) }}</div>
          <div class="stat-label">Beneficiaries</div>
        </div>
      </div>
      <div class="stat-card" style="--stat-color: #8b5cf6">
        <div class="stat-icon-wrapper">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 21h18"/>
            <path d="M6 21V8l6-4 6 4v13"/>
            <path d="M10 21v-7h4v7"/>
          </svg>
        </div>
        <div class="stat-info">
          <div class="stat-value">{{ formatNumber(totalLGU) }}</div>
          <div class="stat-label">LGU Locations</div>
        </div>
      </div>
    </div>

    <!-- Records Table -->
    <div class="records-container">
      <div class="records-header">
        <div>
          <h2 class="records-title">Cash Advance Records</h2>
          <p class="records-subtitle">{{ filteredRecords.length }} active record(s)</p>
        </div>
      </div>
      <div class="table-wrapper">
        <table class="modern-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Date</th>
              <th>Employee</th>
              <th>ADL</th>
              <th class="text-right">LGU</th>
              <th class="text-right">Beneficiaries</th>
              <th class="text-right">Amount</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody v-if="!loading && paginatedRecords.length">
            <tr v-for="row in paginatedRecords" :key="row.id" class="table-row">
              <td class="mono-text">#{{ row.id }}</td>
              <td class="date-cell">{{ formatDate(row.created_at) }}</td>
              <td>
                <div class="employee-info">
                  <div class="employee-name">{{ row.employee }}</div>
                  <div class="employee-position">{{ row.employee_position || '—' }}</div>
                </div>
              </td>
              <td>
                <div class="adl-info">
                  <div class="adl-name">{{ row.adl }}</div>
                  <div class="adl-sponsor">{{ row.sponsor || '—' }}</div>
                </div>
              </td>
              <td class="text-right numeric-cell">{{ formatNumber(row.total_lgu) }}</td>
              <td class="text-right numeric-cell">{{ formatNumber(row.total_beneficiaries) }}</td>
              <td class="text-right amount-highlight">{{ formatMoney(row.total_amount) }}</td>
              <td class="text-center">
                <div class="action-group">
                  <button class="action-btn view-btn" @click="viewRecord(row)" title="View Details">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                      <circle cx="12" cy="12" r="3"/>
                    </svg>
                  </button>
                  <button class="action-btn edit-btn" @click="openEditModal(row)" title="Edit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M17 3l4 4L7 21H3v-4L17 3z"/>
                    </svg>
                  </button>
                  <button class="action-btn export-btn" @click="exportRecord(row)" title="Export to DOCX">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                      <polyline points="7 10 12 15 17 10"/>
                      <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                  </button>
                  <button class="action-btn delete-btn" @click="removeRecord(row)" title="Delete Record">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="3 6 5 6 21 6"/>
                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
          <tbody v-else-if="loading">
            <tr>
              <td colspan="8" class="empty-state">
                <div class="loading-spinner">
                  <div class="spinner-ring"></div>
                  <p>Loading cash advances...</p>
                </div>
              </td>
            </tr>
          </tbody>
          <tbody v-else>
            <tr>
              <td colspan="8" class="empty-state">
                <div class="empty-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                  </svg>
                </div>
                <p>No records found</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="pagination-section">
        <div class="pagination-info">Page <strong>{{ currentPage }}</strong> of <strong>{{ totalPages || 1 }}</strong></div>
        <div class="pagination-controls">
          <button class="pagination-btn" :disabled="currentPage === 1" @click="prevPage">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M15 18l-6-6 6-6"/>
            </svg>
            Previous
          </button>
          <button class="pagination-btn" :disabled="currentPage === totalPages || totalPages === 0" @click="nextPage">
            Next
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M9 18l6-6-6-6"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- CREATE MODAL -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="createModal" class="modal-backdrop" @click.self="closeCreateModal">
          <div class="modal-container modal-xl">
            <div class="modal-header">
              <div class="modal-header-content">
                <div class="modal-header-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14"/>
                  </svg>
                </div>
                <div>
                  <h3 class="modal-title">Create Cash Advance</h3>
                  <p class="modal-description">Select employee and ADL to view available LGU allocations</p>
                </div>
              </div>
              <button class="modal-close" @click="closeCreateModal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
              </button>
            </div>
            <div class="modal-body">
              <div class="form-section">
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                      </svg>
                      Employee
                    </label>
                    <select v-model="form.employee_id" class="form-select">
                      <option value="">Select employee</option>
                      <option v-for="emp in employees" :key="emp.user_id" :value="emp.user_id">
                        {{ emp.first_name }} {{ emp.last_name }}{{ emp.position ? ` — ${emp.position}` : '' }}
                      </option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label class="form-label">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9h18M3 15h18M9 3v18M15 3v18"/>
                      </svg>
                      ADL
                    </label>
                    <select v-model="form.adl_id" class="form-select" @change="onChangeAdl">
                      <option value="">Select ADL</option>
                      <option v-for="adl in enabledAdls" :key="adl.id" :value="adl.id">
                        {{ adl.adl }} — {{ formatMoney(adl.available_amount) }} / {{ formatNumber(adl.available_beneficiaries) }} beneficiaries
                      </option>
                    </select>
                  </div>
                </div>
                <div v-if="selectedAdl" class="stats-grid-mini">
                  <div class="stat-mini">
                    <div class="stat-mini-icon">📊</div>
                    <div class="stat-mini-content">
                      <div class="stat-mini-label">Total LGU</div>
                      <div class="stat-mini-value">{{ formatNumber(allAdlLGU) }}</div>
                    </div>
                  </div>
                  <div class="stat-mini">
                    <div class="stat-mini-icon">👥</div>
                    <div class="stat-mini-content">
                      <div class="stat-mini-label">Total Beneficiaries</div>
                      <div class="stat-mini-value">{{ formatNumber(allAdlBeneficiaries) }}</div>
                    </div>
                  </div>
                  <div class="stat-mini">
                    <div class="stat-mini-icon">💰</div>
                    <div class="stat-mini-content">
                      <div class="stat-mini-label">Total Amount</div>
                      <div class="stat-mini-value">{{ formatMoney(allAdlAmount) }}</div>
                    </div>
                  </div>
                  <div class="stat-mini highlight">
                    <div class="stat-mini-icon">✅</div>
                    <div class="stat-mini-content">
                      <div class="stat-mini-label">Available LGU</div>
                      <div class="stat-mini-value">{{ formatNumber(availableLGU) }}</div>
                    </div>
                  </div>
                  <div class="stat-mini highlight">
                    <div class="stat-mini-icon">👥</div>
                    <div class="stat-mini-content">
                      <div class="stat-mini-label">Available Beneficiaries</div>
                      <div class="stat-mini-value">{{ formatNumber(availableBeneficiaries) }}</div>
                    </div>
                  </div>
                  <div class="stat-mini highlight">
                    <div class="stat-mini-icon">💰</div>
                    <div class="stat-mini-content">
                      <div class="stat-mini-label">Available Amount</div>
                      <div class="stat-mini-value">{{ formatMoney(availableAmount) }}</div>
                    </div>
                  </div>
                </div>
                <div v-if="form.adl_id" class="breakdown-section">
                  <div class="breakdown-header">
                    <div class="breakdown-title-wrapper">
                      <h4 class="breakdown-title">LGU Breakdown</h4>
                      <p class="breakdown-subtitle">Select the LGU rows to include in this cash advance</p>
                    </div>
                    <div class="breakdown-actions">
                      <div class="search-mini">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <circle cx="11" cy="11" r="8"></circle>
                          <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input v-model="breakdownSearch" type="text" placeholder="Search LGU..." />
                      </div>
                      <button class="btn-secondary-sm" @click="selectAllAvailable" type="button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <polygon points="20 6 9 17 4 12"/>
                        </svg>
                        Select All
                      </button>
                      <button class="btn-secondary-sm" @click="clearSelected" type="button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <line x1="18" y1="6" x2="6" y2="18"/>
                          <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                        Clear
                      </button>
                    </div>
                  </div>
                  <div v-if="breakdownLoading" class="loading-container">
                    <div class="spinner-ring"></div>
                    <p>Loading LGU breakdown...</p>
                  </div>
                  <div v-else class="breakdown-table-wrapper">
                    <table class="breakdown-table">
                      <thead>
                        <tr>
                          <th class="text-center" style="width: 60px;">Select</th>
                          <th>Division</th>
                          <th>LGU</th>
                          <th class="text-right">Beneficiaries</th>
                          <th class="text-right">Amount</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="row in filteredBreakdowns" :key="row.id" :class="{ 'row-disabled': row.disabled }">
                          <td class="text-center">
                            <label class="checkbox-label">
                              <input
                                type="checkbox"
                                :checked="isSelected(row.id)"
                                :disabled="row.disabled"
                                @change="toggleSelected(row)"
                                class="checkbox-modern"
                              />
                              <span class="checkbox-custom"></span>
                            </label>
                          </td>
                          <td>{{ row.division_name || '—' }}</td>
                          <td><div class="lgu-name">{{ row.lgu || '—' }}</div></td>
                          <td class="text-right">{{ formatNumber(row.beneficiaries) }}</td>
                          <td class="text-right">{{ formatMoney(row.amount) }}</td>
                          <td>
                            <span v-if="row.disabled" class="status-badge-used">
                              Used{{ row.advanced_by ? ` by ${row.advanced_by}` : '' }}
                            </span>
                            <span v-else class="status-badge-available">Available</span>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <div class="selection-summary">
                <div class="summary-card">
                  <div class="summary-label">Selected LGU</div>
                  <div class="summary-value">{{ formatNumber(selectedLGUCount) }}</div>
                </div>
                <div class="summary-card">
                  <div class="summary-label">Total Beneficiaries</div>
                  <div class="summary-value">{{ formatNumber(selectedBeneficiaries) }}</div>
                </div>
                <div class="summary-card">
                  <div class="summary-label">Total Amount</div>
                  <div class="summary-value">{{ formatMoney(selectedAmount) }}</div>
                </div>
              </div>
              <div class="modal-actions">
                <button class="btn-cancel" @click="closeCreateModal" type="button">Cancel</button>
                <button class="btn-save" @click="save" :disabled="saving || !form.employee_id || !form.adl_id || !selectedItemsData.length" type="button">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/>
                    <polyline points="7 3 7 8 15 8"/>
                  </svg>
                  {{ saving ? 'Creating...' : 'Create Cash Advance' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- VIEW MODAL (grouped by ADL) -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="viewModal" class="modal-backdrop" @click.self="closeViewModal">
          <div class="modal-container modal-lg">
            <div class="modal-header">
              <div class="modal-header-content">
                <div class="modal-header-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                </div>
                <div>
                  <h3 class="modal-title">Cash Advance Details</h3>
                  <p class="modal-description" v-if="viewData">Record #{{ viewData.id }} — {{ viewData.employee }}</p>
                </div>
              </div>
              <button class="modal-close" @click="closeViewModal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
              </button>
            </div>
            <div v-if="viewData" class="modal-body">
              <div class="details-grid">
                <div class="detail-card">
                  <div class="detail-icon">👤</div>
                  <div class="detail-content">
                    <div class="detail-label">Employee</div>
                    <div class="detail-value">{{ viewData.employee }}</div>
                  </div>
                </div>
                <div class="detail-card">
                  <div class="detail-icon">🏢</div>
                  <div class="detail-content">
                    <div class="detail-label">ADL</div>
                    <div class="detail-value">{{ viewData.adl }}</div>
                  </div>
                </div>
                <div class="detail-card">
                  <div class="detail-icon">📍</div>
                  <div class="detail-content">
                    <div class="detail-label">LGU Locations</div>
                    <div class="detail-value">{{ formatNumber(viewData.total_lgu) }}</div>
                  </div>
                </div>
                <div class="detail-card">
                  <div class="detail-icon">👥</div>
                  <div class="detail-content">
                    <div class="detail-label">Beneficiaries</div>
                    <div class="detail-value">{{ formatNumber(viewData.total_beneficiaries) }}</div>
                  </div>
                </div>
                <div class="detail-card">
                  <div class="detail-icon">💰</div>
                  <div class="detail-content">
                    <div class="detail-label">Total Amount</div>
                    <div class="detail-value amount">{{ formatMoney(viewData.total_amount) }}</div>
                  </div>
                </div>
                <div class="detail-card">
                  <div class="detail-icon">📅</div>
                  <div class="detail-content">
                    <div class="detail-label">Created Date</div>
                    <div class="detail-value">{{ formatDateTime(viewData.created_at) }}</div>
                  </div>
                </div>
              </div>
              <!-- Grouped items -->
              <div v-for="(group, adlName) in groupedItems" :key="adlName" class="adl-group-section">
                <h4 class="adl-group-title">{{ adlName }}</h4>
                <div class="items-table-wrapper">
                  <table class="items-table">
                    <thead>
                      <tr>
                        <th>LGU</th>
                        <th class="text-right">Beneficiaries</th>
                        <th class="text-right">Amount</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in group" :key="item.id">
                        <td>{{ item.lgu }}</td>
                        <td class="text-right">{{ formatNumber(item.beneficiaries) }}</td>
                        <td class="text-right amount">{{ formatMoney(item.amount) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- EDIT MODAL -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="editModal" class="modal-backdrop" @click.self="closeEditModal">
          <div class="modal-container modal-xl">
            <div class="modal-header">
              <div class="modal-header-content">
                <div class="modal-header-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 3l4 4L7 21H3v-4L17 3z"/>
                  </svg>
                </div>
                <div>
                  <h3 class="modal-title">Edit Cash Advance</h3>
                  <p class="modal-description">Modify employee, ADL, and selected LGUs</p>
                </div>
              </div>
              <button class="modal-close" @click="closeEditModal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
              </button>
            </div>
            <div class="modal-body">
              <div class="form-section">
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                      </svg>
                      Employee
                    </label>
                    <select v-model="editForm.employee_id" class="form-select">
                      <option value="">Select employee</option>
                      <option v-for="emp in employees" :key="emp.user_id" :value="emp.user_id">
                        {{ emp.first_name }} {{ emp.last_name }}{{ emp.position ? ` — ${emp.position}` : '' }}
                      </option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label class="form-label">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9h18M3 15h18M9 3v18M15 3v18"/>
                      </svg>
                      ADL
                    </label>
                    <select v-model="editForm.adl_id" class="form-select" @change="onChangeEditAdl">
                      <option value="">Select ADL</option>
                      <option v-for="adl in enabledAdls" :key="adl.id" :value="adl.id">
                        {{ adl.adl }}
                      </option>
                    </select>
                  </div>
                </div>
                <!-- Current items grouped by ADL with delete buttons -->
                <div v-if="Object.keys(currentGroupedItems).length > 0" class="current-items-section">
                  <h4 class="section-title">Current LGUs</h4>
                  <div v-for="(group, adlName) in currentGroupedItems" :key="adlName" class="adl-group-section">
                    <h5 class="adl-group-title">{{ adlName }}</h5>
                    <div class="items-table-wrapper">
                      <table class="items-table">
                        <thead>
                          <tr>
                            <th>LGU</th>
                            <th class="text-right">Beneficiaries</th>
                            <th class="text-right">Amount</th>
                            <th class="text-center" style="width: 80px;">Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="item in group" :key="item.id">
                            <td>{{ item.lgu }}</td>
                            <td class="text-right">{{ formatNumber(item.beneficiaries) }}</td>
                            <td class="text-right">{{ formatMoney(item.amount) }}</td>
                            <td class="text-center">
                              <button class="btn-icon-danger" @click="removeItemFromEdit(item.id)" title="Remove">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                  <line x1="18" y1="6" x2="6" y2="18"/>
                                  <line x1="6" y1="6" x2="18" y2="18"/>
                                </svg>
                              </button>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
                <!-- Available LGUs grouped by ADL (with checkboxes) -->
                <div v-if="Object.keys(availableGroupedBreakdowns).length > 0" class="available-items-section">
                  <h4 class="section-title">Add More LGUs</h4>
                  <div v-for="(group, adlName) in availableGroupedBreakdowns" :key="adlName" class="adl-group-section">
                    <h5 class="adl-group-title">{{ adlName }}</h5>
                    <div class="breakdown-table-wrapper">
                      <table class="breakdown-table">
                        <thead>
                          <tr>
                            <th class="text-center" style="width: 60px;">Select</th>
                            <th>LGU</th>
                            <th class="text-right">Beneficiaries</th>
                            <th class="text-right">Amount</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="row in group" :key="row.id">
                            <td class="text-center">
                              <label class="checkbox-label">
                                <input type="checkbox" :value="row.id" v-model="selectedEditItemIds" class="checkbox-modern" />
                                <span class="checkbox-custom"></span>
                              </label>
                            </td>
                            <td>{{ row.lgu }}</td>
                            <td class="text-right">{{ formatNumber(row.beneficiaries) }}</td>
                            <td class="text-right">{{ formatMoney(row.amount) }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <div class="selection-summary">
                <div class="summary-card">
                  <div class="summary-label">Total LGUs</div>
                  <div class="summary-value">{{ editCurrentItems.length + selectedEditItemIds.length }}</div>
                </div>
                <div class="summary-card">
                  <div class="summary-label">Total Beneficiaries</div>
                  <div class="summary-value">{{ formatNumber(editTotalBeneficiaries) }}</div>
                </div>
                <div class="summary-card">
                  <div class="summary-label">Total Amount</div>
                  <div class="summary-value">{{ formatMoney(editTotalAmount) }}</div>
                </div>
              </div>
              <div class="modal-actions">
                <button class="btn-cancel" @click="closeEditModal" type="button">Cancel</button>
                <button class="btn-save" @click="updateCashAdvance" :disabled="updating || !editForm.employee_id || !editForm.adl_id" type="button">
                  {{ updating ? 'Updating...' : 'Update Cash Advance' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch, inject } from "vue";
import axios from "axios";
import Swal from "sweetalert2";

// ========== GLOBAL API BASE (injected from main.js) ==========
const API_BASE = inject("API_BASE");
// Define resource endpoints
const API_URL = `${API_BASE}/cash-advances`;
const EMPLOYEES_URL = `${API_BASE}/cash-advances/employees`;
const ADLS_URL = `${API_BASE}/cash-advances/adls`;
const BREAKDOWN_URL = `${API_BASE}/cash-advances/breakdown`;
const ALL_BREAKDOWNS_URL = `${API_BASE}/cash-advances/breakdown/all`;

const loading = ref(false);
const breakdownLoading = ref(false);
const saving = ref(false);
const updating = ref(false);

const records = ref([]);
const employees = ref([]);
const adls = ref([]);
const breakdownRows = ref([]);
const allBreakdowns = ref([]); // for edit modal

const searchQuery = ref("");
const breakdownSearch = ref("");

const createModal = ref(false);
const viewModal = ref(false);
const editModal = ref(false);
const viewData = ref(null);
const editData = ref(null);

const currentPage = ref(1);
const perPage = 10;

const form = ref({ employee_id: "", adl_id: "" });
const editForm = ref({ employee_id: "", adl_id: "" });

// Persistent selection for create modal: store full item data
const selectedItemsData = ref([]);

const selectedEditItemIds = ref([]);
const editCurrentItems = ref([]);

// ====================== FORMATTING ======================
const formatMoney = (value) =>
  new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Number(value || 0));

const formatNumber = (value) => Number(value || 0).toLocaleString("en-PH");

const formatDate = (value) => {
  if (!value) return "—";
  const d = new Date(value);
  if (isNaN(d.getTime())) return "—";
  return d.toLocaleDateString("en-PH", { year: "numeric", month: "short", day: "2-digit" });
};

const formatDateTime = (value) => {
  if (!value) return "—";
  const d = new Date(value);
  if (isNaN(d.getTime())) return "—";
  return d.toLocaleString("en-PH", { year: "numeric", month: "short", day: "2-digit", hour: "2-digit", minute: "2-digit" });
};

// ====================== COMPUTED ======================
const enabledAdls = computed(() => (adls.value && Array.isArray(adls.value)) ? adls.value.filter((row) => !row.disabled) : []);

const selectedAdl = computed(() =>
  adls.value && Array.isArray(adls.value) ? adls.value.find((row) => Number(row.id) === Number(form.value.adl_id)) || null : null
);

// Safe filtered records
const filteredRecords = computed(() => {
  if (!records.value || !Array.isArray(records.value)) return [];
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return records.value;
  return records.value.filter((row) =>
    [row.employee, row.adl, row.sponsor, row.id].join(" ").toLowerCase().includes(q)
  );
});

const totalPages = computed(() => Math.ceil(filteredRecords.value.length / perPage));
const paginatedRecords = computed(() => {
  const start = (currentPage.value - 1) * perPage;
  return filteredRecords.value.slice(start, start + perPage);
});

const totalAmount = computed(() => {
  if (!filteredRecords.value || !Array.isArray(filteredRecords.value)) return 0;
  return filteredRecords.value.reduce((sum, row) => sum + Number(row.total_amount || 0), 0);
});

const totalBeneficiaries = computed(() => {
  if (!filteredRecords.value || !Array.isArray(filteredRecords.value)) return 0;
  return filteredRecords.value.reduce((sum, row) => sum + Number(row.total_beneficiaries || 0), 0);
});

const totalLGU = computed(() => {
  if (!filteredRecords.value || !Array.isArray(filteredRecords.value)) return 0;
  return filteredRecords.value.reduce((sum, row) => sum + Number(row.total_lgu || 0), 0);
});

// Create modal breakdowns
const filteredBreakdowns = computed(() => {
  if (!breakdownRows.value || !Array.isArray(breakdownRows.value)) return [];
  const q = breakdownSearch.value.trim().toLowerCase();
  if (!q) return breakdownRows.value;
  return breakdownRows.value.filter((row) =>
    [row.lgu, row.division_name, row.advanced_by].join(" ").toLowerCase().includes(q)
  );
});

const allAdlLGU = computed(() => {
  if (!breakdownRows.value || !Array.isArray(breakdownRows.value)) return 0;
  const map = {};
  breakdownRows.value.forEach((row) => {
    const key = String(row.lgu || "").trim().toLowerCase();
    if (key) map[key] = row.lgu;
  });
  return Object.keys(map).length;
});

const allAdlBeneficiaries = computed(() => {
  if (!breakdownRows.value || !Array.isArray(breakdownRows.value)) return 0;
  return breakdownRows.value.reduce((sum, row) => sum + Number(row.beneficiaries || 0), 0);
});

const allAdlAmount = computed(() => {
  if (!breakdownRows.value || !Array.isArray(breakdownRows.value)) return 0;
  return breakdownRows.value.reduce((sum, row) => sum + Number(row.amount || 0), 0);
});

const availableRows = computed(() => {
  if (!breakdownRows.value || !Array.isArray(breakdownRows.value)) return [];
  return breakdownRows.value.filter((row) => !row.disabled);
});

const availableLGU = computed(() => {
  if (!availableRows.value || !Array.isArray(availableRows.value)) return 0;
  const map = {};
  availableRows.value.forEach((row) => {
    const key = String(row.lgu || "").trim().toLowerCase();
    if (key) map[key] = row.lgu;
  });
  return Object.keys(map).length;
});

const availableBeneficiaries = computed(() => {
  if (!availableRows.value || !Array.isArray(availableRows.value)) return 0;
  return availableRows.value.reduce((sum, row) => sum + Number(row.beneficiaries || 0), 0);
});

const availableAmount = computed(() => {
  if (!availableRows.value || !Array.isArray(availableRows.value)) return 0;
  return availableRows.value.reduce((sum, row) => sum + Number(row.amount || 0), 0);
});

// Selection totals (persistent across ADL changes)
const selectedLGUCount = computed(() => selectedItemsData.value.length);
const selectedBeneficiaries = computed(() =>
  selectedItemsData.value.reduce((sum, item) => sum + (item.beneficiaries || 0), 0)
);
const selectedAmount = computed(() =>
  selectedItemsData.value.reduce((sum, item) => sum + (item.amount || 0), 0)
);

// Group items for view modal
const groupedItems = computed(() => {
  if (!viewData.value || !viewData.value.items || !Array.isArray(viewData.value.items)) return {};
  const groups = {};
  viewData.value.items.forEach(item => {
    const adl = item.adl || 'Other';
    if (!groups[adl]) groups[adl] = [];
    groups[adl].push(item);
  });
  return groups;
});

// Group current items for edit modal
const currentGroupedItems = computed(() => {
  if (!editCurrentItems.value || !Array.isArray(editCurrentItems.value)) return {};
  const groups = {};
  editCurrentItems.value.forEach(item => {
    const adl = item.adl || 'Other';
    if (!groups[adl]) groups[adl] = [];
    groups[adl].push(item);
  });
  return groups;
});

// Group available breakdowns by ADL name
const availableGroupedBreakdowns = computed(() => {
  if (!allBreakdowns.value || !Array.isArray(allBreakdowns.value)) return {};
  const groups = {};
  allBreakdowns.value.forEach(b => {
    const adl = b.adl || 'Other';
    if (!groups[adl]) groups[adl] = [];
    groups[adl].push(b);
  });
  return groups;
});

const editTotalAmount = computed(() => {
  const currentSum = (editCurrentItems.value || []).reduce((sum, i) => sum + (i.amount || 0), 0);
  const selectedSum = (allBreakdowns.value || [])
    .filter(b => selectedEditItemIds.value.includes(b.id))
    .reduce((sum, b) => sum + (b.amount || 0), 0);
  return currentSum + selectedSum;
});

const editTotalBeneficiaries = computed(() => {
  const currentSum = (editCurrentItems.value || []).reduce((sum, i) => sum + (i.beneficiaries || 0), 0);
  const selectedSum = (allBreakdowns.value || [])
    .filter(b => selectedEditItemIds.value.includes(b.id))
    .reduce((sum, b) => sum + (b.beneficiaries || 0), 0);
  return currentSum + selectedSum;
});

// ====================== METHODS ======================
const isSelected = (id) => selectedItemsData.value.some(i => i.id === id);

const toggleSelected = (row) => {
  const index = selectedItemsData.value.findIndex(i => i.id === row.id);
  if (index !== -1) {
    selectedItemsData.value.splice(index, 1);
  } else if (!row.disabled) {
    selectedItemsData.value.push({
      id: row.id,
      lgu: row.lgu,
      beneficiaries: row.beneficiaries,
      amount: row.amount
    });
  }
};

const selectAllAvailable = () => {
  const availableRowsList = breakdownRows.value.filter(row => !row.disabled);
  const newSelections = availableRowsList.filter(row => !selectedItemsData.value.some(i => i.id === row.id));
  selectedItemsData.value.push(...newSelections.map(row => ({
    id: row.id,
    lgu: row.lgu,
    beneficiaries: row.beneficiaries,
    amount: row.amount
  })));
};

const clearSelected = () => {
  selectedItemsData.value = [];
};

const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value += 1; };
const prevPage = () => { if (currentPage.value > 1) currentPage.value -= 1; };
watch(searchQuery, () => { currentPage.value = 1; });

const getErrorMessage = (err) => {
  const errors = err?.response?.data?.errors;
  if (errors && typeof errors === "object") {
    return Object.values(errors).flat().join("<br>");
  }
  return err?.response?.data?.message || "Something went wrong.";
};

// Data loading
const year = ref(new Date().getFullYear());

const loadRecords = async () => {
  loading.value = true;
  try {
    const { data } = await axios.get(API_URL, { params: { year: year.value } });
    records.value = Array.isArray(data) ? data : [];
  } catch (err) {
    console.error(err);
    Swal.fire({ title: "Error", html: getErrorMessage(err), icon: "error" });
  } finally {
    loading.value = false;
  }
};

const loadEmployees = async () => {
  try {
    const { data } = await axios.get(EMPLOYEES_URL);
    employees.value = data || [];
  } catch (err) { console.error(err); }
};

const loadAdls = async () => {
  try {
    const { data } = await axios.get(ADLS_URL, { params: { year: year.value } });
    adls.value = Array.isArray(data) ? data : [];
  } catch (err) { console.error(err); }
};

const loadBreakdown = async () => {
  if (!form.value.adl_id) {
    breakdownRows.value = [];
    return;
  }
  breakdownLoading.value = true;
  breakdownSearch.value = "";
  try {
    const { data } = await axios.get(BREAKDOWN_URL, { params: { adl_id: form.value.adl_id } });
    breakdownRows.value = Array.isArray(data) ? data : [];
    // Do NOT reset selectedItemsData here – preserve selections across ADL changes
  } catch (err) {
    console.error(err);
    breakdownRows.value = [];
    Swal.fire({ title: "Error", html: getErrorMessage(err), icon: "error" });
  } finally {
    breakdownLoading.value = false;
  }
};

const loadAllBreakdowns = async () => {
  try {
    const { data } = await axios.get(ALL_BREAKDOWNS_URL);
    allBreakdowns.value = data || [];
  } catch (err) { console.error(err); }
};

const resetForm = () => {
  form.value = { employee_id: "", adl_id: "" };
  breakdownRows.value = [];
  selectedItemsData.value = []; // clear persistent selections
  breakdownSearch.value = "";
};

const openCreateModal = async () => {
  resetForm();
  createModal.value = true;
  if (!employees.value.length) await loadEmployees();
  if (!adls.value.length) await loadAdls();
};

const closeCreateModal = () => {
  createModal.value = false;
  resetForm();
};

const closeViewModal = () => {
  viewModal.value = false;
  viewData.value = null;
};

const onChangeAdl = async () => { await loadBreakdown(); };

// Save new cash advance
const save = async () => {
  if (!form.value.employee_id) {
    Swal.fire("Required", "Please select an employee.", "warning");
    return;
  }
  if (!form.value.adl_id) {
    Swal.fire("Required", "Please select an ADL.", "warning");
    return;
  }
  if (!selectedItemsData.value.length) {
    Swal.fire("Required", "Please select at least one LGU row.", "warning");
    return;
  }
  saving.value = true;
  try {
    await axios.post(API_URL, {
      employee_id: Number(form.value.employee_id),
      adl_id: Number(form.value.adl_id),
      item_ids: selectedItemsData.value.map(i => i.id),
    });
    Swal.fire({ title: "Saved!", text: "Cash advance created successfully.", icon: "success", timer: 1800, showConfirmButton: false });
    closeCreateModal();
    await Promise.all([loadRecords(), loadAdls()]);
  } catch (err) {
    console.error(err);
    Swal.fire({ title: "Error", html: getErrorMessage(err), icon: "error" });
  } finally {
    saving.value = false;
  }
};

// View record
const viewRecord = async (row) => {
  try {
    const { data } = await axios.get(`${API_URL}/${row.id}`);
    viewData.value = data;
    viewModal.value = true;
  } catch (err) {
    console.error(err);
    Swal.fire({ title: "Error", html: getErrorMessage(err), icon: "error" });
  }
};

// Edit record
const openEditModal = async (row) => {
  try {
    const { data } = await axios.get(`${API_URL}/${row.id}`);
    editData.value = data;
    editForm.value.employee_id = data.employee_id || '';
    editForm.value.adl_id = data.adl_id || '';
    editCurrentItems.value = (data.items || []).map(i => ({
      id: i.id,
      breakdown_id: i.breakdown_id,
      lgu: i.lgu,
      beneficiaries: i.beneficiaries,
      amount: i.amount,
      adl: i.adl,
    }));
    selectedEditItemIds.value = [];
    if (!allBreakdowns.value.length) await loadAllBreakdowns();
    editModal.value = true;
  } catch (err) {
    console.error(err);
    Swal.fire('Error', 'Failed to load cash advance data.', 'error');
  }
};

const closeEditModal = () => {
  editModal.value = false;
  editData.value = null;
  editForm.value = { employee_id: "", adl_id: "" };
  editCurrentItems.value = [];
  selectedEditItemIds.value = [];
};

const removeItemFromEdit = (itemId) => {
  editCurrentItems.value = editCurrentItems.value.filter(i => i.id !== itemId);
};

const onChangeEditAdl = async () => {
  // optional
};

const updateCashAdvance = async () => {
  if (!editForm.value.employee_id) {
    Swal.fire("Required", "Please select an employee.", "warning");
    return;
  }
  if (!editForm.value.adl_id) {
    Swal.fire("Required", "Please select an ADL.", "warning");
    return;
  }
  const allItemIds = [
    ...editCurrentItems.value.map(i => i.breakdown_id),
    ...selectedEditItemIds.value,
  ];
  if (allItemIds.length === 0) {
    Swal.fire("Required", "Please select at least one LGU row.", "warning");
    return;
  }
  updating.value = true;
  try {
    await axios.put(`${API_URL}/${editData.value.id}`, {
      employee_id: Number(editForm.value.employee_id),
      adl_id: Number(editForm.value.adl_id),
      item_ids: allItemIds,
    });
    Swal.fire({ title: "Updated!", text: "Cash advance updated successfully.", icon: "success", timer: 1800, showConfirmButton: false });
    closeEditModal();
    await Promise.all([loadRecords(), loadAdls()]);
  } catch (err) {
    console.error(err);
    Swal.fire({ title: "Error", html: getErrorMessage(err), icon: "error" });
  } finally {
    updating.value = false;
  }
};

// Delete record
const removeRecord = async (row) => {
  const result = await Swal.fire({
    title: "Delete cash advance?",
    text: `This will delete cash advance #${row.id}.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, delete",
    confirmButtonColor: "#dc2626",
  });
  if (!result.isConfirmed) return;
  try {
    await axios.delete(`${API_URL}/${row.id}`);
    Swal.fire({ title: "Deleted", text: "Cash advance deleted successfully.", icon: "success", timer: 1500, showConfirmButton: false });
    await Promise.all([loadRecords(), loadAdls()]);
  } catch (err) {
    console.error(err);
    Swal.fire({ title: "Error", html: getErrorMessage(err), icon: "error" });
  }
};

// Export to Excel (XLSX)
const exportRecord = async (row) => {
  try {
    const response = await axios.get(`${API_URL}/${row.id}/export-excel`, {
      responseType: 'blob',
    });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `cash_advance_${row.id}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error('Export error', error);
    if (error.response && error.response.data instanceof Blob) {
      const text = await error.response.data.text();
      try {
        const err = JSON.parse(text);
        Swal.fire('Error', err.error || 'Failed to export document.', 'error');
      } catch {
        Swal.fire('Error', 'Failed to export document.', 'error');
      }
    } else {
      Swal.fire('Error', 'Failed to export document.', 'error');
    }
  }
};

onMounted(async () => {
  await Promise.all([loadRecords(), loadEmployees(), loadAdls()]);
  await loadAllBreakdowns();
});
</script>


<style scoped>
/* MODAL CENTERING FIX */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1rem;
  overflow-y: auto;
}

.modal-container {
  background: white;
  border-radius: 1.5rem;
  width: 100%;
  max-width: 90%;
  margin: 0 !important;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  animation: modalSlideIn 0.2s ease-out;
  display: flex;
  flex-direction: column;
  max-height: 90vh;
}
.modal-xl { max-width: 1200px; }
.modal-lg { max-width: 800px; }

/* Modal Header */
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
  background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
  border-radius: 1.5rem 1.5rem 0 0;
}
.modal-header-content {
  display: flex;
  gap: 1rem;
  align-items: center;
}
.modal-header-icon {
  width: 3rem;
  height: 3rem;
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  border-radius: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
}
.modal-header-icon svg {
  width: 1.5rem;
  height: 1.5rem;
  color: white;
}
.modal-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 0.25rem 0;
}
.modal-description {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0;
}
.modal-close {
  width: 2rem;
  height: 2rem;
  border-radius: 0.5rem;
  border: none;
  background: #f1f5f9;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
}
.modal-close svg {
  width: 1rem;
  height: 1rem;
  color: #64748b;
}
.modal-close:hover {
  background: #e2e8f0;
  transform: scale(0.95);
}

/* Modal Body */
.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
  flex: 1;
}

/* Form Styles */
.form-section {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.form-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #334155;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.form-label svg {
  width: 1rem;
  height: 1rem;
  color: #3b82f6;
}
.form-select {
  padding: 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  font-size: 0.875rem;
  transition: all 0.15s;
  background: white;
}
.form-select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Stats Mini Grid */
.stats-grid-mini {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-top: 1rem;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 1rem;
  border: 1px solid #e2e8f0;
}
.stat-mini {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: white;
  border-radius: 0.75rem;
  transition: all 0.15s;
}
.stat-mini.highlight {
  background: linear-gradient(135deg, #fef3c7, #fde68a);
  border: 1px solid #f59e0b;
}
.stat-mini-icon {
  font-size: 1.5rem;
}
.stat-mini-content {
  flex: 1;
}
.stat-mini-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.stat-mini-value {
  font-size: 1rem;
  font-weight: 700;
  color: #0f172a;
}

/* Breakdown Section */
.breakdown-section {
  margin-top: 1.5rem;
}
.breakdown-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  flex-wrap: wrap;
  gap: 1rem;
}
.breakdown-title-wrapper {
  flex: 1;
}
.breakdown-title {
  font-size: 1rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 0.25rem 0;
}
.breakdown-subtitle {
  font-size: 0.75rem;
  color: #64748b;
  margin: 0;
}
.breakdown-actions {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}
.search-mini {
  position: relative;
}
.search-mini svg {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  width: 1rem;
  height: 1rem;
  color: #94a3b8;
}
.search-mini input {
  padding: 0.5rem 0.75rem 0.5rem 2rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  width: 200px;
}
.search-mini input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}
.btn-secondary-sm {
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  border: 1px solid #e2e8f0;
  background: white;
  font-size: 0.813rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}
.btn-secondary-sm svg {
  width: 1rem;
  height: 1rem;
}
.btn-secondary-sm:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
  transform: translateY(-1px);
}

/* Breakdown Table */
.breakdown-table-wrapper {
  overflow-x: auto;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
}
.breakdown-table {
  width: 100%;
  border-collapse: collapse;
}
.breakdown-table thead th {
  background: #f8fafc;
  padding: 0.875rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
  border-bottom: 1px solid #e2e8f0;
  text-align: left;
}
.breakdown-table tbody td {
  padding: 0.875rem;
  border-bottom: 1px solid #f1f5f9;
  font-size: 0.875rem;
}
.row-disabled {
  background: #fafafa;
  opacity: 0.6;
}

/* Custom Checkbox */
.checkbox-label {
  display: inline-block;
  position: relative;
  cursor: pointer;
}
.checkbox-modern {
  position: absolute;
  opacity: 0;
  cursor: pointer;
  height: 0;
  width: 0;
}
.checkbox-custom {
  display: inline-block;
  width: 1.25rem;
  height: 1.25rem;
  border: 2px solid #cbd5e1;
  border-radius: 0.375rem;
  background: white;
  transition: all 0.15s;
}
.checkbox-modern:checked + .checkbox-custom {
  background: #3b82f6;
  border-color: #3b82f6;
  position: relative;
}
.checkbox-modern:checked + .checkbox-custom::after {
  content: '';
  position: absolute;
  left: 0.375rem;
  top: 0.125rem;
  width: 0.375rem;
  height: 0.625rem;
  border: solid white;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
}
.checkbox-modern:disabled + .checkbox-custom {
  background: #f1f5f9;
  border-color: #e2e8f0;
  cursor: not-allowed;
}

/* Status Badges */
.status-badge-available, .status-badge-used {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 2rem;
  font-size: 0.75rem;
  font-weight: 600;
}
.status-badge-available {
  background: #d1fae5;
  color: #065f46;
}
.status-badge-used {
  background: #fed7aa;
  color: #92400e;
}

/* Modal Footer */
.modal-footer {
  padding: 1.5rem;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #fafbfc;
  border-radius: 0 0 1.5rem 1.5rem;
}
.selection-summary {
  display: flex;
  gap: 1rem;
}
.summary-card {
  background: white;
  padding: 0.75rem 1rem;
  border-radius: 0.75rem;
  border: 1px solid #e2e8f0;
  min-width: 100px;
}
.summary-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.25rem;
}
.summary-value {
  font-size: 1rem;
  font-weight: 700;
  color: #0f172a;
}
.modal-actions {
  display: flex;
  gap: 0.75rem;
}
.btn-cancel {
  padding: 0.625rem 1.25rem;
  border-radius: 0.75rem;
  border: 1px solid #e2e8f0;
  background: white;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.15s;
}
.btn-cancel:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
  transform: translateY(-1px);
}
.btn-save {
  padding: 0.625rem 1.5rem;
  border-radius: 0.75rem;
  border: none;
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: white;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.15s;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}
.btn-save svg {
  width: 1rem;
  height: 1rem;
}
.btn-save:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
}
.btn-save:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Details Grid (view modal) */
.details-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  margin-bottom: 1.5rem;
}
.detail-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 1rem;
  padding: 1rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: all 0.15s;
}
.detail-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
.detail-icon {
  font-size: 1.5rem;
  width: 2.5rem;
  height: 2.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  border-radius: 0.75rem;
}
.detail-content {
  flex: 1;
}
.detail-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.25rem;
}
.detail-value {
  font-size: 1rem;
  font-weight: 600;
  color: #0f172a;
}
.detail-value.amount {
  color: #059669;
  font-size: 1.125rem;
}

/* Items Table */
.items-section {
  margin-top: 1.5rem;
}
.items-title {
  font-size: 0.875rem;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 1rem;
}
.items-table-wrapper {
  overflow-x: auto;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
}
.items-table {
  width: 100%;
  border-collapse: collapse;
}
.items-table thead th {
  background: #f8fafc;
  padding: 0.75rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
  border-bottom: 1px solid #e2e8f0;
}
/* Fix: ensure right-aligned headers and cells */
.items-table thead th.text-right,
.items-table tbody td.text-right {
  text-align: right;
}
.items-table tbody td {
  padding: 0.75rem;
  font-size: 0.875rem;
  border-bottom: 1px solid #f1f5f9;
}
.items-table tbody td.amount {
  font-weight: 600;
  color: #059669;
}

/* Grouped sections */
.adl-group-section {
  margin-top: 1.5rem;
}
.adl-group-title {
  font-size: 1rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 0.75rem;
  padding-left: 0.5rem;
  border-left: 4px solid #3b82f6;
}
.section-title {
  font-size: 1.125rem;
  font-weight: 700;
  margin: 1rem 0;
  color: #0f172a;
}
.current-items-section {
  margin-top: 1rem;
  border-top: 1px solid #e2e8f0;
  padding-top: 1rem;
}
.available-items-section {
  margin-top: 1.5rem;
  border-top: 1px solid #e2e8f0;
  padding-top: 1rem;
}

/* Action Buttons */
.action-group {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
}
.action-btn {
  width: 2rem;
  height: 2rem;
  border-radius: 0.5rem;
  border: 1px solid #e2e8f0;
  background: white;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s;
}
.action-btn svg {
  width: 1rem;
  height: 1rem;
}
.view-btn:hover {
  background: #eff6ff;
  border-color: #3b82f6;
  color: #3b82f6;
}
.edit-btn:hover {
  background: #eff6ff;
  border-color: #f59e0b;
  color: #f59e0b;
}
.export-btn:hover {
  background: #eff6ff;
  border-color: #059669;
  color: #059669;
}
.delete-btn:hover {
  background: #fef2f2;
  border-color: #ef4444;
  color: #ef4444;
}
.btn-icon-danger {
  background: none;
  border: none;
  cursor: pointer;
  color: #dc2626;
  padding: 0.25rem;
  border-radius: 0.25rem;
  transition: all 0.15s;
}
.btn-icon-danger svg {
  width: 1.25rem;
  height: 1.25rem;
}
.btn-icon-danger:hover {
  background: #fef2f2;
  transform: scale(1.1);
}

/* Pagination */
.pagination-section {
  padding: 1rem 1.5rem;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #fafbfc;
}
.pagination-info {
  font-size: 0.875rem;
  color: #64748b;
}
.pagination-controls {
  display: flex;
  gap: 0.5rem;
}
.pagination-btn {
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  border: 1px solid #e2e8f0;
  background: white;
  font-size: 0.875rem;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: all 0.15s;
}
.pagination-btn svg {
  width: 1rem;
  height: 1rem;
}
.pagination-btn:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #cbd5e1;
}
.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Loading States */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  gap: 1rem;
}
.spinner-ring {
  width: 2rem;
  height: 2rem;
  border: 2px solid #e2e8f0;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}
.loading-spinner {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  gap: 1rem;
}
.empty-state {
  text-align: center;
  padding: 3rem;
  color: #94a3b8;
}
.empty-icon {
  width: 3rem;
  height: 3rem;
  margin: 0 auto 1rem;
  color: #cbd5e1;
}

/* Animations */
@keyframes spin {
  to { transform: rotate(360deg); }
}
@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
.modal-enter-active, .modal-leave-active {
  transition: all 0.2s ease;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
  transform: scale(0.95);
}

/* Responsive */
@media (max-width: 1024px) {
  .stats-grid-mini { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
  .modal-xl, .modal-lg { width: 95%; max-width: 95%; margin: 1rem auto; }
  .modal-body { max-height: calc(85vh - 8rem); }
  .modal-header, .modal-footer { padding: 1rem; }
  .form-row { grid-template-columns: 1fr; }
  .stats-grid-mini { grid-template-columns: 1fr; }
  .details-grid { grid-template-columns: 1fr; }
  .breakdown-header { flex-direction: column; align-items: stretch; }
  .breakdown-actions { flex-direction: column; }
  .search-mini input { width: 100%; }
  .modal-footer { flex-direction: column; gap: 1rem; align-items: stretch; }
  .selection-summary { justify-content: space-between; flex-wrap: wrap; }
  .modal-actions { width: 100%; }
  .modal-actions button { flex: 1; }
}

/* Main page styles (unchanged) */
.cash-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #f5f7fa 0%, #f8fafc 100%);
  padding: 2rem;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}
.hero-section {
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  border-radius: 1.5rem;
  padding: 2rem;
  margin-bottom: 2rem;
  position: relative;
  overflow: hidden;
  box-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.1);
}
.hero-section::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -10%;
  width: 300px;
  height: 300px;
  background: radial-gradient(circle, rgba(59,130,246,0.1) 0%, transparent 70%);
  border-radius: 50%;
  pointer-events: none;
}
.hero-content {
  position: relative;
  z-index: 1;
}
.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  padding: 0.5rem 1rem;
  border-radius: 2rem;
  width: fit-content;
  margin-bottom: 1rem;
}
.hero-badge svg {
  width: 1rem;
  height: 1rem;
  color: #60a5fa;
}
.hero-badge span {
  font-size: 0.75rem;
  font-weight: 600;
  color: #cbd5e1;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.hero-title {
  font-size: 2.5rem;
  font-weight: 800;
  color: white;
  margin-bottom: 0.5rem;
  letter-spacing: -0.02em;
}
.hero-description {
  font-size: 0.875rem;
  color: #94a3b8;
  max-width: 500px;
  line-height: 1.5;
}
.hero-actions {
  position: absolute;
  top: 2rem;
  right: 2rem;
  display: flex;
  gap: 1rem;
  align-items: center;
  z-index: 2;
}
.search-wrapper {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border-radius: 0.75rem;
  padding: 0.25rem;
}
.search-container {
  position: relative;
  display: flex;
  align-items: center;
}
.search-icon {
  position: absolute;
  left: 1rem;
  width: 1.25rem;
  height: 1.25rem;
  color: #94a3b8;
  pointer-events: none;
}
.search-input {
  background: white;
  border: none;
  padding: 0.625rem 1rem 0.625rem 2.5rem;
  border-radius: 0.75rem;
  font-size: 0.875rem;
  width: 280px;
  transition: all 0.2s;
}
.search-input:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
}
.btn-primary-modern {
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  border: none;
  padding: 0.625rem 1.25rem;
  border-radius: 0.75rem;
  color: white;
  font-weight: 600;
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}
.btn-primary-modern svg {
  width: 1.25rem;
  height: 1.25rem;
}
.btn-primary-modern:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
}
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  margin-bottom: 2rem;
}
.stat-card {
  background: white;
  border-radius: 1rem;
  padding: 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: all 0.2s;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}
.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
  border-color: var(--stat-color, #3b82f6);
}
.stat-icon-wrapper {
  width: 3rem;
  height: 3rem;
  border-radius: 1rem;
  background: rgba(var(--stat-color-rgb, 59, 130, 246), 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
}
.stat-icon-wrapper svg {
  width: 1.5rem;
  height: 1.5rem;
  color: var(--stat-color, #3b82f6);
}
.stat-info {
  flex: 1;
}
.stat-value {
  font-size: 1.5rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.2;
}
.stat-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-top: 0.25rem;
}
.records-container {
  background: white;
  border-radius: 1rem;
  border: 1px solid #e2e8f0;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}
.records-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
  background: #fafbfc;
}
.records-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 0.25rem;
}
.records-subtitle {
  font-size: 0.875rem;
  color: #64748b;
}
.table-wrapper {
  overflow-x: auto;
}
.modern-table {
  width: 100%;
  border-collapse: collapse;
}
.modern-table thead th {
  background: #f8fafc;
  padding: 1rem;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #64748b;
  border-bottom: 1px solid #e2e8f0;
  text-align: left;
}
.table-row {
  transition: all 0.15s;
}
.table-row:hover {
  background: #fafbff;
}
.modern-table tbody td {
  padding: 1rem;
  border-bottom: 1px solid #f1f5f9;
  font-size: 0.875rem;
  color: #334155;
}
.mono-text {
  font-family: 'SF Mono', monospace;
  font-weight: 600;
  color: #3b82f6;
}
.date-cell {
  color: #64748b;
  font-size: 0.813rem;
}
.employee-info, .adl-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
.employee-name, .adl-name {
  font-weight: 600;
  color: #0f172a;
}
.employee-position, .adl-sponsor {
  font-size: 0.75rem;
  color: #94a3b8;
}
.numeric-cell {
  font-weight: 500;
  font-family: 'SF Mono', monospace;
}
.amount-highlight {
  font-weight: 700;
  color: #059669;
  font-family: 'SF Mono', monospace;
}
.text-right {
  text-align: right;
}
.text-center {
  text-align: center;
}
@media (max-width: 1024px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .hero-actions {
    position: static;
    margin-top: 1rem;
    justify-content: flex-end;
  }
  .hero-section {
    padding: 1.5rem;
  }
}
@media (max-width: 768px) {
  .cash-page {
    padding: 1rem;
  }
  .stats-grid {
    grid-template-columns: 1fr;
  }
  .search-input {
    width: 100%;
  }
  .hero-actions {
    flex-direction: column;
  }
  .search-wrapper {
    width: 100%;
  }
  .search-container {
    width: 100%;
  }
  .search-input {
    width: 100%;
  }
  .btn-primary-modern {
    width: 100%;
  }
}
</style>