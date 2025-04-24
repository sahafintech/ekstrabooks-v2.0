import { Button } from "@/Components/ui/button";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Link, usePage, router } from "@inertiajs/react";
import PageHeader from "@/Components/PageHeader";
import { MoreVertical, Plus, Trash2, Edit } from "lucide-react";
import Modal from "@/Components/Modal";
import { useState, useMemo } from "react";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Checkbox } from "@/Components/ui/checkbox";
import { DataTable } from "@/Components/ui/data-table/data-table";
import TableActions from "@/Components/shared/TableActions";
import { Input } from "@/Components/ui/input";

const DeleteUnitModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete this product unit?
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
          Delete Unit
        </Button>
      </div>
    </form>
  </Modal>
);

const UnitFormModal = ({ show, onClose, onSubmit, processing, unit = null }) => {
  return (
    <Modal show={show} onClose={onClose}>
      <form onSubmit={onSubmit} className="p-6">
        <div className="ti-modal-header">
          <h3 className="text-lg font-bold">
            {unit ? "Edit Product Unit" : "Create New Product Unit"}
          </h3>
        </div>
        <div className="mt-4">
          <div className="mb-4">
            <label htmlFor="unit" className="block text-sm font-medium text-gray-700">
              Unit Name <span className="text-red-500">*</span>
            </label>
            <Input
              type="text"
              id="unit"
              name="unit"
              defaultValue={unit?.unit || ""}
              required
              className="mt-1"
            />
          </div>
        </div>
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
            disabled={processing}
          >
            {unit ? "Update Unit" : "Create Unit"}
          </Button>
        </div>
      </form>
    </Modal>
  );
};

const DeleteSelectedModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete all selected product units?
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
          Delete All
        </Button>
      </div>
    </form>
  </Modal>
);

export default function List() {
  const { product_units, meta, filters } = usePage().props;
  
  // State for modals
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [showDeleteSelectedModal, setShowDeleteSelectedModal] = useState(false);
  const [processing, setProcessing] = useState(false);
  const [selectedUnit, setSelectedUnit] = useState(null);
  const [selectedUnits, setSelectedUnits] = useState([]);
  const [tableRef, setTableRef] = useState(null);

  // Handle delete unit
  const handleShowDeleteModal = (unit) => {
    setSelectedUnit(unit);
    setShowDeleteModal(true);
  };

  const handleDelete = (e) => {
    e.preventDefault();
    setProcessing(true);
    
    router.delete(route('product_units.destroy', selectedUnit.id), {
      onSuccess: () => {
        setShowDeleteModal(false);
        setProcessing(false);
        setSelectedUnit(null);
      },
      onError: () => {
        setProcessing(false);
      }
    });
  };

  // Handle create unit
  const handleCreate = (e) => {
    e.preventDefault();
    setProcessing(true);
    
    const formData = new FormData(e.target);
    
    router.post(route('product_units.store'), formData, {
      onSuccess: () => {
        setShowCreateModal(false);
        setProcessing(false);
      },
      onError: () => {
        setProcessing(false);
      }
    });
  };

  // Handle edit unit
  const handleShowEditModal = (unit) => {
    setSelectedUnit(unit);
    setShowEditModal(true);
  };

  const handleUpdate = (e) => {
    e.preventDefault();
    setProcessing(true);
    
    const formData = new FormData(e.target);
    formData.append('_method', 'PUT'); // Add method spoofing for Laravel
    
    router.post(route('product_units.update', selectedUnit.id), formData, {
      onSuccess: () => {
        setShowEditModal(false);
        setProcessing(false);
        setSelectedUnit(null);
      },
      onError: () => {
        setProcessing(false);
      }
    });
  };

  // Handle delete selected units
  const handleDeleteSelected = (e) => {
    e.preventDefault();
    setProcessing(true);
    
    // Make sure we have the correct data format for Laravel
    const formData = new FormData();
    selectedUnits.forEach((id, index) => {
      formData.append(`ids[${index}]`, id);
    });
    
    router.post(route('product_units.destroy_multiple'), formData, {
      onSuccess: () => {
        setShowDeleteSelectedModal(false);
        setProcessing(false);
        setSelectedUnits([]);
      },
      onError: (errors) => {
        setProcessing(false);
      }
    });
  };

  // When pagination changes
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
    router.get(route('product_units.index'), params, {
      preserveState: true,
      preserveScroll: true,
      only: ['product_units', 'meta', 'filters'],
      replace: false, // Use false to update browser history
    });
  };

  // When global filter, column filters, or sorting changes
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
    router.get(route('product_units.index'), params, {
      preserveState: true,
      preserveScroll: true,
      only: ['product_units', 'meta', 'filters'],
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
            onCheckedChange={(value) => {
              table.toggleAllPageRowsSelected(!!value);
              // Get all row IDs when checked, empty array when unchecked
              const allPageRowIds = value
                ? table.getRowModel().rows.map((row) => row.original.id)
                : [];
              setSelectedUnits(allPageRowIds);
            }}
            aria-label="Select all"
            className="translate-y-[2px]"
          />
        ),
        cell: ({ row }) => (
          <Checkbox
            checked={row.getIsSelected()}
            onCheckedChange={(value) => {
              row.toggleSelected(!!value);
              // Update the selectedUnits state based on the current selection
              setSelectedUnits((prev) => {
                const unitId = row.original.id;
                
                if (value) {
                  // Add to array if not already included
                  return prev.includes(unitId) ? prev : [...prev, unitId];
                } else {
                  // Remove from array
                  return prev.filter((id) => id !== unitId);
                }
              });
            }}
            aria-label="Select row"
            className="translate-y-[2px]"
          />
        ),
        enableSorting: false,
        enableHiding: false,
      },
      {
        accessorKey: "id",
        header: "ID",
        cell: ({ row }) => <div className="w-[80px]">{row.getValue("id")}</div>,
      },
      {
        accessorKey: "unit",
        header: "Unit Name",
        cell: ({ row }) => (
          <div className="flex items-center">
            <span>{row.getValue("unit")}</span>
          </div>
        ),
      },
      {
        id: "actions",
        cell: ({ row }) => {
          return (
            <TableActions
              actions={[
                {
                  label: "Edit",
                  icon: Edit,
                  onClick: () => handleShowEditModal(row.original),
                },
                {
                  label: "Delete",
                  icon: Trash2,
                  onClick: () => handleShowDeleteModal(row.original),
                  className: "text-red-600",
                },
              ]}
            />
          );
        },
      },
    ],
    []
  );

  return (
    <AuthenticatedLayout>
      <SidebarInset>
        <div className="main-content">
          <PageHeader page="Products" subpage="Product Units" url="product_units.index" />

          <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
            <div className="flex items-center space-x-2">
              <Button onClick={() => setShowCreateModal(true)}>
                <Plus className="mr-2 h-4 w-4" /> Add New Unit
              </Button>
              
              {selectedUnits.length > 0 && (
                <Button 
                  variant="destructive" 
                  onClick={() => setShowDeleteSelectedModal(true)}
                >
                  <Trash2 className="mr-2 h-4 w-4" /> Delete Selected
                </Button>
              )}
            </div>

            <DataTable
              columns={columns}
              data={product_units}
              totalRows={meta?.total || 0}
              pageCount={meta?.last_page || 1}
              onPaginationChange={handlePagination}
              onTableStateChange={handleDataTableChange}
              serverSide={true}
              initialState={{
                pagination: {
                  pageIndex: (meta?.current_page || 1) - 1,
                  pageSize: meta?.per_page || 10,
                },
                globalFilter: filters?.search || '',
                columnFilters: filters?.columnFilters || [],
                sorting: filters?.sorting || [],
              }}
              tableRef={setTableRef}
              meta={meta}
            />
          </div>

          <DeleteUnitModal
            show={showDeleteModal}
            onClose={() => setShowDeleteModal(false)}
            onConfirm={handleDelete}
            processing={processing}
          />

          <UnitFormModal
            show={showCreateModal}
            onClose={() => setShowCreateModal(false)}
            onSubmit={handleCreate}
            processing={processing}
          />

          <UnitFormModal
            show={showEditModal}
            onClose={() => setShowEditModal(false)}
            onSubmit={handleUpdate}
            processing={processing}
            unit={selectedUnit}
          />

          <DeleteSelectedModal
            show={showDeleteSelectedModal}
            onClose={() => setShowDeleteSelectedModal(false)}
            onConfirm={handleDeleteSelected}
            processing={processing}
          />
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
