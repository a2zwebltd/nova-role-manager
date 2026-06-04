<template>
  <div class="px-3 md:px-0">
    <Heading class="mb-6">{{ toolName }}</Heading>

    <Card class="overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800">
        <div v-if="guards && Object.keys(guards).length > 0" class="flex items-center gap-1">
          <button
            v-for="guardName in orderedGuardNames"
            :key="guardName"
            @click="selectedGuard = guardName; onGuardChange()"
            :class="[
              'px-4 py-1.5 text-sm font-medium transition-colors duration-150',
              selectedGuard === guardName
                ? 'text-primary-600 dark:text-primary-400 border-b-2 border-primary-600 dark:border-primary-400'
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
            ]"
          >
            {{ formatGuardName(guards[guardName].name) }}
          </button>
        </div>
      </div>

      <div v-if="isLoading" class="p-6 text-sm text-gray-500 dark:text-gray-400">Loading permissions…</div>
      <div v-else-if="loadError" class="p-6 text-sm text-red-600 dark:text-red-400">Failed to load permissions.</div>
      <div v-else-if="!selectedGuard || !flattenedSections.length" class="p-6 text-sm text-gray-500 dark:text-gray-400">No data available. Please select a guard.</div>
      <div v-else class="p-6">
        <div class="mb-4">
          <input
            v-model="searchTerm"
            type="text"
            placeholder="Search by permission name, section, or group..."
            class="w-full px-4 py-2 rounded-md border border-gray-300 dark:border-gray-700 text-sm text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
          />
        </div>
        <div v-if="searchTerm.trim() && filteredSections.length === 0" class="mb-4 text-sm text-gray-500 dark:text-gray-400">No results found for "{{ searchTerm }}"</div>
        <div v-else class="border border-gray-200 dark:border-gray-800 rounded-lg overflow-y-auto overflow-x-auto" style="height: 600px; max-height: 600px;">
          <table class="min-w-[1200px] w-full border-separate border-spacing-0">
            <thead style="position: sticky; top: 0; z-index: 20; border-bottom: 2px solid rgb(229 231 235);" class="bg-white dark:bg-gray-900 shadow-sm dark:[border-bottom-color:rgb(31_41_55)]">
              <tr>
                <th scope="col" style="position: sticky; left: 0; z-index: 30; max-width: 200px; width: 200px;" class="bg-white dark:bg-gray-900 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 px-2 py-3 border-b border-gray-200 dark:border-gray-800">Section / Group</th>
                <th 
                  v-for="role in currentRoles" 
                  :key="role.id" 
                  scope="col" 
                  :class="[
                    'text-left text-sm font-semibold uppercase tracking-wider border-b min-w-[200px] transition-all duration-200 relative group p-0',
                    isRoleEditable(role.id) 
                      ? hasRoleChanges(role.id)
                        ? 'bg-green-500 dark:bg-green-600 text-white border-green-600 dark:border-green-700 shadow-lg'
                        : 'bg-primary-500 dark:bg-primary-600 text-white border-primary-600 dark:border-primary-700 shadow-lg'
                      : role.is_editable === false
                        ? 'bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500 border-gray-200 dark:border-gray-700'
                        : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-800 hover:bg-gray-100 dark:hover:bg-gray-800 hover:shadow-md hover:border-gray-300 dark:hover:border-gray-700'
                  ]"
                  style="height: 100%;"
                >
                  <div class="flex items-stretch" style="min-height: 48px;">
                    <div 
                      @click="role.is_editable !== false && toggleRoleEdit(role.id)"
                      class="flex-1 flex items-center gap-2 px-4 py-3"
                      :class="role.is_editable === false ? 'cursor-not-allowed' : 'cursor-pointer'"
                    >
                      <span class="relative">
                        <span v-if="isRoleEditable(role.id)">
                          <span v-if="hasRoleChanges(role.id)">SAVE </span>
                          <span v-else>EDIT </span>
                        </span>{{ role.name }}<span v-if="isRoleEditable(role.id) && !hasRoleChanges(role.id)" class="text-xs font-normal normal-case opacity-75"> (no changes)</span>
                        <span v-if="!isRoleEditable(role.id)" class="absolute -bottom-1 left-0 right-0 h-0.5 bg-blue-500 opacity-0 group-hover:opacity-100 transition-opacity"></span>
                      </span>
                      <svg 
                        v-if="!isRoleEditable(role.id) && role.is_editable !== false" 
                        class="w-4 h-4 opacity-60 group-hover:opacity-100 transition-opacity" 
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                      </svg>
                      <svg 
                        v-else-if="isRoleEditable(role.id)" 
                        class="w-4 h-4" 
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                      </svg>
                    </div>
                    <button
                      v-if="role.can_view_audit_logs"
                      @click="$inertia.visit(role.audit_log_url || '/nova/resources/audits')"
                      :disabled="role.is_editable === false"
                      class="flex items-center justify-center px-3 border-l border-r cursor-pointer transition-colors self-stretch"
                      :class="[
                        isRoleEditable(role.id)
                          ? 'border-white/30 hover:bg-white/10'
                          : role.is_editable === false
                            ? 'border-gray-300 dark:border-gray-600 cursor-not-allowed opacity-50'
                            : 'border-gray-300 dark:border-gray-700 hover:bg-gray-200 dark:hover:bg-gray-700'
                      ]"
                      title="View audit logs"
                    >
                      <svg 
                        class="w-5 h-5" 
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                        :class="isRoleEditable(role.id) ? 'text-white' : 'text-gray-500 dark:text-gray-400'"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </button>
                  </div>
                </th>
              </tr>
            </thead>
            <tbody class="text-sm bg-white dark:bg-gray-900">
            <template v-for="(section, sectionIndex) in filteredSections" :key="section.path">
              <tr>
                <th scope="row" style="max-width: 200px; width: 200px;" class="sticky left-0 z-10 bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 font-semibold px-2 py-2 border-b border-gray-200 dark:border-gray-700 text-left" v-html="highlightText(section.sectionName)"></th>
                <td 
                  v-for="role in currentRoles" 
                  :key="`section-${section.path}-${role.id}`"
                  :class="[
                    'px-4 py-2 border-b',
                    isRoleEditable(role.id)
                      ? 'bg-primary-50/50 dark:bg-primary-900/10 border-primary-200 dark:border-primary-800'
                      : 'bg-gray-100 dark:bg-gray-800 border-gray-200 dark:border-gray-700'
                  ]"
                ></td>
              </tr>
              <tr v-for="(group, groupIndex) in section.groups" :key="`${section.path}-${group.path}`">
                <th scope="row" style="max-width: 200px; width: 200px;" class="sticky left-0 z-10 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 font-medium px-2 py-3 border-b border-gray-200 dark:border-gray-800 align-top text-xs break-words" v-html="highlightText(group.groupName)"></th>
                <td 
                  v-for="role in currentRoles" 
                  :key="`${group.path}-${role.id}`" 
                  :class="[
                    'px-4 py-3 border-b align-top min-w-[200px]',
                    isRoleEditable(role.id)
                      ? 'bg-primary-50/50 dark:bg-primary-900/10 border-primary-200 dark:border-primary-800'
                      : 'border-gray-200 dark:border-gray-800'
                  ]"
                >
                  <div class="flex flex-col gap-2">
                    <label 
                      v-for="perm in group.permissions" 
                      :key="`${role.id}-${perm.id}`" 
                      :class="[
                        'flex items-center gap-2 px-2 py-1 rounded',
                        isRoleEditable(role.id) ? 'cursor-pointer' : 'cursor-not-allowed',
                        isPermissionChanged(role.id, perm.id) ? 'bg-yellow-100 dark:bg-yellow-900/30' : ''
                      ]"
                    >
                      <input 
                        type="checkbox" 
                        :checked="hasPermission(role.id, perm.id)" 
                        :disabled="!isRoleEditable(role.id) || !!togglingPermissions[`${role.id}-${perm.id}`]" 
                        @change="togglePermission(role.id, perm.id, $event.target.checked)" 
                        class="w-4 h-4 flex-shrink-0 text-primary-600 border-gray-300 rounded focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:checked:bg-primary-600 dark:checked:border-primary-600 disabled:opacity-50 disabled:cursor-not-allowed" 
                        :class="isRoleEditable(role.id) ? 'cursor-pointer' : 'cursor-not-allowed'" 
                      />
                      <span 
                        class="text-xs whitespace-normal break-words" 
                        :class="isRoleEditable(role.id) ? '' : 'text-gray-400 dark:text-gray-500'" 
                        v-html="highlightText(perm.label)"
                      ></span>
                    </label>
                  </div>
                </td>
              </tr>
            </template>
            </tbody>
          </table>
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, shallowRef } from 'vue'
import { formatGuardName } from '../utils'

defineProps({
  toolName: {
    type: String,
    default: 'Roles and Permissions Manager',
  },
})

const guards = ref({})
const selectedGuard = ref(null)
const isLoading = ref(true)
const loadError = ref(false)
const rolePermissions = ref({}) // { roleId: [permissionIds] }
const rolePermissionSets = ref({}) // { roleId: Set<permissionId> } - for O(1) lookups
const togglingPermissions = ref({}) // Track permissions being toggled: { "roleId-permissionId": true }
const searchTerm = ref('')
const isReadOnly = ref(true) // Read-only mode by default
const editableRoleId = ref(null) // Currently editable role ID (null = read-only mode)
const originalRolePermissions = ref({}) // Store original permissions when entering edit mode: { roleId: Set<permissionId> }

// Flattened structure: sections with groups
const flattenedSections = shallowRef([])

// Define guard order and compute ordered guard names
const guardOrder = [] // Empty - will use whatever comes from backend
const orderedGuardNames = computed(() => {
  const guardNames = Object.keys(guards.value)
  const ordered = []
  
  // Add guards in the defined order
  for (const orderedGuard of guardOrder) {
    if (guardNames.includes(orderedGuard)) {
      ordered.push(orderedGuard)
    }
  }
  
  // Add any remaining guards that weren't in the order list
  for (const guardName of guardNames) {
    if (!ordered.includes(guardName)) {
      ordered.push(guardName)
    }
  }
  
  return ordered
})

// Memoized sorted roles - only recompute when rolePermissions changes
const currentRoles = computed(() => {
  if (!selectedGuard.value || !guards.value[selectedGuard.value]) {
    return []
  }
  const roles = guards.value[selectedGuard.value].roles || []
  // Filter to ensure only roles with matching guard_name are shown
  return roles.filter(role => role.guard_name === selectedGuard.value)
})

// Filter sections based on search term
const filteredSections = computed(() => {
  if (!searchTerm.value.trim()) {
    return flattenedSections.value
  }

  const term = searchTerm.value.toLowerCase().trim()
  const filtered = []

  for (const section of flattenedSections.value) {
    const sectionMatches = section.sectionName.toLowerCase().includes(term)
    let hasAnyMatch = sectionMatches
    const filteredGroups = []

    for (const group of section.groups) {
      const groupMatches = group.groupName.toLowerCase().includes(term)
      const matchingPermissions = group.permissions.filter(perm => 
        perm.label.toLowerCase().includes(term) || perm.name.toLowerCase().includes(term)
      )
      const hasMatchingPermissions = matchingPermissions.length > 0

      if (groupMatches || hasMatchingPermissions) {
        hasAnyMatch = true
        filteredGroups.push({
          ...group,
          permissions: hasMatchingPermissions && !groupMatches ? matchingPermissions : group.permissions
        })
      }
    }

    // HIDE section if nothing matches
    if (hasAnyMatch) {
      filtered.push({
        ...section,
        groups: filteredGroups
      })
    }
  }

  return filtered
})

function highlightText(text) {
  if (!searchTerm.value.trim()) {
    return escapeHtml(String(text))
  }

  const term = searchTerm.value.trim()
  const regex = new RegExp(`(${escapeRegex(term)})`, 'gi')
  const escaped = escapeHtml(String(text))
  
  return escaped.replace(regex, '<mark class="bg-yellow-200 dark:bg-yellow-800">$1</mark>')
}

function escapeHtml(text) {
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  }
  return text.replace(/[&<>"']/g, m => map[m])
}

function escapeRegex(text) {
  return text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
}

function onGuardChange() {
  if (selectedGuard.value) {
    // Clear old role permissions when guard changes
    rolePermissions.value = {}
    rolePermissionSets.value = {}
    // Reset to read-only mode when guard changes
    isReadOnly.value = true
    editableRoleId.value = null
    originalRolePermissions.value = {}
    flattenCategories()
    fetchRolePermissions()
  }
}

// Optimized O(1) permission check using Set
function hasPermission(roleId, permissionId) {
  const permissionSet = rolePermissionSets.value[roleId]
  return permissionSet ? permissionSet.has(permissionId) : false
}

// Check if a specific role is editable
function isRoleEditable(roleId) {
  const role = currentRoles.value.find(r => r.id === roleId)
  if (!role || role.is_editable === false) {
    return false
  }
  return !isReadOnly.value && editableRoleId.value === roleId
}

// Check if a permission has been changed from original state
function isPermissionChanged(roleId, permissionId) {
  if (!isRoleEditable(roleId)) {
    return false
  }
  
  const original = originalRolePermissions.value[roleId]
  const current = rolePermissionSets.value[roleId]
  
  if (!original || !current) {
    return false
  }
  
  const wasInOriginal = original.has(permissionId)
  const isInCurrent = current.has(permissionId)
  
  return wasInOriginal !== isInCurrent
}


// Get role name by ID
function getRoleName(roleId) {
  const role = currentRoles.value.find(r => r.id === roleId)
  return role ? role.name : ''
}

// Check if role has unsaved changes
function hasRoleChanges(roleId) {
  if (!editableRoleId.value || editableRoleId.value !== roleId) {
    return false
  }
  
  const original = originalRolePermissions.value[roleId]
  const current = rolePermissionSets.value[roleId]
  
  if (!original || !current) {
    return false
  }
  
  // Compare sets - if sizes differ or sets are not equal, there are changes
  if (original.size !== current.size) {
    return true
  }
  
  // Check if all permissions in original are in current and vice versa
  for (const permId of original) {
    if (!current.has(permId)) {
      return true
    }
  }
  
  for (const permId of current) {
    if (!original.has(permId)) {
      return true
    }
  }
  
  return false
}

// Discard changes for a role and restore original state
function discardRoleChanges(roleId) {
  const original = originalRolePermissions.value[roleId]
  if (original) {
    // Restore original permissions
    rolePermissionSets.value[roleId] = new Set(original)
    rolePermissions.value[roleId] = Array.from(original)
  } else {
    // If no original state, clear all permissions
    rolePermissionSets.value[roleId] = new Set()
    rolePermissions.value[roleId] = []
  }
}

// Toggle edit mode for a specific role
function toggleRoleEdit(roleId) {
  const role = currentRoles.value.find(r => r.id === roleId)
  if (!role || role.is_editable === false) {
    Nova.error('This role cannot be edited')
    return
  }
  
  if (editableRoleId.value === roleId) {
    // If clicking the same role, check if there are changes
    if (hasRoleChanges(roleId)) {
      // If there are changes, save them
      saveRole(roleId)
    } else {
      // If no changes, just exit edit mode
      isReadOnly.value = true
      delete originalRolePermissions.value[roleId]
      editableRoleId.value = null
    }
  } else {
    // If another role is being edited, discard its changes
    if (editableRoleId.value) {
      discardRoleChanges(editableRoleId.value)
      delete originalRolePermissions.value[editableRoleId.value]
    }
    
    // Enable edit mode for the clicked role
    // Store original permissions state
    const currentPermissions = rolePermissionSets.value[roleId]
    if (currentPermissions) {
      originalRolePermissions.value[roleId] = new Set(currentPermissions)
    } else {
      originalRolePermissions.value[roleId] = new Set()
    }
    
    isReadOnly.value = false
    editableRoleId.value = roleId
  }
}

function togglePermission(roleId, permissionId, assign) {
  // Prevent toggling if role is not editable
  if (!isRoleEditable(roleId)) {
    return
  }
  
  // Only update local state - no API call
  if (!rolePermissionSets.value[roleId]) {
    rolePermissionSets.value[roleId] = new Set()
    rolePermissions.value[roleId] = []
  }
  
  if (assign) {
    if (!rolePermissionSets.value[roleId].has(permissionId)) {
      rolePermissionSets.value[roleId].add(permissionId)
      rolePermissions.value[roleId].push(permissionId)
    }
  } else {
    rolePermissionSets.value[roleId].delete(permissionId)
    rolePermissions.value[roleId] = rolePermissions.value[roleId].filter(id => id !== permissionId)
  }
}

async function saveRole(roleId) {
  if (!isRoleEditable(roleId)) {
    return
  }

  const original = originalRolePermissions.value[roleId]
  const current = rolePermissionSets.value[roleId]

  if (!original || !current) {
    return
  }

  // Calculate differences
  const permissionIdsToAdd = []
  const permissionIdsToRemove = []

  // Find permissions to add (in current but not in original)
  for (const permId of current) {
    if (!original.has(permId)) {
      permissionIdsToAdd.push(permId)
    }
  }

  // Find permissions to remove (in original but not in current)
  for (const permId of original) {
    if (!current.has(permId)) {
      permissionIdsToRemove.push(permId)
    }
  }

  // If no changes, just exit edit mode
  if (permissionIdsToAdd.length === 0 && permissionIdsToRemove.length === 0) {
    // Exit edit mode
    isReadOnly.value = true
    delete originalRolePermissions.value[roleId]
    editableRoleId.value = null
    return
  }

  try {
    const response = await Nova.request().post('/nova-vendor/role-manager/role-permissions/save', {
      role_id: roleId,
      permission_ids_to_add: permissionIdsToAdd,
      permission_ids_to_remove: permissionIdsToRemove
    })

    if (!response?.data?.success) {
      throw new Error(response?.data?.error || 'Failed to save role permissions')
    }

    // Update original state to match current (changes are saved)
    originalRolePermissions.value[roleId] = new Set(current)
    
    // Show success notification using Nova's built-in notification system
    const addedCount = permissionIdsToAdd.length
    const removedCount = permissionIdsToRemove.length
    let message = 'Role permissions saved successfully'
    if (addedCount > 0 || removedCount > 0) {
      const parts = []
      if (addedCount > 0) parts.push(`${addedCount} permission${addedCount > 1 ? 's' : ''} added`)
      if (removedCount > 0) parts.push(`${removedCount} permission${removedCount > 1 ? 's' : ''} removed`)
      message = `Role permissions updated: ${parts.join(', ')}`
    }
    Nova.success(message)
    
    // Exit edit mode
    isReadOnly.value = true
    delete originalRolePermissions.value[roleId]
    editableRoleId.value = null
  } catch (e) {
    console.error('Failed to save role permissions:', e)
    const errorMessage = e?.response?.data?.error || e?.message || 'Failed to save role permissions'
    Nova.error(errorMessage)
    
    // If it's a permission error (403), exit edit mode
    if (e?.response?.status === 403) {
      isReadOnly.value = true
      delete originalRolePermissions.value[roleId]
      editableRoleId.value = null
    }
    
    throw e
  }
}

// Flatten nested categories structure into sections and groups
function flattenCategories() {
  if (!selectedGuard.value || !guards.value[selectedGuard.value]) {
    flattenedSections.value = []
    return
  }

  const categories = guards.value[selectedGuard.value].categories || {}
  const sections = []

  // Helper to check if a node or its descendants have permissions
  function hasPermissions(node) {
    if (node.permissions && Array.isArray(node.permissions) && node.permissions.length > 0) {
      return true
    }
    if (typeof node !== 'object' || node === null) {
      return false
    }
    return Object.values(node).some(child => hasPermissions(child))
  }

  // Helper to collect all groups with permissions from a node
  function collectGroups(node, path = '', groups = []) {
    for (const [key, value] of Object.entries(node)) {
      if (key === 'permissions') continue
      
      const currentPath = path ? `${path} / ${key}` : key
      
      if (value.permissions && Array.isArray(value.permissions) && value.permissions.length > 0) {
        // This is a group with permissions
        groups.push({
          groupName: key,
          path: currentPath,
          permissions: value.permissions
        })
      } else if (typeof value === 'object' && value !== null) {
        // Recursively collect groups from children
        collectGroups(value, currentPath, groups)
      }
    }
    return groups
  }

  // Process top-level categories
  for (const [sectionKey, sectionValue] of Object.entries(categories)) {
    if (!hasPermissions(sectionValue)) {
      continue
    }

    // Check if this top-level category has direct permissions or subcategories
    const hasDirectPerms = sectionValue.permissions && Array.isArray(sectionValue.permissions) && sectionValue.permissions.length > 0
    const subKeys = Object.keys(sectionValue).filter(k => k !== 'permissions')
    const hasSubcategories = subKeys.length > 0

    if (hasDirectPerms && !hasSubcategories) {
      // Top-level category with only permissions - treat as group in "Other" section
      let section = sections.find(s => s.sectionName === 'Other')
      if (!section) {
        section = {
          sectionName: 'Other',
          path: 'Other',
          groups: []
        }
        sections.push(section)
      }
      section.groups.push({
        groupName: sectionKey,
        path: sectionKey,
        permissions: sectionValue.permissions
      })
    } else {
      // This is a section - collect all groups from it
      const groups = collectGroups(sectionValue, sectionKey, [])
      
      if (groups.length > 0) {
        sections.push({
          sectionName: sectionKey,
          path: sectionKey,
          groups: groups
        })
      }
    }
  }

  // Sort sections and groups
  sections.forEach(section => {
    section.groups.sort((a, b) => a.groupName.localeCompare(b.groupName))
  })
  
  flattenedSections.value = sections
}

async function fetchGuards() {
  isLoading.value = true
  loadError.value = false

  try {
    const response = await Nova.request().get('/nova-vendor/role-manager/permission-groups')
    guards.value = response?.data ?? {}
    
    // Select first guard by default (using ordered guard names)
    const ordered = guardOrder.filter(guard => guards.value[guard])
    const firstGuard = ordered.length > 0 ? ordered[0] : Object.keys(guards.value)[0]
    if (firstGuard) {
      selectedGuard.value = firstGuard
    }
  } catch (e) {
    loadError.value = true
    guards.value = {}
  } finally {
    isLoading.value = false
  }
}

async function fetchRolePermissions() {
  if (!selectedGuard.value || !guards.value[selectedGuard.value]) {
    return
  }

  try {
    // Fetch all role permissions for the guard in a single request
    const response = await Nova.request().get('/nova-vendor/role-manager/role-permissions/all', {
      params: { guard_name: selectedGuard.value }
    })
    
    const rolePermissionsData = response?.data?.role_permissions || {}
    
    // Build arrays and Sets for fast lookups
    for (const [roleId, permissions] of Object.entries(rolePermissionsData)) {
      const roleIdNum = parseInt(roleId, 10)
      rolePermissions.value[roleIdNum] = permissions || []
      rolePermissionSets.value[roleIdNum] = new Set(permissions || [])
    }
    
    // Ensure all roles have entries (even if they have no permissions)
    const roles = guards.value[selectedGuard.value].roles || []
    for (const role of roles) {
      if (!(role.id in rolePermissions.value)) {
        rolePermissions.value[role.id] = []
        rolePermissionSets.value[role.id] = new Set()
      }
    }
  } catch (e) {
    console.error('Failed to fetch role permissions:', e)
    // Fallback: initialize empty permissions for all roles
    const roles = guards.value[selectedGuard.value].roles || []
    for (const role of roles) {
      rolePermissions.value[role.id] = []
      rolePermissionSets.value[role.id] = new Set()
    }
  }
}

onMounted(async () => {
  await fetchGuards()
  if (selectedGuard.value) {
    flattenCategories()
    await fetchRolePermissions()
  }
})
</script>
