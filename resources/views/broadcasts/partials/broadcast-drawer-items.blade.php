@foreach ($broadcasts as $broadcast)
    @include('broadcasts.partials.broadcast-drawer-item', ['broadcast' => $broadcast, 'today' => $today])
@endforeach

