import React, { useEffect, useState } from "react";
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
import { MoreVertical, FileUp, FileDown, Plus, Eye, Trash2, Edit, ChevronUp, ChevronDown, Receipt, DollarSign, Users, Calendar } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";
import { formatCurrency } from "@/lib/utils";
import DateTimePicker from "@/Components/DateTimePicker";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";

const DeleteReceiptModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete this cash invoice?
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
          Delete Receipt
        </Button>
      </div>
    </form>
  </Modal>
);

const ImportReceiptsModal = ({ show, onClose, onSubmit, processing }) => (
  <Modal show={show} onClose={onClose} maxWidth="3xl">
    <form onSubmit={onSubmit}>
      <div className="ti-modal-header">
        <h3 className="text-lg font-bold">Import Cash Invoices</h3>
      </div>
      <div className="ti-modal-body grid grid-cols-12">
        <div className="col-span-12">
          <div className="flex items-center justify-between">
            <label className="block font-medium text-sm text-gray-700">
              Cash Invoices File
            </label>
            <a href="/uploads/media/default/sample_cash_invoices.xlsx" download>
              <Button variant="secondary" size="sm" type="button">
                Use This Sample File
              </Button>
            </a>
          </div>
          <input type="file" className="w-full dropify" name="receipts_file" required />
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
      <div className="ti-modal-footer flex justify-end mt-4">
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
          Import Cash Invoices
        </Button>
      </div>
    </form>
  </Modal>
);

const DeleteAllReceiptsModal = ({ show, onClose, onConfirm, processing, count }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete {count} selected cash invoice{count !== 1 ? 's' : ''}?
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

const SummaryCards = ({ receipts = [] }) => {
  const totalInvoices = receipts.length;
  const grandTotal = receipts.reduce((sum, receipt) => sum + parseFloat(receipt.grand_total), 0);
  const uniqueCustomers = new Set(receipts.map(receipt => receipt.customer?.id)).size;
  const todayInvoices = receipts.filter(receipt => {
    const receiptDate = new Date(receipt.receipt_date);
    const today = new Date();
    return receiptDate.toDateString() === today.toDateString();
  }).length;

  const cards = [
    {
      title: "Total Invoices",
      value: totalInvoices,
      description: "Total cash invoices",
      icon: Receipt,
      iconColor: "text-blue-500"
    },
    {
      title: "Grand Total",
      value: formatCurrency({ amount: grandTotal, currency: receipts[0]?.currency || 'USD' }),
      description: "Total amount of all invoices",
      icon: DollarSign,
      iconColor: "text-green-500"
    },
    {
      title: "Unique Customers",
      value: uniqueCustomers,
      description: "Number of unique customers",
      icon: Users,
      iconColor: "text-purple-500"
    },
    {
      title: "Today's Invoices",
      value: todayInvoices,
      description: "Invoices created today",
      icon: Calendar,
      iconColor: "text-orange-500"
    }
  ];

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      {cards.map((card, index) => (
        <div key={index} className="bg-white rounded-lg shadow-sm p-4">
          <div className="flex flex-row items-center justify-between space-y-0 pb-2">
            <h3 className="text-sm font-medium">
              {card.title}
            </h3>
            <card.icon className={`h-4 w-4 ${card.iconColor}`} />
          </div>
          <div className="text-2xl font-bold">{card.value}</div>
          <p className="text-xs text-muted-foreground">
            {card.description}
          </p>
        </div>
      ))}
    </div>
  );
};

export default function List({ receipts = [], meta = {}, filters = {}, customers = [] }) {
  const { flash = {} } = usePage().props;
  const { toast } = useToast();
  const [selectedRows, setSelectedRows] = useState([]);
  const [selectAll, setSelectAll] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showImportModal, setShowImportModal] = useState(false);
  const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
  const [currentReceiptId, setCurrentReceiptId] = useState(null);
  const [processing, setProcessing] = useState(false);
  const [perPage, setPerPage] = useState(filters.per_page || 50);
  const [search, setSearch] = useState(filters.search || "");
  const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
  const [sorting, setSorting] = useState(filters.sorting || { column: "id", direction: "desc" });
  const [selectedCustomer, setSelectedCustomer] = useState(filters.customer_id || "");
  const [dateRange, setDateRange] = useState(filters.date_range || null);

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

  const handleSort = (column) => {
    let direction = "asc";
    if (sorting.column === column && sorting.direction === "asc") {
      direction = "desc";
    }
    setSorting({ column, direction });
    router.get(
      route("receipts.index"),
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
      route("receipts.index"),
      { search: value, page: 1, per_page: perPage, sorting },
      { preserveState: true }
    );
  };

  const handlePageChange = (page) => {
    setCurrentPage(page);
    router.get(
      route("receipts.index"),
      { search, page, per_page: perPage, sorting },
      { preserveState: true }
    );
  };

  const handleRowSelect = (id) => {
    if (selectedRows.includes(id)) {
      setSelectedRows(selectedRows.filter((rowId) => rowId !== id));
    } else {
      setSelectedRows([...selectedRows, id]);
    }
  };

  const handleSelectAll = () => {
    if (selectAll) {
      setSelectedRows([]);
    } else {
      setSelectedRows(receipts.map((receipt) => receipt.id));
    }
    setSelectAll(!selectAll);
  };

  const openDeleteModal = (id) => {
    setCurrentReceiptId(id);
    setShowDeleteModal(true);
  };

  const closeDeleteModal = () => {
    setShowDeleteModal(false);
    setCurrentReceiptId(null);
  };

  const confirmDelete = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.delete(route("receipts.destroy", currentReceiptId), {
      onSuccess: () => {
        toast({
          title: "Receipt Deleted",
          description: "Receipt has been deleted successfully.",
        });
        closeDeleteModal();
        setProcessing(false);
      },
      onError: (errors) => {
        toast({
          title: "Error",
          description: "Failed to delete receipt.",
          variant: "destructive",
        });
        setProcessing(false);
      },
    });
  };

  const closeImportModal = () => {
    setShowImportModal(false);
  };

  const handleImport = (e) => {
    e.preventDefault();
    setProcessing(true);

    const formData = new FormData(e.target);

    router.post(route("receipts.import"), formData, {
      onSuccess: () => {
        closeImportModal();
        setProcessing(false);
      },
      onError: (errors) => {
        setProcessing(false);
      },
    });
  };

  const openDeleteAllModal = () => {
    setShowDeleteAllModal(true);
  };

  const closeDeleteAllModal = () => {
    setShowDeleteAllModal(false);
  };

  const confirmDeleteAll = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.post(
      route("receipts.bulk_destroy"),
      {
        ids: selectedRows
      },
      {
        onSuccess: () => {
          closeDeleteAllModal();
          setProcessing(false);
          setSelectedRows([]);
          setSelectAll(false);
        },
        onError: (errors) => {
          setProcessing(false);
        },
      }
    );
  };

  const exportReceipts = () => {
    window.location.href = route("receipts.export");
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
      route("receipts.index"),
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
      route("receipts.index"),
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
            subpage="List"
            url="receipts.index"
          />
          <div className="p-4">
            <SummaryCards receipts={receipts} />
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
              <div className="flex flex-col md:flex-row gap-2">
                <Link href={route("receipts.create")}>
                  <Button>
                    <Plus className="w-4 h-4 mr-2" />
                    Add Cash Invoice
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
                    <DropdownMenuItem onClick={exportReceipts}>
                      <FileDown className="mr-2 h-4 w-4" /> Export
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
              <div className="flex flex-col md:flex-row gap-4 md:items-center">
                <Input
                  placeholder="search receipts..."
                  value={search}
                  onChange={(e) => handleSearch(e)}
                  className="w-full md:w-80"
                />
              </div>
            </div>

            <div className="mb-4 flex flex-col md:flex-row gap-4 justify-between">
              <div className="flex items-center gap-2">
                <Select
                  value={selectedRows.length > 0 ? "delete" : ""}
                  onValueChange={(value) => {
                    if (value === "delete" && selectedRows.length > 0) {
                      openDeleteAllModal();
                    }
                  }}
                >
                  <SelectTrigger className="w-[180px]">
                    <SelectValue placeholder="Bulk actions" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="delete">Delete Selected</SelectItem>
                  </SelectContent>
                </Select>
                <Button
                  onClick={() => selectedRows.length > 0 && openDeleteAllModal()}
                  variant="outline"
                  disabled={selectedRows.length === 0}
                >
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
                <Select value={perPage.toString()} onValueChange={(value) => {
                  setPerPage(parseInt(value));
                  router.get(
                    route("receipts.index"),
                    { search, page: 1, per_page: value, customer_id: selectedCustomer, date_range: dateRange },
                    { preserveState: true }
                  );
                }}>
                  <SelectTrigger className="w-[80px]">
                    <SelectValue placeholder={perPage.toString()} />
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

            {/* Table */}
            <div className="border rounded-md">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className="w-[50px]">
                      <Checkbox
                        checked={selectAll}
                        onCheckedChange={handleSelectAll}
                        aria-label="Select all"
                      />
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("receipt_number")}>Invoice # {renderSortIcon("receipt_number")}</TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("customer.name")}>Customer {renderSortIcon("customer.name")}</TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("receipt_date")}>Date {renderSortIcon("receipt_date")}</TableHead>
                    <TableHead className="text-right cursor-pointer" onClick={() => handleSort("grand_total")}>Grand Total {renderSortIcon("grand_total")}</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {receipts.length > 0 ? (
                    receipts.map((receipt) => (
                      <TableRow key={receipt.id}>
                        <TableCell>
                          <Checkbox
                            checked={selectedRows.includes(receipt.id)}
                            onCheckedChange={() => handleRowSelect(receipt.id)}
                            aria-label={`Select receipt ${receipt.receipt_number}`}
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
                          {formatCurrency(receipt.grand_total)}
                        </TableCell>
                        <TableCell className="text-right">
                          <TableActions
                            actions={[
                              {
                                label: "View",
                                icon: <Eye className="h-4 w-4" />,
                                href: route("receipts.show", receipt.id),
                              },
                              {
                                label: "View POS",
                                icon: <Eye className="h-4 w-4" />,
                                href: route("receipts.invoice_pos", receipt.id),
                              },
                              {
                                label: "Edit",
                                icon: <Edit className="h-4 w-4" />,
                                href: route("receipts.edit", receipt.id),
                              },
                              {
                                label: "Delete",
                                icon: <Trash2 className="h-4 w-4" />,
                                onClick: () => openDeleteModal(receipt.id),
                                destructive: true,
                              },
                            ]}
                          />
                        </TableCell>
                      </TableRow>
                    ))
                  ) : (
                    <TableRow>
                      <TableCell colSpan="8" className="text-center py-4">
                        No receipts found
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>

            {/* Pagination */}
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
        onClose={closeDeleteModal}
        onConfirm={confirmDelete}
        processing={processing}
      />

      <ImportReceiptsModal
        show={showImportModal}
        onClose={closeImportModal}
        onSubmit={handleImport}
        processing={processing}
      />

      <DeleteAllReceiptsModal
        show={showDeleteAllModal}
        onClose={closeDeleteAllModal}
        onConfirm={confirmDeleteAll}
        processing={processing}
        count={selectedRows.length}
      />
    </AuthenticatedLayout>
  );
}
