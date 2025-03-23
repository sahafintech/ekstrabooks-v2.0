import React, { useState, useMemo } from "react";
import { Link, router, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Button } from "@/Components/ui/button";
import { DataTable } from "@/Components/ui/data-table/data-table";
import TableActions from "@/Components/shared/TableActions";
import { Checkbox } from "@/Components/ui/checkbox";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import { Plus, Trash2, Edit, BarChart, MoreVertical } from "lucide-react";
import { Badge } from "@/Components/ui/badge";
import Modal from "@/Components/Modal";
import { Dialog, DialogContent, DialogTitle, DialogHeader, DialogFooter } from "@/Components/ui/dialog";
import { Label } from "@/Components/ui/label";
import { Input } from "@/Components/ui/input";
import { format, subDays } from "date-fns";

// Delete Account Modal Component
const DeleteAccountModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm} className="p-6">
      <h2 className="text-lg font-medium">
        Are you sure you want to delete this account?
      </h2>
      <div className="mt-6 flex justify-end">
        <Button
          type="button"
          variant="secondary"
          onClick={onClose}
          className="mr-3"
        >
          Cancel
        </Button>
        <Button
          type="submit"
          variant="destructive"
          disabled={processing}
        >
          Delete Account
        </Button>
      </div>
    </form>
  </Modal>
);

// Delete Multiple Accounts Modal Component
const DeleteAllAccountsModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm} className="p-6">
      <h2 className="text-lg font-medium">
        Are you sure you want to delete all selected accounts?
      </h2>
      <div className="mt-6 flex justify-end">
        <Button
          type="button"
          variant="secondary"
          onClick={onClose}
          className="mr-3"
        >
          Close
        </Button>
        <Button
          type="submit"
          variant="destructive"
          disabled={processing}
        >
          Delete All
        </Button>
      </div>
    </form>
  </Modal>
);

export default function List({ accounts = [], meta = {}, filters = {} }) {
  const { auth } = usePage().props;
  const [selectedAccounts, setSelectedAccounts] = useState([]);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
  const [accountToDelete, setAccountToDelete] = useState(null);
  const [tableRef, setTableRef] = useState(null);
  const [processing, setProcessing] = useState(false);
  
  // For the dialog when viewing or editing an account
  const [openDialog, setOpenDialog] = useState(false);
  const [dialogContent, setDialogContent] = useState(null);
  const [dialogTitle, setDialogTitle] = useState("");
  
  // For the account statement report
  const [showReportModal, setShowReportModal] = useState(false);
  const [selectedAccount, setSelectedAccount] = useState(null);
  const [fromDate, setFromDate] = useState(format(subDays(new Date(), 30), 'yyyy-MM-dd'));
  const [toDate, setToDate] = useState(format(new Date(), 'yyyy-MM-dd'));

  // Format currency with proper ISO 4217 code
  const formatCurrency = (amount, currencyCode = 'USD') => {
    return new Intl.NumberFormat('en-US', { 
      style: 'currency', 
      currency: currencyCode.split(' ')[0] // Extract ISO code if it has a description
    }).format(amount);
  };

  const handleDelete = (e) => {
    e.preventDefault();
    setProcessing(true);
    
    router.delete(route('accounts.destroy', accountToDelete), {
      onSuccess: () => {
        setShowDeleteModal(false);
        setAccountToDelete(null);
        setProcessing(false);
      },
      onError: () => {
        setProcessing(false);
      }
    });
  };

  const handleDeleteAll = (e) => {
    e.preventDefault();
    if (!tableRef) return;
    
    const selectedIds = tableRef.getSelectedRowModel().rows.map(row => row.original.id);
    if (selectedIds.length === 0) return;

    setProcessing(true);
    router.post(route('accounts.destroy-multiple'), {
      accounts: selectedIds
    }, {
      onSuccess: () => {
        setShowDeleteAllModal(false);
        tableRef.toggleAllRowsSelected(false);
        setProcessing(false);
      },
      onError: () => {
        setProcessing(false);
      }
    });
  };

  const handlePagination = (pagination) => {
    // Ensure we have valid numeric values for page and page size
    const pageIndex = isNaN(pagination.pageIndex) ? 0 : pagination.pageIndex;
    const pageSize = isNaN(pagination.pageSize) ? 10 : pagination.pageSize;
    
    // Create query parameters object (only include non-empty values)
    const params = {
      page: pageIndex + 1, // Convert 0-indexed to 1-indexed
      per_page: pageSize,
    };
    
    // Only add search if it's non-empty
    if (pagination.globalFilter || filters.search) {
      params.search = pagination.globalFilter || filters.search || '';
    }
    
    // Only add column filters if they exist
    const columnFiltersArray = pagination.columnFilters || filters.columnFilters || [];
    if (columnFiltersArray.length > 0) {
      params.columnFilters = JSON.stringify(columnFiltersArray);
    }
    
    // Only add sorting if it exists
    const sortingArray = pagination.sorting || filters.sorting || [];
    if (sortingArray.length > 0) {
      params.sorting = JSON.stringify(sortingArray);
    }
    
    // Update URL and fetch data
    router.get(route('accounts.index'), params, {
      preserveState: true,
      preserveScroll: true,
      only: ['accounts', 'meta', 'filters'],
      replace: false, // Use false to update browser history
    });
  };

  const handleRunReport = (account) => {
    // Navigate directly to the AccountStatement page with the default date range
    router.get(route('accounts.account_statement', account.id));
  };

  const handleDataTableChange = (updatedState) => {
    // Ensure we have valid numeric values for page and page size
    const pageIndex = isNaN(updatedState.pagination.pageIndex) ? 0 : updatedState.pagination.pageIndex;
    const pageSize = isNaN(updatedState.pagination.pageSize) ? 10 : updatedState.pagination.pageSize;
    
    // Create query parameters object (only include non-empty values)
    const params = {
      page: pageIndex + 1, // Convert 0-indexed to 1-indexed
      per_page: pageSize,
    };
    
    // Only add search if it's non-empty
    if (updatedState.globalFilter) {
      params.search = updatedState.globalFilter;
    }
    
    // Only add column filters if they exist
    const columnFiltersArray = updatedState.columnFilters || [];
    if (columnFiltersArray.length > 0) {
      params.columnFilters = JSON.stringify(columnFiltersArray);
    }
    
    // Only add sorting if it exists
    const sortingArray = updatedState.sorting || [];
    if (sortingArray.length > 0) {
      params.sorting = JSON.stringify(sortingArray);
    }
    
    // Update URL and fetch data
    router.get(route('accounts.index'), params, {
      preserveState: true,
      preserveScroll: true,
      only: ['accounts', 'meta', 'filters'],
      replace: false, // Use false to update browser history
    });
  };

  const handleEditAccount = (account) => {
    // Navigate to the Edit.jsx page instead of showing a dialog
    router.get(route('accounts.edit', account.id));
  };

  const getAssetTypeColor = (type) => {
    switch (type) {
      case "Bank":
      case "Cash":
      case "Other Current Asset":
      case "Fixed Asset":
        return "bg-green-100 text-green-800";
      case "Accounts Receivable":
        return "bg-blue-100 text-blue-800";
      case "Accounts Payable":
      case "Credit Card":
      case "Other Current Liability":
      case "Long Term Liability":
        return "bg-red-100 text-red-800";
      case "Equity":
        return "bg-purple-100 text-purple-800";
      case "Income":
      case "Other Income":
        return "bg-emerald-100 text-emerald-800";
      case "Cost of Goods Sold":
      case "Expense":
      case "Other Expense":
        return "bg-amber-100 text-amber-800";
      default:
        return "bg-gray-100 text-gray-800";
    }
  };

  const columns = useMemo(
    () => [
      {
        id: "select",
        header: ({ table }) => (
          <Checkbox
            checked={table.getIsAllPageRowsSelected()}
            onCheckedChange={(value) => table.toggleAllPageRowsSelected(!!value)}
            aria-label="Select all"
          />
        ),
        cell: ({ row }) => (
          <Checkbox
            checked={row.getIsSelected()}
            onCheckedChange={(value) => row.toggleSelected(!!value)}
            aria-label="Select row"
          />
        ),
        enableSorting: false,
        enableHiding: false,
      },
      {
        accessorKey: "account_code",
        header: "Account Code",
      },
      {
        accessorKey: "account_name",
        header: "Account Name",
      },
      {
        accessorKey: "account_type",
        header: "Account Type",
        cell: ({ row }) => (
          <Badge className={`${getAssetTypeColor(row.original.account_type)} hover:bg-transparent hover:text-current`}>
            {row.original.account_type}
          </Badge>
        ),
        filterFn: (row, id, value) => {
          return value.includes(row.getValue(id))
        },
      },
      {
        accessorKey: "currency",
        header: "Currency",
        cell: ({ row }) => (
          <div>{row.original.currency || "---"}</div>
        ),
      },
      {
        accessorKey: "opening_balance",
        header: "Opening Balance",
        cell: ({ row }) => (
          <div className="text-right">
            {row.original.opening_balance ? 
              formatCurrency(row.original.opening_balance, row.original.currency || 'USD') : 
              formatCurrency(0, 'USD')}
          </div>
        ),
      },
      {
        id: "actions",
        header: "Actions",
        cell: ({ row }) => (
          <TableActions
            actions={[
              {
                label: "Edit",
                icon: Edit,
                onClick: () => handleEditAccount(row.original)
              },
              {
                label: "Run Report",
                icon: BarChart,
                onClick: () => handleRunReport(row.original)
              },
              {
                label: "Delete",
                icon: Trash2,
                onClick: () => {
                  setAccountToDelete(row.original.id);
                  setShowDeleteModal(true);
                },
                className: "text-red-600"
              }
            ]}
          />
        ),
      },
    ],
    []
  );

  const filterableColumns = [
    {
      id: "account_type",
      title: "Account Type",
      options: Array.from(new Set(accounts.map(a => a.account_type)))
        .filter(Boolean)
        .map(type => ({ label: type, value: type })),
    },
  ];

  const searchableColumns = [
    {
      id: "account_name",
      title: "Account Name",
    },
    {
      id: "account_code",
      title: "Account Code",
    },
  ];

  return (
    <AuthenticatedLayout>
      <SidebarInset>
        <div className="main-content">
          <PageHeader page="Chart of Accounts" subpage="Accounts List" url="accounts.index" />

          <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
            <div className="flex items-center space-x-2">
              <Link href={route("accounts.create")}>
                <Button>
                  <Plus className="h-4 w-4 mr-2" />
                  Add New Account
                </Button>
              </Link>
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="secondary">
                    <MoreVertical className="h-4 w-4" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <DropdownMenuItem onClick={() => setShowDeleteAllModal(true)}>
                    <Trash2 className="mr-2 h-4 w-4" /> Delete Selected
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>

            <DataTable
              columns={columns}
              data={accounts}
              filterableColumns={filterableColumns}
              searchableColumns={searchableColumns}
              totalRows={meta.total || 0}
              pageCount={meta.last_page || 1}
              onPaginationChange={handlePagination}
              onTableStateChange={handleDataTableChange}
              serverSide={true}
              initialState={{
                pagination: {
                  pageIndex: (meta.current_page || 1) - 1,
                  pageSize: meta.per_page || 10,
                },
                globalFilter: filters.search || '',
                columnFilters: filters.columnFilters || [],
                sorting: filters.sorting || [],
              }}
              tableRef={setTableRef}
              meta={meta}
            />
          </div>

          <DeleteAccountModal
            show={showDeleteModal}
            onClose={() => setShowDeleteModal(false)}
            onConfirm={handleDelete}
            processing={processing}
          />

          <DeleteAllAccountsModal
            show={showDeleteAllModal}
            onClose={() => setShowDeleteAllModal(false)}
            onConfirm={handleDeleteAll}
            processing={processing}
          />

          <Dialog open={openDialog} onOpenChange={setOpenDialog}>
            <DialogContent className="sm:max-w-[80%] max-h-[90vh]">
              <DialogHeader>
                <DialogTitle>{dialogTitle}</DialogTitle>
              </DialogHeader>
              {dialogContent}
            </DialogContent>
          </Dialog>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
