import { Button } from "@/Components/ui/button";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, Link, usePage, router } from "@inertiajs/react";
import PageHeader from "@/Components/PageHeader";
import { MoreVertical, Plus, Trash2, Edit, FileUp, FileDown } from "lucide-react";
import Modal from "@/Components/Modal";
import { useState, useMemo } from "react";
import { DataTable } from "@/Components/ui/data-table/data-table";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Checkbox } from "@/Components/ui/checkbox";

import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";

const DeleteAdjustmentModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm} className="p-6">
      <h2 className="text-lg font-medium">
        Are you sure you want to delete this inventory adjustment?
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
          Delete
        </Button>
      </div>
    </form>
  </Modal>
);

const DeleteSelectedModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm} className="p-6">
      <h2 className="text-lg font-medium">
        Are you sure you want to delete all selected inventory adjustments?
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

export default function List({ adjustments = [], meta = {}, filters = {} }) {
  // State for modals
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showDeleteSelectedModal, setShowDeleteSelectedModal] = useState(false);
  const [processing, setProcessing] = useState(false);
  const [selectedAdjustment, setSelectedAdjustment] = useState(null);
  const [selectedAdjustments, setSelectedAdjustments] = useState([]);
  const [tableRef, setTableRef] = useState(null);

  const handleDeleteAdjustment = (adjustment) => {
    setSelectedAdjustment(adjustment);
    setShowDeleteModal(true);
  };

  const confirmDelete = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.delete(route('inventory_adjustments.destroy', selectedAdjustment.id), {
      onSuccess: () => {
        setShowDeleteModal(false);
        setSelectedAdjustment(null);
        setProcessing(false);
      },
      onError: () => {
        setProcessing(false);
      },
    });
  };

  const handleDeleteSelected = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.post(route('inventory_adjustments.destroy_multiple'), {
      ids: selectedAdjustments
    }, {
      onSuccess: () => {
        setShowDeleteSelectedModal(false);
        setSelectedAdjustments([]);
        setProcessing(false);
      },
      onError: () => {
        setProcessing(false);
      },
    });
  };

  // Add this function to handle row selection
  const handleSelectedRowsChange = (selectedRowIds) => {
    setSelectedAdjustments(selectedRowIds);
  };

  const columns = useMemo(() => [
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
            setSelectedAdjustments(allPageRowIds);
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
            // Update the selectedCategories state based on the current selection
            setSelectedAdjustments((prev) => {
              const adjustmentId = row.original.id;
              
              if (value) {
                // Add to array if not already included
                return prev.includes(adjustmentId) ? prev : [...prev, adjustmentId];
              } else {
                // Remove from array
                return prev.filter((id) => id !== adjustmentId);
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
      id: 'product',
      header: 'Product',
      accessorFn: (row) => row.product ? row.product.name : 'N/A',
      cell: ({ row }) => {
        const product = row.original.product;
        return product ? product.name : 'N/A';
      }
    },
    {
      id: 'quantity_on_hand',
      header: 'Quantity On Hand',
      accessorKey: 'quantity_on_hand',
    },
    {
      id: 'adjusted_quantity',
      header: 'Adjusted Quantity',
      accessorKey: 'adjusted_quantity',
      cell: ({ row }) => {
        const quantity = parseFloat(row.original.adjusted_quantity || 0);
        return (
          <span className={quantity > 0 ? 'text-green-500' : 'text-red-500'}>
            {quantity > 0 ? `+${quantity}` : quantity}
          </span>
        );
      }
    },
    {
      id: 'new_quantity_on_hand',
      header: 'New Quantity',
      accessorKey: 'new_quantity_on_hand',
    },
    {
      id: 'adjustment_date',
      header: 'Date',
      accessorKey: 'adjustment_date',
    },
    {
      id: 'description',
      header: 'Description',
      accessorKey: 'description',
      cell: ({ row }) => {
        const description = row.original.description;
        return description ? description : 'N/A';
      }
    },
    {
      id: 'actions',
      header: 'Actions',
      cell: ({ row }) => (
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" className="h-8 w-8 p-0">
              <span className="sr-only">Open menu</span>
              <MoreVertical className="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem asChild>
              <Link
                href={route('inventory_adjustments.edit', row.original.id)}
                className="cursor-pointer"
              >
                <Edit className="mr-2 h-4 w-4" />
                Edit
              </Link>
            </DropdownMenuItem>
            <DropdownMenuItem
              onClick={() => handleDeleteAdjustment(row.original)}
              className="cursor-pointer text-red-600"
            >
              <Trash2 className="mr-2 h-4 w-4" />
              Delete
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      ),
    },
  ], []);

  return (
    <AuthenticatedLayout>
      <Head title="Inventory Adjustments" />
      <SidebarInset>
        <div className="main-content">
          <PageHeader page="Inventory Adjustments" subpage="list" url="inventory_adjustments.index" />

          <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
            <div className="flex items-center space-x-2">
              <Link href={route("inventory_adjustments.create")}>
                <Button>Add New Adjustment</Button>
              </Link>
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="secondary">
                    <MoreVertical className="h-4 w-4" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <DropdownMenuItem asChild>
                    <Link href={route('inventory_adjustments.import')}>
                      <FileUp className="mr-2 h-4 w-4" /> Import
                    </Link>
                  </DropdownMenuItem>
                  <DropdownMenuItem asChild>
                    <Link href={route('inventory_adjustments.export')}>
                      <FileDown className="mr-2 h-4 w-4" /> Export
                    </Link>
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
              {selectedAdjustments.length > 0 && (
                <Button 
                  variant="destructive"
                  onClick={() => setShowDeleteSelectedModal(true)}
                >
                  <Trash2 className="mr-2 h-4 w-4" />
                  Delete Selected ({selectedAdjustments.length})
                </Button>
              )}
            </div>
            
            <DataTable
              tableRef={setTableRef}
              columns={columns}
              data={adjustments}
              meta={meta}
              filters={filters}
              onSelectedRowsChange={handleSelectedRowsChange}
            />
          </div>
        </div>
      </SidebarInset>

      <DeleteAdjustmentModal
        show={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        onConfirm={confirmDelete}
        processing={processing}
      />

      <DeleteSelectedModal
        show={showDeleteSelectedModal}
        onClose={() => setShowDeleteSelectedModal(false)}
        onConfirm={handleDeleteSelected}
        processing={processing}
      />
    </AuthenticatedLayout>
  );
}
