export const MEDICAL_COVERAGE_SECTIONS = [
    { key: "inpatient",    label: "Inpatient" },
    { key: "outpatient",   label: "Outpatient" },
    { key: "maternity",    label: "Maternity" },
    { key: "dental",       label: "Dental" },
    { key: "optical",      label: "Optical" },
    { key: "telemedicine", label: "Telemedicine" },
];

export const parseFamilySizeValue = (familySize) => {
    if (!familySize) return null;
    const n = String(familySize).trim().toUpperCase();
    const m = n.match(/^M\s*\+\s*(\d+(?:\.\d+)?)$/);
    if (m) return 1 + Number(m[1]);
    if (n === "M") return 1;
    if (/^\d+(?:\.\d+)?$/.test(n)) return Number(n);
    const c = n.match(/^(\d+(?:\.\d+)?)\s*\+\s*(\d+(?:\.\d+)?)$/);
    if (c) return Number(c[1]) + Number(c[2]);
    return null;
};

export const calculateMembersAndFamilyValue = (members, familySize) => {
    const fsv = parseFamilySizeValue(familySize);
    if (fsv === null) return 0;
    return (Number(members) || 0) * fsv;
};

export const getFamilySizeSortValue = (familySize) => {
    const n = String(familySize || "").trim().toUpperCase();
    if (n === "M") return 0;
    const m = n.match(/^M\s*\+\s*(\d+(?:\.\d+)?)$/);
    if (m) return Number(m[1]);
    return Number.MAX_SAFE_INTEGER;
};

export const sortMedicalQuotationItems = (items = []) =>
    [...items].sort((a, b) => {
        const av = getFamilySizeSortValue(a.family_size);
        const bv = getFamilySizeSortValue(b.family_size);
        if (av !== bv) return av - bv;
        return String(a.family_size || "").localeCompare(String(b.family_size || ""));
    });

export const buildMedicalSectionTotals = (items = []) =>
    MEDICAL_COVERAGE_SECTIONS.reduce((totals, section) => {
        totals[section.key] = items.reduce(
            (sum, item) => sum + (Number(item?.[`${section.key}_total_contribution`]) || 0),
            0
        );
        return totals;
    }, {});

export const allocateAmountAcrossSections = (amount, sectionTotals, decimalPlaces) => {
    const factor = 10 ** decimalPlaces;
    const totalAmountUnits = Math.round((Number(amount) || 0) * factor);
    const grandSectionTotal = MEDICAL_COVERAGE_SECTIONS.reduce(
        (sum, section) => sum + (Number(sectionTotals[section.key]) || 0),
        0
    );

    if (grandSectionTotal === 0) {
        return MEDICAL_COVERAGE_SECTIONS.reduce((alloc, section) => {
            alloc[section.key] = 0;
            return alloc;
        }, {});
    }

    let allocatedUnits = 0;
    return MEDICAL_COVERAGE_SECTIONS.reduce((alloc, section, idx) => {
        if (idx === MEDICAL_COVERAGE_SECTIONS.length - 1) {
            alloc[section.key] = (totalAmountUnits - allocatedUnits) / factor;
            return alloc;
        }
        const sectionUnits = Math.round(
            totalAmountUnits * ((Number(sectionTotals[section.key]) || 0) / grandSectionTotal)
        );
        allocatedUnits += sectionUnits;
        alloc[section.key] = sectionUnits / factor;
        return alloc;
    }, {});
};
