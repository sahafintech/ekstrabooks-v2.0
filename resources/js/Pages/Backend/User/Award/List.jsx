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
import { Plus, Edit, Trash, Search, DollarSign, CalendarIcon, ChevronUp, ChevronDown, Trash2 } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";
import { format } from "date-fns";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { Textarea } from "@/Components/ui/textarea";
import { cn } from "@/lib/utils";
import DateTimePicker from "@/Components/DateTimePicker";

// Delete Confirmation Modal Component
const DeleteConfirmationModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete this award?
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
        Are you sure you want to delete {count} selected award{count !== 1 ? 's' : ''}?
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

export default function List({ awards = [], meta = {}, filters = {}, employees = [], trashed_awards = 0 }) {
  const { flash = {} } = usePage().props;
  const { toast } = useToast();
  const [selectedAwards, setSelectedAwards] = useState([]);
  const [isAllSelected, setIsAllSelected] = useState(false);
  const [search, setSearch] = useState(filters.search || "");
  const [perPage, setPerPage] = useState(meta.per_page || 10);
  const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
  const [bulkAction, setBulkAction] = useState("");
  const [sorting, setSorting] = useState(filters.sorting || { column: "id", direction: "desc" });

  // Delete confirmation modal states
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showBulkDeleteModal, setShowBulkDeleteModal] = useState(false);
  const [awardToDelete, setAwardToDelete] = useState(null);
  const [isProcessing, setIsProcessing] = useState(false);

  // Create and Edit dialog states
  const [isCreateDialogOpen, setIsCreateDialogOpen] = useState(false);
  const [isEditDialogOpen, setIsEditDialogOpen] = useState(false);
  const [editingAward, setEditingAward] = useState(null);
  const [form, setForm] = useState({
    employee_id: "",
    award_date: "",
    award_name: "",
    award: "",
    details: ""
  });
  const [errors, setErrors] = useState({});
  const [awardDate, setAwardDate] = useState(null);
  const [isCreateProcessing, setIsCreateProcessing] = useState(false);
  const [isEditProcessing, setIsEditProcessing] = useState(false);

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
      setSelectedAwards([]);
    } else {
      setSelectedAwards(awards.map((award) => award.id));
    }
    setIsAllSelected(!isAllSelected);
  };

  const toggleSelectAward = (id) => {
    if (selectedAwards.includes(id)) {
      setSelectedAwards(selectedAwards.filter((awardId) => awardId !== id));
      setIsAllSelected(false);
    } else {
      setSelectedAwards([...selectedAwards, id]);
      if (selectedAwards.length + 1 === awards.length) {
        setIsAllSelected(true);
      }
    }
  };

  const handleSearch = (e) => {
    e.preventDefault();
    const value = e.target.value;
    setSearch(value);

    router.get(
      route("awards.index"),
      { search: value, page: 1, per_page: perPage, sorting },
      { preserveState: true }
    );
  };

  const handlePerPageChange = (value) => {
    setPerPage(value);
    router.get(
      route("awards.index"),
      { search, page: 1, per_page: value, sorting },
      { preserveState: true }
    );
  };

  const handlePageChange = (page) => {
    setCurrentPage(page);
    router.get(
      route("awards.index"),
      { search, page, per_page: perPage, sorting },
      { preserveState: true }
    );
  };

  const handleBulkAction = () => {
    if (bulkAction === "") return;

    if (selectedAwards.length === 0) {
      toast({
        variant: "destructive",
        title: "Error",
        description: "Please select at least one award record",
      });
      return;
    }

    if (bulkAction === "delete") {
      setShowBulkDeleteModal(true);
    }
  };

  const handleDeleteConfirm = (id) => {
    setAwardToDelete(id);
    setShowDeleteModal(true);
  };

  const handleDelete = (e) => {
    e.preventDefault();
    setIsProcessing(true);

    router.delete(route("awards.destroy", awardToDelete), {
      preserveState: true,
      onSuccess: () => {
        setShowDeleteModal(false);
        setAwardToDelete(null);
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

    router.post(route("awards.bulk_destroy"),
      {
        ids: selectedAwards
      },
      {
        preserveState: true,
        onSuccess: () => {
          setSelectedAwards([]);
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

  const handleSort = (column) => {
    let direction = "asc";
    if (sorting.column === column && sorting.direction === "asc") {
      direction = "desc";
    }
    setSorting({ column, direction });
    router.get(
      route("awards.index"),
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

  // Create form handlers
  const openCreateDialog = () => {
    setForm({
      employee_id: "",
      award_date: "",
      award_name: "",
      award: "",
      details: ""
    });
    setAwardDate(null);
    setErrors({});
    setIsCreateDialogOpen(true);
  };

  const handleCreateSubmit = (e) => {
    e.preventDefault();
    setIsCreateProcessing(true);

    router.post(route("awards.store"), form, {
      preserveState: true,
      onSuccess: () => {
        setIsCreateDialogOpen(false);
        setForm({
          employee_id: "",
          award_date: "",
          award_name: "",
          award: "",
          details: ""
        });
        setAwardDate(null);
        setIsCreateProcessing(false);
      },
      onError: (errors) => {
        setErrors(errors);
        setIsCreateProcessing(false);
      }
    });
  };

  // Edit form handlers
  const openEditDialog = (award) => {
    let awardDateObj = null;

    try {
      if (award.award_date) {
        const [year, month, day] = award.award_date.split('-').map(Number);
        if (!isNaN(year) && !isNaN(month) && !isNaN(day)) {
          awardDateObj = new Date(year, month - 1, day);
          if (!(awardDateObj instanceof Date && !isNaN(awardDateObj))) {
            awardDateObj = null;
          }
        }
      }
    } catch (error) {
      console.error("Error parsing date:", error);
      awardDateObj = null;
    }

    setEditingAward(award);
    setForm({
      employee_id: award.employee_id?.toString() || "",
      award_date: award.award_date || "",
      award_name: award.award_name || "",
      award: award.award || "",
      details: award.details || ""
    });
    setAwardDate(awardDateObj);
    setErrors({});
    setIsEditDialogOpen(true);
  };

  const handleEditSubmit = (e) => {
    e.preventDefault();
    setIsEditProcessing(true);

    router.put(route("awards.update", editingAward.id), form, {
      preserveState: true,
      onSuccess: () => {
        setIsEditDialogOpen(false);
        setEditingAward(null);
        setIsEditProcessing(false);
      },
      onError: (errors) => {
        setErrors(errors);
        setIsEditProcessing(false);
      }
    });
  };

  // Form input change handler
  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setForm({
      ...form,
      [name]: value
    });
  };

  // Handle date change
  const handleDateChange = (date) => {
    setAwardDate(date);
    setForm({
      ...form,
      award_date: date ? format(date, "yyyy-MM-dd") : ""
    });
  };

  // Handle employee selection
  const handleEmployeeChange = (employeeId) => {
    setForm({
      ...form,
      employee_id: employeeId
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

  return (
    <AuthenticatedLayout>
      <Toaster />
      <SidebarInset>
        <div className="main-content">
          <PageHeader
            page="Employee Awards"
            subpage="List"
            url="awards.index"
          />
          <div className="p-4">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
              <div className="flex flex-col md:flex-row gap-2">
                <Button onClick={openCreateDialog}>
                  <Plus className="w-4 h-4 mr-2" />
                  Add Award
                </Button>
                <Link href={route("awards.trash")}>
                    <Button variant="outline" className="relative">
                        <Trash2 className="h-8 w-8" />
                        {trashed_awards > 0 && (
                        <span className="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-6 w-6 flex items-center justify-center font-bold">
                            {trashed_awards}
                        </span>
                        )}
                    </Button>
                </Link>
              </div>
              <div className="flex flex-col md:flex-row gap-4 md:items-center">
                <Input
                  placeholder="Search awards..."
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
                  </SelectContent>
                </Select>
                <Button
                  onClick={handleBulkAction}
                  variant="outline"
                  disabled={!bulkAction || selectedAwards.length === 0}
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
                    <TableHead className="cursor-pointer" onClick={() => handleSort("award_name")}>
                      Award Title {renderSortIcon("award_name")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("award")}>
                      Award {renderSortIcon("award")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("award_date")}>
                      Date {renderSortIcon("award_date")}
                    </TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {awards.length > 0 ? (
                    awards.map((award) => (
                      <TableRow key={award.id}>
                        <TableCell>
                          <Checkbox
                            checked={selectedAwards.includes(award.id)}
                            onCheckedChange={() => toggleSelectAward(award.id)}
                          />
                        </TableCell>
                        <TableCell>{award.id}</TableCell>
                        <TableCell>{award.staff?.name || '-'}</TableCell>
                        <TableCell>{award.award_name}</TableCell>
                        <TableCell>{award.award}</TableCell>
                        <TableCell>{award.award_date}</TableCell>
                        <TableCell className="text-right">
                          <TableActions
                            actions={[
                              {
                                label: "Edit",
                                icon: <Edit className="h-4 w-4" />,
                                onClick: () => openEditDialog(award),
                              },
                              {
                                label: "Delete",
                                icon: <Trash className="h-4 w-4" />,
                                onClick: () => handleDeleteConfirm(award.id),
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
                        No award records found.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>

            {meta.last_page > 1 && (
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

          {/* Create Award Dialog */}
          <Dialog open={isCreateDialogOpen} onOpenChange={setIsCreateDialogOpen}>
            <DialogContent className="sm:max-w-[600px]">
              <DialogHeader>
                <DialogTitle>Add New Award</DialogTitle>
              </DialogHeader>
              <form onSubmit={handleCreateSubmit}>
                <div className="grid gap-4 py-4">
                  <div className="grid grid-cols-1 gap-4">
                    <div>
                      <Label htmlFor="employee_id" className="mb-2 block">
                        Employee <span className="text-red-500">*</span>
                      </Label>
                      <SearchableCombobox
                        options={employees}
                        value={form.employee_id}
                        onChange={handleEmployeeChange}
                        placeholder="Select an employee"
                        emptyMessage="No employees found."
                        className="w-full"
                      />
                      {errors.employee_id && (
                        <p className="text-red-500 text-sm mt-1">{errors.employee_id}</p>
                      )}
                    </div>

                    <div>
                      <Label htmlFor="award_date" className="mb-2 block">
                        Award Date <span className="text-red-500">*</span>
                      </Label>
                      <DateTimePicker
                        value={form.award_date}
                        onChange={(date) => setForm({ ...form, award_date: date })}
                        required
                      />
                      {errors.award_date && (
                        <p className="text-red-500 text-sm mt-1">{errors.award_date}</p>
                      )}
                    </div>

                    <div>
                      <Label htmlFor="award_name" className="mb-2 block">
                        Award Name <span className="text-red-500">*</span>
                      </Label>
                      <Input
                        id="award_name"
                        name="award_name"
                        value={form.award_name}
                        onChange={handleInputChange}
                        placeholder="Employee of the Month"
                      />
                      {errors.award_name && (
                        <p className="text-red-500 text-sm mt-1">{errors.award_name}</p>
                      )}
                    </div>

                    <div>
                      <Label htmlFor="award" className="mb-2 block">
                        Award <span className="text-red-500">*</span>
                      </Label>
                      <Input
                        id="award"
                        name="award"
                        value={form.award}
                        onChange={handleInputChange}
                        placeholder="Certificate & Cash Prize"
                      />
                      {errors.award && (
                        <p className="text-red-500 text-sm mt-1">{errors.award}</p>
                      )}
                    </div>

                    <div>
                      <Label htmlFor="details" className="mb-2 block">
                        Details
                      </Label>
                      <Textarea
                        id="details"
                        name="details"
                        value={form.details}
                        onChange={handleInputChange}
                        placeholder="Additional details about the award"
                        rows={3}
                      />
                    </div>
                  </div>
                </div>
                <DialogFooter>
                  <Button
                    type="button"
                    variant="secondary"
                    onClick={() => setIsCreateDialogOpen(false)}
                    disabled={isCreateProcessing}
                  >
                    Cancel
                  </Button>
                  <Button type="submit" disabled={isCreateProcessing}>
                    {isCreateProcessing ? "Saving..." : "Save Award"}
                  </Button>
                </DialogFooter>
              </form>
            </DialogContent>
          </Dialog>

          {/* Edit Award Dialog */}
          <Dialog open={isEditDialogOpen} onOpenChange={setIsEditDialogOpen}>
            <DialogContent className="sm:max-w-[600px]">
              <DialogHeader>
                <DialogTitle>Edit Award</DialogTitle>
              </DialogHeader>
              <form onSubmit={handleEditSubmit}>
                <div className="grid gap-4 py-4">
                  <div className="grid grid-cols-1 gap-4">
                    <div>
                      <Label htmlFor="employee_id" className="mb-2 block">
                        Employee <span className="text-red-500">*</span>
                      </Label>
                      <SearchableCombobox
                        options={employees}
                        value={form.employee_id}
                        onChange={handleEmployeeChange}
                        placeholder="Select an employee"
                        emptyMessage="No employees found."
                        className="w-full"
                      />
                      {errors.employee_id && (
                        <p className="text-red-500 text-sm mt-1">{errors.employee_id}</p>
                      )}
                    </div>

                    <div>
                      <Label htmlFor="award_date" className="mb-2 block">
                        Award Date <span className="text-red-500">*</span>
                      </Label>
                      <DateTimePicker
                        value={form.award_date}
                        onChange={(date) => setForm({ ...form, award_date: date })}
                        required
                      />
                      {errors.award_date && (
                        <p className="text-red-500 text-sm mt-1">{errors.award_date}</p>
                      )}
                    </div>

                    <div>
                      <Label htmlFor="award_name" className="mb-2 block">
                        Award Name <span className="text-red-500">*</span>
                      </Label>
                      <Input
                        id="award_name"
                        name="award_name"
                        value={form.award_name}
                        onChange={handleInputChange}
                        placeholder="Employee of the Month"
                      />
                      {errors.award_name && (
                        <p className="text-red-500 text-sm mt-1">{errors.award_name}</p>
                      )}
                    </div>

                    <div>
                      <Label htmlFor="award" className="mb-2 block">
                        Award <span className="text-red-500">*</span>
                      </Label>
                      <Input
                        id="award"
                        name="award"
                        value={form.award}
                        onChange={handleInputChange}
                        placeholder="Certificate & Cash Prize"
                      />
                      {errors.award && (
                        <p className="text-red-500 text-sm mt-1">{errors.award}</p>
                      )}
                    </div>

                    <div>
                      <Label htmlFor="details" className="mb-2 block">
                        Details
                      </Label>
                      <Textarea
                        id="details"
                        name="details"
                        value={form.details}
                        onChange={handleInputChange}
                        placeholder="Additional details about the award"
                        rows={3}
                      />
                    </div>
                  </div>
                </div>
                <DialogFooter>
                  <Button
                    type="button"
                    variant="secondary"
                    onClick={() => setIsEditDialogOpen(false)}
                    disabled={isEditProcessing}
                  >
                    Cancel
                  </Button>
                  <Button type="submit" disabled={isEditProcessing}>
                    {isEditProcessing ? "Updating..." : "Update Award"}
                  </Button>
                </DialogFooter>
              </form>
            </DialogContent>
          </Dialog>

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
            count={selectedAwards.length}
          />
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
