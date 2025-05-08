import React, { useState, useEffect } from "react";
import { Head, Link, router, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import { Checkbox } from "@/Components/ui/checkbox";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/Components/ui/table";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/ui/select";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import { Input } from "@/Components/ui/input";
import { Edit, EyeIcon, FileDown, FileUp, MoreVertical, Plus, Trash, ChevronUp, ChevronDown } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";

// Delete Confirmation Modal Component
const DeleteAdjustmentModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete this adjustment?
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

// Bulk Delete Confirmation Modal Component
const DeleteAllAdjustmentsModal = ({ show, onClose, onConfirm, processing, count }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete {count} selected adjustment{count !== 1 ? 's' : ''}?
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
          Delete Selected
        </Button>
      </div>
    </form>
  </Modal>
);

// Import Adjustments Modal Component
const ImportAdjustmentsModal = ({ show, onClose, onSubmit, processing }) => (
  <Modal show={show} onClose={onClose} maxWidth="3xl">
    <form onSubmit={onSubmit}>
      <div className="ti-modal-header">
        <h3 className="text-lg font-bold">Import Adjustments</h3>
      </div>
      <div className="ti-modal-body grid grid-cols-12">
        <div className="col-span-12">
          <div className="flex items-center justify-between">
            <label className="block font-medium text-sm text-gray-700">
              Adjustments File
            </label>
            <a href="/uploads/media/default/sample_adjustments.xlsx" download>
              <Button variant="secondary" size="sm" type="button">
                Use This Sample File
              </Button>
            </a>
          </div>
          <input type="file" className="w-full dropify" name="adjustments_file" required />
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
          Import
        </Button>
      </div>
    </form>
  </Modal>
);

export default function List({ adjustments = [], meta = {}, filters = {} }) {
  const { flash = {} } = usePage().props;
  const { toast } = useToast();
  const [selectedAdjustments, setSelectedAdjustments] = useState([]);
  const [isAllSelected, setIsAllSelected] = useState(false);
  const [search, setSearch] = useState(filters.search || "");
  const [perPage, setPerPage] = useState(meta.per_page || 50);
  const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
  const [bulkAction, setBulkAction] = useState("");

  // Delete confirmation modal states
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
  const [showImportModal, setShowImportModal] = useState(false);
  const [adjustmentsToDelete, setAdjustmentsToDelete] = useState(null);
  const [processing, setProcessing] = useState(false);

  const [sorting, setSorting] = useState(filters.sorting || { column: "id", direction: "desc" });

  useEffect(() => {
    if (flash && flash.success) {
      toast({
        title: "Success",
        description: flash.success,
      });
    }

    if (flash && flash.error) {
      toast({
        variant: "destructive",
        title: "Error",
        description: flash.error,
      });
    }
  }, [flash, toast]);

  const toggleSelectAll = () => {
    if (isAllSelected) {
      setSelectedAdjustments([]);
    } else {
      setSelectedAdjustments(adjustments.map((adjustment) => adjustment.id));
    }
    setIsAllSelected(!isAllSelected);
  };

  const toggleSelectAdjustment = (id) => {
    if (selectedAdjustments.includes(id)) {
      setSelectedAdjustments(selectedAdjustments.filter((adjustmentId) => adjustmentId !== id));
      setIsAllSelected(false);
    } else {
      setSelectedAdjustments([...selectedAdjustments, id]);
      if (selectedAdjustments.length + 1 === adjustments.length) {
        setIsAllSelected(true);
      }
    }
  };

  const handleSearch = (e) => {
    e.preventDefault();
    const value = e.target.value;
    setSearch(value);

    router.get(
      route("inventory_adjustments.index"),
      { search: value, page: 1, per_page: perPage },
      { preserveState: true }
    );
  };


  const handlePerPageChange = (value) => {
    setPerPage(value);
    router.get(
      route("inventory_adjustments.index"),
      { search, page: 1, per_page: value },
      { preserveState: true }
    );
  };

  const handlePageChange = (page) => {
    setCurrentPage(page);
    router.get(
      route("inventory_adjustments.index"),
      { search, page, per_page: perPage },
      { preserveState: true }
    );
  };

  const handleBulkAction = () => {
    if (bulkAction === "") return;

    if (selectedAdjustments.length === 0) {
      toast({
        variant: "destructive",
        title: "Error",
        description: "Please select at least one adjustment",
      });
      return;
    }

    if (bulkAction === "delete") {
      setShowDeleteAllModal(true);
    }
  };

  const handleDeleteConfirm = (id) => {
    setAdjustmentsToDelete(id);
    setShowDeleteModal(true);
  };

  const handleDelete = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.delete(route('inventory_adjustments.destroy', adjustmentsToDelete), {
      onSuccess: () => {
        setShowDeleteModal(false);
        setAdjustmentsToDelete(null);
        setProcessing(false);
      },
      onError: () => {
        setProcessing(false);
      }
    });
  };

  const handleDeleteAll = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.post(route('inventory_adjustments.bulk_destroy'),
      {
        ids: selectedAdjustments
      },
      {
        onSuccess: () => {
          setShowDeleteAllModal(false);
          setSelectedAdjustments([]);
          setIsAllSelected(false);
          setProcessing(false);
        },
        onError: () => {
          setProcessing(false);
        }
      }
    );
  };

  const handleImport = (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    setProcessing(true);

    router.post(route('inventory_adjustments.import'), formData, {
      onSuccess: () => {
        setShowImportModal(false);
        setProcessing(false);
      },
      onError: () => {
        setProcessing(false);
      }
    });
  };

  const handleSort = (column) => {
    let direction = "asc";
    if (sorting.column === column && sorting.direction === "asc") {
      direction = "desc";
    }
    setSorting({ column, direction });
    router.get(
      route("inventory_adjustments.index"),
      { ...filters, sorting: { column, direction }, page: 1, per_page: perPage },
      { preserveState: true }
    );
  };

  const renderSortIcon = (column) => {
    const isActive = sorting.column === column;
    const upColor = isActive && sorting.direction === "asc" ? "text-gray-900" : "text-gray-300";
    const downColor = isActive && sorting.direction === "desc" ? "text-gray-900" : "text-gray-300";
    return (
      <span className="inline-flex flex-col ml-1">
        <ChevronUp className={`w-4 h-4 ${upColor}`} />
        <ChevronDown className={`w-4 h-4 -mt-1 ${downColor}`} />
      </span>
    );
  };

  const renderPageNumbers = () => {
    const totalPages = meta.last_page;
    const pages = [];
    const maxPagesToShow = 5;

    let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
    let endPage = startPage + maxPagesToShow - 1;

    if (endPage > totalPages) {
      endPage = totalPages;
      startPage = Math.max(1, endPage - maxPagesToShow + 1);
    }

    for (let i = startPage; i <= endPage; i++) {
      pages.push(
        <Button
          key={i}
          variant={i === currentPage ? "default" : "outline"}
          size="sm"
          onClick={() => handlePageChange(i)}
          className="mx-1"
        >
          {i}
        </Button>
      );
    }

    return pages;
  };

  const exportAdjustments = () => {
    window.location.href = route("inventory_adjustments.export");
  };

  const AdjustmentTypeBadge = ({ status }) => {
    const statusMap = {
      'adds': { label: "Added", className: "text-green-600 bg-green-200 px-3 py-1 rounded text-sm" },
      'deducts': { label: "Deducted", className: "text-red-600 bg-red-200 px-3 py-1 rounded text-sm" },
    };

    return (
      <span className={statusMap[status].className}>
        {statusMap[status].label}
      </span>
    );
  };

  return (
    <AuthenticatedLayout>
      <Head title="Inventory Adjustments" />
      <Toaster />
      <SidebarInset>
        <div className="main-content">
          <PageHeader
            page="Inventory Adjustments"
            subpage="List"
            url="inventory_adjustments.index"
          />
          <div className="p-4">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
              <div className="flex flex-col md:flex-row gap-2">
                <Link href={route("inventory_adjustments.create")}>
                  <Button>
                    <Plus className="w-4 h-4 mr-2" />
                    Add Adjustment
                  </Button>
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
                    <DropdownMenuItem onClick={exportAdjustments}>
                      <FileDown className="mr-2 h-4 w-4" /> Export
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
              <div className="flex flex-col md:flex-row gap-4 md:items-center">
                <Input
                  placeholder="Search adjustments..."
                  value={search}
                  onChange={(e) => handleSearch(e)}
                  className="w-full md:w-80"
                />
              </div>
            </div>

            <div className="mb-4 flex flex-col md:flex-row gap-4 justify-between">
              <div className="flex items-center gap-2">
                <Select value={bulkAction} onValueChange={setBulkAction}>
                  <SelectTrigger className="w-[180px]">
                    <SelectValue placeholder="Bulk actions" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="delete">Delete Selected</SelectItem>
                  </SelectContent>
                </Select>
                <Button onClick={handleBulkAction} variant="outline">
                  Apply
                </Button>
              </div>
              <div className="flex items-center gap-2">
                <span className="text-sm text-gray-500">Show</span>
                <Select value={perPage.toString()} onValueChange={handlePerPageChange}>
                  <SelectTrigger className="w-[80px]">
                    <SelectValue placeholder="10" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="10">10</SelectItem>
                    <SelectItem value="25">25</SelectItem>
                    <SelectItem value="50">50</SelectItem>
                    <SelectItem value="100">100</SelectItem>
                  </SelectContent>
                </Select>
                <span className="text-sm text-gray-500">entries</span>
              </div>
            </div>

            <div className="rounded-md border">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className="w-[50px]">
                      <Checkbox
                        checked={isAllSelected}
                        onCheckedChange={toggleSelectAll}
                      />
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("id")}>
                      ID {renderSortIcon("id")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("adjustment_date")}>
                      Date {renderSortIcon("adjustment_date")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("product.name")}>
                      Product Name {renderSortIcon("product.name")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("quantity_on_hand")}>
                      Qty on Hand {renderSortIcon("quantity_on_hand")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("adjusted_quantity")}>
                      Qty Adjusted {renderSortIcon("adjusted_quantity")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("adjustment_type")}>
                      Adjustment Type {renderSortIcon("adjustment_type")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("new_quantity_on_hand")}>
                      New Quantity {renderSortIcon("new_quantity_on_hand")}
                    </TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {adjustments.length > 0 ? (
                    adjustments.map((adjustment) => (
                      <TableRow key={adjustment.id}>
                        <TableCell>
                          <Checkbox
                            checked={selectedAdjustments.includes(adjustment.id)}
                            onCheckedChange={() => toggleSelectAdjustment(adjustment.id)}
                          />
                        </TableCell>
                        <TableCell>{adjustment.id}</TableCell>
                        <TableCell>{adjustment.adjustment_date}</TableCell>
                        <TableCell>{adjustment.product.name}</TableCell>
                        <TableCell>{adjustment.quantity_on_hand}</TableCell>
                        <TableCell>{adjustment.adjusted_quantity}</TableCell>
                        <TableCell>
                          {<AdjustmentTypeBadge status={adjustment.adjustment_type} /> || "-"}
                        </TableCell>
                        <TableCell>{adjustment.new_quantity_on_hand || "-"}</TableCell>
                        <TableCell className="text-right">
                          <TableActions
                            actions={[
                              {
                                label: "View",
                                icon: <EyeIcon className="h-4 w-4" />,
                                href: route("inventory_adjustments.show", adjustment.id),
                              },
                              {
                                label: "Edit",
                                icon: <Edit className="h-4 w-4" />,
                                href: route("inventory_adjustments.edit", adjustment.id),
                              },
                              {
                                label: "Delete",
                                icon: <Trash className="h-4 w-4" />,
                                onClick: () => handleDeleteConfirm(adjustment.id),
                                destructive: true,
                              },
                            ]}
                          />
                        </TableCell>
                      </TableRow>
                    ))
                  ) : (
                    <TableRow>
                      <TableCell colSpan={8} className="h-24 text-center">
                        No adjustments found.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>

            {adjustments.length > 0 && meta.total > 0 && (
              <div className="flex items-center justify-between mt-4">
                <div className="text-sm text-gray-500">
                  Showing {(currentPage - 1) * perPage + 1} to {Math.min(currentPage * perPage, meta.total)} of {meta.total} entries
                </div>
                <div className="flex items-center space-x-2">
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => handlePageChange(1)}
                    disabled={currentPage === 1}
                  >
                    First
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => handlePageChange(currentPage - 1)}
                    disabled={currentPage === 1}
                  >
                    Previous
                  </Button>
                  {renderPageNumbers()}
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => handlePageChange(currentPage + 1)}
                    disabled={currentPage === meta.last_page}
                  >
                    Next
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => handlePageChange(meta.last_page)}
                    disabled={currentPage === meta.last_page}
                  >
                    Last
                  </Button>
                </div>
              </div>
            )}
          </div>
        </div>
      </SidebarInset>

      <DeleteAdjustmentModal
        show={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        onConfirm={handleDelete}
        processing={processing}
      />

      <DeleteAllAdjustmentsModal
        show={showDeleteAllModal}
        onClose={() => setShowDeleteAllModal(false)}
        onConfirm={handleDeleteAll}
        processing={processing}
        count={selectedAdjustments.length}
      />

      <ImportAdjustmentsModal
        show={showImportModal}
        onClose={() => setShowImportModal(false)}
        onSubmit={handleImport}
        processing={processing}
      />
    </AuthenticatedLayout>
  );
}
