"use client";

import * as React from "react";
import {
    PieChart,
    Users,
    Package,
    GroupIcon,
    ChartPieIcon,
    Building2Icon,
    Building
} from "lucide-react";

import { usePage } from "@inertiajs/react";

import { NavOperations } from "@/Components/nav-operations";
import { NavManagement } from "@/Components/nav-management";
import { NavUser } from "@/Components/nav-user";
import { BusinessSwitcher } from "@/Components/business-switcher";
import { NavUserDashboard } from "@/Components/nav-user-dashboard";
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarRail,
} from "@/Components/ui/sidebar";

export function UserSidebar({ ...props }) {
    const { url } = usePage(); // e.g. "/user/products/123/edit"
    const { auth, activeBusiness } = usePage().props;

    // ———————————————————————————————
    // Define all your “startsWith” base-paths here:
    // ———————————————————————————————
    const dashboardBase = "/dashboard";

    // Products
    const productsBase = "/user/products";
    const mainCategoryBase = "/user/main_categories";
    const subCategoriesBase = "/user/sub_categories";
    const brandsBase = "/user/brands";
    const unitsBase = "/user/product_units";
    const invAdjustBase = "/user/inventory_adjustments";

    // Suppliers
    const vendorsBase = "/user/vendors";
    const purchaseOrdersBase = "/user/purchase_orders";
    const cashPurchasesBase = "/user/cash_purchases";
    const billInvoicesBase = "/user/bill_invoices";
    const billPaymentsBase = "/user/bill_payments";
    const purchaseReturnsBase = "/user/purchase_returns";

    // Customers
    const customersBase = "/user/customers";
    const receiptsBase = "/user/receipts";
    const creditInvoicesBase = "/user/invoices";
    const medicalRecordsBase = "/user/medical_records";
    const quotationsBase = "/user/quotations";
    const deferredInvoicesBase = "/user/deffered_invoices";
    const salesReturnsBase = "/user/sales_returns";
    const prescriptionsBase = "/user/prescriptions";
    const receivePaymentsBase = "/user/receive_payments";

    // HR & Payroll
    const staffsBase = "/user/staffs";
    const attendanceBase = "/user/attendance";
    const departmentsBase = "/user/departments";
    const designationsBase = "/user/designations";
    const payslipsBase = "/user/payslips";
    const holidaysBase = "/user/holidays";
    const leavesBase = "/user/leaves";
    const awardsBase = "/user/awards";

    // Construction
    const projectsBase = "/user/projects";
    const costCodesBase = "/user/cost_codes";
    const projectGroupsBase = "/user/project_groups";

    // Accounting
    const accountsBase = "/user/accounts";
    const journalsBase = "/user/journals";
    const transactionMethodsBase = "/user/transaction_methods";

    // Business
    const businessesBase = "/user/business";
    const rolesBase = "/user/roles";
    const taxesBase = "/user/taxes";
    const currencyBase = "/user/currency";
    const auditLogsBase = "/user/audit_logs";
    const businessSettingsFragment = "/business/settings/"; // we’ll use `.includes`

    // Reports
    const reportsJournalBase = "/user/reports/journal";
    const reportsLedgerBase = "/user/reports/ledger";
    const reportsIncomeStatement = "/user/reports/income_statement";
    const reportsTrialBalance = "/user/reports/trial_balance";
    const reportsBalanceSheet = "/user/reports/balance_sheet";
    const reportsReceivables = "/user/reports/receivables";
    const reportsPayables = "/user/reports/payables";
    const reportsPayrollSummary = "/user/reports/payroll_summary";
    const reportsPayrollCost = "/user/reports/payroll_cost";
    const reportsIncomeByCustomer = "/user/reports/income_by_customer";
    const reportsInventoryDetails = "/user/reports/inventory_details";
    const reportsInventorySummary = "/user/reports/inventory_summary";

    const data = {
        user: {
            id: auth.user.id,
            name: auth.user.name,
            email: auth.user.email,
            avatar: "/uploads/media/" + auth.user.profile_picture,
        },
    };

    // ——————————————————————————————————
    // Build each section using url.startsWith
    // ——————————————————————————————————
    const dashboardItems = [
        {
            title: "Dashboard",
            url: route("dashboard.index"),
            icon: PieChart,
            isActive: url.startsWith(dashboardBase),
        },
    ];

    const navOperationsItems = [
        {
            title: "Products",
            url: "#",
            icon: Package,
            isActive:
                url.startsWith(productsBase) ||
                url.startsWith(mainCategoryBase) ||
                url.startsWith(subCategoriesBase) ||
                url.startsWith(brandsBase) ||
                url.startsWith(unitsBase) ||
                url.startsWith(invAdjustBase),
            items: [
                {
                    title: "All Products",
                    url: route("products.index"),
                    isActive: url === productsBase,
                },
                {
                    title: "Main Categories",
                    url: route("main_categories.index"),
                    isActive: url === mainCategoryBase,
                },
                {
                    title: "Sub Categories",
                    url: route("sub_categories.index"),
                    isActive: url.startsWith(subCategoriesBase),
                },
                {
                    title: "Brands",
                    url: route("brands.index"),
                    isActive: url.startsWith(brandsBase),
                },
                {
                    title: "Units",
                    url: route("product_units.index"),
                    isActive: url.startsWith(unitsBase),
                },
                {
                    title: "Inventory Adjustment",
                    url: route("inventory_adjustments.index"),
                    isActive: url.startsWith(invAdjustBase),
                },
            ],
        },
        {
            title: "Suppliers",
            url: "#",
            icon: Users,
            isActive:
                url.startsWith(vendorsBase) ||
                url.startsWith(purchaseOrdersBase) ||
                url.startsWith(cashPurchasesBase) ||
                url.startsWith(billInvoicesBase) ||
                url.startsWith(billPaymentsBase) ||
                url.startsWith(purchaseReturnsBase),
            items: [
                {
                    title: "All Suppliers",
                    url: route("vendors.index"),
                    isActive: url.startsWith(vendorsBase),
                },
                {
                    title: "Purchase Order",
                    url: route("purchase_orders.index"),
                    isActive: url.startsWith(purchaseOrdersBase),
                },
                {
                    title: "Cash Purchase",
                    url: route("cash_purchases.index"),
                    isActive: url.startsWith(cashPurchasesBase),
                },
                {
                    title: "Bill Invoice",
                    url: route("bill_invoices.index"),
                    isActive: url.startsWith(billInvoicesBase),
                },
                {
                    title: "Pay Bills",
                    url: route("bill_payments.index"),
                    isActive: url.startsWith(billPaymentsBase),
                },
                {
                    title: "Purchase Return",
                    url: route("purchase_returns.index"),
                    isActive: url.startsWith(purchaseReturnsBase),
                },
            ],
        },
        {
            title: "Customers",
            url: "#",
            icon: Users,
            isActive:
                url.startsWith(customersBase) ||
                url.startsWith(receiptsBase) ||
                url.startsWith(creditInvoicesBase) ||
                url.startsWith(medicalRecordsBase) ||
                url.startsWith(quotationsBase) ||
                url.startsWith(deferredInvoicesBase) ||
                url.startsWith(salesReturnsBase) ||
                url.startsWith(prescriptionsBase) ||
                url.startsWith(receivePaymentsBase),
            items: [
                {
                    title: "All Customers",
                    url: route("customers.index"),
                    isActive: url.startsWith(customersBase),
                },
                {
                    title: "Cash Invoice",
                    url: route("receipts.index"),
                    isActive: url.startsWith(receiptsBase),
                },
                {
                    title: "Credit Invoice",
                    url: route("invoices.index"),
                    isActive: url.startsWith(creditInvoicesBase),
                },
                {
                    title: "Medical Records",
                    url: route("medical_records.index"),
                    isActive: url.startsWith(medicalRecordsBase),
                },
                {
                    title: "Prescriptions",
                    url: route("prescriptions.index"),
                    isActive: url.startsWith(prescriptionsBase),
                },
                {
                    title: "Deferred Invoice",
                    url: route("deffered_invoices.index"),
                    isActive: url.startsWith(deferredInvoicesBase),
                },
                {
                    title: "Received Payment",
                    url: route("receive_payments.index"),
                    isActive: url.startsWith(receivePaymentsBase),
                },
                {
                    title: "Sales Return",
                    url: route("sales_returns.index"),
                    isActive: url.startsWith(salesReturnsBase),
                },
                {
                    title: "Quotations",
                    url: route("quotations.index"),
                    isActive: url.startsWith(quotationsBase),
                },
            ],
        },
    ];

    const navManagementItems = [
        {
            title: "HR & Payroll",
            url: "#",
            icon: GroupIcon,
            isActive:
                url.startsWith(staffsBase) ||
                url.startsWith(attendanceBase) ||
                url.startsWith(departmentsBase) ||
                url.startsWith(designationsBase) ||
                url.startsWith(payslipsBase) ||
                url.startsWith(holidaysBase) ||
                url.startsWith(leavesBase) ||
                url.startsWith(awardsBase),
            items: [
                {
                    title: "Staff Management",
                    url: route("staffs.index"),
                    isActive: url.startsWith(staffsBase),
                },
                {
                    title: "Attendance",
                    url: route("attendance.index"),
                    isActive: url.startsWith(attendanceBase),
                },
                {
                    title: "Departments",
                    url: route("departments.index"),
                    isActive: url.startsWith(departmentsBase),
                },
                {
                    title: "Designations",
                    url: route("designations.index"),
                    isActive: url.startsWith(designationsBase),
                },
                {
                    title: "Manage Payroll",
                    url: route("payslips.index"),
                    isActive: url.startsWith(payslipsBase),
                },
                {
                    title: "Holidays",
                    url: route("holidays.index"),
                    isActive: url.startsWith(holidaysBase),
                },
                {
                    title: "Leave Management",
                    url: route("leaves.index"),
                    isActive: url.startsWith(leavesBase),
                },
                {
                    title: "Awards",
                    url: route("awards.index"),
                    isActive: url.startsWith(awardsBase),
                },
            ],
        },
        {
            title: "Construction",
            url: "#",
            icon: Building,
            isActive:
                url.startsWith(projectsBase) ||
                url.startsWith(costCodesBase) ||
                url.startsWith(projectGroupsBase),
            items: [
                {
                    title: "Projects",
                    url: route("projects.index"),
                    isActive: url.startsWith(projectsBase),
                },
                {
                    title: "Project Groups",
                    url: route("project_groups.index"),
                    isActive: url.startsWith(projectGroupsBase),
                },
                {
                    title: "Cost Codes",
                    url: route("cost_codes.index"),
                    isActive: url.startsWith(costCodesBase),
                },
            ],
        },
        {
            title: "Accounting",
            url: "#",
            icon: ChartPieIcon,
            isActive:
                url.startsWith(accountsBase) ||
                url.startsWith(journalsBase) ||
                url.startsWith(transactionMethodsBase),
            items: [
                {
                    title: "Chart of Accounts",
                    url: route("accounts.index"),
                    isActive: url.startsWith(accountsBase),
                },
                {
                    title: "Journal Entry",
                    url: route("journals.index"),
                    isActive: url.startsWith(journalsBase),
                },
                {
                    title: "Transaction Methods",
                    url: route("transaction_methods.index"),
                    isActive: url.startsWith(transactionMethodsBase),
                },
            ],
        },
        {
            title: "Business",
            url: "#",
            icon: Building2Icon,
            isActive:
                url.startsWith(businessesBase) ||
                url.startsWith(rolesBase) ||
                url.startsWith(taxesBase) ||
                url.startsWith(currencyBase) ||
                url.startsWith(auditLogsBase) ||
                url.includes(businessSettingsFragment),
            items: [
                {
                    title: "Manage Businesses",
                    url: route("business.index"),
                    isActive: url.startsWith(businessesBase),
                },
                {
                    title: "Roles & Permissions",
                    url: route("roles.index"),
                    isActive: url.startsWith(rolesBase),
                },
                {
                    title: "Business Settings",
                    url: route("business.settings", activeBusiness.id),
                    isActive: url.includes(businessSettingsFragment),
                },
                {
                    title: "Tax Settings",
                    url: route("taxes.index"),
                    isActive: url.startsWith(taxesBase),
                },
                {
                    title: "Currency Settings",
                    url: route("currency.index"),
                    isActive: url.startsWith(currencyBase),
                },
                {
                    title: "Audit Logs",
                    url: route("audit_logs.index"),
                    isActive: url.startsWith(auditLogsBase),
                },
            ],
        },
        {
            title: "Reports",
            url: "#",
            icon: Building2Icon,
            isActive:
                url.startsWith(reportsJournalBase) ||
                url.startsWith(reportsLedgerBase) ||
                url.startsWith(reportsIncomeStatement) ||
                url.startsWith(reportsTrialBalance) ||
                url.startsWith(reportsBalanceSheet) ||
                url.startsWith(reportsReceivables) ||
                url.startsWith(reportsPayables) ||
                url.startsWith(reportsPayrollSummary) ||
                url.startsWith(reportsPayrollCost) ||
                url.startsWith(reportsIncomeByCustomer) ||
                url.startsWith(reportsInventoryDetails) ||
                url.startsWith(reportsInventorySummary),
            items: [
                {
                    title: "General Journal",
                    url: route("reports.journal"),
                    isActive: url.startsWith(reportsJournalBase),
                },
                {
                    title: "General Ledger",
                    url: route("reports.ledger"),
                    isActive: url.startsWith(reportsLedgerBase),
                },
                {
                    title: "Income Statement",
                    url: route("reports.income_statement"),
                    isActive: url.startsWith(reportsIncomeStatement),
                },
                {
                    title: "Trial Balance",
                    url: route("reports.trial_balance"),
                    isActive: url.startsWith(reportsTrialBalance),
                },
                {
                    title: "Balance Sheet",
                    url: route("reports.balance_sheet"),
                    isActive: url.startsWith(reportsBalanceSheet),
                },
                {
                    title: "Income By Customer",
                    url: route("reports.income_by_customer"),
                    isActive: url.startsWith(reportsIncomeByCustomer),
                },
                {
                    title: "Receivables",
                    url: route("reports.receivables"),
                    isActive: url.startsWith(reportsReceivables),
                },
                {
                    title: "Payables",
                    url: route("reports.payables"),
                    isActive: url.startsWith(reportsPayables),
                },
                {
                    title: "Payroll Summary",
                    url: route("reports.payroll_summary"),
                    isActive: url.startsWith(reportsPayrollSummary),
                },
                {
                    title: "Monthly Payroll Cost",
                    url: route("reports.payroll_cost"),
                    isActive: url.startsWith(reportsPayrollCost),
                },
                {
                    title: "Inventory Details",
                    url: route("reports.inventory_details"),
                    isActive: url.startsWith(reportsInventoryDetails),
                },
                {
                    title: "Inventory Summary",
                    url: route("reports.inventory_summary"),
                    isActive: url.startsWith(reportsInventorySummary),
                },
            ],
        },
    ];

    return (
        <Sidebar collapsible="icon" {...props}>
            <SidebarHeader>
                <BusinessSwitcher />
            </SidebarHeader>
            <SidebarContent>
                <NavUserDashboard items={dashboardItems} />
                <NavOperations items={navOperationsItems} />
                <NavManagement items={navManagementItems} />
            </SidebarContent>
            <SidebarFooter>
                <NavUser user={data.user} />
            </SidebarFooter>
            <SidebarRail />
        </Sidebar>
    );
}
