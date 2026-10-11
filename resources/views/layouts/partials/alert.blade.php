@if (session('success') || session('error') || session('info'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.LP) return;
    @if (session('success')) LP.toast(@json(session('success')), 'ok'); @endif
    @if (session('error')) LP.toast(@json(session('error')), 'error'); @endif
    @if (session('info')) LP.toast(@json(session('info')), 'info'); @endif
});
</script>
@endif
