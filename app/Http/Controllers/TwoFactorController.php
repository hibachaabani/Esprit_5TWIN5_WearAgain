<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorController extends Controller
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Affiche la page de configuration 2FA avec le QR code.
     * Génère un nouveau secret temporaire en session si besoin.
     */
    public function setup(Request $request): View
    {
        $user = $request->user();

        // Générer un nouveau secret temporaire et le stocker en session
        if (! $request->session()->has('2fa_secret')) {
            $request->session()->put('2fa_secret', $this->google2fa->generateSecretKey());
        }

        $secret = $request->session()->get('2fa_secret');

        // Construire l'URL OTP pour le QR code
        $otpUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        // Générer le QR code en SVG
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrCodeSvg = $writer->writeString($otpUrl);

        return view('profile.two-factor-setup', compact('secret', 'qrCodeSvg'));
    }

    /**
     * Valide le code TOTP saisi et active la 2FA si correct.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ], [
            'code.required' => 'Le code de vérification est obligatoire.',
            'code.digits'   => 'Le code doit contenir exactement 6 chiffres.',
        ]);

        $secret = $request->session()->get('2fa_secret');

        if (! $secret) {
            return redirect()->route('2fa.setup')
                ->with('erreur', 'Session expirée. Veuillez recommencer.');
        }

        $valid = $this->google2fa->verifyKey($secret, $request->code);

        if (! $valid) {
            return back()->withErrors(['code' => 'Code incorrect. Vérifiez votre application et réessayez.']);
        }

        // Code valide : sauvegarder le secret et activer la 2FA
        $request->user()->update([
            'two_factor_secret'  => $secret,
            'two_factor_enabled' => true,
        ]);

        $request->session()->forget('2fa_secret');

        return redirect()->route('profile.edit')
            ->with('status', '2fa-enabled');
    }

    /**
     * Désactive la double authentification pour l'utilisateur connecté.
     */
    public function disable(Request $request): RedirectResponse
    {
        $request->user()->update([
            'two_factor_enabled' => false,
            'two_factor_secret'  => null,
        ]);

        return redirect()->route('profile.edit')
            ->with('status', '2fa-disabled');
    }
}
