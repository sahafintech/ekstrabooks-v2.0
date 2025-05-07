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
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogDescription,
} from "@/Components/ui/dialog";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/ui/select";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/Components/ui/popover";
import { Calendar } from "@/Components/ui/calendar";
import { Plus, Edit, Trash, Search, CalendarIcon } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";
import { format } from "date-fns";
import { cn } from "@/lib/utils";

// Delete Confirmation Modal Component
const DeleteConfirmationModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete this leave record?
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
const BulkDeleteConfirmationModal = ({ show, onClose, onConfirm, processing, count }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete {count} selected leave record{count !== 1 ? 's' : ''}?
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

export default function List({ leaves = [], meta = {}, filters = {} }) {
  const { flash = {} } = usePage().props;
  const { toast } = useToast();
  const [isAllSelected, setIsAllSelected] = useState(false);
  const [selectedLeaves, setSelectedLeaves] = useState([]);
  const [bulkAction, setBulkAction] = useState("");
  const [search, setSearch] = useState(filters.search || "");
  const [dateFilter, setDateFilter] = useState(filters.date || "");
  const [perPage, setPerPage] = useState(filters.per_page || 50);
  const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showBulkDeleteModal, setShowBulkDeleteModal] = useState(false);
  const [leaveToDelete, setLeaveToDelete] = useState(null);
  const [isProcessing, setIsProcessing] = useState(false);

  useEffect(() => {
    if (flash && flash.success) {
      toast({
        title: "Success",
        description: flash.success,
      });
    } else if (flash && flash.error) {
      toast({
        variant: "destructive",
        title: "Error",
        description: flash.error,
      });
    }
  }, [flash]);

  useEffect(() => {
    // Update isAllSelected when leaves or selectedLeaves change
    if (leaves.length > 0 && selectedLeaves.length === leaves.length) {
      setIsAllSelected(true);
    } else {
      setIsAllSelected(false);
    }
  }, [leaves, selectedLeaves]);

  const toggleSelectAll = () => {
    if (isAllSelected) {
      setSelectedLeaves([]);
    } else {
      setSelectedLeaves(leaves.map((leave) => leave.id));
    }
    setIsAllSelected(!isAllSelected);
  };

  const toggleSelectLeave = (id) => {
    if (selectedLeaves.includes(id)) {
      setSelectedLeaves(selectedLeaves.filter((leaveId) => leaveId !== id));
      setIsAllSelected(false);
    } else {
      setSelectedLeaves([...selectedLeaves, id]);
      if (selectedLeaves.length + 1 === leaves.length) {
        setIsAllSelected(true);
      }
    }
  };

  const handleSearch = (e) => {
    e.preventDefault();
    const value = e.target.value;
    setSearch(value);

    router.get(
      route("leaves.index"),
      { search: value, page: 1, per_page: perPage },
      { preserveState: true }
    );
  };

  const handleDateFilterChange = (date) => {
    setDateFilter(date ? format(date, "yyyy-MM-dd") : "");
    router.get(
      route("leaves.index"),
      { search, date: date ? format(date, "yyyy-MM-dd") : "", page: 1, per_page: perPage },
      { preserveState: true }
    );
  };

  const handlePerPageChange = (value) => {
    setPerPage(value);
    router.get(
      route("leaves.index"),
      { search, date: dateFilter, page: 1, per_page: value },
      { preserveState: true }
    );
  };

  const handlePageChange = (page) => {
    setCurrentPage(page);
    router.get(
      route("leaves.index"),
      { search, date: dateFilter, page, per_page: perPage },
      { preserveState: true }
    );
  };

  const handleBulkAction = () => {
    if (bulkAction === "") return;

    if (selectedLeaves.length === 0) {
      toast({
        variant: "destructive",
        title: "Error",
        description: "Please select at least one leave record",
      });
      return;
    }

    if (bulkAction === "delete") {
      setShowBulkDeleteModal(true);
    }
  };

  const handleDeleteConfirm = (id) => {
    setLeaveToDelete(id);
    setShowDeleteModal(true);
  };

  const handleDelete = (e) => {
    e.preventDefault();
    setIsProcessing(true);

    router.delete(route("leaves.destroy", leaveToDelete), {
      preserveState: true,
      onSuccess: () => {
        setShowDeleteModal(false);
        setLeaveToDelete(null);
        setIsProcessing(false);
      },
      onError: () => {
        setIsProcessing(false);
      }
    });
  };

  const handleBulkDeleteConfirm = (e) => {
    e.preventDefault();
    setIsProcessing(true);

    router.post(route("leaves.bulk_destroy"),
      {
        ids: selectedLeaves
      },
      {
        preserveState: true,
        onSuccess: () => {
          setSelectedLeaves([]);
          setIsAllSelected(false);
          setBulkAction("");
          setShowBulkDeleteModal(false);
          setIsProcessing(false);
        },
        onError: () => {
          setIsProcessing(false);
        }
      }
    );
  };

  const formatDate = (dateString) => {
    if (!dateString) return "-";
    try {
      return format(new Date(dateString), "PPP");
    } catch (error) {
      return dateString;
    }
  };

  const getLeaveTypeLabel = (type) => {
    const types = {
      casual: "Casual Leave",
      sick: "Sick Leave",
      maternity: "Maternity Leave",
      other: "Other Leave"
    };
    return types[type] || type;
  };

  const getStatusBadge = (status) => {
    switch (status) {
      case 'approved':
        return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Approved</span>;
      case 'rejected':
        return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Rejected</span>;
      case 'pending':
      default:
        return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>;
    }
  };

  // Generate pagination buttons
  const renderPageNumbers = () => {
    const pageNumbers = [];
    const pageRangeDisplayed = 5; // Number of pages to display

    let startPage = Math.max(1, currentPage - Math.floor(pageRangeDisplayed / 2));
    let endPage = Math.min(meta.last_page, startPage + pageRangeDisplayed - 1);

    if (endPage - startPage + 1 < pageRangeDisplayed) {
      startPage = Math.max(1, endPage - pageRangeDisplayed + 1);
    }

    // First page
    pageNumbers.push(
      <Button
        key="first"
        variant="outline"
        size="sm"
        onClick={() => handlePageChange(1)}
        disabled={currentPage === 1}
        className="h-8 w-8 p-0"
      >
        1
      </Button>
    );

    // Ellipsis after first page if needed
    if (startPage > 2) {
      pageNumbers.push(<span key="ellipsis1" className="px-2">...</span>);
    }

    // Page numbers
    for (let i = startPage; i <= endPage; i++) {
      if (i !== 1 && i !== meta.last_page) {
        pageNumbers.push(
          <Button
            key={i}
            variant={currentPage === i ? "default" : "outline"}
            size="sm"
            onClick={() => handlePageChange(i)}
            className="h-8 w-8 p-0"
          >
            {i}
          </Button>
        );
      }
    }

    // Ellipsis before last page if needed
    if (endPage < meta.last_page - 1) {
      pageNumbers.push(<span key="ellipsis2" className="px-2">...</span>);
    }

    // Last page
    if (meta.last_page > 1) {
      pageNumbers.push(
        <Button
          key="last"
          variant="outline"
          size="sm"
          onClick={() => handlePageChange(meta.last_page)}
          disabled={currentPage === meta.last_page}
          className="h-8 w-8 p-0"
        >
          {meta.last_page}
        </Button>
      );
    }

    return pageNumbers;
  };

  return (
    <AuthenticatedLayout>
      <Head title="Leave Management" />
      <Toaster />
      <SidebarInset>
        <div className="main-content">
          <PageHeader
            page="Leave Management"
            subpage="List"
            url="leaves.index"
          />
          <div className="p-4">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
              <div className="flex flex-col md:flex-row gap-4">
                <Button onClick={() => router.visit(route("leaves.create"))}>
                  <Plus className="w-4 h-4 mr-2" />
                  Add Leave
                </Button>
              </div>
              <div className="flex flex-col md:flex-row gap-4 md:items-center">
                <div className="flex flex-col md:flex-row gap-2">
                  <Input
                    placeholder="search leaves..."
                    value={search}
                    onChange={(e) => handleSearch(e)}
                    className="w-full md:w-60"
                  />
                  <Popover>
                    <PopoverTrigger asChild>
                      <Button
                        variant={"outline"}
                        className={cn(
                          "w-full md:w-auto justify-start text-left font-normal",
                          !dateFilter && "text-muted-foreground"
                        )}
                      >
                        <CalendarIcon className="mr-2 h-4 w-4" />
                        {dateFilter ? (
                          format(new Date(dateFilter), "PPP")
                        ) : (
                          <span>Filter by date</span>
                        )}
                      </Button>
                    </PopoverTrigger>
                    <PopoverContent className="w-auto p-0" align="start">
                      <Calendar
                        mode="single"
                        selected={dateFilter ? new Date(dateFilter) : undefined}
                        onSelect={handleDateFilterChange}
                        initialFocus
                      />
                    </PopoverContent>
                  </Popover>
                </div>
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
                <Button
                  onClick={handleBulkAction}
                  variant="outline"
                  disabled={!bulkAction || selectedLeaves.length === 0}
                >
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
                    <TableHead className="w-[50px]">ID</TableHead>
                    <TableHead>Employee</TableHead>
                    <TableHead>Leave Type</TableHead>
                    <TableHead>Duration</TableHead>
                    <TableHead>Start Date</TableHead>
                    <TableHead>End Date</TableHead>
                    <TableHead>Total Days</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {leaves.length > 0 ? (
                    leaves.map((leave) => (
                      <TableRow key={leave.id}>
                        <TableCell>
                          <Checkbox
                            checked={selectedLeaves.includes(leave.id)}
                            onCheckedChange={() => toggleSelectLeave(leave.id)}
                          />
                        </TableCell>
                        <TableCell>{leave.id}</TableCell>
                        <TableCell>{leave.staff?.name || '-'}</TableCell>
                        <TableCell>{getLeaveTypeLabel(leave.leave_type)}</TableCell>
                        <TableCell>{leave.leave_duration === 'full_day' ? 'Full Day' : 'Half Day'}</TableCell>
                        <TableCell>{formatDate(leave.start_date)}</TableCell>
                        <TableCell>{formatDate(leave.end_date)}</TableCell>
                        <TableCell>{leave.total_days}</TableCell>
                        <TableCell>{getStatusBadge(leave.status)}</TableCell>
                        <TableCell className="text-right">
                          <TableActions
                            actions={[
                              {
                                label: "Edit",
                                icon: <Edit className="h-4 w-4" />,
                                onClick: () => router.visit(route('leaves.edit', leave.id)),
                              },
                              {
                                label: "Delete",
                                icon: <Trash className="h-4 w-4" />,
                                onClick: () => handleDeleteConfirm(leave.id),
                                destructive: true,
                              },
                            ]}
                          />
                        </TableCell>
                      </TableRow>
                    ))
                  ) : (
                    <TableRow>
                      <TableCell colSpan={10} className="h-24 text-center">
                        No leave records found.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>

            {meta.last_page > 1 && (
              <div className="flex justify-between items-center mt-4">
                <div className="text-sm text-gray-500">
                  Showing {meta.from || 0} to {meta.to || 0} of {meta.total} entries
                </div>
                <div className="flex gap-1">{renderPageNumbers()}</div>
              </div>
            )}
          </div>

          {/* Delete Confirmation Modal */}
          <DeleteConfirmationModal
            show={showDeleteModal}
            onClose={() => setShowDeleteModal(false)}
            onConfirm={handleDelete}
            processing={isProcessing}
          />

          {/* Bulk Delete Confirmation Modal */}
          <BulkDeleteConfirmationModal
            show={showBulkDeleteModal}
            onClose={() => setShowBulkDeleteModal(false)}
            onConfirm={handleBulkDeleteConfirm}
            processing={isProcessing}
            count={selectedLeaves.length}
          />
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
