<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('open-url-in-new-tab', (event) => {
            window.open(event.url, '_blank');
        });
    });
</script>
