<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/admin_auth.php';
et_admin_boot(); et_require_admin_at_root(); et_redirect('account.php');