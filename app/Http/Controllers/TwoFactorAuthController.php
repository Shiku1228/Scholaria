<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthController extends Controller
{
    protected $google2fa;

    public function __construct(Google2FA $google2fa)
    {
        $this->google2fa = $google2fa;
    }

    /**
     * Show the 2FA setup page
     */
    public function showSetup()
    {
        $user = Auth::user();

        if ($user->google2fa_enabled) {
            return redirect()->route('dashboard')->with('info', '2FA is already enabled.');
        }

        // Generate a new secret key
        $secret = $this->google2fa->generateSecretKey();

        // Store the secret temporarily in session
        session(['2fa_secret' => $secret]);

        // Generate QR code
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        // Use inline SVG for QR code (no external library needed)
        $qrCode = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($qrCodeUrl);

        // Return JSON if AJAX request
        if (request()->expectsJson()) {
            return response()->json([
                'secret' => $secret,
                'qrCode' => $qrCode,
                'qrCodeUrl' => $qrCodeUrl
            ]);
        }

        return view('auth.2fa-setup', compact('secret', 'qrCode'));
    }

    /**
     * Get QR code data for modal
     */
    public function getQrCode()
    {
        $user = Auth::user();

        if ($user->google2fa_enabled) {
            return response()->json(['success' => false, 'message' => '2FA is already enabled.']);
        }

        // Generate a new secret key
        $secret = $this->google2fa->generateSecretKey();

        // Store the secret temporarily in session
        session(['2fa_secret' => $secret]);

        // Generate QR code
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        // Use inline SVG for QR code (no external library needed)
        $qrCode = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($qrCodeUrl);

        return response()->json([
            'success' => true,
            'secret' => $secret,
            'qrCode' => $qrCode,
            'qrCodeUrl' => $qrCodeUrl
        ]);
    }

    /**
     * Enable 2FA for the user
     */
    public function enable(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = Auth::user();
        $secret = session('2fa_secret');

        if (!$secret) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Session expired. Please try again.']);
            }
            return redirect()->back()->with('error', 'Session expired. Please try again.');
        }

        // Verify the code
        $valid = $this->google2fa->verifyKey($secret, $request->code);

        if ($valid) {
            // Encrypt and save the secret
            $user->google2fa_secret = Crypt::encryptString($secret);
            $user->google2fa_enabled = true;
            $user->save();

            // Clear the session
            session()->forget('2fa_secret');

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => '2FA has been enabled successfully.']);
            }
            return redirect()->back()->with('success', '2FA has been enabled successfully.');
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => 'Invalid verification code. Please try again.']);
        }
        return redirect()->back()->with('error', 'Invalid verification code. Please try again.');
    }

    /**
     * Disable 2FA for the user
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = Auth::user();

        // Verify password
        if (!Auth::attempt(['email' => $user->email, 'password' => $request->password])) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Invalid password.']);
            }
            return redirect()->back()->with('error', 'Invalid password.');
        }

        $user->google2fa_secret = null;
        $user->google2fa_enabled = false;
        $user->save();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => '2FA has been disabled.']);
        }
        return redirect()->back()->with('success', '2FA has been disabled.');
    }

    /**
     * Show the 2FA verification page
     */
    public function showVerification()
    {
        return view('auth.2fa-verify');
    }

    /**
     * Verify the 2FA code during login
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = Auth::user();

        if (!$user->google2fa_secret) {
            return redirect()->route('dashboard');
        }

        // Decrypt the secret
        try {
            $secret = Crypt::decryptString($user->google2fa_secret);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Unable to verify 2FA. Please contact support.');
        }

        // Verify the code
        $valid = $this->google2fa->verifyKey($secret, $request->code);

        if ($valid) {
            // Mark 2FA as verified in session
            session(['2fa_verified' => true]);

            // Redirect based on user role
            $user = Auth::user();
            if ($user->hasRole('Admin')) {
                return redirect()->intended(route('admin.dashboard'));
            } elseif ($user->hasRole('Teacher')) {
                return redirect()->intended(route('teacher.dashboard'));
            } elseif ($user->hasRole('Student')) {
                return redirect()->intended(route('student.dashboard'));
            }

            return redirect()->intended(route('student.dashboard'));
        }

        return redirect()->back()->with('error', 'Invalid verification code. Please try again.');
    }
}
