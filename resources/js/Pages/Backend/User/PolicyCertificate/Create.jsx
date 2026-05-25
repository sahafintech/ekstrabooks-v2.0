import { useState } from "react";
import { router } from "@inertiajs/react";
import { Label } from "@/Components/ui/label";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset, SidebarSeparator } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Input } from "@/Components/ui/input";
import { Button } from "@/Components/ui/button";
import { Textarea } from "@/Components/ui/textarea";
import { toast } from "sonner";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { Plus, Trash2, GripVertical, Lock } from "lucide-react";
import InputError from "@/Components/InputError";
import DateTimePicker from "@/Components/DateTimePicker";

const SECTION_TYPES = [
    { id: "fields", name: "Fields (Key-Value Pairs)" },
    { id: "table", name: "Table" },
    { id: "text", name: "Text" },
    { id: "note", name: "Note" },
    { id: "terms", name: "Terms & Conditions" },
    { id: "exclusions", name: "Exclusions" },
    { id: "signature", name: "Signature" },
];

const makeSection = (sortOrder) => ({
    title: "",
    type: "fields",
    sort_order: sortOrder,
    fields: [{ label: "", value: "" }],
    columns: [""],
    rows: [[""]],
    content: "",
    _locked: false,
});

const buildCertNumber = (prefix, slug, increment) => {
    const year = new Date().getFullYear();
    return [prefix, slug, year, increment].filter(Boolean).join("/");
};

// Strip the frontend-only _locked flag before submitting
const stripMeta = (sections) =>
    sections.map(({ _locked, ...s }) => s);

export default function Create({ cert_prefix = "", cert_increment = 1, policy_prefix = "", policy_increment = 1, certificateTypes = [], customers = [] }) {
    const [basic, setBasic] = useState({
        customer_id: "",
        certificate_type: "",
        policy_start_date: null,
        policy_end_date: null,
    });

    const formatDate = (date) =>
        date ? date.toLocaleDateString("en-CA") : "";
    const [errors, setErrors] = useState({});
    const [sections, setSections] = useState([]);
    const [processing, setProcessing] = useState(false);

    // ── Type selection: auto-populate from template ──────────────────

    const handleTypeChange = (typeId) => {
        setBasic((prev) => ({ ...prev, certificate_type: typeId }));
        const type = certificateTypes.find((t) => String(t.id) === String(typeId));
        if (!type || !type.sections || type.sections.length === 0) {
            setSections([]);
            return;
        }
        setSections(
            type.sections.map((s, i) => ({
                title:      s.title,
                type:       s.type,
                sort_order: i,
                // fields: map default_value → value so the existing submit logic works
                fields:  (s.fields  ?? []).map((f) => ({ label: f.label, value: f.default_value ?? "" })),
                columns: s.columns ?? [""],
                rows:    s.rows    ?? [[""]],
                content: s.content ?? "",
                _locked: true,
            }))
        );
    };

    // ── Section helpers (only for unlocked sections) ─────────────────

    const addSection = () => setSections((prev) => [...prev, makeSection(prev.length)]);

    const removeSection = (si) =>
        setSections((prev) => prev.filter((_, i) => i !== si));

    const updateSection = (si, key, value) =>
        setSections((prev) => {
            const next = [...prev];
            next[si] = { ...next[si], [key]: value };
            return next;
        });

    // ── Fields helpers ───────────────────────────────────────────────

    const addField = (si) =>
        setSections((prev) => {
            const next = [...prev];
            next[si] = { ...next[si], fields: [...next[si].fields, { label: "", value: "" }] };
            return next;
        });

    const removeField = (si, fi) =>
        setSections((prev) => {
            const next = [...prev];
            next[si] = { ...next[si], fields: next[si].fields.filter((_, i) => i !== fi) };
            return next;
        });

    const updateField = (si, fi, key, value) =>
        setSections((prev) => {
            const next = [...prev];
            const fields = [...next[si].fields];
            fields[fi] = { ...fields[fi], [key]: value };
            next[si] = { ...next[si], fields };
            return next;
        });

    // ── Table helpers ────────────────────────────────────────────────

    const addColumn = (si) =>
        setSections((prev) => {
            const next = [...prev];
            const columns = [...next[si].columns, ""];
            const rows = next[si].rows.map((r) => [...r, ""]);
            next[si] = { ...next[si], columns, rows };
            return next;
        });

    const removeColumn = (si, ci) =>
        setSections((prev) => {
            const next = [...prev];
            const columns = next[si].columns.filter((_, i) => i !== ci);
            const rows = next[si].rows.map((r) => r.filter((_, i) => i !== ci));
            next[si] = { ...next[si], columns, rows };
            return next;
        });

    const updateColumn = (si, ci, value) =>
        setSections((prev) => {
            const next = [...prev];
            const columns = [...next[si].columns];
            columns[ci] = value;
            next[si] = { ...next[si], columns };
            return next;
        });

    const addRow = (si) =>
        setSections((prev) => {
            const next = [...prev];
            const emptyRow = Array(next[si].columns.length).fill("");
            next[si] = { ...next[si], rows: [...next[si].rows, emptyRow] };
            return next;
        });

    const removeRow = (si, ri) =>
        setSections((prev) => {
            const next = [...prev];
            next[si] = { ...next[si], rows: next[si].rows.filter((_, i) => i !== ri) };
            return next;
        });

    const updateCell = (si, ri, ci, value) =>
        setSections((prev) => {
            const next = [...prev];
            const rows = next[si].rows.map((r, i) => {
                if (i !== ri) return r;
                const row = [...r];
                row[ci] = value;
                return row;
            });
            next[si] = { ...next[si], rows };
            return next;
        });

    // ── Submit ───────────────────────────────────────────────────────

    const submit = (e) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        router.post(
            route("policy_certificates.store"),
            {
                customer_id:       basic.customer_id,
                certificate_type:  basic.certificate_type,
                policy_start_date: formatDate(basic.policy_start_date),
                policy_end_date:   formatDate(basic.policy_end_date),
                sections:          stripMeta(sections),
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success("Policy certificate created successfully");
                },
                onError: (errs) => {
                    setErrors(errs);
                    setProcessing(false);
                },
                onFinish: () => setProcessing(false),
            }
        );
    };

    // ── Render ───────────────────────────────────────────────────────

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader
                    page="Policy Certificates"
                    subpage="Add New"
                    url="policy_certificates.index"
                />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <form onSubmit={submit}>

                        {/* ── Basic Info ───────────────────────────── */}

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="customer_id" className="md:col-span-2 col-span-12">
                                Customer *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <SearchableCombobox
                                        options={customers}
                                        value={basic.customer_id}
                                        onChange={(v) => setBasic({ ...basic, customer_id: v })}
                                        placeholder="Select customer"
                                        emptyMessage="No customers found."
                                    />
                                </div>
                                <InputError message={errors.customer_id} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="certificate_type" className="md:col-span-2 col-span-12">
                                Certificate Type *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <SearchableCombobox
                                        options={certificateTypes}
                                        value={basic.certificate_type}
                                        onChange={handleTypeChange}
                                        placeholder="Select certificate type"
                                        emptyMessage="No certificate types found. Add them in Underwriting Configuration."
                                    />
                                </div>
                                <InputError message={errors.certificate_type} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label className="md:col-span-2 col-span-12">
                                Certificate Number
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2 flex items-center gap-2">
                                <span className="inline-flex items-center px-3 py-1.5 rounded-md border bg-gray-100 text-sm font-mono font-semibold text-gray-700 select-all">
                                    {basic.certificate_type
                                        ? buildCertNumber(
                                            cert_prefix,
                                            certificateTypes.find((t) => String(t.id) === String(basic.certificate_type))?.slug ?? "",
                                            cert_increment
                                        )
                                        : <span className="text-gray-400 font-normal">Select a certificate type first</span>
                                    }
                                </span>
                                <span className="text-xs text-gray-400">Auto-generated</span>
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label className="md:col-span-2 col-span-12">
                                Policy Number
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2 flex items-center gap-2">
                                <span className="inline-flex items-center px-3 py-1.5 rounded-md border bg-gray-100 text-sm font-mono font-semibold text-gray-700 select-all">
                                    {basic.certificate_type
                                        ? buildCertNumber(
                                            policy_prefix,
                                            certificateTypes.find((t) => String(t.id) === String(basic.certificate_type))?.slug ?? "",
                                            policy_increment
                                        )
                                        : <span className="text-gray-400 font-normal">Select a certificate type first</span>
                                    }
                                </span>
                                <span className="text-xs text-gray-400">Auto-generated</span>
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="policy_start_date" className="md:col-span-2 col-span-12">
                                Policy Start Date *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <DateTimePicker
                                    value={basic.policy_start_date}
                                    onChange={(date) => setBasic({ ...basic, policy_start_date: date })}
                                    className="md:w-1/2 w-full"
                                />
                                <InputError message={errors.policy_start_date} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="policy_end_date" className="md:col-span-2 col-span-12">
                                Policy End Date *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <DateTimePicker
                                    value={basic.policy_end_date}
                                    onChange={(date) => setBasic({ ...basic, policy_end_date: date })}
                                    className="md:w-1/2 w-full"
                                />
                                <InputError message={errors.policy_end_date} className="text-sm" />
                            </div>
                        </div>

                        <SidebarSeparator className="my-6" />

                        {/* ── Dynamic Sections ─────────────────────── */}

                        <div className="space-y-4">
                            <div className="flex justify-between items-center">
                                <h3 className="text-lg font-semibold">Certificate Sections</h3>
                                <Button type="button" variant="secondary" onClick={addSection}>
                                    <Plus className="w-4 h-4 mr-2" />
                                    Add Section
                                </Button>
                            </div>

                            {sections.map((section, si) => {
                                const locked = section._locked;
                                return (
                                    <div key={si} className={`border rounded-lg ${locked ? "bg-blue-50/40" : "bg-gray-50"}`}>
                                        {/* Section header */}
                                        <div className="flex items-center gap-3 p-4 border-b bg-white rounded-t-lg">
                                            {locked
                                                ? <Lock className="w-4 h-4 text-blue-400 shrink-0" title="Structure defined by template" />
                                                : <GripVertical className="w-4 h-4 text-gray-400 shrink-0" />
                                            }
                                            <div className="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3">
                                                <div>
                                                    <Label className="text-xs text-gray-500 mb-1 block">Section Title</Label>
                                                    <Input
                                                        value={section.title}
                                                        disabled={locked}
                                                        onChange={(e) => updateSection(si, "title", e.target.value)}
                                                        placeholder="e.g. INSURED DETAILS"
                                                        className=""
                                                    />
                                                </div>
                                                <div>
                                                    <Label className="text-xs text-gray-500 mb-1 block">Section Type</Label>
                                                    {locked
                                                        ? <Input value={SECTION_TYPES.find((t) => t.id === section.type)?.name ?? section.type} disabled />
                                                        : <SearchableCombobox options={SECTION_TYPES} value={section.type} onChange={(v) => updateSection(si, "type", v)} placeholder="Select type" />
                                                    }
                                                </div>
                                            </div>
                                            {!locked && (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="text-red-500 hover:text-red-700 shrink-0"
                                                    onClick={() => removeSection(si)}
                                                >
                                                    <Trash2 className="w-4 h-4" />
                                                </Button>
                                            )}
                                        </div>

                                        {/* Section body */}
                                        <div className="p-4">

                                            {/* ── fields type ─────────────── */}
                                            {section.type === "fields" && (
                                                <div className="space-y-2">
                                                    {section.fields.map((field, fi) => (
                                                        <div key={fi} className="flex items-center gap-2">
                                                            <Input
                                                                value={field.label}
                                                                disabled={locked}
                                                                onChange={(e) => updateField(si, fi, "label", e.target.value)}
                                                                placeholder="Label"
                                                                className="w-1/3"
                                                            />
                                                            <Input
                                                                value={field.value}
                                                                onChange={(e) => updateField(si, fi, "value", e.target.value)}
                                                                placeholder="Value"
                                                                className="flex-1"
                                                            />
                                                            {!locked && (
                                                                <Button
                                                                    type="button"
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="text-red-500 hover:text-red-700 shrink-0"
                                                                    onClick={() => removeField(si, fi)}
                                                                    disabled={section.fields.length === 1}
                                                                >
                                                                    <Trash2 className="w-4 h-4" />
                                                                </Button>
                                                            )}
                                                        </div>
                                                    ))}
                                                    {!locked && (
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => addField(si)}
                                                        >
                                                            <Plus className="w-3 h-3 mr-1" />
                                                            Add Field
                                                        </Button>
                                                    )}
                                                </div>
                                            )}

                                            {/* ── table type ──────────────── */}
                                            {section.type === "table" && (
                                                <div className="space-y-3">
                                                    {/* Column headers */}
                                                    <div>
                                                        <Label className="text-xs text-gray-500 mb-2 block">Column Headers</Label>
                                                        <div className="flex flex-wrap gap-2">
                                                            {section.columns.map((col, ci) => (
                                                                <div key={ci} className="flex items-center gap-1">
                                                                    <Input
                                                                        value={col}
                                                                        disabled={locked}
                                                                        onChange={(e) => updateColumn(si, ci, e.target.value)}
                                                                        placeholder={`Column ${ci + 1}`}
                                                                        className="w-36"
                                                                    />
                                                                    {!locked && (
                                                                        <Button
                                                                            type="button"
                                                                            variant="ghost"
                                                                            size="icon"
                                                                            className="text-red-500 hover:text-red-700 shrink-0"
                                                                            onClick={() => removeColumn(si, ci)}
                                                                            disabled={section.columns.length === 1}
                                                                        >
                                                                            <Trash2 className="w-3 h-3" />
                                                                        </Button>
                                                                    )}
                                                                </div>
                                                            ))}
                                                            {!locked && (
                                                                <Button
                                                                    type="button"
                                                                    variant="outline"
                                                                    size="sm"
                                                                    onClick={() => addColumn(si)}
                                                                >
                                                                    <Plus className="w-3 h-3 mr-1" />
                                                                    Add Column
                                                                </Button>
                                                            )}
                                                        </div>
                                                    </div>

                                                    {/* Rows */}
                                                    <div className="overflow-x-auto">
                                                        <table className="w-full text-sm border-collapse">
                                                            <thead>
                                                                <tr className="bg-gray-100">
                                                                    {section.columns.map((col, ci) => (
                                                                        <th key={ci} className="border px-2 py-1 text-left font-medium text-gray-600">
                                                                            {col || `Column ${ci + 1}`}
                                                                        </th>
                                                                    ))}
                                                                    <th className="border px-2 py-1 w-10" />
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                {section.rows.map((row, ri) => (
                                                                    <tr key={ri}>
                                                                        {row.map((cell, ci) => (
                                                                            <td key={ci} className="border px-1 py-1">
                                                                                <Input
                                                                                    value={cell}
                                                                                    onChange={(e) => updateCell(si, ri, ci, e.target.value)}
                                                                                    className="border-0 shadow-none focus-visible:ring-0 h-7 px-1"
                                                                                    placeholder="—"
                                                                                />
                                                                            </td>
                                                                        ))}
                                                                        <td className="border px-1 py-1 text-center">
                                                                            <Button
                                                                                type="button"
                                                                                variant="ghost"
                                                                                size="icon"
                                                                                className="text-red-500 hover:text-red-700 h-7 w-7"
                                                                                onClick={() => removeRow(si, ri)}
                                                                                disabled={section.rows.length === 1}
                                                                            >
                                                                                <Trash2 className="w-3 h-3" />
                                                                            </Button>
                                                                        </td>
                                                                    </tr>
                                                                ))}
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => addRow(si)}
                                                    >
                                                        <Plus className="w-3 h-3 mr-1" />
                                                        Add Row
                                                    </Button>
                                                </div>
                                            )}

                                            {/* ── text / note / terms / exclusions / signature ── */}
                                            {["text", "note", "terms", "exclusions", "signature"].includes(section.type) && (
                                                <Textarea
                                                    value={section.content}
                                                    onChange={(e) => updateSection(si, "content", e.target.value)}
                                                    placeholder={`Enter ${section.type} content...`}
                                                    rows={5}
                                                    className="w-full"
                                                />
                                            )}
                                        </div>
                                    </div>
                                );
                            })}

                            {sections.length === 0 && (
                                <p className="text-sm text-gray-400 text-center py-6 border border-dashed rounded-lg">
                                    {basic.certificate_type
                                        ? "No template sections defined for this type. Click \"Add Section\" to add sections manually."
                                        : "Select a certificate type above to auto-load its sections, or click \"Add Section\" to add manually."
                                    }
                                </p>
                            )}
                        </div>

                        <SidebarSeparator className="my-6" />

                        <Button type="submit" disabled={processing}>
                            {processing ? "Saving..." : "Create Certificate"}
                        </Button>
                    </form>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
