<?php
/**
 * Evil Portal - Docker Standalone Entry Point
 * Includes a minimal Portal stub so MyPortal.php works without Pineapple framework.
 */

namespace evilportal;

class Portal {
    protected function authorizeClient($ip) {
        error_log("[EvilPortal] Authorized: $ip");
    }
    protected function unauthorizeClient($ip) {
        error_log("[EvilPortal] Unauthorized: $ip");
    }
    protected function getClientMac($ip) {
        return '00:00:00:00:00:00';
    }
}

require_once(__DIR__ . '/MyPortal.php');

use evilportal\MyPortal;

$portal = new MyPortal();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $portal->onSuccess();
} else {
    $portal->handleAuthorization();
}
