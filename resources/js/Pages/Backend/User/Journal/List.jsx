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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/ui/select";
import { Input } from "@/Components/ui/input";
import { format, parse, isValid } from "date-fns";
import { Download, Edit, EyeIcon, Plus, Trash } from "lucide-react";
import { Toaster } from "@/components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";

export default function List({ journals = [], meta = {}, filters = {} }) {
  const { flash = {} } = usePage().props;
  const { toast } = useToast();
  const [selectedJournals, setSelectedJournals] = useState([]);
  const [isAllSelected, setIsAllSelected] = useState(false);
  const [search, setSearch] = useState(filters.search || "");
  const [perPage, setPerPage] = useState(meta.per_page || 10);
  const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
  const [bulkAction, setBulkAction] = useState("");

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
      setSelectedJournals([]);
    } else {
      setSelectedJournals(journals.map((journal) => journal.id));
    }
    setIsAllSelected(!isAllSelected);
  };

  const toggleSelectJournal = (id) => {
    if (selectedJournals.includes(id)) {
      setSelectedJournals(selectedJournals.filter((journalId) => journalId !== id));
      setIsAllSelected(false);
    } else {
      setSelectedJournals([...selectedJournals, id]);
      if (selectedJournals.length + 1 === journals.length) {
        setIsAllSelected(true);
      }
    }
  };

  const handleSearch = (e) => {
    e.preventDefault();
    router.get(
      route("journals.index"),
      { search, page: 1, per_page: perPage },
      { preserveState: true }
    );
  };

  const handlePerPageChange = (value) => {
    setPerPage(value);
    router.get(
      route("journals.index"),
      { search, page: 1, per_page: value },
      { preserveState: true }
    );
  };

  const handlePageChange = (page) => {
    setCurrentPage(page);
    router.get(
      route("journals.index"),
      { search, page, per_page: perPage },
      { preserveState: true }
    );
  };

  const handleBulkAction = () => {
    if (bulkAction === "") return;

    if (selectedJournals.length === 0) {
      toast({
        variant: "destructive",
        title: "Error",
        description: "Please select at least one journal entry",
      });
      return;
    }

    router.post(
      route("journals.all"),
      {
        journals: selectedJournals,
        type: bulkAction
      },
      {
        preserveState: true,
        onSuccess: () => {
          setSelectedJournals([]);
          setIsAllSelected(false);
          setBulkAction("");
        }
      }
    );
  };

  const handleExport = (id) => {
    window.location.href = route("journals.export_journal", id);
  };

  const formatCurrency = (amount, currency = "USD") => {
    // Ensure we use proper ISO 4217 currency code
    // Extract just the currency code if it contains additional text
    const currencyCode = (currency || "USD").split(' ')[0];

    return new Intl.NumberFormat("en-US", {
      style: "currency",
      currency: currencyCode,
    }).format(amount);
  };

  const formatDate = (dateString) => {
    if (!dateString) return "N/A";

    // Try different date parsing approaches
    let date;

    // First try direct Date parsing
    date = new Date(dateString);

    // If that fails, try parsing common formats
    if (!isValid(date)) {
      // Try parsing yyyy-mm-dd format
      if (dateString.match(/^\d{4}-\d{2}-\d{2}$/)) {
        const [year, month, day] = dateString.split('-').map(Number);
        date = new Date(year, month - 1, day);
      }
      // Try parsing dd/mm/yyyy format
      else if (dateString.match(/^\d{2}\/\d{2}\/\d{4}$/)) {
        date = parse(dateString, 'dd/MM/yyyy', new Date());
      }
    }

    // If still invalid, return the original string
    if (!isValid(date)) {
      return dateString;
    }

    return format(date, "dd MMM yyyy");
  };

  const getStatusBadge = (status) => {
    if (status === 1) {
      return (
        <p className="text-green-500">
          Approved
        </p>
      );
    }
    return (
      <p className="text-yellow-500">
        Pending
      </p>
    );
  };

  const handleDelete = (id) => {
    router.delete(route("journals.destroy", id), {
      preserveState: true,
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
            page="Journal Entries"
            subpage="List"
            url="journals.index"
          />
          <div className="p-4">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
              <div className="flex flex-col md:flex-row gap-4">
                <Button asChild>
                  <Link href={route("journals.create")}>
                    <Plus className="w-4 h-4 mr-2" />
                    Create Journal Entry
                  </Link>
                </Button>
              </div>
              <div className="flex flex-col md:flex-row gap-4 md:items-center">
                <form onSubmit={handleSearch} className="flex gap-2">
                  <Input
                    placeholder="Search journal entries..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="w-full md:w-80"
                  />
                  <Button type="submit">Search</Button>
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
                    <SelectItem value="approve">Approve Selected</SelectItem>
                    <SelectItem value="reject">Reject Selected</SelectItem>
                  </SelectContent>
                </Select>
                <Button onClick={handleBulkAction} variant="outline">
                  Apply
                </Button>
              </div>
              <div className="flex items-center gap-2">
                <span className="text-sm text-gray-500">
                  Show
                </span>
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
                <span className="text-sm text-gray-500">
                  entries
                </span>
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
                        aria-label="Select all"
                      />
                    </TableHead>
                    <TableHead className="w-[100px]">Date</TableHead>
                    <TableHead className="w-[120px]">Journal #</TableHead>
                    <TableHead>Description</TableHead>
                    <TableHead className="text-right">Amount</TableHead>
                    <TableHead className="w-[100px] text-center">Status</TableHead>
                    <TableHead className="w-[100px] text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {journals.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={7} className="text-center py-8">
                        No journal entries found
                      </TableCell>
                    </TableRow>
                  ) : (
                    journals.map((journal) => (
                      <TableRow key={journal.id}>
                        <TableCell>
                          <Checkbox
                            checked={selectedJournals.includes(journal.id)}
                            onCheckedChange={() => toggleSelectJournal(journal.id)}
                            aria-label={`Select journal ${journal.journal_number}`}
                          />
                        </TableCell>
                        <TableCell>
                          {formatDate(journal.date)}
                        </TableCell>
                        <TableCell>
                          <Link
                            href={route("journals.show", journal.id)}
                            className="font-medium hover:underline text-primary"
                          >
                            {journal.journal_number}
                          </Link>
                        </TableCell>
                        <TableCell className="max-w-[300px] truncate">
                          {journal.description || "Journal entry"}
                        </TableCell>
                        <TableCell className="text-right">
                          {formatCurrency(journal.transaction_amount, journal.transaction_currency)}
                          {journal.transaction_currency !== journal.base_currency && (
                            <div className="text-xs text-gray-500">
                              {formatCurrency(journal.base_currency_amount, journal.base_currency || "USD")}
                            </div>
                          )}
                        </TableCell>
                        <TableCell className="text-center">
                          {getStatusBadge(journal.status)}
                        </TableCell>
                        <TableCell className="text-right">
                          <TableActions
                            actions={[
                              {
                                label: "View",
                                icon: EyeIcon,
                                onClick: () => router.visit(route("journals.show", journal.id))
                              },
                              {
                                label: "Edit",
                                icon: Edit,
                                onClick: () => router.visit(route("journals.edit", journal.id))
                              },
                              {
                                label: "Export",
                                icon: Download,
                                onClick: () => handleExport(journal.id)
                              },
                              {
                                label: "Delete",
                                icon: Trash,
                                onClick: () => {
                                  if (confirm("Are you sure you want to delete this journal entry? This action cannot be undone.")) {
                                    handleDelete(journal.id);
                                  }
                                },
                                className: "text-destructive"
                              }
                            ]}
                          />
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </div>

            <div className="mt-4 flex items-center justify-between">
              <div className="text-sm text-gray-500">
                Showing {meta.from || 0} to {meta.to || 0} of {meta.total || 0} entries
              </div>
              <div className="flex items-center">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => handlePageChange(currentPage - 1)}
                  disabled={currentPage === 1}
                  className="mr-1"
                >
                  Previous
                </Button>
                {renderPageNumbers()}
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => handlePageChange(currentPage + 1)}
                  disabled={currentPage === meta.last_page}
                  className="ml-1"
                >
                  Next
                </Button>
              </div>
            </div>
          </div>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
