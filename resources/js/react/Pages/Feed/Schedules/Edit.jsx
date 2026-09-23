import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import { Card, Badge } from "../../../Components/Card";
import ScheduleForm from "./ScheduleForm";

/** Edit a feeding schedule (still a plan). */
function SchedulesEdit({ schedule = {}, options = {} }) {
    const { props } = usePage();
    const routes = props.routes || {};

    return (
        <>
            <Head title="Edit — Feeding Schedule" />
            <PageHeader
                title="Edit — Feeding Schedule"
                subtitle="Change the plan. Recorded feedings are not affected."
                breadcrumb={[
                    { label: "Food Management" },
                    { label: "Feeding", href: routes["feed.feedings.index"] },
                    {
                        label: "Schedules",
                        href: routes["feed.schedules.index"],
                    },
                    { label: "Edit" },
                ]}
            />

            <div className="mt-5 max-w-2xl">
                <Card title="Recorded feedings">
                    <p className="text-sm text-text-soft">
                        This schedule has{" "}
                        <Badge tone="info">
                            {schedule.recorded_count ?? 0}
                        </Badge>{" "}
                        recorded feeding(s). Those records are kept even if the
                        plan is changed or deleted.
                    </p>
                </Card>

                <div className="mt-4">
                    <ScheduleForm
                        schedule={schedule}
                        options={options}
                        mode="edit"
                    />
                </div>
            </div>
        </>
    );
}

export default withLayout(SchedulesEdit, "Edit Feeding Schedule");
