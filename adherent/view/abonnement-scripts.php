<script>
document.addEventListener('DOMContentLoaded', () => {

    const yearFilter = document.getElementById('invoice-year');
    yearFilter?.addEventListener('change', () => {
        document.querySelectorAll('[data-payment-year]').forEach(row => {
            row.hidden = yearFilter.value !== 'all' && row.dataset.paymentYear !== yearFilter.value;
        });
    });

    // Animation de la barre de progression au chargement
    const fill = document.querySelector('.progress-fill');
    if (fill) {
        const w = fill.style.width;
        fill.style.width = '0';
        requestAnimationFrame(() => {
            setTimeout(() => (fill.style.width = w), 100);
        });
    }

});

</script>
