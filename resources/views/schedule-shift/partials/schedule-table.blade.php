<div class="overflow-x-auto">
    <table class="min-w-full border border-gray-200 text-sm">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-4 py-2 text-left">Users</th>

                @foreach ($weekDates as $date)
                    <th class="px-4 py-2 text-center">
                        {{ $date->format('D') }} <br>
                        {{ $date->format('d M') }}
                    </th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            @foreach ($users as $user)
                @include('schedule-shift.partials.schedule-row')
            @endforeach
        </tbody>

    </table>
</div>
