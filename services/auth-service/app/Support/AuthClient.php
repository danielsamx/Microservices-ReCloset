<?php
namespace App\Support;
/** Not used by the Auth Service itself (it authenticates via Sanctum directly),
 *  present only so the shared AuthenticateService middleware autoloads. */
class AuthClient { public function verify(string $token): ?array { return null; } }
