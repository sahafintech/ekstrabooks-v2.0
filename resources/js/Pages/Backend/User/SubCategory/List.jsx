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
import { Edit, EyeIcon, Plus, Trash } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";

// Delete Confirmation Modal Component
const DeleteSubCategoryModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete this sub category?
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
const DeleteAllSubCategoriesModal = ({ show, onClose, onConfirm, processing, count }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete {count} selected sub categories{count !== 1 ? 's' : ''}?
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

const CategoryFormModal = ({ show, onClose, onSubmit, processing, category = null, mainCategories = [] }) => {
  // Initialize with the correct value from the category object, or empty string if creating new
  const [selectedMainCategory, setSelectedMainCategory] = useState(category?.main_category_id || "");

  // Update the selected main category when the category prop changes (for edit mode)
  useEffect(() => {
    if (category) {
      setSelectedMainCategory(category.main_category_id);
    } else {
      setSelectedMainCategory("");
    }
  }, [category]);

  return (
    <Modal show={show} onClose={onClose}>
      <form onSubmit={onSubmit} encType="multipart/form-data">
        <div className="ti-modal-header">
          <h3 className="text-lg font-bold">
            {category ? "Edit Subcategory" : "Create New Subcategory"}
          </h3>
        </div>
        <div className="mt-4">
          <div className="mb-4">
            <label htmlFor="name" className="block text-sm font-medium text-gray-700">
              Subcategory Name <span className="text-red-500">*</span>
            </label>
            <Input
              type="text"
              id="name"
              name="name"
              defaultValue={category?.name || ""}
              required
              className="mt-1"
            />
          </div>

          <div className="mb-4">
            <label htmlFor="main_category_id" className="block text-sm font-medium text-gray-700">
              Main Category <span className="text-red-500">*</span>
            </label>
            <SearchableCombobox
              options={mainCategories.map(category => ({
                id: category.id,
                name: category.name
              }))}
              value={selectedMainCategory}
              onChange={(value) => setSelectedMainCategory(value)}
              placeholder="Select main category"
            />
            <input
              type="hidden"
              name="main_category_id"
              value={selectedMainCategory}
            />
          </div>

          <div className="mb-4">
            <label htmlFor="image" className="block text-sm font-medium text-gray-700">
              Image
            </label>
            <Input
              type="file"
              id="image"
              name="image"
              className="mt-1"
            />
            {category?.image && category.image !== 'default.png' && (
              <div className="mt-2">
                <p className="text-sm text-gray-500">Current Image:</p>
                <img
                  src={`/uploads/media/${category.image}`}
                  alt={category.name}
                  className="mt-1 h-20 w-auto object-contain"
                />
              </div>
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
          <Button
            type="submit"
            disabled={processing}
          >
            {category ? "Update Subcategory" : "Create Subcategory"}
          </Button>
        </div>
      </form>
    </Modal>
  );
};

export default function List({ categories = [], meta = {}, filters = {}, mainCategories }) {
  const { flash = {} } = usePage().props;
  const { toast } = useToast();
  const [selectedSubCategories, setSelectedSubCategories] = useState([]);
  const [isAllSelected, setIsAllSelected] = useState(false);
  const [search, setSearch] = useState(filters.search || "");
  const [perPage, setPerPage] = useState(meta.per_page || 50);
  const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
  const [bulkAction, setBulkAction] = useState("");

  // Delete confirmation modal states
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
  const [subCategoryToDelete, setSubCategoryToDelete] = useState(null);
  const [processing, setProcessing] = useState(false);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState(null);

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
      setSelectedSubCategories([]);
    } else {
      setSelectedSubCategories(categories.map((category) => category.id));
    }
    setIsAllSelected(!isAllSelected);
  };

  const toggleSelectSubCategory = (id) => {
    if (selectedSubCategories.includes(id)) {
      setSelectedSubCategories(selectedSubCategories.filter((categoryId) => categoryId !== id));
      setIsAllSelected(false);
    } else {
      setSelectedSubCategories([...selectedSubCategories, id]);
      if (selectedSubCategories.length + 1 === categories.length) {
        setIsAllSelected(true);
      }
    }
  };

  const handleSearch = (e) => {
    e.preventDefault();
    const value = e.target.value;
    setSearch(value);

    router.get(
      route("sub_categories.index"),
      { search: value, page: 1, per_page: perPage },
      { preserveState: true }
    );
  };


  const handlePerPageChange = (value) => {
    setPerPage(value);
    router.get(
      route("sub_categories.index"),
      { search, page: 1, per_page: value },
      { preserveState: true }
    );
  };

  const handlePageChange = (page) => {
    setCurrentPage(page);
    router.get(
      route("sub_categories.index"),
      { search, page, per_page: perPage },
      { preserveState: true }
    );
  };

  const handleBulkAction = () => {
    if (bulkAction === "") return;

    if (selectedSubCategories.length === 0) {
      toast({
        variant: "destructive",
        title: "Error",
        description: "Please select at least one category",
      });
      return;
    }

    if (bulkAction === "delete") {
      setShowDeleteAllModal(true);
    }
  };

  const handleDeleteConfirm = (id) => {
    setSubCategoryToDelete(id);
    setShowDeleteModal(true);
  };

  // Handle create category
  const handleCreate = (e) => {
    e.preventDefault();
    setProcessing(true);

    const formData = new FormData(e.target);

    router.post(route('sub_categories.store'), formData, {
      onSuccess: () => {
        setShowCreateModal(false);
        setProcessing(false);
      },
      onError: () => {
        setProcessing(false);
      }
    });
  };

  // Handle edit category
  const handleShowEditModal = (category) => {
    setSelectedCategory(category);
    setShowEditModal(true);
  };

  const handleUpdate = (e) => {
    e.preventDefault();
    setProcessing(true);

    const formData = new FormData(e.target);
    formData.append('_method', 'PUT'); // Add method spoofing for Laravel

    router.post(route('sub_categories.update', selectedCategory.id), formData, {
      onSuccess: () => {
        setShowEditModal(false);
        setProcessing(false);
        setSelectedCategory(null);
      },
      onError: () => {
        setProcessing(false);
      }
    });
  };

  const handleDelete = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.delete(route('sub_categories.destroy', subCategoryToDelete), {
      onSuccess: () => {
        setShowDeleteModal(false);
        setSubCategoryToDelete(null);
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

    router.post(route('sub_categories.bulk_destroy'),
      {
        ids: selectedSubCategories
      },
      {
        onSuccess: () => {
          setShowDeleteAllModal(false);
          setSelectedSubCategories([]);
          setIsAllSelected(false);
          setProcessing(false);
        },
        onError: () => {
          setProcessing(false);
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

  return (
    <AuthenticatedLayout>
      <Head title="Sub Categories" />
      <Toaster />
      <SidebarInset>
        <div className="main-content">
          <PageHeader
            page="Sub Categories"
            subpage="List"
            url="sub_categories.index"
          />
          <div className="p-4">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
              <div className="flex flex-col md:flex-row gap-4">
                <Button onClick={() => setShowCreateModal(true)}>
                  <Plus className="w-4 h-4 mr-2" />
                  Add Sub Category
                </Button>
              </div>
              <div className="flex flex-col md:flex-row gap-4 md:items-center">
                <Input
                  placeholder="Search sub categories..."
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
                    <TableHead className="w-[80px]">ID</TableHead>
                    <TableHead>Image</TableHead>
                    <TableHead>Name</TableHead>
                    <TableHead>Main Category</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {categories.length > 0 ? (
                    categories.map((category) => (
                      <TableRow key={category.id}>
                        <TableCell>
                          <Checkbox
                            checked={selectedSubCategories.includes(category.id)}
                            onCheckedChange={() => toggleSelectSubCategory(category.id)}
                          />
                        </TableCell>
                        <TableCell>{category.id}</TableCell>
                        <TableCell>
                          <img
                            src={`/uploads/media/${category.image}`}
                            alt={category.name}
                            className="mr-2 h-8 w-8 rounded-full object-cover"
                          />
                        </TableCell>
                        <TableCell>{category.name}</TableCell>
                        <TableCell>{category.main_category.name}</TableCell>
                        <TableCell className="text-right">
                          <TableActions
                            actions={[
                              {
                                label: "View",
                                icon: <EyeIcon className="h-4 w-4" />,
                                href: route("sub_categories.show", category.id),
                              },
                              {
                                label: "Edit",
                                icon: <Edit className="h-4 w-4" />,
                                onClick: () => handleShowEditModal(category)
                              },
                              {
                                label: "Delete",
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
                        No sub categories found.
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

      <DeleteSubCategoryModal
        show={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        onConfirm={handleDelete}
        processing={processing}
      />

      <DeleteAllSubCategoriesModal
        show={showDeleteAllModal}
        onClose={() => setShowDeleteAllModal(false)}
        onConfirm={handleDeleteAll}
        processing={processing}
        count={selectedSubCategories.length}
      />

      <CategoryFormModal
        show={showCreateModal}
        onClose={() => setShowCreateModal(false)}
        onSubmit={handleCreate}
        processing={processing}
        mainCategories={mainCategories || []}
      />

      <CategoryFormModal
        show={showEditModal}
        onClose={() => setShowEditModal(false)}
        onSubmit={handleUpdate}
        processing={processing}
        category={selectedCategory}
        mainCategories={mainCategories || []}
      />

    </AuthenticatedLayout>
  );
}
