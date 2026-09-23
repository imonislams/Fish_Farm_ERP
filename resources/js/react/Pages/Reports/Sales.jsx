import React from 'react';
import { withLayout, date } from '../../Components/Page';
import { ReportShell } from './ReportShell';
import { Badge } from '../../Components/Card';
import { money } from '../../Components/Page';

/** Sales report — paginated sales with totals, filterable by customer. */
function SalesReport({ rows, totals, customerOptions, filters }) {
    const summary = [
        { label: 'Sales', value: totals.count },
        { label: 'Subtotal', value: money(totals.subtotal) },
        { label: 'Discount', value: money(totals.discount), tone: 'warning' },
        { label: 'Total', value: money(totals.total), tone: 'success' },
        { label: 'Due', value: money(totals.due), tone: 'danger' },
    ];

    const columns = [
        { key: 'date', label: 'Date', render: (r) => date(r.date) },
        { key: 'invoice_no', label: 'Invoice', render: (r) => <code className="rounded bg-surface-muted px-1.5 py-0.5 text-xs">{r.invoice_no}</code> },
        { key: 'customer', label: 'Customer' },
        { key: 'subtotal', label: 'Subtotal', align: 'right', render: (r) => money(r.subtotal) },
        { key: 'total', label: 'Total', align: 'right', render: (r) => <span className="font-medium">{money(r.total)}</span> },
        { key: 'paid', label: 'Paid', align: 'right', render: (r) => <span className="text-success">{money(r.paid)}</span> },
        { key: 'due', label: 'Due', align: 'right', render: (r) => <span className={r.due > 0 ? 'text-danger' : 'text-muted'}>{money(r.due)}</span> },
        { key: 'status', label: 'Status', align: 'center', render: (r) => <Badge tone={r.status_tone || 'default'}>{r.status}</Badge> },
    ];

    return (
        <ReportShell
            title="Sales Report"
            subtitle="Fish sales to customers over a period. Every figure is a real total."
            breadcrumb={[{ label: 'Reports', href: '/fish-farm/reports' }, { label: 'Sales Report' }]}
            filterAction="reports.sales"
            extraFilters={[
                { name: 'customer', label: 'Customer', placeholder: 'All customers', options: customerOptions },
            ]}
            summary={summary}
            columns={columns}
            rows={{ ...rows, title: 'Sales' }}
            exportReport="reports.export"
            exportParams={{ report: 'sales' }}
            emptyTitle="No sales in this period"
            emptyMessage="Widen the date range, or record a sale."
        />
    );
}

export default withLayout(SalesReport, 'Sales Report');