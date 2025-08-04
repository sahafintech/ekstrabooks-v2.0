import React, { useState, useEffect } from "react";
import { router, usePage } from "@inertiajs/react";
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
import { Input } from "@/Components/ui/input";
import { Trash, ChevronUp, ChevronDown, RotateCcw } from "lucide-react";
import { toast, Toaster } from 'sonner'
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";

// Delete Confirmation Modal Component
const DeleteVendorModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete this vendor?
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
const DeleteAllVendorsModal = ({ show, onClose, onConfirm, processing, count }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete {count} selected vendor{count !== 1 ? 's' : ''}?
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

// Restore Confirmation Modal Component
const RestoreVendorModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to restore this vendor?
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
          variant="default"
          disabled={processing}
        >
          Restore
        </Button>
      </div>
    </form>
  </Modal>
);

// Bulk Restore Confirmation Modal Component
const RestoreAllVendorsModal = ({ show, onClose, onConfirm, processing, count }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to restore {count} selected vendor{count !== 1 ? 's' : ''}?
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
          variant="default"
          disabled={processing}
        >
          Restore Selected
        </Button>
      </div>
    </form>
  </Modal>
);

export default function List({ vendors = [], meta = {}, filters = {} }) {
  const { flash = {} } = usePage().props;
  const [selectedVendors, setSelectedVendors] = useState([]);
  const [isAllSelected, setIsAllSelected] = useState(false);
  const [search, setSearch] = useState(filters.search || "");
  const [perPage, setPerPage] = useState(meta.per_page || 50);
  const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
  const [bulkAction, setBulkAction] = useState("");
  const [sorting, setSorting] = useState(filters.sorting || { column: "id", direction: "desc" });

  // Delete confirmation modal states
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
  const [showRestoreModal, setShowRestoreModal] = useState(false);
  const [showRestoreAllModal, setShowRestoreAllModal] = useState(false);
  const [vendorToRestore, setVendorToRestore] = useState(null);
  const [vendorToDelete, setVendorToDelete] = useState(null);
  const [processing, setProcessing] = useState(false);

  useEffect(() => {
    if (flash && flash.success) {
      toast("Success Message", {
        description: flash.success,
        action: {
          label: "Close",
          onClick: () => {
            toast.dismiss();
          }
        }
      });
    }

    if (flash && flash.error) {
      toast("Error Message", {
        description: flash.error,
        action: {
          label: "Close",
          onClick: () => {
            toast.dismiss();
          }
        }
      });
    }
  }, [flash, toast]);

  const toggleSelectAll = () => {
    if (isAllSelected) {
      setSelectedVendors([]);
    } else {
      setSelectedVendors(vendors.map((vendor) => vendor.id));
    }
    setIsAllSelected(!isAllSelected);
  };

  const toggleSelectVendor = (id) => {
    if (selectedVendors.includes(id)) {
      setSelectedVendors(selectedVendors.filter((vendorId) => vendorId !== id));
      setIsAllSelected(false);
    } else {
      setSelectedVendors([...selectedVendors, id]);
      if (selectedVendors.length + 1 === vendors.length) {
        setIsAllSelected(true);
      }
    }
  };

  const handleSearch = (e) => {
    e.preventDefault();
    const value = e.target.value;
    setSearch(value);

    router.get(
      route("vendors.trash"),
      { search: value, page: 1, per_page: perPage },
      { preserveState: true }
    );
  };

  const handlePerPageChange = (value) => {
    setPerPage(value);
    router.get(
      route("vendors.trash"),
      { search, page: 1, per_page: value },
      { preserveState: true }
    );
  };

  const handlePageChange = (page) => {
    setCurrentPage(page);
    router.get(
      route("vendors.trash"),
      { search, page, per_page: perPage },
      { preserveState: true }
    );
  };

  const handleBulkAction = () => {
    if (bulkAction === "") return;

    if (selectedVendors.length === 0) {
      toast({
        variant: "destructive",
        title: "Error",
        description: "Please select at least one vendor",
      });
      return;
    }

    if (bulkAction === "delete") {
      setShowDeleteAllModal(true);
    }else if (bulkAction === "restore") {
      setShowRestoreAllModal(true);
    }
  };

  const handleDeleteConfirm = (id) => {
    setVendorToDelete(id);
    setShowDeleteModal(true);
  };

  const handleRestoreConfirm = (id) => {
    setVendorToRestore(id);
    setShowRestoreModal(true);
  };

  const handleDelete = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.delete(route('vendors.permanent_destroy', vendorToDelete), {
      onSuccess: () => {
        setShowDeleteModal(false);
        setVendorToDelete(null);
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

    router.post(route('vendors.bulk_permanent_destroy'),
      {
        ids: selectedVendors
      },
      {
        onSuccess: () => {
          setShowDeleteAllModal(false);
          setSelectedVendors([]);
          setIsAllSelected(false);
          setProcessing(false);
        },
        onError: () => {
          setProcessing(false);
        }
      }
    );
  };

  const handleRestore = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.post(route('vendors.restore', vendorToRestore), {
      onSuccess: () => {
        setShowRestoreModal(false);
        setVendorToRestore(null);
        setProcessing(false);
      },
      onError: () => {
        setProcessing(false);
      }
    });
  };

  const handleRestoreAll = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.post(route('vendors.bulk_restore'),
      {
        ids: selectedVendors
      },
      {
        onSuccess: () => {
          setShowRestoreAllModal(false);
          setSelectedVendors([]);
          setIsAllSelected(false);
          setProcessing(false);
        },
        onError: () => {
          setProcessing(false);
        }
      }
    );
  };

  const handleSort = (column) => {
    let direction = "asc";
    if (sorting.column === column && sorting.direction === "asc") {
      direction = "desc";
    }
    setSorting({ column, direction });
    router.get(
      route("vendors.trash"),
      { ...filters, sorting: { column, direction } },
      { preserveState: true }
    );
  };

  const renderSortIcon = (column) => {
    const isActive = sorting.column === column;
    return (
      <span className="inline-flex flex-col ml-1">
        <ChevronUp
          className={`w-3 h-3 ${isActive && sorting.direction === "asc" ? "text-gray-800" : "text-gray-300"}`}
        />
        <ChevronDown
          className={`w-3 h-3 -mt-1 ${isActive && sorting.direction === "desc" ? "text-gray-800" : "text-gray-300"}`}
        />
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

  return (
    <AuthenticatedLayout>
      <Toaster position="top-center" />
      <SidebarInset>
        <div className="main-content">
          <PageHeader
            page="Vendors"
            subpage="Trash"
            url="vendors.index"
          />
          <div className="p-4">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <div className="text-red-500">
                        Total trashed vendors: {meta.total}
                    </div>
                </div>
              <div className="flex flex-col md:flex-row gap-4 md:items-center">
                <Input
                  placeholder="Search trashed vendors..."
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
                    <SelectItem value="delete">Permanently Delete Selected</SelectItem>
                    <SelectItem value="restore">Restore Selected</SelectItem>
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
                    <TableHead className="w-[80px] cursor-pointer" onClick={() => handleSort("id")}>ID {renderSortIcon("id")}</TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("name")}>Name {renderSortIcon("name")}</TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("company_name")}>Company {renderSortIcon("company_name")}</TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("email")}>Email {renderSortIcon("email")}</TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("mobile")}>Phone {renderSortIcon("mobile")}</TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("address")}>Address {renderSortIcon("address")}</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {vendors.length > 0 ? (
                    vendors.map((vendor) => (
                      <TableRow key={vendor.id}>
                        <TableCell>
                          <Checkbox
                            checked={selectedVendors.includes(vendor.id)}
                            onCheckedChange={() => toggleSelectVendor(vendor.id)}
                          />
                        </TableCell>
                        <TableCell>{vendor.id}</TableCell>
                        <TableCell>{vendor.name}</TableCell>
                        <TableCell>{vendor.company_name || "-"}</TableCell>
                        <TableCell>{vendor.email || "-"}</TableCell>
                        <TableCell>{vendor.mobile || "-"}</TableCell>
                        <TableCell>{vendor.address || "-"}</TableCell>
                        <TableCell className="text-right">
                        <TableActions
                            actions={[
                              {
                                label: "Restore",
                                icon: <RotateCcw className="h-4 w-4" />,
                                onClick: () => handleRestoreConfirm(vendor.id)
                              },
                              {
                                label: "Permanently Delete",
                                icon: <Trash className="h-4 w-4" />,
                                onClick: () => handleDeleteConfirm(vendor.id),
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
                        No vendors found.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>

            {vendors.length > 0 && meta.total > 0 && (
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

      <DeleteVendorModal
        show={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        onConfirm={handleDelete}
        processing={processing}
      />

      <DeleteAllVendorsModal
        show={showDeleteAllModal}
        onClose={() => setShowDeleteAllModal(false)}
        onConfirm={handleDeleteAll}
        processing={processing}
        count={selectedVendors.length}
      />
      <RestoreVendorModal
        show={showRestoreModal}
        onClose={() => setShowRestoreModal(false)}
        onConfirm={handleRestore}
        processing={processing}
      />

      <RestoreAllVendorsModal
        show={showRestoreAllModal}
        onClose={() => setShowRestoreAllModal(false)}
        onConfirm={handleRestoreAll}
        processing={processing}
        count={selectedVendors.length}
      />
    </AuthenticatedLayout>
  );
}
