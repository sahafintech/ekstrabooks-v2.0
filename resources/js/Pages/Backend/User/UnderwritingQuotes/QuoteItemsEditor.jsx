import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Button } from "@/Components/ui/button";
import { Textarea } from "@/Components/ui/textarea";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { Plus, Trash2 } from "lucide-react";
import QuoteSections from "@/Components/shared/QuoteSections";
import {
    CALCULATION_TYPES,
    parseNumericValue,
    calculateFinancialLineTotal,
    getRateLabel,
} from "@/lib/quote-form-utils";

export default function QuoteItemsEditor({
    quotationItems,
    sections,
    insuranceCategoryId,
    products,
    ratingRules,
    onAddItem,
    onRemoveItem,
    onUpdateItem,
    onSectionsUpdate,
}) {
    const productOptions = products.map((p) => ({ id: p.id, name: p.name }));

    const getRatingRuleOptions = (item) => [
        { id: "", name: "Manual / No Rule" },
        ...ratingRules
            .filter((rule) => {
                if (String(rule.insurance_category_id) !== String(insuranceCategoryId)) return false;
                if (!rule.product_id) return true;
                return String(rule.product_id) === String(item.product_id);
            })
            .map((rule) => ({
                id:   rule.id,
                name: rule.product_id ? `${rule.name} (Product)` : rule.name,
            })),
    ];

    const autoResizeTextarea = (event, onChange) => {
        onChange(event.target.value);
        event.target.style.height = "auto";
        event.target.style.height = `${event.target.scrollHeight}px`;
    };

    return (
        <div className="space-y-4">
            <div className="flex justify-between items-center">
                <h3 className="text-lg font-medium">Quote Items</h3>
                <Button variant="secondary" type="button" onClick={onAddItem} disabled={!insuranceCategoryId}>
                    <Plus className="w-4 h-4 mr-2" />Add Item
                </Button>
            </div>

            {!insuranceCategoryId ? (
                <div className="border rounded-lg p-4 bg-gray-50 text-sm text-muted-foreground">
                    Select an insurance category above to unlock item fields.
                </div>
            ) : (
                quotationItems.map((item, index) => {
                    const hasBasis = item.calculation_type === "percentage_of_amount";
                    return (
                        <div key={index} className="border rounded-lg p-3 bg-gray-50">
                            <div className="flex gap-2 items-end">
                                <div className="grid grid-cols-12 gap-2 items-end flex-1">
                                    {/* Product — 2 cols */}
                                    <div className="md:col-span-2 col-span-12">
                                        <Label>Product</Label>
                                        <SearchableCombobox
                                            options={productOptions}
                                            value={item.product_id}
                                            onChange={(v) => onUpdateItem(index, "product_id", v)}
                                            placeholder="Select product"
                                        />
                                    </div>

                                    {/* Rating Rule — 2 cols */}
                                    <div className="md:col-span-2 col-span-12">
                                        <Label>Rating Rule</Label>
                                        <SearchableCombobox
                                            options={getRatingRuleOptions(item)}
                                            value={item.rating_rule_id}
                                            onChange={(v) => onUpdateItem(index, "rating_rule_id", v)}
                                            placeholder="Select rule"
                                        />
                                    </div>

                                    {/* Qty — 1 col */}
                                    <div className="md:col-span-1 col-span-12">
                                        <Label>Qty</Label>
                                        <Input
                                            type="number"
                                            step="0.01"
                                            value={item.quantity}
                                            onChange={(e) => onUpdateItem(index, "quantity", parseNumericValue(e.target.value))}
                                        />
                                    </div>

                                    {/* Calculation Type — 2 cols */}
                                    <div className="md:col-span-2 col-span-12">
                                        <Label>Calculation Type</Label>
                                        <SearchableCombobox
                                            options={CALCULATION_TYPES}
                                            value={item.calculation_type}
                                            onChange={(v) => onUpdateItem(index, "calculation_type", v)}
                                            placeholder="Select"
                                        />
                                    </div>

                                    {/* Rate — 1 col */}
                                    <div className="md:col-span-1 col-span-12">
                                        <Label>{getRateLabel(item)}</Label>
                                        <Input
                                            type="number"
                                            step="0.01"
                                            value={Number(item.rate_value) || 0}
                                            onChange={(e) => onUpdateItem(index, "rate_value", parseNumericValue(e.target.value))}
                                        />
                                    </div>

                                    {/* Sum Insured — 1 col, only for percentage_of_amount */}
                                    {hasBasis && (
                                        <div className="md:col-span-1 col-span-12">
                                            <Label>Sum Insured</Label>
                                            <Input
                                                type="number"
                                                step="0.01"
                                                value={item.basis_amount}
                                                onChange={(e) => onUpdateItem(index, "basis_amount", parseNumericValue(e.target.value))}
                                            />
                                        </div>
                                    )}

                                    {/* Description */}
                                    <div className="md:col-span-2 col-span-12">
                                        <Label>Description</Label>
                                        <Textarea
                                            value={item.description}
                                            onChange={(e) => autoResizeTextarea(e, (v) => onUpdateItem(index, "description", v))}
                                            className="min-h-[30px] resize-none overflow-hidden"
                                            rows={1}
                                        />
                                    </div>

                                    {/* Subtotal — 1 col */}
                                    <div className="md:col-span-1 col-span-12">
                                        <Label>Subtotal</Label>
                                        <div className="h-10 flex items-center justify-end px-2 bg-white rounded border text-sm font-medium">
                                            {calculateFinancialLineTotal(item).toFixed(2)}
                                        </div>
                                    </div>
                                </div>

                                {quotationItems.length > 1 && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        className="text-red-500 shrink-0 self-end"
                                        onClick={() => onRemoveItem(index)}
                                    >
                                        <Trash2 className="w-4 h-4" />
                                    </Button>
                                )}
                            </div>
                        </div>
                    );
                })
            )}

            {insuranceCategoryId && sections.length > 0 && (
                <div className="mt-4">
                    <QuoteSections sections={sections} onUpdate={onSectionsUpdate} />
                </div>
            )}
        </div>
    );
}
