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

const DeleteMedicalRecordsModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm} className="p-6">
      <h2 className="text-lg font-medium">
        Are you sure you want to delete this medical record?
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
          Delete Medical Record
        </Button>
      </div>
    </form>
  </Modal>
);

const DeleteAllMedicalRecordsModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm} className="p-6">
      <h2 className="text-lg font-medium">
        Are you sure you want to delete all selected medical records?
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

export default function List({ records = [], meta = {}, filters = {} }) {
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
  const [recordToDelete, setRecordToDelete] = useState(null);
  const [tableRef, setTableRef] = useState(null);
  const [processing, setProcessing] = useState(false);

  const handleDelete = (e) => {
    e.preventDefault();
    setProcessing(true);
    
    router.delete(route('medical_records.destroy', recordToDelete), {
      onSuccess: () => {
        setShowDeleteModal(false);
        setRecordToDelete(null);
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
    router.post(route('medical_records.destroy-multiple'), {
      medical_records: selectedIds
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

    // Debug the parameters being sent to the server
    console.log('Sending to server:', params);
    
    // Update URL and fetch data
    router.get(route('medical_records.index'), params, {
      preserveState: true,
      preserveScroll: true,
      only: ['medical_records', 'meta', 'filters'],
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
    router.get(route('medical_records.index'), params, {
      preserveState: true,
      preserveScroll: true,
      only: ['medical_records', 'meta', 'filters'],
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
        accessorKey: "patient_id",
        header: "Patient ID",
        cell: ({ row }) => (
          <span className="whitespace-nowrap">
            {row.original.patient_id}
          </span>
        )
      },
      {
        accessorKey: "date",
        header: "Date",
        cell: ({ row }) => (
          <span className="whitespace-nowrap">
            {row.original.date}
          </span>
        )
      },
      {
        accessorKey: "name",
        header: "Name",
        cell: ({ row }) => (
          <span className="whitespace-nowrap">
            {row.original.customer.name}
          </span>
        )
      },
      {
        accessorKey: "age",
        header: "Age",
        cell: ({ row }) => (
          <span className="whitespace-nowrap">
            {row.original.customer.age}
          </span>
        )
      },
      {
        accessorKey: "phone",
        header: "Phone",
        cell: ({ row }) => (
          <span className="whitespace-nowrap">
            {row.original.customer.mobile}
          </span>
        )
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
                onClick: () => router.visit(route('medical_records.edit', row.original.id))
              },
              {
                label: "View",
                icon: Eye,
                onClick: () => router.visit(route('medical_records.show', row.original.id))
              },
              {
                label: "Delete",
                icon: Trash2,
                onClick: () => {
                  setRecordToDelete(row.original.id);
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
          <PageHeader page="Medical Records" subpage="list" url="medical_records.index" />

          <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
            <div>
              <Link href={route("medical_records.create")}>
                <Button>Add New Medical Record</Button>
              </Link>
            </div>

              <DataTable
                columns={columns}
                data={records}
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

          <DeleteMedicalRecordsModal
            show={showDeleteModal}
            onClose={() => setShowDeleteModal(false)}
            onConfirm={handleDelete}
            processing={processing}
          />

          <DeleteAllMedicalRecordsModal
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