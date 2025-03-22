<template>
  <Head title="Users" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
        <div class="py-12">
          <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6 text-gray-900">
                <h2 class="text-2xl font-bold mb-4">User Management</h2>

                <!-- Form to create or update a user -->
                <form @submit.prevent="handleSubmit" class="mb-6">
                  <div class="flex items-center gap-4">
                    <input type="text" v-model="form.name" placeholder="Name" class="border rounded p-2" required />
                    <input type="email" v-model="form.email" placeholder="Email" class="border rounded p-2" required />
                    <input type="password" v-model="form.password" placeholder="Password" class="border rounded p-2" required />
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">{{ isEditing ? 'Update' : 'Create' }} User</button>
                  </div>
                </form>

                <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                      <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                      </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                      <tr v-for="user in users" :key="user.id">
                        <td class="px-6 py-4 whitespace-nowrap">{{ user.name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ user.email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <DropdownMenu>
                            <DropdownMenuTrigger class="bg-blue-500 text-white px-4 py-2 rounded">Select Action</DropdownMenuTrigger>
                            <DropdownMenuContent>
                              <DropdownMenuLabel>Actions</DropdownMenuLabel>
                              <DropdownMenuSeparator />
                              <DropdownMenuItem @click="() => editUser(user)">Edit</DropdownMenuItem>
                              <DropdownMenuItem @click="() => deleteUser(user.id)">Delete</DropdownMenuItem>
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
import { useToast } from '@/components/ui/toast/use-toast'
import { ref, onMounted } from 'vue';
import axios from 'axios';
import type { Ref } from 'vue';

const { toast } = useToast()

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
}

const users: Ref<User[]> = ref([]);
const form: Ref<Partial<User> & { password?: string }> = ref({
  id: undefined,
  name: '',
  email: '',
  password: '',
});
const isEditing = ref(false);

const fetchUsers = async () => {
  try {
    const response = await axios.get('/api/users');
    users.value = response.data;
  } catch (error) {
    console.error('Failed to fetch users:', error);
  }
};

const handleSubmit = async () => {
  try {
    if (isEditing.value) {
      await updateUser();
    } else {
      await createUser();
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

const createUser = async () => {
  try {
    await axios.post('/api/users', form.value);
    fetchUsers();
    resetForm();
    toast({
      title: 'Success',
      description: 'User created successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to create user.',
      variant: 'destructive',
    });
  }
};

const editUser = (user: User) => {
  form.value = { ...user };
  isEditing.value = true;
};

const updateUser = async () => {
  try {
    await axios.put(`/api/users/${form.value.id}`, form.value);
    fetchUsers();
    resetForm();
    toast({
      title: 'Success',
      description: 'User updated successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to update user.',
      variant: 'destructive',
    });
  }
};

const deleteUser = async (id: string) => {
  try {
    await axios.delete(`/api/users/${id}`);
    fetchUsers();
    toast({
      title: 'Success',
      description: 'User deleted successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: 'Failed to delete user.',
      variant: 'destructive',
    });
  }
};

const resetForm = () => {
  form.value = { id: undefined, name: '', email: '', password: '' };
  isEditing.value = false;
};

onMounted(() => {
  fetchUsers();
});
</script>

<style scoped>
/* Add your styles here */
</style> 