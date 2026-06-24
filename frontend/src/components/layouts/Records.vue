<template>
  <div class="document-records">
    <div class="page-header">
      <div class="header-title">
        <h1>Document Tracking System</h1>
        <p>Manage and track document movements across divisions</p>
      </div>
      <div class="header-actions">
        <button class="btn-secondary" @click="openDocumentTypeModal">
          <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
            <path d="M10 5v10M5 10h10" stroke="currentColor" stroke-width="2"/>
          </svg>
          Add Document Type
        </button>
        <button class="btn-primary" @click="openCreateModal">
          <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
            <path d="M10 5v10M5 10h10" stroke="currentColor" stroke-width="2"/>
          </svg>
          New Document
        </button>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon blue">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
          </svg>
        </div>
        <div class="stat-info">
          <div class="stat-value">{{ documents.length }}</div>
          <div class="stat-label">Total Documents</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon yellow">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
          </svg>
        </div>
        <div class="stat-info">
          <div class="stat-value">{{ pendingCount }}</div>
          <div class="stat-label">Pending</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon orange">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
          </svg>
        </div>
        <div class="stat-info">
          <div class="stat-value">{{ inProgressCount }}</div>
          <div class="stat-label">In Progress</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon purple">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 6L9 17l-5-5"/>
          </svg>
        </div>
        <div class="stat-info">
          <div class="stat-value">{{ completedCount }}</div>
          <div class="stat-label">Completed</div>
        </div>
      </div>
    </div>

    <div class="filters-bar">
      <div class="search-box">
        <svg viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
        </svg>
        <input v-model="searchQuery" type="text" placeholder="Search by document number or title...">
      </div>
      <select v-model="statusFilter" class="filter-select">
        <option value="all">All Status</option>
        <option value="pending">Pending</option>
        <option value="in_progress">In Progress</option>
        <option value="completed">Completed</option>
      </select>
      <select v-model="typeFilter" class="filter-select">
        <option value="all">All Document Types</option>
        <option v-for="type in documentTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
      </select>
    </div>

    <div class="table-container">
      <div class="table-wrapper">
        <table class="documents-table">
          <thead>
            <tr>
              <th>Document No.</th>
              <th>Title</th>
              <th>Type</th>
              <th>Submitted By</th>
              <th>Tracking Chain</th>
              <th>Current Location</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="doc in paginatedDocuments" :key="doc.id">
              <td class="doc-number">#{{ doc.document_number }}</td>
              <td class="doc-title">{{ doc.title }}</td>
              <td><span class="type-badge">{{ doc.type?.name || doc.type_name }}</span></td>
              <td>
                <div class="submitter-info">
                  <span class="submitter-name">{{ doc.submitter?.first_name }} {{ doc.submitter?.last_name }}</span>
                  <span class="submitter-division">{{ doc.origin_division }}</span>
                </div>
              </td>

              <td class="tracking-cell">
                <div class="tracking-chain-compact">
                  <div class="chain-node done" title="Origin">
                    <span class="node-dot"></span>
                    <div class="node-body">
                      <span class="node-name">{{ doc.submitter?.first_name }} {{ doc.submitter?.last_name }}</span>
                      <span class="node-div">{{ doc.origin_division }}</span>
                      <span class="node-time">{{ formatDate(doc.created_at) }}</span>
                    </div>
                  </div>
                  <template v-for="track in doc.tracking" :key="track.id">
                    <span class="chain-arrow">›</span>
                    <div class="chain-node"
                         :class="{
                           done: track.status === 'received',
                           current: isCurrentStep(doc, track) && track.status === 'pending'
                         }"
                         :title="track.status === 'received' ? 'Received' : 'Pending'">
                      <span class="node-dot"></span>
                      <div class="node-body">
                        <span class="node-name">{{ track.toUser?.first_name }} {{ track.toUser?.last_name }}</span>
                        <span class="node-div">{{ track.to_division }}</span>
                        <span class="node-time sent">Sent: {{ formatDate(track.created_at) }}</span>
                        <span v-if="track.received_at" class="node-time rcvd">Rcvd: {{ formatDate(track.received_at) }}</span>
                        <span v-else class="node-time pending-label">Awaiting receipt</span>
                      </div>
                    </div>
                  </template>
                </div>
              </td>

              <td class="location-cell">
                <div class="location-badge" :class="doc.status">
                  <span class="location-icon">
                    <svg v-if="doc.status === 'completed'" viewBox="0 0 16 16" fill="currentColor">
                      <path d="M13.854 3.646a.5.5 0 010 .708l-7 7a.5.5 0 01-.708 0l-3.5-3.5a.5.5 0 11.708-.708L6.5 10.293l6.646-6.647a.5.5 0 01.708 0z"/>
                    </svg>
                    <svg v-else viewBox="0 0 16 16" fill="currentColor">
                      <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                    </svg>
                  </span>
                  <div class="location-info">
                    <span class="location-name">{{ getCurrentLocationName(doc) }}</span>
                    <span class="location-div">{{ getCurrentLocationDivision(doc) }}</span>
                  </div>
                </div>
              </td>

              <td>
                <span class="status-badge" :class="doc.status">
                  {{ doc.status === 'in_progress' ? 'In Progress' : doc.status }}
                </span>
              </td>

              <td class="actions-cell">
                <button class="action-btn view" @click="viewDocument(doc)" title="View full tracking">
                  <svg viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                  </svg>
                </button>
                <button
                  class="action-btn submit"
                  @click="openSubmitModal(doc)"
                  :disabled="!canSubmit(doc)"
                  title="Forward to next division"
                >
                  <svg viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/>
                  </svg>
                </button>
                <button
                  class="action-btn receive"
                  @click="receiveDocument(doc)"
                  :disabled="!canReceive(doc)"
                  title="Receive document"
                >
                  <svg viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                  </svg>
                </button>
                <button
                  class="action-btn complete"
                  @click="completeDocument(doc)"
                  :disabled="!canComplete(doc)"
                  title="Mark as complete"
                >
                  <svg viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                  </svg>
                </button>
                <button class="action-btn edit" @click="editDocument(doc)" title="Edit">
                  <svg viewBox="0 0 20 20" fill="currentColor">
                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                  </svg>
                </button>
                <button class="action-btn delete" @click="deleteDocument(doc)" title="Delete">
                  <svg viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                  </svg>
                </button>
              </td>
            </tr>
            <tr v-if="filteredDocuments.length === 0">
              <td colspan="8" class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p>No documents found</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="pagination">
        <button @click="currentPage--" :disabled="currentPage === 1" class="page-btn">Previous</button>
        <span class="page-info">Page {{ currentPage }} of {{ totalPages }}</span>
        <button @click="currentPage++" :disabled="currentPage === totalPages" class="page-btn">Next</button>
      </div>
    </div>

    <div v-if="showViewModal" class="modal-overlay" @click.self="showViewModal = false">
      <div class="modal-container modal-wide">
        <div class="modal-header">
          <h3>Document Tracking Details</h3>
          <button class="modal-close" @click="showViewModal = false">✕</button>
        </div>
        <div class="modal-body" v-if="viewingDoc">
          <div class="doc-info-grid">
            <div class="doc-info-item">
              <span class="info-label">Document No.</span>
              <span class="info-value mono">#{{ viewingDoc.document_number }}</span>
            </div>
            <div class="doc-info-item">
              <span class="info-label">Title</span>
              <span class="info-value">{{ viewingDoc.title }}</span>
            </div>
            <div class="doc-info-item">
              <span class="info-label">Type</span>
              <span class="info-value">{{ viewingDoc.type?.name }}</span>
            </div>
            <div class="doc-info-item">
              <span class="info-label">Status</span>
              <span class="status-badge" :class="viewingDoc.status">
                {{ viewingDoc.status === 'in_progress' ? 'In Progress' : viewingDoc.status }}
              </span>
            </div>
            <div class="doc-info-item" style="grid-column: 1 / -1">
              <span class="info-label">Description</span>
              <span class="info-value">{{ viewingDoc.description || '—' }}</span>
            </div>
          </div>

          <hr class="divider">
          <h4 class="timeline-heading">Tracking History</h4>

          <div class="timeline">
            <div class="timeline-item done">
              <div class="timeline-marker">
                <div class="tl-dot done"></div>
                <div class="tl-line"></div>
              </div>
              <div class="timeline-content">
                <div class="tl-header">
                  <span class="tl-badge origin">Origin</span>
                  <span class="tl-division">{{ viewingDoc.origin_division }}</span>
                </div>
                <div class="tl-name">{{ viewingDoc.submitter?.first_name }} {{ viewingDoc.submitter?.last_name }}</div>
                <div class="tl-times">
                  <span class="tl-time-entry created">
                    <svg viewBox="0 0 16 16" fill="currentColor"><path d="M8 3.5a.5.5 0 00-1 0V9a.5.5 0 00.252.434l3.5 2a.5.5 0 00.496-.868L8 8.71V3.5z"/><path d="M8 16A8 8 0 108 0a8 8 0 000 16zm7-8A7 7 0 111 8a7 7 0 0114 0z"/></svg>
                    Created: {{ formatDate(viewingDoc.created_at) }}
                  </span>
                </div>
              </div>
            </div>

            <div
              v-for="(track, idx) in viewingDoc.tracking"
              :key="track.id"
              class="timeline-item"
              :class="{
                done: track.status === 'received',
                current: isCurrentStep(viewingDoc, track) && track.status === 'pending',
                pending: track.status === 'pending'
              }"
            >
              <div class="timeline-marker">
                <div class="tl-dot" :class="track.status === 'received' ? 'done' : 'pending'"></div>
                <div class="tl-line" v-if="idx < viewingDoc.tracking.length - 1"></div>
              </div>
              <div class="timeline-content">
                <div class="tl-header">
                  <span class="tl-badge" :class="track.status">
                    {{ track.status === 'received' ? 'Received' : 'Pending' }}
                  </span>
                  <span class="tl-division">{{ track.to_division }}</span>
                  <span v-if="isCurrentStep(viewingDoc, track)" class="tl-current-label">📍 Current Location</span>
                </div>
                <div class="tl-name">{{ track.toUser?.first_name }} {{ track.toUser?.last_name }}</div>
                <div class="tl-times">
                  <span class="tl-time-entry sent">
                    <svg viewBox="0 0 16 16" fill="currentColor"><path d="M15.964.686a.5.5 0 00-.65-.65L.767 5.855H.766l-.452.18a.5.5 0 00-.082.887l.41.26.001.002 4.995 3.178 3.178 4.995.002.002.26.41a.5.5 0 00.886-.083l6-15zm-1.833 1.89L6.637 10.07l-.215-.338a.5.5 0 00-.154-.154l-.338-.215 7.494-7.494 1.178-.471-.47 1.178z"/></svg>
                    Forwarded: {{ formatDate(track.created_at) }}
                    <span class="tl-from" v-if="track.fromUser"> by {{ track.fromUser?.first_name }} {{ track.fromUser?.last_name }}</span>
                  </span>
                  <span v-if="track.received_at" class="tl-time-entry received">
                    <svg viewBox="0 0 16 16" fill="currentColor"><path d="M10.97 4.97a.75.75 0 011.07 1.05l-3.99 4.99a.75.75 0 01-1.08.02L4.324 8.384a.75.75 0 111.06-1.06l2.094 2.093 3.473-4.425a.236.236 0 01.02-.022z"/><path d="M8 15A7 7 0 118 1a7 7 0 010 14zm0 1A8 8 0 108 0a8 8 0 000 16z"/></svg>
                    Received: {{ formatDate(track.received_at) }}
                  </span>
                  <span v-if="track.remarks" class="tl-remarks">
                    💬 {{ track.remarks }}
                  </span>
                </div>
              </div>
            </div>

            <div v-if="viewingDoc.status === 'completed'" class="timeline-item done">
              <div class="timeline-marker">
                <div class="tl-dot done completed-dot"></div>
              </div>
              <div class="timeline-content">
                <div class="tl-header">
                  <span class="tl-badge completed">Completed</span>
                </div>
                <div class="tl-name">Document processing complete</div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="showViewModal = false">Close</button>
        </div>
      </div>
    </div>

    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-container">
        <div class="modal-header">
          <h3>{{ isEditing ? 'Edit Document' : 'Create New Document' }}</h3>
          <button class="modal-close" @click="closeModal">✕</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Document Type *</label>
            <select v-model="form.document_type_id" class="form-select" @change="onTypeChange">
              <option value="">Select document type</option>
              <option v-for="type in documentTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
            </select>
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea v-model="form.description" class="form-textarea" rows="3" placeholder="Document description"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="closeModal">Cancel</button>
          <button class="btn-primary" @click="saveDocument">{{ isEditing ? 'Update' : 'Create' }}</button>
        </div>
      </div>
    </div>

    <div v-if="showDocTypeModal" class="modal-overlay" @click.self="closeDocTypeModal">
      <div class="modal-container">
        <div class="modal-header">
          <h3>{{ isEditingDocType ? 'Edit Document Type' : 'Add Document Type' }}</h3>
          <button class="modal-close" @click="closeDocTypeModal">✕</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Document Type Name *</label>
            <input v-model="docTypeForm.name" type="text" class="form-input" placeholder="e.g., Memorandum, Contract, Report">
          </div>
          </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="closeDocTypeModal">Cancel</button>
          <button class="btn-primary" @click="saveDocumentType">{{ isEditingDocType ? 'Update' : 'Add' }}</button>
        </div>
      </div>
    </div>

    <div v-if="showSubmitModal" class="modal-overlay" @click.self="closeSubmitModal">
      <div class="modal-container">
        <div class="modal-header">
          <h3>Forward Document</h3>
          <button class="modal-close" @click="closeSubmitModal">✕</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Document: <strong>{{ selectedDocument?.title }}</strong></label>
          </div>
          <div class="form-group">
            <label>Current Location</label>
            <div class="info-box">📍 {{ getCurrentLocationDivision(selectedDocument) }}</div>
          </div>
          <div class="form-group">
            <label>Forward To *</label>
            <select v-model="submitForm.to_user_id" class="form-select">
              <option value="">Select recipient</option>
              <option v-for="user in availableUsers" :key="user.user_id" :value="user.user_id">
                {{ user.first_name }} {{ user.last_name }} — {{ user.division }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Remarks (Optional)</label>
            <textarea v-model="submitForm.remarks" class="form-textarea" rows="2" placeholder="Add remarks..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="closeSubmitModal">Cancel</button>
          <button class="btn-primary" @click="submitDocument" :disabled="!submitForm.to_user_id">Forward</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue';
import Swal from 'sweetalert2';
import axios from '../../axios.js';

// ✅ Inject the API_BASE URL provided from main.js
const API_BASE = inject('API_BASE');

const documents     = ref([]);
const documentTypes = ref([]);
const users         = ref([]);
const currentUser   = ref(null);
const loading       = ref(false);
const searchQuery   = ref('');
const statusFilter  = ref('all');
const typeFilter    = ref('all');
const currentPage   = ref(1);
const itemsPerPage  = ref(10);

// Modals
const showModal       = ref(false);
const showDocTypeModal = ref(false);
const showSubmitModal = ref(false);
const showViewModal   = ref(false);
const isEditing       = ref(false);
const isEditingDocType = ref(false);
const selectedDocument = ref(null);
const selectedDocType  = ref(null);
const viewingDoc       = ref(null);

// Forms
const form        = ref({ title: '', document_type_id: '', description: '' });
const docTypeForm = ref({ name: '' });
const submitForm  = ref({ to_user_id: '', remarks: '' });

// ── Helpers ──────────────────────────────────────────────────
const formatDate = (dateString) => {
  if (!dateString) return '—';
  return new Date(dateString).toLocaleString('en-PH', {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit'
  });
};

/** Returns the last tracking entry (active step) */
const getLastTracking = (doc) =>
  doc.tracking?.length ? doc.tracking[doc.tracking.length - 1] : null;

/** True when this tracking entry is the active (last) one */
const isCurrentStep = (doc, track) =>
  getLastTracking(doc)?.id === track.id;

/** Name of person currently holding the document */
const getCurrentLocationName = (doc) => {
  if (doc.status === 'completed') return 'Completed';
  const last = getLastTracking(doc);
  if (!last) {
    const s = doc.submitter;
    return s ? `${s.first_name ?? ''} ${s.last_name ?? ''}`.trim() : '—';
  }
  const u = last.toUser;
  if (u && u.first_name) return `${u.first_name} ${u.last_name ?? ''}`.trim();
  return last.to_division || '—';
};

/** Division currently holding the document */
const getCurrentLocationDivision = (doc) => {
  if (!doc) return '—';
  if (doc.status === 'completed') return 'Process Completed';
  const last = getLastTracking(doc);
  return last ? (last.to_division || '—') : (doc.origin_division || '—');
};

// ── Computed ─────────────────────────────────────────────────
const filteredDocuments = computed(() => {
  let list = [...documents.value];
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter(d =>
      d.document_number?.toLowerCase().includes(q) ||
      d.title?.toLowerCase().includes(q)
    );
  }
  if (statusFilter.value !== 'all') list = list.filter(d => d.status === statusFilter.value);
  if (typeFilter.value   !== 'all') list = list.filter(d => d.document_type_id === typeFilter.value);
  return list;
});

const totalPages         = computed(() => Math.max(1, Math.ceil(filteredDocuments.value.length / itemsPerPage.value)));
const paginatedDocuments = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  return filteredDocuments.value.slice(start, start + itemsPerPage.value);
});

const pendingCount    = computed(() => documents.value.filter(d => d.status === 'pending').length);
const inProgressCount = computed(() => documents.value.filter(d => d.status === 'in_progress').length);
const completedCount  = computed(() => documents.value.filter(d => d.status === 'completed').length);

const availableUsers = computed(() =>
  users.value.filter(u => u.user_id !== currentUser.value?.user_id)
);

// ── Permission helpers ────────────────────────────────────────
const canSubmit = (doc) => {
  if (!doc || doc.status === 'completed' || doc.status === 'rejected') return false;
  const last = getLastTracking(doc);
  if (!last) return doc.submitted_by === currentUser.value?.user_id;
  return last.to_user_id === currentUser.value?.user_id && last.status === 'received';
};

const canReceive = (doc) => {
  if (!doc || doc.status === 'completed' || doc.status === 'rejected') return false;
  const last = getLastTracking(doc);
  return last &&
    last.to_user_id === currentUser.value?.user_id &&
    last.status === 'pending';
};

const canComplete = (doc) => {
  if (!doc || doc.status !== 'in_progress') return false;
  const last = getLastTracking(doc);
  return last &&
    last.to_user_id === currentUser.value?.user_id &&
    last.status === 'received';
};

// ── API calls ─────────────────────────────────────────────────
const fetchDocuments = async () => {
  loading.value = true;
  try {
    const res = await axios.get(`${API_BASE}/documents`);
    documents.value = res.data;
  } catch {
    Swal.fire('Error', 'Failed to load documents', 'error');
  } finally {
    loading.value = false;
  }
};

const fetchDocumentTypes = async () => {
  try { const res = await axios.get(`${API_BASE}/document-types`); documentTypes.value = res.data; }
  catch (err) { console.error(err); }
};

const fetchUsers = async () => {
  try { const res = await axios.get(`${API_BASE}/users`); users.value = res.data; }
  catch (err) { console.error(err); }
};

const fetchCurrentUser = async () => {
  try { const res = await axios.get(`${API_BASE}/me`); currentUser.value = res.data; }
  catch (err) { console.error(err); }
};

// ── Document CRUD ─────────────────────────────────────────────
const onTypeChange = () => {
  const type = documentTypes.value.find(t => t.id === form.value.document_type_id);
  if (type && !isEditing.value) {
    form.value.title = type.name;
  }
};

const openCreateModal = () => {
  isEditing.value = false;
  form.value = { title: '', document_type_id: '', description: '' };
  showModal.value = true;
};

const editDocument = (doc) => {
  isEditing.value = true;
  selectedDocument.value = doc;
  form.value = { title: doc.title, document_type_id: doc.document_type_id, description: doc.description || '' };
  showModal.value = true;
};

const saveDocument = async () => {
  if (!form.value.document_type_id) {
    Swal.fire('Error', 'Please select a document type', 'error');
    return;
  }
  if (!form.value.title) {
    const type = documentTypes.value.find(t => t.id === form.value.document_type_id);
    form.value.title = type ? type.name : 'Untitled Document';
  }
  try {
    if (isEditing.value) {
      await axios.put(`${API_BASE}/documents/${selectedDocument.value.id}`, form.value);
      Swal.fire('Success', 'Document updated', 'success');
    } else {
      await axios.post(`${API_BASE}/documents`, form.value);
      Swal.fire('Success', 'Document created', 'success');
    }
    closeModal();
    await fetchDocuments();
  } catch (err) {
    Swal.fire('Error', err.response?.data?.message || 'Failed to save document', 'error');
  }
};

const deleteDocument = async (doc) => {
  const result = await Swal.fire({
    title: 'Delete Document?',
    text: `"${doc.title}" will be permanently deleted.`,
    icon: 'warning', showCancelButton: true,
    confirmButtonText: 'Yes, delete', confirmButtonColor: '#ef4444'
  });
  if (result.isConfirmed) {
    try {
      await axios.delete(`${API_BASE}/documents/${doc.id}`);
      Swal.fire('Deleted', 'Document deleted', 'success');
      await fetchDocuments();
    } catch (err) {
      Swal.fire('Error', err.response?.data?.message || 'Failed to delete', 'error');
    }
  }
};

// ── Document Type ─────────────────────────────────────────────
const openDocumentTypeModal = () => {
  isEditingDocType.value = false;
  docTypeForm.value = { name: '' };
  showDocTypeModal.value = true;
};

const saveDocumentType = async () => {
  if (!docTypeForm.value.name) {
    Swal.fire('Error', 'Please enter a document type name', 'error');
    return;
  }
  try {
    if (isEditingDocType.value) {
      await axios.put(`${API_BASE}/document-types/${selectedDocType.value.id}`, docTypeForm.value);
      Swal.fire('Success', 'Document type updated', 'success');
    } else {
      await axios.post(`${API_BASE}/document-types`, docTypeForm.value);
      Swal.fire('Success', 'Document type added', 'success');
    }
    closeDocTypeModal();
    await fetchDocumentTypes();
  } catch (err) {
    Swal.fire('Error', err.response?.data?.message || 'Failed to save document type', 'error');
  }
};

// ── View ──────────────────────────────────────────────────────
const viewDocument = (doc) => {
  viewingDoc.value = doc;
  showViewModal.value = true;
};

// ── Submit / Receive / Complete ───────────────────────────────
const openSubmitModal = (doc) => {
  selectedDocument.value = doc;
  submitForm.value = { to_user_id: '', remarks: '' };
  showSubmitModal.value = true;
};

const submitDocument = async () => {
  if (!submitForm.value.to_user_id) {
    Swal.fire('Error', 'Please select a recipient', 'error');
    return;
  }
  try {
    await axios.post(`${API_BASE}/documents/${selectedDocument.value.id}/submit`, submitForm.value);
    Swal.fire('Success', 'Document forwarded successfully', 'success');
    closeSubmitModal();
    await fetchDocuments();
  } catch (err) {
    Swal.fire('Error', err.response?.data?.message || 'Failed to forward document', 'error');
  }
};

const receiveDocument = async (doc) => {
  const result = await Swal.fire({
    title: 'Receive Document?',
    html: `Confirm that you have received <strong>${doc.title}</strong>.`,
    icon: 'question', showCancelButton: true,
    confirmButtonText: 'Yes, I received it', confirmButtonColor: '#10b981'
  });
  if (result.isConfirmed) {
    try {
      await axios.post(`${API_BASE}/documents/${doc.id}/receive`);
      Swal.fire('Received', 'Document marked as received.', 'success');
      await fetchDocuments();
    } catch (err) {
      Swal.fire('Error', err.response?.data?.message || 'Failed to receive document', 'error');
    }
  }
};

const completeDocument = async (doc) => {
  const result = await Swal.fire({
    title: 'Complete Document?',
    html: `Mark <strong>${doc.title}</strong> as fully processed and complete?`,
    icon: 'question', showCancelButton: true,
    confirmButtonText: 'Yes, complete it', confirmButtonColor: '#8b5cf6'
  });
  if (result.isConfirmed) {
    try {
      await axios.post(`${API_BASE}/documents/${doc.id}/complete`);
      Swal.fire('Completed!', 'Document has been marked as complete.', 'success');
      await fetchDocuments();
    } catch (err) {
      Swal.fire('Error', err.response?.data?.message || 'Failed to complete document', 'error');
    }
  }
};

// ── Close modals ──────────────────────────────────────────────
const closeModal        = () => { showModal.value = false; selectedDocument.value = null; };
const closeDocTypeModal = () => { showDocTypeModal.value = false; selectedDocType.value = null; };
const closeSubmitModal  = () => { showSubmitModal.value = false; selectedDocument.value = null; };

onMounted(async () => {
  await Promise.all([fetchDocuments(), fetchDocumentTypes(), fetchUsers(), fetchCurrentUser()]);
});
</script>

<style scoped>
/* (All original styles remain identical and are fully preserved here) */
.document-records { padding: 1.5rem; background: #f1f5f9; min-height: 100vh; font-family: 'Inter', sans-serif; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
.header-title h1 { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0 0 0.25rem; }
.header-title p  { color: #64748b; font-size: 0.875rem; margin: 0; }
.header-actions  { display: flex; gap: 0.75rem; flex-wrap: wrap; }
.btn-primary, .btn-secondary { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; border: none; transition: background 0.2s; }
.btn-primary           { background: #3b82f6; color: #fff; }
.btn-primary:hover     { background: #2563eb; }
.btn-secondary         { background: #fff; color: #374151; border: 1px solid #d1d5db; }
.btn-secondary:hover   { background: #f9fafb; }
.btn-primary svg, .btn-secondary svg { width: 16px; height: 16px; flex-shrink: 0; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.stat-card { background: #fff; border-radius: 0.75rem; padding: 1.25rem; display: flex; align-items: center; gap: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,.07); }
.stat-icon { width: 48px; height: 48px; min-width: 48px; border-radius: 0.625rem; display: flex; align-items: center; justify-content: center; }
.stat-icon svg { width: 24px; height: 24px; flex-shrink: 0; }
.stat-icon.blue   { background: #eff6ff; color: #3b82f6; }
.stat-icon.yellow { background: #fefce8; color: #eab308; }
.stat-icon.orange { background: #fff7ed; color: #f97316; }
.stat-icon.purple { background: #f5f3ff; color: #8b5cf6; }
.stat-info   { flex: 1; }
.stat-value  { font-size: 1.75rem; font-weight: 700; color: #1e293b; line-height: 1; }
.stat-label  { font-size: 0.8rem; color: #64748b; margin-top: 0.25rem; }
.filters-bar { display: flex; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap; align-items: center; }
.search-box { display: flex; align-items: center; gap: 0.5rem; background: #fff; border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.5rem 0.75rem; flex: 1; min-width: 200px; }
.search-box svg   { width: 16px; height: 16px; color: #9ca3af; flex-shrink: 0; }
.search-box input { border: none; outline: none; font-size: 0.875rem; color: #374151; width: 100%; background: transparent; }
.filter-select { padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; color: #374151; background: #fff; cursor: pointer; outline: none; }
.filter-select:focus { border-color: #3b82f6; }
.table-container { background: #fff; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,.07); overflow: hidden; }
.table-wrapper { overflow-x: auto; }
.documents-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.documents-table thead { background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
.documents-table th { padding: 0.875rem 1rem; text-align: left; font-weight: 600; color: #475569; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; white-space: nowrap; }
.documents-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .15s; }
.documents-table tbody tr:hover { background: #f8fafc; }
.documents-table td { padding: 0.875rem 1rem; color: #334155; vertical-align: top; }
.doc-number { font-family: monospace; font-weight: 600; color: #3b82f6; white-space: nowrap; }
.doc-title  { font-weight: 500; color: #1e293b; max-width: 180px; }
.submitter-info { display: flex; flex-direction: column; gap: 0.15rem; }
.submitter-name { font-weight: 500; font-size: 0.82rem; color: #1e293b; }
.submitter-division { font-size: 0.72rem; color: #64748b; }
.tracking-cell { min-width: 280px; max-width: 380px; }
.tracking-chain-compact { display: flex; align-items: flex-start; flex-wrap: wrap; gap: 0.15rem; }
.chain-arrow { color: #94a3b8; font-size: 1rem; font-weight: 700; align-self: flex-start; margin-top: 0.3rem; padding: 0 0.1rem; }
.chain-node { display: flex; align-items: flex-start; gap: 0.3rem; padding: 0.3rem 0.45rem; border-radius: 0.375rem; border: 1px solid #e2e8f0; background: #f8fafc; font-size: 0.72rem; transition: all .15s; }
.chain-node.done    { background: #f0fdf4; border-color: #bbf7d0; }
.chain-node.current { background: #eff6ff; border-color: #93c5fd; box-shadow: 0 0 0 2px rgba(59,130,246,.2); }
.node-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; margin-top: 3px; background: #cbd5e1; }
.chain-node.done .node-dot    { background: #10b981; }
.chain-node.current .node-dot { background: #3b82f6; }
.node-body { display: flex; flex-direction: column; gap: 0.05rem; }
.node-name { font-weight: 600; color: #1e293b; font-size: 0.72rem; }
.node-div  { color: #64748b; font-size: 0.67rem; }
.node-time { font-size: 0.62rem; color: #94a3b8; }
.node-time.sent    { color: #6366f1; }
.node-time.rcvd    { color: #10b981; }
.node-time.pending-label { color: #f59e0b; font-style: italic; }
.location-cell { min-width: 160px; }
.location-badge { display: flex; align-items: flex-start; gap: 0.4rem; padding: 0.4rem 0.6rem; border-radius: 0.5rem; border: 1px solid #e2e8f0; background: #f8fafc; }
.location-badge.pending     { background: #fffbeb; border-color: #fde68a; }
.location-badge.in_progress { background: #eff6ff; border-color: #bfdbfe; }
.location-badge.completed   { background: #f0fdf4; border-color: #bbf7d0; }
.location-icon { flex-shrink: 0; margin-top: 1px; }
.location-icon svg { width: 13px; height: 13px; }
.location-badge.pending svg, .location-badge.in_progress svg { color: #3b82f6; }
.location-badge.completed svg { color: #10b981; }
.location-info { display: flex; flex-direction: column; gap: 0.1rem; }
.location-name { font-weight: 600; font-size: 0.78rem; color: #1e293b; }
.location-div  { font-size: 0.68rem; color: #64748b; }
.type-badge { display: inline-block; padding: 0.2rem 0.6rem; background: #e0f2fe; color: #0369a1; border-radius: 9999px; font-size: 0.72rem; font-weight: 500; white-space: nowrap; }
.status-badge { display: inline-block; padding: 0.25rem 0.7rem; border-radius: 9999px; font-size: 0.72rem; font-weight: 600; text-transform: capitalize; white-space: nowrap; }
.status-badge.pending     { background: #fef9c3; color: #a16207; }
.status-badge.in_progress { background: #dbeafe; color: #1d4ed8; }
.status-badge.completed   { background: #dcfce7; color: #15803d; }
.status-badge.rejected    { background: #fee2e2; color: #b91c1c; }
.actions-cell { white-space: nowrap; }
.action-btn { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 0.375rem; border: none; cursor: pointer; transition: background .15s, opacity .15s; margin-right: 2px; }
.action-btn svg     { width: 14px; height: 14px; }
.action-btn:disabled { opacity: .3; cursor: not-allowed; }
.action-btn.view    { background: #eff6ff; color: #3b82f6; }
.action-btn.view:hover:not(:disabled)     { background: #dbeafe; }
.action-btn.submit  { background: #f0fdf4; color: #16a34a; }
.action-btn.submit:hover:not(:disabled)   { background: #dcfce7; }
.action-btn.receive { background: #ecfdf5; color: #10b981; }
.action-btn.receive:hover:not(:disabled)  { background: #d1fae5; }
.action-btn.complete { background: #f5f3ff; color: #7c3aed; }
.action-btn.complete:hover:not(:disabled) { background: #ede9fe; }
.action-btn.edit    { background: #fefce8; color: #ca8a04; }
.action-btn.edit:hover:not(:disabled)     { background: #fef9c3; }
.action-btn.delete  { background: #fff1f2; color: #e11d48; }
.action-btn.delete:hover:not(:disabled)   { background: #ffe4e6; }
.empty-state { text-align: center; padding: 3rem 1rem !important; color: #94a3b8; }
.empty-state svg { width: 48px; height: 48px; margin: 0 auto 0.75rem; display: block; color: #cbd5e1; }
.empty-state p   { margin: 0; font-size: 0.9rem; }
.pagination { display: flex; align-items: center; justify-content: center; gap: 1rem; padding: 1rem; border-top: 1px solid #f1f5f9; }
.page-btn { padding: 0.4rem 0.9rem; border: 1px solid #d1d5db; border-radius: 0.375rem; background: #fff; font-size: 0.875rem; cursor: pointer; color: #374151; transition: background .15s; }
.page-btn:hover:not(:disabled) { background: #f3f4f6; }
.page-btn:disabled { opacity: .4; cursor: not-allowed; }
.page-info { font-size: 0.875rem; color: #64748b; }
.modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,.55); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem; }
.modal-container { background: #fff; border-radius: 0.75rem; width: 100%; max-width: 520px; box-shadow: 0 20px 60px rgba(0,0,0,.2); overflow: hidden; max-height: 90vh; display: flex; flex-direction: column; }
.modal-wide { max-width: 720px; }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem; border-bottom: 1px solid #e2e8f0; flex-shrink: 0; }
.modal-header h3 { font-size: 1.1rem; font-weight: 600; color: #1e293b; margin: 0; }
.modal-close { background: none; border: none; font-size: 1.1rem; cursor: pointer; color: #94a3b8; padding: 0.2rem 0.4rem; border-radius: 0.25rem; transition: color .15s; }
.modal-close:hover { color: #475569; }
.modal-body { padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; overflow-y: auto; }
.modal-footer { display: flex; justify-content: flex-end; gap: 0.75rem; padding: 1rem 1.5rem; border-top: 1px solid #e2e8f0; background: #f8fafc; flex-shrink: 0; }
.doc-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.doc-info-item { display: flex; flex-direction: column; gap: 0.25rem; }
.info-label { font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: #94a3b8; }
.info-value { font-size: 0.875rem; color: #1e293b; }
.info-value.mono { font-family: monospace; color: #3b82f6; font-weight: 600; }
.divider { border: none; border-top: 1px solid #e2e8f0; margin: 0.5rem 0; }
.timeline-heading { font-size: 0.9rem; font-weight: 600; color: #475569; margin: 0 0 1rem; }
.timeline { display: flex; flex-direction: column; }
.timeline-item { display: flex; gap: 0; }
.timeline-marker { display: flex; flex-direction: column; align-items: center; margin-right: 1rem; flex-shrink: 0; }
.tl-dot { width: 14px; height: 14px; border-radius: 50%; flex-shrink: 0; background: #cbd5e1; border: 2px solid #e2e8f0; z-index: 1; margin-top: 2px; }
.tl-dot.done          { background: #10b981; border-color: #6ee7b7; }
.tl-dot.pending       { background: #f59e0b; border-color: #fde68a; }
.tl-dot.completed-dot { background: #8b5cf6; border-color: #c4b5fd; }
.tl-line { width: 2px; flex: 1; background: #e2e8f0; margin: 4px 0; min-height: 20px; }
.timeline-item.done   .tl-line { background: #bbf7d0; }
.timeline-item.current .tl-line { background: #bfdbfe; }
.timeline-content { padding-bottom: 1.25rem; flex: 1; }
.tl-header { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 0.2rem; }
.tl-badge { display: inline-block; padding: 0.15rem 0.55rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; }
.tl-badge.origin   { background: #f1f5f9; color: #475569; }
.tl-badge.pending  { background: #fef9c3; color: #a16207; }
.tl-badge.received { background: #dcfce7; color: #15803d; }
.tl-badge.completed { background: #ede9fe; color: #7c3aed; }
.tl-division { font-size: 0.78rem; font-weight: 500; color: #64748b; }
.tl-current-label { font-size: 0.72rem; color: #3b82f6; font-weight: 600; }
.tl-name { font-size: 0.9rem; font-weight: 600; color: #1e293b; margin-bottom: 0.3rem; }
.tl-times { display: flex; flex-direction: column; gap: 0.25rem; }
.tl-time-entry { display: flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; color: #64748b; }
.tl-time-entry svg { width: 12px; height: 12px; flex-shrink: 0; }
.tl-time-entry.created  { color: #64748b; }
.tl-time-entry.sent     { color: #6366f1; }
.tl-time-entry.received { color: #10b981; }
.tl-from { color: #94a3b8; font-style: italic; }
.tl-remarks { font-size: 0.75rem; color: #78716c; font-style: italic; margin-top: 0.1rem; }
.form-group { display: flex; flex-direction: column; gap: 0.4rem; }
.form-group label { font-size: 0.875rem; font-weight: 500; color: #374151; }
.form-input, .form-select, .form-textarea { padding: 0.55rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; color: #374151; background: #fff; outline: none; transition: border-color .15s, box-shadow .15s; width: 100%; box-sizing: border-box; font-family: inherit; }
.form-input:focus, .form-select:focus, .form-textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15); }
.form-textarea { resize: vertical; min-height: 70px; }
.info-box { padding: 0.55rem 0.75rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.875rem; color: #475569; }
@media (max-width: 768px) {
  .document-records { padding: 1rem; }
  .page-header { flex-direction: column; align-items: flex-start; }
  .header-actions { flex-direction: column; width: 100%; }
  .btn-primary, .btn-secondary { width: 100%; justify-content: center; }
  .filters-bar { flex-direction: column; }
  .search-box, .filter-select { width: 100%; }
  .stat-card { padding: 1rem; }
  .stat-value { font-size: 1.4rem; }
  .doc-info-grid { grid-template-columns: 1fr; }
  .tracking-chain-compact { flex-direction: column; }
  .chain-arrow { transform: rotate(90deg); align-self: center; }
}
</style>