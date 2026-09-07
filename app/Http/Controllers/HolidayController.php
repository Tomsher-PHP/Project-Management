<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\User;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HolidayController extends Controller
{
    /**
     * Display a listing of holidays.
     */
    public function index(Request $request): View
    {
        $query = Holiday::with([
            'users',
            'shifts',
        ]);

        /*
         * Search by holiday name.
         */
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        /*
         * Filter by active/inactive.
         */
        if ($request->filled('status')) {

            if ($request->status === 'active') {
                $query->where('is_active', true);
            }

            if ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        /*
         * Filter by public/private.
         */
        if ($request->filled('is_public')) {
            $query->where(
                'is_public',
                $request->is_public == 1
            );
        }

        $holidays = $query
            ->orderBy('from_date', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('holidays.index', compact('holidays'));
    }


    /**
     * Show the form for creating a new holiday.
     */
    public function create(): View
    {
        $users = User::where('is_active', 1)
            ->orderBy('name')
            ->get();

        $shifts = Shift::where('is_active', 1)
            ->orderBy('name')
            ->get();

        return view('holidays.create', compact(
            'users',
            'shifts'
        ));
    }


    /**
     * Store a newly created holiday.
     */
    public function store(Request $request): RedirectResponse
    {
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

            /*
             * Required only for shift-wise holidays.
             */
            'shift_ids' => [
                'nullable',
                'required_if:applied_to,shift',
                'array',
            ],

            'shift_ids.*' => [
                'integer',
                'exists:shifts,id',
            ],

            /*
             * Required only for user-wise holidays.
             */
            'user_ids' => [
                'nullable',
                'required_if:applied_to,user',
                'array',
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

            'user_ids.required_if' =>
                'Please select at least one user.',
        ]);


        /*
         * Create holiday.
         */
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
         * Attach users if holiday is user-wise.
         */
        if (
            $validated['applied_to'] === 'user'
            && !empty($validated['user_ids'])
        ) {
            $holiday->users()->sync(
                $validated['user_ids']
            );
        }


        /*
         * Attach shifts if holiday is shift-wise.
         */
        if (
            $validated['applied_to'] === 'shift'
            && !empty($validated['shift_ids'])
        ) {
            $holiday->shifts()->sync(
                $validated['shift_ids']
            );
        }


        return redirect()
            ->route('holidays.index')
            ->with('success', 'Holiday created successfully.');
    }


    /**
     * Show the form for editing a holiday.
     */
    public function edit(string $id): View
    {
        $holiday = Holiday::with([
            'users',
            'shifts',
        ])->findOrFail($id);

        $users = User::where('is_active', 1)
            ->orderBy('name')
            ->get();

        $shifts = Shift::where('is_active', 1)
            ->orderBy('name')
            ->get();

        /*
         * Get selected user IDs.
         */
        $selectedUserIds = $holiday->users
            ->pluck('id')
            ->toArray();

        /*
         * Get selected shift IDs.
         */
        $selectedShiftIds = $holiday->shifts
            ->pluck('id')
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
     * Update the specified holiday.
     */
    public function update(
        Request $request,
        string $id
    ): RedirectResponse {
        $holiday = Holiday::findOrFail($id);

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
            ],

            'shift_ids.*' => [
                'integer',
                'exists:shifts,id',
            ],

            'user_ids' => [
                'nullable',
                'required_if:applied_to,user',
                'array',
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

            'user_ids.required_if' =>
                'Please select at least one user.',
        ]);


        /*
         * Update main holiday record.
         */
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
         * Always sync users.
         *
         * If the holiday is not user-wise,
         * this removes any old user assignments.
         */
        if ($validated['applied_to'] === 'user') {

            $holiday->users()->sync(
                $validated['user_ids'] ?? []
            );

        } else {

            $holiday->users()->detach();
        }


        /*
         * Always sync shifts.
         *
         * If the holiday is not shift-wise,
         * this removes any old shift assignments.
         */
        if ($validated['applied_to'] === 'shift') {

            $holiday->shifts()->sync(
                $validated['shift_ids'] ?? []
            );

        } else {

            $holiday->shifts()->detach();
        }


        return redirect()
            ->route('holidays.index')
            ->with('success', 'Holiday updated successfully.');
    }


    /**
     * Remove the specified holiday.
     */
    public function destroy(string $id): RedirectResponse
    {
        $holiday = Holiday::findOrFail($id);

        /*
         * Pivot records will be deleted automatically
         * because of cascadeOnDelete().
         */
        $holiday->delete();

        return redirect()
            ->route('holidays.index')
            ->with('success', 'Holiday deleted successfully.');
    }


    /**
     * Toggle holiday active/inactive status.
     */
    public function toggleStatus(string $id): RedirectResponse
    {
        $holiday = Holiday::findOrFail($id);

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
}
