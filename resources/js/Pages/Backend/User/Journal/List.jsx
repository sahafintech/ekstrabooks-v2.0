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
import { MoreVertical, FileUp, FileDown, Plus, Eye, Trash2, Edit, ChevronUp, ChevronDown, Receipt, DollarSign, CheckCircle, AlertCircle } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";
import { formatCurrency } from "@/lib/utils";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";

const DeleteJournalModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete this journal?
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
          Delete Journal
        </Button>
      </div>
    </form>
  </Modal>
);

const ImportJournalsModal = ({ show, onClose, onSubmit, processing }) => (
  <Modal show={show} onClose={onClose} maxWidth="3xl">
    <form onSubmit={onSubmit}>
      <div className="ti-modal-header">
        <h3 className="text-lg font-bold">Import Journals</h3>
      </div>
      <div className="ti-modal-body grid grid-cols-12">
        <div className="col-span-12">
          <div className="flex items-center justify-between">
            <label className="block font-medium text-sm text-gray-700">
              Journal File
            </label>
            <a href="/uploads/media/default/sample_journal.xlsx" download>
              <Button variant="secondary" size="sm" type="button">
                Use This Sample File
              </Button>
            </a>
          </div>
          <input type="file" className="w-full dropify" name="journal_file" required />
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
          Import Journal
        </Button>
      </div>
    </form>
  </Modal>
);

const DeleteAllJournalsModal = ({ show, onClose, onConfirm, processing, count }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete {count} selected journal{count !== 1 ? 's' : ''}?
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

const ApproveAllJournalssModal = ({ show, onClose, onConfirm, processing, count }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to approve {count} selected journal{count !== 1 ? 's' : ''}?
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

const RejectAllJournalsModal = ({ show, onClose, onConfirm, processing, count }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to reject {count} selected journal{count !== 1 ? 's' : ''}?
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

const JournalStatusBadge = ({ status }) => {
  const statusMap = {
    1: { label: "Approved", className: "text-green-500" },
    0: { label: "Pending", className: "text-yellow-500" },
    2: { label: "Rejected", className: "text-red-500" },
  };

  return (
    <span className={statusMap[status].className}>
      {statusMap[status].label}
    </span>
  );
};

const SummaryCards = ({ journals = [] }) => {
  const totalJournals = journals.length;
  const totalApproved = journals.filter(journal => journal.status === 1).length;
  const totalPending = journals.filter(journal => journal.status === 2).length;
  const totalAmount = journals.reduce((sum, journal) => sum + parseFloat(journal.base_currency_amount), 0);

  const cards = [
    {
      title: "Total Journals",
      value: totalJournals,
      description: "Total number of journals",
      icon: Receipt,
      iconColor: "text-blue-500"
    },
    {
      title: "Total Amount",
      value: formatCurrency({ amount: totalAmount }),
      description: "Total amount in base currency",
      icon: DollarSign,
      iconColor: "text-green-500"
    },
    {
      title: "Approved Journals",
      value: totalApproved,
      description: "Journals that have been approved",
      icon: CheckCircle,
      iconColor: "text-purple-500"
    },
    {
      title: "Pending Journals",
      value: totalPending,
      description: "Journals pending approval",
      icon: AlertCircle,
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

export default function List({ journals = [], meta = {}, filters = {} }) {
  const { flash = {} } = usePage().props;
  const { toast } = useToast();
  const [selectedJournals, setSelectedJournals] = useState([]);
  const [isAllSelected, setIsAllSelected] = useState(false);
  const [search, setSearch] = useState(filters.search || "");
  const [perPage, setPerPage] = useState(filters.per_page || 50);
  const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
  const [bulkAction, setBulkAction] = useState("");
  const [sorting, setSorting] = useState(filters.sorting || { column: "id", direction: "desc" });
  const [selectedStatus, setSelectedStatus] = useState(filters.status || "");

  // Delete confirmation modal states
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showImportModal, setShowImportModal] = useState(false);
  const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
  const [showApproveAllModal, setShowApproveAllModal] = useState(false);
  const [showRejectAllModal, setShowRejectAllModal] = useState(false);
  const [journalToDelete, setJournalToDelete] = useState(null);
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
    setSelectedJournals(checked ? journals.map(journal => journal.id) : []);
  };

  const toggleSelectJournal = (journalId) => {
    setSelectedJournals(prev => {
      if (prev.includes(journalId)) {
        return prev.filter(id => id !== journalId);
      } else {
        return [...prev, journalId];
      }
    });
  };

  const handleDeleteConfirm = (journalId) => {
    setJournalToDelete(journalId);
    setShowDeleteModal(true);
  };

  const handleDelete = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.delete(route("journals.destroy", journalToDelete), {
      onSuccess: () => {
        setShowDeleteModal(false);
        setJournalToDelete(null);
        setProcessing(false);
        setSelectedJournals(prev => prev.filter(id => id !== journalToDelete));
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
      route("journals.bulk_destroy"),
      {
        ids: selectedJournals,
      },
      {
        onSuccess: () => {
          setShowDeleteAllModal(false);
          setProcessing(false);
          setSelectedJournals([]);
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

    router.post(route("journals.bulk_approve"),
      {
        ids: selectedJournals,
      },
      {
        onSuccess: () => {
          setShowApproveAllModal(false);
          setProcessing(false);
          setSelectedJournals([]);
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
      route("journals.bulk_reject"),
      {
        ids: selectedJournals,
      },
      {
        onSuccess: () => {
          setShowRejectAllModal(false);
          setProcessing(false);
          setSelectedJournals([]);
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

    router.post(route("journals.import"), formData, {
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
      route("journals.index"),
      { search: value, page: 1, per_page: perPage },
      { preserveState: true }
    );
  };

  const handlePerPageChange = (value) => {
    setPerPage(parseInt(value));
    router.get(
      route("journals.index"),
      { search, page: 1, per_page: value },
      { preserveState: true }
    );
  };

  const handlePageChange = (page) => {
    setCurrentPage(page);
    router.get(
      route("journals.index"),
      { search, page, per_page: perPage },
      { preserveState: true }
    );
  };

  const handleBulkAction = () => {
    if (bulkAction === "delete" && selectedJournals.length > 0) {
      setShowDeleteAllModal(true);
    }
    if (bulkAction === "approve" && selectedJournals.length > 0) {
      setShowApproveAllModal(true);
    }
    if (bulkAction === "reject" && selectedJournals.length > 0) {
      setShowRejectAllModal(true);
    }
  };

  const handleExport = () => {
    window.location.href = route("journals.export")
  }

  const handleSort = (column) => {
    let direction = "asc";
    if (sorting.column === column && sorting.direction === "asc") {
      direction = "desc";
    }
    setSorting({ column, direction });
    router.get(
      route("journals.index"),
      { ...filters, sorting: { column, direction } },
      { preserveState: true }
    );
  };

  const handleStatusChange = (value) => {
    setSelectedStatus(value);
    router.get(
      route("journals.index"),
      { 
        search, 
        page: 1, 
        per_page: perPage,
        status: value || null
      },
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

  return (
    <AuthenticatedLayout>
      <Head title="Journals" />
      <Toaster />
      <SidebarInset>
        <div className="main-content">
          <PageHeader
            page="Journals"
            subpage="List"
            url="journals.index"
          />
          <div className="p-4">
            <SummaryCards journals={journals} />
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
              <div className="flex flex-col md:flex-row gap-4">
                <Link href={route("journals.create")}>
                  <Button>
                    <Plus className="h-4 w-4 mr-2" />
                    Add Journal
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
                    <DropdownMenuItem onClick={handleExport}>
                      <FileDown className="mr-2 h-4 w-4" /> Export
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
              <div className="flex flex-col md:flex-row gap-4 md:items-center">
                <Input
                  placeholder="search journals..."
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
                    <SelectItem value="approve">Approve Selected</SelectItem>
                    <SelectItem value="reject">Reject Selected</SelectItem>
                  </SelectContent>
                </Select>
                <Button onClick={handleBulkAction} variant="outline">
                  Apply
                </Button>
                <SearchableCombobox
                  options={[
                    { id: "", name: "All Status" },
                    { id: "0", name: "Pending" },
                    { id: "1", name: "Approved" },
                    { id: "2", name: "Rejected" }
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
                        aria-label="Select all"
                      />
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("date")}>
                      Date {renderSortIcon("date")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("journal_number")}>
                      Journal # {renderSortIcon("journal_number")}
                    </TableHead>
                    <TableHead className="cursor-pointer text-right" onClick={() => handleSort("transaction_amount")}>
                      Transaction Amount {renderSortIcon("transaction_amount")}
                    </TableHead>
                    <TableHead className="cursor-pointer text-right" onClick={() => handleSort("currency_rate")}>
                      Currency Rate {renderSortIcon("currency_rate")}
                    </TableHead>
                    <TableHead className="cursor-pointer text-right" onClick={() => handleSort("base_currency_amount")}>
                      Base Currency Amount {renderSortIcon("base_currency_amount")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("status")}>
                      Status {renderSortIcon("status")}
                    </TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {journals.length > 0 ? (
                    journals.map((journal) => (
                      <TableRow key={journal.id}>
                        <TableCell>
                          <Checkbox
                            checked={selectedJournals.includes(journal.id)}
                            onCheckedChange={() => toggleSelectJournal(journal.id)}
                            aria-label={`Select journal ${journal.journal_number}`}
                          />
                        </TableCell>
                        <TableCell>
                          {journal.date}
                        </TableCell>
                        <TableCell>
                          <Link
                            href={route("journals.show", journal.id)}
                            className="font-medium hover:underline text-primary"
                          >
                            {journal.journal_number}
                          </Link>
                        </TableCell>
                        <TableCell className="text-right">
                          {formatCurrency({ amount: journal.transaction_amount, currency: journal.transaction_currency })}
                        </TableCell>
                        <TableCell className="text-right">
                          {formatCurrency({ amount: journal.currency_rate, currency: journal.transaction_currency })}
                        </TableCell>
                        <TableCell className="text-right">
                          {formatCurrency({ amount: journal.base_currency_amount })}
                        </TableCell>
                        <TableCell className="text-center">
                          <JournalStatusBadge status={journal.status} />
                        </TableCell>
                        <TableCell className="text-right">
                          <TableActions
                            actions={[
                              {
                                label: "View",
                                icon: <Eye className="h-4 w-4" />,
                                href: route("journals.show", journal.id),
                              },
                              {
                                label: "Edit",
                                icon: <Edit className="h-4 w-4" />,
                                href: route("journals.edit", journal.id),
                              },
                              {
                                label: "Delete",
                                icon: <Trash2 className="h-4 w-4" />,
                                onClick: () => handleDeleteConfirm(journal.id),
                                destructive: true,
                              },
                            ]}
                          />
                        </TableCell>
                      </TableRow>
                    ))
                  ) : (
                    <TableRow>
                      <TableCell colSpan={7} className="h-24 text-center">
                        No journals found.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>

            {journals.length > 0 && meta.total > 0 && (
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

      <DeleteJournalModal
        show={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        onConfirm={handleDelete}
        processing={processing}
      />

      <DeleteAllJournalsModal
        show={showDeleteAllModal}
        onClose={() => setShowDeleteAllModal(false)}
        onConfirm={handleDeleteAll}
        processing={processing}
        count={selectedJournals.length}
      />

      <ApproveAllJournalssModal
        show={showApproveAllModal}
        onClose={() => setShowApproveAllModal(false)}
        onConfirm={handleApproveAll}
        processing={processing}
        count={selectedJournals.length}
      />

      <RejectAllJournalsModal
        show={showRejectAllModal}
        onClose={() => setShowRejectAllModal(false)}
        onConfirm={handleRejectAll}
        processing={processing}
        count={selectedJournals.length}
      />

      <ImportJournalsModal
        show={showImportModal}
        onClose={() => setShowImportModal(false)}
        onSubmit={handleImport}
        processing={processing}
      />
    </AuthenticatedLayout>
  );
}
