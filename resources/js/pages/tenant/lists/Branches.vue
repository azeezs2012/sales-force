<template>
  <Head title="Branches" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <Card class="flex h-full flex-1 flex-col bg-muted/10">
      <CardHeader>
        <CardTitle class="text-2xl">Branch Management</CardTitle>
      </CardHeader>
      <CardContent>
        <!-- Create/Edit Form -->
        <div class="mb-6 grid grid-cols-4 gap-4">
          <Input
            v-model="form.branch_name"
            placeholder="Branch Name"
            class="bg-background"
            required
          />
          <Select v-model="form.parent">
            <SelectTrigger class="bg-background">
              <SelectValue placeholder="Select Parent Branch" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem :value="null">None</SelectItem>
              <SelectItem v-for="branch in availableParentBranches" :key="branch.id" :value="branch.id">
                {{ branch.branch_name }}
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
        </div>
        
        <!-- Action Buttons -->
        <div class="mb-6 flex gap-2">
          <Button @click="resetForm" class="w-fit" variant="secondary">
            Cancel
          </Button>
          <Button @click="handleSubmit" class="w-fit">
            {{ isEditing ? 'Update' : 'Create' }} Branch
          </Button>
        </div>

        <!-- Branches Table -->
        <div class="rounded-md border bg-card">
          <Table>
            <TableHeader class="bg-muted/50">
              <TableRow>
                <TableHead class="uppercase">Branch Name</TableHead>
                <TableHead class="uppercase">Active</TableHead>
                <TableHead class="uppercase">Approved</TableHead>
                <TableHead class="uppercase">Approved By</TableHead>
                <TableHead class="uppercase">Created By</TableHead>
                <TableHead class="uppercase">Updated By</TableHead>
                <TableHead class="w-[100px] uppercase">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="branch in branches" :key="branch.id" class="bg-background/50">
                <TableCell>
                  <span :style="{ paddingLeft: `${getIndentationLevel(branch)}rem` }">
                    <span v-if="branch.parent">• </span>{{ branch.branch_name }}
                  </span>
                </TableCell>
                <TableCell>
                  <Badge :variant="branch.active ? 'default' : 'secondary'">
                    {{ branch.active ? 'Yes' : 'No' }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <Badge :variant="branch.approved ? 'default' : 'secondary'">
                    {{ branch.approved ? 'Yes' : 'No' }}
                  </Badge>
                </TableCell>
                <TableCell>{{ branch.approver ? branch.approver.name : 'N/A' }}</TableCell>
                <TableCell>{{ branch.creator ? branch.creator.name : 'N/A' }}</TableCell>
                <TableCell>{{ branch.updater ? branch.updater.name : 'N/A' }}</TableCell>
                <TableCell>
                  <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                      <Button variant="secondary" class="w-[130px]">Select Action</Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-[130px]">
                      <DropdownMenuItem @click="editBranch(branch)">
                        <Pencil class="mr-2 h-4 w-4" />
                        <span>Edit</span>
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem @click="showDeleteDialog(branch)" class="text-destructive focus:text-destructive">
                        <Trash class="mr-2 h-4 w-4" />
                        <span>Delete</span>
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
              <TableRow v-if="branches.length === 0">
                <TableCell colspan="7" class="h-24 text-center">
                  No branches found.
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </CardContent>
    </Card>

    <!-- Delete Confirmation Dialog -->
    <Dialog :open="!!branchToDelete" @update:open="closeDeleteDialog">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete Branch</DialogTitle>
          <DialogDescription>
            Are you sure you want to delete this branch? This action cannot be undone.
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
import { ref, onMounted, computed } from 'vue';
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
    title: 'Branches',
    href: '/branches',
  },
];

interface Branch {
  id: string;
  branch_name: string;
  active: boolean;
  approved: boolean;
  parent?: string;
  childBranches?: Branch[];
  creator?: { name: string };
  updater?: { name: string };
  approver?: { name: string };
}

const branches: Ref<Branch[]> = ref([]);
const isEditing = ref(false);
const branchToDelete: Ref<Branch | null> = ref(null);
const form = ref({
  id: undefined as string | undefined,
  branch_name: '',
  active: true,
  approved: true,
  parent: undefined as string | undefined,
});

// Computed property to filter out current branch from parent options
const availableParentBranches = computed(() => {
  if (!isEditing.value || !form.value.id) {
    return branches.value;
  }
  return branches.value.filter(branch => branch.id !== form.value.id);
});

// Function to check for circular references
const hasCircularReference = (parentId: string | null | undefined): boolean => {
  if (!parentId || !form.value.id) return false;
  
  let currentParentId = parentId;
  const visited = new Set<string>();
  
  while (currentParentId) {
    if (visited.has(currentParentId)) return true;
    if (currentParentId === form.value.id) return true;
    
    visited.add(currentParentId);
    const parentBranch = branches.value.find(b => b.id === currentParentId);
    currentParentId = parentBranch?.parent;
  }
  
  return false;
};

const getIndentationLevel = (branch: Branch): number => {
  let level = 0;
  let currentBranch = branch;
  while (currentBranch.parent) {
    level++;
    currentBranch = branches.value.find(b => b.id === currentBranch.parent) as Branch;
  }
  return level;
};

const fetchBranches = async () => {
  try {
    const response = await axios.get('/api/branches');
    branches.value = sortBranchesHierarchically(response.data);
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to fetch branches',
      variant: 'destructive',
    });
  }
};

const sortBranchesHierarchically = (branchList: Branch[]): Branch[] => {
  const branchMap = new Map<string, Branch>();
  branchList.forEach(branch => branchMap.set(branch.id, branch));

  const sortedBranches: Branch[] = [];

  const addBranchWithChildren = (branch: Branch) => {
    sortedBranches.push(branch);
    const children = branchList.filter(b => b.parent === branch.id);
    children.forEach(addBranchWithChildren);
  };

  branchList.filter(branch => !branch.parent).forEach(addBranchWithChildren);

  return sortedBranches;
};

const handleSubmit = async () => {
  // Prevent circular reference
  if (form.value.parent && hasCircularReference(form.value.parent)) {
    toast({
      title: 'Error',
      description: 'A branch cannot be its own parent or create a circular reference.',
      variant: 'destructive',
    });
    return;
  }
  try {
    if (isEditing.value) {
      await axios.put(`/api/branches/${form.value.id}`, form.value);
      toast({
        title: 'Success',
        description: 'Branch updated successfully!',
      });
    } else {
      await axios.post('/api/branches', form.value);
      toast({
        title: 'Success',
        description: 'Branch created successfully!',
      });
    }
    await fetchBranches();
    resetForm();
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Operation failed',
      variant: 'destructive',
    });
  }
};

const editBranch = (branch: Branch) => {
  form.value = {
    id: branch.id,
    branch_name: branch.branch_name,
    active: branch.active,
    approved: branch.approved,
    parent: branch.parent,
  };
  isEditing.value = true;
};

const showDeleteDialog = (branch: Branch) => {
  branchToDelete.value = branch;
};

const closeDeleteDialog = () => {
  branchToDelete.value = null;
};

const confirmDelete = async () => {
  if (!branchToDelete.value) return;
  
  try {
    await axios.delete(`/api/branches/${branchToDelete.value.id}`);
    await fetchBranches();
    toast({
      title: 'Success',
      description: 'Branch deleted successfully!',
    });
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to delete branch',
      variant: 'destructive',
    });
  } finally {
    closeDeleteDialog();
  }
};

const resetForm = () => {
  form.value = {
    id: undefined,
    branch_name: '',
    active: true,
    approved: true,
    parent: undefined,
  };
  isEditing.value = false;
};

onMounted(() => {
  fetchBranches();
});
</script>

<style scoped>
/* Add your styles here */
</style> 