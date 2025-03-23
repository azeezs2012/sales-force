<template>
  <Head title="Locations" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div :class="{'dark': appearance === 'dark'}" class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 bg-white dark:bg-neutral-800">
      <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
        <div class="absolute top-0 left-0 py-12">
          <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-neutral-700 overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6 text-gray-900 dark:text-neutral-100">
                <h2 class="text-2xl font-bold mb-4">Location Management</h2>

                <!-- Form to create or update a location -->
                <form @submit.prevent="handleSubmit" class="mb-6">
                  <div class="flex items-center gap-4">
                    <input type="text" v-model="form.location_name" placeholder="Location Name" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" required />
                    <input type="checkbox" v-model="form.active" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" /> Active
                    <input type="checkbox" v-model="form.approved" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" /> Approved
                    <DropdownMenu>
                      <DropdownMenuTrigger class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100">
                        {{ form.parent ? locations.find(location => location.id === form.parent)?.location_name : 'Select Parent Location' }}
                      </DropdownMenuTrigger>
                      <DropdownMenuContent>
                        <DropdownMenuItem v-for="location in locations" :key="location.id" @click="form.parent = location.id">
                          {{ ' '.repeat(getIndentationLevel(location) * 2) + location.location_name }}
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                    <button type="submit" class="bg-neutral-500 text-white px-4 py-2 rounded hover:bg-neutral-600 dark:bg-neutral-600 dark:hover:bg-neutral-500">{{ isEditing ? 'Update' : 'Create' }} Location</button>
                  </div>
                </form>

                <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-neutral-800">
                      <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Location Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Active</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Approved</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Approved By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Created By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Updated By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Actions</th>
                      </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-neutral-700 divide-y divide-gray-200 dark:divide-gray-700">
                      <tr v-for="location in locations" :key="location.id">
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">
                          <span :style="{ paddingLeft: `${getIndentationLevel(location)}rem` }">
                            <span v-if="location.parent">• </span>{{ location.location_name }}
                          </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ location.active ? 'Yes' : 'No' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ location.approved ? 'Yes' : 'No' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">
                          {{ location.approver ? location.approver.name : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">
                          {{ location.creator ? location.creator.name : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">
                          {{ location.updater ? location.updater.name : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <DropdownMenu>
                            <DropdownMenuTrigger class="bg-neutral-500 text-white px-4 py-2 rounded hover:bg-neutral-600 dark:bg-neutral-600 dark:hover:bg-neutral-500">Select Action</DropdownMenuTrigger>
                            <DropdownMenuContent>
                              <DropdownMenuLabel>Actions</DropdownMenuLabel>
                              <DropdownMenuSeparator />
                              <DropdownMenuItem @click="() => editLocation(location)">Edit</DropdownMenuItem>
                              <DropdownMenuItem @click="() => deleteLocation(location.id)">Delete</DropdownMenuItem>
                            </DropdownMenuContent>
                          </DropdownMenu>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/TenantAppLayout.vue';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useToast } from '@/components/ui/toast/use-toast';
import { ref, onMounted, defineComponent, h, VNode } from 'vue';
import axios from 'axios';
import type { Ref } from 'vue';
import { useAppearance } from '@/composables/useAppearance';

const { toast } = useToast()

const breadcrumbs = [
  {
    title: 'Locations',
    href: '/locations',
  },
];

interface Location {
  id: string;
  location_name: string;
  active: boolean;
  approved: boolean;
  parent?: string;
  childLocations?: Location[];
  creator?: { name: string };
  updater?: { name: string };
  approver?: { name: string };
}

const locations: Ref<Location[]> = ref([]);
const form: Ref<Partial<Location>> = ref({
  id: undefined,
  location_name: '',
  active: true,
  approved: true,
  parent: undefined,
});
const isEditing = ref(false);

const { appearance, updateAppearance } = useAppearance();

const fetchActiveApprovedLocations = async () => {
  try {
    const response = await axios.get('/api/locations');
    const allLocations = sortLocationsHierarchically(response.data);
    locations.value = allLocations.filter((location: Location) => location.active && location.approved);
  } catch (error) {
    console.error('Failed to fetch active and approved locations:', error);
  }
};

const sortLocationsHierarchically = (locations: Location[]): Location[] => {
  const locationMap = new Map<string, Location>();
  locations.forEach(location => locationMap.set(location.id, location));

  const sortedLocations: Location[] = [];

  const addLocationWithChildren = (location: Location) => {
    sortedLocations.push(location);
    const children = locations.filter(l => l.parent === location.id);
    children.forEach(addLocationWithChildren);
  };

  locations.filter(location => !location.parent).forEach(addLocationWithChildren);

  return sortedLocations;
};

const fetchLocations = async () => {
  try {
    const response = await axios.get('/api/locations');
    const allLocations = response.data;
    locations.value = sortLocationsHierarchically(allLocations);
  } catch (error) {
    console.error('Failed to fetch locations:', error);
  }
};

const handleSubmit = async () => {
  try {
    if (isEditing.value) {
      await updateLocation();
    } else {
      await createLocation();
    }
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'An error occurred.',
      variant: 'destructive',
    });
  }
};

const createLocation = async () => {
  try {
    await axios.post('/api/locations', form.value);
    fetchLocations();
    resetForm();
    toast({
      title: 'Success',
      description: 'Location created successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to create location.',
      variant: 'destructive',
    });
  }
};

const editLocation = (location: Location) => {
  form.value = { ...location };
  isEditing.value = true;
};

const updateLocation = async () => {
  try {
    await axios.put(`/api/locations/${form.value.id}`, form.value);
    fetchLocations();
    resetForm();
    toast({
      title: 'Success',
      description: 'Location updated successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to update location.',
      variant: 'destructive',
    });
  }
};

const deleteLocation = async (id: string) => {
  try {
    await axios.delete(`/api/locations/${id}`);
    fetchLocations();
    toast({
      title: 'Success',
      description: 'Location deleted successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to delete location.',
      variant: 'destructive',
    });
  }
};

const resetForm = () => {
  form.value = { id: undefined, location_name: '', active: true, approved: true, parent: undefined };
  isEditing.value = false;
};

onMounted(() => {
  fetchLocations();
});

// Function to calculate indentation level based on hierarchy
const getIndentationLevel = (location: Location): number => {
  let level = 0;
  let currentLocation = location;
  while (currentLocation.parent) {
    level++;
    currentLocation = locations.value.find(l => l.id === currentLocation.parent) || currentLocation;
  }
  return level * 1.5; // Adjust multiplier for desired indentation
};
</script>

<style scoped>
/* Add your styles here */
</style> 