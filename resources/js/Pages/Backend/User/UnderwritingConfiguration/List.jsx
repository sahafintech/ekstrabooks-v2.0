import { useForm, usePage, router } from "@inertiajs/react";
import { useEffect, useState } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Label } from "@/Components/ui/label";
import { Input } from "@/Components/ui/input";
import { Button } from "@/Components/ui/button";
import InputError from "@/Components/InputError";
import { Tabs, TabsList, TabsTrigger, TabsContent } from "@/Components/ui/tabs";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import Modal from "@/Components/Modal";
import { Checkbox } from "@/Components/ui/checkbox";
import {
    Table, TableBody, TableCell,
    TableHead, TableHeader, TableRow,
} from "@/Components/ui/table";
import {
    Select, SelectContent, SelectItem,
    SelectTrigger, SelectValue,
} from "@/Components/ui/select";
import { Pencil, Trash2, Plus, ChevronUp, ChevronDown, LayoutTemplate } from "lucide-react";
import { Link } from "@inertiajs/react";

// ── Slug auto-generator ───────────────────────────────────────────
const generateSlug = (name) =>
    name.replace(/[^a-zA-Z]/g, "").slice(0, 3).toUpperCase();

// ── Create Modal ─────────────────────────────────────────────────
function CreateTypeModal({ show, onClose, errors }) {
    const { data, setData, post, processing, reset } = useForm({ name: "", slug: "" });

    const handleNameChange = (value) =>
        setData((prev) => ({ ...prev, name: value, slug: generateSlug(value) }));

    const submit = (e) => {
        e.preventDefault();
        post(route("underwriting_configuration.certificate_types.store"), {
            preserveScroll: true,
            onSuccess: () => { reset(); onClose(); },
        });
    };

    useEffect(() => { if (!show) reset(); }, [show]);

    return (
        <Modal show={show} onClose={onClose} maxWidth="md">
            <form onSubmit={submit} className="p-6">
                <h2 className="text-lg font-semibold mb-4">Add Certificate Type</h2>
                <div className="space-y-4">
                    <div>
                        <Label htmlFor="create_name">Name *</Label>
                        <Input id="create_name" value={data.name} onChange={(e) => handleNameChange(e.target.value)} placeholder="e.g. Motor" className="mt-1" required />
                        <InputError message={errors.name} className="mt-1" />
                    </div>
                    <div>
                        <Label htmlFor="create_slug">Slug (3 letters, auto-generated)</Label>
                        <div className="flex items-center gap-2 mt-1">
                            <Input
                                id="create_slug" value={data.slug}
                                onChange={(e) => setData("slug", e.target.value.replace(/[^a-zA-Z]/g, "").slice(0, 3).toUpperCase())}
                                placeholder="MOT" maxLength={3} className="w-24 font-mono uppercase"
                            />
                            <span className="text-xs text-gray-400">Auto-generated · editable</span>
                        </div>
                        <InputError message={errors.slug} className="mt-1" />
                    </div>
                </div>
                <div className="flex justify-end gap-2 mt-6">
                    <Button type="button" variant="secondary" onClick={onClose}>Cancel</Button>
                    <Button type="submit" disabled={processing}>{processing ? "Saving..." : "Create"}</Button>
                </div>
            </form>
        </Modal>
    );
}

// ── Edit Modal ───────────────────────────────────────────────────
function EditTypeModal({ show, onClose, certificateType, errors }) {
    const { data, setData, put, processing, reset } = useForm({ name: "", slug: "" });

    useEffect(() => {
        if (certificateType) setData({ name: certificateType.name, slug: certificateType.slug });
        if (!show) reset();
    }, [certificateType, show]);

    const handleNameChange = (value) =>
        setData((prev) => ({ ...prev, name: value, slug: generateSlug(value) }));

    const submit = (e) => {
        e.preventDefault();
        put(route("underwriting_configuration.certificate_types.update", certificateType.id), {
            preserveScroll: true,
            onSuccess: () => onClose(),
        });
    };

    return (
        <Modal show={show} onClose={onClose} maxWidth="md">
            <form onSubmit={submit} className="p-6">
                <h2 className="text-lg font-semibold mb-4">Edit Certificate Type</h2>
                <div className="space-y-4">
                    <div>
                        <Label htmlFor="edit_name">Name *</Label>
                        <Input id="edit_name" value={data.name} onChange={(e) => handleNameChange(e.target.value)} placeholder="e.g. Motor" className="mt-1" required />
                        <InputError message={errors.name} className="mt-1" />
                    </div>
                    <div>
                        <Label htmlFor="edit_slug">Slug (3 letters, auto-generated)</Label>
                        <div className="flex items-center gap-2 mt-1">
                            <Input
                                id="edit_slug" value={data.slug}
                                onChange={(e) => setData("slug", e.target.value.replace(/[^a-zA-Z]/g, "").slice(0, 3).toUpperCase())}
                                placeholder="MOT" maxLength={3} className="w-24 font-mono uppercase"
                            />
                            <span className="text-xs text-gray-400">Auto-generated · editable</span>
                        </div>
                        <InputError message={errors.slug} className="mt-1" />
                    </div>
                </div>
                <div className="flex justify-end gap-2 mt-6">
                    <Button type="button" variant="secondary" onClick={onClose}>Cancel</Button>
                    <Button type="submit" disabled={processing}>{processing ? "Saving..." : "Update"}</Button>
                </div>
            </form>
        </Modal>
    );
}

// ── Delete Single Modal ───────────────────────────────────────────
function DeleteTypeModal({ show, onClose, certificateType }) {
    const [processing, setProcessing] = useState(false);
    const confirm = (e) => {
        e.preventDefault();
        setProcessing(true);
        router.delete(
            route("underwriting_configuration.certificate_types.destroy", certificateType.id),
            { preserveScroll: true, onSuccess: () => { onClose(); setProcessing(false); }, onError: () => setProcessing(false) }
        );
    };
    return (
        <Modal show={show} onClose={onClose} maxWidth="sm">
            <form onSubmit={confirm} className="p-6">
                <h2 className="text-lg font-medium">Delete &ldquo;{certificateType?.name}&rdquo;?</h2>
                <p className="mt-1 text-sm text-gray-600">It will be moved to the trash and can be restored later.</p>
                <div className="flex justify-end gap-2 mt-6">
                    <Button type="button" variant="secondary" onClick={onClose}>Cancel</Button>
                    <Button type="submit" variant="destructive" disabled={processing}>Delete</Button>
                </div>
            </form>
        </Modal>
    );
}

// ── Bulk Delete Modal ─────────────────────────────────────────────
function DeleteAllTypesModal({ show, onClose, onConfirm, processing, count }) {
    return (
        <Modal show={show} onClose={onClose} maxWidth="sm">
            <form onSubmit={onConfirm} className="p-6">
                <h2 className="text-lg font-medium">
                    Delete {count} selected type{count !== 1 ? "s" : ""}?
                </h2>
                <p className="mt-1 text-sm text-gray-600">They will be moved to the trash.</p>
                <div className="flex justify-end gap-2 mt-6">
                    <Button type="button" variant="secondary" onClick={onClose}>Cancel</Button>
                    <Button type="submit" variant="destructive" disabled={processing}>Delete Selected</Button>
                </div>
            </form>
        </Modal>
    );
}

// ── Main Page ─────────────────────────────────────────────────────
export default function List({
    settings = {},
    certificateTypes = [],
    certMeta = {},
    certFilters = {},
    certTrashedCount = 0,
    defaultTab = "policy_certificate",
}) {
    const { flash = {}, errors = {} } = usePage().props;
    const { toast } = useToast();

    // ── Tab state (controlled so URL navigations restore the tab) ───
    const [activeTab, setActiveTab] = useState(defaultTab);

    // ── Settings form ───────────────────────────────────────────────
    const { data, setData, post, processing, errors: settingsErrors } = useForm({
        cert_number_prefix:    settings.cert_number_prefix    ?? "",
        cert_number_increment: settings.cert_number_increment ?? "1",
        policy_number_prefix:    settings.policy_number_prefix    ?? "",
        policy_number_increment: settings.policy_number_increment ?? "1",
        invoice_primary_color: settings.invoice_primary_color ?? "#6b1f2a",
        invoice_text_color:    settings.invoice_text_color    ?? "#ffffff",
    });

    // ── Certificate Types list state ────────────────────────────────
    const [certSearch, setCertSearch]         = useState(certFilters.search || "");
    const [certPerPage, setCertPerPage]       = useState(certMeta.per_page || 10);
    const [certCurrentPage, setCertCurrentPage] = useState(certMeta.current_page || 1);
    const [certSorting, setCertSorting]       = useState(certFilters.sorting || { column: "name", direction: "asc" });
    const [certSelectedIds, setCertSelectedIds] = useState([]);
    const [certIsAllSelected, setCertIsAllSelected] = useState(false);
    const [certBulkAction, setCertBulkAction] = useState("");

    // ── Cert type modal state ───────────────────────────────────────
    const [showCreate, setShowCreate]     = useState(false);
    const [showEdit,   setShowEdit]       = useState(false);
    const [showDelete, setShowDelete]     = useState(false);
    const [showDeleteAll, setShowDeleteAll] = useState(false);
    const [activeType, setActiveType]     = useState(null);
    const [certDeleteProcessing, setCertDeleteProcessing] = useState(false);

    useEffect(() => {
        if (flash?.success) toast({ title: "Success", description: flash.success });
        if (flash?.error)   toast({ variant: "destructive", title: "Error", description: flash.error });
    }, [flash]);

    // ── Settings submit ─────────────────────────────────────────────
    const submitSettings = (e) => {
        e.preventDefault();
        post(route("underwriting_configuration.store"), { preserveScroll: true });
    };

    // ── Cert types navigation helper ────────────────────────────────
    const certNav = (params) => {
        router.get(
            route("underwriting_configuration.index"),
            { tab: "certificate_types", cert_search: certSearch, cert_per_page: certPerPage, cert_sorting: certSorting, ...params },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleCertSearch = (e) => {
        const value = e.target.value;
        setCertSearch(value);
        certNav({ cert_search: value, cert_page: 1 });
    };

    const handleCertPerPage = (value) => {
        setCertPerPage(value);
        certNav({ cert_per_page: value, cert_page: 1 });
    };

    const handleCertPageChange = (page) => {
        setCertCurrentPage(page);
        certNav({ cert_page: page });
    };

    const handleCertSort = (column) => {
        const direction = certSorting.column === column && certSorting.direction === "asc" ? "desc" : "asc";
        const newSorting = { column, direction };
        setCertSorting(newSorting);
        certNav({ cert_sorting: newSorting });
    };

    const renderCertSortIcon = (column) => {
        const isActive = certSorting.column === column;
        return (
            <span className="inline-flex flex-col ml-1">
                <ChevronUp className={`w-3 h-3 ${isActive && certSorting.direction === "asc" ? "text-gray-800" : "text-gray-300"}`} />
                <ChevronDown className={`w-3 h-3 -mt-1 ${isActive && certSorting.direction === "desc" ? "text-gray-800" : "text-gray-300"}`} />
            </span>
        );
    };

    // ── Cert types selection ────────────────────────────────────────
    const toggleCertSelectAll = () => {
        if (certIsAllSelected) {
            setCertSelectedIds([]);
        } else {
            setCertSelectedIds(certificateTypes.map((t) => t.id));
        }
        setCertIsAllSelected(!certIsAllSelected);
    };

    const toggleCertSelect = (id) => {
        if (certSelectedIds.includes(id)) {
            setCertSelectedIds(certSelectedIds.filter((i) => i !== id));
            setCertIsAllSelected(false);
        } else {
            const next = [...certSelectedIds, id];
            setCertSelectedIds(next);
            if (next.length === certificateTypes.length) setCertIsAllSelected(true);
        }
    };

    const handleCertBulkAction = () => {
        if (!certBulkAction) return;
        if (certSelectedIds.length === 0) {
            toast({ variant: "destructive", title: "Error", description: "Please select at least one certificate type." });
            return;
        }
        if (certBulkAction === "delete") setShowDeleteAll(true);
    };

    const handleCertDeleteAll = (e) => {
        e.preventDefault();
        setCertDeleteProcessing(true);
        router.post(
            route("underwriting_configuration.certificate_types.bulk_destroy"),
            { ids: certSelectedIds },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setShowDeleteAll(false);
                    setCertSelectedIds([]);
                    setCertIsAllSelected(false);
                    setCertDeleteProcessing(false);
                },
                onError: () => setCertDeleteProcessing(false),
            }
        );
    };

    // ── Cert types pagination ───────────────────────────────────────
    const renderCertPageNumbers = () => {
        const totalPages = certMeta.last_page || 1;
        const maxPages   = 5;
        let start = Math.max(1, certCurrentPage - Math.floor(maxPages / 2));
        let end   = Math.min(totalPages, start + maxPages - 1);
        if (end - start < maxPages - 1) start = Math.max(1, end - maxPages + 1);
        const pages = [];
        for (let i = start; i <= end; i++) {
            pages.push(
                <Button key={i} variant={i === certCurrentPage ? "default" : "outline"} size="sm" onClick={() => handleCertPageChange(i)} className="mx-1">{i}</Button>
            );
        }
        return pages;
    };

    return (
        <AuthenticatedLayout>
            <Toaster />
            <SidebarInset>
                <PageHeader page="Underwriting" subpage="Configuration" url="underwriting_configuration.index" />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <Tabs value={activeTab} onValueChange={setActiveTab}>
                        <TabsList>
                            <TabsTrigger value="policy_certificate">Policy Certificate</TabsTrigger>
                            <TabsTrigger value="certificate_types">Certificate Types</TabsTrigger>
                        </TabsList>

                        {/* ── Policy Certificate Tab ─────────────── */}
                        <TabsContent value="policy_certificate">
                            <form onSubmit={submitSettings} className="mt-4">

                                {/* Certificate Settings */}
                                <h2 className="text-base font-semibold mb-4">Certificate Settings</h2>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="cert_number_prefix" className="col-span-12 md:col-span-3 flex items-center">
                                        Certificate Number Prefix
                                    </Label>
                                    <div className="col-span-12 md:col-span-9 md:ml-4 mt-2 md:mt-0">
                                        <Input id="cert_number_prefix" type="text" value={data.cert_number_prefix} onChange={(e) => setData("cert_number_prefix", e.target.value)} placeholder="e.g. AMANAH" className="md:w-1/2 w-full" />
                                        <p className="text-xs text-gray-500 mt-1">Prepended to every generated certificate number.</p>
                                        <InputError message={settingsErrors.cert_number_prefix} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-6">
                                    <Label htmlFor="cert_number_increment" className="col-span-12 md:col-span-3 flex items-center">
                                        Certificate Number Increment
                                    </Label>
                                    <div className="col-span-12 md:col-span-9 md:ml-4 mt-2 md:mt-0">
                                        <Input id="cert_number_increment" type="number" min="1" value={data.cert_number_increment} onChange={(e) => setData("cert_number_increment", e.target.value)} placeholder="e.g. 1001" className="md:w-1/2 w-full" />
                                        <p className="text-xs text-gray-500 mt-1">The next certificate will be assigned this number. Auto-increments on each issue.</p>
                                        <InputError message={settingsErrors.cert_number_increment} className="mt-1" />
                                    </div>
                                </div>

                                <hr className="my-6" />

                                {/* Policy Settings */}
                                <h2 className="text-base font-semibold mb-4">Policy Settings</h2>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="policy_number_prefix" className="col-span-12 md:col-span-3 flex items-center">
                                        Policy Number Prefix
                                    </Label>
                                    <div className="col-span-12 md:col-span-9 md:ml-4 mt-2 md:mt-0">
                                        <Input id="policy_number_prefix" type="text" value={data.policy_number_prefix} onChange={(e) => setData("policy_number_prefix", e.target.value)} placeholder="e.g. POL" className="md:w-1/2 w-full" />
                                        <p className="text-xs text-gray-500 mt-1">Prepended to every generated policy number.</p>
                                        <InputError message={settingsErrors.policy_number_prefix} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-6">
                                    <Label htmlFor="policy_number_increment" className="col-span-12 md:col-span-3 flex items-center">
                                        Policy Number Increment
                                    </Label>
                                    <div className="col-span-12 md:col-span-9 md:ml-4 mt-2 md:mt-0">
                                        <Input id="policy_number_increment" type="number" min="1" value={data.policy_number_increment} onChange={(e) => setData("policy_number_increment", e.target.value)} placeholder="e.g. 1001" className="md:w-1/2 w-full" />
                                        <p className="text-xs text-gray-500 mt-1">The next policy will be assigned this number. Auto-increments on each issue.</p>
                                        <InputError message={settingsErrors.policy_number_increment} className="mt-1" />
                                    </div>
                                </div>

                                <hr className="my-6" />

                                {/* Certificate Colors */}
                                <h2 className="text-base font-semibold mb-4">Certificate Colors</h2>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="invoice_primary_color" className="col-span-12 md:col-span-3 flex items-center">
                                        Primary Color
                                    </Label>
                                    <div className="col-span-12 md:col-span-9 md:ml-4 mt-2 md:mt-0">
                                        <div className="flex items-center gap-3">
                                            <input
                                                id="invoice_primary_color"
                                                type="color"
                                                value={data.invoice_primary_color}
                                                onChange={(e) => setData("invoice_primary_color", e.target.value)}
                                                className="h-10 w-16 cursor-pointer rounded border border-gray-300 p-1"
                                            />
                                            <span className="text-sm font-mono text-gray-600">{data.invoice_primary_color}</span>
                                        </div>
                                        <p className="text-xs text-gray-500 mt-1">Used for borders, section headers, and footer backgrounds on certificates.</p>
                                        <InputError message={settingsErrors.invoice_primary_color} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-6">
                                    <Label htmlFor="invoice_text_color" className="col-span-12 md:col-span-3 flex items-center">
                                        Text Color
                                    </Label>
                                    <div className="col-span-12 md:col-span-9 md:ml-4 mt-2 md:mt-0">
                                        <div className="flex items-center gap-3">
                                            <input
                                                id="invoice_text_color"
                                                type="color"
                                                value={data.invoice_text_color}
                                                onChange={(e) => setData("invoice_text_color", e.target.value)}
                                                className="h-10 w-16 cursor-pointer rounded border border-gray-300 p-1"
                                            />
                                            <span className="text-sm font-mono text-gray-600">{data.invoice_text_color}</span>
                                        </div>
                                        <p className="text-xs text-gray-500 mt-1">Text color used on top of the primary color background.</p>
                                        <InputError message={settingsErrors.invoice_text_color} className="mt-1" />
                                    </div>
                                </div>

                                {/* Color Preview */}
                                <div className="mb-6">
                                    <Label className="mb-2 block">Preview</Label>
                                    <div
                                        className="inline-flex items-center gap-4 rounded border-4 px-4 py-2 text-sm font-semibold"
                                        style={{ borderColor: data.invoice_primary_color, backgroundColor: data.invoice_primary_color, color: data.invoice_text_color }}
                                    >
                                        <span>Section Header</span>
                                        <span>·</span>
                                        <span>Footer Bar</span>
                                    </div>
                                </div>

                                <Button type="submit" disabled={processing}>{processing ? "Saving..." : "Save Settings"}</Button>
                            </form>
                        </TabsContent>

                        {/* ── Certificate Types Tab ─────────────────── */}
                        <TabsContent value="certificate_types">
                            <div className="mt-4">

                                {/* Toolbar */}
                                <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                                    <div className="flex flex-col md:flex-row gap-2">
                                        <Button onClick={() => setShowCreate(true)}>
                                            <Plus className="w-4 h-4 mr-2" />
                                            Add Type
                                        </Button>
                                        <Link href={route("underwriting_configuration.certificate_types.trash")}>
                                            <Button variant="outline" className="relative">
                                                <Trash2 className="h-5 w-5" />
                                                {certTrashedCount > 0 && (
                                                    <span className="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-6 w-6 flex items-center justify-center font-bold">
                                                        {certTrashedCount}
                                                    </span>
                                                )}
                                            </Button>
                                        </Link>
                                    </div>
                                    <Input
                                        placeholder="Search types..."
                                        value={certSearch}
                                        onChange={handleCertSearch}
                                        className="w-full md:w-72"
                                    />
                                </div>

                                {/* Bulk + per-page bar */}
                                <div className="mb-4 flex flex-col md:flex-row gap-4 justify-between">
                                    <div className="flex items-center gap-2">
                                        <Select value={certBulkAction} onValueChange={setCertBulkAction}>
                                            <SelectTrigger className="w-[180px]"><SelectValue placeholder="Bulk actions" /></SelectTrigger>
                                            <SelectContent><SelectItem value="delete">Delete Selected</SelectItem></SelectContent>
                                        </Select>
                                        <Button onClick={handleCertBulkAction} variant="outline">Apply</Button>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <span className="text-sm text-gray-500">Show</span>
                                        <Select value={certPerPage.toString()} onValueChange={handleCertPerPage}>
                                            <SelectTrigger className="w-[80px]"><SelectValue /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="10">10</SelectItem>
                                                <SelectItem value="25">25</SelectItem>
                                                <SelectItem value="50">50</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <span className="text-sm text-gray-500">entries</span>
                                    </div>
                                </div>

                                {/* Table */}
                                <div className="rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead className="w-[50px]">
                                                    <Checkbox checked={certIsAllSelected} onCheckedChange={toggleCertSelectAll} />
                                                </TableHead>
                                                <TableHead className="cursor-pointer" onClick={() => handleCertSort("id")}>
                                                    # {renderCertSortIcon("id")}
                                                </TableHead>
                                                <TableHead className="cursor-pointer" onClick={() => handleCertSort("name")}>
                                                    Name {renderCertSortIcon("name")}
                                                </TableHead>
                                                <TableHead className="cursor-pointer" onClick={() => handleCertSort("slug")}>
                                                    Slug {renderCertSortIcon("slug")}
                                                </TableHead>
                                                <TableHead className="text-right">Actions</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {certificateTypes.length > 0 ? (
                                                certificateTypes.map((type, index) => (
                                                    <TableRow key={type.id}>
                                                        <TableCell>
                                                            <Checkbox checked={certSelectedIds.includes(type.id)} onCheckedChange={() => toggleCertSelect(type.id)} />
                                                        </TableCell>
                                                        <TableCell>{(certCurrentPage - 1) * certPerPage + index + 1}</TableCell>
                                                        <TableCell>{type.name}</TableCell>
                                                        <TableCell>
                                                            <span className="font-mono text-xs bg-gray-100 border px-2 py-0.5 rounded">{type.slug}</span>
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            <div className="flex justify-end gap-2">
                                                                <Link href={route("underwriting_configuration.certificate_types.layout", type.id)}>
                                                                    <Button variant="outline" size="icon" title="Configure Layout">
                                                                        <LayoutTemplate className="h-4 w-4" />
                                                                    </Button>
                                                                </Link>
                                                                <Button variant="outline" size="icon" onClick={() => { setActiveType(type); setShowEdit(true); }}>
                                                                    <Pencil className="h-4 w-4" />
                                                                </Button>
                                                                <Button variant="outline" size="icon" className="text-red-500 hover:text-red-700" onClick={() => { setActiveType(type); setShowDelete(true); }}>
                                                                    <Trash2 className="h-4 w-4" />
                                                                </Button>
                                                            </div>
                                                        </TableCell>
                                                    </TableRow>
                                                ))
                                            ) : (
                                                <TableRow>
                                                    <TableCell colSpan={5} className="h-24 text-center text-gray-400">
                                                        No certificate types found.
                                                    </TableCell>
                                                </TableRow>
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>

                                {/* Pagination */}
                                {certificateTypes.length > 0 && certMeta.total > 0 && (
                                    <div className="flex items-center justify-between mt-4">
                                        <div className="text-sm text-gray-500">
                                            Showing {(certCurrentPage - 1) * certPerPage + 1} to {Math.min(certCurrentPage * certPerPage, certMeta.total)} of {certMeta.total} entries
                                        </div>
                                        <div className="flex items-center space-x-2">
                                            <Button variant="outline" size="sm" onClick={() => handleCertPageChange(1)} disabled={certCurrentPage === 1}>First</Button>
                                            <Button variant="outline" size="sm" onClick={() => handleCertPageChange(certCurrentPage - 1)} disabled={certCurrentPage === 1}>Previous</Button>
                                            {renderCertPageNumbers()}
                                            <Button variant="outline" size="sm" onClick={() => handleCertPageChange(certCurrentPage + 1)} disabled={certCurrentPage === certMeta.last_page}>Next</Button>
                                            <Button variant="outline" size="sm" onClick={() => handleCertPageChange(certMeta.last_page)} disabled={certCurrentPage === certMeta.last_page}>Last</Button>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </TabsContent>
                    </Tabs>
                </div>
            </SidebarInset>

            <CreateTypeModal show={showCreate} onClose={() => setShowCreate(false)} errors={errors} />
            <EditTypeModal   show={showEdit}   onClose={() => setShowEdit(false)}   certificateType={activeType} errors={errors} />
            <DeleteTypeModal show={showDelete} onClose={() => setShowDelete(false)} certificateType={activeType} />
            <DeleteAllTypesModal
                show={showDeleteAll}
                onClose={() => setShowDeleteAll(false)}
                onConfirm={handleCertDeleteAll}
                processing={certDeleteProcessing}
                count={certSelectedIds.length}
            />
        </AuthenticatedLayout>
    );
}
