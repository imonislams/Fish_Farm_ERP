/**
 * Navigation — the React mirror of config/navigation.php (single source of truth).
 *
 * IMPORTANT: keep this in sync with config/navigation.php. It is a MIRROR, not a
 * second source of truth: the PHP config remains authoritative and the sidebar also
 * filters by the user's permissions at render time.
 *
 * Shape mirrors the PHP file exactly:
 *   flat  : { label, route, icon, permission }
 *   group : { label, icon, children: [ … ] }
 *
 * `route` is the Laravel ROUTE NAME. The URL is resolved from the shared
 * `routes` prop that Laravel sends per request (see AppLayout) — the frontend
 * never hard-codes URLs.
 */
export const NAVIGATION = [
    {
        label: "Dashboard",
        route: "dashboard",
        icon: "grid",
        permission: "dashboard.view",
    },

    {
        label: "Pond Management",
        icon: "droplet",
        children: [
            {
                label: "All Ponds",
                route: "ponds.index",
                permission: "pond.view",
            },
            {
                label: "New Pond",
                route: "ponds.create",
                permission: "pond.create",
            },
            {
                label: "Pond Types",
                route: "ponds.types.index",
                permission: "pond_type.view",
            },
            {
                label: "Pond Status",
                route: "ponds.status",
                permission: "pond.view",
            },
        ],
    },

    {
        label: "Pond Ledger",
        icon: "book",
        children: [
            {
                label: "Pond Ledger",
                route: "ledger.index",
                permission: "pond_ledger.view",
            },
            {
                label: "Stocking",
                route: "ledger.stocking",
                permission: "pond_ledger.view",
            },
            {
                label: "Death (Mortality)",
                route: "ledger.mortality",
                permission: "pond_ledger.view",
            },
            {
                label: "Transfers",
                route: "ledger.transfers",
                permission: "pond_ledger.view",
            },
            {
                label: "Money Entries",
                route: "ledger.transactions",
                permission: "ledger.view",
            },
            {
                label: "New Entry",
                route: "ledger.transactions.create",
                permission: "ledger.create",
            },
        ],
    },

    {
        label: "Food Management",
        icon: "feed",
        children: [
            {
                label: "Food Dashboard",
                route: "feed.index",
                permission: "feed.view",
            },
            {
                label: "Food Types",
                route: "feed.types.index",
                permission: "feed.type.manage",
            },
            {
                label: "Food Stock",
                route: "feed.stock",
                permission: "feed.view",
            },
            {
                label: "Food Purchase",
                route: "feed.purchases.index",
                permission: "feed.purchase",
            },
            {
                label: "Food Usage",
                route: "feed.usages.index",
                permission: "feed.usage",
            },
            {
                label: "Feeding",
                route: "feed.feedings.index",
                permission: "feed.view",
            },
            {
                label: "Feeding Schedules",
                route: "feed.schedules.index",
                permission: "feed.schedule.manage",
            },
            {
                label: "Stock Adjustment",
                route: "feed.adjustments.index",
                permission: "feed.adjust",
            },
        ],
    },

    {
        label: "FCR & Growth",
        icon: "chart",
        children: [
            {
                label: "FCR Dashboard",
                route: "fcr.index",
                permission: "fcr.view",
            },
            {
                label: "Pond Inspection",
                route: "fcr.inspections.index",
                permission: "fcr.view",
            },
            {
                label: "New Inspection",
                route: "fcr.inspections.create",
                permission: "fcr.inspection.create",
            },
            {
                label: "Growth Monitoring",
                route: "fcr.growth",
                permission: "growth.view",
            },
            {
                label: "Feed vs Growth",
                route: "fcr.feed-growth",
                permission: "fcr.view",
            },
            {
                label: "Pond Comparison",
                route: "fcr.comparison",
                permission: "fcr.view",
            },
            {
                label: "Inspection Schedule",
                route: "fcr.schedules.index",
                permission: "fcr.schedule.manage",
            },
            {
                label: "FCR Reports",
                route: "fcr.reports",
                permission: "fcr.view",
            },
        ],
    },

    {
        label: "Fish Stock",
        icon: "fish",
        children: [
            {
                label: "Stock Dashboard",
                route: "fish.index",
                permission: "fish.view",
            },
            {
                label: "Fish Batches",
                route: "fish.batches.index",
                permission: "fish.batch.view",
            },
            {
                label: "Fish Species",
                route: "fish.species.index",
                permission: "fish.species.manage",
            },
            {
                label: "Stock In / Stocking",
                route: "fish.stockings.index",
                permission: "fish.stock",
            },
            {
                label: "Mortality",
                route: "fish.mortalities.index",
                permission: "fish.mortality",
            },
            {
                label: "Harvest",
                route: "fish.harvests.index",
                permission: "fish.harvest",
            },
        ],
    },

    {
        label: "Sales & Accounts",
        icon: "cart",
        children: [
            {
                label: "Sales Dashboard",
                route: "sales.index",
                permission: "sales.view",
            },
            {
                label: "Fish Sales",
                route: "sales.list",
                permission: "sales.view",
            },
            {
                label: "Customers",
                route: "customers.index",
                permission: "customer.view",
            },
            {
                label: "Customer Payments",
                route: "customers.payments.index",
                permission: "customer.payment.create",
            },
            {
                label: "Customer Due",
                route: "customers.dues",
                permission: "customer.view",
            },
            {
                label: "Sales Ledger",
                route: "sales.ledger",
                permission: "sales.view",
            },
        ],
    },

    {
        label: "Suppliers",
        icon: "truck",
        children: [
            {
                label: "Supplier List",
                route: "suppliers.index",
                permission: "supplier.view",
            },
            {
                label: "Add Supplier",
                route: "suppliers.create",
                permission: "supplier.create",
            },
            {
                label: "Supplier Purchases",
                route: "suppliers.purchases.index",
                permission: "supplier.purchase.create",
            },
            {
                label: "Supplier Payments",
                route: "suppliers.payments.index",
                permission: "supplier.payment.create",
            },
            {
                label: "Supplier Due",
                route: "suppliers.dues",
                permission: "supplier.view",
            },
        ],
    },

    {
        label: "Party",
        icon: "users",
        children: [
            {
                label: "Party List",
                route: "parties.index",
                permission: "party.view",
            },
            {
                label: "Add Party",
                route: "parties.create",
                permission: "party.create",
            },
            {
                label: "Party Transactions",
                route: "parties.transactions",
                permission: "party.transaction.create",
            },
            {
                label: "Party Ledger",
                route: "parties.ledger",
                permission: "party.view",
            },
        ],
    },

    {
        label: "Finance",
        icon: "report",
        children: [
            {
                label: "Income",
                route: "finance.income.index",
                permission: "finance.view",
            },
            {
                label: "Expenses",
                route: "finance.expenses.index",
                permission: "finance.view",
            },
            {
                label: "Expense Categories",
                route: "finance.categories.index",
                permission: "finance.view",
            },
        ],
    },

    {
        label: "Reports",
        icon: "report",
        children: [
            {
                label: "Sales Report",
                route: "reports.sales",
                permission: "reports.view",
            },
            {
                label: "Purchase Report",
                route: "reports.purchases",
                permission: "reports.view",
            },
            {
                label: "Food Report",
                route: "reports.feed",
                permission: "reports.view",
            },
            {
                label: "Fish Stock Report",
                route: "reports.fish-stock",
                permission: "reports.view",
            },
            {
                label: "Pond Report",
                route: "reports.ponds",
                permission: "reports.view",
            },
            {
                label: "FCR & Growth Report",
                route: "reports.fcr-growth",
                permission: "reports.view",
            },
            {
                label: "Income Report",
                route: "reports.income",
                permission: "reports.financial",
            },
            {
                label: "Expense Report",
                route: "reports.expenses",
                permission: "reports.financial",
            },
            {
                label: "Profit & Loss",
                route: "reports.profit-loss",
                permission: "reports.financial",
            },
        ],
    },

    {
        label: "Settings",
        icon: "cog",
        children: [
            {
                label: "Company Settings",
                route: "settings.company.edit",
                permission: "company.view",
            },
            {
                label: "User Profile",
                route: "settings.profile.edit",
                permission: null,
            },
            {
                label: "Users",
                route: "settings.users.index",
                permission: "users.view",
            },
            {
                label: "Roles",
                route: "settings.roles.index",
                permission: "roles.view",
            },
            {
                label: "Permissions",
                route: "settings.permissions.index",
                permission: "permissions.view",
            },
        ],
    },
];

export default NAVIGATION;
