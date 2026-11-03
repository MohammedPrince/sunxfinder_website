/*
 * Image resizer for admin upload fields (campaigns, app slider).
 *
 * Resizes in the browser to the exact width the server stores, so the upload stays far
 * below the PHP limit and the server can keep the file as-is (no second compression).
 * Quality: step-down scaling with high smoothing, WebP at 0.92, lowered only when the
 * file would still be too big. Shows the original and final width × height and size.
 *
 * AdminImageResizer.attach({
 *     input,        // <input type="file">
 *     info,         // element that shows the status
 *     submit,       // submit button, disabled while working
 *     maxBytes,     // server upload limit in bytes
 *     targetWidth,  // width the server stores (e.g. 1200)
 *     ratio,        // expected width / height in the app (e.g. 2), for a crop hint
 *     onReady,      // function (file, objectUrl) after a file is ready
 * });
 */
(function () {
    'use strict';

    const MB = 1048576;
    const QUALITIES = [0.92, 0.88, 0.84, 0.8, 0.75];

    // Browsers without WebP encoding (old Safari) fall back to high-quality JPEG.
    const OUTPUT_TYPE = (() => {
        const probe = document.createElement('canvas');
        probe.width = probe.height = 1;
        return probe.toDataURL('image/webp').startsWith('data:image/webp') ? 'image/webp' : 'image/jpeg';
    })();

    const formatSize = (bytes) => bytes >= MB
        ? (bytes / MB).toFixed(2) + ' MB'
        : Math.max(1, Math.round(bytes / 1024)) + ' KB';

    const dims = (w, h) => w + ' × ' + h + ' px';

    const loadImage = (file) => new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const img = new Image();
        img.onload = () => resolve({ img, url });
        img.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error('unreadable'));
        };
        img.src = url;
    });

    const canvasOf = (w, h) => {
        const canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');
        ctx.imageSmoothingEnabled = true;
        ctx.imageSmoothingQuality = 'high';
        return { canvas, ctx };
    };

    // Halve the size step by step, then draw the final size: much sharper than one
    // big jump (which skips pixels and looks jagged).
    const scale = (img, width, height) => {
        let source = img;
        let w = img.naturalWidth;
        let h = img.naturalHeight;

        while (w / 2 >= width && h / 2 >= height) {
            w = Math.round(w / 2);
            h = Math.round(h / 2);
            const step = canvasOf(w, h);
            step.ctx.drawImage(source, 0, 0, w, h);
            source = step.canvas;
        }

        const final = canvasOf(width, height);
        if (OUTPUT_TYPE === 'image/jpeg') {
            // JPEG has no transparency: paint transparent areas white rather than black.
            final.ctx.fillStyle = '#fff';
            final.ctx.fillRect(0, 0, width, height);
        }
        final.ctx.drawImage(source, 0, 0, width, height);
        return final.canvas;
    };

    const encode = (canvas, type, quality) => new Promise((resolve) => canvas.toBlob(resolve, type, quality));

    // Highest quality that fits the budget.
    const bestEncode = async (canvas, budget) => {
        let blob = null;

        for (const quality of QUALITIES) {
            blob = await encode(canvas, OUTPUT_TYPE, quality);
            if (blob && blob.size <= budget) return { blob, quality };
        }
        return { blob, quality: QUALITIES[QUALITIES.length - 1] };
    };

    function attach(options) {
        const { input, info, submit, maxBytes, targetWidth, ratio, onReady } = options;
        const budget = Math.min(maxBytes * 0.95, 1.5 * MB);
        let readyUrl = null;

        const show = (html, state) => {
            info.hidden = false;
            info.className = 'image-resize-info' + (state ? ' is-' + state : '');
            info.innerHTML = html;
        };

        const ratioHint = (w, h) => {
            if (!ratio) return '';
            const actual = w / h;
            if (Math.abs(actual - ratio) / ratio <= 0.15) return '';
            return '<small class="image-resize-hint">This image is ' + actual.toFixed(2) + ':1. The app shows '
                + ratio + ':1, so the ' + (actual < ratio ? 'top and bottom' : 'sides') + ' may be cropped.</small>';
        };

        const ready = (file, html, state) => {
            if (readyUrl) URL.revokeObjectURL(readyUrl);
            readyUrl = URL.createObjectURL(file);
            show(html, state);
            if (onReady) onReady(file, readyUrl);
        };

        input.addEventListener('change', async () => {
            const file = input.files[0];
            if (!file) return;

            let loaded;
            try {
                loaded = await loadImage(file);
            } catch (e) {
                input.value = '';
                show('This file is not an image the browser can read. Choose a JPG, PNG or WebP.', 'error');
                return;
            }

            const { img, url } = loaded;
            const w = img.naturalWidth;
            const h = img.naturalHeight;
            const original = '<span class="image-resize-row"><b>Original</b>' + dims(w, h) + ' · ' + formatSize(file.size) + '</span>';

            // Already the right size and light: upload untouched (best quality).
            if (w <= targetWidth && file.size <= budget) {
                URL.revokeObjectURL(url);
                ready(file, original + '<span class="image-resize-row"><b>Upload</b>No resize needed</span>' + ratioHint(w, h), 'ok');
                return;
            }

            let width = Math.min(w, targetWidth);
            let height = Math.round(h * (width / w));

            if (submit) submit.disabled = true;
            show(original + '<span class="image-resize-row"><b>Resizing</b>' + dims(w, h) + ' → ' + dims(width, height) + '…</span>', 'busy');

            // Give the browser a frame to paint the status before the heavy work.
            await new Promise((resolve) => requestAnimationFrame(() => setTimeout(resolve, 0)));

            let result = null;
            try {
                // Very detailed images may still be too heavy at the lowest quality: shrink a little more.
                for (let attempt = 0; attempt < 4; attempt++) {
                    result = await bestEncode(scale(img, width, height), budget);
                    if (result.blob && result.blob.size <= budget) break;
                    width = Math.round(width * 0.85);
                    height = Math.round(height * 0.85);
                }
            } finally {
                URL.revokeObjectURL(url);
                if (submit) submit.disabled = false;
            }

            const blob = result && result.blob;
            if (!blob || blob.size > maxBytes || !window.DataTransfer) {
                if (file.size <= maxBytes) {
                    ready(file, original + '<span class="image-resize-row"><b>Upload</b>Original file (browser could not resize)</span>', 'ok');
                } else {
                    input.value = '';
                    show(original + '<span class="image-resize-row">Larger than the server limit of ' + formatSize(maxBytes)
                        + ' and could not be resized here. Please compress it and choose it again.</span>', 'error');
                }
                return;
            }

            const extension = blob.type === 'image/webp' ? 'webp' : 'jpg';
            const resized = new File([blob], file.name.replace(/\.[^.]+$/, '') + '.' + extension, { type: blob.type, lastModified: Date.now() });
            const transfer = new DataTransfer();
            transfer.items.add(resized);
            input.files = transfer.files;

            ready(resized, original
                + '<span class="image-resize-row"><b>Upload</b>' + dims(width, height) + ' · ' + formatSize(resized.size)
                + ' · ' + extension.toUpperCase() + ' ' + Math.round(result.quality * 100) + '% quality</span>'
                + ratioHint(width, height), 'ok');
        });
    }

    window.AdminImageResizer = { attach };
})();
