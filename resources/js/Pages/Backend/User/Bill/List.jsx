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
import { MoreVertical, FileUp, FileDown, Plus, Eye, Trash2, Edit, Check, X } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";

const DeleteBillModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete this bill?
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
          Delete Bill
        </Button>
      </div>
    </form>
  </Modal>
);

const ApproveBillModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to approve this bill?
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
          Approve Bill
        </Button>
      </div>
    </form>
  </Modal>
);

const RejectBillModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to reject this bill?
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
          Reject Bill
        </Button>
      </div>
    </form>
  </Modal>
);

const ImportBillsModal = ({ show, onClose, onSubmit, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onSubmit} className="p-6">
      <div className="ti-modal-header">
        <h3 className="text-lg font-bold">Import Bills</h3>
      </div>
      <div className="ti-modal-body grid grid-cols-12">
        <div className="col-span-12">
          <div className="flex items-center justify-between">
            <label className="block font-medium text-sm text-gray-700">
              Bills File
            </label>
            <Link href="/uploads/media/default/sample_bills.xlsx">
              <Button variant="secondary" size="sm">
                Use This Sample File
              </Button>
            </Link>
          </div>
          <input type="file" className="w-full dropify" name="bills_file" required />
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
          Import Bills
        </Button>
      </div>
    </form>
  </Modal>
);

const DeleteAllBillsModal = ({ show, onClose, onConfirm, processing, count }) => (
  <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete {count} selected bill{count !== 1 ? 's' : ''}?
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

const BillApprovalStatusBadge = ({ status }) => {
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

const BillStatusBadge = ({ status }) => {
  const statusMap = {
    0: { label: "Active", className: "text-blue-600" },
    1: { label: "Partial Paid", className: "text-yellow-600" },
    2: { label: "Paid", className: "text-green-600" },
  };

  const { label, className } = statusMap[status] || statusMap[0];

  return (
    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${className}`}>
      {label}
    </span>
  );
};

export default function List({ bills = [], meta = {}, filters = {} }) {
  const { flash = {} } = usePage().props;
  const { toast } = useToast();
  const [selectedBills, setSelectedBills] = useState([]);
  const [isAllSelected, setIsAllSelected] = useState(false);
  const [search, setSearch] = useState(filters.search || "");
  const [perPage, setPerPage] = useState(filters.per_page || 10);
  const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
  const [bulkAction, setBulkAction] = useState("");
  
  // Delete confirmation modal states
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showImportModal, setShowImportModal] = useState(false);
  const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
  const [billToDelete, setBillToDelete] = useState(null);
  const [processing, setProcessing] = useState(false);
  const [billToApprove, setBillToApprove] = useState(null);
  const [billToReject, setBillToReject] = useState(null);
  const [showApproveModal, setShowApproveModal] = useState(false);
  const [showRejectModal, setShowRejectModal] = useState(false);

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
    setSelectedBills(checked ? bills.map(bill => bill.id) : []);
  };

  const toggleSelectBill = (billId) => {
    setSelectedBills(prev => {
      if (prev.includes(billId)) {
        return prev.filter(id => id !== billId);
      } else {
        return [...prev, billId];
      }
    });
  };

  const handleDeleteConfirm = (billId) => {
    setBillToDelete(billId);
    setShowDeleteModal(true);
  };

  const handleApproveConfirm = (billId) => {
    setBillToApprove(billId);
    setShowApproveModal(true);
  };

  const handleRejectConfirm = (billId) => {
    setBillToReject(billId);
    setShowRejectModal(true);
  };

  const handleDelete = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.delete(route("bill_invoices.destroy", billToDelete), {
      onSuccess: () => {
        setShowDeleteModal(false);
        setBillToDelete(null);
        setProcessing(false);
        setSelectedBills(prev => prev.filter(id => id !== billToDelete));
        toast({
          title: "Bill Deleted",
          description: "bill has been deleted successfully.",
        });
      },
      onError: () => {
        setProcessing(false);
        toast({
          variant: "destructive",
          title: "Error",
          description: "There was an error deleting the bill.",
        });
      }
    });
  };

  const handleApprove = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.post(route("bill_invoices.approve", billToApprove), {
      onSuccess: () => {
        setShowApproveModal(false);
        setBillToApprove(null);
        setProcessing(false);
        setSelectedBills(prev => prev.filter(id => id !== billToApprove));
        toast({
          title: "Bill Approved",
          description: "bill has been approved successfully.",
        });
      },
      onError: () => {
        setProcessing(false);
        toast({
          variant: "default",
          title: "Error",
          description: "There was an error approving the bill.",
        });
      }
    });
  };

  const handleReject = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.post(route("bill_invoices.reject", billToReject), {
      onSuccess: () => {
        setShowRejectModal(false);
        setBillToReject(null);
        setProcessing(false);
        setSelectedBills(prev => prev.filter(id => id !== billToReject));
        toast({
          title: "Bill Rejected",
          description: "bill has been rejected successfully.",
        });
      },
      onError: () => {
        setProcessing(false);
        toast({
          variant: "default",
          title: "Error",
          description: "There was an error approving the bill.",
        });
      }
    });
  };

  const handleDeleteAll = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.delete(route("bill_invoices.delete_all"), {
      data: { ids: selectedBills },
      onSuccess: () => {
        setShowDeleteAllModal(false);
        setProcessing(false);
        setSelectedBills([]);
        setIsAllSelected(false);
        toast({
          title: "Bills Deleted",
          description: "Selected cash bills have been deleted successfully.",
        });
      },
      onError: () => {
        setProcessing(false);
        toast({
          variant: "destructive",
          title: "Error",
          description: "There was an error deleting the selected cash bills.",
        });
      }
    });
  };

  const handleImport = (e) => {
    e.preventDefault();
    setProcessing(true);
    
    const formData = new FormData(e.target);
    
    router.post(route("bill_invoices.import"), formData, {
      onSuccess: () => {
        setShowImportModal(false);
        setProcessing(false);
        toast({
          title: "Import Successful",
          description: "Bill have been imported successfully.",
        });
      },
      onError: (errors) => {
        setProcessing(false);
        toast({
          variant: "destructive",
          title: "Import Failed",
          description: Object.values(errors).flat().join(", "),
        });
      }
    });
  };

  const handleSearch = (e) => {
    e.preventDefault();
    router.get(
      route("bill_invoices.index"),
      { search, page: 1, per_page: perPage },
      { preserveState: true }
    );
  };

  const handlePerPageChange = (value) => {
    setPerPage(parseInt(value));
    router.get(
      route("bill_invoices.index"),
      { search, page: 1, per_page: value },
      { preserveState: true }
    );
  };

  const handlePageChange = (page) => {
    setCurrentPage(page);
    router.get(
      route("bill_invoices.index"),
      { search, page, per_page: perPage },
      { preserveState: true }
    );
  };

  const handleBulkAction = () => {
    if (bulkAction === "delete" && selectedBills.length > 0) {
      setShowDeleteAllModal(true);
    }
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

  return (
    <AuthenticatedLayout>
      <Head title="Bill Invoices" />
      <Toaster />
      <SidebarInset>
        <div className="main-content">
          <PageHeader
            page="Bill Invoices"
            subpage="List"
            url="bill_invoices.index"
          />
          <div className="p-4">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
              <div className="flex flex-col md:flex-row gap-4">
                <Link href={route("bill_invoices.create")}>
                  <Button>
                    <Plus className="h-4 w-4 mr-2" />
                    Add Bill
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
                    <DropdownMenuItem onClick={() => window.location.href = route("bill_invoices.export")}>
                      <FileDown className="mr-2 h-4 w-4" /> Export
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
              <div className="flex flex-col md:flex-row gap-4 md:items-center">
                <form onSubmit={handleSearch} className="flex gap-2">
                  <Input
                    placeholder="Search cash bills..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="w-full md:w-80"
                  />
                  <Button type="submit">Search</Button>
                </form>
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
                    <TableHead>Bill Number</TableHead>
                    <TableHead>Vendor</TableHead>
                    <TableHead>Date</TableHead>
                    <TableHead>Due Date</TableHead>
                    <TableHead className="text-right">Grand Total</TableHead>
                    <TableHead className="text-right">Paid</TableHead>
                    <TableHead className="text-right">Due</TableHead>
                    <TableHead>Approval Status</TableHead>
                    <TableHead>Bill Status</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {bills.length > 0 ? (
                    bills.map((bill) => (
                      <TableRow key={bill.id}>
                        <TableCell>
                          <Checkbox
                            checked={selectedBills.includes(bill.id)}
                            onCheckedChange={() => toggleSelectBill(bill.id)}
                          />
                        </TableCell>
                        <TableCell>{bill.bill_no}</TableCell>
                        <TableCell>{bill.vendor ? bill.vendor.name : "-"}</TableCell>
                        <TableCell>{bill.purchase_date}</TableCell>
                        <TableCell>{bill.due_date}</TableCell>
                        <TableCell className="text-right">
                          {bill.grand_total}
                        </TableCell>
                        <TableCell className="text-right">
                          {bill.paid}
                        </TableCell>
                        <TableCell className="text-right">
                          {bill.grand_total - bill.paid}
                        </TableCell>
                        <TableCell>
                          <BillApprovalStatusBadge status={bill.approval_status} />
                        </TableCell>
                        <TableCell>
                          <BillStatusBadge status={bill.status} />
                        </TableCell>
                        <TableCell className="text-right">
                          <TableActions
                            actions={[
                              {
                                label: "View",
                                icon: <Eye className="h-4 w-4" />,
                                href: route("bill_invoices.show", bill.id),
                              },
                              {
                                label: "Edit",
                                icon: <Edit className="h-4 w-4" />,
                                href: route("bill_invoices.edit", bill.id),
                              },
                              {
                                label: "Approve",
                                icon: <Check className="h-4 w-4" />,
                                onclick: () => handleApproveConfirm(bill.id),
                              },
                              {
                                label: "Reject",
                                icon: <X className="h-4 w-4" />,
                                onclick: () => handleRejectConfirm(bill.id)
                              },
                              {
                                label: "Delete",
                                icon: <Trash2 className="h-4 w-4" />,
                                onClick: () => handleDeleteConfirm(bill.id),
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
                        No cash bills found.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>

            {bills.length > 0 && meta.total > 0 && (
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

      <DeleteBillModal
        show={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        onConfirm={handleDelete}
        processing={processing}
      />

      <ApproveBillModal
        show={showApproveModal}
        onClose={() => setShowApproveModal(false)}
        onConfirm={handleApprove}
        processing={processing}
      />

      <RejectBillModal
        show={showRejectModal}
        onClose={() => setShowRejectModal(false)}
        onConfirm={handleReject}
        processing={processing}
      />

      <DeleteAllBillsModal
        show={showDeleteAllModal}
        onClose={() => setShowDeleteAllModal(false)}
        onConfirm={handleDeleteAll}
        processing={processing}
        count={selectedBills.length}
      />

      <ImportBillsModal
        show={showImportModal}
        onClose={() => setShowImportModal(false)}
        onSubmit={handleImport}
        processing={processing}
      />
    </AuthenticatedLayout>
  );
}
