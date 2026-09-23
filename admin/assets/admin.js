document.addEventListener('DOMContentLoaded', function () {
    const recoveryDownload = document.querySelector('[data-download-recovery]');
    const recoveryCodes = document.getElementById('recoveryCodes');
    const displayName = document.getElementById('display_name');
    if (recoveryDownload && recoveryCodes) {
        recoveryDownload.addEventListener('click', function () {
            const content = 'ELIMUTAIFA | '+ displayName.textContent.trim() + '\n'
                + '2FA- Ten(10) Backup Codes \n\n'
                +'-> Keep this file and use codes private.\n' 
                +'-> Each code can be used once.\n' 
                +'-> New recovery codes invalidate old ones.\n\n'
                
                +'___________________________\n\n'
                + recoveryCodes.textContent.trim() 
                + '\n___________________________'
            ;

            const url = URL.createObjectURL(new Blob([content], { type: 'text/plain;charset=utf-8' }));
            const link = document.createElement('a');
            link.href = url;
            link.download = 'elimutaifa-backup-codes.txt';
            document.body.appendChild(link);
            link.click();
            link.remove();
            setTimeout(function () { URL.revokeObjectURL(url); }, 10000);
            const status = document.querySelector('[data-recovery-download-status]');
            if (status) status.textContent = ' Download requested. Make sure the file is saved somewhere safe.';
        });
    }
    document.querySelectorAll('[data-mfa-countdown]').forEach(function (box) {
        const remaining = Number(box.dataset.mfaCountdown);
        if (!Number.isFinite(remaining) || remaining <= 0) return;
        const deadline = performance.now() + remaining * 1000;
        const label = box.querySelector('[data-mfa-time]');
        const buttons = Array.from(document.querySelectorAll('form[method="post"]'))
            .filter(function (form) { return form.querySelector('input[name="code"], input[name="password"]'); })
            .flatMap(function (form) { return Array.from(form.querySelectorAll('button')); });
        const enabledButtons = buttons.filter(function (button) { return !button.disabled; });
        enabledButtons.forEach(function (button) { button.disabled = true; });
        function update() {
            const seconds = Math.max(0, Math.ceil((deadline - performance.now()) / 1000));
            if (seconds === 0) {
                box.textContent = 'The wait is over. Try again. If setup or sign-in has expired, start again.';
                enabledButtons.forEach(function (button) { button.disabled = false; });
                clearInterval(timer);
                return;
            }
            if (label) label.textContent = String(Math.floor(seconds / 60)).padStart(2, '0') + ':' + String(seconds % 60).padStart(2, '0');
        }
        const timer = setInterval(update, 1000);
        update();
    });
    const menuButton = document.getElementById('adminMenuButton');
    const sidebar = document.getElementById('adminSidebar');
    if (menuButton && sidebar) {
        menuButton.addEventListener('click', function () {
            const isOpen = sidebar.classList.toggle('open');
            menuButton.setAttribute('aria-expanded', String(isOpen));
        });
    }

    document.querySelectorAll('[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.dataset.confirm || 'Una uhakika?')) {
                event.preventDefault();
            }
        });
    });

    const title = document.getElementById('title');
    const slug = document.getElementById('slug');
    if (title && slug) {
        let slugWasEdited = slug.value.trim() !== '';
        slug.addEventListener('input', function () { slugWasEdited = slug.value.trim() !== ''; });
        title.addEventListener('input', function () {
            if (slugWasEdited) return;
            slug.value = title.value.toLowerCase().normalize('NFKD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        });
    }

    const mediaTypes = Array.from(document.querySelectorAll('input[name="media_type"]'));
    const mediaUpload = document.getElementById('media_image');
    const imageUrl = document.getElementById('image_url');
    const imagePreview = document.getElementById('mediaImagePreview');
    const imagePreviewElement = document.getElementById('mediaImagePreviewElement');
    const imagePreviewStatus = document.getElementById('mediaImagePreviewStatus');
    const youtubeUrl = document.getElementById('youtube_url');
    const youtubePreview = document.getElementById('youtubeLinkPreview');
    const youtubePreviewImage = document.getElementById('youtubePreviewImage');
    const youtubePreviewTitle = document.getElementById('youtubePreviewTitle');
    const youtubePreviewStatus = document.getElementById('youtubePreviewStatus');
    const youtubePreviewOpen = document.getElementById('youtubePreviewOpen');
    const mediaCaption = document.getElementById('media_caption');
    const mediaPanels = Array.from(document.querySelectorAll('[data-media-panel]'));
    const destinationType = document.getElementById('destination_type');
    const mediaExternalNote = document.getElementById('mediaExternalNote');
    let imageObjectUrl = '';

    function selectedMediaType() {
        const selected = mediaTypes.find(function (input) { return input.checked; });
        return selected ? selected.value : 'none';
    }

    function showImagePreview(url, status) {
        if (!imagePreview || !imagePreviewElement || !imagePreviewStatus) return;
        if (!url) {
            imagePreview.hidden = true;
            imagePreviewElement.removeAttribute('src');
            return;
        }
        imagePreview.hidden = false;
        imagePreview.classList.remove('has-error');
        imagePreviewElement.hidden = false;
        imagePreviewStatus.textContent = status;
        imagePreviewElement.src = url;
    }

    function refreshImagePreview() {
        if (!imagePreview || !imagePreviewElement || !imagePreviewStatus) return;
        const file = mediaUpload && mediaUpload.files ? mediaUpload.files[0] : null;
        if (file) {
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5242880) {
                imagePreview.hidden = false;
                imagePreview.classList.add('has-error');
                imagePreviewElement.hidden = true;
                imagePreviewStatus.textContent = 'Choose a JPG, PNG or WebP image up to 5 MB.';
                return;
            }
            if (imageObjectUrl) URL.revokeObjectURL(imageObjectUrl);
            imageObjectUrl = URL.createObjectURL(file);
            showImagePreview(imageObjectUrl, file.name + ' · ' + Math.max(1, Math.round(file.size / 1024)) + ' KB');
            return;
        }
        const remoteUrl = imageUrl ? imageUrl.value.trim() : '';
        if (remoteUrl) {
            if (!/^https:\/\//i.test(remoteUrl)) {
                imagePreview.hidden = false;
                imagePreview.classList.add('has-error');
                imagePreviewElement.hidden = true;
                imagePreviewStatus.textContent = 'The image URL must start with https://';
                return;
            }
            showImagePreview(remoteUrl, 'Image preview from an HTTPS URL.');
            return;
        }
        showImagePreview(imagePreview.dataset.existingUrl || '', 'Current saved image.');
    }

    if (imagePreviewElement && imagePreviewStatus) {
        imagePreviewElement.addEventListener('error', function () {
            imagePreview.classList.add('has-error');
            imagePreviewElement.hidden = true;
            imagePreviewStatus.textContent = 'Image preview unavailable. Check the URL.';
        });
    }

    function youtubeVideoId(value) {
        try {
            const url = new URL(value);
            if (url.protocol !== 'https:') return '';
            const host = url.hostname.toLowerCase().replace(/^(www\.|m\.|music\.)/, '');
            const path = url.pathname.replace(/^\/+|\/+$/g, '');
            let id = '';
            if (host === 'youtu.be') id = path.split('/')[0] || '';
            if (host === 'youtube.com') {
                if (path === 'watch') id = url.searchParams.get('v') || '';
                const match = path.match(/^(?:embed|shorts|live)\/([^/]+)/);
                if (!id && match) id = match[1];
            }
            if (host === 'youtube-nocookie.com') {
                const match = path.match(/^embed\/([^/]+)/);
                if (match) id = match[1];
            }
            return /^[A-Za-z0-9_-]{11}$/.test(id) ? id : '';
        } catch (error) {
            return '';
        }
    }

    function refreshYoutubePreview() {
        if (!youtubePreview || !youtubePreviewTitle || !youtubePreviewStatus || !youtubePreviewOpen || !youtubeUrl) return;
        const value = youtubeUrl.value.trim();
        const id = youtubeVideoId(value);
        youtubePreview.classList.toggle('is-valid', Boolean(id));
        youtubePreview.classList.toggle('has-error', Boolean(value) && !id);
        youtubePreviewTitle.textContent = id ? 'YouTube link recognised' : (value ? 'Link not recognised' : 'Paste a YouTube link');
        youtubePreviewStatus.textContent = id
            ? 'Video ID: ' + id + '. The player loads when the reader presses Play.'
            : (value ? 'Use a full HTTPS link from YouTube.' : 'The video will not load or play automatically.');
        youtubePreviewOpen.hidden = !id;
        if (id) {
            youtubePreviewOpen.href = 'https://www.youtube.com/watch?v=' + encodeURIComponent(id);
            if (youtubePreviewImage) {
                youtubePreviewImage.src = 'https://i.ytimg.com/vi/' + encodeURIComponent(id) + '/hqdefault.jpg';
                youtubePreviewImage.hidden = false;
            }
        } else {
            youtubePreviewOpen.removeAttribute('href');
            if (youtubePreviewImage) {
                youtubePreviewImage.removeAttribute('src');
                youtubePreviewImage.hidden = true;
            }
        }
    }

    function updateMediaFields() {
        if (!mediaTypes.length) return;
        const isExternal = destinationType && destinationType.value === 'external';
        if (isExternal) {
            const noneOption = mediaTypes.find(function (input) { return input.value === 'none'; });
            if (noneOption) noneOption.checked = true;
        }
        const type = selectedMediaType();
        mediaTypes.forEach(function (input) {
            input.disabled = Boolean(isExternal && input.value !== 'none');
            const choice = input.closest('.media-choice');
            if (choice) {
                choice.classList.toggle('is-selected', input.checked);
                choice.classList.toggle('is-disabled', input.disabled);
            }
        });
        mediaPanels.forEach(function (panel) {
            const panelType = panel.dataset.mediaPanel;
            panel.hidden = panelType === 'caption' ? type === 'none' : panelType !== type;
        });
        if (mediaUpload) mediaUpload.disabled = type !== 'image' || Boolean(isExternal);
        if (imageUrl) imageUrl.disabled = type !== 'image' || Boolean(isExternal);
        if (youtubeUrl) {
            youtubeUrl.disabled = type !== 'youtube' || Boolean(isExternal);
            youtubeUrl.required = type === 'youtube' && !isExternal;
        }
        if (mediaCaption) mediaCaption.disabled = type === 'none' || Boolean(isExternal);
        if (mediaExternalNote) mediaExternalNote.hidden = !isExternal;
        if (type === 'image') refreshImagePreview();
        if (type === 'youtube') refreshYoutubePreview();
    }

    if (mediaTypes.length) {
        mediaTypes.forEach(function (input) { input.addEventListener('change', updateMediaFields); });
        if (destinationType) destinationType.addEventListener('change', updateMediaFields);
        if (mediaUpload) mediaUpload.addEventListener('change', refreshImagePreview);
        if (imageUrl) imageUrl.addEventListener('change', refreshImagePreview);
        if (youtubeUrl) youtubeUrl.addEventListener('input', refreshYoutubePreview);
        updateMediaFields();
    }
});
