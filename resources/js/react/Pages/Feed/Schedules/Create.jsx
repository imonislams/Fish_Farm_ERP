import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import ScheduleForm from "./ScheduleForm";

/** Create a feeding schedule (a plan). */
function SchedulesCreate({ options = {}, defaultDate = "" }) {
    const { props } = usePage();
    const routes = props.routes || {};

    return (
        <>
            <Head title="New Feeding Schedule" />
            <PageHeader
                title="New Feeding Schedule"
                subtitle="Plan a meal for a pond. Scheduling does not consume feed stock."
                breadcrumb={[
                    { label: "Food Management" },
                    { label: "Feeding", href: routes["feed.feedings.index"] },
                    {
                        label: "Schedules",
                        href: routes["feed.schedules.index"],
                    },
                    { label: "New" },
                ]}
            />
            <div className="mt-5 max-w-2xl">
                <ScheduleForm
                    options={options}
                    mode="create"
                    defaultDate={defaultDate}
                />
            </div>
        </>
    );
}

export default withLayout(SchedulesCreate, "New Feeding Schedule");
