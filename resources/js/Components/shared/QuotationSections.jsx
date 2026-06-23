export default function QuotationSections({ sections = [], style }) {
    if (!sections.length) return null;

    return (
        <div className="mt-5 grid grid-cols-1 gap-3 md:grid-cols-2 print:grid-cols-2">
            {sections.map((section, index) => {
                const data    = section.data_json ?? {};
                const fields  = data.fields  ?? [];
                const columns = data.columns ?? [];
                const rows    = data.rows    ?? [];
                const isLastOddSection = sections.length % 2 === 1 && index === sections.length - 1;

                return (
                    <div
                        key={section.id ?? index}
                        className={`border border-slate-900 ${isLastOddSection ? "md:col-span-2 print:col-span-2" : ""}`}
                    >
                        <div className="px-2 py-1 text-xs font-bold uppercase" style={style}>
                            {section.title}
                        </div>

                        {section.type === "fields" && (
                            <table className="w-full border-collapse text-sm">
                                <tbody>
                                    {fields.map((field, fi) => (
                                        <tr key={fi}>
                                            <td className="w-1/3 border-t border-slate-900 px-3 py-2 font-semibold">{field.label}</td>
                                            <td className="border-t border-slate-900 px-3 py-2">{field.value || "-"}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}

                        {section.type === "table" && (
                            <table className="w-full border-collapse text-sm">
                                <thead>
                                    <tr>
                                        {columns.map((col, ci) => (
                                            <th key={ci} className="border-t border-slate-900 px-3 py-2 text-left">{col}</th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.map((row, ri) => (
                                        <tr key={ri}>
                                            {row.map((cell, ci) => (
                                                <td key={ci} className="border-t border-slate-900 px-3 py-2">{cell || "-"}</td>
                                            ))}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}

                        {section.type !== "fields" && section.type !== "table" && (
                            <div className="min-h-[80px] whitespace-pre-line p-3 text-sm leading-6 text-slate-800">
                                {section.content || "-"}
                            </div>
                        )}
                    </div>
                );
            })}
        </div>
    );
}
