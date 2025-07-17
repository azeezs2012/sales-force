<template>
  <Head title="Users" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <Card class="flex h-full flex-1 flex-col bg-muted/10">
      <CardHeader>
        <CardTitle class="text-2xl">User Management</CardTitle>
      </CardHeader>
      <CardContent>
        <!-- Create/Edit Form -->
        <div class="mb-6 grid grid-cols-4 gap-4">
          <Input
            v-model="form.name"
            placeholder="Name"
            class="bg-background"
            required
          />
          <Input
            v-model="form.email"
            type="email"
            placeholder="Email"
            class="bg-background"
            required
          />
          <Input
            v-model="form.password"
            type="password"
            placeholder="Password"
            class="bg-background"
            :required="!isEditing"
          />
        </div>
        
        <!-- Action Buttons -->
        <div class="mb-6 flex gap-2">
          <Button @click="resetForm" class="w-fit" variant="secondary">
            Cancel
          </Button>
          <Button @click="handleSubmit" class="w-fit">
            {{ isEditing ? 'Update' : 'Create' }} User
          </Button>
        </div>

        <!-- Users Table -->
        <div class="rounded-md border bg-card">
          <Table>
            <TableHeader class="bg-muted/50">
              <TableRow>
                <TableHead class="uppercase">Name</TableHead>
                <TableHead class="uppercase">Email</TableHead>
                <TableHead class="uppercase">Role</TableHead>
                <TableHead class="w-[100px] uppercase">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="user in users" :key="user.id" class="bg-background/50">
                <TableCell>{{ user.name }}</TableCell>
                <TableCell>{{ user.email }}</TableCell>
                <TableCell>{{ user.role }}</TableCell>
                <TableCell>
                  <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                      <Button variant="secondary" class="w-[130px]">Select Action</Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-[130px]">
                      <DropdownMenuItem @click="editUser(user)">
                        <Pencil class="mr-2 h-4 w-4" />
                        <span>Edit</span>
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem @click="showDeleteDialog(user)" class="text-destructive focus:text-destructive">
                        <Trash class="mr-2 h-4 w-4" />
                        <span>Delete</span>
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
              <TableRow v-if="users.length === 0">
                <TableCell colspan="3" class="h-24 text-center">
                  No users found.
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </CardContent>
    </Card>

    <!-- Delete Confirmation Dialog -->
    <Dialog :open="!!userToDelete" @update:open="closeDeleteDialog">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete User</DialogTitle>
          <DialogDescription>
            Are you sure you want to delete this user? This action cannot be undone.
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

const { toast } = useToast();

const breadcrumbs = [
  {
    title: 'Users',
    href: '/users',
  },
];

interface User {
  id: string;
  name: string;
  email: string;
  role: string;
}

const users: Ref<User[]> = ref([]);
const isEditing = ref(false);
const userToDelete: Ref<User | null> = ref(null);
const form = ref({
  id: undefined as string | undefined,
  name: '',
  email: '',
  password: '',
});

const fetchUsers = async () => {
  try {
    const response = await axios.get('/api/users');
    users.value = response.data;
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to fetch users',
      variant: 'destructive',
    });
  }
};

const handleSubmit = async () => {
  try {
    if (isEditing.value) {
      await axios.put(`/api/users/${form.value.id}`, form.value);
      toast({
        title: 'Success',
        description: 'User updated successfully!',
      });
    } else {
      await axios.post('/api/users', form.value);
      toast({
        title: 'Success',
        description: 'User created successfully!',
      });
    }
    await fetchUsers();
    resetForm();
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Operation failed',
      variant: 'destructive',
    });
  }
};

const editUser = (user: User) => {
  form.value = {
    id: user.id,
    name: user.name,
    email: user.email,
    password: '',
  };
  isEditing.value = true;
};

const showDeleteDialog = (user: User) => {
  userToDelete.value = user;
};

const closeDeleteDialog = () => {
  userToDelete.value = null;
};

const confirmDelete = async () => {
  if (!userToDelete.value) return;
  
  try {
    await axios.delete(`/api/users/${userToDelete.value.id}`);
    await fetchUsers();
    toast({
      title: 'Success',
      description: 'User deleted successfully!',
    });
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to delete user',
      variant: 'destructive',
    });
  } finally {
    closeDeleteDialog();
  }
};

const resetForm = () => {
  form.value = {
    id: undefined,
    name: '',
    email: '',
    password: '',
  };
  isEditing.value = false;
};

onMounted(() => {
  fetchUsers();
});
</script>

<style scoped>
/* Override browser autofill styling */
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus,
input:-webkit-autofill:active {
  -webkit-box-shadow: 00l(var(--background)) inset !important;
  -webkit-text-fill-color: hsl(var(--foreground)) !important;
  transition: background-color 500s ease-in-out 0s;
}

/* For Firefox */
input:-moz-autofill {
  background-color: hsl(var(--background)) !important;
  color: hsl(var(--foreground)) !important;
}
</style> 