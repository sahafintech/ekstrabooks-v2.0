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
  DialogTrigger 
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
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";

// Delete Confirmation Modal Component
const DeleteConfirmationModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm} className="p-6">
      <h2 className="text-lg font-medium">
        Are you sure you want to delete this holiday?
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
        Are you sure you want to delete {count} selected holiday{count !== 1 ? 's' : ''}?
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

// Weekend Selection Modal Component
const WeekendSelectionModal = ({ show, onClose, onConfirm, processing, selectedWeekends, setSelectedWeekends }) => {
  const weekdays = [
    { value: "sunday", label: "Sunday" },
    { value: "monday", label: "Monday" },
    { value: "tuesday", label: "Tuesday" },
    { value: "wednesday", label: "Wednesday" },
    { value: "thursday", label: "Thursday" },
    { value: "friday", label: "Friday" },
    { value: "saturday", label: "Saturday" }
  ];

  const toggleWeekend = (weekday) => {
    if (selectedWeekends.includes(weekday)) {
      setSelectedWeekends(selectedWeekends.filter(day => day !== weekday));
    } else {
      setSelectedWeekends([...selectedWeekends, weekday]);
    }
  };

  return (
    <Modal show={show} onClose={onClose}>
      <form onSubmit={onConfirm} className="p-6">
        <h2 className="text-lg font-medium mb-4">
          Select Weekend Days
        </h2>
        <div className="space-y-3">
          {weekdays.map((day) => (
            <div key={day.value} className="flex items-center gap-2">
              <Checkbox
                id={`weekend-${day.value}`}
                checked={selectedWeekends.includes(day.value)}
                onCheckedChange={() => toggleWeekend(day.value)}
              />
              <Label htmlFor={`weekend-${day.value}`}>{day.label}</Label>
            </div>
          ))}
        </div>
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
            disabled={processing}
          >
            Save Weekend Days
          </Button>
        </div>
      </form>
    </Modal>
  );
};

export default function List({ holidays = [], meta = {}, filters = {} }) {
  const { flash = {} } = usePage().props;
  const { toast } = useToast();
  const [selectedHolidays, setSelectedHolidays] = useState([]);
  const [isAllSelected, setIsAllSelected] = useState(false);
  const [search, setSearch] = useState(filters.search || "");
  const [perPage, setPerPage] = useState(meta.per_page || 10);
  const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
  const [bulkAction, setBulkAction] = useState("");
  const [dateFilter, setDateFilter] = useState(filters.date || "");
  
  // Form state for Create/Edit dialogs
  const [isCreateDialogOpen, setIsCreateDialogOpen] = useState(false);
  const [isEditDialogOpen, setIsEditDialogOpen] = useState(false);
  const [editingHoliday, setEditingHoliday] = useState(null);
  const [form, setForm] = useState({
    title: "",
    date: "",
    details: ""
  });

  // Form errors
  const [errors, setErrors] = useState({});

  // Delete confirmation modal states
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showBulkDeleteModal, setShowBulkDeleteModal] = useState(false);
  const [holidayToDelete, setHolidayToDelete] = useState(null);
  const [isProcessing, setIsProcessing] = useState(false);
  // Weekend selection modal state
  const [showWeekendModal, setShowWeekendModal] = useState(false);
  const [selectedWeekends, setSelectedWeekends] = useState([]);

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

  // Fetch weekend days when component mounts
  useEffect(() => {
    const fetchWeekendDays = async () => {
      try {
        const response = await fetch(route('holidays.weekends'));
        const data = await response.json();
        if (data && data.weekends) {
          setSelectedWeekends(Array.isArray(data.weekends) ? data.weekends : []);
        }
      } catch (error) {
        console.error("Error fetching weekend days:", error);
      }
    };

    fetchWeekendDays();
  }, []);

  const toggleSelectAll = () => {
    if (isAllSelected) {
      setSelectedHolidays([]);
    } else {
      setSelectedHolidays(holidays.map((holiday) => holiday.id));
    }
    setIsAllSelected(!isAllSelected);
  };

  const toggleSelectHoliday = (id) => {
    if (selectedHolidays.includes(id)) {
      setSelectedHolidays(selectedHolidays.filter((holidayId) => holidayId !== id));
      setIsAllSelected(false);
    } else {
      setSelectedHolidays([...selectedHolidays, id]);
      if (selectedHolidays.length + 1 === holidays.length) {
        setIsAllSelected(true);
      }
    }
  };

  // Weekend selection handler
  const handleWeekendSubmit = (e) => {
    e.preventDefault();
    setIsProcessing(true);

    router.post(route("holidays.weekends"), {
      weekends: selectedWeekends
    }, {
      onSuccess: () => {
        setShowWeekendModal(false);
        setIsProcessing(false);
        toast({
          title: "Success",
          description: "Weekend days updated successfully",
        });
      },
      onError: (errors) => {
        setIsProcessing(false);
        toast({
          variant: "destructive",
          title: "Error",
          description: "Failed to update weekend days",
        });
      }
    });
  };

  const handleSearch = (e) => {
    e.preventDefault();
    router.get(
      route("holidays.index"),
      { search, date: dateFilter, page: 1, per_page: perPage },
      { preserveState: true }
    );
  };

  const handleDateFilterChange = (date) => {
    setDateFilter(date ? format(date, "yyyy-MM-dd") : "");
    router.get(
      route("holidays.index"),
      { search, date: date ? format(date, "yyyy-MM-dd") : "", page: 1, per_page: perPage },
      { preserveState: true }
    );
  };

  const handlePerPageChange = (value) => {
    setPerPage(value);
    router.get(
      route("holidays.index"),
      { search, date: dateFilter, page: 1, per_page: value },
      { preserveState: true }
    );
  };

  const handlePageChange = (page) => {
    setCurrentPage(page);
    router.get(
      route("holidays.index"),
      { search, date: dateFilter, page, per_page: perPage },
      { preserveState: true }
    );
  };

  const handleBulkAction = () => {
    if (bulkAction === "") return;

    if (selectedHolidays.length === 0) {
      toast({
        variant: "destructive",
        title: "Error",
        description: "Please select at least one holiday",
      });
      return;
    }

    if (bulkAction === "delete") {
      setShowBulkDeleteModal(true);
    }
  };

  const handleDeleteConfirm = (id) => {
    setHolidayToDelete(id);
    setShowDeleteModal(true);
  };

  const handleDelete = (e) => {
    e.preventDefault();
    setIsProcessing(true);
    
    router.delete(route("holidays.destroy", holidayToDelete), {
      preserveState: true,
      onSuccess: () => {
        setShowDeleteModal(false);
        setHolidayToDelete(null);
        setIsProcessing(false);
      },
      onError: () => {
        setIsProcessing(false);
      }
    });
  };

  const handleBulkDelete = (e) => {
    e.preventDefault();
    setIsProcessing(true);

    router.delete(route("holidays.bulk-destroy"), {
      data: { ids: selectedHolidays },
      preserveState: true,
      onSuccess: () => {
        setShowBulkDeleteModal(false);
        setSelectedHolidays([]);
        setIsAllSelected(false);
        setIsProcessing(false);
        setBulkAction("");
      },
      onError: () => {
        setIsProcessing(false);
      }
    });
  };

  // Create form handlers
  const openCreateDialog = () => {
    setForm({
      title: "",
      date: "",
      details: ""
    });
    setErrors({});
    setIsCreateDialogOpen(true);
  };

  const handleCreateSubmit = (e) => {
    e.preventDefault();

    router.post(route("holidays.store"), form, {
      preserveState: true,
      onSuccess: () => {
        setIsCreateDialogOpen(false);
      },
      onError: (errors) => {
        setErrors(errors);
      }
    });
  };

  // Edit form handlers
  const openEditDialog = (holiday) => {
    setEditingHoliday(holiday);
    setForm({
      title: holiday.title,
      date: holiday.date,
      details: holiday.details || ""
    });
    setErrors({});
    setIsEditDialogOpen(true);
  };

  const handleEditSubmit = (e) => {
    e.preventDefault();

    router.put(route("holidays.update", editingHoliday.id), form, {
      preserveState: true,
      onSuccess: () => {
        setIsEditDialogOpen(false);
        setEditingHoliday(null);
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

  const formatDate = (dateString) => {
    if (!dateString) return "-";
    try {
      return format(new Date(dateString), "PPP");
    } catch (error) {
      return dateString;
    }
  };

  // Function to safely check if a date string is valid for display
  const isValidDate = (dateString) => {
    if (!dateString) return false;
    try {
      const date = new Date(dateString);
      return !isNaN(date.getTime());
    } catch (error) {
      return false;
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
      <Head title="Holiday List" />
      <Toaster />
      <SidebarInset>
        <div className="main-content">
          <PageHeader
            page="Holiday"
            subpage="List"
            url="holidays.index"
          />
          <div className="p-4">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
              <div className="flex flex-col md:flex-row gap-4">
                <Button onClick={openCreateDialog}>
                  <Plus className="w-4 h-4 mr-2" />
                  Add Holiday
                </Button>
                <Button onClick={() => setShowWeekendModal(true)} variant="secondary">
                  Weekend
                </Button>
              </div>
              <div className="flex flex-col md:flex-row gap-4 md:items-center">
                <form onSubmit={handleSearch} className="flex flex-col md:flex-row gap-2">
                  <Input
                    placeholder="Search holidays..."
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
                  disabled={!bulkAction || selectedHolidays.length === 0}
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
                    <TableHead>Title</TableHead>
                    <TableHead>Date</TableHead>
                    <TableHead>Details</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {holidays.length > 0 ? (
                    holidays.map((holiday) => (
                      <TableRow key={holiday.id}>
                        <TableCell>
                          <Checkbox
                            checked={selectedHolidays.includes(holiday.id)}
                            onCheckedChange={() => toggleSelectHoliday(holiday.id)}
                          />
                        </TableCell>
                        <TableCell>{holiday.id}</TableCell>
                        <TableCell className="font-medium">{holiday.title}</TableCell>
                        <TableCell>{isValidDate(holiday.date) ? formatDate(holiday.date) : "-"}</TableCell>
                        <TableCell>{holiday.details || "-"}</TableCell>
                        <TableCell className="text-right">
                          <TableActions
                            actions={[
                              {
                                label: "Edit",
                                icon: <Edit className="h-4 w-4" />,
                                onClick: () => openEditDialog(holiday),
                              },
                              {
                                label: "Delete",
                                icon: <Trash className="h-4 w-4" />,
                                onClick: () => handleDeleteConfirm(holiday.id),
                                destructive: true,
                              },
                            ]}
                          />
                        </TableCell>
                      </TableRow>
                    ))
                  ) : (
                    <TableRow>
                      <TableCell colSpan={6} className="h-24 text-center">
                        No holidays found.
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

          {/* Create Dialog */}
          <Dialog open={isCreateDialogOpen} onOpenChange={setIsCreateDialogOpen}>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Add Holiday</DialogTitle>
              </DialogHeader>
              <form onSubmit={handleCreateSubmit}>
                <div className="grid gap-4 py-4">
                  <div className="grid gap-2">
                    <Label htmlFor="title">Title</Label>
                    <Input
                      id="title"
                      name="title"
                      value={form.title}
                      onChange={handleInputChange}
                      className={errors.title ? "border-red-500" : ""}
                    />
                    {errors.title && (
                      <p className="text-red-500 text-sm">{errors.title}</p>
                    )}
                  </div>
                  <div className="grid gap-2">
                    <Label htmlFor="date">Date</Label>
                    <Popover>
                      <PopoverTrigger asChild>
                        <Button
                          variant={"outline"}
                          className={cn(
                            "w-full justify-start text-left font-normal",
                            !form.date && "text-muted-foreground"
                          )}
                        >
                          <CalendarIcon className="mr-2 h-4 w-4" />
                          {form.date && isValidDate(form.date) ? (
                            format(new Date(form.date), "PPP")
                          ) : (
                            <span>Select date</span>
                          )}
                        </Button>
                      </PopoverTrigger>
                      <PopoverContent className="w-auto p-0" align="start">
                        <Calendar
                          mode="single"
                          selected={form.date && isValidDate(form.date) ? new Date(form.date) : undefined}
                          onSelect={(date) => setForm({ ...form, date: date ? format(date, "yyyy-MM-dd") : "" })}
                          initialFocus
                        />
                      </PopoverContent>
                    </Popover>
                    {errors.date && (
                      <p className="text-red-500 text-sm">{errors.date}</p>
                    )}
                  </div>
                  <div className="grid gap-2">
                    <Label htmlFor="details">Details</Label>
                    <Input
                      id="details"
                      name="details"
                      value={form.details}
                      onChange={handleInputChange}
                      className={errors.details ? "border-red-500" : ""}
                    />
                    {errors.details && (
                      <p className="text-red-500 text-sm">{errors.details}</p>
                    )}
                  </div>
                </div>
                <DialogFooter>
                  <Button type="button" variant="outline" onClick={() => setIsCreateDialogOpen(false)}>
                    Cancel
                  </Button>
                  <Button type="submit">Save</Button>
                </DialogFooter>
              </form>
            </DialogContent>
          </Dialog>

          {/* Edit Dialog */}
          <Dialog open={isEditDialogOpen} onOpenChange={setIsEditDialogOpen}>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Edit Holiday</DialogTitle>
              </DialogHeader>
              <form onSubmit={handleEditSubmit}>
                <div className="grid gap-4 py-4">
                  <div className="grid gap-2">
                    <Label htmlFor="edit-title">Title</Label>
                    <Input
                      id="edit-title"
                      name="title"
                      value={form.title}
                      onChange={handleInputChange}
                      className={errors.title ? "border-red-500" : ""}
                    />
                    {errors.title && (
                      <p className="text-red-500 text-sm">{errors.title}</p>
                    )}
                  </div>
                  <div className="grid gap-2">
                    <Label htmlFor="edit-date">Date</Label>
                    <Popover>
                      <PopoverTrigger asChild>
                        <Button
                          variant={"outline"}
                          className={cn(
                            "w-full justify-start text-left font-normal",
                            !form.date && "text-muted-foreground"
                          )}
                        >
                          <CalendarIcon className="mr-2 h-4 w-4" />
                          {form.date && isValidDate(form.date) ? (
                            format(new Date(form.date), "PPP")
                          ) : (
                            <span>Select date</span>
                          )}
                        </Button>
                      </PopoverTrigger>
                      <PopoverContent className="w-auto p-0" align="start">
                        <Calendar
                          mode="single"
                          selected={form.date && isValidDate(form.date) ? new Date(form.date) : undefined}
                          onSelect={(date) => setForm({ ...form, date: date ? format(date, "yyyy-MM-dd") : "" })}
                          initialFocus
                        />
                      </PopoverContent>
                    </Popover>
                    {errors.date && (
                      <p className="text-red-500 text-sm">{errors.date}</p>
                    )}
                  </div>
                  <div className="grid gap-2">
                    <Label htmlFor="edit-details">Details</Label>
                    <Input
                      id="edit-details"
                      name="details"
                      value={form.details}
                      onChange={handleInputChange}
                      className={errors.details ? "border-red-500" : ""}
                    />
                    {errors.details && (
                      <p className="text-red-500 text-sm">{errors.details}</p>
                    )}
                  </div>
                </div>
                <DialogFooter>
                  <Button type="button" variant="outline" onClick={() => setIsEditDialogOpen(false)}>
                    Cancel
                  </Button>
                  <Button type="submit">Update</Button>
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
            onConfirm={handleBulkDelete}
            processing={isProcessing}
            count={selectedHolidays.length}
          />

          {/* Weekend Selection Modal */}
          <WeekendSelectionModal
            show={showWeekendModal}
            onClose={() => setShowWeekendModal(false)}
            onConfirm={handleWeekendSubmit}
            processing={false}
            selectedWeekends={selectedWeekends}
            setSelectedWeekends={setSelectedWeekends}
          />
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
