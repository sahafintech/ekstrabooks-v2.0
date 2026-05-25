import { useState, useEffect } from "react";
import { router, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset, SidebarSeparator } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Label } from "@/Components/ui/label";
import { Input } from "@/Components/ui/input";
import { Button } from "@/Components/ui/button";
import { Textarea } from "@/Components/ui/textarea";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { Plus, Trash2, GripVertical } from "lucide-react";

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
    fields: [{ label: "", default_value: "" }],
    columns: [""], rows: [[""]], content: "",
});

export default function CertificateTypeLayout({ certificateType, sections: initialSections = [] }) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [sections, setSections] = useState(
        initialSections.length > 0 ? initialSections : []
    );
    const [processing, setProcessing] = useState(false);

    useEffect(() => {
        if (flash?.success) toast({ title: "Success", description: flash.success });
        if (flash?.error)   toast({ variant: "destructive", title: "Error", description: flash.error });
    }, [flash]);

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
            next[si] = { ...next[si], fields: [...next[si].fields, { label: "", default_value: "" }] };
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
        router.post(
            route("underwriting_configuration.certificate_types.save_layout", certificateType.id),
            { sections },
            {
                preserveScroll: true,
                onFinish: () => setProcessing(false),
            }
        );
    };

    return (
        <AuthenticatedLayout>
            <Toaster />
            <SidebarInset>
                <PageHeader
                    page="Certificate Types"
                    subpage={`${certificateType.name} — Layout`}
                    url="underwriting_configuration.index"
                />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <form onSubmit={submit}>
                        <div className="space-y-4">
                            <div className="flex justify-between items-center">
                                <div>
                                    <h3 className="text-lg font-semibold">Certificate Sections</h3>
                                    <p className="text-sm text-gray-500 mt-0.5">
                                        Define the structure and default values for <span className="font-semibold">{certificateType.name}</span> certificates.
                                        Keys and structure defined here will be locked when creating a certificate.
                                    </p>
                                </div>
                                <Button type="button" variant="secondary" onClick={addSection}>
                                    <Plus className="w-4 h-4 mr-2" />
                                    Add Section
                                </Button>
                            </div>

                            {sections.map((section, si) => (
                                <div key={si} className="border rounded-lg bg-gray-50">
                                    {/* Section header */}
                                    <div className="flex items-center gap-3 p-4 border-b bg-white rounded-t-lg">
                                        <GripVertical className="w-4 h-4 text-gray-400 shrink-0" />
                                        <div className="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <div>
                                                <Label className="text-xs text-gray-500 mb-1 block">Section Title</Label>
                                                <Input
                                                    value={section.title}
                                                    onChange={(e) => updateSection(si, "title", e.target.value)}
                                                    placeholder="e.g. INSURED DETAILS"
                                                />
                                            </div>
                                            <div>
                                                <Label className="text-xs text-gray-500 mb-1 block">Section Type</Label>
                                                <SearchableCombobox
                                                    options={SECTION_TYPES}
                                                    value={section.type}
                                                    onChange={(v) => updateSection(si, "type", v)}
                                                    placeholder="Select type"
                                                />
                                            </div>
                                        </div>
                                        <Button
                                            type="button" variant="ghost" size="icon"
                                            className="text-red-500 hover:text-red-700 shrink-0"
                                            onClick={() => removeSection(si)}
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </Button>
                                    </div>

                                    {/* Section body */}
                                    <div className="p-4">

                                        {/* ── fields type ─────────────── */}
                                        {section.type === "fields" && (
                                            <div className="space-y-2">
                                                <div className="grid grid-cols-2 gap-2 mb-1 pr-9">
                                                    <span className="text-xs font-medium text-gray-500">Field Key (Label)</span>
                                                    <span className="text-xs font-medium text-gray-500">
                                                        Default Value <span className="font-normal text-gray-400">(optional — pre-fills when creating)</span>
                                                    </span>
                                                </div>
                                                {section.fields.map((field, fi) => (
                                                    <div key={fi} className="flex items-center gap-2">
                                                        <Input
                                                            value={field.label}
                                                            onChange={(e) => updateField(si, fi, "label", e.target.value)}
                                                            placeholder="e.g. Vehicle Reg No."
                                                            className="w-1/2"
                                                        />
                                                        <Input
                                                            value={field.default_value}
                                                            onChange={(e) => updateField(si, fi, "default_value", e.target.value)}
                                                            placeholder="Leave blank if dynamic"
                                                            className="flex-1"
                                                        />
                                                        <Button
                                                            type="button" variant="ghost" size="icon"
                                                            className="text-red-500 hover:text-red-700 shrink-0"
                                                            onClick={() => removeField(si, fi)}
                                                            disabled={section.fields.length === 1}
                                                        >
                                                            <Trash2 className="w-4 h-4" />
                                                        </Button>
                                                    </div>
                                                ))}
                                                <Button type="button" variant="outline" size="sm" onClick={() => addField(si)}>
                                                    <Plus className="w-3 h-3 mr-1" /> Add Field
                                                </Button>
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
                                                                    onChange={(e) => updateColumn(si, ci, e.target.value)}
                                                                    placeholder={`Column ${ci + 1}`}
                                                                    className="w-36"
                                                                />
                                                                <Button
                                                                    type="button" variant="ghost" size="icon"
                                                                    className="text-red-500 hover:text-red-700 shrink-0"
                                                                    onClick={() => removeColumn(si, ci)}
                                                                    disabled={section.columns.length === 1}
                                                                >
                                                                    <Trash2 className="w-3 h-3" />
                                                                </Button>
                                                            </div>
                                                        ))}
                                                        <Button type="button" variant="outline" size="sm" onClick={() => addColumn(si)}>
                                                            <Plus className="w-3 h-3 mr-1" /> Add Column
                                                        </Button>
                                                    </div>
                                                </div>
                                                <div>
                                                    <Label className="text-xs text-gray-500 mb-2 block">
                                                        Default Rows <span className="font-normal text-gray-400">(optional — pre-fills when creating)</span>
                                                    </Label>
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
                                                    <Button type="button" variant="outline" size="sm" className="mt-2" onClick={() => addRow(si)}>
                                                        <Plus className="w-3 h-3 mr-1" /> Add Row
                                                    </Button>
                                                </div>
                                            </div>
                                        )}

                                        {/* ── text / note / terms / exclusions / signature ── */}
                                        {["text", "note", "terms", "exclusions", "signature"].includes(section.type) && (
                                            <div>
                                                <Label className="text-xs text-gray-500 mb-1 block">
                                                    Default Content <span className="font-normal text-gray-400">(optional — pre-fills when creating)</span>
                                                </Label>
                                                <Textarea
                                                    value={section.content}
                                                    onChange={(e) => updateSection(si, "content", e.target.value)}
                                                    placeholder={`Default content for ${section.type}...`}
                                                    rows={4}
                                                    className="w-full"
                                                />
                                            </div>
                                        )}
                                    </div>
                                </div>
                            ))}

                            {sections.length === 0 && (
                                <p className="text-sm text-gray-400 text-center py-8 border border-dashed rounded-lg">
                                    No sections defined yet. Click "Add Section" to build the certificate layout.
                                </p>
                            )}
                        </div>

                        <SidebarSeparator className="my-6" />

                        <div className="flex gap-3">
                            <Button type="submit" disabled={processing}>
                                {processing ? "Saving..." : "Save Layout"}
                            </Button>
                            <Button
                                type="button" variant="outline"
                                onClick={() => router.visit(route("underwriting_configuration.index", { tab: "certificate_types" }))}
                            >
                                Back
                            </Button>
                        </div>
                    </form>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
