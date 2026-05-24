import { useState, useEffect } from "react";
import { router, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import { Checkbox } from "@/Components/ui/checkbox";
import {
    Table, TableBody, TableCell,
    TableHead, TableHeader, TableRow,
} from "@/Components/ui/table";
import {
    Select, SelectContent, SelectItem,
    SelectTrigger, SelectValue,
} from "@/Components/ui/select";
import { Input } from "@/Components/ui/input";
import { Trash, ChevronUp, ChevronDown, RotateCcw } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";

const DeleteTypeModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm} className="p-6">
            <h2 className="text-lg font-medium">
                Are you sure you want to permanently delete this certificate type?
            </h2>
            <p className="mt-2 text-sm text-gray-500">This action cannot be undone.</p>
            <div className="mt-6 flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={onClose}>Cancel</Button>
                <Button type="submit" variant="destructive" disabled={processing}>Delete</Button>
            </div>
        </form>
    </Modal>
);

const DeleteAllTypesModal = ({ show, onClose, onConfirm, processing, count }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm} className="p-6">
            <h2 className="text-lg font-medium">
                Permanently delete {count} selected certificate type{count !== 1 ? "s" : ""}?
            </h2>
            <p className="mt-2 text-sm text-gray-500">This action cannot be undone.</p>
            <div className="mt-6 flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={onClose}>Cancel</Button>
                <Button type="submit" variant="destructive" disabled={processing}>Permanently Delete Selected</Button>
            </div>
        </form>
    </Modal>
);

const RestoreTypeModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm} className="p-6">
            <h2 className="text-lg font-medium">Restore this certificate type?</h2>
            <div className="mt-6 flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={onClose}>Cancel</Button>
                <Button type="submit" disabled={processing}>Restore</Button>
            </div>
        </form>
    </Modal>
);

const RestoreAllTypesModal = ({ show, onClose, onConfirm, processing, count }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm} className="p-6">
            <h2 className="text-lg font-medium">
                Restore {count} selected certificate type{count !== 1 ? "s" : ""}?
            </h2>
            <div className="mt-6 flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={onClose}>Cancel</Button>
                <Button type="submit" disabled={processing}>Restore Selected</Button>
            </div>
        </form>
    </Modal>
);

export default function CertTypesTrash({ certificateTypes = [], meta = {}, filters = {} }) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();

    const [selected, setSelected] = useState([]);
    const [isAllSelected, setIsAllSelected] = useState(false);
    const [search, setSearch] = useState(filters.search || "");
    const [perPage, setPerPage] = useState(meta.per_page || 10);
    const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
    const [sorting, setSorting] = useState(filters.sorting || { column: "name", direction: "asc" });
    const [bulkAction, setBulkAction] = useState("");
    const [processing, setProcessing] = useState(false);

    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
    const [typeToDelete, setTypeToDelete] = useState(null);

    const [showRestoreModal, setShowRestoreModal] = useState(false);
    const [showRestoreAllModal, setShowRestoreAllModal] = useState(false);
    const [typeToRestore, setTypeToRestore] = useState(null);

    useEffect(() => {
        if (flash?.success) toast({ title: "Success", description: flash.success });
        if (flash?.error) toast({ variant: "destructive", title: "Error", description: flash.error });
    }, [flash]);

    const nav = (extra = {}) =>
        router.get(
            route("underwriting_configuration.certificate_types.trash"),
            { search, per_page: perPage, sorting, ...extra },
            { preserveState: true }
        );

    const toggleAll = () => {
        if (isAllSelected) { setSelected([]); } else { setSelected(certificateTypes.map((t) => t.id)); }
        setIsAllSelected(!isAllSelected);
    };

    const toggleOne = (id) => {
        if (selected.includes(id)) {
            setSelected(selected.filter((x) => x !== id));
            setIsAllSelected(false);
        } else {
            const next = [...selected, id];
            setSelected(next);
            if (next.length === certificateTypes.length) setIsAllSelected(true);
        }
    };

    const handleSearch = (e) => {
        const value = e.target.value;
        setSearch(value);
        nav({ search: value, page: 1 });
    };

    const handlePerPage = (value) => {
        setPerPage(value);
        nav({ per_page: value, page: 1 });
    };

    const handlePage = (page) => {
        setCurrentPage(page);
        nav({ page });
    };

    const handleSort = (column) => {
        const direction = sorting.column === column && sorting.direction === "asc" ? "desc" : "asc";
        setSorting({ column, direction });
        nav({ sorting: { column, direction } });
    };

    const sortIcon = (column) => {
        const active = sorting.column === column;
        return (
            <span className="inline-flex flex-col ml-1">
                <ChevronUp className={`w-3 h-3 ${active && sorting.direction === "asc" ? "text-gray-800" : "text-gray-300"}`} />
                <ChevronDown className={`w-3 h-3 -mt-1 ${active && sorting.direction === "desc" ? "text-gray-800" : "text-gray-300"}`} />
            </span>
        );
    };

    const handleBulkAction = () => {
        if (!bulkAction) return;
        if (selected.length === 0) {
            toast({ variant: "destructive", title: "Error", description: "Please select at least one certificate type." });
            return;
        }
        if (bulkAction === "delete") setShowDeleteAllModal(true);
        else if (bulkAction === "restore") setShowRestoreAllModal(true);
    };

    const handleDelete = (e) => {
        e.preventDefault();
        setProcessing(true);
        router.delete(route("underwriting_configuration.certificate_types.permanent_destroy", typeToDelete), {
            onSuccess: () => { setShowDeleteModal(false); setTypeToDelete(null); setProcessing(false); },
            onError: () => setProcessing(false),
        });
    };

    const handleDeleteAll = (e) => {
        e.preventDefault();
        setProcessing(true);
        router.post(route("underwriting_configuration.certificate_types.bulk_permanent_destroy"), { ids: selected }, {
            onSuccess: () => { setShowDeleteAllModal(false); setSelected([]); setIsAllSelected(false); setProcessing(false); },
            onError: () => setProcessing(false),
        });
    };

    const handleRestore = (e) => {
        e.preventDefault();
        setProcessing(true);
        router.post(route("underwriting_configuration.certificate_types.restore", typeToRestore), {}, {
            onSuccess: () => { setShowRestoreModal(false); setTypeToRestore(null); setProcessing(false); },
            onError: () => setProcessing(false),
        });
    };

    const handleRestoreAll = (e) => {
        e.preventDefault();
        setProcessing(true);
        router.post(route("underwriting_configuration.certificate_types.bulk_restore"), { ids: selected }, {
            onSuccess: () => { setShowRestoreAllModal(false); setSelected([]); setIsAllSelected(false); setProcessing(false); },
            onError: () => setProcessing(false),
        });
    };

    const renderPageNumbers = () => {
        const total = meta.last_page || 1;
        const max = 5;
        let start = Math.max(1, currentPage - Math.floor(max / 2));
        let end = Math.min(total, start + max - 1);
        if (end - start + 1 < max) start = Math.max(1, end - max + 1);
        return Array.from({ length: end - start + 1 }, (_, i) => start + i).map((p) => (
            <Button key={p} variant={p === currentPage ? "default" : "outline"} size="sm" onClick={() => handlePage(p)} className="mx-1">
                {p}
            </Button>
        ));
    };

    return (
        <AuthenticatedLayout>
            <Toaster />
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Underwriting Configuration"
                        subpage="Certificate Types Trash"
                        url="underwriting_configuration.index"
                    />
                    <div className="p-4">
                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                            <div className="text-red-500 text-sm">
                                Total trashed certificate types: {meta.total ?? 0}
                            </div>
                            <Input
                                placeholder="Search certificate types..."
                                value={search}
                                onChange={handleSearch}
                                className="w-full md:w-80"
                            />
                        </div>

                        <div className="mb-4 flex flex-col md:flex-row gap-4 justify-between">
                            <div className="flex items-center gap-2">
                                <Select value={bulkAction} onValueChange={setBulkAction}>
                                    <SelectTrigger className="w-[200px]">
                                        <SelectValue placeholder="Bulk actions" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="restore">Restore Selected</SelectItem>
                                        <SelectItem value="delete">Permanently Delete Selected</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Button onClick={handleBulkAction} variant="outline">Apply</Button>
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="text-sm text-gray-500">Show</span>
                                <Select value={perPage.toString()} onValueChange={handlePerPage}>
                                    <SelectTrigger className="w-[80px]">
                                        <SelectValue />
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
                                            <Checkbox checked={isAllSelected} onCheckedChange={toggleAll} />
                                        </TableHead>
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("id")}>
                                            ID {sortIcon("id")}
                                        </TableHead>
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("name")}>
                                            Name {sortIcon("name")}
                                        </TableHead>
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("slug")}>
                                            Slug {sortIcon("slug")}
                                        </TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {certificateTypes.length > 0 ? (
                                        certificateTypes.map((type) => (
                                            <TableRow key={type.id}>
                                                <TableCell>
                                                    <Checkbox
                                                        checked={selected.includes(type.id)}
                                                        onCheckedChange={() => toggleOne(type.id)}
                                                    />
                                                </TableCell>
                                                <TableCell>{type.id}</TableCell>
                                                <TableCell>{type.name}</TableCell>
                                                <TableCell>
                                                    <span className="font-mono text-xs bg-gray-100 px-2 py-0.5 rounded">
                                                        {type.slug}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <TableActions
                                                        actions={[
                                                            {
                                                                label: "Restore",
                                                                icon: <RotateCcw className="h-4 w-4" />,
                                                                onClick: () => { setTypeToRestore(type.id); setShowRestoreModal(true); },
                                                            },
                                                            {
                                                                label: "Permanently Delete",
                                                                icon: <Trash className="h-4 w-4" />,
                                                                onClick: () => { setTypeToDelete(type.id); setShowDeleteModal(true); },
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
                                                No trashed certificate types found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {meta.total > 0 && (
                            <div className="flex items-center justify-between mt-4">
                                <div className="text-sm text-gray-500">
                                    Showing {(currentPage - 1) * perPage + 1} to {Math.min(currentPage * perPage, meta.total)} of {meta.total} entries
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Button variant="outline" size="sm" onClick={() => handlePage(1)} disabled={currentPage === 1}>First</Button>
                                    <Button variant="outline" size="sm" onClick={() => handlePage(currentPage - 1)} disabled={currentPage === 1}>Previous</Button>
                                    {renderPageNumbers()}
                                    <Button variant="outline" size="sm" onClick={() => handlePage(currentPage + 1)} disabled={currentPage === meta.last_page}>Next</Button>
                                    <Button variant="outline" size="sm" onClick={() => handlePage(meta.last_page)} disabled={currentPage === meta.last_page}>Last</Button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </SidebarInset>

            <DeleteTypeModal show={showDeleteModal} onClose={() => setShowDeleteModal(false)} onConfirm={handleDelete} processing={processing} />
            <DeleteAllTypesModal show={showDeleteAllModal} onClose={() => setShowDeleteAllModal(false)} onConfirm={handleDeleteAll} processing={processing} count={selected.length} />
            <RestoreTypeModal show={showRestoreModal} onClose={() => setShowRestoreModal(false)} onConfirm={handleRestore} processing={processing} />
            <RestoreAllTypesModal show={showRestoreAllModal} onClose={() => setShowRestoreAllModal(false)} onConfirm={handleRestoreAll} processing={processing} count={selected.length} />
        </AuthenticatedLayout>
    );
}
