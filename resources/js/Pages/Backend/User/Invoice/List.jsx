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

const DeleteInvoiceModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm} className="p-6">
      <h2 className="text-lg font-medium">
        Are you sure you want to delete this invoice?
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
          Delete Invoice
        </Button>
      </div>
    </form>
  </Modal>
);

const ImportInvoicesModal = ({ show, onClose, onSubmit, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onSubmit} className="p-6">
      <div className="ti-modal-header">
        <h3 className="text-lg font-bold">Import Invoices</h3>
      </div>
      <div className="ti-modal-body grid grid-cols-12">
        <div className="col-span-12">
          <div className="flex items-center justify-between">
            <label className="block font-medium text-sm text-gray-700">
              Invoices File
            </label>
            <Link href="/uploads/media/default/sample_invoices.xlsx">
              <Button variant="secondary" size="sm">
                Use This Sample File
              </Button>
            </Link>
          </div>
          <input type="file" className="w-full dropify" name="invoices_file" required />
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
          Import Invoices
        </Button>
      </div>
    </form>
  </Modal>
);

const DeleteAllInvoicesModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm} className="p-6">
      <h2 className="text-lg font-medium">
        Are you sure you want to delete all selected invoices?
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

const InvoiceStatusBadge = ({ status }) => {
  const statusMap = {
    0: { label: "Draft", className: "text-gray-600" },
    1: { label: "Active", className: "text-blue-600" },
    2: { label: "Paid", className: "text-success" },
    3: { label: "Partially Paid", className: "text-warning" },
    4: { label: "Canceled", className: "text-danger" }
  };

  return (
    <span className={statusMap[status].className}>
      {statusMap[status].label}
    </span>
  );
};

export default function List({ invoices = [], meta = {} }) {
  const { auth } = usePage().props;
  const [selectedInvoices, setSelectedInvoices] = useState([]);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showImportModal, setShowImportModal] = useState(false);
  const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
  const [invoiceToDelete, setInvoiceToDelete] = useState(null);
  const [tableRef, setTableRef] = useState(null);
  const [processing, setProcessing] = useState(false);

  const handleDelete = (e) => {
    e.preventDefault();
    setProcessing(true);
    
    router.delete(route('invoices.destroy', invoiceToDelete), {
      onSuccess: () => {
        setShowDeleteModal(false);
        setInvoiceToDelete(null);
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
    router.post(route('invoices.destroy-multiple'), {
      invoices: selectedIds
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
    
    router.post(route('invoices.import'), formData, {
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
    const params = new URLSearchParams(window.location.search);
    params.set('page', pagination.pageIndex + 1);
    params.set('per_page', pagination.pageSize);

    router.get(`${route('invoices.index')}?${params.toString()}`, {}, {
      preserveState: true,
      preserveScroll: true,
      only: ['invoices', 'meta']
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
        accessorKey: "invoice_number",
        header: "Invoice Number",
      },
      {
        accessorKey: "customer",
        header: "Customer",
        cell: ({ row }) => row.original.customer.name,
      },
      {
        accessorKey: "invoice_date",
        header: "Date",
      },
      {
        accessorKey: "due_date",
        header: "Due Date",
      },
      {
        accessorKey: "grand_total",
        header: "Grand Total",
        cell: ({ row }) => (
          <div className="text-right">{row.original.grand_total}</div>
        ),
      },
      {
        accessorKey: "paid",
        header: "Paid",
        cell: ({ row }) => (
          <div className="text-right">{row.original.paid}</div>
        ),
      },
      {
        accessorKey: "due",
        header: "Due",
        cell: ({ row }) => (
          <div className="text-right">{row.original.grand_total - row.original.paid}</div>
        ),
      },
      {
        accessorKey: "status",
        header: "Status",
        cell: ({ row }) => (
          <div className="text-center">
            <InvoiceStatusBadge status={row.original.status} />
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
                onClick: () => router.visit(route('invoices.edit', row.original.id))
              },
              {
                label: "View",
                icon: Eye,
                onClick: () => router.visit(route('invoices.show', row.original.id))
              },
              {
                label: "Delete",
                icon: Trash2,
                onClick: () => {
                  setInvoiceToDelete(row.original.id);
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
        { label: "Draft", value: "0" },
        { label: "Active", value: "1" },
        { label: "Paid", value: "2" },
        { label: "Partially Paid", value: "3" },
        { label: "Canceled", value: "4" },
      ],
    },
  ];

  const searchableColumns = [
    {
      id: "invoice_number",
      title: "Invoice Number",
    },
    {
      id: "customer.name",
      title: "Customer Name",
    },
  ];

  return (
    <AuthenticatedLayout>
      <SidebarInset>
        <div className="main-content">
          <PageHeader page="Invoices" subpage="list" url="invoices.index" />

          <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
            <div className="flex justify-between">
              <Link href={route("invoices.create")}>
                <Button>Add New Invoice</Button>
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
                    <Link href={route('invoices.export')}>
                      <FileDown className="mr-2 h-4 w-4" /> Export
                    </Link>
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>

            <DataTable
              columns={columns}
              data={invoices}
              filterableColumns={filterableColumns}
              searchableColumns={searchableColumns}
              totalRows={meta.total || 0}
              pageCount={meta.last_page || 1}
              onPaginationChange={handlePagination}
              tableRef={setTableRef}
              meta={meta}
            />
          </div>

          <DeleteInvoiceModal
            show={showDeleteModal}
            onClose={() => setShowDeleteModal(false)}
            onConfirm={handleDelete}
            processing={processing}
          />

          <ImportInvoicesModal
            show={showImportModal}
            onClose={() => setShowImportModal(false)}
            onSubmit={handleImport}
            processing={processing}
          />

          <DeleteAllInvoicesModal
            show={showDeleteAllModal}
            onClose={() => setShowDeleteAllModal(false)}
            onConfirm={handleDeleteAll}
            processing={processing}
          />
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
