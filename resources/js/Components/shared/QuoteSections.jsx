import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import { Button } from "@/Components/ui/button";
import { Plus, Trash2 } from "lucide-react";
import { TEXT_SECTION_TYPES } from "@/lib/quote-form-utils";

export default function QuoteSections({ sections, onUpdate }) {
    if (!sections || sections.length === 0) return null;

    const setField = (si, fi, value) =>
        onUpdate(sections.map((s, i) =>
            i !== si ? s : { ...s, fields: s.fields.map((f, j) => j !== fi ? f : { ...f, value }) }
        ));

    const setContent = (si, value) =>
        onUpdate(sections.map((s, i) => i !== si ? s : { ...s, content: value }));

    const setCell = (si, ri, ci, value) =>
        onUpdate(sections.map((s, i) => {
            if (i !== si) return s;
            const rows = s.rows.map((r, j) => {
                if (j !== ri) return r;
                const row = [...r]; row[ci] = value; return row;
            });
            return { ...s, rows };
        }));

    const addRow = (si) =>
        onUpdate(sections.map((s, i) =>
            i !== si ? s : { ...s, rows: [...s.rows, Array((s.columns ?? []).length).fill("")] }
        ));

    const removeRow = (si, ri) =>
        onUpdate(sections.map((s, i) =>
            i !== si ? s : { ...s, rows: s.rows.filter((_, j) => j !== ri) }
        ));

    return (
        <div className="mt-3 pt-3 border-t space-y-3">
            <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide">Quotation Sections</p>
            {sections.map((section, si) => (
                <div key={si} className="bg-white border rounded p-2 space-y-1">
                    <p className="text-xs font-semibold text-gray-700">{section.title}</p>

                    {section.type === "fields" && (
                        <div className="space-y-1">
                            {(section.fields ?? []).map((field, fi) => (
                                <div key={fi} className="flex items-center gap-2">
                                    <span className="text-xs text-gray-500 w-1/3 shrink-0 truncate">{field.label}:</span>
                                    <Input
                                        value={field.value ?? ""}
                                        onChange={(e) => setField(si, fi, e.target.value)}
                                        className="h-7 text-xs flex-1"
                                        placeholder="-"
                                    />
                                </div>
                            ))}
                        </div>
                    )}

                    {section.type === "table" && (
                        <div>
                            <div className="overflow-x-auto">
                                <table className="w-full text-xs border-collapse">
                                    <thead>
                                        <tr className="bg-gray-50">
                                            {(section.columns ?? []).map((col, ci) => (
                                                <th key={ci} className="border px-2 py-1 text-left font-medium text-gray-600">{col}</th>
                                            ))}
                                            <th className="border px-1 py-1 w-8" />
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {(section.rows ?? []).map((row, ri) => (
                                            <tr key={ri}>
                                                {row.map((cell, ci) => (
                                                    <td key={ci} className="border px-1 py-0.5">
                                                        <Input
                                                            value={cell}
                                                            onChange={(e) => setCell(si, ri, ci, e.target.value)}
                                                            className="h-6 text-xs border-0 shadow-none px-1 focus-visible:ring-0"
                                                            placeholder="-"
                                                        />
                                                    </td>
                                                ))}
                                                <td className="border px-1 py-0.5 text-center">
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-5 w-5 text-red-400 hover:text-red-600"
                                                        onClick={() => removeRow(si, ri)}
                                                        disabled={(section.rows ?? []).length === 1}
                                                    >
                                                        <Trash2 className="w-3 h-3" />
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            <Button type="button" variant="outline" size="sm" className="mt-1 h-6 text-xs" onClick={() => addRow(si)}>
                                <Plus className="w-3 h-3 mr-1" /> Add Row
                            </Button>
                        </div>
                    )}

                    {TEXT_SECTION_TYPES.includes(section.type) && (
                        <Textarea
                            value={section.content ?? ""}
                            onChange={(e) => setContent(si, e.target.value)}
                            rows={2}
                            className="text-xs w-full"
                            placeholder={`Enter ${section.type}...`}
                        />
                    )}
                </div>
            ))}
        </div>
    );
}
