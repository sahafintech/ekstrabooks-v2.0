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
    { id: "fields",     name: "Fields (Key-Value Pairs)" },
    { id: "table",      name: "Table" },
    { id: "text",       name: "Text" },
    { id: "note",       name: "Note" },
    { id: "terms",      name: "Terms & Conditions" },
    { id: "exclusions", name: "Exclusions" },
    { id: "signature",  name: "Signature" },
];

const makeSection = (sortOrder) => ({
    title: "", type: "fields", sort_order: sortOrder,
    fields: [{ label: "", value: "" }], columns: [""], rows: [[""]], content: "",
    _locked: false,
});

const parseDate = (str) => (str ? new Date(str + "T00:00:00") : null);
const formatDate = (date) => (date ? date.toLocaleDateString("en-CA") : "");

const stripMeta = (sections) =>
    sections.map(({ _locked, ...s }) => s);

export default function Edit({ certificate, insuranceCategories = [], customers = [], initialSections = [] }) {
    const [basic, setBasic] = useState({
        customer_id:            String(certificate.customer_id ?? ""),
        insurance_category_id:  String(certificate.insurance_category_id ?? ""),
        policy_start_date:   parseDate(certificate.policy_start_date),
        policy_end_date:     parseDate(certificate.policy_end_date),
    });

    // All sections loaded from the saved certificate are structure-locked
    const [sections, setSections] = useState(
        initialSections.length > 0
            ? initialSections.map((s) => ({ ...s, _locked: true }))
            : []
    );
    const [errors, setErrors] = useState({});
    const [processing, setProcessing] = useState(false);

    // ── Section helpers ──────────────────────────────────────────────

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
            next[si] = { ...next[si], columns: [...next[si].columns, ""], rows: next[si].rows.map((r) => [...r, ""]) };
            return next;
        });

    const removeColumn = (si, ci) =>
        setSections((prev) => {
            const next = [...prev];
            next[si] = {
                ...next[si],
                columns: next[si].columns.filter((_, i) => i !== ci),
                rows:    next[si].rows.map((r) => r.filter((_, i) => i !== ci)),
            };
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
            next[si] = { ...next[si], rows: [...next[si].rows, Array(next[si].columns.length).fill("")] };
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

        router.put(
            route("policy_certificates.update", certificate.id),
            {
                customer_id:            basic.customer_id,
                insurance_category_id:  basic.insurance_category_id,
                policy_start_date:   formatDate(basic.policy_start_date),
                policy_end_date:     formatDate(basic.policy_end_date),
                sections:            stripMeta(sections),
            },
            {
                preserveScroll: true,
                onSuccess: () => toast.success("Policy certificate updated successfully"),
                onError: (errs) => { setErrors(errs); setProcessing(false); },
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
                    subpage="Edit"
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
                            <Label htmlFor="insurance_category_id" className="md:col-span-2 col-span-12">
                                Insurance Category *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <SearchableCombobox
                                        options={insuranceCategories}
                                        value={basic.insurance_category_id}
                                        onChange={(v) => setBasic({ ...basic, insurance_category_id: v })}
                                        placeholder="Select insurance category"
                                        emptyMessage="No insurance categories found. Add them in Underwriting Configuration."
                                    />
                                </div>
                                <InputError message={errors.insurance_category_id} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label className="md:col-span-2 col-span-12">Certificate Number</Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2 flex items-center gap-2">
                                <span className="inline-flex items-center px-3 py-1.5 rounded-md border bg-gray-100 text-sm font-mono font-semibold text-gray-700 select-all">
                                    {certificate.certificate_number || "—"}
                                </span>
                                <span className="text-xs text-gray-400">Read-only</span>
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label className="md:col-span-2 col-span-12">Policy Number</Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2 flex items-center gap-2">
                                <span className="inline-flex items-center px-3 py-1.5 rounded-md border bg-gray-100 text-sm font-mono font-semibold text-gray-700 select-all">
                                    {certificate.policy_number || "—"}
                                </span>
                                <span className="text-xs text-gray-400">Read-only</span>
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
                                                    type="button" variant="ghost" size="icon"
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
                                                                    type="button" variant="ghost" size="icon"
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
                                                        <Button type="button" variant="outline" size="sm" onClick={() => addField(si)}>
                                                            <Plus className="w-3 h-3 mr-1" /> Add Field
                                                        </Button>
                                                    )}
                                                </div>
                                            )}

                                            {/* ── table type ──────────────── */}
                                            {section.type === "table" && (
                                                <div className="space-y-3">
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
                                                                            type="button" variant="ghost" size="icon"
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
                                                                <Button type="button" variant="outline" size="sm" onClick={() => addColumn(si)}>
                                                                    <Plus className="w-3 h-3 mr-1" /> Add Column
                                                                </Button>
                                                            )}
                                                        </div>
                                                    </div>
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
                                                                                type="button" variant="ghost" size="icon"
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
                                                    <Button type="button" variant="outline" size="sm" onClick={() => addRow(si)}>
                                                        <Plus className="w-3 h-3 mr-1" /> Add Row
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
                                    No sections yet. Click "Add Section" to begin.
                                </p>
                            )}
                        </div>

                        <SidebarSeparator className="my-6" />

                        <div className="flex gap-3">
                            <Button type="submit" disabled={processing}>
                                {processing ? "Saving..." : "Update Certificate"}
                            </Button>
                            <Button
                                type="button" variant="outline"
                                onClick={() => router.visit(route("policy_certificates.index"))}
                            >
                                Cancel
                            </Button>
                        </div>
                    </form>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
