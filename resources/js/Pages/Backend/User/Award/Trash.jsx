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
import { Edit, Trash, ChevronUp, ChevronDown, RotateCcw } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";

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

// Restore Confirmation Modal Component
const RestoreConfirmationModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to restore this award?
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

// Bulk Restore Confirmation Modal Component
const BulkRestoreConfirmationModal = ({ show, onClose, onConfirm, processing, count }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to restore {count} selected award{count !== 1 ? 's' : ''}?
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

export default function TrashList({ awards = [], meta = {}, filters = {} }) {
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
  const [showBulkRestoreModal, setShowBulkRestoreModal] = useState(false);
  const [awardToDelete, setAwardToDelete] = useState(null);
  const [isProcessing, setIsProcessing] = useState(false);

  // Restore confirmation modal states
  const [showRestoreModal, setShowRestoreModal] = useState(false);
  const [awardToRestore, setAwardToRestore] = useState(null);

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
      route("awards.trash"),
      { search: value, page: 1, per_page: perPage },
      { preserveState: true }
    );
  };

  const handlePerPageChange = (value) => {
    setPerPage(value);
    router.get(
      route("awards.trash"),
      { search, page: 1, per_page: value },
      { preserveState: true }
    );
  };

  const handlePageChange = (page) => {
    setCurrentPage(page);
    router.get(
      route("awards.trash"),
      { search, page, per_page: perPage },
      { preserveState: true }
    );
  };

  const handleBulkAction = () => {
    if (bulkAction === "") return;

    if (selectedAwards.length === 0) {
      toast({
        variant: "destructive",
        title: "Error",
        description: "Please select at least one award",
      });
      return;
    }

    if (bulkAction === "delete") {
      setShowBulkDeleteModal(true);
    } else if (bulkAction === "restore") {
      setShowBulkRestoreModal(true);
    }
  };

  const handleDeleteConfirm = (id) => {
    setAwardToDelete(id);
    setShowDeleteModal(true);
  };

  const handleRestoreConfirm = (id) => {
    setAwardToRestore(id);
    setShowRestoreModal(true);
  };

  const handleDelete = (e) => {
    e.preventDefault();
    setIsProcessing(true);

    router.delete(route("awards.permanent_destroy", awardToDelete), {
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

  const handleBulkDelete = (e) => {
    e.preventDefault();
    setIsProcessing(true);

    router.post(route("awards.bulk_permanent_destroy"),
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

  const handleRestore = (e) => {
    e.preventDefault();
    setIsProcessing(true);

    router.post(route("awards.restore", awardToRestore), {
      preserveState: true,
      onSuccess: () => {
        setShowRestoreModal(false);
        setAwardToRestore(null);
        setIsProcessing(false);
      },
      onError: () => {
        setIsProcessing(false);
      }
    });
  };

  const handleBulkRestore = (e) => {
    e.preventDefault();
    setIsProcessing(true);

    router.post(route("awards.bulk_restore"),
      {
        ids: selectedAwards
      },
      {
        preserveState: true,
        onSuccess: () => {
          setSelectedAwards([]);
          setIsAllSelected(false);
          setBulkAction("");
          setShowBulkRestoreModal(false);
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
      route("awards.trash"),
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
            subpage="Trash"
            url="awards.index"
          />
          <div className="p-4">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div className="flex flex-col md:flex-row gap-2">
                    <div>
                        <div className="text-red-500">
                            Total trashed awards: {meta.total}
                        </div>
                    </div>
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
                                label: "Restore",
                                icon: <RotateCcw className="h-4 w-4" />,
                                onClick: () => handleRestoreConfirm(award.id)
                            },
                            {
                                label: "Permanently Delete",
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
                        No awards found.
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

          <DeleteConfirmationModal
            show={showDeleteModal}
            onClose={() => setShowDeleteModal(false)}
            onConfirm={handleDelete}
            processing={isProcessing}
          />

          <BulkDeleteConfirmationModal
            show={showBulkDeleteModal}
            onClose={() => setShowBulkDeleteModal(false)}
            onConfirm={handleBulkDelete}
            processing={isProcessing}
            count={selectedAwards.length}
          />

          <RestoreConfirmationModal
            show={showRestoreModal}
            onClose={() => setShowRestoreModal(false)}
            onConfirm={handleRestore}
            processing={isProcessing}
          />

          <BulkRestoreConfirmationModal
            show={showBulkRestoreModal}
            onClose={() => setShowBulkRestoreModal(false)}
            onConfirm={handleBulkRestore}
            processing={isProcessing}
            count={selectedAwards.length}
          />
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
