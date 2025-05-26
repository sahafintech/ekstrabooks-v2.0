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
import { format, parse, isValid } from "date-fns";
import { Download, Edit, EyeIcon, FileDown, FileUp, MoreVertical, Plus, Trash } from "lucide-react";
import { toast } from "sonner";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";

// Delete Confirmation Modal Component
const DeleteConfirmationModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete this attendance record?
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
        Are you sure you want to delete {count} selected attendance {count !== 1 ? 'records' : 'record'}?
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

// Import Attendance Modal Component
const ImportAttendanceModal = ({ show, onClose, onSubmit, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onSubmit} className="p-6">
      <div className="ti-modal-header">
        <h3 className="text-lg font-bold">Import Attendance</h3>
      </div>
      <div className="ti-modal-body grid grid-cols-12">
        <div className="col-span-12">
          <div className="flex items-center justify-between">
            <label className="block font-medium text-sm text-gray-700">
              Attendance File
            </label>
            <Link href="/uploads/media/default/sample_attendance.xlsx">
              <Button variant="secondary" size="sm">
                Use This Sample File
              </Button>
            </Link>
          </div>
          <input type="file" className="w-full dropify" name="attendances_file" required />
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
                File format Supported: XLS, XLSX
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
      <div className="mt-6 flex justify-end">
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
          Import Attendance
        </Button>
      </div>
    </form>
  </Modal>
);

export default function List({ attendances = [], meta = {}, filters = {} }) {
  const { flash = {} } = usePage().props;
  const [selectedAttendances, setSelectedAttendances] = useState([]);
  const [isAllSelected, setIsAllSelected] = useState(false);
  const [search, setSearch] = useState(filters.search || "");
  const [perPage, setPerPage] = useState(meta.per_page || 10);
  const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
  const [bulkAction, setBulkAction] = useState("");
  
  // Modal states
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showBulkDeleteModal, setShowBulkDeleteModal] = useState(false);
  const [showImportModal, setShowImportModal] = useState(false);
  const [attendanceToDelete, setAttendanceToDelete] = useState(null);
  const [isProcessing, setIsProcessing] = useState(false);

  useEffect(() => {
    if (flash && flash.success) {
      toast.success(flash.success);
    }

    if (flash && flash.error) {
      toast.error(flash.error);
    }
  }, [flash]);

  const toggleSelectAll = () => {
    if (isAllSelected) {
      setSelectedAttendances([]);
    } else {
      setSelectedAttendances(attendances.map((attendance) => attendance.date));
    }
    setIsAllSelected(!isAllSelected);
  };

  const toggleSelectAttendance = (date) => {
    if (selectedAttendances.includes(date)) {
      setSelectedAttendances(selectedAttendances.filter((d) => d !== date));
      setIsAllSelected(false);
    } else {
      setSelectedAttendances([...selectedAttendances, date]);
      if (selectedAttendances.length + 1 === attendances.length) {
        setIsAllSelected(true);
      }
    }
  };

  const handleSearch = (e) => {
    e.preventDefault();
    router.get(
      route("attendance.index"),
      { search, page: 1, per_page: perPage },
      { preserveState: true }
    );
  };

  const handlePerPageChange = (value) => {
    setPerPage(value);
    router.get(
      route("attendance.index"),
      { search, page: 1, per_page: value },
      { preserveState: true }
    );
  };

  const handlePageChange = (page) => {
    setCurrentPage(page);
    router.get(
      route("attendance.index"),
      { search, page, per_page: perPage },
      { preserveState: true }
    );
  };

  const handleBulkAction = () => {
    if (bulkAction === "") return;

    if (selectedAttendances.length === 0) {
      toast.error("Please select at least one attendance record");
      return;
    }

    if (bulkAction === "delete") {
      setShowBulkDeleteModal(true);
    }
  };

  const handleDeleteConfirm = (date) => {
    setAttendanceToDelete(date);
    setShowDeleteModal(true);
  };

  const handleDelete = (e) => {
    e.preventDefault();
    setIsProcessing(true);
    
    router.delete(route("attendance.destroy", attendanceToDelete), {
      preserveState: true,
      onSuccess: () => {
        setShowDeleteModal(false);
        setAttendanceToDelete(null);
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
    
    router.post(route("attendance.bulk_delete"), {
      dates: selectedAttendances
    }, {
      preserveState: true,
      onSuccess: () => {
        setSelectedAttendances([]);
        setIsAllSelected(false);
        setBulkAction("");
        setShowBulkDeleteModal(false);
        setIsProcessing(false);
      },
      onError: () => {
        setIsProcessing(false);
      }
    });
  };

  const handleImportSubmit = (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    setIsProcessing(true);
    
    router.post(route('attendance.import_attendance'), formData, {
      onSuccess: () => {
        setShowImportModal(false);
        setIsProcessing(false);
      },
      onError: () => {
        setIsProcessing(false);
      }
    });
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

  const formatDate = (dateString) => {
    if (!dateString) return "-";
    const parsed = parse(dateString, "yyyy-MM-dd", new Date());
    if (!isValid(parsed)) return dateString;
    return format(parsed, "dd MMM yyyy");
  };

  return (
    <AuthenticatedLayout>      
      <DeleteConfirmationModal
        show={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        onConfirm={handleDelete}
        processing={isProcessing}
      />
      
      <BulkDeleteConfirmationModal
        show={showBulkDeleteModal}
        onClose={() => setShowBulkDeleteModal(false)}
        onConfirm={handleBulkDeleteConfirm}
        processing={isProcessing}
        count={selectedAttendances.length}
      />
      
      <ImportAttendanceModal
        show={showImportModal}
        onClose={() => setShowImportModal(false)}
        onSubmit={handleImportSubmit}
        processing={isProcessing}
      />
      
      <SidebarInset>
        <div className="main-content">
          <PageHeader
            page="Staff Management"
            subpage="Attendance"
            url="attendance.index"
          />
          
          <div className="p-4">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
              <div className="flex flex-col md:flex-row gap-4">
                <Link href={route("attendance.create")}>
                  <Button>
                    <Plus className="w-4 h-4 mr-2" />
                    Add Attendance
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
                    <DropdownMenuItem>
                      <FileDown className="mr-2 h-4 w-4" /> Export
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
              <div className="flex flex-col md:flex-row gap-4 md:items-center">
                <form onSubmit={handleSearch} className="flex gap-2">
                  <Input
                    placeholder="Search attendance by date..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="w-full md:w-80"
                  />
                  <Button type="submit">Search</Button>
                </form>
              </div>
            </div>

            <div className="rounded-md border">
              {attendances.length > 0 ? (
                <>
                  <div className="p-3 border-b bg-muted/40 flex items-center justify-between">
                    <div className="flex items-center space-x-2">
                      <Checkbox
                        id="select-all"
                        checked={isAllSelected}
                        onCheckedChange={toggleSelectAll}
                      />
                      <label
                        htmlFor="select-all"
                        className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                      >
                        Select All
                      </label>
                    </div>
                    <div className="flex items-center space-x-2">
                      <Select value={bulkAction} onValueChange={setBulkAction}>
                        <SelectTrigger className="w-36">
                          <SelectValue placeholder="Bulk Action" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="delete">Delete</SelectItem>
                        </SelectContent>
                      </Select>
                      <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={handleBulkAction}
                      >
                        Apply
                      </Button>
                    </div>
                  </div>
                  <div className="overflow-x-auto">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead className="w-12"></TableHead>
                          <TableHead>Date</TableHead>
                          <TableHead>Total</TableHead>
                          <TableHead>Present</TableHead>
                          <TableHead>Absent</TableHead>
                          <TableHead>Leave</TableHead>
                          <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {attendances.map((attendance) => (
                          <TableRow key={attendance.date}>
                            <TableCell>
                              <Checkbox
                                checked={selectedAttendances.includes(attendance.date)}
                                onCheckedChange={() => toggleSelectAttendance(attendance.date)}
                              />
                            </TableCell>
                            <TableCell>{formatDate(attendance.date)}</TableCell>
                            <TableCell>{attendance.total_attendance}</TableCell>
                            <TableCell>{attendance.present}</TableCell>
                            <TableCell>{attendance.absent}</TableCell>
                            <TableCell>{attendance.leaves}</TableCell>
                            <TableCell className="text-right">
                              <div className="flex justify-end items-center space-x-1">
                                <Link href={route("attendance.show", attendance.date)}>
                                  <Button size="icon" variant="ghost" title="View">
                                    <EyeIcon className="h-4 w-4" />
                                  </Button>
                                </Link>
                                <Link href={route("attendance.edit", attendance.date)}>
                                  <Button size="icon" variant="ghost" title="Edit">
                                    <Edit className="h-4 w-4" />
                                  </Button>
                                </Link>
                                <Button 
                                  size="icon" 
                                  variant="ghost" 
                                  title="Delete"
                                  onClick={() => handleDeleteConfirm(attendance.date)}
                                >
                                  <Trash className="h-4 w-4" />
                                </Button>
                              </div>
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  </div>
                  <div className="p-3 border-t flex flex-col sm:flex-row justify-between items-center">
                    <div className="text-sm text-muted-foreground mb-4 sm:mb-0">
                      Showing {meta.from || 0} to {meta.to || 0} of {meta.total || 0} entries
                    </div>
                    <div className="flex items-center space-x-6">
                      <div className="flex items-center space-x-2">
                        <span className="text-sm">Rows per page:</span>
                        <Select
                          value={perPage.toString()}
                          onValueChange={handlePerPageChange}
                        >
                          <SelectTrigger className="w-16 h-8">
                            <SelectValue placeholder={perPage} />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="10">10</SelectItem>
                            <SelectItem value="25">25</SelectItem>
                            <SelectItem value="50">50</SelectItem>
                            <SelectItem value="100">100</SelectItem>
                          </SelectContent>
                        </Select>
                      </div>
                      <div className="flex items-center space-x-1">
                        <Button
                          size="sm"
                          variant="outline"
                          disabled={currentPage === 1}
                          onClick={() => handlePageChange(currentPage - 1)}
                        >
                          Previous
                        </Button>
                        {renderPageNumbers()}
                        <Button
                          size="sm"
                          variant="outline"
                          disabled={currentPage === meta.last_page}
                          onClick={() => handlePageChange(currentPage + 1)}
                        >
                          Next
                        </Button>
                      </div>
                    </div>
                  </div>
                </>
              ) : (
                <div className="p-8 text-center">
                  <p className="text-muted-foreground mb-4">No attendance records found</p>
                </div>
              )}
            </div>
          </div>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
