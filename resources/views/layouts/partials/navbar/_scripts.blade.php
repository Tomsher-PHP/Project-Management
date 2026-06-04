<script>
    function copyProfileEmail(event, email) {
        event.preventDefault();
        event.stopPropagation();

        const fallbackCopy = (value) => {
            const input = document.createElement('textarea');
            input.value = value;
            input.setAttribute('readonly', '');
            input.style.position = 'fixed';
            input.style.opacity = '0';
            input.style.pointerEvents = 'none';
            document.body.appendChild(input);
            input.focus();
            input.select();

            try {
                document.execCommand('copy');
            } finally {
                document.body.removeChild(input);
            }
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(email).catch(() => fallbackCopy(email));
            return false;
        }

        fallbackCopy(email);
        return false;
    }
</script>
