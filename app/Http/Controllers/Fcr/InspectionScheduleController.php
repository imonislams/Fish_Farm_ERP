<?php

namespace App\Http\Controllers\Fcr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fcr\SaveInspectionScheduleRequest;
use App\Models\InspectionSchedule;
use App\Models\Pond;
use App\Services\Fcr\InspectionScheduleService;
use App\Support\AsyncResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Inspection schedules — the recurring inspection plan per pond.
 *
 * Thin controller: authorize, validate via FormRequest, delegate to
 * InspectionScheduleService (the due date is always derived there), redirect.
 *
 * AUTHORIZATION: `permission:fcr.schedule.manage` plus the policy check.
 */
class InspectionScheduleController extends Controller
{
    public function __construct(
        private readonly InspectionScheduleService $scheduleService,
    ) {}

    /** Paginated schedule list with real due status (Inertia/React). */
    public function index(Request $request): \Inertia\Response
    {
        $status = (string) $request->query('status', '');
        $status = in_array($status, ['overdue', 'due_soon', 'scheduled', 'inactive'], true) ? $status : '';

        $query = InspectionSchedule::query()
            ->with('pond:id,name,pond_number');

        $all = $query->get();

        $schedules = $all
            ->filter(fn(InspectionSchedule $s) => $status === '' || $s->statusKey() === $status)
            ->sortBy(fn(InspectionSchedule $s) => $s->next_due_on?->timestamp ?? PHP_INT_MAX)
            ->values()
            ->map(function (InspectionSchedule $s): array {
                $days = $s->daysUntilDue();

                return [
                    'id' => $s->id,
                    'pond_id' => $s->pond_id,
                    'pond' => $s->pond?->name,
                    'pond_number' => $s->pond?->pond_number,
                    'frequency' => $s->frequencyLabel(),
                    'last_completed_on' => $s->last_completed_on?->toDateString(),
                    'next_due_on' => $s->next_due_on?->toDateString(),
                    'days_until_due' => $days,
                    'due_text' => $days === null
                        ? null
                        : ($days < 0
                            ? abs($days) . ' day(s) overdue'
                            : ($days === 0 ? 'Due today' : "in {$days} day(s)")),
                    'status' => $s->statusLabel(),
                    'status_tone' => $s->statusTone(),
                    'urls' => [
                        'edit' => route('fcr.schedules.edit', $s, absolute: false),
                        'destroy' => route('fcr.schedules.destroy', $s, absolute: false),
                    ],
                ];
            })
            ->all();

        return \Inertia\Inertia::render('Fcr/Schedules/Index', [
            'title' => 'Inspection Schedule',
            'schedules' => $schedules,
            'statusCounts' => $this->scheduleService->statusCounts(),
            'status' => $status,
            'pondsWithoutSchedule' => $this->scheduleService->pondsWithoutSchedule()
                ->pluck('name')->all(),
            'options' => ['pondOptions' => $this->pondOptions()->all()],
            'frequencyOptions' => collect(config('fcr.frequencies', []))
                ->map(fn(array $meta): string => $meta['label'])
                ->all(),
            'dueSoonDays' => config('fcr.due_soon_days', 2),
        ]);
    }

    /** Create or replace a pond's schedule. */
    public function store(SaveInspectionScheduleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $pond = Pond::findOrFail($data['pond_id']);

        $schedule = $this->scheduleService->setForPond($pond, $data);

        return redirect()
            ->route('fcr.schedules.index')
            ->with('success', "Inspection schedule for \"{$pond->name}\" set to {$schedule->frequencyLabel()}. Next due {$schedule->next_due_on->format('d M Y')}.");
    }

    /** Show the edit form (Inertia/React). */
    public function edit(InspectionSchedule $schedule): \Inertia\Response
    {
        Gate::authorize('update', $schedule);

        return \Inertia\Inertia::render('Fcr/Schedules/Edit', [
            'title' => 'Edit Schedule — ' . ($schedule->pond?->name ?? ''),
            'schedule' => [
                'id' => $schedule->id,
                'pond_id' => $schedule->pond_id,
                'pond' => $schedule->pond?->name,
                'pond_label' => $schedule->pond
                    ? $schedule->pond->pond_number . ' — ' . $schedule->pond->name
                    : '',
                'frequency' => $schedule->frequency,
                'frequency_label' => $schedule->frequencyLabel(),
                'last_completed_on' => $schedule->last_completed_on?->toDateString(),
                'next_due_on' => $schedule->next_due_on?->toDateString(),
                'is_active' => (bool) $schedule->is_active,
                'status_label' => $schedule->statusLabel(),
                'status_tone' => $schedule->statusTone(),
            ],
            'frequencyOptions' => collect(config('fcr.frequencies', []))
                ->map(fn(array $meta): string => $meta['label'])
                ->all(),
        ]);
    }

    /** Persist changes. */
    public function update(SaveInspectionScheduleRequest $request, InspectionSchedule $schedule): RedirectResponse
    {
        Gate::authorize('update', $schedule);

        $this->scheduleService->update($schedule, $request->validated());

        return redirect()
            ->route('fcr.schedules.index')
            ->with('success', 'Inspection schedule updated.');
    }

    /** Delete a schedule. */
    public function destroy(Request $request, InspectionSchedule $schedule): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        Gate::authorize('delete', $schedule);

        $this->scheduleService->delete($schedule);

        return AsyncResponse::ok($request, 'Inspection schedule removed.', 'fcr.schedules.index');
    }

    /**
     * Ponds selectable when creating a schedule: id => "P-01 — Pond name".
     *
     * @return Collection<int, string>
     */
    private function pondOptions(): Collection
    {
        return Pond::query()
            ->orderBy('pond_number')
            ->get(['id', 'pond_number', 'name'])
            ->mapWithKeys(fn(Pond $p) => [$p->id => "{$p->pond_number} — {$p->name}"]);
    }
}
