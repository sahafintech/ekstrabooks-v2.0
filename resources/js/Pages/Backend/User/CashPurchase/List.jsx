import React, { useState, useEffect, useMemo } from "react";
import { Head, Link, router, useForm, usePage } from "@inertiajs/react";
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
import { MoreVertical, FileUp, FileDown, Plus, Eye, Trash2, Edit, ChevronUp, ChevronDown, ShoppingCart, DollarSign, CheckCircle, Clock } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";
import { formatCurrency } from "@/lib/utils";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import DateTimePicker from "@/Components/DateTimePicker";

const DeleteCashPurchaseModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete this cash purchase?
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
          Delete Cash Purchase
        </Button>
      </div>
    </form>
  </Modal>
);

const ImportCashPurchasesModal = ({ show, onClose, onSubmit, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onSubmit} className="p-6">
      <div className="ti-modal-header">
        <h3 className="text-lg font-bold">Import Cash Purchases</h3>
      </div>
      <div className="ti-modal-body grid grid-cols-12">
        <div className="col-span-12">
          <div className="flex items-center justify-between">
            <label className="block font-medium text-sm text-gray-700">
              Cash Purchases File
            </label>
            <Link href="/uploads/media/default/sample_cash_purchases.xlsx">
              <Button variant="secondary" size="sm">
                Use This Sample File
              </Button>
            </Link>
          </div>
          <input type="file" className="w-full dropify" name="cash_purchases_file" required />
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
          Close
        </Button>
        <Button
          type="submit"
          disabled={processing}
        >
          Import Cash Purchases
        </Button>
      </div>
    </form>
  </Modal>
);

const DeleteAllCashPurchasesModal = ({ show, onClose, onConfirm, processing, count }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete {count} selected cash purchase{count !== 1 ? 's' : ''}?
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

const ApproveAllCashPurchasesModal = ({ show, onClose, onConfirm, processing, count }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to approve {count} selected cash purchase{count !== 1 ? 's' : ''}?
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
          Approve Selected
        </Button>
      </div>
    </form>
  </Modal>
);

const RejectAllCashPurchasesModal = ({ show, onClose, onConfirm, processing, count }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to reject {count} selected cash purchase{count !== 1 ? 's' : ''}?
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
          Reject Selected
        </Button>
      </div>
    </form>
  </Modal>
);

const PurchaseApprovalStatusBadge = ({ status }) => {
  const statusMap = {
    0: { label: "Pending", className: "text-gray-400" },
    1: { label: "Approved", className: "text-green-400" },
  };

  const { label, className } = statusMap[status] || statusMap[0];

  return (
    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${className}`}>
      {label}
    </span>
  );
};

const SummaryCards = ({ summary = {} }) => {
  const cards = [
    {
      title: "Total Purchases",
      value: summary.total_purchases || 0,
      description: "Total cash purchases",
      icon: ShoppingCart,
      iconColor: "text-blue-500"
    },
    {
      title: "Grand Total",
      value: formatCurrency({ amount: summary.grand_total || 0 }),
      description: "Total amount of all purchases",
      icon: DollarSign,
      iconColor: "text-green-500"
    },
    {
      title: "Total Approved",
      value: summary.total_approved || 0,
      description: "Approved cash purchases",
      icon: CheckCircle,
      iconColor: "text-purple-500"
    },
    {
      title: "Total Pending",
      value: summary.total_pending || 0,
      description: "Pending cash purchases",
      icon: Clock,
      iconColor: "text-orange-500"
    }
  ];

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      {cards.map((card, index) => (
        <div key={index} className="bg-gray-100 rounded-lg shadow-sm p-4">
          <div className="flex flex-row items-center justify-between space-y-0 pb-2">
            <h3 className="text-lg font-medium">
              {card.title}
            </h3>
            <card.icon className={`h-8 w-8 ${card.iconColor}`} />
          </div>
          <div className="text-2xl font-bold">{card.value}
          <p className="text-xs text-muted-foreground">
            {card.description}
            </p>
          </div>
        </div>
      ))}
    </div>
  );
};

export default function List({ purchases = [], meta = {}, filters = {}, vendors = [], summary = {} }) {
  const { flash = {} } = usePage().props;
  const { toast } = useToast();
  const [selectedPurchases, setSelectedPurchases] = useState([]);
  const [isAllSelected, setIsAllSelected] = useState(false);
  const [search, setSearch] = useState(filters.search || "");
  const [perPage, setPerPage] = useState(filters.per_page || 50);
  const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
  const [bulkAction, setBulkAction] = useState("");
  const [sorting, setSorting] = useState(filters.sorting || { column: "id", direction: "desc" });
  const [selectedVendor, setSelectedVendor] = useState(filters.vendor_id || "");
  const [dateRange, setDateRange] = useState(filters.date_range || []);
  const [selectedStatus, setSelectedStatus] = useState(filters.status || "");

  // Delete confirmation modal states
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showImportModal, setShowImportModal] = useState(false);
  const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
  const [showApproveAllModal, setShowApproveAllModal] = useState(false);
  const [showRejectAllModal, setShowRejectAllModal] = useState(false);
  const [purchaseToDelete, setPurchaseToDelete] = useState(null);
  const [processing, setProcessing] = useState(false);

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

  const toggleSelectAll = (checked) => {
    setIsAllSelected(checked);
    setSelectedPurchases(checked ? purchases.map(purchase => purchase.id) : []);
  };

  const toggleSelectPurchase = (purchaseId) => {
    setSelectedPurchases(prev => {
      if (prev.includes(purchaseId)) {
        return prev.filter(id => id !== purchaseId);
      } else {
        return [...prev, purchaseId];
      }
    });
  };

  const handleDeleteConfirm = (purchaseId) => {
    setPurchaseToDelete(purchaseId);
    setShowDeleteModal(true);
  };

  const handleDelete = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.delete(route("cash_purchases.destroy", purchaseToDelete), {
      onSuccess: () => {
        setShowDeleteModal(false);
        setPurchaseToDelete(null);
        setProcessing(false);
        setSelectedPurchases(prev => prev.filter(id => id !== purchaseToDelete));
      },
      onError: () => {
        setProcessing(false);
      }
    });
  };

  const handleDeleteAll = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.post(
      route("cash_purchases.bulk_destroy"),
      {
        ids: selectedPurchases,
      },
      {
        onSuccess: () => {
          setShowDeleteAllModal(false);
          setProcessing(false);
          setSelectedPurchases([]);
          setIsAllSelected(false);
        },
        onError: () => {
          setProcessing(false);
        }
      });
  };

  const handleApproveAll = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.post(route("cash_purchases.bulk_approve"),
      {
        ids: selectedPurchases,
      },
      {
        onSuccess: () => {
          setShowApproveAllModal(false);
          setProcessing(false);
          setSelectedPurchases([]);
          setIsAllSelected(false);
        },
        onError: () => {
          setProcessing(false);
        }
      });
  };

  const handleRejectAll = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.post(
      route("cash_purchases.bulk_reject"),
      {
        ids: selectedPurchases,
      },
      {
        onSuccess: () => {
          setShowRejectAllModal(false);
          setProcessing(false);
          setSelectedPurchases([]);
          setIsAllSelected(false);
        },
        onError: () => {
          setProcessing(false);
        }
      });
  };

  const handleImport = (e) => {
    e.preventDefault();
    setProcessing(true);

    const formData = new FormData(e.target);

    router.post(route("cash_purchases.import"), formData, {
      onSuccess: () => {
        setShowImportModal(false);
        setProcessing(false);
      },
      onError: (errors) => {
        setProcessing(false);
      }
    });
  };

  const handleSearch = (e) => {
    e.preventDefault();
    const value = e.target.value;
    setSearch(value);

    router.get(
      route("cash_purchases.index"),
      { search: value, page: 1, per_page: perPage },
      { preserveState: true }
    );
  };

  const handlePerPageChange = (value) => {
    setPerPage(parseInt(value));
    router.get(
      route("cash_purchases.index"),
      { search, page: 1, per_page: value },
      { preserveState: true }
    );
  };

  const handlePageChange = (page) => {
    setCurrentPage(page);
    router.get(
      route("cash_purchases.index"),
      { 
        search, 
        page, 
        per_page: perPage,
        vendor_id: selectedVendor,
        date_range: dateRange,
        status: selectedStatus,
        sorting: sorting
      },
      { preserveState: true }
    );
  };

  const handleBulkAction = () => {
    if (bulkAction === "delete" && selectedPurchases.length > 0) {
      setShowDeleteAllModal(true);
    }
    if (bulkAction === "approve" && selectedPurchases.length > 0) {
      setShowApproveAllModal(true);
    }
    if (bulkAction === "reject" && selectedPurchases.length > 0) {
      setShowRejectAllModal(true);
    }
  };

  const handleSort = (column) => {
    let direction = "asc";
    if (sorting.column === column && sorting.direction === "asc") {
      direction = "desc";
    }
    setSorting({ column, direction });
    router.get(
      route("cash_purchases.index"),
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
    const totalPages = meta.last_page || 1;
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

  const handleFilter = (filterType, value) => {
    // Update the state first
    switch (filterType) {
      case 'vendor':
        setSelectedVendor(value);
        break;
      case 'date':
        setDateRange(value);
        break;
      case 'status':
        setSelectedStatus(value);
        break;
    }

    // Use the new value directly in the request instead of state
    router.get(
      route("cash_purchases.index"),
      { 
        search, 
        page: 1, 
        per_page: perPage,
        vendor_id: filterType === 'vendor' ? value : selectedVendor,
        date_range: filterType === 'date' ? value : dateRange,
        status: filterType === 'status' ? value : selectedStatus
      },
      { preserveState: true }
    );
  };

  const exportCashPurchases = () => {
    window.location.href = route("cash_purchases.export");
  };
  return (
    <AuthenticatedLayout>
      <Toaster />
      <SidebarInset>
        <div className="main-content">
          <PageHeader
            page="Cash Purchases"
            subpage="List"
            url="cash_purchases.index"
          />
          <div className="p-4">
            <SummaryCards summary={summary} />
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
              <div className="flex flex-col md:flex-row gap-2">
                <Link href={route("cash_purchases.create")}>
                  <Button>
                    <Plus className="h-4 w-4 mr-2" />
                    Add Cash Purchase
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
                    <DropdownMenuItem onClick={exportCashPurchases}>
                      <FileDown className="mr-2 h-4 w-4" /> Export
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
              <div className="flex flex-col md:flex-row gap-4 md:items-center">
                <Input
                  placeholder="Search cash purchases..."
                  value={search}
                  onChange={(e) => handleSearch(e)}
                  className="w-full md:w-80"
                />
              </div>
            </div>

            <div className="mb-4 flex flex-col md:flex-row gap-2 justify-between">
              <div className="flex flex-col md:flex-row gap-2">
                <Select value={bulkAction} onValueChange={setBulkAction}>
                  <SelectTrigger className="w-[180px]">
                    <SelectValue placeholder="Bulk actions" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="delete">Delete Selected</SelectItem>
                    <SelectItem value="approve">Approve Selected</SelectItem>
                    <SelectItem value="reject">Reject Selected</SelectItem>
                  </SelectContent>
                </Select>
                <Button onClick={handleBulkAction} variant="outline">
                  Apply
                </Button>

                <div className="flex flex-col md:flex-row gap-4">
                  <SearchableCombobox
                    options={vendors.map(vendor => ({
                      id: vendor.id,
                      name: vendor.name
                    }))}
                    value={selectedVendor}
                    onChange={(value) => handleFilter('vendor', value)}
                    placeholder="Select vendor"
                    className="w-[200px]"
                  />

                  <DateTimePicker
                    value={dateRange}
                    onChange={(dates) => handleFilter('date', dates)}
                    isRange={true}
                    className="w-[200px]"
                    placeholder="Select date range"
                  />

                  <SearchableCombobox
                    options={[
                      { id: "", name: "All Status" },
                      { id: "0", name: "Pending" },
                      { id: "1", name: "Approved" }
                    ]}
                    value={selectedStatus}
                    onChange={(value) => handleFilter('status', value)}
                    placeholder="Select status"
                    className="w-[150px]"
                  />
                </div>
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
                    <TableHead className="cursor-pointer" onClick={() => handleSort("bill_no")}>Bill Number {renderSortIcon("bill_no")}</TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("vendor.name")}>Supplier {renderSortIcon("vendor.name")}</TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("purchase_date")}>Purchase Date {renderSortIcon("purchase_date")}</TableHead>
                    <TableHead className="cursor-pointer text-right" onClick={() => handleSort("grand_total")}>Grand Total {renderSortIcon("grand_total")}</TableHead>
                    <TableHead className="cursor-pointer text-right" onClick={() => handleSort("approval_status")}>Status {renderSortIcon("approval_status")}</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {purchases.length > 0 ? (
                    purchases.map((purchase) => (
                      <TableRow key={purchase.id}>
                        <TableCell>
                          <Checkbox
                            checked={selectedPurchases.includes(purchase.id)}
                            onCheckedChange={() => toggleSelectPurchase(purchase.id)}
                          />
                        </TableCell>
                        <TableCell>{purchase.bill_no}</TableCell>
                        <TableCell>{purchase.vendor ? purchase.vendor.name : "-"}</TableCell>
                        <TableCell>{purchase.purchase_date}</TableCell>
                        <TableCell className="text-right">
                          {purchase.grand_total !== purchase.converted_total ? (
                            <span>
                              {formatCurrency({ amount: purchase.grand_total, currency: purchase.business.currency })} ({formatCurrency({ amount: purchase.converted_total, currency: purchase.currency })})
                            </span>
                          ) : (
                            <span>
                              {formatCurrency({ amount: purchase.grand_total, currency: purchase.business.currency })}
                            </span>
                          )}
                        </TableCell>
                        <TableCell>
                          <PurchaseApprovalStatusBadge status={purchase.approval_status} />
                        </TableCell>
                        <TableCell className="text-right">
                          <TableActions
                            actions={[
                              {
                                label: "View",
                                icon: <Eye className="h-4 w-4" />,
                                href: route("cash_purchases.show", purchase.id),
                              },
                              {
                                label: "Edit",
                                icon: <Edit className="h-4 w-4" />,
                                href: route("cash_purchases.edit", purchase.id),
                              },
                              {
                                label: "Delete",
                                icon: <Trash2 className="h-4 w-4" />,
                                onClick: () => handleDeleteConfirm(purchase.id),
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
                        No cash purchases found.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>

            {purchases.length > 0 && meta.total > 0 && (
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

      <DeleteCashPurchaseModal
        show={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        onConfirm={handleDelete}
        processing={processing}
      />

      <DeleteAllCashPurchasesModal
        show={showDeleteAllModal}
        onClose={() => setShowDeleteAllModal(false)}
        onConfirm={handleDeleteAll}
        processing={processing}
        count={selectedPurchases.length}
      />

      <ApproveAllCashPurchasesModal
        show={showApproveAllModal}
        onClose={() => setShowApproveAllModal(false)}
        onConfirm={handleApproveAll}
        processing={processing}
        count={selectedPurchases.length}
      />

      <RejectAllCashPurchasesModal
        show={showRejectAllModal}
        onClose={() => setShowRejectAllModal(false)}
        onConfirm={handleRejectAll}
        processing={processing}
        count={selectedPurchases.length}
      />

      <ImportCashPurchasesModal
        show={showImportModal}
        onClose={() => setShowImportModal(false)}
        onSubmit={handleImport}
        processing={processing}
      />
    </AuthenticatedLayout>
  );
}
