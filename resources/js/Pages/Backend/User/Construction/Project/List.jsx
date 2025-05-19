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
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import { Input } from "@/Components/ui/input";
import { Edit, FileDown, FileUp, MoreVertical, Plus, Trash, ChevronUp, ChevronDown, Eye } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";

// Delete Confirmation Modal Component
const DeleteProjectModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete this project?
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
const DeleteAllProjectsModal = ({ show, onClose, onConfirm, processing, count }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm}>
      <h2 className="text-lg font-medium">
        Are you sure you want to delete {count} selected project{count !== 1 ? 's' : ''}?
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

const ProjectStatusBadge = ({ status }) => {
  const statusMap = {
      "Planning": {
          label: "Planning",
          className: "text-gray-600 bg-gray-200 px-3 py-1 rounded text-xs",
      },
      "In Progress": {
          label: "In Progress",
          className: "text-green-400 bg-green-200 px-3 py-1 rounded text-xs",
      },
      "Completed": {
          label: "Completed",
          className: "text-green-400 bg-green-200 px-3 py-1 rounded text-xs",
      },
      "On Hold": {
          label: "On Hold",
          className: "text-yellow-400 bg-yellow-200 px-3 py-1 rounded text-xs",
      },
      "Cancelled": {
          label: "Cancelled",
          className: "text-red-400 bg-red-200 px-3 py-1 rounded text-xs",
      },
      "Archived": {
          label: "Archived",
          className: "text-gray-400 bg-gray-200 px-3 py-1 rounded text-xs",
      },
  };

  return (
      <span className={statusMap[status].className}>
          {statusMap[status].label}
      </span>
  );
};

const ProjectPriorityBadge = ({ status }) => {
  const statusMap = {
      "Low": {
          label: "Low",
          className: "text-green-400 bg-green-200 px-3 py-1 rounded text-xs",
      },
      "Medium": {
          label: "Medium",
          className: "text-yellow-400 bg-yellow-200 px-3 py-1 rounded text-xs",
      },
      "High": {
          label: "High",
          className: "text-red-400 bg-red-200 px-3 py-1 rounded text-xs",
      },
      "Critical": {
          label: "Critical",
          className: "text-red-400 bg-red-200 px-3 py-1 rounded text-xs",
      },
  };

  return (
      <span className={statusMap[status].className}>
          {statusMap[status].label}
      </span>
  );
};

// Import Projects Modal Component
const ImportProjectsModal = ({ show, onClose, onSubmit, processing }) => (
  <Modal show={show} onClose={onClose} maxWidth="3xl">
    <form onSubmit={onSubmit}>
      <div className="ti-modal-header">
        <h3 className="text-lg font-bold">Import Projects</h3>
      </div>
      <div className="ti-modal-body grid grid-cols-12">
        <div className="col-span-12">
          <div className="flex items-center justify-between">
            <label className="block font-medium text-sm text-gray-700">
              Projects File
            </label>
            <a href="/uploads/media/default/sample_projects.xlsx" download>
              <Button variant="secondary" size="sm" type="button">
                Use This Sample File
              </Button>
            </a>
          </div>
          <input type="file" className="w-full dropify" name="projects_file" required />
        </div>
        <div className="col-span-12 mt-4">
          <ul className="space-y-3 text-sm">
            <li className="flex space-x-3">
              <span className="text-primary bg-primary/20 rounded-full px-1">✓</span>
              <span className="text-gray-800 dark:text-white/70">
                Maximum File Size: 1 MB
              </span>
            </li>
            <li className="flex space-x-3">
              <span className="text-primary bg-primary/20 rounded-full px-1">✓</span>
              <span className="text-gray-800 dark:text-white/70">
                File format Supported: CSV, TSV, XLS
              </span>
            </li>
            <li className="flex space-x-3">
              <span className="text-primary bg-primary/20 rounded-full px-1">✓</span>
              <span className="text-gray-800 dark:text-white/70">
                Make sure the format of the import file matches our sample file by comparing them.
              </span>
            </li>
          </ul>
        </div>
      </div>
      <div className="mt-6 flex justify-end">
        <Button
          type="button"
          variant="secondary"
          onClick={onClose}
          className="mr-3"
        >
          Close
        </Button>
        <Button
          type="submit"
          disabled={processing}
        >
          Import
        </Button>
      </div>
    </form>
  </Modal>
);

export default function List({ projects = [], meta = {}, filters = {} }) {
  const { flash = {} } = usePage().props;
  const { toast } = useToast();
  const [selectedProjects, setSelectedProjects] = useState([]);
  const [isAllSelected, setIsAllSelected] = useState(false);
  const [search, setSearch] = useState(filters.search || "");
  const [perPage, setPerPage] = useState(meta.per_page || 50);
  const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
  const [bulkAction, setBulkAction] = useState("");
  const [sorting, setSorting] = useState(filters.sorting || { column: "id", direction: "desc" });

  // Delete confirmation modal states
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
  const [showImportModal, setShowImportModal] = useState(false);
  const [projectToDelete, setProjectToDelete] = useState(null);
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

  const toggleSelectAll = () => {
    if (isAllSelected) {
      setSelectedProjects([]);
    } else {
      setSelectedProjects(projects.map((project) => project.id));
    }
    setIsAllSelected(!isAllSelected);
  };

  const toggleSelectProject = (id) => {
    if (selectedProjects.includes(id)) {
      setSelectedProjects(selectedProjects.filter((projectId) => projectId !== id));
      setIsAllSelected(false);
    } else {
      setSelectedProjects([...selectedProjects, id]);
      if (selectedProjects.length + 1 === projects.length) {
        setIsAllSelected(true);
      }
    }
  };

  const handleSearch = (e) => {
    e.preventDefault();
    const value = e.target.value;
    setSearch(value);

    router.get(
      route("projects.index"),
      { search: value, page: 1, per_page: perPage },
      { preserveState: true }
    );
  };

  const handlePerPageChange = (value) => {
    setPerPage(value);
    router.get(
      route("projects.index"),
      { search, page: 1, per_page: value },
      { preserveState: true }
    );
  };

  const handlePageChange = (page) => {
    setCurrentPage(page);
    router.get(
      route("projects.index"),
      { search, page, per_page: perPage },
      { preserveState: true }
    );
  };

  const handleBulkAction = () => {
    if (bulkAction === "") return;

    if (selectedProjects.length === 0) {
      toast({
        variant: "destructive",
        title: "Error",
        description: "Please select at least one project",
      });
      return;
    }

    if (bulkAction === "delete") {
      setShowDeleteAllModal(true);
    }
  };

  const handleDeleteConfirm = (id) => {
    setProjectToDelete(id);
    setShowDeleteModal(true);
  };

  const handleDelete = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.delete(route('projects.destroy', projectToDelete), {
      onSuccess: () => {
        setShowDeleteModal(false);
        setProjectToDelete(null);
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

    router.post(route('projects.bulk_destroy'),
      {
        ids: selectedProjects
      },
      {
        onSuccess: () => {
          setShowDeleteAllModal(false);
          setSelectedProjects([]);
          setIsAllSelected(false);
          setProcessing(false);
        },
        onError: () => {
          setProcessing(false);
        }
      }
    );
  };

  const handleImport = (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    setProcessing(true);

    router.post(route('projects.import'), formData, {
      onSuccess: () => {
        setShowImportModal(false);
        setProcessing(false);
      },
      onError: () => {
        setProcessing(false);
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
      route("projects.index"),
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

  const exportProjects = () => {
    window.location.href = route("projects.export")
  };

  return (
    <AuthenticatedLayout>
      <Head title="Projects" />
      <Toaster />
      <SidebarInset>
        <div className="main-content">
          <PageHeader
            page="Projects"
            subpage="List"
            url="projects.index"
          />
          <div className="p-4">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
              <div className="flex flex-col md:flex-row gap-4">
                <Link href={route("projects.create")}>
                  <Button>
                    <Plus className="w-4 h-4 mr-2" />
                    Add Project
                  </Button>
                </Link>
                <DropdownMenu>
                  <DropdownMenuTrigger asChild>
                    <Button variant="secondary">
                      <MoreVertical className="h-4 w-4" />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end">
                    <DropdownMenuItem onClick={() => setShowImportModal(true)}>
                      <FileUp className="mr-2 h-4 w-4" /> Import
                    </DropdownMenuItem>
                    <DropdownMenuItem onClick={exportProjects}>
                      <FileDown className="mr-2 h-4 w-4" /> Export
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
              <div className="flex flex-col md:flex-row gap-4 md:items-center">
                <Input
                  placeholder="search projects..."
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
                    <TableHead className="cursor-pointer" onClick={() => handleSort("project_code")}>
                      Project Code {renderSortIcon("project_code")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("project_name")}>
                      Project Name {renderSortIcon("project_name")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("project_group_id")}>
                      Project Group {renderSortIcon("project_group_id")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("customer_id")}>
                      Customer {renderSortIcon("customer_id")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("project_manager_id")}>
                      Project Manager {renderSortIcon("project_manager_id")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("start_date")}>
                      Start Date {renderSortIcon("start_date")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("end_date")}>
                      End Date {renderSortIcon("end_date")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("status")}>
                      Status {renderSortIcon("status")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("priority")}>
                      Priority {renderSortIcon("priority")}
                    </TableHead>
                    <TableHead className="cursor-pointer" onClick={() => handleSort("completion_date")}>
                      Completion Date {renderSortIcon("completion_date")}
                    </TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {projects.length > 0 ? (
                    projects.map((project) => (
                      <TableRow key={project.id}>
                        <TableCell>
                          <Checkbox
                            checked={selectedProjects.includes(project.id)}
                            onCheckedChange={() => toggleSelectProject(project.id)}
                          />
                        </TableCell>
                        <TableCell>
                          <Link href={route("projects.show", project.id)} className="text-blue-500 underline">
                            {project.project_code}
                          </Link>
                        </TableCell>
                        <TableCell>{project.project_name || "-"}</TableCell>
                        <TableCell>{project.project_group?.group_name || "-"}</TableCell>
                        <TableCell>{project.customer?.name || "-"}</TableCell>
                        <TableCell>{project.manager?.name || "-"}</TableCell>
                        <TableCell>{project.start_date || "-"}</TableCell>
                        <TableCell>{project.end_date || "-"}</TableCell>
                        <TableCell><ProjectStatusBadge status={project.status} /></TableCell>
                        <TableCell><ProjectPriorityBadge status={project.priority} /></TableCell>
                        <TableCell>{project.completion_date || "-"}</TableCell>
                        
                        <TableCell className="text-right">
                          <TableActions
                            actions={[
                              {
                                label: "View",
                                icon: <Eye className="h-4 w-4" />,
                                href: route("projects.show", project.id),
                              },
                              {
                                label: "Edit",
                                icon: <Edit className="h-4 w-4" />,
                                href: route("projects.edit", project.id),
                              },
                              {
                                label: "Delete",
                                icon: <Trash className="h-4 w-4" />,
                                onClick: () => handleDeleteConfirm(project.id),
                                destructive: true,
                              },
                            ]}
                          />
                        </TableCell>
                      </TableRow>
                    ))
                  ) : (
                    <TableRow>
                      <TableCell colSpan={5} className="h-24 text-center">
                        No projects found.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>

            {projects.length > 0 && meta.total > 0 && (
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

      <DeleteProjectModal
        show={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        onConfirm={handleDelete}
        processing={processing}
      />

      <DeleteAllProjectsModal
        show={showDeleteAllModal}
        onClose={() => setShowDeleteAllModal(false)}
        onConfirm={handleDeleteAll}
        processing={processing}
        count={selectedProjects.length}
      />

      <ImportProjectsModal
        show={showImportModal}
        onClose={() => setShowImportModal(false)}
        onSubmit={handleImport}
        processing={processing}
      />
    </AuthenticatedLayout>
  );
}
