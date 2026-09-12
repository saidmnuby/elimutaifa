<?php
declare(strict_types=1);

function et_send_nonindex_page_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow, noarchive', true);
    header('Cache-Control: private, no-store, max-age=0', true);
    header('Pragma: no-cache', true);
}
