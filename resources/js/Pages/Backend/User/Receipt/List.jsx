import React, { useState, useEffect, useMemo } from "react";
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
import { MoreVertical, FileUp, FileDown, Plus, Eye, Trash2, Edit } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";

const DeleteReceiptModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm} className="p-6">
      <h2 className="text-lg font-medium">
        Are you sure you want to delete this receipt?
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
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onSubmit} className="p-6">
      <div className="ti-modal-header">
        <h3 className="text-lg font-bold">Import Receipts</h3>
      </div>
      <div className="ti-modal-body grid grid-cols-12">
        <div className="col-span-12">
          <div className="flex items-center justify-between">
            <label className="block font-medium text-sm text-gray-700">
              Receipts File
            </label>
            <Link href="/uploads/media/default/sample_receipts.xlsx">
              <Button variant="secondary" size="sm">
                Use This Sample File
              </Button>
            </Link>
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
          variant="primary"
          disabled={processing}
        >
          Import Receipts
        </Button>
      </div>
    </form>
  </Modal>
);

const DeleteAllReceiptsModal = ({ show, onClose, onConfirm, processing, count }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm} className="p-6">
      <h2 className="text-lg font-medium">
        Are you sure you want to delete {count} selected receipt{count !== 1 ? 's' : ''}?
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

const ReceiptStatusBadge = ({ status }) => {
  const statusClasses = {
    0: "bg-amber-100 text-amber-800",
    1: "bg-green-100 text-green-800",
    2: "bg-red-100 text-red-800",
  };

  const statusText = {
    0: "Draft",
    1: "Paid",
    2: "Cancelled",
  };

  return (
    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusClasses[status]}`}>
      {statusText[status]}
    </span>
  );
};

export default function List({ receipts = [], meta = {}, filters = {} }) {
  const { toast } = useToast();
  const { auth } = usePage().props;
  const [selectedRows, setSelectedRows] = useState([]);
  const [selectAll, setSelectAll] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showImportModal, setShowImportModal] = useState(false);
  const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
  const [currentReceiptId, setCurrentReceiptId] = useState(null);
  const [processing, setProcessing] = useState(false);
  const [perPage, setPerPage] = useState(filters.per_page || 10);
  const [search, setSearch] = useState(filters.search || "");
  const [currentPage, setCurrentPage] = useState(meta.current_page || 1);

  const handleSearch = (e) => {
    e.preventDefault();
    router.get(
      route("receipts.index"),
      { search, page: 1, per_page: perPage },
      { preserveState: true }
    );
  };

  const handlePerPageChange = (value) => {
    setPerPage(parseInt(value));
    router.get(
      route("receipts.index"),
      { search, page: 1, per_page: value },
      { preserveState: true }
    );
  };

  const handlePageChange = (page) => {
    setCurrentPage(page);
    router.get(
      route("receipts.index"),
      { search, page, per_page: perPage },
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

  const openImportModal = () => {
    setShowImportModal(true);
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
        toast({
          title: "Receipts Imported",
          description: "Receipts have been imported successfully.",
        });
        closeImportModal();
        setProcessing(false);
      },
      onError: (errors) => {
        toast({
          title: "Error",
          description: "Failed to import receipts.",
          variant: "destructive",
        });
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

    router.delete(route("receipts.delete_all"), {
      onSuccess: () => {
        toast({
          title: "All Receipts Deleted",
          description: "All receipts have been deleted successfully.",
        });
        closeDeleteAllModal();
        setProcessing(false);
        setSelectedRows([]);
        setSelectAll(false);
      },
      onError: (errors) => {
        toast({
          title: "Error",
          description: "Failed to delete all receipts.",
          variant: "destructive",
        });
        setProcessing(false);
      },
    });
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

  return (
    <AuthenticatedLayout>
      <Head title="Receipts" />
      <Toaster />
      <SidebarInset>
        <div className="main-content">
          <PageHeader
            page="Receipts"
            subpage="List"
            url="receipts.index"
          />
          <div className="p-4">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
              <div className="flex flex-col md:flex-row gap-4">
                <Link href={route("receipts.create")}>
                  <Button>
                    <Plus className="w-4 h-4 mr-2" />
                    Add Receipt
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
                <form onSubmit={handleSearch} className="flex gap-2">
                  <Input
                    placeholder="Search receipts..."
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
              </div>
              <div className="flex items-center gap-2">
                <span className="text-sm text-gray-500">Show</span>
                <Select value={perPage.toString()} onValueChange={(value) => {
                  setPerPage(parseInt(value));
                  router.get(
                    route("receipts.index"),
                    { search, page: 1, per_page: value },
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
                    <TableHead>Receipt Number</TableHead>
                    <TableHead>Customer</TableHead>
                    <TableHead>Receipt Date</TableHead>
                    <TableHead>Title</TableHead>
                    <TableHead>Total</TableHead>
                    <TableHead>Currency</TableHead>
                    <TableHead>Actions</TableHead>
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
                          {receipt.customer ? receipt.customer.name : "N/A"}
                        </TableCell>
                        <TableCell>
                          {receipt.receipt_date ? new Date(receipt.receipt_date).toLocaleDateString() : "N/A"}
                        </TableCell>
                        <TableCell>{receipt.title}</TableCell>
                        <TableCell>
                          {receipt.grand_total ? receipt.grand_total.toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                          }) : "0.00"} {receipt.currency}
                        </TableCell>
                        <TableCell>
                          {receipt.currency} {parseFloat(receipt.exchange_rate) === 1 ? 
                            <span className="text-xs text-green-600">(Base)</span> : 
                            <span className="text-xs">({parseFloat(receipt.exchange_rate).toFixed(6)})</span>}
                        </TableCell>
                        <TableCell>
                          <TableActions
                            actions={[
                              {
                                label: "View",
                                icon: <Eye className="h-4 w-4" />,
                                href: route("receipts.show", receipt.id),
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
