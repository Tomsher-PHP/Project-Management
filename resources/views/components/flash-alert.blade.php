@if (session('success') || session('error') || session('warning') || session('info'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            @if (session('success'))
                Alert.success(@json(session('success')));
            @endif

            @if (session('error'))
                Alert.error(@json(session('error')));
            @endif

            @if (session('warning'))
                Alert.info(@json(session('warning')), 'Warning');
            @endif

            @if (session('info'))
                Alert.info(@json(session('info')));
            @endif

        });
    </script>
@endif
