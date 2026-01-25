<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Sends reset links without leaking whether an email exists.
 */
class PasswordResetLinkController extends Controller
{
    /**
    * Handle the incoming request to send a reset link.
    */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // Allow disabling password resets entirely in demo/locked-down deployments.
        if (! config('mail.enabled', true)) {
            return response()->json([
                'status' => __('Password reset is not enabled.'),
                'sent' => false,
            ], 200);
        }

        $mailer = config('mail.default');
        $mailerConfig = config("mail.mailers.{$mailer}", []);
        $mailEnabled = $mailer && ! in_array($mailer, ['log', 'array'], true)
            && ! empty($mailerConfig['host'])
            && ! empty($mailerConfig['port']);

        // Short-circuit if the mail transport is not configured.
        if (! $mailEnabled) {
            return response()->json([
                'status' => __('Password reset email is not configured.'),
                'sent' => false,
            ], 200);
        }

        // Intentionally avoid signaling whether the email exists to the client.
        try {
            $status = Password::sendResetLink($request->only('email'));
            $sent = $status === Password::RESET_LINK_SENT;
        } catch (\Throwable $e) {
            // Mail transport failure or other issue; surface a generic status.
            report($e);
            $status = 'reset.link.failed';
            $sent = false;
        }

        return response()->json([
            'status' => __($status),
            'sent' => $sent,
        ], 200);
    }
}
