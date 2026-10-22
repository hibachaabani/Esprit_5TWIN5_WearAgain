<?php

namespace App\Http\Controllers;

use App\Services\RecoveryCodeService;
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
    protected RecoveryCodeService $recoveryCodes;

    public function __construct(RecoveryCodeService $recoveryCodes)
    {
        $this->google2fa    = new Google2FA();
        $this->recoveryCodes = $recoveryCodes;
    }

    /**
     * Affiche la page de configuration 2FA avec le QR code.
     */
    public function setup(Request $request): View
    {
        $user = $request->user();

        if (! $request->session()->has('2fa_secret')) {
            $request->session()->put('2fa_secret', $this->google2fa->generateSecretKey());
        }

        $secret = $request->session()->get('2fa_secret');

        $otpUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        $qrCodeSvg = (new Writer($renderer))->writeString($otpUrl);

        return view('profile.two-factor-setup', compact('secret', 'qrCodeSvg'));
    }

    /**
     * Valide le code TOTP, active la 2FA et génère les codes de récupération.
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

        if (! $this->google2fa->verifyKey($secret, $request->code)) {
            return back()->withErrors(['code' => 'Code incorrect. Vérifiez votre application et réessayez.']);
        }

        // Générer les codes de récupération en clair (à afficher une seule fois)
        $plainCodes  = $this->recoveryCodes->generate();
        $hashedCodes = $this->recoveryCodes->hash($plainCodes);

        $request->user()->update([
            'two_factor_secret'         => $secret,
            'two_factor_enabled'        => true,
            'two_factor_recovery_codes' => $hashedCodes,
        ]);

        $request->session()->forget('2fa_secret');

        // Stocker les codes en clair en session pour les afficher une seule fois
        $request->session()->put('2fa_recovery_codes_plain', $plainCodes);

        return redirect()->route('2fa.recovery-codes.show');
    }

    /**
     * Désactive la 2FA et efface le secret et les codes de récupération.
     */
    public function disable(Request $request): RedirectResponse
    {
        $request->user()->update([
            'two_factor_enabled'        => false,
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
        ]);

        return redirect()->route('profile.edit')
            ->with('status', 'La double authentification est désactivée.');
    }
}
