<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HolidayController extends Controller
{
    protected string $pageTitle;

    public function __construct()
    {
        $this->pageTitle = 'Holiday Management';

        view()->share([
            'pageTitle' => $this->pageTitle,
        ]);
    }

    /**
     * Display a listing of holidays.
     */
    public function index(Request $request): View
    {
        $perPage = $request->input(
            'per_page',
            config('constants.per_page_count')
        );

        $query = Holiday::query()
            ->with([
                'users',
                'shifts',
            ]);

        /*
         * Search.
         */
        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere(
                        'description',
                        'like',
                        '%' . $search . '%'
                    );
            });
        }

        /*
         * Active / inactive.
         */
        if ($request->filled('status')) {
            $query->when(
                $request->input('status') === 'active',
                fn ($q) => $q->where('is_active', true)
            );

            $query->when(
                $request->input('status') === 'inactive',
                fn ($q) => $q->where('is_active', false)
            );
        }

        /*
         * Public / private.
         */
        if ($request->filled('is_public')) {
            $query->where(
                'is_public',
                $request->boolean('is_public')
            );
        }

        /*
         * Applied to.
         */
        if ($request->filled('applied_to')) {
            $query->where(
                'applied_to',
                $request->input('applied_to')
            );
        }

        $holidays = $query
            ->orderBy('from_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        session([
            'holidays_return_url' => $request->fullUrl(),
        ]);

        return view('holidays.index', compact(
            'holidays',
            'perPage'
        ));
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        $users = User::query()
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();

        $shifts = Shift::query()
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();

        return view('holidays.create', compact(
            'users',
            'shifts'
        ));
    }

    /**
     * Store holiday.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateHoliday($request);

        DB::transaction(function () use ($validated, $request) {

            $holiday = Holiday::create([
                'name' => $validated['name'],
                'from_date' => $validated['from_date'],
                'to_date' => $validated['to_date'],
                'description' => $validated['description'] ?? null,
                'is_public' => $request->boolean('is_public'),
                'applied_to' => $validated['applied_to'],
                'is_active' => $request->boolean('is_active'),
            ]);

            /*
             * ALL USERS
             *
             * Store all active users in holiday_users.
             */
            if ($validated['applied_to'] === 'all_users') {

                $userIds = User::query()
                    ->where('is_active', 1)
                    ->pluck('id')
                    ->toArray();

                $holiday->users()->sync($userIds);
            }

            /*
             * SELECTED USERS
             */
            if ($validated['applied_to'] === 'user') {

                $holiday->users()->sync(
                    $validated['user_ids'] ?? []
                );
            }

            /*
             * SHIFT-WISE
             *
             * Only store shifts here.
             * Attendance will resolve users through
             * UserShiftAssignment.
             */
            if ($validated['applied_to'] === 'shift') {

                $holiday->shifts()->sync(
                    $validated['shift_ids'] ?? []
                );
            }
        });

        return redirect()
            ->route('holidays.index')
            ->with(
                'success',
                'Holiday created successfully.'
            );
    }

    /**
     * Show edit form.
     */
    public function edit(Holiday $holiday): View
    {
        $holiday->load([
            'users',
            'shifts',
        ]);

        $users = User::query()
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();

        $shifts = Shift::query()
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();

        $selectedUserIds = $holiday->users
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $selectedShiftIds = $holiday->shifts
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        return view('holidays.edit', compact(
            'holiday',
            'users',
            'shifts',
            'selectedUserIds',
            'selectedShiftIds'
        ));
    }

    /**
     * Update holiday.
     */
    public function update(
        Request $request,
        Holiday $holiday
    ): RedirectResponse {
        $validated = $this->validateHoliday(
            $request,
            $holiday
        );

        DB::transaction(function () use (
            $validated,
            $request,
            $holiday
        ) {

            $holiday->update([
                'name' => $validated['name'],
                'from_date' => $validated['from_date'],
                'to_date' => $validated['to_date'],
                'description' => $validated['description'] ?? null,
                'is_public' => $request->boolean('is_public'),
                'applied_to' => $validated['applied_to'],
                'is_active' => $request->boolean('is_active'),
            ]);

            /*
             * ALL USERS
             */
            if ($validated['applied_to'] === 'all_users') {

                $userIds = User::query()
                    ->where('is_active', 1)
                    ->pluck('id')
                    ->toArray();

                $holiday->users()->sync($userIds);
                $holiday->shifts()->detach();

                return;
            }

            /*
             * SELECTED USERS
             */
            if ($validated['applied_to'] === 'user') {

                $holiday->users()->sync(
                    $validated['user_ids'] ?? []
                );

                $holiday->shifts()->detach();

                return;
            }

            /*
             * SHIFT-WISE
             */
            if ($validated['applied_to'] === 'shift') {

                $holiday->shifts()->sync(
                    $validated['shift_ids'] ?? []
                );

                $holiday->users()->detach();

                return;
            }

            /*
             * Safety fallback.
             */
            $holiday->users()->detach();
            $holiday->shifts()->detach();
        });

        return redirect(
            session(
                'holidays_return_url',
                route('holidays.index')
            )
        )->with(
            'success',
            'Holiday updated successfully.'
        );
    }

    /**
     * Delete holiday.
     */
    public function destroy(Holiday $holiday): RedirectResponse
    {
        DB::transaction(function () use ($holiday) {

            $holiday->users()->detach();
            $holiday->shifts()->detach();

            $holiday->delete();
        });

        return redirect(
            session(
                'holidays_return_url',
                route('holidays.index')
            )
        )->with(
            'success',
            'Holiday deleted successfully.'
        );
    }

    /**
     * Toggle active/inactive.
     */
    public function toggleStatus(Holiday $holiday): RedirectResponse
    {
        $holiday->update([
            'is_active' => !$holiday->is_active,
        ]);

        return redirect()
            ->back()
            ->with(
                'success',
                $holiday->is_active
                    ? 'Holiday activated successfully.'
                    : 'Holiday deactivated successfully.'
            );
    }

    /**
     * Validate holiday.
     */
    private function validateHoliday(
        Request $request,
        ?Holiday $holiday = null
    ): array {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'from_date' => [
                'required',
                'date',
            ],

            'to_date' => [
                'required',
                'date',
                'after_or_equal:from_date',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'is_public' => [
                'nullable',
                'boolean',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'applied_to' => [
                'required',
                Rule::in([
                    'all_users',
                    'shift',
                    'user',
                ]),
            ],

            'shift_ids' => [
                'nullable',
                'required_if:applied_to,shift',
                'array',
                'min:1',
            ],

            'shift_ids.*' => [
                'integer',
                'exists:shifts,id',
            ],

            'user_ids' => [
                'nullable',
                'required_if:applied_to,user',
                'array',
                'min:1',
            ],

            'user_ids.*' => [
                'integer',
                'exists:users,id',
            ],
        ], [
            'to_date.after_or_equal' =>
                'The to date must be on or after the from date.',

            'shift_ids.required_if' =>
                'Please select at least one shift.',

            'shift_ids.min' =>
                'Please select at least one shift.',

            'user_ids.required_if' =>
                'Please select at least one user.',

            'user_ids.min' =>
                'Please select at least one user.',
        ]);

        /*
        * Check for overlapping holidays.
        *
        * Any date overlap is blocked.
        */
        $overlappingHoliday = Holiday::query()
            ->where('id', '!=', $holiday?->id)
            ->where(function ($query) use ($validated) {
                $query
                    ->whereDate('from_date', '<=', $validated['to_date'])
                    ->whereDate('to_date', '>=', $validated['from_date']);
            })
            ->first();

        if ($overlappingHoliday) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'from_date' => sprintf(
                    'This holiday overlaps with the existing holiday "%s" (%s - %s). Please choose a different date range.',
                    $overlappingHoliday->name,
                    $overlappingHoliday->from_date->format(config('constants.date_format')),
                    $overlappingHoliday->to_date->format(config('constants.date_format'))
                ),
            ]);
        }

        return $validated;
    }
}
