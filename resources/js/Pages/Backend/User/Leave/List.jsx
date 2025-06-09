import React, { useState, useEffect } from "react";
import { Link, router, usePage } from "@inertiajs/react";
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/ui/select";
import { Plus, Edit, Trash, ChevronUp, ChevronDown } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";
import { format } from "date-fns";


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

const ApproveAllLeavesModal = ({ show, onClose, onConfirm, processing, count }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to approve {count} selected leave record{count !== 1 ? 's' : ''}?
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

const RejectAllLeavesModal = ({ show, onClose, onConfirm, processing, count }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to reject {count} selected leave record{count !== 1 ? 's' : ''}?
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

export default function List({ leaves = [], meta = {}, filters = {} }) {
  const { flash = {} } = usePage().props;
  const { toast } = useToast();
  const [selectedLeaves, setSelectedLeaves] = useState([]);
  const [isAllSelected, setIsAllSelected] = useState(false);
  const [search, setSearch] = useState(filters.search || "");
  const [dateFilter, setDateFilter] = useState(filters.date || "");
  const [perPage, setPerPage] = useState(meta.per_page || 50);
  const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
  const [bulkAction, setBulkAction] = useState("");
  const [sorting, setSorting] = useState(filters.sorting || { column: "id", direction: "desc" });

  // Delete confirmation modal states
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
  const [showApproveAllModal, setShowApproveAllModal] = useState(false);
  const [showRejectAllModal, setShowRejectAllModal] = useState(false);
  const [leaveToDelete, setLeaveToDelete] = useState(null);
  const [processing, setProcessing] = useState(false);

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
      setShowDeleteAllModal(true);
    } else if (bulkAction === "approve") {
      setShowApproveAllModal(true);
    } else if (bulkAction === "reject") {
      setShowRejectAllModal(true);
    }
  };

  const handleDeleteConfirm = (id) => {
    setLeaveToDelete(id);
    setShowDeleteModal(true);
  };

  const handleDelete = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.delete(route("leaves.destroy", leaveToDelete), {
      preserveState: true,
      onSuccess: () => {
        setShowDeleteModal(false);
        setLeaveToDelete(null);
        setProcessing(false);
      },
      onError: () => {
        setProcessing(false);
      }
    });
  };

  const handleBulkDeleteConfirm = (e) => {
    e.preventDefault();
    setProcessing(true);

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
          setShowDeleteAllModal(false);
          setProcessing(false);
        },
        onError: () => {
          setProcessing(false);
        }
      }
    );
  };

  const handleApproveAll = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.post(route("leaves.bulk_approve"),
      {
        ids: selectedLeaves,
      },
      {
        onSuccess: () => {
          setShowApproveAllModal(false);
          setProcessing(false);
          setSelectedLeaves([]);
          setIsAllSelected(false);
          setBulkAction("");
        },
        onError: () => {
          setProcessing(false);
        }
      });
  };

  const handleRejectAll = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.post(route("leaves.bulk_reject"),
      {
        ids: selectedLeaves,
      },
      {
        onSuccess: () => {
          setShowRejectAllModal(false);
          setProcessing(false);
          setSelectedLeaves([]);
          setIsAllSelected(false);
          setBulkAction("");
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
      route("leaves.index"),
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

  const getLeaveTypeLabel = (type) => {
    const types = {
      casual: "Casual Leave",
      sick: "Sick Leave",
      maternity: "Maternity Leave",
      other: "Other Leave"
    };
    return types[type] || type;
  };

  const LeaveStatusBadge = ({ status }) => {
      const statusMap = {
          0: {
              label: "Pending",
              className: "text-gray-600 bg-gray-200 px-3 py-1 rounded text-xs",
          },
          1: {
              label: "Approved",
              className:
                  "text-green-600 bg-green-200 px-3 py-1 rounded text-xs",
          },
          2: {
              label: "Cancelled",
              className: "text-red-600 bg-red-200 px-3 py-1 rounded text-xs",
          },
      };

      return (
          <span className={statusMap[status].className}>
              {statusMap[status].label}
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
                <Link href={route("leaves.create")}>
                  <Button>
                    <Plus className="w-4 h-4 mr-2" />
                    Add Leave
                  </Button>
                </Link>
              <div className="flex flex-col md:flex-row gap-4 md:items-center">
                <Input
                  placeholder="search leaves..."
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
                    <TableHead className="cursor-pointer" onClick={() => handleSort("id")}>
                      ID {renderSortIcon("id")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("staff.name")}>
                      Employee {renderSortIcon("staff.name")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("leave_type")}>
                      Leave Type {renderSortIcon("leave_type")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("leave_duration")}>
                      Duration {renderSortIcon("leave_duration")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("start_date")}>
                      Start Date {renderSortIcon("start_date")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("end_date")}>
                      End Date {renderSortIcon("end_date")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("total_days")}>
                      Total Days {renderSortIcon("total_days")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("status")}>
                      Status {renderSortIcon("status")}
                    </TableHead>
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
                        <TableCell>{leave.start_date}</TableCell>
                        <TableCell>{leave.end_date}</TableCell>
                        <TableCell>{leave.total_days}</TableCell>
                        <TableCell><LeaveStatusBadge status={leave.status} /></TableCell>
                        <TableCell className="text-right">
                          <TableActions
                            actions={[
                              {
                                label: "Edit",
                                icon: <Edit className="h-4 w-4" />,
                                href: route("leaves.edit", leave.id),
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

            {leaves.length > 0 && meta.total > 0 && (
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

      <DeleteConfirmationModal
        show={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        onConfirm={handleDelete}
        processing={processing}
      />

      <BulkDeleteConfirmationModal
        show={showDeleteAllModal}
        onClose={() => setShowDeleteAllModal(false)}
        onConfirm={handleBulkDeleteConfirm}
        processing={processing}
        count={selectedLeaves.length}
      />

      <ApproveAllLeavesModal
        show={showApproveAllModal}
        onClose={() => setShowApproveAllModal(false)}
        onConfirm={handleApproveAll}
        processing={processing}
        count={selectedLeaves.length}
      />

      <RejectAllLeavesModal
        show={showRejectAllModal}
        onClose={() => setShowRejectAllModal(false)}
        onConfirm={handleRejectAll}
        processing={processing}
        count={selectedLeaves.length}
      />
    </AuthenticatedLayout>
  );
}
