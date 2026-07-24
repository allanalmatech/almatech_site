(function () {
    function buildWhatsappMessage(payload) {
        var lines = [];
        lines.push('Hello, I would like to order this product:');
        lines.push('');
        lines.push('Product: ' + payload.name);
        lines.push('Price: ' + payload.priceLabel);
        if (payload.discountLabel) {
            lines.push('Discount Price: ' + payload.discountLabel);
        }
        lines.push('Quantity: ' + payload.qty);
        if (payload.note) {
            lines.push('Note/Variant: ' + payload.note);
        }
        lines.push('Product Link: ' + payload.link);
        lines.push('');
        lines.push('Please confirm availability. Thank you.');

        return lines.join('\n');
    }

    function waLink(number, message) {
        return 'https://wa.me/' + encodeURIComponent(number) + '?text=' + encodeURIComponent(message);
    }

    function handleOrderClick(event) {
        var btn = event.currentTarget;
        var qtyInputSelector = btn.getAttribute('data-qty-input');
        var noteInputSelector = btn.getAttribute('data-note-input');

        var qty = 1;
        if (qtyInputSelector) {
            var qtyInput = document.querySelector(qtyInputSelector);
            if (qtyInput) {
                qty = parseInt(qtyInput.value, 10);
            }
        }
        if (!qty || qty < 1) qty = 1;

        var note = '';
        if (noteInputSelector) {
            var noteInput = document.querySelector(noteInputSelector);
            if (noteInput) {
                note = (noteInput.value || '').trim();
            }
        }

        var payload = {
            name: btn.getAttribute('data-product-name') || 'Product',
            priceLabel: btn.getAttribute('data-price-label') || '-',
            discountLabel: btn.getAttribute('data-discount-label') || '',
            qty: qty,
            note: note,
            link: btn.getAttribute('data-product-link') || window.location.href
        };

        var number = btn.getAttribute('data-wa-number') || '';
        var url = waLink(number, buildWhatsappMessage(payload));
        window.open(url, '_blank', 'noopener');
    }

    document.addEventListener('DOMContentLoaded', function () {
        var orderButtons = document.querySelectorAll('[data-wa-order]');
        orderButtons.forEach(function (btn) {
            btn.addEventListener('click', handleOrderClick);
        });
    });
})();
