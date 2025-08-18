import React, { useEffect, useState } from "react";
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
import { ChevronUp, ChevronDown, Trash, RotateCcw } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";
import { formatCurrency } from "@/lib/utils";
import DateTimePicker from "@/Components/DateTimePicker";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";

// Delete Confirmation Modal Component
const DeleteReceiptModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to permanently delete this cash invoice?
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
const DeleteAllReceiptsModal = ({ show, onClose, onConfirm, processing, count }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to permanently delete {count} selected cash invoice{count !== 1 ? 's' : ''}?
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
          Permanently Delete Selected
        </Button>
      </div>
    </form>
  </Modal>
);

// Restore Confirmation Modal Component
const RestoreReceiptModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to restore this cash invoice?
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
const RestoreAllReceiptsModal = ({ show, onClose, onConfirm, processing, count }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to restore {count} selected cash invoice{count !== 1 ? 's' : ''}?
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

export default function TrashList({ receipts = [], meta = {}, filters = {}, customers = [], business }) {
  const { flash = {} } = usePage().props;
  const { toast } = useToast();
  const [selectedReceipts, setSelectedReceipts] = useState([]);
  const [isAllSelected, setIsAllSelected] = useState(false);
  const [search, setSearch] = useState(filters.search || "");
  const [perPage, setPerPage] = useState(meta.per_page || 50);
  const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
  const [bulkAction, setBulkAction] = useState("");
  const [sorting, setSorting] = useState(filters.sorting || { column: "id", direction: "desc" });
  const [selectedCustomer, setSelectedCustomer] = useState(filters.customer_id || "");
  const [dateRange, setDateRange] = useState(filters.date_range || null);

  // Delete confirmation modal states
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
  const [receiptToDelete, setReceiptToDelete] = useState(null);
  const [processing, setProcessing] = useState(false);

  // Restore confirmation modal states
  const [showRestoreModal, setShowRestoreModal] = useState(false);
  const [showRestoreAllModal, setShowRestoreAllModal] = useState(false);
  const [receiptToRestore, setReceiptToRestore] = useState(null);

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
      setSelectedReceipts([]);
    } else {
      setSelectedReceipts(receipts.map((receipt) => receipt.id));
    }
    setIsAllSelected(!isAllSelected);
  };

  const toggleSelectReceipt = (id) => {
    if (selectedReceipts.includes(id)) {
      setSelectedReceipts(selectedReceipts.filter((receiptId) => receiptId !== id));
      setIsAllSelected(false);
    } else {
      setSelectedReceipts([...selectedReceipts, id]);
      if (selectedReceipts.length + 1 === receipts.length) {
        setIsAllSelected(true);
      }
    }
  };

  const handleSearch = (e) => {
    e.preventDefault();
    const value = e.target.value;
    setSearch(value);

    router.get(
      route("receipts.trash"),
      { search: value, page: 1, per_page: perPage, customer_id: selectedCustomer, date_range: dateRange },
      { preserveState: true }
    );
  };

  const handlePerPageChange = (value) => {
    setPerPage(value);
    router.get(
      route("receipts.trash"),
      { search, page: 1, per_page: value, customer_id: selectedCustomer, date_range: dateRange },
      { preserveState: true }
    );
  };

  const handlePageChange = (page) => {
    setCurrentPage(page);
    router.get(
      route("receipts.trash"),
      { 
        search, 
        page, 
        per_page: perPage, 
        customer_id: selectedCustomer,
        date_range: dateRange
      },
      { preserveState: true }
    );
  };

  const handleBulkAction = () => {
    if (bulkAction === "") return;

    if (selectedReceipts.length === 0) {
      toast({
        variant: "destructive",
        title: "Error",
        description: "Please select at least one cash invoice",
      });
      return;
    }

    if (bulkAction === "delete") {
      setShowDeleteAllModal(true);
    } else if (bulkAction === "restore") {
      setShowRestoreAllModal(true);
    }
  };

  const handleDeleteConfirm = (id) => {
    setReceiptToDelete(id);
    setShowDeleteModal(true);
  };

  const handleRestoreConfirm = (id) => {
    setReceiptToRestore(id);
    setShowRestoreModal(true);
  };

  const handleDelete = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.delete(route('receipts.permanent_destroy', receiptToDelete), {
      onSuccess: () => {
        setShowDeleteModal(false);
        setReceiptToDelete(null);
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

    router.post(route('receipts.bulk_permanent_destroy'),
      {
        ids: selectedReceipts
      },
      {
        onSuccess: () => {
          setShowDeleteAllModal(false);
          setSelectedReceipts([]);
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

    router.post(route('receipts.restore', receiptToRestore), {
      onSuccess: () => {
        setShowRestoreModal(false);
        setReceiptToRestore(null);
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

    router.post(route('receipts.bulk_restore'),
      {
        ids: selectedReceipts
      },
      {
        onSuccess: () => {
          setShowRestoreAllModal(false);
          setSelectedReceipts([]);
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
      route("receipts.trash"),
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

  const handleCustomerChange = (value) => {
    setSelectedCustomer(value);
    router.get(
      route("receipts.trash"),
      {
        search,
        page: 1,
        per_page: perPage,
        customer_id: value,
        date_range: dateRange,
      },
      { preserveState: true }
    );
  };

  const handleDateRangeChange = (dates) => {
    setDateRange(dates);
    router.get(
      route("receipts.trash"),
      {
        search,
        page: 1,
        per_page: perPage,
        customer_id: selectedCustomer,
        date_range: dates,
      },
      { preserveState: true }
    );
  };

  return (
    <AuthenticatedLayout>
      <Toaster />
      <SidebarInset>
        <div className="main-content">
          <PageHeader
            page="Cash Invoices"
            subpage="Trash"
            url="receipts.index"
          />
          <div className="p-4">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <div className="text-red-500">
                        Total trashed cash invoices: {meta.total}
                    </div>
                </div>
              <div className="flex flex-col md:flex-row gap-4 md:items-center">
                <Input
                  placeholder="search trashed cash invoices..."
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
                <SearchableCombobox
                  options={customers.map(customer => ({
                    id: customer.id,
                    name: customer.name
                  }))}
                  value={selectedCustomer}
                  onChange={handleCustomerChange}
                  placeholder="Select customer"
                  className="w-[200px]"
                />
                <DateTimePicker
                  value={dateRange}
                  onChange={handleDateRangeChange}
                  isRange={true}
                  className="w-[200px]"
                  placeholder="Select date range"
                />
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
                    <TableHead className="cursor-pointer" onClick={() => handleSort("receipt_number")}>
                      Invoice # {renderSortIcon("receipt_number")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("customer.name")}>
                      Customer {renderSortIcon("customer.name")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("receipt_date")}>
                      Date {renderSortIcon("receipt_date")}
                    </TableHead>
                    <TableHead className="text-right cursor-pointer" onClick={() => handleSort("grand_total")}>
                      Grand Total {renderSortIcon("grand_total")}
                    </TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {receipts.length > 0 ? (
                    receipts.map((receipt) => (
                      <TableRow key={receipt.id}>
                        <TableCell>
                          <Checkbox
                            checked={selectedReceipts.includes(receipt.id)}
                            onCheckedChange={() => toggleSelectReceipt(receipt.id)}
                          />
                        </TableCell>
                        <TableCell>{receipt.receipt_number}</TableCell>
                        <TableCell>
                          {receipt.customer ? receipt.customer.name : "-"}
                        </TableCell>
                        <TableCell>
                          {receipt.receipt_date}
                        </TableCell>
                        <TableCell className="text-right">
                          {receipt.grand_total !== receipt.converted_total ? (
                            <span>
                              {formatCurrency({ amount: receipt.grand_total, currency: business.currency })} ({formatCurrency({ amount: receipt.converted_total, currency: receipt.currency })})
                            </span>
                          ) : (
                            <span>
                              {formatCurrency({ amount: receipt.grand_total, currency: business.currency })}
                            </span>
                          )}
                        </TableCell>
                        <TableCell className="text-right">
                        <TableActions
                            actions={[
                              {
                                label: "Restore",
                                icon: <RotateCcw className="h-4 w-4" />,
                                onClick: () => handleRestoreConfirm(receipt.id)
                              },
                              {
                                label: "Permanently Delete",
                                icon: <Trash className="h-4 w-4" />,
                                onClick: () => handleDeleteConfirm(receipt.id),
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
                        No cash invoices found.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>

            {receipts.length > 0 && meta.total > 0 && (
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

      <DeleteReceiptModal
        show={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        onConfirm={handleDelete}
        processing={processing}
      />

      <DeleteAllReceiptsModal
        show={showDeleteAllModal}
        onClose={() => setShowDeleteAllModal(false)}
        onConfirm={handleDeleteAll}
        processing={processing}
        count={selectedReceipts.length}
      />

      <RestoreReceiptModal
        show={showRestoreModal}
        onClose={() => setShowRestoreModal(false)}
        onConfirm={handleRestore}
        processing={processing}
      />

      <RestoreAllReceiptsModal
        show={showRestoreAllModal}
        onClose={() => setShowRestoreAllModal(false)}
        onConfirm={handleRestoreAll}
        processing={processing}
        count={selectedReceipts.length}
      />
    </AuthenticatedLayout>
  );
}
