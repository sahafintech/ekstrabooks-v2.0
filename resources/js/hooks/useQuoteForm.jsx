import { useState, useEffect } from "react";
import { formatCurrency } from "@/lib/utils";
import {
    parseNumericValue,
    getDefaultRateType,
    calculateFinancialLineTotal,
    calculateLineUnitCost,
    createEmptyQuotationItem,
    sectionsFromTemplate,
} from "@/lib/quote-form-utils";

/**
 * Shared state and business logic for Create and Edit quote forms.
 *
 * @param {object} data             - Inertia form data (read)
 * @param {Function} setData        - Inertia form setData (write)
 * @param {Array}  products
 * @param {Array}  currencies
 * @param {Array}  taxes            - All available taxes (id + rate)
 * @param {Array}  insuranceCategories
 * @param {Array}  ratingRules
 * @param {Array}  initialItems     - Initial quotation item objects
 * @param {Array}  initialSections  - Initial section objects
 * @param {number} initialExchangeRate
 */
export function useQuoteForm({
    data,
    setData,
    products,
    currencies,
    taxes,
    insuranceCategories,
    ratingRules,
    initialItems,
    initialSections,
    initialExchangeRate = 1,
}) {
    const [quotationItems, setQuotationItems] = useState(initialItems);
    const [sections, setSections]             = useState(initialSections);
    const [exchangeRate, setExchangeRate]     = useState(initialExchangeRate);
    const [baseCurrencyInfo, setBaseCurrencyInfo] = useState(null);

    useEffect(() => {
        const baseC = currencies.find((c) => c.base_currency === 1) || currencies[0];
        if (baseC) setBaseCurrencyInfo(baseC);
    }, [currencies]);

    // ── Calculations ──────────────────────────────────────────────────────────

    const taxRateMap = new Map(taxes.map((t) => [t.id, Number(t.rate)]));

    const calculateSubtotal = (items = quotationItems) =>
        items.reduce((s, item) => s + calculateFinancialLineTotal(item), 0);

    const calculateTaxes = (items = quotationItems, taxIds = data.taxes) =>
        items.reduce((sum, item) => {
            const base = calculateFinancialLineTotal(item);
            return sum + (taxIds ?? []).reduce(
                (ts, id) => ts + (base * (taxRateMap.get(Number(id)) || 0)) / 100,
                0,
            );
        }, 0);

    const calculateDiscount = (items = quotationItems) => {
        const sub = calculateSubtotal(items);
        const dv  = Number(data.discount_value) || 0;
        return data.discount_type === "0" ? (sub * dv) / 100 : dv;
    };

    const calculateTotal = (items = quotationItems) =>
        calculateSubtotal(items) + calculateTaxes(items) - calculateDiscount(items);

    // Keep converted_total in sync. data.taxes added so changing taxes triggers update.
    // eslint-disable-next-line react-hooks/exhaustive-deps
    useEffect(() => {
        setData("converted_total", calculateTotal());
    }, [data.currency, data.taxes, data.discount_type, data.discount_value, quotationItems, exchangeRate]);

    // ── Rating rule helpers ───────────────────────────────────────────────────

    const findDefaultRatingRule = (item, categoryId = data.insurance_category_id) => {
        const rules = ratingRules.filter((rule) => {
            if (String(rule.insurance_category_id) !== String(categoryId)) return false;
            if (!rule.product_id) return true;
            return String(rule.product_id) === String(item.product_id);
        });
        return (
            rules.find((r) => r.product_id && String(r.product_id) === String(item.product_id)) ??
            rules.find((r) => !r.product_id) ??
            null
        );
    };

    const applyRatingRule = (item, rule) => {
        if (!rule) return item;
        const calcType = rule.calculation_type || "manual_premium";
        return {
            ...item,
            rating_rule_id:   rule.id,
            calculation_type: calcType,
            rate_type:        rule.rate_type || getDefaultRateType(calcType),
            rate_value:       parseNumericValue(rule.default_rate ?? item.rate_value ?? 0),
            minimum_premium:  parseNumericValue(rule.minimum_premium ?? item.minimum_premium ?? ""),
        };
    };

    // ── Inertia form sync helpers ─────────────────────────────────────────────

    const syncItems = (items) => {
        setData("product_id",       items.map((i) => i.product_id));
        setData("product_name",     items.map((i) => i.product_name));
        setData("description",      items.map((i) => i.description));
        setData("rating_rule_id",   items.map((i) => i.rating_rule_id ?? ""));
        setData("calculation_type", items.map((i) => i.calculation_type));
        setData("rate_type",        items.map((i) => i.rate_type));
        setData("rate_value",       items.map((i) => i.rate_value));
        setData("basis_amount",     items.map((i) => i.basis_amount));
        setData("basis_quantity",   items.map((i) => i.basis_quantity || i.quantity || 1));
        setData("minimum_premium",  items.map((i) => i.minimum_premium));
        setData("quantity",         items.map((i) => i.quantity));
        setData("unit_cost",        items.map((i) => calculateLineUnitCost(i)));
    };

    const syncSections = (nextSections) => {
        setSections(nextSections);
        setData("sections", nextSections);
    };

    const updateItems = (items) => {
        const normalized = items.map((item) => ({
            ...item,
            basis_quantity: item.basis_quantity || item.quantity || 1,
            unit_cost:      calculateLineUnitCost(item),
        }));
        setQuotationItems(normalized);
        syncItems(normalized);
    };

    const sanitizeItems = (items, categoryId = data.insurance_category_id) =>
        items.map((item) => {
            const next = {
                ...item,
                rating_rule_id:   "",
                calculation_type: "manual_premium",
                rate_type:        getDefaultRateType("manual_premium"),
                basis_amount:     "",
                basis_quantity:   item.basis_quantity || item.quantity || 1,
            };
            return applyRatingRule(next, findDefaultRatingRule(next, categoryId));
        });

    // ── Section helpers ───────────────────────────────────────────────────────

    /**
     * Returns true if any current section contains user-entered values.
     * Used to warn before a category change wipes sections.
     */
    const hasSectionData = () =>
        sections.some(
            (s) =>
                s.fields?.some((f) => f.value) ||
                s.rows?.some((r) => r.some((c) => c)) ||
                s.content,
        );

    // ── Public item actions ───────────────────────────────────────────────────

    const addItem = () => {
        const newItem = sanitizeItems([createEmptyQuotationItem()])[0];
        updateItems([...quotationItems, newItem]);
    };

    const removeItem = (index) => {
        updateItems(quotationItems.filter((_, i) => i !== index));
    };

    const updateItem = (index, field, value) => {
        const updated = [...quotationItems];
        updated[index] = { ...updated[index], [field]: value };

        if (field === "rating_rule_id") {
            const rule = ratingRules.find((r) => String(r.id) === String(value));
            updated[index] = rule
                ? applyRatingRule(updated[index], rule)
                : { ...updated[index], rating_rule_id: "" };
        }
        if (field === "calculation_type") {
            updated[index].rate_type = getDefaultRateType(value);
            if (value !== "percentage_of_amount") updated[index].basis_amount = "";
        }
        if (field === "quantity") {
            updated[index].basis_quantity = value || 0;
        }
        if (field === "product_id") {
            const product = products.find((p) => p.id === parseInt(value, 10));
            if (product) {
                updated[index].product_name = product.name;
                if (!updated[index].description) updated[index].description = product.description || "";
                const rule = findDefaultRatingRule(updated[index]);
                if (rule) {
                    updated[index] = applyRatingRule(updated[index], rule);
                } else {
                    updated[index].rate_value     = parseNumericValue(product.selling_price ?? updated[index].rate_value ?? 0);
                    updated[index].rating_rule_id = "";
                }
            }
        }
        updateItems(updated);
    };

    // ── Category change ───────────────────────────────────────────────────────

    /**
     * Changes the insurance category, re-loading sections from its template
     * and resetting item rating rules. Warns the user if section data will be lost.
     */
    const handleInsuranceCategoryChange = (catId) => {
        if (
            hasSectionData() &&
            !window.confirm(
                "Changing the insurance category will replace all filled section data with the new template. Continue?",
            )
        ) {
            return;
        }
        const cat = insuranceCategories.find((c) => String(c.id) === String(catId));
        setData("insurance_category_id", catId);
        syncSections(cat ? sectionsFromTemplate(cat.sections) : []);
        updateItems(sanitizeItems(quotationItems, catId));
    };

    // ── Currency ──────────────────────────────────────────────────────────────

    const handleCurrencyChange = (currencyName) => {
        const currencyObj = currencies.find((c) => c.name === currencyName);
        if (!currencyObj) return;
        const rate = parseFloat(currencyObj.exchange_rate);
        setExchangeRate(rate);
        setData("exchange_rate", rate);
        fetch(`/user/find_currency/${currencyObj.name}`)
            .then((r) => r.json())
            .then((d) => {
                if (d?.exchange_rate) {
                    const ar = parseFloat(d.exchange_rate);
                    setExchangeRate(ar);
                    setData("exchange_rate", ar);
                }
            })
            .catch(() => {});
    };

    // ── Total display ─────────────────────────────────────────────────────────

    const renderTotal = () => {
        const total    = calculateTotal();
        const selected = currencies.find((c) => c.name === data.currency);
        if (!selected) return <h2 className="text-xl font-bold">Total: 0.00</h2>;
        if (baseCurrencyInfo && selected.name !== baseCurrencyInfo.name && exchangeRate && exchangeRate !== 1) {
            return (
                <div>
                    <h2 className="text-xl font-bold">
                        Total: {formatCurrency({ amount: total, currency: selected.name })}
                    </h2>
                    <p className="text-sm text-gray-600">
                        Equivalent to {formatCurrency({ amount: total / exchangeRate, currency: baseCurrencyInfo.name })}
                    </p>
                </div>
            );
        }
        return (
            <h2 className="text-xl font-bold">
                Total: {formatCurrency({ amount: total, currency: selected.name })}
            </h2>
        );
    };

    return {
        quotationItems,
        setQuotationItems,
        sections,
        syncSections,
        exchangeRate,
        setExchangeRate,
        handleInsuranceCategoryChange,
        hasSectionData,
        addItem,
        removeItem,
        updateItem,
        sanitizeItems,
        updateItems,
        calculateSubtotal,
        calculateTaxes,
        calculateDiscount,
        calculateTotal,
        handleCurrencyChange,
        renderTotal,
    };
}
