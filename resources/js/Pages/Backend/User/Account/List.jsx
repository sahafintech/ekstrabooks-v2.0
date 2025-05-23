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
import { Book, Edit, Eye, Plus, Settings, Trash, Users, ChevronUp, ChevronDown } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";

// Delete Confirmation Modal Component
const DeleteAccountModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete this account?
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
const DeleteAllAccountModal = ({ show, onClose, onConfirm, processing, count }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete {count} selected account{count !== 1 ? 's' : ''}?
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


export default function List({ accounts = [], meta = {}, filters = {} }) {
  const { flash = {} } = usePage().props;
  const { toast } = useToast();
  const [selectedAccounts, setSelectedAccounts] = useState([]);
  const [isAllSelected, setIsAllSelected] = useState(false);
  const [search, setSearch] = useState(filters.search || "");
  const [perPage, setPerPage] = useState(meta.per_page || 10);
  const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
  const [bulkAction, setBulkAction] = useState("");
  const [sorting, setSorting] = useState(filters.sorting || { column: "id", direction: "desc" });

  // Delete confirmation modal states
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
  const [accountToDelete, setAccountToDelete] = useState(null);
  const [processing, setProcessing] = useState(false);

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

  const { auth } = usePage().props;

  const toggleSelectAll = () => {
    if (isAllSelected) {
      setSelectedAccounts([]);
    } else {
      setSelectedAccounts(accounts.map((account) => account.id));
    }
    setIsAllSelected(!isAllSelected);
  };

  const toggleSelectAccount = (id) => {
    if (selectedAccounts.includes(id)) {
      setSelectedAccounts(selectedAccounts.filter((accountId) => accountId !== id));
      setIsAllSelected(false);
    } else {
      setSelectedAccounts([...selectedAccounts, id]);
      if (selectedAccounts.length + 1 === accounts.length) {
        setIsAllSelected(true);
      }
    }
  };

  const handleSearch = (e) => {
    e.preventDefault();
    const value = e.target.value;
    setSearch(value);

    router.get(
      route("accounts.index"),
      { search: value, page: 1, per_page: perPage },
      { preserveState: true }
    );
  };

  const handlePerPageChange = (value) => {
    setPerPage(value);
    router.get(
      route("accounts.index"),
      { search, page: 1, per_page: value },
      { preserveState: true }
    );
  };

  const handlePageChange = (page) => {
    setCurrentPage(page);
    router.get(
      route("accounts.index"),
      { search, page, per_page: perPage },
      { preserveState: true }
    );
  };

  const handleBulkAction = () => {
    if (bulkAction === "") return;

    if (selectedAccounts.length === 0) {
      toast({
        variant: "destructive",
        title: "Error",
        description: "Please select at least one account",
      });
      return;
    }

    if (bulkAction === "delete") {
      setShowDeleteAllModal(true);
    }
  };

  const handleSort = (column) => {
    let direction = "asc";
    if (sorting.column === column && sorting.direction === "asc") {
      direction = "desc";
    }
    setSorting({ column, direction });
    router.get(
      route("accounts.index"),
      { ...filters, sorting: { column, direction } },
      { preserveState: true }
    );
  };

  const handleDeleteConfirm = (id) => {
    setAccountToDelete(id);
    setShowDeleteModal(true);
  };

  const handleDelete = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.delete(route('accounts.destroy', accountToDelete), {
      onSuccess: () => {
        setShowDeleteModal(false);
        setAccountToDelete(null);
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

    router.post(route('accounts.bulk_destroy'),
      {
        ids: selectedAccounts
      },
      {
        onSuccess: () => {
          setShowDeleteAllModal(false);
          setSelectedAccounts([]);
          setIsAllSelected(false);
          setProcessing(false);
        },
        onError: () => {
          setProcessing(false);
        }
      });
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
      <Head title="Account" />
      <Toaster />
      <SidebarInset>
        <div className="main-content">
          <PageHeader
            page="Account"
            subpage="List"
            url="accounts.index"
          />
          <div className="p-4">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
              <div className="flex flex-col md:flex-row gap-4">
                <Link href={route("accounts.create")}>
                  <Button>
                    <Plus className="w-4 h-4 mr-2" />
                    Add Account
                  </Button>
                </Link>
              </div>
              <div className="flex flex-col md:flex-row gap-4 md:items-center">
                <Input
                  placeholder="Search accounts..."
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
                    <TableHead className="cursor-pointer" onClick={() => handleSort("account_code")}>
                      Account Code {renderSortIcon("account_code")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("account_name")}>
                      Account Name {renderSortIcon("account_name")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("account_type")}>
                      Account Type {renderSortIcon("account_type")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("opening_date")}>
                      Opening Date {renderSortIcon("opening_date")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("currency")}>
                      Currency {renderSortIcon("currency")}
                    </TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {accounts.length > 0 ? (
                    accounts.map((account) => (
                      <TableRow key={account.id}>
                        <TableCell>
                          <Checkbox
                            checked={selectedAccounts.includes(account.id)}
                            onCheckedChange={() => toggleSelectAccount(account.id)}
                          />
                        </TableCell>
                        <TableCell>{account.account_code}</TableCell>
                        <TableCell>{account.account_name}</TableCell>
                        <TableCell>{account.account_type}</TableCell>
                        <TableCell>{account.opening_date}</TableCell>
                        <TableCell>{account.currency || "-"}</TableCell>
                        <TableCell className="text-right">
                          <TableActions
                            actions={[
                              {
                                label: "View",
                                icon: <Eye className="h-4 w-4" />,
                                href: route("accounts.show", account.id),
                              },
                              {
                                label: "Edit",
                                icon: <Edit className="h-4 w-4" />,
                                href: route("accounts.edit", account.id),
                              },
                              {
                                label: "Delete",
                                icon: <Trash className="h-4 w-4" />,
                                onClick: () => handleDeleteConfirm(account.id),
                                destructive: true,
                              },
                              {
                                label: "Statement",
                                icon: <Book className="h-4 w-4" />,
                                href: route("accounts.account_statement", account.id),
                              },
                            ]}
                          />
                        </TableCell>
                      </TableRow>
                    ))
                  ) : (
                    <TableRow>
                      <TableCell colSpan={8} className="h-24 text-center">
                        No accounts found.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>

            {accounts.length > 0 && meta.total > 0 && (
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

      <DeleteAccountModal
        show={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        onConfirm={handleDelete}
        processing={processing}
      />

      <DeleteAllAccountModal
        show={showDeleteAllModal}
        onClose={() => setShowDeleteAllModal(false)}
        onConfirm={handleDeleteAll}
        processing={processing}
        count={selectedAccounts.length}
      />
    </AuthenticatedLayout>
  );
}
