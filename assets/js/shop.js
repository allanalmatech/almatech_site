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

    function setShareFeedback(button, text) {
        var icon = button.querySelector('i');
        var originalTitle = button.getAttribute('title') || 'Share product';
        button.classList.add('is-shared');
        button.setAttribute('title', text);
        if (icon) {
            icon.className = 'bi bi-check2';
        }

        window.setTimeout(function () {
            button.classList.remove('is-shared');
            button.setAttribute('title', originalTitle);
            if (icon) {
                icon.className = 'bi bi-share-fill';
            }
        }, 1800);
    }

    function fallbackShare(url, button) {
        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            navigator.clipboard.writeText(url).then(function () {
                setShareFeedback(button, 'Product link copied');
            }).catch(function () {
                window.prompt('Copy this product link to share:', url);
            });
            return;
        }

        window.prompt('Copy this product link to share:', url);
    }

    function handleShareClick(event) {
        event.preventDefault();
        var button = event.currentTarget;
        var url = button.getAttribute('data-share-url') || window.location.href;
        var title = button.getAttribute('data-share-title') || 'Product';
        var imageUrl = button.getAttribute('data-share-image') || '';

        // Prepare the message template
        var lines = [];
        lines.push('Hey 👋');
        lines.push('');
        lines.push('I found this product at Alma Tech Online Shop and thought you might like it.');
        lines.push('');
        lines.push(title);
        lines.push('');
        lines.push('Check it here:');
        lines.push(url);
        var message = lines.join('\n');

        // Populate Modal
        var previewTextArea = document.getElementById('sharePreviewText');
        var previewImage = document.getElementById('sharePreviewImage');
        var btnCopy = document.getElementById('btnShareCopy');
        var btnWa = document.getElementById('btnShareWhatsapp');
        var btnNative = document.getElementById('btnShareNative');

        if (previewTextArea) {
            previewTextArea.value = message;
        }

        if (previewImage) {
            if (imageUrl) {
                previewImage.src = imageUrl;
                previewImage.style.display = 'block';
            } else {
                previewImage.style.display = 'none';
            }
        }

        if (btnWa) {
            btnWa.href = waLink('', message);
        }

        // Setup Copy listener
        if (btnCopy) {
            // Remove old listeners by replacing the node
            var newBtnCopy = btnCopy.cloneNode(true);
            btnCopy.parentNode.replaceChild(newBtnCopy, btnCopy);

            newBtnCopy.addEventListener('click', function () {
                if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                    navigator.clipboard.writeText(message).then(function () {
                        var originalHtml = newBtnCopy.innerHTML;
                        newBtnCopy.innerHTML = '<i class="bi bi-check2"></i>';
                        newBtnCopy.classList.remove('btn-outline-secondary');
                        newBtnCopy.classList.add('btn-success');
                        setTimeout(function () {
                            newBtnCopy.innerHTML = originalHtml;
                            newBtnCopy.classList.remove('btn-success');
                            newBtnCopy.classList.add('btn-outline-secondary');
                        }, 2000);
                    }).catch(function () {
                        window.prompt('Copy this message:', message);
                    });
                } else {
                    window.prompt('Copy this message:', message);
                }
            });
        }

        // Setup Native Share fallback if supported
        if (btnNative) {
            if (typeof navigator.share === 'function') {
                btnNative.classList.remove('d-none');

                var newBtnNative = btnNative.cloneNode(true);
                btnNative.parentNode.replaceChild(newBtnNative, btnNative);

                newBtnNative.addEventListener('click', function () {
                    navigator.share({
                        title: title,
                        text: text,
                        url: url
                    }).catch(function (e) { });
                });
            } else {
                btnNative.classList.add('d-none');
            }
        }

        // Show Modal
        var modalEl = document.getElementById('productShareModal');
        if (modalEl && window.bootstrap) {
            var modal = new bootstrap.Modal(modalEl);
            modal.show();
        } else {
            // Fallback if modal isn't ready
            fallbackShare(url, button);
        }
    }

    function initHeroScrollBlur() {
        var heroes = document.querySelectorAll('.hero-scroll-blur');
        if (!heroes.length) return;

        var maxBlur = 6;
        var maxScroll = 320;

        function updateHeroBlur() {
            var y = window.scrollY || window.pageYOffset || 0;
            var ratio = Math.min(y / maxScroll, 1);
            var blurValue = (ratio * maxBlur).toFixed(2) + 'px';
            heroes.forEach(function (hero) {
                hero.style.setProperty('--hero-scroll-blur', blurValue);
            });
        }

        updateHeroBlur();
        window.addEventListener('scroll', updateHeroBlur, { passive: true });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var orderButtons = document.querySelectorAll('[data-wa-order]');
        orderButtons.forEach(function (btn) {
            btn.addEventListener('click', handleOrderClick);
        });

        var shareButtons = document.querySelectorAll('[data-product-share]');
        shareButtons.forEach(function (button) {
            button.addEventListener('click', handleShareClick);
        });

        initHeroScrollBlur();
    });
})();
