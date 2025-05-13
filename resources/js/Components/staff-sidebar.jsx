"use client";

import * as React from "react";
import {
  PieChart,
  Users,
  Package,
  GroupIcon,
  ChartPieIcon,
  Building2Icon,
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

export function StaffSidebar(props) {
  const page = usePage();
  const url = page.url;
  const { auth, activeBusiness, permissionList } = page.props;

  // turn permissions into a Set for quick lookups
  const perms = new Set(permissionList.map((p) => p.permission));

  // ─── 1) ALL BASE PATHS ─────────────────────────────────────
  const dashboardBase = "/dashboard";

  // Products
  const productsBase = "/user/products";
  const mainCategoriesBase = "/user/main_categories";
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

  // Accounting
  const accountsBase = "/user/accounts";
  const journalsBase = "/user/journals";
  const transactionMethodsBase = "/user/transaction_methods";

  // Business
  const businessesBase = "/user/business";
  const rolesBase = "/user/roles";
  const businessSettingsBase = "/business/settings/"; // detect via `.includes`
  const taxesBase = "/user/taxes";
  const currencyBase = "/user/currency";
  const auditLogsBase = "/user/audit_logs";

  // Reports
  const reportsJournalBase = "/user/reports/journal";
  const reportsLedgerBase = "/user/reports/ledger";
  const reportsIncomeStatementBase = "/user/reports/income_statement";
  const reportsTrialBalanceBase = "/user/reports/trial_balance";
  const reportsBalanceSheetBase = "/user/reports/balance_sheet";
  const reportsReceivablesBase = "/user/reports/receivables";
  const reportsPayablesBase = "/user/reports/payables";
  const reportsPayrollSummaryBase = "/user/reports/payroll_summary";
  const reportsPayrollCostBase = "/user/reports/payroll_cost";
  const reportsIncomeByCustomerBase = "/user/reports/income_by_customer";
  const reportsInventoryDetailsBase = "/user/reports/inventory_details";
  const reportsInventorySummaryBase = "/user/reports/inventory_summary";

  // ─── 2) DASHBOARD SECTION ──────────────────────────────────
  const dashboardItems = [
    {
      title: "Dashboard",
      url: route("dashboard.index"),
      icon: PieChart,
      isActive: url.startsWith(dashboardBase),
    },
  ];

  // ─── 3) OPERATIONS ─────────────────────────────────────────
  const navOperationsItems = [];

  // Products
  if ([
    "products.index",
    "products.create",
    "main_categories.index",
    "sub_categories.index",
    "brands.index",
    "product_units.index",
    "inventory_adjustments.index",
  ].some((p) => perms.has(p))) {
    const items = [];
    if (perms.has("products.index") || perms.has("products.create")) {
      items.push({
        title: "All Products",
        url: route("products.index"),
        isActive:
          url === productsBase || url.startsWith(productsBase + "/"),
      });
    }
    if (perms.has("main_categories.index")) {
      items.push({
        title: "Main Categories",
        url: route("main_categories.index"),
        isActive:
          url === mainCategoriesBase ||
          url.startsWith(mainCategoriesBase + "/"),
      });
    }
    if (perms.has("sub_categories.index")) {
      items.push({
        title: "Sub Categories",
        url: route("sub_categories.index"),
        isActive: url.startsWith(subCategoriesBase),
      });
    }
    if (perms.has("brands.index")) {
      items.push({
        title: "Brands",
        url: route("brands.index"),
        isActive: url.startsWith(brandsBase),
      });
    }
    if (perms.has("product_units.index")) {
      items.push({
        title: "Units",
        url: route("product_units.index"),
        isActive: url.startsWith(unitsBase),
      });
    }
    if (perms.has("inventory_adjustments.index")) {
      items.push({
        title: "Inventory Adjustment",
        url: route("inventory_adjustments.index"),
        isActive: url.startsWith(invAdjustBase),
      });
    }

    navOperationsItems.push({
      title: "Products",
      icon: Package,
      url: "#",
      isActive: items.some((i) => i.isActive),
      items,
    });
  }

  // Suppliers
  if ([
    "vendors.index",
    "purchase_orders.index",
    "cash_purchases.index",
    "bill_invoices.index",
    "bill_payments.index",
    "purchase_returns.index",
  ].some((p) => perms.has(p))) {
    const items = [];
    if (perms.has("vendors.index")) {
      items.push({
        title: "All Suppliers",
        url: route("vendors.index"),
        isActive: url.startsWith(vendorsBase),
      });
    }
    if (perms.has("purchase_orders.index")) {
      items.push({
        title: "Purchase Order",
        url: route("purchase_orders.index"),
        isActive: url.startsWith(purchaseOrdersBase),
      });
    }
    if (perms.has("cash_purchases.index")) {
      items.push({
        title: "Cash Purchase",
        url: route("cash_purchases.index"),
        isActive: url.startsWith(cashPurchasesBase),
      });
    }
    if (perms.has("bill_invoices.index")) {
      items.push({
        title: "Bill Invoice",
        url: route("bill_invoices.index"),
        isActive: url.startsWith(billInvoicesBase),
      });
    }
    if (perms.has("bill_payments.index")) {
      items.push({
        title: "Pay Bills",
        url: route("bill_payments.index"),
        isActive: url.startsWith(billPaymentsBase),
      });
    }
    if (perms.has("purchase_returns.index")) {
      items.push({
        title: "Purchase Return",
        url: route("purchase_returns.index"),
        isActive: url.startsWith(purchaseReturnsBase),
      });
    }

    navOperationsItems.push({
      title: "Suppliers",
      icon: Users,
      url: "#",
      isActive: items.some((i) => i.isActive),
      items,
    });
  }

  // Customers
  if ([
    "customers.index",
    "receipts.index",
    "invoices.index",
    "medical_records.index",
    "quotations.index",
    "deffered_invoices.index",
    "sales_returns.index",
    "prescriptions.index",
    "receive_payments.index",
  ].some((p) => perms.has(p))) {
    const items = [];
    if (perms.has("customers.index")) {
      items.push({
        title: "All Customers",
        url: route("customers.index"),
        isActive: url.startsWith(customersBase),
      });
    }
    if (perms.has("receipts.index")) {
      items.push({
        title: "Cash Invoice",
        url: route("receipts.index"),
        isActive: url.startsWith(receiptsBase),
      });
    }
    if (perms.has("invoices.index")) {
      items.push({
        title: "Credit Invoice",
        url: route("invoices.index"),
        isActive: url.startsWith(creditInvoicesBase),
      });
    }
    if (perms.has("medical_records.index")) {
      items.push({
        title: "Medical Records",
        url: route("medical_records.index"),
        isActive: url.startsWith(medicalRecordsBase),
      });
    }
    if (perms.has("prescriptions.index")) {
      items.push({
        title: "Prescriptions",
        url: route("prescriptions.index"),
        isActive: url.startsWith(prescriptionsBase),
      });
    }
    if (perms.has("deffered_invoices.index")) {
      items.push({
        title: "Deferred Invoice",
        url: route("deffered_invoices.index"),
        isActive: url.startsWith(deferredInvoicesBase),
      });
    }
    if (perms.has("receive_payments.index")) {
      items.push({
        title: "Received Payment",
        url: route("receive_payments.index"),
        isActive: url.startsWith(receivePaymentsBase),
      });
    }
    if (perms.has("sales_returns.index")) {
      items.push({
        title: "Sales Return",
        url: route("sales_returns.index"),
        isActive: url.startsWith(salesReturnsBase),
      });
    }
    if (perms.has("quotations.index")) {
      items.push({
        title: "Quotations",
        url: route("quotations.index"),
        isActive: url.startsWith(quotationsBase),
      });
    }

    navOperationsItems.push({
      title: "Customers",
      icon: Users,
      url: "#",
      isActive: items.some((i) => i.isActive),
      items,
    });
  }

  // ─── 4) MANAGEMENT ──────────────────────────────────────────
  const navManagementItems = [];

  // HR & Payroll
  if ([
    "staffs.index",
    "attendance.index",
    "departments.index",
    "designations.index",
    "payslips.index",
    "holidays.index",
    "leaves.index",
    "awards.index",
  ].some((p) => perms.has(p))) {
    const items = [];
    if (perms.has("staffs.index")) {
      items.push({
        title: "Staff Management",
        url: route("staffs.index"),
        isActive: url.startsWith(staffsBase),
      });
    }
    if (perms.has("attendance.index")) {
      items.push({
        title: "Attendance",
        url: route("attendance.index"),
        isActive: url.startsWith(attendanceBase),
      });
    }
    if (perms.has("departments.index")) {
      items.push({
        title: "Departments",
        url: route("departments.index"),
        isActive: url.startsWith(departmentsBase),
      });
    }
    if (perms.has("designations.index")) {
      items.push({
        title: "Designations",
        url: route("designations.index"),
        isActive: url.startsWith(designationsBase),
      });
    }
    if (perms.has("payslips.index")) {
      items.push({
        title: "Manage Payroll",
        url: route("payslips.index"),
        isActive: url.startsWith(payslipsBase),
      });
    }
    if (perms.has("holidays.index")) {
      items.push({
        title: "Holidays",
        url: route("holidays.index"),
        isActive: url.startsWith(holidaysBase),
      });
    }
    if (perms.has("leaves.index")) {
      items.push({
        title: "Leave Management",
        url: route("leaves.index"),
        isActive: url.startsWith(leavesBase),
      });
    }
    if (perms.has("awards.index")) {
      items.push({
        title: "Awards",
        url: route("awards.index"),
        isActive: url.startsWith(awardsBase),
      });
    }

    navManagementItems.push({
      title: "HR & Payroll",
      icon: GroupIcon,
      url: "#",
      isActive: items.some((i) => i.isActive),
      items,
    });
  }

  // Accounting
  if (
    perms.has("accounts.index") ||
    perms.has("journals.index") ||
    perms.has("transaction_methods.index")
  ) {
    const items = [];
    if (perms.has("accounts.index")) {
      items.push({
        title: "Chart of Accounts",
        url: route("accounts.index"),
        isActive: url.startsWith(accountsBase),
      });
    }
    if (perms.has("journals.index")) {
      items.push({
        title: "Journal Entry",
        url: route("journals.index"),
        isActive: url.startsWith(journalsBase),
      });
    }
    if (perms.has("transaction_methods.index")) {
      items.push({
        title: "Transaction Methods",
        url: route("transaction_methods.index"),
        isActive: url.startsWith(transactionMethodsBase),
      });
    }

    navManagementItems.push({
      title: "Accounting",
      icon: ChartPieIcon,
      url: "#",
      isActive: items.some((i) => i.isActive),
      items,
    });
  }

  // Business
  if ([
    "business.index",
    "roles.index",
    "business.settings",
    "taxes.index",
    "currency.index",
    "audit_logs.index",
  ].some((p) => perms.has(p))) {
    const items = [];
    if (perms.has("business.index")) {
      items.push({
        title: "Manage Businesses",
        url: route("business.index"),
        isActive: url.startsWith(businessesBase),
      });
    }
    if (perms.has("roles.index")) {
      items.push({
        title: "Roles & Permissions",
        url: route("roles.index"),
        isActive: url.startsWith(rolesBase),
      });
    }
    if (perms.has("business.settings")) {
      items.push({
        title: "Business Settings",
        url: route("business.settings", activeBusiness.id),
        isActive: url.includes(businessSettingsBase),
      });
    }
    if (perms.has("taxes.index")) {
      items.push({
        title: "Tax Settings",
        url: route("taxes.index"),
        isActive: url.startsWith(taxesBase),
      });
    }
    if (perms.has("currency.index")) {
      items.push({
        title: "Currency Settings",
        url: route("currency.index"),
        isActive: url.startsWith(currencyBase),
      });
    }
    if (perms.has("audit_logs.index")) {
      items.push({
        title: "Audit Logs",
        url: route("audit_logs.index"),
        isActive: url.startsWith(auditLogsBase),
      });
    }

    navManagementItems.push({
      title: "Business",
      icon: Building2Icon,
      url: "#",
      isActive: items.some((i) => i.isActive),
      items,
    });
  }

  // Reports
  if (
    perms.has("reports.journal") ||
    perms.has("reports.ledger") ||
    perms.has("reports.income_statement") ||
    perms.has("reports.trial_balance") ||
    perms.has("reports.balance_sheet") ||
    perms.has("reports.receivables") ||
    perms.has("reports.payables") ||
    perms.has("reports.payroll_summary") ||
    perms.has("reports.payroll_cost") ||
    perms.has("reports.income_by_customer")
  ) {
    const items = [];
    if (perms.has("reports.journal")) {
      items.push({
        title: "General Journal",
        url: route("reports.journal"),
        isActive: url.startsWith(reportsJournalBase),
      });
    }
    if (perms.has("reports.ledger")) {
      items.push({
        title: "General Ledger",
        url: route("reports.ledger"),
        isActive: url.startsWith(reportsLedgerBase),
      });
    }
    if (perms.has("reports.income_statement")) {
      items.push({
        title: "Income Statement",
        url: route("reports.income_statement"),
        isActive: url.startsWith(reportsIncomeStatementBase),
      });
    }
    if (perms.has("reports.trial_balance")) {
      items.push({
        title: "Trial Balance",
        url: route("reports.trial_balance"),
        isActive: url.startsWith(reportsTrialBalanceBase),
      });
    }
    if (perms.has("reports.balance_sheet")) {
      items.push({
        title: "Balance Sheet",
        url: route("reports.balance_sheet"),
        isActive: url.startsWith(reportsBalanceSheetBase),
      });
    }
    if (perms.has("reports.receivables")) {
      items.push({
        title: "Receivables",
        url: route("reports.receivables"),
        isActive: url.startsWith(reportsReceivablesBase),
      });
    }
    if (perms.has("reports.payables")) {
      items.push({
        title: "Payables",
        url: route("reports.payables"),
        isActive: url.startsWith(reportsPayablesBase),
      });
    }
    if (perms.has("reports.payroll_summary")) {
      items.push({
        title: "Payroll Summary",
        url: route("reports.payroll_summary"),
        isActive: url.startsWith(reportsPayrollSummaryBase),
      });
    }
    if (perms.has("reports.payroll_cost")) {
      items.push({
        title: "Monthly Payroll Cost",
        url: route("reports.payroll_cost"),
        isActive: url.startsWith(reportsPayrollCostBase),
      });
    }
    if (perms.has("reports.income_by_customer")) {
      items.push({
        title: "Income By Customer",
        url: route("reports.income_by_customer"),
        isActive: url.startsWith(reportsIncomeByCustomerBase),
      });
    }

    navManagementItems.push({
      title: "Reports",
      icon: Building2Icon,
      url: "#",
      isActive: items.some((i) => i.isActive),
      items,
    });
  }

  // user for footer
  const user = {
    id: auth.user.id,
    name: auth.user.name,
    email: auth.user.email,
    avatar: `/uploads/media/${auth.user.profile_picture}`,
  };

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
        <NavUser user={user} />
      </SidebarFooter>

      <SidebarRail />
    </Sidebar>
  );
}
