import React, { useState, useEffect } from "react";
import { router, usePage } from "@inertiajs/react";
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
import { Trash, ChevronUp, ChevronDown, RotateCcw } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";

// Delete Confirmation Modal Component
const DeleteMainCategoryModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete this main category?
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

// Restore Confirmation Modal Component
const RestoreMainCategoryModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to restore this main category?
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
          Restore
        </Button>
      </div>
    </form>
  </Modal>
);

// Bulk Delete Confirmation Modal Component
const DeleteAllMainCategoriesModal = ({ show, onClose, onConfirm, processing, count }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete {count} selected main main categories{count !== 1 ? 's' : ''}?
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

// Bulk Restore Confirmation Modal Component
const RestoreAllMainCategoriesModal = ({ show, onClose, onConfirm, processing, count }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to restore {count} selected main main categories{count !== 1 ? 's' : ''}?
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
          Restore Selected
        </Button>
      </div>
    </form>
  </Modal>
);


export default function List({ categories = [], meta = {}, filters = {} }) {
  const { flash = {} } = usePage().props;
  const { toast } = useToast();
  const [selectedMainCategories, setSelectedMainCategories] = useState([]);
  const [isAllSelected, setIsAllSelected] = useState(false);
  const [search, setSearch] = useState(filters.search || "");
  const [perPage, setPerPage] = useState(meta.per_page || 50);
  const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
  const [bulkAction, setBulkAction] = useState("");
  const [sorting, setSorting] = useState(filters.sorting || { column: "id", direction: "desc" });

  // Delete confirmation modal states
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
  const [mainCategoryToDelete, setMainCategoryToDelete] = useState(null);
  const [processing, setProcessing] = useState(false);
  const [showRestoreModal, setShowRestoreModal] = useState(false);
  const [mainCategoryToRestore, setMainCategoryToRestore] = useState(null);
  const [showRestoreAllModal, setShowRestoreAllModal] = useState(false);
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
      setSelectedMainCategories([]);
    } else {
      setSelectedMainCategories(categories.map((category) => category.id));
    }
    setIsAllSelected(!isAllSelected);
  };

  const toggleSelectMainCategory = (id) => {
    if (selectedMainCategories.includes(id)) {
      setSelectedMainCategories(selectedMainCategories.filter((categoryId) => categoryId !== id));
      setIsAllSelected(false);
    } else {
      setSelectedMainCategories([...selectedMainCategories, id]);
      if (selectedMainCategories.length + 1 === categories.length) {
        setIsAllSelected(true);
      }
    }
  };

  const handleSearch = (e) => {
    e.preventDefault();
    const value = e.target.value;
    setSearch(value);

    router.get(
      route("main_categories.trash"),
      { search: value, page: 1, per_page: perPage },
      { preserveState: true }
    );
  };


  const handlePerPageChange = (value) => {
    setPerPage(value);
    router.get(
      route("main_categories.trash"),
      { search, page: 1, per_page: value },
      { preserveState: true }
    );
  };

  const handlePageChange = (page) => {
    setCurrentPage(page);
    router.get(
      route("main_categories.trash"),
      { search, page, per_page: perPage },
      { preserveState: true }
    );
  };

  const handleBulkAction = () => {
    if (bulkAction === "") return;

    if (selectedMainCategories.length === 0) {
      toast({
        variant: "destructive",
        title: "Error",
        description: "Please select at least one category",
      });
      return;
    }

    if (bulkAction === "delete") {
      setShowDeleteAllModal(true);
    } else if (bulkAction === "restore") {
      setShowRestoreAllModal(true);
    }
  };

  const handleDeleteConfirm = (id) => {
    setMainCategoryToDelete(id);
    setShowDeleteModal(true);
  };

  const handleRestoreConfirm = (id) => {
    setMainCategoryToRestore(id);
    setShowRestoreModal(true);
  };

  const handleRestore = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.post(route('main_categories.restore', mainCategoryToRestore), {
      onSuccess: () => {
        setShowRestoreModal(false);
        setMainCategoryToRestore(null);
        setProcessing(false);
      },
      onError: () => {
        setProcessing(false);
      }
    });
  };

  const handleDelete = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.delete(route('main_categories.permanent_destroy', mainCategoryToDelete), {
      onSuccess: () => {
        setShowDeleteModal(false);
        setMainCategoryToDelete(null);
        setProcessing(false);
      },
      onError: () => {
        setProcessing(false);
      }
    });
  };

  const handleDeleteAll = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.post(route('main_categories.bulk_permanent_destroy'),
      {
        ids: selectedMainCategories
      },
      {
        onSuccess: () => {
          setShowDeleteAllModal(false);
          setSelectedMainCategories([]);
          setIsAllSelected(false);
          setProcessing(false);
        },
        onError: () => {
          setProcessing(false);
        }
      }
    );
  };

  const handleRestoreAll = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.post(route('main_categories.bulk_restore'),
      {
        ids: selectedMainCategories
      },
      {
        onSuccess: () => {
          setShowRestoreAllModal(false);
          setSelectedMainCategories([]);
          setIsAllSelected(false);
          setProcessing(false);
        },
        onError: () => {
          setProcessing(false);
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
      route("main_categories.trash"),
      { ...filters, sorting: { column, direction }, page: 1, per_page: perPage },
      { preserveState: true }
    );
  };

  const renderSortIcon = (column) => {
    const isActive = sorting.column === column;
    const upColor = isActive && sorting.direction === "asc" ? "text-gray-900" : "text-gray-300";
    const downColor = isActive && sorting.direction === "desc" ? "text-gray-900" : "text-gray-300";
    return (
      <span className="inline-flex flex-col ml-1">
        <ChevronUp className={`w-4 h-4 ${upColor}`} />
        <ChevronDown className={`w-4 h-4 -mt-1 ${downColor}`} />
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

  const handleCloseDeleteModal = () => {
    setShowDeleteModal(false);
    setProcessing(false);
    setMainCategoryToDelete(null);
  };

  const handleCloseRestoreModal = () => {
    setShowRestoreModal(false);
    setProcessing(false);
    setMainCategoryToRestore(null);
  };

  const handleCloseDeleteAllModal = () => {
    setShowDeleteAllModal(false);
    setProcessing(false);
  };

  const handleCloseRestoreAllModal = () => {
    setShowRestoreAllModal(false);
    setProcessing(false);
  };

  return (
    <AuthenticatedLayout>
      <Toaster />
      <SidebarInset>
        <div className="main-content">
          <PageHeader
            page="Main Categories"
            subpage="Trash"
            url="main_categories.index"
          />
          <div className="p-4">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
              <div>
                <div className="text-red-500">
                  Total trashed categories: {meta.total}
                </div>
              </div>
              <div className="flex flex-col md:flex-row gap-4 md:items-center">
                <Input
                  placeholder="Search trashed main categories..."
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
                    <SelectItem value="delete">Permanently Delete Selected</SelectItem>
                    <SelectItem value="restore">Restore Selected</SelectItem>
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
                    <TableHead className="w-[80px] cursor-pointer" onClick={() => handleSort("id")}>ID {renderSortIcon("id")}</TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("name")}>Name {renderSortIcon("name")}</TableHead>
                    <TableHead>Image</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {categories.length > 0 ? (
                    categories.map((category) => (
                      <TableRow key={category.id}>
                        <TableCell>
                          <Checkbox
                            checked={selectedMainCategories.includes(category.id)}
                            onCheckedChange={() => toggleSelectMainCategory(category.id)}
                          />
                        </TableCell>
                        <TableCell>{category.id}</TableCell>
                        <TableCell>{category.name}</TableCell>
                        <TableCell>
                          <img
                            src={`/uploads/media/${category.image}`}
                            alt={category.name}
                            className="mr-2 h-8 w-8 rounded-full object-cover"
                          />
                        </TableCell>
                        <TableCell className="text-right">
                          <TableActions
                            actions={[
                              {
                                label: "Restore",
                                icon: <RotateCcw className="h-4 w-4" />,
                                onClick: () => handleRestoreConfirm(category.id)
                              },
                              {
                                label: "Permanently Delete",
                                icon: <Trash className="h-4 w-4" />,
                                onClick: () => handleDeleteConfirm(category.id),
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
                        No main categories found.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>

            {categories.length > 0 && meta.total > 0 && (
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

      <DeleteMainCategoryModal
        show={showDeleteModal}
        onClose={handleCloseDeleteModal}
        onConfirm={handleDelete}
        processing={processing}
      />

      <DeleteAllMainCategoriesModal
        show={showDeleteAllModal}
        onClose={handleCloseDeleteAllModal}
        onConfirm={handleDeleteAll}
        processing={processing}
        count={selectedMainCategories.length}
      />

      <RestoreAllMainCategoriesModal
        show={showRestoreAllModal}
        onClose={handleCloseRestoreAllModal}
        onConfirm={handleRestoreAll}
        processing={processing}
        count={selectedMainCategories.length}
      />

      <RestoreMainCategoryModal
        show={showRestoreModal}
        onClose={handleCloseRestoreModal}
        onConfirm={handleRestore}
        processing={processing}
      />
    </AuthenticatedLayout>
  );
}
