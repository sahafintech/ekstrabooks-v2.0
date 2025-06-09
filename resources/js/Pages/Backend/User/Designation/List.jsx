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
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Textarea } from "@/Components/ui/textarea";
import { Plus, Edit, Trash, Search, ChevronUp, ChevronDown } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";

// Delete Confirmation Modal Component
const DeleteConfirmationModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete this designation?
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
        Are you sure you want to delete {count} selected designation{count !== 1 ? 's' : ''}?
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

// Create Modal Component
const CreateModal = ({ show, onClose, onSubmit, form, errors, departments, handleInputChange, handleDepartmentChange, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onSubmit}>
      <h2 className="text-lg font-medium mb-4">Add Designation</h2>
      <div className="grid gap-4">
        <div className="grid gap-2">
          <Label htmlFor="name">Name <span className="text-red-500">*</span></Label>
          <Input
            id="name"
            name="name"
            value={form.name}
            onChange={handleInputChange}
            placeholder="Enter designation name"
          />
          {errors.name && (
            <p className="text-sm text-red-500">{errors.name}</p>
          )}
        </div>
        <div className="grid gap-2">
          <Label htmlFor="department_id">Department <span className="text-red-500">*</span></Label>
          <SearchableCombobox
            options={
              departments.map((department) => ({
                id: department.id,
                name: department.name,
              }))
            }
            value={form.department_id}
            onChange={handleDepartmentChange}
            placeholder="Select department"
          />
          {errors.department_id && (
            <p className="text-sm text-red-500">{errors.department_id}</p>
          )}
        </div>
        <div className="grid gap-2">
          <Label htmlFor="descriptions">Description</Label>
          <Textarea
            id="descriptions"
            name="descriptions"
            value={form.descriptions}
            onChange={handleInputChange}
            placeholder="Enter designation description"
            rows={3}
          />
          {errors.descriptions && (
            <p className="text-sm text-red-500">{errors.descriptions}</p>
          )}
        </div>
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
        <Button type="submit" disabled={processing}>Save</Button>
      </div>
    </form>
  </Modal>
);

// Edit Modal Component
const EditModal = ({ show, onClose, onSubmit, form, errors, departments, handleInputChange, handleDepartmentChange, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onSubmit}>
      <h2 className="text-lg font-medium mb-4">Edit Designation</h2>
      <div className="grid gap-4">
        <div className="grid gap-2">
          <Label htmlFor="name">Name <span className="text-red-500">*</span></Label>
          <Input
            id="name"
            name="name"
            value={form.name}
            onChange={handleInputChange}
            placeholder="Enter designation name"
          />
          {errors.name && (
            <p className="text-sm text-red-500">{errors.name}</p>
          )}
        </div>
        <div className="grid gap-2">
          <Label htmlFor="department_id">Department <span className="text-red-500">*</span></Label>
          <SearchableCombobox
            options={
              departments.map((department) => ({
                id: department.id,
                name: department.name,
              }))
            }
            placeholder="Select department"
            value={form.department_id}
            onChange={handleDepartmentChange}
          />
          {errors.department_id && (
            <p className="text-sm text-red-500">{errors.department_id}</p>
          )}
        </div>
        <div className="grid gap-2">
          <Label htmlFor="descriptions">Description</Label>
          <Textarea
            id="descriptions"
            name="descriptions"
            value={form.descriptions}
            onChange={handleInputChange}
            placeholder="Enter designation description"
            rows={3}
          />
          {errors.descriptions && (
            <p className="text-sm text-red-500">{errors.descriptions}</p>
          )}
        </div>
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
        <Button type="submit" disabled={processing}>Update</Button>
      </div>
    </form>
  </Modal>
);

export default function List({ designations = [], departments = [], meta = {}, filters = {} }) {
  const { flash = {} } = usePage().props;
  const { toast } = useToast();
  const [selectedDesignations, setSelectedDesignations] = useState([]);
  const [isAllSelected, setIsAllSelected] = useState(false);
  const [search, setSearch] = useState(filters.search || "");
  const [perPage, setPerPage] = useState(meta.per_page || 10);
  const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
  const [bulkAction, setBulkAction] = useState("");
  const [sorting, setSorting] = useState(filters.sorting || { column: "id", direction: "desc" });

  // Form state for Create/Edit dialogs
  const [isCreateDialogOpen, setIsCreateDialogOpen] = useState(false);
  const [isEditDialogOpen, setIsEditDialogOpen] = useState(false);
  const [editingDesignation, setEditingDesignation] = useState(null);
  const [form, setForm] = useState({
    name: "",
    descriptions: "",
    department_id: ""
  });

  // Form errors
  const [errors, setErrors] = useState({});

  // Delete confirmation modal states
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showBulkDeleteModal, setShowBulkDeleteModal] = useState(false);
  const [designationToDelete, setDesignationToDelete] = useState(null);
  const [isProcessing, setIsProcessing] = useState(false);

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
      setSelectedDesignations([]);
    } else {
      setSelectedDesignations(designations.map((designation) => designation.id));
    }
    setIsAllSelected(!isAllSelected);
  };

  const toggleSelectDesignation = (id) => {
    if (selectedDesignations.includes(id)) {
      setSelectedDesignations(selectedDesignations.filter((designationId) => designationId !== id));
      setIsAllSelected(false);
    } else {
      setSelectedDesignations([...selectedDesignations, id]);
      if (selectedDesignations.length + 1 === designations.length) {
        setIsAllSelected(true);
      }
    }
  };

  const handleSearch = (e) => {
    e.preventDefault();
    const value = e.target.value;
    setSearch(value);

    router.get(
      route("designations.index"),
      { search: value, page: 1, per_page: perPage },
      { preserveState: true }
    );
  };

  const handlePerPageChange = (value) => {
    setPerPage(value);
    router.get(
      route("designations.index"),
      { search, page: 1, per_page: value },
      { preserveState: true }
    );
  };

  const handlePageChange = (page) => {
    setCurrentPage(page);
    router.get(
      route("designations.index"),
      { search, page, per_page: perPage },
      { preserveState: true }
    );
  };

  const handleBulkAction = () => {
    if (bulkAction === "") return;

    if (selectedDesignations.length === 0) {
      toast({
        variant: "destructive",
        title: "Error",
        description: "Please select at least one designation",
      });
      return;
    }

    if (bulkAction === "delete") {
      setShowBulkDeleteModal(true);
    }
  };

  const handleDeleteConfirm = (id) => {
    setDesignationToDelete(id);
    setShowDeleteModal(true);
  };

  const handleDelete = (e) => {
    e.preventDefault();
    setIsProcessing(true);

    router.delete(route("designations.destroy", designationToDelete), {
      preserveState: true,
      onSuccess: () => {
        setShowDeleteModal(false);
        setDesignationToDelete(null);
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

    router.post(route("designations.bulk_destroy"),
      {
        ids: selectedDesignations
      },
      {
        preserveState: true,
        onSuccess: () => {
          setSelectedDesignations([]);
          setIsAllSelected(false);
          setBulkAction("");
          setShowBulkDeleteModal(false);
          setIsProcessing(false);
        },
        onError: (errors) => {
          setIsProcessing(false);
        }
      }
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

  // Form input change handler
  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setForm({
      ...form,
      [name]: value
    });
  };

  // Department select change handler
  const handleDepartmentChange = (value) => {
    setForm({
      ...form,
      department_id: value
    });
  };

  // Create form handlers
  const openCreateDialog = () => {
    setForm({
      name: "",
      descriptions: "",
      department_id: ""
    });
    setErrors({});
    setIsCreateDialogOpen(true);
  };

  const handleCreateSubmit = (e) => {
    e.preventDefault();
    setIsProcessing(true);

    router.post(route("designations.store"), form, {
      preserveState: true,
      onSuccess: () => {
        setIsCreateDialogOpen(false);
        setForm({
          name: "",
          descriptions: "",
          department_id: ""
        });
        setIsProcessing(false);
      },
      onError: (errors) => {
        setErrors(errors);
        setIsProcessing(false);
      }
    });
  };

  // Edit form handlers
  const openEditDialog = (designation) => {
    setEditingDesignation(designation);
    setForm({
      name: designation.name,
      descriptions: designation.descriptions || "",
      department_id: designation.department_id.toString()
    });
    setErrors({});
    setIsEditDialogOpen(true);
  };

  const handleEditSubmit = (e) => {
    e.preventDefault();
    setIsProcessing(true);

    router.put(route("designations.update", editingDesignation.id), form, {
      preserveState: true,
      onSuccess: () => {
        setIsEditDialogOpen(false);
        setEditingDesignation(null);
        setIsProcessing(false);
      },
      onError: (errors) => {
        setErrors(errors);
        setIsProcessing(false);
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
      route("designations.index"),
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

  return (
    <AuthenticatedLayout>
      <Toaster />
      <SidebarInset>
        <div className="main-content">
          <PageHeader
            page="Designation Management"
            subpage="List"
            url="designations.index"
          />
          <div className="p-4">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
              <div className="flex flex-col md:flex-row gap-4">
                <Button onClick={openCreateDialog}>
                  <Plus className="w-4 h-4 mr-2" />
                  Add Designation
                </Button>
              </div>
              <div className="flex flex-col md:flex-row gap-4 md:items-center">
                <Input
                  placeholder="Search designations..."
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
                    <TableHead className="w-[80px] cursor-pointer" onClick={() => handleSort("id")}>
                      ID {renderSortIcon("id")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("name")}>
                      Name {renderSortIcon("name")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("department.name")}>
                      Department {renderSortIcon("department.name")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("descriptions")}>
                      Description {renderSortIcon("descriptions")}
                    </TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {designations.length > 0 ? (
                    designations.map((designation) => (
                      <TableRow key={designation.id}>
                        <TableCell>
                          <Checkbox
                            checked={selectedDesignations.includes(designation.id)}
                            onCheckedChange={() => toggleSelectDesignation(designation.id)}
                          />
                        </TableCell>
                        <TableCell>{designation.id}</TableCell>
                        <TableCell>{designation.name}</TableCell>
                        <TableCell>{designation.department?.name || "-"}</TableCell>
                        <TableCell>{designation.descriptions || "-"}</TableCell>
                        <TableCell className="text-right">
                          <TableActions
                            actions={[
                              {
                                label: "Edit",
                                icon: <Edit className="h-4 w-4" />,
                                onClick: () => openEditDialog(designation),
                              },
                              {
                                label: "Delete",
                                icon: <Trash className="h-4 w-4" />,
                                onClick: () => handleDeleteConfirm(designation.id),
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
                        No designations found.
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

          {/* Create Modal */}
          <CreateModal
            show={isCreateDialogOpen}
            onClose={() => setIsCreateDialogOpen(false)}
            onSubmit={handleCreateSubmit}
            form={form}
            errors={errors}
            departments={departments}
            handleInputChange={handleInputChange}
            handleDepartmentChange={handleDepartmentChange}
            processing={isProcessing}
          />

          {/* Edit Modal */}
          <EditModal
            show={isEditDialogOpen}
            onClose={() => setIsEditDialogOpen(false)}
            onSubmit={handleEditSubmit}
            form={form}
            errors={errors}
            departments={departments}
            handleInputChange={handleInputChange}
            handleDepartmentChange={handleDepartmentChange}
            processing={isProcessing}
          />

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
            count={selectedDesignations.length}
          />
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
