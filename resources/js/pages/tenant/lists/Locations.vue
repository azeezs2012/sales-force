<template>
  <Head title="Locations" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <Card class="flex h-full flex-1 flex-col bg-muted/10">
      <CardHeader>
        <CardTitle class="text-2xl">Location Management</CardTitle>
      </CardHeader>
      <CardContent>
        <!-- Create/Edit Form -->
        <div class="mb-6 grid grid-cols-4 gap-4">
          <Input
            v-model="form.location_name"
            placeholder="Location Name"
            class="bg-background"
            required
          />
          <Select v-model="form.parent">
            <SelectTrigger class="bg-background">
              <SelectValue placeholder="Select Parent Location" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem :value="null">None</SelectItem>
              <SelectItem
                v-for="location in locations"
                :key="location.id"
                :value="location.id"
              >
                {{ location.location_name }}
              </SelectItem>
            </SelectContent>
          </Select>
          <div class="flex items-center gap-4">
            <Label class="flex items-center gap-2">
              <Switch v-model="form.active" />
              Active
            </Label>
            <Label class="flex items-center gap-2">
              <Switch v-model="form.approved" />
              Approved
            </Label>
          </div>
          <Button @click="handleSubmit" class="w-fit">
            {{ isEditing ? 'Update' : 'Create' }} Location
          </Button>
        </div>

        <!-- Locations Table -->
        <div class="rounded-md border bg-card">
          <Table>
            <TableHeader class="bg-muted/50">
              <TableRow>
                <TableHead class="uppercase">Location Name</TableHead>
                <TableHead class="uppercase">Active</TableHead>
                <TableHead class="uppercase">Approved</TableHead>
                <TableHead class="uppercase">Approved By</TableHead>
                <TableHead class="uppercase">Created By</TableHead>
                <TableHead class="uppercase">Updated By</TableHead>
                <TableHead class="w-[100px] uppercase">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="location in locations" :key="location.id" class="bg-background/50">
                <TableCell>
                  <span :style="{ paddingLeft: `${getIndentationLevel(location)}rem` }">
                    <span v-if="location.parent">• </span>{{ location.location_name }}
                  </span>
                </TableCell>
                <TableCell>
                  <Badge :variant="location.active ? 'default' : 'secondary'">
                    {{ location.active ? 'Yes' : 'No' }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <Badge :variant="location.approved ? 'default' : 'secondary'">
                    {{ location.approved ? 'Yes' : 'No' }}
                  </Badge>
                </TableCell>
                <TableCell>{{ location.approver ? location.approver.name : 'N/A' }}</TableCell>
                <TableCell>{{ location.creator ? location.creator.name : 'N/A' }}</TableCell>
                <TableCell>{{ location.updater ? location.updater.name : 'N/A' }}</TableCell>
                <TableCell>
                  <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                      <Button variant="secondary" class="w-[130px]">Select Action</Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-[130px]">
                      <DropdownMenuItem @click="editLocation(location)">
                        <Pencil class="mr-2 h-4 w-4" />
                        <span>Edit</span>
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem @click="showDeleteDialog(location)" class="text-destructive focus:text-destructive">
                        <Trash class="mr-2 h-4 w-4" />
                        <span>Delete</span>
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
              <TableRow v-if="locations.length === 0">
                <TableCell colspan="7" class="h-24 text-center">
                  No locations found.
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </CardContent>
    </Card>

    <!-- Delete Confirmation Dialog -->
    <Dialog :open="!!locationToDelete" @update:open="closeDeleteDialog">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete Location</DialogTitle>
          <DialogDescription>
            Are you sure you want to delete this location? This action cannot be undone.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button variant="ghost" @click="closeDeleteDialog">Cancel</Button>
          <Button variant="destructive" @click="confirmDelete">Delete</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/TenantAppLayout.vue';
import { ref, onMounted } from 'vue';
import type { Ref } from 'vue';
import axios from 'axios';
import { useToast } from '@/components/ui/toast/use-toast';
import { Pencil, Trash } from 'lucide-vue-next';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

const { toast } = useToast();

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
const isEditing = ref(false);
const locationToDelete: Ref<Location | null> = ref(null);

const form = ref({
  id: undefined as string | undefined,
  location_name: '',
  active: true,
  approved: true,
  parent: null as string | null,
});

const getIndentationLevel = (location: Location): number => {
  let level = 0;
  let currentLocation = location;
  while (currentLocation.parent) {
    level++;
    currentLocation = locations.value.find(l => l.id === currentLocation.parent) || currentLocation;
  }
  return level * 1.5;
};

const sortLocationsHierarchically = (locationList: Location[]): Location[] => {
  const locationMap = new Map<string, Location>();
  locationList.forEach(location => locationMap.set(location.id, location));

  const sortedLocations: Location[] = [];

  const addLocationWithChildren = (location: Location) => {
    sortedLocations.push(location);
    const children = locationList.filter(l => l.parent === location.id);
    children.forEach(addLocationWithChildren);
  };

  locationList.filter(location => !location.parent).forEach(addLocationWithChildren);

  return sortedLocations;
};

const fetchLocations = async () => {
  try {
    const response = await axios.get('/api/locations');
    locations.value = sortLocationsHierarchically(response.data);
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to fetch locations',
      variant: 'destructive',
    });
  }
};

const handleSubmit = async () => {
  try {
    if (isEditing.value) {
      await axios.put(`/api/locations/${form.value.id}`, form.value);
      toast({
        title: 'Success',
        description: 'Location updated successfully!',
      });
    } else {
      await axios.post('/api/locations', form.value);
      toast({
        title: 'Success',
        description: 'Location created successfully!',
      });
    }
    await fetchLocations();
    resetForm();
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Operation failed',
      variant: 'destructive',
    });
  }
};

const editLocation = (location: Location) => {
  form.value = {
    id: location.id,
    location_name: location.location_name,
    active: location.active,
    approved: location.approved,
    parent: location.parent || null,
  };
  isEditing.value = true;
};

const showDeleteDialog = (location: Location) => {
  locationToDelete.value = location;
};

const closeDeleteDialog = () => {
  locationToDelete.value = null;
};

const confirmDelete = async () => {
  if (!locationToDelete.value) return;
  
  try {
    await axios.delete(`/api/locations/${locationToDelete.value.id}`);
    await fetchLocations();
    toast({
      title: 'Success',
      description: 'Location deleted successfully!',
    });
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to delete location',
      variant: 'destructive',
    });
  } finally {
    closeDeleteDialog();
  }
};

const resetForm = () => {
  form.value = {
    id: undefined,
    location_name: '',
    active: true,
    approved: true,
    parent: null,
  };
  isEditing.value = false;
};

onMounted(() => {
  fetchLocations();
});
</script>

<style scoped>
/* Add your styles here */
</style> 