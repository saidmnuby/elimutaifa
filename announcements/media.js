document.addEventListener('DOMContentLoaded', function () {
    const videoBlocks = Array.from(document.querySelectorAll('.article-video[data-youtube-id]'));
    let activeBlock = null;

    function createPlayButton(block) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'article-video-trigger';
        button.setAttribute('aria-label', 'Cheza ' + (block.dataset.videoTitle || 'video ya YouTube'));

        const poster = document.createElement('img');
        poster.className = 'article-video-poster';
        poster.src = block.dataset.youtubeCover || '';
        poster.alt = '';
        poster.loading = 'lazy';
        poster.decoding = 'async';
        poster.referrerPolicy = 'no-referrer';

        const playIcon = document.createElement('span');
        playIcon.className = 'article-video-play-icon';
        playIcon.setAttribute('aria-hidden', 'true');
        playIcon.textContent = '▶';

        const copy = document.createElement('span');
        copy.className = 'article-video-trigger-copy';
        const title = document.createElement('strong');
        title.textContent = block.dataset.videoTitle || 'Video ya YouTube';
        const hint = document.createElement('small');
        hint.textContent = 'YouTube itaunganishwa baada ya kubonyeza';
        copy.append(title, hint);
        const overlay = document.createElement('span');
        overlay.className = 'article-video-trigger-overlay';
        overlay.append(playIcon, copy);
        button.append(poster, overlay);
        button.addEventListener('click', function () { activateVideo(block); });
        return button;
    }

    function resetVideo(block) {
        block.replaceChildren(createPlayButton(block));
        if (activeBlock === block) activeBlock = null;
    }

    function activateVideo(block) {
        if (activeBlock && activeBlock !== block) resetVideo(activeBlock);

        const iframe = document.createElement('iframe');
        iframe.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(block.dataset.youtubeId) + '?autoplay=1&rel=0&playsinline=1';
        iframe.title = block.dataset.videoTitle || 'Video ya YouTube';
        iframe.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
        iframe.referrerPolicy = 'strict-origin-when-cross-origin';
        iframe.allowFullscreen = true;
        const player = document.createElement('div');
        player.className = 'article-video-player';
        const stopButton = document.createElement('button');
        stopButton.type = 'button';
        stopButton.className = 'article-video-stop';
        stopButton.textContent = 'Funga video';
        stopButton.addEventListener('click', function () { resetVideo(block); });
        player.append(iframe, stopButton);
        block.replaceChildren(player);
        activeBlock = block;
        iframe.focus();
    }

    videoBlocks.forEach(resetVideo);
});
