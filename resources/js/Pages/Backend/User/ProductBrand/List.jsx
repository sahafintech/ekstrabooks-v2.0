import { Button } from "@/Components/ui/button";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Link, usePage, router } from "@inertiajs/react";
import PageHeader from "@/Components/PageHeader";
import { MoreVertical, Plus, Trash2, Edit, Image } from "lucide-react";
import Modal from "@/Components/Modal";
import { useState, useMemo, useEffect } from "react";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Checkbox } from "@/Components/ui/checkbox";
import { DataTable } from "@/Components/ui/data-table/data-table";
import TableActions from "@/Components/shared/TableActions";
import { Input } from "@/Components/ui/input";

const DeleteBrandModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete this brand?
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
          Delete Brand
        </Button>
      </div>
    </form>
  </Modal>
);

const BrandFormModal = ({ show, onClose, onSubmit, processing, brand = null }) => {
  return (
    <Modal show={show} onClose={onClose}>
      <form onSubmit={onSubmit} className="p-6" encType="multipart/form-data">
        <div className="ti-modal-header">
          <h3 className="text-lg font-bold">
            {brand ? "Edit Brand" : "Create New Brand"}
          </h3>
        </div>
        <div className="mt-4">
          <div className="mb-4">
            <label htmlFor="name" className="block text-sm font-medium text-gray-700">
              Brand Name <span className="text-red-500">*</span>
            </label>
            <Input
              type="text"
              id="name"
              name="name"
              defaultValue={brand?.name || ""}
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
            {brand ? "Update Brand" : "Create Brand"}
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
        Are you sure you want to delete all selected brands?
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
  const { brands, meta, filters } = usePage().props;
  
  // State for modals
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [showDeleteSelectedModal, setShowDeleteSelectedModal] = useState(false);
  const [processing, setProcessing] = useState(false);
  const [selectedBrand, setSelectedBrand] = useState(null);
  const [selectedBrands, setSelectedBrands] = useState([]);
  const [tableRef, setTableRef] = useState(null);

  // Handle delete brand
  const handleShowDeleteModal = (brand) => {
    setSelectedBrand(brand);
    setShowDeleteModal(true);
  };

  const handleDelete = (e) => {
    e.preventDefault();
    setProcessing(true);
    
    router.delete(route('brands.destroy', selectedBrand.id), {
      onSuccess: () => {
        setShowDeleteModal(false);
        setProcessing(false);
        setSelectedBrand(null);
      },
      onError: () => {
        setProcessing(false);
      }
    });
  };

  // Handle create brand
  const handleCreate = (e) => {
    e.preventDefault();
    setProcessing(true);
    
    const formData = new FormData(e.target);
    
    router.post(route('brands.store'), formData, {
      onSuccess: () => {
        setShowCreateModal(false);
        setProcessing(false);
      },
      onError: () => {
        setProcessing(false);
      }
    });
  };

  // Handle edit brand
  const handleShowEditModal = (brand) => {
    setSelectedBrand(brand);
    setShowEditModal(true);
  };

  const handleUpdate = (e) => {
    e.preventDefault();
    setProcessing(true);
    
    const formData = new FormData(e.target);
    formData.append('_method', 'PUT'); // Add method spoofing for Laravel
    
    router.post(route('brands.update', selectedBrand.id), formData, {
      onSuccess: () => {
        setShowEditModal(false);
        setProcessing(false);
        setSelectedBrand(null);
      },
      onError: () => {
        setProcessing(false);
      }
    });
  };

  // Handle delete selected brands
  const handleDeleteSelected = (e) => {
    e.preventDefault();
    setProcessing(true);
    
    // Make sure we have the correct data format for Laravel
    const formData = new FormData();
    selectedBrands.forEach((id, index) => {
      formData.append(`ids[${index}]`, id);
    });
    
    router.post(route('brands.destroy_multiple'), formData, {
      onSuccess: () => {
        setShowDeleteSelectedModal(false);
        setProcessing(false);
        setSelectedBrands([]);
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
    router.get(route('brands.index'), params, {
      preserveState: true,
      preserveScroll: true,
      only: ['brands', 'meta', 'filters'],
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
    router.get(route('brands.index'), params, {
      preserveState: true,
      preserveScroll: true,
      only: ['brands', 'meta', 'filters'],
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
              setSelectedBrands(allPageRowIds);
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
              // Update the selectedBrands state based on the current selection
              setSelectedBrands((prev) => {
                const brandId = row.original.id;
                
                if (value) {
                  // Add to array if not already included
                  return prev.includes(brandId) ? prev : [...prev, brandId];
                } else {
                  // Remove from array
                  return prev.filter((id) => id !== brandId);
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
        accessorKey: "name",
        header: "Name",
        cell: ({ row }) => (
          <div className="flex items-center">
            <span>{row.getValue("name")}</span>
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
          <PageHeader page="Products" subpage="Brands" url="brands.index" />

          <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
            <div className="flex items-center space-x-2">
              <Button onClick={() => setShowCreateModal(true)}>
                <Plus className="mr-2 h-4 w-4" /> Add New Brand
              </Button>
              
              {selectedBrands.length > 0 && (
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
              data={brands}
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

          <DeleteBrandModal
            show={showDeleteModal}
            onClose={() => setShowDeleteModal(false)}
            onConfirm={handleDelete}
            processing={processing}
          />

          <BrandFormModal
            show={showCreateModal}
            onClose={() => setShowCreateModal(false)}
            onSubmit={handleCreate}
            processing={processing}
          />

          <BrandFormModal
            show={showEditModal}
            onClose={() => setShowEditModal(false)}
            onSubmit={handleUpdate}
            processing={processing}
            brand={selectedBrand}
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
