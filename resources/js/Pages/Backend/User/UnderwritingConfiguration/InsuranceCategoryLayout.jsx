import { useState, useEffect } from "react";
import { router, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset, SidebarSeparator } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Tabs, TabsList, TabsTrigger, TabsContent } from "@/Components/ui/tabs";
import { Label } from "@/Components/ui/label";
import { Input } from "@/Components/ui/input";
import { Button } from "@/Components/ui/button";
import { Textarea } from "@/Components/ui/textarea";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { Plus, Trash2 } from "lucide-react";

const CERT_SECTION_TYPES = [
    { id: "fields",     name: "Fields (Key-Value Pairs)" },
    { id: "table",      name: "Table" },
    { id: "text",       name: "Text" },
    { id: "note",       name: "Note" },
    { id: "terms",      name: "Terms & Conditions" },
    { id: "exclusions", name: "Exclusions" },
    { id: "signature",  name: "Signature" },
];

const QUOT_SECTION_TYPES = [
    { id: "fields",          name: "Fields (Key-Value Pairs)" },
    { id: "table",           name: "Table" },
    { id: "benefits",        name: "Benefits" },
    { id: "remarks",         name: "Remarks / Exclusions" },
    { id: "rich_text",       name: "Rich Text" },
    { id: "calculation",     name: "Calculation" },
    { id: "premium_summary", name: "Premium Summary" },
];

const CONTENT_SECTION_TYPES = [
    "text",
    "note",
    "terms",
    "exclusions",
    "signature",
    "benefits",
    "remarks",
    "rich_text",
    "calculation",
    "premium_summary",
];

const CONTENT_SECTION_LABELS = {
    benefits: "Default Benefits",
    remarks: "Default Remarks / Exclusions",
    rich_text: "Default Rich Text",
    calculation: "Default Calculation Notes",
    premium_summary: "Default Premium Summary Notes",
};

const makeSection = (sortOrder) => ({
    title: "", type: "fields", sort_order: sortOrder,
    fields: [{ label: "", default_value: "" }],
    columns: [""], rows: [[""]], content: "",
});

// ── Reusable sections builder ─────────────────────────────────────
function SectionsBuilder({ sections, setSections, sectionTypes = CERT_SECTION_TYPES }) {

    const add    = () => setSections((p) => [...p, makeSection(p.length)]);
    const remove = (si) => setSections((p) => p.filter((_, i) => i !== si));
    const update = (si, key, value) => setSections((p) => { const n = [...p]; n[si] = { ...n[si], [key]: value }; return n; });

    const addField    = (si) => setSections((p) => { const n = [...p]; n[si] = { ...n[si], fields: [...n[si].fields, { label: "", default_value: "" }] }; return n; });
    const removeField = (si, fi) => setSections((p) => { const n = [...p]; n[si] = { ...n[si], fields: n[si].fields.filter((_, i) => i !== fi) }; return n; });
    const updateField = (si, fi, key, value) => setSections((p) => {
        const n = [...p]; const fields = [...n[si].fields]; fields[fi] = { ...fields[fi], [key]: value }; n[si] = { ...n[si], fields }; return n;
    });

    const addColumn    = (si) => setSections((p) => { const n = [...p]; n[si] = { ...n[si], columns: [...n[si].columns, ""], rows: n[si].rows.map((r) => [...r, ""]) }; return n; });
    const removeColumn = (si, ci) => setSections((p) => { const n = [...p]; n[si] = { ...n[si], columns: n[si].columns.filter((_, i) => i !== ci), rows: n[si].rows.map((r) => r.filter((_, i) => i !== ci)) }; return n; });
    const updateColumn = (si, ci, value) => setSections((p) => { const n = [...p]; const cols = [...n[si].columns]; cols[ci] = value; n[si] = { ...n[si], columns: cols }; return n; });

    const addRow    = (si) => setSections((p) => { const n = [...p]; n[si] = { ...n[si], rows: [...n[si].rows, Array(n[si].columns.length).fill("")] }; return n; });
    const removeRow = (si, ri) => setSections((p) => { const n = [...p]; n[si] = { ...n[si], rows: n[si].rows.filter((_, i) => i !== ri) }; return n; });
    const updateCell = (si, ri, ci, value) => setSections((p) => {
        const n = [...p];
        const rows = n[si].rows.map((r, i) => { if (i !== ri) return r; const row = [...r]; row[ci] = value; return row; });
        n[si] = { ...n[si], rows }; return n;
    });

    return (
        <div className="space-y-4">
            <div className="flex justify-end">
                <Button type="button" variant="secondary" onClick={add}>
                    <Plus className="w-4 h-4 mr-2" />Add Section
                </Button>
            </div>

            {sections.map((section, si) => (
                <div key={si} className="border rounded-lg bg-gray-50">
                    {/* Section header */}
                    <div className="flex items-center gap-3 p-4 border-b bg-white rounded-t-lg">

                        <div className="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <Label className="text-xs text-gray-500 mb-1 block">Section Title</Label>
                                <Input value={section.title} onChange={(e) => update(si, "title", e.target.value)} placeholder="e.g. INSURED DETAILS" />
                            </div>
                            <div>
                                <Label className="text-xs text-gray-500 mb-1 block">Section Type</Label>
                                <SearchableCombobox options={sectionTypes} value={section.type} onChange={(v) => update(si, "type", v)} placeholder="Select type" />
                            </div>
                        </div>
                        <Button type="button" variant="ghost" size="icon" className="text-red-500 hover:text-red-700 shrink-0" onClick={() => remove(si)}>
                            <Trash2 className="w-4 h-4" />
                        </Button>
                    </div>

                    {/* Section body */}
                    <div className="p-4">

                        {/* fields */}
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
                                        <Input value={field.label} onChange={(e) => updateField(si, fi, "label", e.target.value)} placeholder="e.g. Vehicle Reg No." className="w-1/2" />
                                        <Input value={field.default_value} onChange={(e) => updateField(si, fi, "default_value", e.target.value)} placeholder="Leave blank if dynamic" className="flex-1" />
                                        <Button type="button" variant="ghost" size="icon" className="text-red-500 hover:text-red-700 shrink-0" onClick={() => removeField(si, fi)} disabled={section.fields.length === 1}>
                                            <Trash2 className="w-4 h-4" />
                                        </Button>
                                    </div>
                                ))}
                                <Button type="button" variant="outline" size="sm" onClick={() => addField(si)}>
                                    <Plus className="w-3 h-3 mr-1" /> Add Field
                                </Button>
                            </div>
                        )}

                        {/* table */}
                        {section.type === "table" && (
                            <div className="space-y-3">
                                <div>
                                    <Label className="text-xs text-gray-500 mb-2 block">Column Headers</Label>
                                    <div className="flex flex-wrap gap-2">
                                        {section.columns.map((col, ci) => (
                                            <div key={ci} className="flex items-center gap-1">
                                                <Input value={col} onChange={(e) => updateColumn(si, ci, e.target.value)} placeholder={`Column ${ci + 1}`} className="w-36" />
                                                <Button type="button" variant="ghost" size="icon" className="text-red-500 hover:text-red-700 shrink-0" onClick={() => removeColumn(si, ci)} disabled={section.columns.length === 1}>
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
                                                        <th key={ci} className="border px-2 py-1 text-left font-medium text-gray-600">{col || `Column ${ci + 1}`}</th>
                                                    ))}
                                                    <th className="border px-2 py-1 w-10" />
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {section.rows.map((row, ri) => (
                                                    <tr key={ri}>
                                                        {row.map((cell, ci) => (
                                                            <td key={ci} className="border px-1 py-1">
                                                                <Input value={cell} onChange={(e) => updateCell(si, ri, ci, e.target.value)} className="border-0 shadow-none focus-visible:ring-0 h-7 px-1" placeholder="—" />
                                                            </td>
                                                        ))}
                                                        <td className="border px-1 py-1 text-center">
                                                            <Button type="button" variant="ghost" size="icon" className="text-red-500 hover:text-red-700 h-7 w-7" onClick={() => removeRow(si, ri)} disabled={section.rows.length === 1}>
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

                        {/* content-backed sections */}
                        {CONTENT_SECTION_TYPES.includes(section.type) && (
                            <div>
                                <Label className="text-xs text-gray-500 mb-1 block">
                                    {CONTENT_SECTION_LABELS[section.type] ?? "Default Content"} <span className="font-normal text-gray-400">(optional — pre-fills when creating)</span>
                                </Label>
                                <Textarea value={section.content} onChange={(e) => update(si, "content", e.target.value)} placeholder={`Default content for ${section.type}...`} rows={4} className="w-full" />
                            </div>
                        )}
                    </div>
                </div>
            ))}

            {sections.length === 0 && (
                <p className="text-sm text-gray-400 text-center py-8 border border-dashed rounded-lg">
                    No sections defined yet. Click "Add Section" to start building.
                </p>
            )}
        </div>
    );
}

// ── Main component ────────────────────────────────────────────────
export default function InsuranceCategoryLayout({
    insuranceCategory,
    certSections: initialCertSections = [],
    quotSections: initialQuotSections = [],
}) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();

    const [certSections, setCertSections] = useState(initialCertSections);
    const [quotSections, setQuotSections] = useState(initialQuotSections);
    const [certProcessing, setCertProcessing] = useState(false);
    const [quotProcessing, setQuotProcessing] = useState(false);

    useEffect(() => {
        if (flash?.success) toast({ title: "Success", description: flash.success });
        if (flash?.error)   toast({ variant: "destructive", title: "Error", description: flash.error });
    }, [flash]);

    const saveCertSections = (e) => {
        e.preventDefault();
        setCertProcessing(true);
        router.post(
            route("underwriting_configuration.insurance_categories.save_layout", insuranceCategory.id),
            { sections: certSections },
            { preserveScroll: true, onFinish: () => setCertProcessing(false) }
        );
    };

    const saveQuotSections = (e) => {
        e.preventDefault();
        setQuotProcessing(true);
        router.post(
            route("underwriting_configuration.insurance_categories.save_quotation_sections", insuranceCategory.id),
            { sections: quotSections },
            { preserveScroll: true, onFinish: () => setQuotProcessing(false) }
        );
    };

    return (
        <AuthenticatedLayout>
            <Toaster />
            <SidebarInset>
                <PageHeader
                    page="Insurance Categories"
                    subpage={`${insuranceCategory.name} — Layout`}
                    url="underwriting_configuration.index"
                />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <Tabs defaultValue="certificate_sections">
                        <TabsList>
                            <TabsTrigger value="certificate_sections">Certificate Sections</TabsTrigger>
                            <TabsTrigger value="quotation_sections">Quotation Sections</TabsTrigger>
                        </TabsList>

                        {/* ── Certificate Sections ─────────────────── */}
                        <TabsContent value="certificate_sections">
                            <div className="mt-2 mb-2">
                                <p className="text-sm text-gray-500">
                                    Define the structure and default values for <span className="font-semibold">{insuranceCategory.name}</span> certificates.
                                    Keys and structure defined here will be locked when creating a certificate.
                                </p>
                            </div>
                            <form onSubmit={saveCertSections}>
                                <SectionsBuilder sections={certSections} setSections={setCertSections} sectionTypes={CERT_SECTION_TYPES} />
                                <SidebarSeparator className="my-6" />
                                <div className="flex gap-3">
                                    <Button type="submit" disabled={certProcessing}>
                                        {certProcessing ? "Saving..." : "Save Certificate Sections"}
                                    </Button>
                                    <Button type="button" variant="outline" onClick={() => router.visit(route("underwriting_configuration.index", { tab: "insurance_categories" }))}>
                                        Back
                                    </Button>
                                </div>
                            </form>
                        </TabsContent>

                        {/* ── Quotation Sections ───────────────────── */}
                        <TabsContent value="quotation_sections">
                            <div className="mt-2 mb-2">
                                <p className="text-sm text-gray-500">
                                    Define the structure and default values for <span className="font-semibold">{insuranceCategory.name}</span> quotations.
                                    These sections will be available when building quotes for this category.
                                </p>
                            </div>
                            <form onSubmit={saveQuotSections}>
                                <SectionsBuilder sections={quotSections} setSections={setQuotSections} sectionTypes={QUOT_SECTION_TYPES} />
                                <SidebarSeparator className="my-6" />
                                <div className="flex gap-3">
                                    <Button type="submit" disabled={quotProcessing}>
                                        {quotProcessing ? "Saving..." : "Save Quotation Sections"}
                                    </Button>
                                    <Button type="button" variant="outline" onClick={() => router.visit(route("underwriting_configuration.index", { tab: "insurance_categories" }))}>
                                        Back
                                    </Button>
                                </div>
                            </form>
                        </TabsContent>
                    </Tabs>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
