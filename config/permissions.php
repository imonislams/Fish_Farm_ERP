<?php

/*
|--------------------------------------------------------------------------
| Fish Farm ERP — Roles & Permissions catalogue
|--------------------------------------------------------------------------
| SINGLE SOURCE OF TRUTH for the permission keys and the seeded roles.
|
| Consumed by:
|   - database/seeders/PermissionSeeder.php
|   - database/seeders/RoleSeeder.php
|   - the future Roles & Permissions admin UI (grouped display)
|
| Also documented in docs/PERMISSIONS.md — keep the two in step.
|
| RULES
|   - Permission names are STABLE IDENTIFIERS (`resource.action`). Renaming one
|     requires a migration and a CHANGELOG entry.
|   - Adding a role or permission here and re-seeding is idempotent: existing
|     roles/permissions are updated, never duplicated.
|   - This is the authorization catalogue for Version 1 (single company).
|     There is no tenant/company dimension — see docs/DATABASE.md §1.
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Permission catalogue
    |--------------------------------------------------------------------------
    | group => [permission name => human label]
    | The group key is used for grouped display in the admin UI.
    */
    'permissions' => [
        'Dashboard' => [
            'dashboard.view' => 'View dashboard',
        ],

        'Pond' => [
            'pond.view' => 'View ponds',
            'pond.create' => 'Create ponds',
            'pond.update' => 'Update ponds',
            'pond.delete' => 'Delete ponds',
            'pond.type.manage' => 'Manage pond types',
        ],

        'Feed' => [
            'feed.view' => 'View feed',
            'feed.purchase' => 'Record feed purchases',
            'feed.usage' => 'Record feed usage',
            'feed.adjust' => 'Adjust feed stock',
            'feed.type.manage' => 'Manage feed types',
        ],

        'Fish Stock' => [
            'fish.view' => 'View fish stock',
            'fish.stock' => 'Record stocking',
            'fish.mortality' => 'Record mortality',
            'fish.harvest' => 'Record harvest',
            'fish.species.manage' => 'Manage fish species',
        ],

        'FCR & Growth' => [
            'fcr.view' => 'View FCR & growth',
            'fcr.inspection.create' => 'Create inspections',
            'fcr.inspection.update' => 'Update inspections',
            'fcr.schedule.manage' => 'Manage inspection schedules',
            'growth.view' => 'View growth records',
            'growth.create' => 'Record growth samples',
        ],

        'Sales' => [
            'sales.view' => 'View sales',
            'sales.create' => 'Create sales',
            'sales.update' => 'Update sales',
            'sales.delete' => 'Delete sales',
            'sales.payment.create' => 'Record sales payments',
        ],

        'Customers' => [
            'customer.view' => 'View customers',
            'customer.create' => 'Create customers',
            'customer.update' => 'Update customers',
            'customer.delete' => 'Delete customers',
            'customer.payment.create' => 'Record customer payments',
        ],

        'Suppliers' => [
            'supplier.view' => 'View suppliers',
            'supplier.create' => 'Create suppliers',
            'supplier.update' => 'Update suppliers',
            'supplier.delete' => 'Delete suppliers',
            'supplier.purchase.create' => 'Record supplier purchases',
            'supplier.payment.create' => 'Record supplier payments',
        ],

        'Party' => [
            'party.view' => 'View parties',
            'party.create' => 'Create parties',
            'party.update' => 'Update parties',
            'party.delete' => 'Delete parties',
            'party.transaction.create' => 'Record party transactions',
        ],

        'Finance' => [
            'finance.view' => 'View finance',
            'ledger.view' => 'View ledgers',
            'income.create' => 'Record income',
            'income.update' => 'Update income',
            'income.delete' => 'Delete income',
            'expense.create' => 'Record expenses',
            'expense.update' => 'Update expenses',
            'expense.delete' => 'Delete expenses',
            'expense.category.manage' => 'Manage expense categories',
        ],

        'Reports' => [
            'reports.view' => 'View reports',
            'reports.export' => 'Export reports',
            'reports.financial' => 'View financial reports',
        ],

        /*
        | Foundation modules — ENFORCED now (Phase 1).
        | These drive the Settings area: company, users, roles, permissions.
        */
        'Company' => [
            'company.view' => 'View company information',
            'company.update' => 'Update company information',
        ],

        'Users' => [
            'users.view' => 'View users',
            'users.create' => 'Create users',
            'users.update' => 'Update users',
            'users.delete' => 'Delete users',
        ],

        'Roles' => [
            'roles.view' => 'View roles',
            'roles.create' => 'Create roles',
            'roles.update' => 'Update roles',
            'roles.delete' => 'Delete roles',
        ],

        'Permissions' => [
            'permissions.view' => 'View permissions',
            'permissions.manage' => 'Assign permissions to roles',
        ],

        'Settings' => [
            'settings.view' => 'View settings',
            'settings.update' => 'Update settings',
            'notifications.manage' => 'Manage notifications',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Seeded roles
    |--------------------------------------------------------------------------
    | name        machine key (stable, used by User::hasRole())
    | label       human-readable
    | is_system   protected from deletion in the UI
    | permissions permission keys granted
    |
    | `super_admin` is the ONLY role shortcut in the codebase: User::hasPermission()
    | returns true for it unconditionally. Every other role is defined purely by
    | its permission list.
    */
    'roles' => [

        'super_admin' => [
            'label' => 'Super Admin',
            'description' => 'Full system control, including company information, users, roles and permissions.',
            'is_system' => true,
            'permissions' => ['*'],
        ],

        'farm_admin' => [
            'label' => 'Farm Admin',
            'description' => 'Full control of the farm operations and its users, excluding company/role management.',
            'is_system' => true,
            'permissions' => [
                'dashboard.view',
                'pond.view', 'pond.create', 'pond.update', 'pond.delete', 'pond.type.manage',
                'feed.view', 'feed.purchase', 'feed.usage', 'feed.adjust', 'feed.type.manage',
                'fish.view', 'fish.stock', 'fish.mortality', 'fish.harvest', 'fish.species.manage',
                'fcr.view', 'fcr.inspection.create', 'fcr.inspection.update', 'fcr.schedule.manage',
                'growth.view', 'growth.create',
                'sales.view', 'sales.create', 'sales.update', 'sales.delete', 'sales.payment.create',
                'customer.view', 'customer.create', 'customer.update', 'customer.delete', 'customer.payment.create',
                'supplier.view', 'supplier.create', 'supplier.update', 'supplier.delete',
                'supplier.purchase.create', 'supplier.payment.create',
                'party.view', 'party.create', 'party.update', 'party.delete', 'party.transaction.create',
                'finance.view', 'ledger.view',
                'income.create', 'income.update', 'income.delete',
                'expense.create', 'expense.update', 'expense.delete', 'expense.category.manage',
                'reports.view', 'reports.export', 'reports.financial',
                'company.view', 'company.update',
                'users.view', 'users.create', 'users.update', 'users.delete',
                // Deliberately NOT roles.* / permissions.manage: role definition
                // stays with Super Admin. Matches this role's description.
                'roles.view', 'permissions.view',
                'settings.view', 'settings.update', 'notifications.manage',
            ],
        ],

        'manager' => [
            'label' => 'Manager',
            'description' => 'Day-to-day operations: ponds, feed, fish stock and inspections.',
            'is_system' => true,
            'permissions' => [
                'dashboard.view',
                'pond.view', 'pond.create', 'pond.update', 'pond.type.manage',
                'feed.view', 'feed.purchase', 'feed.usage', 'feed.adjust', 'feed.type.manage',
                'fish.view', 'fish.stock', 'fish.mortality', 'fish.harvest', 'fish.species.manage',
                'fcr.view', 'fcr.inspection.create', 'fcr.inspection.update', 'fcr.schedule.manage',
                'growth.view', 'growth.create',
                'sales.view', 'customer.view', 'supplier.view', 'party.view',
                'finance.view', 'ledger.view',
                'reports.view',
                'company.view', 'users.view', 'roles.view', 'settings.view',
            ],
        ],

        'accountant' => [
            'label' => 'Accountant',
            'description' => 'Sales, payments, purchases, ledgers and financial reporting.',
            'is_system' => true,
            'permissions' => [
                'dashboard.view',
                'pond.view', 'feed.view', 'fish.view', 'fcr.view', 'growth.view',
                'sales.view', 'sales.create', 'sales.update', 'sales.payment.create',
                'customer.view', 'customer.create', 'customer.update', 'customer.payment.create',
                'supplier.view', 'supplier.purchase.create', 'supplier.payment.create',
                'party.view', 'party.create', 'party.transaction.create',
                'finance.view', 'ledger.view',
                'income.create', 'income.update', 'expense.create', 'expense.update',
                'expense.category.manage',
                'reports.view', 'reports.export', 'reports.financial',
                'company.view',
            ],
        ],

        'farm_staff' => [
            'label' => 'Farm Staff',
            'description' => 'Field data entry: feed usage, mortality, growth samples and inspections.',
            'is_system' => true,
            'permissions' => [
                'dashboard.view',
                'pond.view',
                'feed.view', 'feed.usage',
                'fish.view', 'fish.stock', 'fish.mortality',
                'fcr.view', 'fcr.inspection.create', 'growth.view', 'growth.create',
            ],
        ],

        'sales_staff' => [
            'label' => 'Sales Staff',
            'description' => 'Sales and customer collections.',
            'is_system' => true,
            'permissions' => [
                'dashboard.view',
                'pond.view', 'fish.view',
                'sales.view', 'sales.create', 'sales.payment.create',
                'customer.view', 'customer.create', 'customer.payment.create',
                'reports.view',
                'company.view',
            ],
        ],

        'viewer' => [
            'label' => 'Viewer',
            'description' => 'Read-only access across the farm.',
            'is_system' => true,
            'permissions' => [
                'dashboard.view',
                'pond.view', 'feed.view', 'fish.view',
                'fcr.view', 'growth.view',
                'sales.view', 'customer.view', 'supplier.view', 'party.view',
                'finance.view', 'ledger.view',
                'reports.view',
                'company.view',
            ],
        ],
    ],
];