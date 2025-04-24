import { Button } from "@/Components/ui/button";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Link, usePage, router } from "@inertiajs/react";
import PageHeader from "@/Components/PageHeader";
import { MoreVertical, FileUp, FileDown, Plus, Eye, Trash2, Edit } from "lucide-react";
import Modal from "@/Components/Modal";
import { useState, useMemo } from "react";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Checkbox } from "@/Components/ui/checkbox";
import { DataTable } from "@/Components/ui/data-table/data-table";
import TableActions from "@/Components/shared/TableActions";

import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";

const DeleteProductModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete this product?
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
          Delete Product
        </Button>
      </div>
    </form>
  </Modal>
);

const ImportProductsModal = ({ show, onClose, onSubmit, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onSubmit} className="p-6">
      <div className="ti-modal-header">
        <h3 className="text-lg font-bold">Import Products</h3>
      </div>
      <div className="ti-modal-body grid grid-cols-12">
        <div className="col-span-12">
          <div className="flex items-center justify-between">
            <label className="block font-medium text-sm text-gray-700">
              Products File
            </label>
            <Link href="/uploads/media/default/sample_items.xlsx">
              <Button variant="secondary" size="sm">
                Use This Sample File
              </Button>
            </Link>
          </div>
          <input type="file" className="w-full dropify" name="products_file" required />
        </div>
        <div className="col-span-12 mt-4">
          <ul className="space-y-3 text-sm">
            <li className="flex space-x-3">
              <span className="text-primary bg-primary/20 rounded-full px-1">✓</span>
              <span className="text-gray-800 dark:text-white/70">
                Maximum File Size: 1 MB
              </span>
            </li>
            <li className="flex space-x-3">
              <span className="text-primary bg-primary/20 rounded-full px-1">✓</span>
              <span className="text-gray-800 dark:text-white/70">
                File format Supported: CSV, TSV, XLS
              </span>
            </li>
            <li className="flex space-x-3">
              <span className="text-primary bg-primary/20 rounded-full px-1">✓</span>
              <span className="text-gray-800 dark:text-white/70">
                Make sure the format of the import file matches our sample file by comparing them.
              </span>
            </li>
          </ul>
        </div>
      </div>
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
          disabled={processing}
        >
          Import Items
        </Button>
      </div>
    </form>
  </Modal>
);

const DeleteAllProductsModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete all selected products?
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

const ProductStatusBadge = ({ status }) => (
  <span className={status === 1 ? "text-success" : "text-danger"}>
    {status === 1 ? "Active" : "Disabled"}
  </span>
);

export default function List({ products = [], meta = {}, filters = {} }) {
  const { auth } = usePage().props;
  const [selectedProducts, setSelectedProducts] = useState([]);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showImportModal, setShowImportModal] = useState(false);
  const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
  const [productToDelete, setProductToDelete] = useState(null);
  const [tableRef, setTableRef] = useState(null);
  const [processing, setProcessing] = useState(false);

  // Format currency with proper ISO 4217 code
  const formatCurrency = (amount, currencyCode = 'USD') => {
    return new Intl.NumberFormat('en-US', { 
      style: 'currency', 
      currency: currencyCode 
    }).format(amount);
  };

  const handleDelete = (e) => {
    e.preventDefault();
    setProcessing(true);
    
    router.delete(route('products.destroy', productToDelete), {
      onSuccess: () => {
        setShowDeleteModal(false);
        setProductToDelete(null);
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
    router.post(route('products.destroy-multiple'), {
      products: selectedIds
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

  const handleImport = (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    setProcessing(true);
    
    router.post(route('products.import'), formData, {
      onSuccess: () => {
        setShowImportModal(false);
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

    // Debug the parameters being sent to the server
    console.log('Sending to server:', params);
    
    // Update URL and fetch data
    router.get(route('products.index'), params, {
      preserveState: true,
      preserveScroll: true,
      only: ['products', 'meta', 'filters'],
      replace: false, // Use false to update browser history
    });
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

    // Debug the parameters being sent to the server
    console.log('Table state change:', params);
    
    // Update URL and fetch data
    router.get(route('products.index'), params, {
      preserveState: true,
      preserveScroll: true,
      only: ['products', 'meta', 'filters'],
      replace: false, // Use false to update browser history
    });
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
        accessorKey: "image",
        header: "Image",
        cell: ({ row }) => (
          <img 
            src={`/uploads/media/${row.original.image}`} 
            className="w-14" 
            alt={row.original.name} 
          />
        ),
        enableSorting: false,
      },
      {
        accessorKey: "name",
        header: "Name",
      },
      {
        accessorKey: "type",
        header: "Type",
      },
      {
        accessorKey: "purchase_cost",
        header: "Purchase Cost",
        cell: ({ row }) => (
          <div className="text-right">
            {formatCurrency(row.original.purchase_cost, row.original.currency || 'USD')}
          </div>
        ),
      },
      {
        accessorKey: "selling_price",
        header: "Selling Price",
        cell: ({ row }) => (
          <div className="text-right">
            {formatCurrency(row.original.selling_price, row.original.currency || 'USD')}
          </div>
        ),
      },
      {
        accessorKey: "stock",
        header: "Stock",
        cell: ({ row }) => (
          <div className="text-center">{row.original.stock}</div>
        ),
      },
      {
        accessorKey: "status",
        header: "Status",
        cell: ({ row }) => (
          <div className="text-center">
            <ProductStatusBadge status={row.original.status} />
          </div>
        ),
        filterFn: (row, id, value) => {
          return value.includes(row.getValue(id))
        },
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
                onClick: () => router.visit(route('products.edit', row.original.id))
              },
              {
                label: "View",
                icon: Eye,
                onClick: () => router.visit(route('products.show', row.original.id))
              },
              {
                label: "Delete",
                icon: Trash2,
                onClick: () => {
                  setProductToDelete(row.original.id);
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
      id: "status",
      title: "Status",
      options: [
        { label: "Active", value: "1" },
        { label: "Disabled", value: "0" },
      ],
    },
    {
      id: "type",
      title: "Type",
      options: Array.from(new Set(products.map(p => p.type)))
        .filter(Boolean)
        .map(type => ({ label: type, value: type })),
    },
  ];

  const searchableColumns = [
    {
      id: "name",
      title: "Name",
    },
  ];

  return (
    <AuthenticatedLayout>
      <SidebarInset>
        <div className="main-content">
          <PageHeader page="Products" subpage="list" url="products.index" />

          <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
            <div className="flex items-center space-x-2">
              <Link href={route("products.create")}>
                <Button>Add New Product</Button>
              </Link>
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="secondary">
                    <MoreVertical className="h-4 w-4" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <DropdownMenuItem onClick={() => setShowImportModal(true)}>
                    <FileUp className="mr-2 h-4 w-4" /> Import
                  </DropdownMenuItem>
                  <DropdownMenuItem asChild>
                    <Link href={route('products.export')}>
                      <FileDown className="mr-2 h-4 w-4" /> Export
                    </Link>
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>

              <DataTable
                columns={columns}
                data={products}
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

          <DeleteProductModal
            show={showDeleteModal}
            onClose={() => setShowDeleteModal(false)}
            onConfirm={handleDelete}
            processing={processing}
          />

          <DeleteAllProductsModal
            show={showDeleteAllModal}
            onClose={() => setShowDeleteAllModal(false)}
            onConfirm={handleDeleteAll}
            processing={processing}
          />

          <ImportProductsModal
            show={showImportModal}
            onClose={() => setShowImportModal(false)}
            onSubmit={handleImport}
            processing={processing}
          />
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}