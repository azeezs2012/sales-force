<template>
  <Head title="Accounts" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <Card class="flex h-full flex-1 flex-col bg-muted/10">
      <CardHeader>
        <CardTitle class="text-2xl">Account Management</CardTitle>
      </CardHeader>
      <CardContent>
        <!-- Create/Edit Form -->
        <div class="mb-6 grid grid-cols-5 gap-4">
          <Input
            v-model="form.account_name"
            placeholder="Account Name"
            class="bg-background"
          />
          <Input
            v-model="form.account_description"
            placeholder="Account Description"
            class="bg-background"
          />
          <Select v-model="form.account_type_id">
            <SelectTrigger class="bg-background">
              <SelectValue :placeholder="'Select Account Type'" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="accountType in accountTypes" :key="accountType.id" :value="accountType.id">
                {{ accountType.account_type_name }}
              </SelectItem>
            </SelectContent>
          </Select>
          <Select v-model="form.parent_id">
            <SelectTrigger class="bg-background">
              <SelectValue :placeholder="'Select Parent Account'" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem :value="null">None</SelectItem>
              <SelectItem v-for="account in accounts" :key="account.id" :value="account.id">
                {{ ' '.repeat(getIndentationLevel(account) * 2) + account.account_name }}
              </SelectItem>
            </SelectContent>
          </Select>
          <Button @click="handleSubmit" class="w-fit whitespace-nowrap">
            {{ isEditing ? 'Update' : 'Create' }} Account
          </Button>
        </div>

        <!-- Accounts Table -->
        <div class="rounded-md border bg-card">
          <Table>
            <TableHeader class="bg-muted/50">
              <TableRow>
                <TableHead class="uppercase">Account Name</TableHead>
                <TableHead class="uppercase">Description</TableHead>
                <TableHead class="uppercase">Account Type</TableHead>
                <TableHead class="uppercase">Parent Account</TableHead>
                <TableHead class="uppercase">Created By</TableHead>
                <TableHead class="uppercase">Updated By</TableHead>
                <TableHead class="w-[100px] uppercase">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="account in accounts" :key="account.id" class="bg-background/50">
                <TableCell>
                  <span :style="{ paddingLeft: `${getIndentationLevel(account)}rem` }">
                    <span v-if="account.parent_id">• </span>{{ account.account_name }}
                  </span>
                </TableCell>
                <TableCell>{{ account.account_description || 'N/A' }}</TableCell>
                <TableCell>
                  <Badge variant="outline">
                    {{ account.account_type?.account_type_name || 'N/A' }}
                  </Badge>
                </TableCell>
                <TableCell>{{ account.parent_account?.account_name || 'N/A' }}</TableCell>
                <TableCell>{{ account.creator?.name || 'N/A' }}</TableCell>
                <TableCell>{{ account.updater?.name || 'N/A' }}</TableCell>
                <TableCell>
                  <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                      <Button variant="secondary" class="w-[130px]">Select Action</Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-[130px]">
                      <DropdownMenuItem @click="editAccount(account)">
                        <Pencil class="mr-2 h-4 w-4" />
                        <span>Edit</span>
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem @click="showDeleteDialog(account)" class="text-destructive focus:text-destructive">
                        <Trash class="mr-2 h-4 w-4" />
                        <span>Delete</span>
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
              <TableRow v-if="accounts.length === 0">
                <TableCell colspan="7" class="h-24 text-center">
                  No accounts found.
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </CardContent>
    </Card>

    <!-- Delete Confirmation Dialog -->
    <Dialog :open="!!accountToDelete" @update:open="closeDeleteDialog">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete Account</DialogTitle>
          <DialogDescription>
            Are you sure you want to delete this account? This action cannot be undone.
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
    title: 'Accounts',
    href: '/accounts',
  },
];

interface AccountType {
  id: string;
  account_type_name: string;
  bs: boolean;
  pl: boolean;
  display_order: number;
}

interface Account {
  id: string;
  account_name: string;
  account_description?: string;
  account_type_id: string;
  parent_id?: string;
  account_type?: AccountType;
  parent_account?: Account;
  child_accounts?: Account[];
  creator?: { name: string };
  updater?: { name: string };
}

const accounts: Ref<Account[]> = ref([]);
const accountTypes: Ref<AccountType[]> = ref([]);
const isEditing = ref(false);
const accountToDelete: Ref<Account | null> = ref(null);
const form = ref({
  id: undefined as string | undefined,
  account_name: '',
  account_description: '',
  account_type_id: undefined as string | undefined,
  parent_id: undefined as string | undefined,
});

const getIndentationLevel = (account: Account): number => {
  let level = 0;
  let currentAccount = account;
  while (currentAccount.parent_id) {
    level++;
    currentAccount = accounts.value.find(a => a.id === currentAccount.parent_id) as Account;
  }
  return level;
};

const fetchAccounts = async () => {
  try {
    const response = await axios.get('/api/accounts');
    accounts.value = sortAccountsHierarchically(response.data);
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to fetch accounts',
      variant: 'destructive',
    });
  }
};

const fetchAccountTypes = async () => {
  try {
    const response = await axios.get('/api/account-types');
    accountTypes.value = response.data;
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to fetch account types',
      variant: 'destructive',
    });
  }
};

const sortAccountsHierarchically = (accountList: Account[]): Account[] => {
  const accountMap = new Map<string, Account>();
  accountList.forEach(account => accountMap.set(account.id, account));

  const sortedAccounts: Account[] = [];

  const addAccountWithChildren = (account: Account) => {
    sortedAccounts.push(account);
    const children = accountList.filter(a => a.parent_id === account.id);
    children.forEach(addAccountWithChildren);
  };

  accountList.filter(account => !account.parent_id).forEach(addAccountWithChildren);

  return sortedAccounts;
};

const handleSubmit = async () => {
  try {
    if (isEditing.value) {
      await axios.put(`/api/accounts/${form.value.id}`, form.value);
      toast({
        title: 'Success',
        description: 'Account updated successfully!',
      });
    } else {
      await axios.post('/api/accounts', form.value);
      toast({
        title: 'Success',
        description: 'Account created successfully!',
      });
    }
    await fetchAccounts();
    resetForm();
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Operation failed',
      variant: 'destructive',
    });
  }
};

const editAccount = (account: Account) => {
  form.value = {
    id: account.id,
    account_name: account.account_name,
    account_description: account.account_description || '',
    account_type_id: account.account_type_id,
    parent_id: account.parent_id,
  };
  isEditing.value = true;
};

const showDeleteDialog = (account: Account) => {
  accountToDelete.value = account;
};

const closeDeleteDialog = () => {
  accountToDelete.value = null;
};

const confirmDelete = async () => {
  if (!accountToDelete.value) return;
  
  try {
    await axios.delete(`/api/accounts/${accountToDelete.value.id}`);
    await fetchAccounts();
    toast({
      title: 'Success',
      description: 'Account deleted successfully!',
    });
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to delete account',
      variant: 'destructive',
    });
  } finally {
    closeDeleteDialog();
  }
};

const resetForm = () => {
  form.value = {
    id: undefined,
    account_name: '',
    account_description: '',
    account_type_id: undefined,
    parent_id: undefined,
  };
  isEditing.value = false;
};

onMounted(() => {
  fetchAccounts();
  fetchAccountTypes();
});
</script>

<style scoped>
/* Add your styles here */
</style> 