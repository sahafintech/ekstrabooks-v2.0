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
    const { url } = usePage();
    const { auth, activeBusiness, isOwner, permissionList, userPackage } = usePage().props;

    // Convert permissions array to Set for quick lookups
    const perms = new Set(Array.isArray(permissionList) ? permissionList : []);

    // Helper function to check permission (owners bypass permission checks)
    const hasPermission = (permission) => {
        return isOwner || perms.has(permission);
    };

    // Helper function to check if any permission exists
    const hasAnyPermission = (permissions) => {
        return isOwner || permissions.some(p => perms.has(p));
    };

    // ———————————————————————————————
    // Define all your "startsWith" base-paths here:
    // ———————————————————————————————
    const dashboardBase = "/dashboard";

    // Products
    const productsBase = "/user/products";
    const mainCategoryBase = "/user/main_categories";
    const subCategoriesBase = "/user/sub_categories";
    const brandsBase = "/user/brands";
    const unitsBase = "/user/product_units";
    const invAdjustBase = "/user/inventory_adjustments";
    const invTransferBase = "/user/inventory_transfers";

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
    const attendanceDevicesBase = "/user/attendance_devices";
    const attendanceLogsBase = "/user/attendance_logs";
    const timeSheetsBase = "/user/timesheets";
    const departmentsBase = "/user/departments";
    const designationsBase = "/user/designations";
    const salaryAdvancesBase = "/user/salary_advances";
    const payslipsBase = "/user/payslips";
    const holidaysBase = "/user/holidays";
    const leavesBase = "/user/leaves";
    const awardsBase = "/user/awards";

    // Construction
    const projectsBase = "/user/projects";
    const costCodesBase = "/user/cost_codes";
    const projectGroupsBase = "/user/project_groups";
    const projectSubcontractsBase = "/user/project_subcontracts";
    
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
    const businessSettingsFragment = "/business/settings/";
    const businessUserManagementFragment = "/business/user-management";
    const businessInvitationsFragment = "/business/invitations";

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
    // Dashboard
    // ——————————————————————————————————
    const dashboardItems = [
        {
            title: "Dashboard",
            url: route("dashboard.index"),
            icon: PieChart,
            isActive: url.startsWith(dashboardBase),
        },
    ];

    // ——————————————————————————————————
    // Build Operations section
    // ——————————————————————————————————
    const navOperationsItems = [];

    // Products
    if (hasAnyPermission([
        "products.view", "products.create", "main_categories.view", "sub_categories.view",
        "brands.view", "product_units.view", "inventory_adjustments.view", "inventory_transfers.view"
    ])) {
        const items = [];
        if (hasPermission("products.view") || hasPermission("products.create")) {
            items.push({
                title: "All Products",
                url: route("products.index"),
                isActive: url.startsWith(productsBase),
            });
        }
        if (hasPermission("main_categories.view")) {
            items.push({
                title: "Main Categories",
                url: route("main_categories.index"),
                isActive: url.startsWith(mainCategoryBase),
            });
        }
        if (hasPermission("sub_categories.view")) {
            items.push({
                title: "Sub Categories",
                url: route("sub_categories.index"),
                isActive: url.startsWith(subCategoriesBase),
            });
        }
        if (hasPermission("brands.view")) {
            items.push({
                title: "Brands",
                url: route("brands.index"),
                isActive: url.startsWith(brandsBase),
            });
        }
        if (hasPermission("product_units.view")) {
            items.push({
                title: "Units",
                url: route("product_units.index"),
                isActive: url.startsWith(unitsBase),
            });
        }
        if (hasPermission("inventory_adjustments.view")) {
            items.push({
                title: "Inventory Adjustment",
                url: route("inventory_adjustments.index"),
                isActive: url.startsWith(invAdjustBase),
            });
        }
        if (hasPermission("inventory_transfers.view")) {
            items.push({
                title: "Inventory Transfer",
                url: route("inventory_transfers.index"),
                isActive: url.startsWith(invTransferBase),
            });
        }

        if (items.length > 0) {
            navOperationsItems.push({
                title: "Products",
                url: "#",
                icon: Package,
                isActive: items.some(i => i.isActive),
                items,
            });
        }
    }

    // Suppliers
    if (hasAnyPermission([
        "vendors.view", "purchase_orders.view", "cash_purchases.view",
        "bill_invoices.view", "bill_payments.view", "purchase_returns.view"
    ])) {
        const items = [];
        if (hasPermission("vendors.view")) {
            items.push({
                title: "All Suppliers",
                url: route("vendors.index"),
                isActive: url.startsWith(vendorsBase),
            });
        }
        if (hasPermission("purchase_orders.view")) {
            items.push({
                title: "Purchase Order",
                url: route("purchase_orders.index"),
                isActive: url.startsWith(purchaseOrdersBase),
            });
        }
        if (hasPermission("cash_purchases.view")) {
            items.push({
                title: "Cash Purchase",
                url: route("cash_purchases.index"),
                isActive: url.startsWith(cashPurchasesBase),
            });
        }
        if (hasPermission("bill_invoices.view")) {
            items.push({
                title: "Credit Purchase",
                url: route("bill_invoices.index"),
                isActive: url.startsWith(billInvoicesBase),
            });
        }
        if (hasPermission("bill_payments.view")) {
            items.push({
                title: "Pay Credit Purchase",
                url: route("bill_payments.index"),
                isActive: url.startsWith(billPaymentsBase),
            });
        }
        if (hasPermission("purchase_returns.view")) {
            items.push({
                title: "Purchase Return",
                url: route("purchase_returns.index"),
                isActive: url.startsWith(purchaseReturnsBase),
            });
        }

        if (items.length > 0) {
            navOperationsItems.push({
                title: "Suppliers",
                url: "#",
                icon: Users,
                isActive: items.some(i => i.isActive),
                items,
            });
        }
    }

    // Customers
    if (hasAnyPermission([
        "customers.view", "receipts.view", "invoices.view", "medical_records.view",
        "quotations.view", "deffered_invoices.view", "sales_returns.view",
        "prescriptions.view", "receive_payments.view"
    ])) {
        const items = [];
        if (hasPermission("customers.view")) {
            items.push({
                title: "All Customers",
                url: route("customers.index"),
                isActive: url.startsWith(customersBase),
            });
        }
        if (hasPermission("receipts.view")) {
            items.push({
                title: "Cash Invoice",
                url: route("receipts.index"),
                isActive: url.startsWith(receiptsBase),
            });
        }
        if (hasPermission("invoices.view")) {
            items.push({
                title: "Credit Invoice",
                url: route("invoices.index"),
                isActive: url.startsWith(creditInvoicesBase),
            });
        }
        if (userPackage?.medical_record === 1 && hasPermission("medical_records.view")) {
            items.push({
                title: "Medical Records",
                url: route("medical_records.index"),
                isActive: url.startsWith(medicalRecordsBase),
            });
        }
        if (userPackage?.prescription === 1 && hasPermission("prescriptions.view")) {
            items.push({
                title: "Prescriptions",
                url: route("prescriptions.index"),
                isActive: url.startsWith(prescriptionsBase),
            });
        }
        if (userPackage?.deffered_invoice === 1 && hasPermission("deffered_invoices.view")) {
            items.push({
                title: "Deferred Invoice",
                url: route("deffered_invoices.index"),
                isActive: url.startsWith(deferredInvoicesBase),
            });
        }
        if (hasPermission("receive_payments.view")) {
            items.push({
                title: "Received Payment",
                url: route("receive_payments.index"),
                isActive: url.startsWith(receivePaymentsBase),
            });
        }
        if (hasPermission("sales_returns.view")) {
            items.push({
                title: "Sales Return",
                url: route("sales_returns.index"),
                isActive: url.startsWith(salesReturnsBase),
            });
        }
        if (hasPermission("quotations.view")) {
            items.push({
                title: "Quotations",
                url: route("quotations.index"),
                isActive: url.startsWith(quotationsBase),
            });
        }

        if (items.length > 0) {
            navOperationsItems.push({
                title: "Customers",
                url: "#",
                icon: Users,
                isActive: items.some(i => i.isActive),
                items,
            });
        }
    }

    // ——————————————————————————————————
    // Build Management section
    // ——————————————————————————————————
    const navManagementItems = [];

    // HR & Payroll
    if (userPackage?.payroll_module === 1 && hasAnyPermission([
        "staffs.view", "attendance_devices.view", "attendance_logs.view", "timesheets.view",
        "departments.view", "designations.view", "salary_advances.view", "payslips.view",
        "holidays.view", "leaves.view", "awards.view"
    ])) {
        const items = [];
        if (hasPermission("staffs.view")) {
            items.push({
                title: "Staff Management",
                url: route("staffs.index"),
                isActive: url.startsWith(staffsBase),
            });
        }
        if (userPackage?.time_sheet_module === 1 && hasPermission("attendance_devices.view")) {
            items.push({
                title: "Attendance Devices",
                url: route("attendance_devices.index"),
                isActive: url.startsWith(attendanceDevicesBase),
            });
        }
        if (userPackage?.time_sheet_module === 1 && hasPermission("attendance_logs.view")) {
            items.push({
                title: "Attendance Logs",
                url: route("attendance_logs.index"),
                isActive: url.startsWith(attendanceLogsBase),
            });
        }
        if (userPackage?.time_sheet_module === 1 && hasPermission("timesheets.view")) {
            items.push({
                title: "Time Sheet",
                url: route("timesheets.index"),
                isActive: url.startsWith(timeSheetsBase),
            });
        }
        if (hasPermission("departments.view")) {
            items.push({
                title: "Departments",
                url: route("departments.index"),
                isActive: url.startsWith(departmentsBase),
            });
        }
        if (hasPermission("designations.view")) {
            items.push({
                title: "Designations",
                url: route("designations.index"),
                isActive: url.startsWith(designationsBase),
            });
        }
        if (hasPermission("salary_advances.view")) {
            items.push({
                title: "Salary Advances",
                url: route("salary_advances.index"),
                isActive: url.startsWith(salaryAdvancesBase),
            });
        }
        if (hasPermission("payslips.view")) {
            items.push({
                title: "Manage Payroll",
                url: route("payslips.index"),
                isActive: url.startsWith(payslipsBase),
            });
        }
        if (hasPermission("holidays.view")) {
            items.push({
                title: "Holidays",
                url: route("holidays.index"),
                isActive: url.startsWith(holidaysBase),
            });
        }
        if (hasPermission("leaves.view")) {
            items.push({
                title: "Leave Management",
                url: route("leaves.index"),
                isActive: url.startsWith(leavesBase),
            });
        }
        if (hasPermission("awards.view")) {
            items.push({
                title: "Awards",
                url: route("awards.index"),
                isActive: url.startsWith(awardsBase),
            });
        }

        if (items.length > 0) {
            navManagementItems.push({
                title: "HR & Payroll",
                url: "#",
                icon: GroupIcon,
                isActive: items.some(i => i.isActive),
                items,
            });
        }
    }

    // Construction
    if (userPackage?.construction_module === 1 && hasAnyPermission([
        "projects.view", "project_groups.view", "cost_codes.view", "project_subcontracts.view"
    ])) {
        const items = [];
        if (hasPermission("projects.view")) {
            items.push({
                title: "Projects",
                url: route("projects.index"),
                isActive: url.startsWith(projectsBase),
            });
        }
        if (hasPermission("project_groups.view")) {
            items.push({
                title: "Project Groups",
                url: route("project_groups.index"),
                isActive: url.startsWith(projectGroupsBase),
            });
        }
        if (hasPermission("cost_codes.view")) {
            items.push({
                title: "Cost Codes",
                url: route("cost_codes.index"),
                isActive: url.startsWith(costCodesBase),
            });
        }
        if (hasPermission("project_subcontracts.view")) {
            items.push({
                title: "Subcontracts",
                url: route("project_subcontracts.index"),
                isActive: url.startsWith(projectSubcontractsBase),
            });
        }

        if (items.length > 0) {
            navManagementItems.push({
                title: "Construction",
                url: "#",
                icon: Building,
                isActive: items.some(i => i.isActive),
                items,
            });
        }
    }

    // Accounting
    if (hasAnyPermission(["accounts.view", "journals.view", "transaction_methods.view"])) {
        const items = [];
        if (hasPermission("accounts.view")) {
            items.push({
                title: "Chart of Accounts",
                url: route("accounts.index"),
                isActive: url.startsWith(accountsBase),
            });
        }
        if (hasPermission("journals.view")) {
            items.push({
                title: "Journal Entry",
                url: route("journals.index"),
                isActive: url.startsWith(journalsBase),
            });
        }
        if (hasPermission("transaction_methods.view")) {
            items.push({
                title: "Transaction Methods",
                url: route("transaction_methods.index"),
                isActive: url.startsWith(transactionMethodsBase),
            });
        }

        if (items.length > 0) {
            navManagementItems.push({
                title: "Accounting",
                url: "#",
                icon: ChartPieIcon,
                isActive: items.some(i => i.isActive),
                items,
            });
        }
    }

    // Business
    if (hasAnyPermission([
        "business.view", "business.roles-permissions", "business.user-management", "business.settings",
        "taxes.view", "currency.view", "audit_logs.view"
    ])) {
        const items = [];
        if (hasPermission("business.view")) {
            items.push({
                title: "Manage Businesses",
                url: route("business.index"),
                isActive: url.startsWith(businessesBase),
            });
        }
        if (hasPermission("business.roles-permissions")) {
            items.push({
                title: "Roles & Permissions",
                url: route("roles.index"),
                isActive: url.startsWith(rolesBase),
            });
        }
        if (hasPermission("business.user-management")) {
            items.push({
                title: "User Management",
                url: route("business.user-management"),
                isActive: url.includes(businessUserManagementFragment) || url.includes(businessInvitationsFragment),
            });
        }
        if (hasPermission("business.settings")) {
            items.push({
                title: "Business Settings",
                url: route("business.settings", activeBusiness?.id),
                isActive: url.includes(businessSettingsFragment),
            });
        }
        if (hasPermission("taxes.view")) {
            items.push({
                title: "Tax Settings",
                url: route("taxes.index"),
                isActive: url.startsWith(taxesBase),
            });
        }
        if (hasPermission("currency.view")) {
            items.push({
                title: "Currency Settings",
                url: route("currency.index"),
                isActive: url.startsWith(currencyBase),
            });
        }
        if (hasPermission("audit_logs.view")) {
            items.push({
                title: "Audit Logs",
                url: route("audit_logs.index"),
                isActive: url.startsWith(auditLogsBase),
            });
        }

        if (items.length > 0) {
            navManagementItems.push({
                title: "Business",
                url: "#",
                icon: Building2Icon,
                isActive: items.some(i => i.isActive),
                items,
            });
        }
    }

    // Reports
    if (hasAnyPermission([
        "reports.journal", "reports.ledger", "reports.income_statement", "reports.trial_balance",
        "reports.balance_sheet", "reports.receivables", "reports.payables", "reports.payroll_summary",
        "reports.payroll_cost", "reports.income_by_customer", "reports.inventory_details", "reports.inventory_summary"
    ])) {
        const items = [];
        if (hasPermission("reports.journal")) {
            items.push({
                title: "General Journal",
                url: route("reports.journal"),
                isActive: url.startsWith(reportsJournalBase),
            });
        }
        if (hasPermission("reports.ledger")) {
            items.push({
                title: "General Ledger",
                url: route("reports.ledger"),
                isActive: url.startsWith(reportsLedgerBase),
            });
        }
        if (hasPermission("reports.income_statement")) {
            items.push({
                title: "Income Statement",
                url: route("reports.income_statement"),
                isActive: url.startsWith(reportsIncomeStatement),
            });
        }
        if (hasPermission("reports.trial_balance")) {
            items.push({
                title: "Trial Balance",
                url: route("reports.trial_balance"),
                isActive: url.startsWith(reportsTrialBalance),
            });
        }
        if (hasPermission("reports.balance_sheet")) {
            items.push({
                title: "Balance Sheet",
                url: route("reports.balance_sheet"),
                isActive: url.startsWith(reportsBalanceSheet),
            });
        }
        if (hasPermission("reports.income_by_customer")) {
            items.push({
                title: "Income By Customer",
                url: route("reports.income_by_customer"),
                isActive: url.startsWith(reportsIncomeByCustomer),
            });
        }
        if (hasPermission("reports.receivables")) {
            items.push({
                title: "Receivables",
                url: route("reports.receivables"),
                isActive: url.startsWith(reportsReceivables),
            });
        }
        if (hasPermission("reports.payables")) {
            items.push({
                title: "Payables",
                url: route("reports.payables"),
                isActive: url.startsWith(reportsPayables),
            });
        }
        if (hasPermission("reports.payroll_summary")) {
            items.push({
                title: "Payroll Summary",
                url: route("reports.payroll_summary"),
                isActive: url.startsWith(reportsPayrollSummary),
            });
        }
        if (hasPermission("reports.payroll_cost")) {
            items.push({
                title: "Monthly Payroll Cost",
                url: route("reports.payroll_cost"),
                isActive: url.startsWith(reportsPayrollCost),
            });
        }
        if (hasPermission("reports.inventory_details")) {
            items.push({
                title: "Inventory Details",
                url: route("reports.inventory_details"),
                isActive: url.startsWith(reportsInventoryDetails),
            });
        }
        if (hasPermission("reports.inventory_summary")) {
            items.push({
                title: "Inventory Summary",
                url: route("reports.inventory_summary"),
                isActive: url.startsWith(reportsInventorySummary),
            });
        }

        if (items.length > 0) {
            navManagementItems.push({
                title: "Reports",
                url: "#",
                icon: Building2Icon,
                isActive: items.some(i => i.isActive),
                items,
            });
        }
    }

    return (
        <Sidebar collapsible="icon" {...props}>
            <SidebarHeader>
                <BusinessSwitcher />
            </SidebarHeader>
            <SidebarContent>
                <NavUserDashboard items={dashboardItems} />
                {navOperationsItems.length > 0 && (
                    <NavOperations items={navOperationsItems} />
                )}
                {navManagementItems.length > 0 && (
                    <NavManagement items={navManagementItems} />
                )}
            </SidebarContent>
            <SidebarFooter>
                <NavUser user={data.user} />
            </SidebarFooter>
            <SidebarRail />
        </Sidebar>
    );
}
