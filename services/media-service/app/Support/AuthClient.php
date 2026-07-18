<?php
namespace App\Support;
/** Media Service authenticates callers via the X-Internal-Token shared secret,
 *  not token introspection. Stub kept for shared-middleware autoloading. */
class AuthClient { public function verify(string $token): ?array { return null; } }
