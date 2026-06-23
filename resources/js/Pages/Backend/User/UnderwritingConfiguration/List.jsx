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
import { Pencil, Trash2, Plus, ChevronUp, ChevronDown, LayoutTemplate, ToggleLeft, ToggleRight } from "lucide-react";
import { Link } from "@inertiajs/react";
import TableActions from "@/Components/shared/TableActions";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import DateTimePicker from "@/Components/DateTimePicker";

const generateSlug = (name) =>
    name.replace(/[^a-zA-Z]/g, "").slice(0, 3).toUpperCase();

// ── Rating Rule constants ─────────────────────────────────────────
const CALCULATION_TYPES = [
    { id: "percentage_of_amount", name: "% of Amount" },
    { id: "fixed_per_quantity",   name: "Fixed per Quantity" },
    { id: "fixed_amount",         name: "Fixed Amount" },
    { id: "manual_premium",       name: "Manual Premium" },
    { id: "tiered_rate",          name: "Tiered Rate" },
    { id: "contribution_table",   name: "Contribution Table" },
];
const RATE_TYPES = [
    { id: "percentage", name: "Percentage" },
    { id: "fixed",      name: "Fixed" },
    { id: "manual",     name: "Manual" },
    { id: "range",      name: "Range" },
];

const formatRateNumber = (value, emptyValue = "-") => {
    if (value === null || value === undefined || value === "") return emptyValue;

    return String(value)
        .replace(/(\.\d*?[1-9])0+$/, "$1")
        .replace(/\.0+$/, "");
};

function CreateModal({ show, onClose, errors }) {
    const { data, setData, post, processing, reset } = useForm({ name: "", slug: "" });

    const handleNameChange = (value) =>
        setData((prev) => ({ ...prev, name: value, slug: generateSlug(value) }));

    const submit = (e) => {
        e.preventDefault();
        post(route("underwriting_configuration.insurance_categories.store"), {
            preserveScroll: true,
            onSuccess: () => { reset(); onClose(); },
        });
    };

    useEffect(() => { if (!show) reset(); }, [show]);

    return (
        <Modal show={show} onClose={onClose} maxWidth="md">
            <form onSubmit={submit} className="p-6">
                <h2 className="text-lg font-semibold mb-4">Add Insurance Category</h2>
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

function EditModal({ show, onClose, insuranceCategory, errors }) {
    const { data, setData, put, processing, reset } = useForm({ name: "", slug: "" });

    useEffect(() => {
        if (insuranceCategory) setData({ name: insuranceCategory.name, slug: insuranceCategory.slug });
        if (!show) reset();
    }, [insuranceCategory, show]);

    const handleNameChange = (value) =>
        setData((prev) => ({ ...prev, name: value, slug: generateSlug(value) }));

    const submit = (e) => {
        e.preventDefault();
        put(route("underwriting_configuration.insurance_categories.update", insuranceCategory.id), {
            preserveScroll: true,
            onSuccess: () => onClose(),
        });
    };

    return (
        <Modal show={show} onClose={onClose} maxWidth="md">
            <form onSubmit={submit} className="p-6">
                <h2 className="text-lg font-semibold mb-4">Edit Insurance Category</h2>
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

function DeleteModal({ show, onClose, insuranceCategory }) {
    const [processing, setProcessing] = useState(false);
    const confirm = (e) => {
        e.preventDefault();
        setProcessing(true);
        router.delete(
            route("underwriting_configuration.insurance_categories.destroy", insuranceCategory.id),
            { preserveScroll: true, onSuccess: () => { onClose(); setProcessing(false); }, onError: () => setProcessing(false) }
        );
    };
    return (
        <Modal show={show} onClose={onClose} maxWidth="sm">
            <form onSubmit={confirm} className="p-6">
                <h2 className="text-lg font-medium">Delete &ldquo;{insuranceCategory?.name}&rdquo;?</h2>
                <p className="mt-1 text-sm text-gray-600">It will be moved to the trash and can be restored later.</p>
                <div className="flex justify-end gap-2 mt-6">
                    <Button type="button" variant="secondary" onClick={onClose}>Cancel</Button>
                    <Button type="submit" variant="destructive" disabled={processing}>Delete</Button>
                </div>
            </form>
        </Modal>
    );
}

function DeleteAllModal({ show, onClose, onConfirm, processing, count }) {
    return (
        <Modal show={show} onClose={onClose} maxWidth="sm">
            <form onSubmit={onConfirm} className="p-6">
                <h2 className="text-lg font-medium">Delete {count} selected categor{count !== 1 ? "ies" : "y"}?</h2>
                <p className="mt-1 text-sm text-gray-600">They will be moved to the trash.</p>
                <div className="flex justify-end gap-2 mt-6">
                    <Button type="button" variant="secondary" onClick={onClose}>Cancel</Button>
                    <Button type="submit" variant="destructive" disabled={processing}>Delete Selected</Button>
                </div>
            </form>
        </Modal>
    );
}

// ── Rating Rule modals ────────────────────────────────────────────
function RuleFormFields({ data, setData, errors, allInsuranceCategories, allProducts }) {
    return (
        <div className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <Label>Insurance Category *</Label>
                    <SearchableCombobox
                        options={allInsuranceCategories.map((c) => ({ id: c.id, name: c.name }))}
                        value={String(data.insurance_category_id || "")}
                        onChange={(v) => setData("insurance_category_id", v)}
                        placeholder="Select category"
                        className="mt-1 w-full"
                    />
                    <InputError message={errors.insurance_category_id} className="mt-1" />
                </div>
                <div>
                    <Label>Product (optional)</Label>
                    <SearchableCombobox
                        options={[
                            { id: "0", name: "— Any product —" },
                            ...allProducts.map((p) => ({ id: p.id, name: p.name })),
                        ]}
                        value={String(data.product_id || "0")}
                        onChange={(v) => setData("product_id", v === "0" || v === "" ? "" : v)}
                        placeholder="Any product"
                        className="mt-1 w-full"
                    />
                    <InputError message={errors.product_id} className="mt-1" />
                </div>
            </div>
            <div>
                <Label>Rule Name *</Label>
                <Input value={data.name} onChange={(e) => setData("name", e.target.value)} placeholder="e.g. Travel Basic Rate" className="mt-1" required />
                <InputError message={errors.name} className="mt-1" />
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <Label>Calculation Type *</Label>
                    <SearchableCombobox
                        options={CALCULATION_TYPES}
                        value={data.calculation_type}
                        onChange={(v) => setData("calculation_type", v)}
                        placeholder="Select type"
                        className="mt-1 w-full"
                    />
                    <InputError message={errors.calculation_type} className="mt-1" />
                </div>
                <div>
                    <Label>Rate Type *</Label>
                    <SearchableCombobox
                        options={RATE_TYPES}
                        value={data.rate_type}
                        onChange={(v) => setData("rate_type", v)}
                        placeholder="Select type"
                        className="mt-1 w-full"
                    />
                    <InputError message={errors.rate_type} className="mt-1" />
                </div>
            </div>
            <div className="grid grid-cols-3 gap-3">
                <div>
                    <Label>Min Rate</Label>
                    <Input type="number" step="any" value={data.min_rate} onChange={(e) => setData("min_rate", e.target.value)} placeholder="0" className="mt-1" />
                    <InputError message={errors.min_rate} className="mt-1" />
                </div>
                <div>
                    <Label>Max Rate</Label>
                    <Input type="number" step="any" value={data.max_rate} onChange={(e) => setData("max_rate", e.target.value)} placeholder="0" className="mt-1" />
                    <InputError message={errors.max_rate} className="mt-1" />
                </div>
                <div>
                    <Label>Default Rate</Label>
                    <Input type="number" step="any" value={data.default_rate} onChange={(e) => setData("default_rate", e.target.value)} placeholder="0" className="mt-1" />
                    <InputError message={errors.default_rate} className="mt-1" />
                </div>
            </div>
            <div className="grid grid-cols-2 gap-4">
                <div>
                    <Label>Minimum Premium</Label>
                    <Input type="number" step="any" value={data.minimum_premium} onChange={(e) => setData("minimum_premium", e.target.value)} placeholder="0" className="mt-1" />
                    <InputError message={errors.minimum_premium} className="mt-1" />
                </div>
                <div>
                    <Label>Tax Rate (%)</Label>
                    <Input type="number" step="any" value={data.tax_rate} onChange={(e) => setData("tax_rate", e.target.value)} placeholder="0" className="mt-1" />
                    <InputError message={errors.tax_rate} className="mt-1" />
                </div>
            </div>
            <div className="grid grid-cols-3 gap-3">
                <div>
                    <Label>Currency</Label>
                    <Input value={data.currency} onChange={(e) => setData("currency", e.target.value)} placeholder="USD" className="mt-1" />
                    <InputError message={errors.currency} className="mt-1" />
                </div>
                <div>
                    <Label>Active From</Label>
                    <DateTimePicker
                        value={data.active_from || null}
                        onChange={(date) => setData("active_from", date ? date.toISOString().split("T")[0] : "")}
                        className="mt-1"
                        placeholder="Select date"
                    />
                    <InputError message={errors.active_from} className="mt-1" />
                </div>
                <div>
                    <Label>Active To</Label>
                    <DateTimePicker
                        value={data.active_to || null}
                        onChange={(date) => setData("active_to", date ? date.toISOString().split("T")[0] : "")}
                        className="mt-1"
                        placeholder="Select date"
                    />
                    <InputError message={errors.active_to} className="mt-1" />
                </div>
            </div>
            <div className="flex items-center gap-2">
                <Checkbox
                    id="rule_is_active"
                    checked={!!data.is_active}
                    onCheckedChange={(v) => setData("is_active", v)}
                />
                <Label htmlFor="rule_is_active">Active</Label>
            </div>
        </div>
    );
}

const EMPTY_RULE = {
    insurance_category_id: "", product_id: "",
    name: "", calculation_type: "percentage_of_amount", rate_type: "percentage",
    min_rate: "", max_rate: "", default_rate: "",
    minimum_premium: "", tax_rate: "", currency: "",
    active_from: "", active_to: "", is_active: true,
};

function CreateRuleModal({ show, onClose, errors, allInsuranceCategories, allProducts }) {
    const { data, setData, post, processing, reset } = useForm({ ...EMPTY_RULE });
    useEffect(() => { if (!show) reset(); }, [show]);
    const submit = (e) => {
        e.preventDefault();
        post(route("underwriting_configuration.rating_rules.store"), {
            preserveScroll: true,
            onSuccess: () => { reset(); onClose(); },
        });
    };
    return (
        <Modal show={show} onClose={onClose} maxWidth="4xl">
            <form onSubmit={submit} className="p-6 max-h-[85vh] overflow-y-auto">
                <h2 className="text-lg font-semibold mb-4">Add Rating Rule</h2>
                <RuleFormFields data={data} setData={setData} errors={errors} allInsuranceCategories={allInsuranceCategories} allProducts={allProducts} />
                <div className="flex justify-end gap-2 mt-6">
                    <Button type="button" variant="secondary" onClick={onClose}>Cancel</Button>
                    <Button type="submit" disabled={processing}>{processing ? "Saving..." : "Create"}</Button>
                </div>
            </form>
        </Modal>
    );
}

function EditRuleModal({ show, onClose, rule, errors, allInsuranceCategories, allProducts }) {
    const { data, setData, put, processing, reset } = useForm({ ...EMPTY_RULE });
    useEffect(() => {
        if (rule) setData({
            insurance_category_id: rule.insurance_category_id ?? "",
            product_id:            rule.product_id ?? "",
            name:                  rule.name ?? "",
            calculation_type:      rule.calculation_type ?? "percentage_of_amount",
            rate_type:             rule.rate_type ?? "percentage",
            min_rate:              rule.min_rate ?? "",
            max_rate:              rule.max_rate ?? "",
            default_rate:          formatRateNumber(rule.default_rate, ""),
            minimum_premium:       rule.minimum_premium ?? "",
            tax_rate:              rule.tax_rate ?? "",
            currency:              rule.currency ?? "",
            active_from:           rule.active_from ?? "",
            active_to:             rule.active_to ?? "",
            is_active:             rule.is_active ?? true,
        });
        if (!show) reset();
    }, [rule, show]);
    const submit = (e) => {
        e.preventDefault();
        put(route("underwriting_configuration.rating_rules.update", rule.id), {
            preserveScroll: true,
            onSuccess: () => onClose(),
        });
    };
    return (
        <Modal show={show} onClose={onClose} maxWidth="lg">
            <form onSubmit={submit} className="p-6 max-h-[85vh] overflow-y-auto">
                <h2 className="text-lg font-semibold mb-4">Edit Rating Rule</h2>
                <RuleFormFields data={data} setData={setData} errors={errors} allInsuranceCategories={allInsuranceCategories} allProducts={allProducts} />
                <div className="flex justify-end gap-2 mt-6">
                    <Button type="button" variant="secondary" onClick={onClose}>Cancel</Button>
                    <Button type="submit" disabled={processing}>{processing ? "Saving..." : "Update"}</Button>
                </div>
            </form>
        </Modal>
    );
}

function DeleteRuleModal({ show, onClose, rule }) {
    const [processing, setProcessing] = useState(false);
    const confirm = (e) => {
        e.preventDefault();
        setProcessing(true);
        router.delete(route("underwriting_configuration.rating_rules.destroy", rule.id), {
            preserveScroll: true,
            onSuccess: () => { onClose(); setProcessing(false); },
            onError: () => setProcessing(false),
        });
    };
    return (
        <Modal show={show} onClose={onClose} maxWidth="sm">
            <form onSubmit={confirm} className="p-6">
                <h2 className="text-lg font-medium">Delete &ldquo;{rule?.name}&rdquo;?</h2>
                <p className="mt-1 text-sm text-gray-600">This rating rule will be permanently removed.</p>
                <div className="flex justify-end gap-2 mt-6">
                    <Button type="button" variant="secondary" onClick={onClose}>Cancel</Button>
                    <Button type="submit" variant="destructive" disabled={processing}>Delete</Button>
                </div>
            </form>
        </Modal>
    );
}

function DeleteAllRulesModal({ show, onClose, onConfirm, processing, count }) {
    return (
        <Modal show={show} onClose={onClose} maxWidth="sm">
            <form onSubmit={onConfirm} className="p-6">
                <h2 className="text-lg font-medium">Delete {count} selected rule{count !== 1 ? "s" : ""}?</h2>
                <p className="mt-1 text-sm text-gray-600">These rating rules will be permanently removed.</p>
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
    insuranceCategories = [],
    certMeta = {},
    certFilters = {},
    certTrashedCount = 0,
    ratingRules = [],
    ratingRulesMeta = {},
    ratingRulesFilters = {},
    allInsuranceCategories = [],
    allProducts = [],
    defaultTab = "policy_certificate",
}) {
    const { flash = {}, errors = {} } = usePage().props;
    const { toast } = useToast();

    const [activeTab, setActiveTab] = useState(defaultTab);

    const { data, setData, post, processing, errors: settingsErrors } = useForm({
        cert_number_prefix:      settings.cert_number_prefix      ?? "",
        cert_number_increment:   settings.cert_number_increment   ?? "1",
        policy_number_prefix:    settings.policy_number_prefix    ?? "",
        policy_number_increment: settings.policy_number_increment ?? "1",
        invoice_primary_color:   settings.invoice_primary_color   ?? "#6b1f2a",
        invoice_text_color:      settings.invoice_text_color      ?? "#ffffff",
    });

    const [certSearch, setCertSearch]               = useState(certFilters.search || "");
    const [certPerPage, setCertPerPage]             = useState(certMeta.per_page || 10);
    const [certCurrentPage, setCertCurrentPage]     = useState(certMeta.current_page || 1);
    const [certSorting, setCertSorting]             = useState(certFilters.sorting || { column: "name", direction: "asc" });
    const [certSelectedIds, setCertSelectedIds]     = useState([]);
    const [certIsAllSelected, setCertIsAllSelected] = useState(false);
    const [certBulkAction, setCertBulkAction]       = useState("");
    const [showCreate, setShowCreate]               = useState(false);
    const [showEdit,   setShowEdit]                 = useState(false);
    const [showDelete, setShowDelete]               = useState(false);
    const [showDeleteAll, setShowDeleteAll]         = useState(false);
    const [activeItem, setActiveItem]               = useState(null);
    const [certDeleteProcessing, setCertDeleteProcessing] = useState(false);

    useEffect(() => {
        if (flash?.success) toast({ title: "Success", description: flash.success });
        if (flash?.error)   toast({ variant: "destructive", title: "Error", description: flash.error });
    }, [flash]);

    const submitSettings = (e) => {
        e.preventDefault();
        post(route("underwriting_configuration.store"), { preserveScroll: true });
    };

    const certNav = (params) => {
        router.get(
            route("underwriting_configuration.index"),
            { tab: "insurance_categories", cert_search: certSearch, cert_per_page: certPerPage, cert_sorting: certSorting, ...params },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleCertSearch = (e) => { const v = e.target.value; setCertSearch(v); certNav({ cert_search: v, cert_page: 1 }); };
    const handleCertPerPage = (v) => { setCertPerPage(v); certNav({ cert_per_page: v, cert_page: 1 }); };
    const handleCertPageChange = (page) => { setCertCurrentPage(page); certNav({ cert_page: page }); };
    const handleCertSort = (column) => {
        const direction = certSorting.column === column && certSorting.direction === "asc" ? "desc" : "asc";
        const s = { column, direction };
        setCertSorting(s);
        certNav({ cert_sorting: s });
    };
    const renderCertSortIcon = (column) => {
        const a = certSorting.column === column;
        return (
            <span className="inline-flex flex-col ml-1">
                <ChevronUp className={`w-3 h-3 ${a && certSorting.direction === "asc" ? "text-gray-800" : "text-gray-300"}`} />
                <ChevronDown className={`w-3 h-3 -mt-1 ${a && certSorting.direction === "desc" ? "text-gray-800" : "text-gray-300"}`} />
            </span>
        );
    };
    const toggleCertSelectAll = () => {
        if (certIsAllSelected) { setCertSelectedIds([]); } else { setCertSelectedIds(insuranceCategories.map((t) => t.id)); }
        setCertIsAllSelected(!certIsAllSelected);
    };
    const toggleCertSelect = (id) => {
        if (certSelectedIds.includes(id)) { setCertSelectedIds(certSelectedIds.filter((i) => i !== id)); setCertIsAllSelected(false); }
        else { const next = [...certSelectedIds, id]; setCertSelectedIds(next); if (next.length === insuranceCategories.length) setCertIsAllSelected(true); }
    };
    const handleCertBulkAction = () => {
        if (!certBulkAction) return;
        if (certSelectedIds.length === 0) { toast({ variant: "destructive", title: "Error", description: "Please select at least one insurance category." }); return; }
        if (certBulkAction === "delete") setShowDeleteAll(true);
    };
    const handleCertDeleteAll = (e) => {
        e.preventDefault();
        setCertDeleteProcessing(true);
        router.post(route("underwriting_configuration.insurance_categories.bulk_destroy"), { ids: certSelectedIds }, {
            preserveScroll: true,
            onSuccess: () => { setShowDeleteAll(false); setCertSelectedIds([]); setCertIsAllSelected(false); setCertDeleteProcessing(false); },
            onError: () => setCertDeleteProcessing(false),
        });
    };
    const renderCertPageNumbers = () => {
        const total = certMeta.last_page || 1, max = 5;
        let start = Math.max(1, certCurrentPage - Math.floor(max / 2));
        let end = Math.min(total, start + max - 1);
        if (end - start < max - 1) start = Math.max(1, end - max + 1);
        return Array.from({ length: end - start + 1 }, (_, i) => start + i).map((p) => (
            <Button key={p} variant={p === certCurrentPage ? "default" : "outline"} size="sm" onClick={() => handleCertPageChange(p)} className="mx-1">{p}</Button>
        ));
    };

    // ── Rating Rules state ────────────────────────────────────────
    const [ruleSearch, setRuleSearch]               = useState(ratingRulesFilters.search || "");
    const [rulePerPage, setRulePerPage]             = useState(ratingRulesMeta.per_page || 10);
    const [ruleCurrentPage, setRuleCurrentPage]     = useState(ratingRulesMeta.current_page || 1);
    const [ruleSorting, setRuleSorting]             = useState(ratingRulesFilters.sorting || { column: "name", direction: "asc" });
    const [ruleSelectedIds, setRuleSelectedIds]     = useState([]);
    const [ruleIsAllSelected, setRuleIsAllSelected] = useState(false);
    const [ruleBulkAction, setRuleBulkAction]       = useState("");
    const [showCreateRule, setShowCreateRule]               = useState(false);
    const [showEditRule,   setShowEditRule]                 = useState(false);
    const [showDeleteRule, setShowDeleteRule]               = useState(false);
    const [showDeleteAllRules, setShowDeleteAllRules]       = useState(false);
    const [activeRule, setActiveRule]               = useState(null);
    const [ruleDeleteProcessing, setRuleDeleteProcessing]   = useState(false);

    const ruleNav = (params) => {
        router.get(
            route("underwriting_configuration.index"),
            { tab: "rating_rules", rule_search: ruleSearch, rule_per_page: rulePerPage, rule_sorting: ruleSorting, ...params },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleRuleSearch = (e) => { const v = e.target.value; setRuleSearch(v); ruleNav({ rule_search: v, rule_page: 1 }); };
    const handleRulePerPage = (v) => { setRulePerPage(v); ruleNav({ rule_per_page: v, rule_page: 1 }); };
    const handleRulePageChange = (page) => { setRuleCurrentPage(page); ruleNav({ rule_page: page }); };
    const handleRuleSort = (column) => {
        const direction = ruleSorting.column === column && ruleSorting.direction === "asc" ? "desc" : "asc";
        const s = { column, direction };
        setRuleSorting(s);
        ruleNav({ rule_sorting: s });
    };
    const renderRuleSortIcon = (column) => {
        const a = ruleSorting.column === column;
        return (
            <span className="inline-flex flex-col ml-1">
                <ChevronUp className={`w-3 h-3 ${a && ruleSorting.direction === "asc" ? "text-gray-800" : "text-gray-300"}`} />
                <ChevronDown className={`w-3 h-3 -mt-1 ${a && ruleSorting.direction === "desc" ? "text-gray-800" : "text-gray-300"}`} />
            </span>
        );
    };
    const toggleRuleSelectAll = () => {
        if (ruleIsAllSelected) { setRuleSelectedIds([]); } else { setRuleSelectedIds(ratingRules.map((r) => r.id)); }
        setRuleIsAllSelected(!ruleIsAllSelected);
    };
    const toggleRuleSelect = (id) => {
        if (ruleSelectedIds.includes(id)) { setRuleSelectedIds(ruleSelectedIds.filter((i) => i !== id)); setRuleIsAllSelected(false); }
        else { const next = [...ruleSelectedIds, id]; setRuleSelectedIds(next); if (next.length === ratingRules.length) setRuleIsAllSelected(true); }
    };
    const handleRuleBulkAction = () => {
        if (!ruleBulkAction) return;
        if (ruleSelectedIds.length === 0) { toast({ variant: "destructive", title: "Error", description: "Please select at least one rating rule." }); return; }
        if (ruleBulkAction === "delete") setShowDeleteAllRules(true);
    };
    const handleRuleDeleteAll = (e) => {
        e.preventDefault();
        setRuleDeleteProcessing(true);
        router.post(route("underwriting_configuration.rating_rules.bulk_destroy"), { ids: ruleSelectedIds }, {
            preserveScroll: true,
            onSuccess: () => { setShowDeleteAllRules(false); setRuleSelectedIds([]); setRuleIsAllSelected(false); setRuleDeleteProcessing(false); },
            onError: () => setRuleDeleteProcessing(false),
        });
    };
    const renderRulePageNumbers = () => {
        const total = ratingRulesMeta.last_page || 1, max = 5;
        let start = Math.max(1, ruleCurrentPage - Math.floor(max / 2));
        let end = Math.min(total, start + max - 1);
        if (end - start < max - 1) start = Math.max(1, end - max + 1);
        return Array.from({ length: end - start + 1 }, (_, i) => start + i).map((p) => (
            <Button key={p} variant={p === ruleCurrentPage ? "default" : "outline"} size="sm" onClick={() => handleRulePageChange(p)} className="mx-1">{p}</Button>
        ));
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
                            <TabsTrigger value="insurance_categories">Insurance Categories</TabsTrigger>
                            <TabsTrigger value="rating_rules">Rating Rules</TabsTrigger>
                        </TabsList>

                        {/* ── Policy Certificate Tab ─────────────── */}
                        <TabsContent value="policy_certificate">
                            <form onSubmit={submitSettings} className="mt-4">
                                <h2 className="text-base font-semibold mb-4">Certificate Settings</h2>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="cert_number_prefix" className="col-span-12 md:col-span-3 flex items-center">Certificate Number Prefix</Label>
                                    <div className="col-span-12 md:col-span-9 md:ml-4 mt-2 md:mt-0">
                                        <Input id="cert_number_prefix" type="text" value={data.cert_number_prefix} onChange={(e) => setData("cert_number_prefix", e.target.value)} placeholder="e.g. AMANAH" className="md:w-1/2 w-full" />
                                        <p className="text-xs text-gray-500 mt-1">Prepended to every generated certificate number.</p>
                                        <InputError message={settingsErrors.cert_number_prefix} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-6">
                                    <Label htmlFor="cert_number_increment" className="col-span-12 md:col-span-3 flex items-center">Certificate Number Increment</Label>
                                    <div className="col-span-12 md:col-span-9 md:ml-4 mt-2 md:mt-0">
                                        <Input id="cert_number_increment" type="number" min="1" value={data.cert_number_increment} onChange={(e) => setData("cert_number_increment", e.target.value)} placeholder="e.g. 1001" className="md:w-1/2 w-full" />
                                        <p className="text-xs text-gray-500 mt-1">The next certificate will be assigned this number. Auto-increments on each issue.</p>
                                        <InputError message={settingsErrors.cert_number_increment} className="mt-1" />
                                    </div>
                                </div>

                                <hr className="my-6" />
                                <h2 className="text-base font-semibold mb-4">Policy Settings</h2>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="policy_number_prefix" className="col-span-12 md:col-span-3 flex items-center">Policy Number Prefix</Label>
                                    <div className="col-span-12 md:col-span-9 md:ml-4 mt-2 md:mt-0">
                                        <Input id="policy_number_prefix" type="text" value={data.policy_number_prefix} onChange={(e) => setData("policy_number_prefix", e.target.value)} placeholder="e.g. POL" className="md:w-1/2 w-full" />
                                        <p className="text-xs text-gray-500 mt-1">Prepended to every generated policy number.</p>
                                        <InputError message={settingsErrors.policy_number_prefix} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-6">
                                    <Label htmlFor="policy_number_increment" className="col-span-12 md:col-span-3 flex items-center">Policy Number Increment</Label>
                                    <div className="col-span-12 md:col-span-9 md:ml-4 mt-2 md:mt-0">
                                        <Input id="policy_number_increment" type="number" min="1" value={data.policy_number_increment} onChange={(e) => setData("policy_number_increment", e.target.value)} placeholder="e.g. 1001" className="md:w-1/2 w-full" />
                                        <p className="text-xs text-gray-500 mt-1">The next policy will be assigned this number. Auto-increments on each issue.</p>
                                        <InputError message={settingsErrors.policy_number_increment} className="mt-1" />
                                    </div>
                                </div>

                                <hr className="my-6" />
                                <h2 className="text-base font-semibold mb-4">Certificate Colors</h2>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="invoice_primary_color" className="col-span-12 md:col-span-3 flex items-center">Primary Color</Label>
                                    <div className="col-span-12 md:col-span-9 md:ml-4 mt-2 md:mt-0">
                                        <div className="flex items-center gap-3">
                                            <input id="invoice_primary_color" type="color" value={data.invoice_primary_color} onChange={(e) => setData("invoice_primary_color", e.target.value)} className="h-10 w-16 cursor-pointer rounded border border-gray-300 p-1" />
                                            <span className="text-sm font-mono text-gray-600">{data.invoice_primary_color}</span>
                                        </div>
                                        <p className="text-xs text-gray-500 mt-1">Used for borders, section headers, and footer backgrounds on certificates.</p>
                                        <InputError message={settingsErrors.invoice_primary_color} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-6">
                                    <Label htmlFor="invoice_text_color" className="col-span-12 md:col-span-3 flex items-center">Text Color</Label>
                                    <div className="col-span-12 md:col-span-9 md:ml-4 mt-2 md:mt-0">
                                        <div className="flex items-center gap-3">
                                            <input id="invoice_text_color" type="color" value={data.invoice_text_color} onChange={(e) => setData("invoice_text_color", e.target.value)} className="h-10 w-16 cursor-pointer rounded border border-gray-300 p-1" />
                                            <span className="text-sm font-mono text-gray-600">{data.invoice_text_color}</span>
                                        </div>
                                        <p className="text-xs text-gray-500 mt-1">Text color used on top of the primary color background.</p>
                                        <InputError message={settingsErrors.invoice_text_color} className="mt-1" />
                                    </div>
                                </div>

                                <div className="mb-6">
                                    <Label className="mb-2 block">Preview</Label>
                                    <div className="inline-flex items-center gap-4 rounded border-4 px-4 py-2 text-sm font-semibold" style={{ borderColor: data.invoice_primary_color, backgroundColor: data.invoice_primary_color, color: data.invoice_text_color }}>
                                        <span>Section Header</span><span>·</span><span>Footer Bar</span>
                                    </div>
                                </div>

                                <Button type="submit" disabled={processing}>{processing ? "Saving..." : "Save Settings"}</Button>
                            </form>
                        </TabsContent>

                        {/* ── Insurance Categories Tab ──────────────── */}
                        <TabsContent value="insurance_categories">
                            <div className="mt-4">
                                <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                                    <div className="flex flex-col md:flex-row gap-2">
                                        <Button onClick={() => setShowCreate(true)}>
                                            <Plus className="w-4 h-4 mr-2" />Add Category
                                        </Button>
                                        <Link href={route("underwriting_configuration.insurance_categories.trash")}>
                                            <Button variant="outline" className="relative">
                                                <Trash2 className="h-5 w-5" />
                                                {certTrashedCount > 0 && (
                                                    <span className="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-6 w-6 flex items-center justify-center font-bold">{certTrashedCount}</span>
                                                )}
                                            </Button>
                                        </Link>
                                    </div>
                                    <Input placeholder="Search categories..." value={certSearch} onChange={handleCertSearch} className="w-full md:w-72" />
                                </div>

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

                                <div className="rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead className="w-[50px]"><Checkbox checked={certIsAllSelected} onCheckedChange={toggleCertSelectAll} /></TableHead>
                                                <TableHead className="cursor-pointer" onClick={() => handleCertSort("id")}># {renderCertSortIcon("id")}</TableHead>
                                                <TableHead className="cursor-pointer" onClick={() => handleCertSort("name")}>Name {renderCertSortIcon("name")}</TableHead>
                                                <TableHead className="cursor-pointer" onClick={() => handleCertSort("slug")}>Slug {renderCertSortIcon("slug")}</TableHead>
                                                <TableHead className="text-right">Actions</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {insuranceCategories.length > 0 ? (
                                                insuranceCategories.map((item, index) => (
                                                    <TableRow key={item.id}>
                                                        <TableCell><Checkbox checked={certSelectedIds.includes(item.id)} onCheckedChange={() => toggleCertSelect(item.id)} /></TableCell>
                                                        <TableCell>{(certCurrentPage - 1) * certPerPage + index + 1}</TableCell>
                                                        <TableCell>{item.name}</TableCell>
                                                        <TableCell><span className="font-mono text-xs bg-gray-100 border px-2 py-0.5 rounded">{item.slug}</span></TableCell>
                                                        <TableCell className="text-right">
                                                            <TableActions actions={[
                                                                { label: "Layout", icon: <LayoutTemplate className="h-4 w-4" />, href: route("underwriting_configuration.insurance_categories.layout", item.id) },
                                                                { label: "Edit", icon: <Pencil className="h-4 w-4" />, onClick: () => { setActiveItem(item); setShowEdit(true); } },
                                                                { label: "Delete", icon: <Trash2 className="h-4 w-4" />, onClick: () => { setActiveItem(item); setShowDelete(true); }, destructive: true },
                                                            ]} />
                                                        </TableCell>
                                                    </TableRow>
                                                ))
                                            ) : (
                                                <TableRow><TableCell colSpan={5} className="h-24 text-center text-gray-400">No insurance categories found.</TableCell></TableRow>
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>

                                {insuranceCategories.length > 0 && certMeta.total > 0 && (
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

                        {/* ── Rating Rules Tab ──────────────────────── */}
                        <TabsContent value="rating_rules">
                            <div className="mt-4">
                                <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                                    <Button onClick={() => setShowCreateRule(true)}>
                                        <Plus className="w-4 h-4 mr-2" />Add Rating Rule
                                    </Button>
                                    <Input placeholder="Search rules..." value={ruleSearch} onChange={handleRuleSearch} className="w-full md:w-72" />
                                </div>

                                <div className="mb-4 flex flex-col md:flex-row gap-4 justify-between">
                                    <div className="flex items-center gap-2">
                                        <Select value={ruleBulkAction} onValueChange={setRuleBulkAction}>
                                            <SelectTrigger className="w-[180px]"><SelectValue placeholder="Bulk actions" /></SelectTrigger>
                                            <SelectContent><SelectItem value="delete">Delete Selected</SelectItem></SelectContent>
                                        </Select>
                                        <Button onClick={handleRuleBulkAction} variant="outline">Apply</Button>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <span className="text-sm text-gray-500">Show</span>
                                        <Select value={rulePerPage.toString()} onValueChange={handleRulePerPage}>
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

                                <div className="rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead className="w-[50px]"><Checkbox checked={ruleIsAllSelected} onCheckedChange={toggleRuleSelectAll} /></TableHead>
                                                <TableHead className="cursor-pointer" onClick={() => handleRuleSort("id")}># {renderRuleSortIcon("id")}</TableHead>
                                                <TableHead className="cursor-pointer" onClick={() => handleRuleSort("name")}>Name {renderRuleSortIcon("name")}</TableHead>
                                                <TableHead>Category</TableHead>
                                                <TableHead>Calculation</TableHead>
                                                <TableHead>Rate Type</TableHead>
                                                <TableHead className="cursor-pointer" onClick={() => handleRuleSort("default_rate")}>Default Rate {renderRuleSortIcon("default_rate")}</TableHead>
                                                <TableHead>Status</TableHead>
                                                <TableHead className="text-right">Actions</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {ratingRules.length > 0 ? (
                                                ratingRules.map((rule, index) => (
                                                    <TableRow key={rule.id}>
                                                        <TableCell><Checkbox checked={ruleSelectedIds.includes(rule.id)} onCheckedChange={() => toggleRuleSelect(rule.id)} /></TableCell>
                                                        <TableCell>{(ruleCurrentPage - 1) * rulePerPage + index + 1}</TableCell>
                                                        <TableCell className="font-medium">{rule.name}</TableCell>
                                                        <TableCell className="text-sm text-gray-600">{rule.insurance_category?.name ?? "—"}</TableCell>
                                                        <TableCell>
                                                            <span className="text-xs font-mono bg-blue-50 text-blue-700 border border-blue-200 px-2 py-0.5 rounded">
                                                                {CALCULATION_TYPES.find((t) => t.id === rule.calculation_type)?.name ?? rule.calculation_type}
                                                            </span>
                                                        </TableCell>
                                                        <TableCell>
                                                            <span className="text-xs font-mono bg-gray-100 border px-2 py-0.5 rounded">
                                                                {RATE_TYPES.find((t) => t.id === rule.rate_type)?.name ?? rule.rate_type}
                                                            </span>
                                                        </TableCell>
                                                        <TableCell>{formatRateNumber(rule.default_rate)}</TableCell>
                                                        <TableCell>
                                                            {rule.is_active
                                                                ? <span className="inline-flex items-center gap-1 text-xs text-green-700"><ToggleRight className="w-4 h-4" />Active</span>
                                                                : <span className="inline-flex items-center gap-1 text-xs text-gray-400"><ToggleLeft className="w-4 h-4" />Inactive</span>
                                                            }
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            <TableActions actions={[
                                                                { label: "Edit",   icon: <Pencil className="h-4 w-4" />, onClick: () => { setActiveRule(rule); setShowEditRule(true); } },
                                                                { label: "Delete", icon: <Trash2 className="h-4 w-4" />, onClick: () => { setActiveRule(rule); setShowDeleteRule(true); }, destructive: true },
                                                            ]} />
                                                        </TableCell>
                                                    </TableRow>
                                                ))
                                            ) : (
                                                <TableRow><TableCell colSpan={9} className="h-24 text-center text-gray-400">No rating rules found.</TableCell></TableRow>
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>

                                {ratingRules.length > 0 && ratingRulesMeta.total > 0 && (
                                    <div className="flex items-center justify-between mt-4">
                                        <div className="text-sm text-gray-500">
                                            Showing {(ruleCurrentPage - 1) * rulePerPage + 1} to {Math.min(ruleCurrentPage * rulePerPage, ratingRulesMeta.total)} of {ratingRulesMeta.total} entries
                                        </div>
                                        <div className="flex items-center space-x-2">
                                            <Button variant="outline" size="sm" onClick={() => handleRulePageChange(1)} disabled={ruleCurrentPage === 1}>First</Button>
                                            <Button variant="outline" size="sm" onClick={() => handleRulePageChange(ruleCurrentPage - 1)} disabled={ruleCurrentPage === 1}>Previous</Button>
                                            {renderRulePageNumbers()}
                                            <Button variant="outline" size="sm" onClick={() => handleRulePageChange(ruleCurrentPage + 1)} disabled={ruleCurrentPage === ratingRulesMeta.last_page}>Next</Button>
                                            <Button variant="outline" size="sm" onClick={() => handleRulePageChange(ratingRulesMeta.last_page)} disabled={ruleCurrentPage === ratingRulesMeta.last_page}>Last</Button>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </TabsContent>
                    </Tabs>
                </div>
            </SidebarInset>

            <CreateModal  show={showCreate} onClose={() => setShowCreate(false)} errors={errors} />
            <EditModal    show={showEdit}   onClose={() => setShowEdit(false)}   insuranceCategory={activeItem} errors={errors} />
            <DeleteModal  show={showDelete} onClose={() => setShowDelete(false)} insuranceCategory={activeItem} />
            <DeleteAllModal
                show={showDeleteAll}
                onClose={() => setShowDeleteAll(false)}
                onConfirm={handleCertDeleteAll}
                processing={certDeleteProcessing}
                count={certSelectedIds.length}
            />
            <CreateRuleModal
                show={showCreateRule}
                onClose={() => setShowCreateRule(false)}
                errors={errors}
                allInsuranceCategories={allInsuranceCategories}
                allProducts={allProducts}
            />
            <EditRuleModal
                show={showEditRule}
                onClose={() => setShowEditRule(false)}
                rule={activeRule}
                errors={errors}
                allInsuranceCategories={allInsuranceCategories}
                allProducts={allProducts}
            />
            <DeleteRuleModal  show={showDeleteRule}    onClose={() => setShowDeleteRule(false)}    rule={activeRule} />
            <DeleteAllRulesModal
                show={showDeleteAllRules}
                onClose={() => setShowDeleteAllRules(false)}
                onConfirm={handleRuleDeleteAll}
                processing={ruleDeleteProcessing}
                count={ruleSelectedIds.length}
            />
        </AuthenticatedLayout>
    );
}
