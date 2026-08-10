<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Concerns\DetectsTeamInvitation;
use Filament\Actions\Action;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Support\Htmlable;

final class Login extends \Filament\Auth\Pages\Login
{
    use DetectsTeamInvitation;

    /**
     * Scopes the sign-in styling to this page alone. Every other simple page —
     * register, password reset, email verification, workspace creation — shares
     * the `fi-simple-*` classes, so the theme keys off this class instead.
     *
     * @var array<string, string>
     */
    protected array $extraBodyAttributes = [
        'class' => 'fi-auth-login',
    ];

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Html::make(fn (): string => $this->getInvitationContentHtml()),
                RenderHook::make(PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE),
                $this->getFormContentComponent(),
                $this->getMultiFactorChallengeFormContentComponent(),
                RenderHook::make(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER),
            ]);
    }

    /**
     * Drops the public "or sign up for an account" call to action — accounts are
     * not created self-service from the sign-in page.
     *
     * A guest arriving from a team invitation is the exception: they may have no
     * account yet, and this link is their only route to the register page from
     * here, so the default subheading stands while an invitation is pending.
     */
    public function getSubheading(): string|Htmlable|null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return __('filament-panels::auth/pages/login.multi_factor.subheading');
        }

        if ($this->getTeamInvitationSubheading() instanceof Htmlable) {
            return parent::getSubheading();
        }

        return null;
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->size(Size::Medium)
            ->label(__('filament-panels::auth/pages/login.form.actions.authenticate.label'))
            ->submit('authenticate');
    }
}
