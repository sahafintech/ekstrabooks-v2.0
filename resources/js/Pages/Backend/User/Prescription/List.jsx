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
import { Edit, EyeIcon, Plus, Trash, ChevronUp, ChevronDown } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";

// Delete Confirmation Modal Component
const DeletePrescriptionModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete this prescription?
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
const DeleteAllPrescriptionsModal = ({ show, onClose, onConfirm, processing, count }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete {count} selected prescription{count !== 1 ? 's' : ''}?
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

// Change Status Modal Component
const ChangeStatusModal = ({ show, onClose, onConfirm, processing, currentStatus }) => {
  const [newStatus, setNewStatus] = useState(currentStatus);

  const handleSubmit = (e) => {
    e.preventDefault();
    onConfirm(newStatus);
  };

  return (
    <Modal show={show} onClose={onClose}>
      <form onSubmit={handleSubmit}>
        <h2 className="text-lg font-medium mb-4">Change Prescription Status</h2>
        <div className="mb-4">
          <SearchableCombobox
            options={
              [
                { name: "Not Started", id: 0 },
                { name: "Working Progress", id: 1 },
                { name: "Completed", id: 2 },
                { name: "Delivered", id: 3 },
              ]
            }
            value={newStatus}
            onChange={(value) => setNewStatus(value)}
          />
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
            Update Status
          </Button>
        </div>
      </form>
    </Modal>
  );
};

// Send to POS Confirmation Modal Component
const SendToPosModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to send this prescription to POS?
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
          disabled={processing}
        >
          Send to POS
        </Button>
      </div>
    </form>
  </Modal>
);

const PrescriptionStatusBadge = ({ status }) => {
  const statusMap = {
      0: {
          label: "Not Started",
          className: "text-red-400 bg-red-200 px-3 py-1 rounded text-xs",
      },
      1: {
          label: "Working Progress",
          className: "text-yellow-400 bg-yellow-200 px-3 py-1 rounded text-xs",
      },
      2: {
          label: "Completed",
          className: "text-green-400 bg-green-200 px-3 py-1 rounded text-xs",
      },
      3: {
          label: "Delivered",
          className: "text-blue-400 bg-blue-200 px-3 py-1 rounded text-xs",
      },
  };

  return (
      <span className={statusMap[status].className}>
          {statusMap[status].label}
      </span>
  );
};

export default function List({ prescriptions = [], meta = {}, filters = {} }) {
  const { flash = {} } = usePage().props;
  const { toast } = useToast();
  const [selectedPrescriptions, setSelectedPrescriptions] = useState([]);
  const [isAllSelected, setIsAllSelected] = useState(false);
  const [search, setSearch] = useState(filters.search || "");
  const [perPage, setPerPage] = useState(meta.per_page || 50);
  const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
  const [bulkAction, setBulkAction] = useState("");
  const [sorting, setSorting] = useState(filters.sorting || { column: "id", direction: "desc" });

  // Delete confirmation modal states
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
  const [prescriptionToDelete, setPrescriptionToDelete] = useState(null);
  const [processing, setProcessing] = useState(false);

  // Change Status modal states
  const [showStatusModal, setShowStatusModal] = useState(false);
  const [prescriptionToUpdate, setPrescriptionToUpdate] = useState(null);

  // Send to POS modal states
  const [showSendToPosModal, setShowSendToPosModal] = useState(false);
  const [prescriptionToSendToPos, setPrescriptionToSendToPos] = useState(null);

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
      setSelectedPrescriptions([]);
    } else {
      setSelectedPrescriptions(prescriptions.map((prescription) => prescription.id));
    }
    setIsAllSelected(!isAllSelected);
  };

  const toggleSelectPrescription = (id) => {
    if (selectedPrescriptions.includes(id)) {
      setSelectedPrescriptions(selectedPrescriptions.filter((prescriptionId) => prescriptionId !== id));
      setIsAllSelected(false);
    } else {
      setSelectedPrescriptions([...selectedPrescriptions, id]);
      if (selectedPrescriptions.length + 1 === prescriptions.length) {
        setIsAllSelected(true);
      }
    }
  };

  const handleSearch = (e) => {
    e.preventDefault();
    const value = e.target.value;
    setSearch(value);
    router.get(
      route("prescriptions.index"),
      { search: value, page: 1, per_page: perPage, sorting },
      { preserveState: true }
    );
  };

  const handlePerPageChange = (value) => {
    setPerPage(value);
    router.get(
      route("prescriptions.index"),
      { search, page: 1, per_page: value, sorting },
      { preserveState: true }
    );
  };

  const handlePageChange = (page) => {
    setCurrentPage(page);
    router.get(
      route("prescriptions.index"),
      { search, page, per_page: perPage, sorting },
      { preserveState: true }
    );
  };

  const handleSort = (column) => {
    let direction = "asc";
    if (sorting.column === column && sorting.direction === "asc") {
      direction = "desc";
    }
    setSorting({ column, direction });
    router.get(
      route("prescriptions.index"),
      { ...filters, sorting: { column, direction } },
      { preserveState: true }
    );
  };

  const handleBulkAction = () => {
    if (bulkAction === "") return;

    if (selectedPrescriptions.length === 0) {
      toast({
        variant: "destructive",
        title: "Error",
        description: "Please select at least one prescription",
      });
      return;
    }

    if (bulkAction === "delete") {
      setShowDeleteAllModal(true);
    }
  };

  const handleDeleteConfirm = (id) => {
    setPrescriptionToDelete(id);
    setShowDeleteModal(true);
  };

  const handleDelete = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.delete(route('prescriptions.destroy', prescriptionToDelete), {
      onSuccess: () => {
        setShowDeleteModal(false);
        setPrescriptionToDelete(null);
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

    router.post(route('prescriptions.bulk_destroy'),
      {
        ids: selectedPrescriptions
      },
      {
        onSuccess: () => {
          setShowDeleteAllModal(false);
          setSelectedPrescriptions([]);
          setIsAllSelected(false);
          setProcessing(false);
        },
        onError: () => {
          setProcessing(false);
        }
      }
    );
  };

  const handleStatusChange = (id) => {
    const prescription = prescriptions.find(p => p.id === id);
    setPrescriptionToUpdate(prescription);
    setShowStatusModal(true);
  };

  const handleStatusUpdate = (newStatus) => {
    setProcessing(true);

    router.post(route('prescriptions.change_status', prescriptionToUpdate.id), 
      { status: newStatus },
      {
        onSuccess: () => {
          setShowStatusModal(false);
          setPrescriptionToUpdate(null);
          setProcessing(false);
        },
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

  const handleSendToPos = (id) => {
    setPrescriptionToSendToPos(id);
    setShowSendToPosModal(true);
  };

  const handleSendToPosConfirm = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.post(route('prescriptions.send_to_pos', prescriptionToSendToPos), {
      onSuccess: () => {
        setShowSendToPosModal(false);
        setPrescriptionToSendToPos(null);
        setProcessing(false);
        toast({
          title: "Success",
          description: "Prescription sent to POS successfully",
        });
      },
      onError: () => {
        setProcessing(false);
      }
    });
  };

  return (
    <AuthenticatedLayout>
      <Toaster />
      <SidebarInset>
        <div className="main-content">
          <PageHeader
            page="Prescriptions"
            subpage="List"
            url="prescriptions.index"
          />
          <div className="p-4">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
              <div className="flex flex-col md:flex-row gap-4">
                <Link href={route("prescriptions.create")}>
                  <Button>
                    <Plus className="w-4 h-4" />
                    Add Prescription
                  </Button>
                </Link>
              </div>
              <div className="flex flex-col md:flex-row gap-4 md:items-center">
                <Input
                  placeholder="search prescriptions..."
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

            <div className="overflow-x-auto rounded-lg border">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>
                      <Checkbox
                        checked={isAllSelected}
                        onCheckedChange={toggleSelectAll}
                      />
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("id")}>
                      ID {renderSortIcon("id")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("date")}>
                      Date {renderSortIcon("date")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("result_date")}>
                      Result Date {renderSortIcon("result_date")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("customer.name")}>
                      Customer {renderSortIcon("customer.name")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("customer.age")}>
                      Age {renderSortIcon("customer.age")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("customer.mobile")}>
                      Mobile {renderSortIcon("customer.mobile")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("status")}>
                      Status {renderSortIcon("status")}
                    </TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {prescriptions.length > 0 ? (
                    prescriptions.map((prescription) => (
                      <TableRow key={prescription.id}>
                        <TableCell>
                          <Checkbox
                            checked={selectedPrescriptions.includes(prescription.id)}
                            onCheckedChange={() => toggleSelectPrescription(prescription.id)}
                          />
                        </TableCell>
                        <TableCell>{prescription.id}</TableCell>
                        <TableCell>{prescription.date}</TableCell>
                        <TableCell>{prescription.result_date}</TableCell>
                        <TableCell>{prescription.customer?.name}</TableCell>
                        <TableCell>{prescription.customer?.age}</TableCell>
                        <TableCell>{prescription.customer?.mobile}</TableCell>
                        <TableCell>{<PrescriptionStatusBadge status={prescription.status} />}</TableCell>
                        <TableCell className="text-right">
                          <TableActions
                            actions={[
                              {
                                label: "View",
                                icon: <EyeIcon className="h-4 w-4" />,
                                href: route("prescriptions.show", prescription.id),
                              },
                              {
                                label: "Edit",
                                icon: <Edit className="h-4 w-4" />,
                                href: route("prescriptions.edit", prescription.id),
                              },
                              {
                                label: "Change Status",
                                icon: <Edit className="h-4 w-4" />,
                                onClick: () => handleStatusChange(prescription.id),
                              },
                              {
                                label: "Send To POS",
                                icon: <Edit className="h-4 w-4" />,
                                onClick: () => handleSendToPos(prescription.id),
                              },
                              {
                                label: "Delete",
                                icon: <Trash className="h-4 w-4" />,
                                onClick: () => handleDeleteConfirm(prescription.id),
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
                        No prescriptions found.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>

            {prescriptions.length > 0 && meta.total > 0 && (
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

      <DeletePrescriptionModal
        show={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        onConfirm={handleDelete}
        processing={processing}
      />

      <DeleteAllPrescriptionsModal
        show={showDeleteAllModal}
        onClose={() => setShowDeleteAllModal(false)}
        onConfirm={handleDeleteAll}
        processing={processing}
        count={selectedPrescriptions.length}
      />

      <ChangeStatusModal
        show={showStatusModal}
        onClose={() => setShowStatusModal(false)}
        onConfirm={handleStatusUpdate}
        processing={processing}
        currentStatus={prescriptionToUpdate?.status}
      />

      <SendToPosModal
        show={showSendToPosModal}
        onClose={() => setShowSendToPosModal(false)}
        onConfirm={handleSendToPosConfirm}
        processing={processing}
      />

    </AuthenticatedLayout>
  );
}
