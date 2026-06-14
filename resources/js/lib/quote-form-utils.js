export const CALCULATION_TYPES = [
    { id: "percentage_of_amount", name: "Percentage of Amount" },
    { id: "fixed_per_quantity",   name: "Fixed per Quantity" },
    { id: "fixed_amount",         name: "Fixed Amount" },
    { id: "manual_premium",       name: "Manual Premium" },
    { id: "tiered_rate",          name: "Tiered Rate" },
];

export const TEXT_SECTION_TYPES = [
    "text", "note", "terms", "exclusions", "signature",
    "benefits", "remarks", "rich_text", "calculation", "premium_summary",
];

export const parseNumericValue = (value) => {
    if (value === "" || value === null || value === undefined) return "";
    const parsed = parseFloat(value);
    return Number.isNaN(parsed) ? "" : parsed;
};

export const getDefaultRateType = (calculationType) => {
    if (calculationType === "percentage_of_amount") return "percentage";
    if (calculationType === "tiered_rate")          return "range";
    if (calculationType === "fixed_per_quantity" || calculationType === "fixed_amount") return "fixed";
    return "manual";
};

export const calculateFinancialLineTotal = (item) => {
    const rate          = Number(item.rate_value) || 0;
    const basisAmount   = Number(item.basis_amount) || 0;
    const basisQuantity = Number(item.basis_quantity || item.quantity) || 0;
    const minimumPremium =
        item.minimum_premium === "" || item.minimum_premium === null || item.minimum_premium === undefined
            ? null
            : Number(item.minimum_premium) || 0;

    let lineTotal = rate;
    if (item.calculation_type === "percentage_of_amount") lineTotal = (basisAmount * rate) / 100;
    if (item.calculation_type === "fixed_per_quantity")   lineTotal = basisQuantity * rate;

    return minimumPremium !== null && lineTotal < minimumPremium ? minimumPremium : lineTotal;
};

export const calculateLineUnitCost = (item) =>
    item.calculation_type === "fixed_per_quantity"
        ? Number(item.rate_value) || 0
        : calculateFinancialLineTotal(item);

export const getRateLabel = (item) => {
    if (item.calculation_type === "percentage_of_amount") return "Rate (%) *";
    if (item.calculation_type === "fixed_per_quantity")   return "Rate / Quantity *";
    return "Premium *";
};

export const sectionsFromTemplate = (templateSections) =>
    (templateSections ?? []).map((s) => ({
        insurance_category_section_id: s.id ?? null,
        title:      s.title,
        type:       s.type,
        sort_order: s.sort_order ?? 0,
        fields:     (s.fields ?? []).map((f) => ({ label: f.label, value: f.default_value ?? f.value ?? "" })),
        columns:    s.columns ?? [""],
        rows:       s.rows && s.rows.length > 0
            ? s.rows
            : [Array(Math.max((s.columns ?? [""]).length, 1)).fill("")],
        content:    s.content ?? "",
    }));

export const createEmptyQuotationItem = () => ({
    product_id:       "",
    product_name:     "",
    description:      "",
    rating_rule_id:   "",
    calculation_type: "manual_premium",
    rate_type:        "manual",
    rate_value:       0,
    basis_amount:     "",
    basis_quantity:   1,
    minimum_premium:  "",
    quantity:         1,
    unit_cost:        0,
});
