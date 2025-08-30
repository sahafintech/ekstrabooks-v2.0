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
import { ChevronUp, ChevronDown, RotateCcw, Trash } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";
import { formatCurrency } from "@/lib/utils";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import DateTimePicker from "@/Components/DateTimePicker";

const DeleteInvoiceModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
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

const DeleteAllInvoicesModal = ({ show, onClose, onConfirm, processing, count }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete {count} selected invoice{count !== 1 ? 's' : ''}?
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

const RestoreInvoiceModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to restore this invoice?
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
          Restore Invoice
        </Button>
      </div>
    </form>
  </Modal>
);

const RestoreAllInvoicesModal = ({ show, onClose, onConfirm, processing, count }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to restore {count} selected invoice{count !== 1 ? 's' : ''}?
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

const InvoiceStatusBadge = ({ status }) => {
  const statusMap = {
    0: { label: "Draft", className: "text-gray-600 bg-gray-200 px-3 py-1 rounded text-sm" },
    1: { label: "Active", className: "text-blue-600 bg-blue-200 px-3 py-1 rounded text-sm" },
    2: { label: "Paid", className: "text-green-600 bg-green-200 px-3 py-1 rounded text-sm" },
    3: { label: "Partially Paid", className: "text-yellow-600 bg-yellow-200 px-3 py-1 rounded text-sm" },
    4: { label: "Canceled", className: "text-red-600 bg-red-200 px-3 py-1 rounded text-sm" }
  };

  return (
    <span className={statusMap[status].className}>
      {statusMap[status].label}
    </span>
  );
};

export default function TrashList({ invoices = [], meta = {}, filters = {}, customers = [] }) {
  const { flash = {} } = usePage().props;
  const { toast } = useToast();
  const [selectedInvoices, setSelectedInvoices] = useState([]);
  const [isAllSelected, setIsAllSelected] = useState(false);
  const [search, setSearch] = useState(filters.search || "");
  const [perPage, setPerPage] = useState(meta.per_page || 50);
  const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
  const [bulkAction, setBulkAction] = useState("");
  const [sorting, setSorting] = useState(filters.sorting || { column: "id", direction: "desc" });
  const [selectedCustomer, setSelectedCustomer] = useState(filters.customer_id || "");
  const [dateRange, setDateRange] = useState(filters.date_range || null);
  const [selectedStatus, setSelectedStatus] = useState(filters.status || "");

  // Delete confirmation modal states
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
  const [invoiceToDelete, setInvoiceToDelete] = useState(null);
  const [processing, setProcessing] = useState(false);

  // Restore confirmation modal states
  const [showRestoreModal, setShowRestoreModal] = useState(false);
  const [showRestoreAllModal, setShowRestoreAllModal] = useState(false);
  const [invoiceToRestore, setInvoiceToRestore] = useState(null);

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
      setSelectedInvoices([]);
    } else {
      setSelectedInvoices(invoices.map((invoice) => invoice.id));
    }
    setIsAllSelected(!isAllSelected);
  };

  const toggleSelectInvoice = (id) => {
    if (selectedInvoices.includes(id)) {
      setSelectedInvoices(selectedInvoices.filter((invoiceId) => invoiceId !== id));
      setIsAllSelected(false);
    } else {
      setSelectedInvoices([...selectedInvoices, id]);
      if (selectedInvoices.length + 1 === invoices.length) {
        setIsAllSelected(true);
      }
    }
  };

  const handleSort = (column) => {
    let direction = "asc";
    if (sorting.column === column && sorting.direction === "asc") {
      direction = "desc";
    }
    setSorting({ column, direction });
    router.get(
      route("deffered_invoices.trash"),
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

  const handleSearch = (e) => {
    e.preventDefault();
    const value = e.target.value;
    setSearch(value);
    router.get(
      route("deffered_invoices.trash"),
      { search: value, page: 1, per_page: perPage, sorting },
      { preserveState: true }
    );
  };

  const handlePerPageChange = (value) => {
    setPerPage(value);
    router.get(
      route("deffered_invoices.trash"),
      { search, page: 1, per_page: value, sorting },
      { preserveState: true }
    );
  };

  const handlePageChange = (page) => {
    setCurrentPage(page);
    router.get(
      route("deffered_invoices.trash"),
      { search, page, per_page: perPage, sorting },
      { preserveState: true }
    );
  };

  const handleBulkAction = () => {
    if (bulkAction === "") return;

    if (selectedInvoices.length === 0) {
      toast({
        variant: "destructive",
        title: "Error",
        description: "Please select at least one invoice",
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
    setInvoiceToDelete(id);
    setShowDeleteModal(true);
  };

  const handleRestoreConfirm = (id) => {
    setInvoiceToRestore(id);
    setShowRestoreModal(true);
  };

  const handleDelete = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.delete(route('deffered_invoices.permanent_destroy', invoiceToDelete), {
      onSuccess: () => {
        setShowDeleteModal(false);
        setInvoiceToDelete(null);
        setSelectedInvoices([]);
        setIsAllSelected(false);
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

    router.post(route('deffered_invoices.bulk_permanent_destroy'),
      {
        ids: selectedInvoices
      },
      {
        onSuccess: () => {
          setShowDeleteAllModal(false);
          setSelectedInvoices([]);
          setIsAllSelected(false);
          setProcessing(false);
          setBulkAction("");
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

    router.delete(route('deffered_invoices.permanent_destroy', invoiceToRestore), {
      onSuccess: () => {
        setShowRestoreModal(false);
        setInvoiceToRestore(null);
        setSelectedInvoices([]);
        setIsAllSelected(false);
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

    router.post(route('deffered_invoices.bulk_restore'),
      {
        ids: selectedInvoices
      },
      {
        onSuccess: () => {
          setShowRestoreAllModal(false);
          setSelectedInvoices([]);
          setIsAllSelected(false);
          setProcessing(false);
          setBulkAction("");
        },
        onError: () => {
          setProcessing(false);
        }
      }
    );
  };

  const handleCustomerChange = (value) => {
    setSelectedCustomer(value);
    router.get(
        route("deffered_invoices.trash"),
        {
            search,
            page: 1,
            per_page: perPage,
            customer_id: value,
            date_range: dateRange,
            status: selectedStatus,
            sorting,
        },
        { preserveState: true }
    );
  };

  const handleDateRangeChange = (dates) => {
    setDateRange(dates);
    router.get(
        route("deffered_invoices.trash"),
        {
            search,
            page: 1,
            per_page: perPage,
            customer_id: selectedCustomer,
            date_range: dates,
            status: selectedStatus,
            sorting,
        },
        { preserveState: true }
    );
  };

  const handleStatusChange = (value) => {
    setSelectedStatus(value);
    router.get(
        route("deffered_invoices.trash"),
        {
            search,
            page: 1,
            per_page: perPage,
            customer_id: selectedCustomer,
            date_range: dateRange,
            status: value,
            sorting,
        },
        { preserveState: true }
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

  const exportInvoices = () => {
    router.get(route("deffered_invoices.export"));
  };

  return (
    <AuthenticatedLayout>
      <Toaster />
      <SidebarInset>
        <div className="main-content">
          <PageHeader
            page="Deffered Invoices"
            subpage="Trash"
            url="deffered_invoices.index"
          />
          <div className="p-4">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <div className="text-red-500">
                        Total trashed invoices: {meta.total}
                    </div>
                </div>
              <div className="flex flex-col md:flex-row gap-4 md:items-center">
                <Input
                  placeholder="Search trashed invoices..."
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
                <SearchableCombobox
                  options={[
                      { id: "", name: "All Status" },
                      { id: "0", name: "Draft" },
                      { id: "1", name: "Active" },
                      { id: "2", name: "Paid" },
                      { id: "3", name: "Partially Paid" },
                      { id: "4", name: "Canceled" }
                  ]}
                  value={selectedStatus}
                  onChange={handleStatusChange}
                  placeholder="Select status"
                  className="w-[150px]"
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
                    <TableHead className="cursor-pointer" onClick={() => handleSort("order_number")}>
                      Policy Number {renderSortIcon("order_number")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("deffered_start")}>
                      Policy Start {renderSortIcon("deffered_start")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("deffered_end")}>
                      Policy End {renderSortIcon("deffered_end")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("customer.name")}>
                      Customer {renderSortIcon("customer.name")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("invoice_date")}>
                      Invoice Date {renderSortIcon("invoice_date")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("due_date")}>
                      Due Date {renderSortIcon("due_date")}
                    </TableHead>
                    <TableHead className="cursor-pointer text-right" onClick={() => handleSort("grand_total")}>
                      Grand Total {renderSortIcon("grand_total")}
                    </TableHead>
                    <TableHead className="cursor-pointer text-right" onClick={() => handleSort("paid")}>
                      Paid {renderSortIcon("paid")}
                    </TableHead>
                    <TableHead className="cursor-pointer text-right" onClick={() => handleSort("grand_total")}>
                      Due {renderSortIcon("grand_total")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("status")}>
                      Status {renderSortIcon("status")}
                    </TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {invoices.length > 0 ? (
                    invoices.map((invoice) => (
                      <TableRow key={invoice.id}>
                        <TableCell>
                          <Checkbox
                            checked={selectedInvoices.includes(invoice.id)}
                            onCheckedChange={() => toggleSelectInvoice(invoice.id)}
                          />
                        </TableCell>
                        <TableCell>{invoice.order_number}</TableCell>
                        <TableCell>{invoice.deffered_start}</TableCell>
                        <TableCell>{invoice.deffered_end}</TableCell>
                        <TableCell>{invoice.customer ? invoice.customer.name : "-"}</TableCell>
                        <TableCell>{invoice.invoice_date}</TableCell>
                        <TableCell>{invoice.due_date}</TableCell>
                        <TableCell className="text-right">{formatCurrency(invoice.grand_total)}</TableCell>
                        <TableCell className="text-right">{formatCurrency(invoice.paid)}</TableCell>
                        <TableCell className="text-right">{formatCurrency(invoice.grand_total - invoice.paid)}</TableCell>
                        <TableCell>
                          <InvoiceStatusBadge status={invoice.status} />
                        </TableCell>
                        <TableCell className="text-right">
                        <TableActions
                            actions={[
                            {
                                label: "Restore",
                                icon: <RotateCcw className="h-4 w-4" />,
                                onClick: () => handleRestoreConfirm(invoice.id)
                            },
                            {
                                label: "Permanently Delete",
                                icon: <Trash className="h-4 w-4" />,
                                onClick: () => handleDeleteConfirm(invoice.id),
                                destructive: true,
                            },
                            ]}
                        />
                        </TableCell>
                      </TableRow>
                    ))
                  ) : (
                    <TableRow>
                      <TableCell colSpan={12} className="h-24 text-center">
                        No invoices found.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>

            {invoices.length > 0 && meta.total > 0 && (
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

      <DeleteInvoiceModal
        show={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        onConfirm={handleDelete}
        processing={processing}
      />

      <DeleteAllInvoicesModal
        show={showDeleteAllModal}
        onClose={() => setShowDeleteAllModal(false)}
        onConfirm={handleDeleteAll}
        processing={processing}
        count={selectedInvoices.length}
      />

      <RestoreInvoiceModal
        show={showRestoreModal}
        onClose={() => setShowRestoreModal(false)}
        onConfirm={handleRestore}
        processing={processing}
      />

      <RestoreAllInvoicesModal
        show={showRestoreAllModal}
        onClose={() => setShowRestoreAllModal(false)}
        onConfirm={handleRestoreAll}
        processing={processing}
        count={selectedInvoices.length}
      />
    </AuthenticatedLayout>
  );
}
