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

    function initProductGallery() {
        var galleries = document.querySelectorAll('[data-product-gallery]');
        if (!galleries.length) return;

        galleries.forEach(function (gallery) {
            var mainFrame = gallery.querySelector('[data-gallery-main]');
            var mainImage = gallery.querySelector('[data-gallery-main-image]');
            var mainVideoWrap = gallery.querySelector('[data-gallery-main-video-wrap]');
            var mainVideo = gallery.querySelector('[data-gallery-main-video]');
            var mainEmbed = gallery.querySelector('[data-gallery-main-embed]');
            var thumbsWrap = gallery.querySelector('[data-gallery-thumbs]');
            var thumbs = gallery.querySelectorAll('[data-gallery-thumb]');

            if (!mainFrame || !mainImage) return;

            function showImage() {
                if (mainVideo) {
                    mainVideo.pause();
                    mainVideo.onerror = null;
                    mainVideo.removeAttribute('src');
                    mainVideo.load();
                    mainVideo.classList.add('d-none');
                }

                if (mainEmbed) {
                    mainEmbed.setAttribute('src', 'about:blank');
                    mainEmbed.classList.add('d-none');
                }

                if (mainVideoWrap) {
                    mainVideoWrap.classList.add('d-none');
                }

                mainImage.classList.remove('d-none');
                mainFrame.classList.remove('has-video');
            }

            function showVideo(kind, src, sources) {
                if (!mainVideoWrap || !src) {
                    return;
                }

                mainVideoWrap.classList.remove('d-none');
                mainImage.classList.add('d-none');
                mainFrame.classList.add('has-video');

                if (kind === 'embed') {
                    if (mainVideo) {
                        mainVideo.pause();
                        mainVideo.onerror = null;
                        mainVideo.removeAttribute('src');
                        mainVideo.load();
                        mainVideo.classList.add('d-none');
                    }
                    if (mainEmbed) {
                        mainEmbed.setAttribute('src', src);
                        mainEmbed.classList.remove('d-none');
                    }
                    return;
                }

                if (mainEmbed) {
                    mainEmbed.setAttribute('src', 'about:blank');
                    mainEmbed.classList.add('d-none');
                }
                if (mainVideo) {
                    var sourceList = [];
                    if (Array.isArray(sources)) {
                        sourceList = sources.filter(function (item) {
                            return typeof item === 'string' && item.trim() !== '';
                        });
                    }

                    if (src && sourceList.indexOf(src) === -1) {
                        sourceList.unshift(src);
                    }

                    if (!sourceList.length) {
                        sourceList = [src];
                    }

                    var sourceIndex = 0;
                    var setSource = function (index) {
                        mainVideo.setAttribute('src', sourceList[index]);
                        mainVideo.load();
                        var playPromise = mainVideo.play();
                        if (playPromise && typeof playPromise.catch === 'function') {
                            playPromise.catch(function () {});
                        }
                    };

                    mainVideo.onerror = function () {
                        sourceIndex += 1;
                        if (sourceIndex < sourceList.length) {
                            setSource(sourceIndex);
                        }
                    };

                    mainVideo.classList.remove('d-none');
                    setSource(sourceIndex);
                }
            }

            mainFrame.addEventListener('mousemove', function (event) {
                if (mainFrame.classList.contains('has-video') || mainImage.classList.contains('d-none')) {
                    return;
                }

                var rect = mainFrame.getBoundingClientRect();
                if (!rect.width || !rect.height) return;

                var x = ((event.clientX - rect.left) / rect.width) * 100;
                var y = ((event.clientY - rect.top) / rect.height) * 100;
                mainImage.style.transformOrigin = x.toFixed(2) + '% ' + y.toFixed(2) + '%';
            });

            mainFrame.addEventListener('mouseleave', function () {
                mainImage.style.transformOrigin = '50% 50%';
            });

            if (!thumbs.length || !thumbsWrap) return;

            function setActiveThumb(activeThumb) {
                thumbs.forEach(function (thumb) {
                    var isActive = thumb === activeThumb;
                    thumb.classList.toggle('is-active', isActive);
                    if (isActive) {
                        thumb.setAttribute('aria-current', 'true');
                    } else {
                        thumb.removeAttribute('aria-current');
                    }
                });

                if (activeThumb && typeof activeThumb.scrollIntoView === 'function') {
                    activeThumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                }
            }

            function swapMainImage(nextSrc, nextAlt) {
                if (!nextSrc || nextSrc === mainImage.getAttribute('src')) {
                    showImage();
                    return;
                }

                mainFrame.classList.add('is-transitioning');
                var preload = new Image();
                preload.onload = function () {
                    mainImage.setAttribute('src', nextSrc);
                    mainImage.setAttribute('alt', nextAlt || mainImage.getAttribute('alt') || 'Product image');
                    window.setTimeout(function () {
                        mainFrame.classList.remove('is-transitioning');
                    }, 140);
                };

                preload.onerror = function () {
                    mainFrame.classList.remove('is-transitioning');
                };

                preload.src = nextSrc;
            }

            thumbs.forEach(function (thumb) {
                thumb.addEventListener('click', function () {
                    var mediaType = thumb.getAttribute('data-media-type') || 'image';
                    if (mediaType === 'video') {
                        var videoKind = thumb.getAttribute('data-video-kind') || 'file';
                        var videoSrc = thumb.getAttribute('data-video-src') || '';
                        var videoSourcesRaw = thumb.getAttribute('data-video-sources') || '[]';
                        var videoSources = [];
                        try {
                            videoSources = JSON.parse(videoSourcesRaw);
                            if (!Array.isArray(videoSources)) {
                                videoSources = [];
                            }
                        } catch (e) {
                            videoSources = [];
                        }

                        showVideo(videoKind, videoSrc, videoSources);
                    } else {
                        var nextSrc = thumb.getAttribute('data-image-src') || '';
                        var nextAlt = thumb.getAttribute('data-image-alt') || '';
                        showImage();
                        swapMainImage(nextSrc, nextAlt);
                    }

                    setActiveThumb(thumb);
                });
            });

            thumbsWrap.addEventListener('wheel', function (event) {
                if (Math.abs(event.deltaY) <= Math.abs(event.deltaX)) {
                    return;
                }

                event.preventDefault();
                thumbsWrap.scrollLeft += event.deltaY;
            }, { passive: false });
        });
    }

    function initRatingStars() {
        var groups = document.querySelectorAll('.product-rate-options');
        if (!groups.length) return;

        groups.forEach(function (group) {
            var options = group.querySelectorAll('.product-rate-option');
            var inputs = group.querySelectorAll('input[name="rating"]');

            function paintSelectedStars() {
                var selectedValue = 0;
                inputs.forEach(function (input) {
                    if (input.checked) {
                        selectedValue = parseInt(input.value || '0', 10) || 0;
                    }
                });

                options.forEach(function (option) {
                    var input = option.querySelector('input[name="rating"]');
                    var value = parseInt((input && input.value) ? input.value : '0', 10) || 0;
                    option.classList.toggle('is-selected', selectedValue > 0 && value <= selectedValue);
                });
            }

            inputs.forEach(function (input) {
                input.addEventListener('change', paintSelectedStars);
            });

            paintSelectedStars();
        });
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
        initProductGallery();
        initRatingStars();
    });
})();
