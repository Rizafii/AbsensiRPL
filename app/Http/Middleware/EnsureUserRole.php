<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        if ($roles !== [] && ! in_array($user->role, $roles, true)) {
            return $this->redirectToRoleHome($user->role);
        }

        return $next($request);
    }

    private function redirectToRoleHome(string $role): Response
    {
        return match ($role) {
            User::ROLE_ADMIN => redirect()->route('dashboard'),
            User::ROLE_STUDENT => redirect()->route('student.attendance.dashboard'),
            default => abort(403),
        };
    }
}
