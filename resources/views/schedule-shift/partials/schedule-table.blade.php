<div class="overflow-x-auto">
    <table class="min-w-full border border-gray-200 text-sm">
        <thead class="bg-gray-100">
            <tr>
                <th class="border border-gray-300 px-4 py-2 text-left">
                    <input type="checkbox" id="select-all-users" class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">
                </th>
                <th class="border border-gray-300 px-4 py-2 text-left">Users</th>

                @foreach ($weekDates as $date)
                    <th class="border border-gray-300 px-4 py-2 text-center">
                        {{ $date->format('D') }} <br>
                        {{ $date->format('d M') }}
                    </th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            @forelse ($users as $user)
                @include('schedule-shift.partials.schedule-row')
            @empty
                <x-table-no-data :col-span="9" message="No users found." />
            @endforelse
        </tbody>

    </table>
</div>
