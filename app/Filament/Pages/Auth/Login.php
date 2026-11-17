<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as FilamentLogin;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class Login extends FilamentLogin
{
    public bool $authFailed = false;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Banner error di PALING ATAS — hanya muncul saat login gagal
                Placeholder::make('auth_error_banner')
                    ->label('')
                    ->content(function () {
                        if (! $this->authFailed) {
                            return new HtmlString('');
                        }

                        return new HtmlString('
                            <div style="
                                background-color: #fef2f2;
                                border: 1px solid #fecaca;
                                border-radius: 10px;
                                padding: 14px 18px;
                                display: flex;
                                align-items: flex-start;
                                gap: 10px;
                                margin-bottom: 4px;
                            ">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="#ef4444"
                                    style="width:20px;height:20px;flex-shrink:0;margin-top:1px;">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                                </svg>
                                <span style="color:#b91c1c;font-size:14px;font-weight:500;line-height:1.5;">
                                    Email atau kata sandi yang Anda masukkan salah. Silakan coba lagi.
                                </span>
                            </div>
                        ');
                    })
                    ->columnSpanFull(),

                TextInput::make('email')
                    ->label('ALAMAT EMAIL')
                    ->email()
                    ->required()
                    ->autocomplete()
                    ->autofocus()
                    ->placeholder('email@shoeworkshop.com')
                    ->prefixIcon('heroicon-m-envelope')
                    ->live()
                    ->afterStateUpdated(fn () => $this->authFailed = false)
                    ->extraInputAttributes(['tabindex' => 1]),

                TextInput::make('password')
                    ->label('KATA SANDI AMAN')
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->required()
                    ->placeholder('············')
                    ->prefixIcon('heroicon-m-lock-closed')
                    ->live()
                    ->afterStateUpdated(fn () => $this->authFailed = false)
                    ->extraInputAttributes(['tabindex' => 2]),

                Checkbox::make('remember')
                    ->label('Ingat saya'),
            ]);
    }

    protected function getAuthenticateFormAction(): \Filament\Actions\Action
    {
        return parent::getAuthenticateFormAction()
            ->label('Masuk ke Portal →');
    }

    protected function throwFailureValidationException(): never
    {
        $this->authFailed = true;

        throw ValidationException::withMessages([
            'data.email' => ' ', // spasi — field jadi merah tapi tidak ada teks inline
        ]);
    }
}
