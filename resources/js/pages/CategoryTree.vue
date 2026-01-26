<template>
  <div class="tree-container">
    <div
      v-for="(category, categoryKey, index) in categories"
      :key="categoryKey"
      class="tree-item"
    >
      <div v-if="isCategory(category) || (hasSubcategories(category) && hasPermissions(category))" class="tree-node">
        <div class="tree-line-wrapper">
          <div
            v-if="index < Object.keys(categories).length - 1"
            class="tree-line-vertical"
          />
          <div class="tree-line-horizontal" />
        </div>
        <div class="flex-1">
          <div v-if="hasSubcategories(category)" class="flex items-center gap-1 w-full">
            <button
              type="button"
              class="flex items-center justify-center w-5 h-5 rounded transition-colors text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 flex-shrink-0"
              @click.stop="toggleCategory(categoryKey)"
            >
              <svg
                class="w-3 h-3 transition-transform"
                :class="expanded.has(categoryKey) ? 'rotate-90' : ''"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2.5"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <path d="M9 18l6-6-6-6" />
              </svg>
            </button>
            <button
              type="button"
              class="tree-button flex items-center justify-between flex-1 px-2 py-1.5 text-sm rounded transition-colors"
              :class="hasPermissions(category) && isSelected(categoryKey)
                ? 'bg-primary-500 text-white'
                : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800'"
              @click="hasPermissions(category) ? selectCategory(category, getCategoryPath(categoryKey)) : toggleCategory(categoryKey)"
            >
              <span class="min-w-0 truncate font-medium">{{ categoryKey }}</span>
              <span v-if="hasPermissions(category)" class="text-xs opacity-75 ml-2">({{ category.permissions.length }})</span>
            </button>
          </div>
          <button
            v-else-if="hasPermissions(category)"
            type="button"
            class="tree-button flex items-center justify-between w-full px-2 py-1.5 text-sm rounded transition-colors"
              :class="isSelected(categoryKey)
              ? 'bg-primary-500 text-white'
              : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800'"
            @click="selectCategory(category, getCategoryPath(categoryKey))"
          >
            <div class="flex items-center gap-2 min-w-0">
              <svg
                class="w-3 h-3 flex-shrink-0 text-gray-400 dark:text-gray-500"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <circle cx="12" cy="12" r="3" />
              </svg>
              <span class="min-w-0 truncate">{{ categoryKey }}</span>
            </div>
            <span class="text-xs opacity-75 ml-2">({{ category.permissions.length }})</span>
          </button>
          <div
            v-if="hasSubcategories(category) && expanded.has(categoryKey)"
            class="tree-children"
          >
            <div
              v-if="index < Object.keys(categories).length - 1"
              class="tree-line-vertical-continue"
            />
            <CategoryTree
              :key="`${guardName}-${getCategoryPath(categoryKey)}`"
              :categories="category"
              :guard-name="guardName"
              :selected-path="selectedPath"
              :selected-guard-name="selectedGuardName"
              :parent-path="getCategoryPath(categoryKey)"
              @select="selectCategory"
            />
          </div>
        </div>
      </div>
      <div v-else-if="hasPermissions(category) && !hasSubcategories(category)" class="tree-node">
        <div class="tree-line-wrapper">
          <div
            v-if="index < Object.keys(categories).length - 1"
            class="tree-line-vertical"
          />
          <div class="tree-line-horizontal" />
        </div>
        <button
          type="button"
          class="tree-button flex items-center justify-between flex-1 px-2 py-1.5 text-sm rounded transition-colors"
          :class="isSelected(categoryKey)
            ? 'bg-primary-500 text-white'
            : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800'"
          @click="selectCategory(category, getCategoryPath(categoryKey))"
        >
          <div class="flex items-center gap-2 min-w-0">
            <svg
              class="w-3 h-3 flex-shrink-0 text-gray-400 dark:text-gray-500"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <circle cx="12" cy="12" r="3" />
            </svg>
            <span class="min-w-0 truncate">{{ categoryKey }}</span>
          </div>
          <span class="text-xs opacity-75 ml-2">({{ category.permissions.length }})</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import CategoryTree from './CategoryTree.vue'

const props = defineProps({
  categories: {
    type: Object,
    required: true,
  },
  guardName: {
    type: String,
    required: true,
  },
  selectedPath: {
    type: String,
    default: null,
  },
  selectedGuardName: {
    type: String,
    default: null,
  },
  parentPath: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['select'])

const expanded = ref(new Set())

const toggleCategory = (categoryKey) => {
  if (expanded.value.has(categoryKey)) {
    expanded.value.delete(categoryKey)
  } else {
    expanded.value.add(categoryKey)
  }
}

const selectCategory = (category, path) => {
  emit('select', category, path, props.guardName)
}

const isCategory = (category) => {
  if (!category || typeof category !== 'object' || Array.isArray(category)) {
    return false
  }
  // Check if it has subcategories (keys other than 'permissions')
  const keys = Object.keys(category)
  return keys.some(key => key !== 'permissions')
}

const hasPermissions = (category) => {
  return category && category.permissions && Array.isArray(category.permissions) && category.permissions.length > 0
}

const hasSubcategories = (category) => {
  if (!category || typeof category !== 'object' || Array.isArray(category)) {
    return false
  }
  const keys = Object.keys(category)
  return keys.some(key => key !== 'permissions')
}

const getCategoryPath = (categoryKey) => {
  return props.parentPath ? `${props.parentPath} / ${categoryKey}` : categoryKey
}

// Computed property to track current selection - ensures reactivity
const currentSelection = computed(() => {
  if (!props.selectedPath || !props.selectedGuardName) {
    return null
  }
  return {
    guardName: String(props.selectedGuardName).trim(),
    path: String(props.selectedPath).trim()
  }
})

// Function to check if a category is selected
// Uses the computed property to ensure reactivity
const isSelected = (categoryKey) => {
  // No selection active
  if (!currentSelection.value) {
    return false
  }
  
  const currentGuard = String(props.guardName).trim()
  
  // Guard names must match exactly - CRITICAL CHECK
  if (currentGuard !== currentSelection.value.guardName) {
    return false
  }
  
  // Path must match exactly
  const path = getCategoryPath(categoryKey)
  const categoryPath = String(path).trim()
  
  if (categoryPath !== currentSelection.value.path) {
    return false
  }
  
  // Both guard and path match
  return true
}
</script>

<style scoped>
.tree-container {
  position: relative;
}

.tree-item {
  position: relative;
  display: flex;
  align-items: flex-start;
}

.tree-node {
  display: flex;
  align-items: flex-start;
  width: 100%;
  position: relative;
}

.tree-line-wrapper {
  position: relative;
  width: 16px;
  flex-shrink: 0;
  margin-top: 12px;
}

.tree-line-vertical {
  position: absolute;
  left: 7px;
  top: 0;
  width: 1px;
  height: 100%;
  background-color: #e5e7eb;
}

.dark .tree-line-vertical {
  background-color: #374151;
}

.tree-line-horizontal {
  position: absolute;
  left: 7px;
  top: 12px;
  width: 8px;
  height: 1px;
  background-color: #e5e7eb;
}

.dark .tree-line-horizontal {
  background-color: #374151;
}

.tree-children {
  position: relative;
  flex: 1;
  margin-left: 16px;
}

.tree-line-vertical-continue {
  position: absolute;
  left: -16px;
  top: 0;
  width: 1px;
  height: 12px;
  background-color: #e5e7eb;
}

.dark .tree-line-vertical-continue {
  background-color: #374151;
}

.tree-button {
  flex: 1;
  min-width: 0;
}
</style>

