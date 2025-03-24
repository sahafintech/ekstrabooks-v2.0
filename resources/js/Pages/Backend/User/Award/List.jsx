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
import { Plus, Edit, Trash, Search, DollarSign, CalendarIcon } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";
import { format } from "date-fns";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { Textarea } from "@/Components/ui/textarea";
import { cn } from "@/lib/utils";

// Delete Confirmation Modal Component
const DeleteConfirmationModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm} className="p-6">
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
    <form onSubmit={onConfirm} className="p-6">
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

export default function List({ awards = [], meta = {}, filters = {}, employees = [] }) {
  const { flash = {} } = usePage().props;
  const { toast } = useToast();
  const [selectedAwards, setSelectedAwards] = useState([]);
  const [isAllSelected, setIsAllSelected] = useState(false);
  const [search, setSearch] = useState(filters.search || "");
  const [perPage, setPerPage] = useState(meta.per_page || 10);
  const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
  const [bulkAction, setBulkAction] = useState("");
  const [dateFilter, setDateFilter] = useState(filters.date || "");
  
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
    router.get(
      route("awards.index"),
      { search, date: dateFilter, page: 1, per_page: perPage },
      { preserveState: true }
    );
  };

  const handleDateFilterChange = (date) => {
    setDateFilter(date ? format(date, "yyyy-MM-dd") : "");
    router.get(
      route("awards.index"),
      { search, date: date ? format(date, "yyyy-MM-dd") : "", page: 1, per_page: perPage },
      { preserveState: true }
    );
  };

  const handlePerPageChange = (value) => {
    setPerPage(value);
    router.get(
      route("awards.index"),
      { search, date: dateFilter, page: 1, per_page: value },
      { preserveState: true }
    );
  };

  const handlePageChange = (page) => {
    setCurrentPage(page);
    router.get(
      route("awards.index"),
      { search, date: dateFilter, page, per_page: perPage },
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
    
    router.post(route("awards.bulk_delete"), {
      ids: selectedAwards
    }, {
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
    });
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
      },
      onError: (errors) => {
        setErrors(errors);
      }
    });
  };

  // Edit form handlers
  const openEditDialog = (award) => {
    let awardDateObj = null;
    
    // Safe date parsing
    try {
      if (award.award_date) {
        const [year, month, day] = award.award_date.split('-').map(Number);
        // Check if valid date parts before creating Date object
        if (!isNaN(year) && !isNaN(month) && !isNaN(day)) {
          awardDateObj = new Date(year, month - 1, day); // month is 0-indexed in JS
          
          // Verify the date is valid
          const isValidDate = awardDateObj instanceof Date && !isNaN(awardDateObj);
          if (!isValidDate) {
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

    router.put(route("awards.update", editingAward.id), form, {
      preserveState: true,
      onSuccess: () => {
        setIsEditDialogOpen(false);
        setEditingAward(null);
      },
      onError: (errors) => {
        setErrors(errors);
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

  const formatDate = (dateString) => {
    if (!dateString) return "-";
    try {
      return format(new Date(dateString), "PPP");
    } catch (error) {
      return dateString;
    }
  };

  return (
    <AuthenticatedLayout>
      <Head title="Employee Awards" />
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
              <div className="flex flex-col md:flex-row gap-4">
                <Button onClick={openCreateDialog}>
                  <Plus className="w-4 h-4 mr-2" />
                  Add Award
                </Button>
              </div>
              <div className="flex flex-col md:flex-row gap-4 md:items-center">
                <form onSubmit={handleSearch} className="flex flex-col md:flex-row gap-2">
                  <Input
                    placeholder="Search awards..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
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
                  <Button type="submit">
                    <Search className="w-4 h-4 mr-2" />
                    Search
                  </Button>
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
                    <TableHead className="w-[50px]">ID</TableHead>
                    <TableHead>Employee</TableHead>
                    <TableHead>Award Title</TableHead>
                    <TableHead>Gift</TableHead>
                    <TableHead>Cash Price</TableHead>
                    <TableHead>Month</TableHead>
                    <TableHead>Year</TableHead>
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
                        <TableCell>{award.award}</TableCell>
                        <TableCell>{award.gift || '-'}</TableCell>
                        <TableCell>{award.cash_price}</TableCell>
                        <TableCell>{award.month}</TableCell>
                        <TableCell>{award.year}</TableCell>
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
                      <TableCell colSpan={9} className="h-24 text-center">
                        No award records found.
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
                      <Popover>
                        <PopoverTrigger asChild>
                          <Button
                            variant="outline"
                            className={cn(
                              "w-full justify-start text-left font-normal",
                              !awardDate && "text-muted-foreground"
                            )}
                          >
                            <CalendarIcon className="mr-2 h-4 w-4" />
                            {awardDate ? format(awardDate, "PPP") : <span>Pick a date</span>}
                          </Button>
                        </PopoverTrigger>
                        <PopoverContent className="w-auto p-0">
                          <Calendar
                            mode="single"
                            selected={awardDate}
                            onSelect={handleDateChange}
                            initialFocus
                          />
                        </PopoverContent>
                      </Popover>
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
                  >
                    Cancel
                  </Button>
                  <Button type="submit">Save Award</Button>
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
                      <Popover>
                        <PopoverTrigger asChild>
                          <Button
                            variant="outline"
                            className={cn(
                              "w-full justify-start text-left font-normal",
                              !awardDate && "text-muted-foreground"
                            )}
                          >
                            <CalendarIcon className="mr-2 h-4 w-4" />
                            {awardDate ? format(awardDate, "PPP") : <span>Pick a date</span>}
                          </Button>
                        </PopoverTrigger>
                        <PopoverContent className="w-auto p-0">
                          <Calendar
                            mode="single"
                            selected={awardDate}
                            onSelect={handleDateChange}
                            initialFocus
                          />
                        </PopoverContent>
                      </Popover>
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
                  >
                    Cancel
                  </Button>
                  <Button type="submit">Update Award</Button>
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
