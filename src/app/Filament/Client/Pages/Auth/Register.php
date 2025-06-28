<?php

namespace App\Filament\Client\Pages\Auth;

use App\Models\Branch;
use App\Models\Client;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Form;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;

class Register extends BaseRegister
{
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Account Info')
                        ->columns(2)
                        ->schema([
                            $this->getNameFormComponent(),
                            $this->getEmailFormComponent(),
                            $this->getPasswordFormComponent(),
                            $this->getPasswordConfirmationFormComponent(),
                            Select::make('branch_id')
                                ->label('Branch')
                                ->options(fn() => Branch::pluck('name', 'id')->toArray())
                                ->required(),
                        ]),

                    Step::make('Client Details')
                        ->columns(2)
                        ->schema([
                            TextInput::make('address')->required(),
                            TextInput::make('state')->required(),
                            TextInput::make('country')->required(),
                            TextInput::make('postcode')->required(),
                            TextInput::make('contact_person')
                                ->label('Contact Person')
                                ->required(),
                            TextInput::make('phone')
                                ->tel()
                                ->required(),
                        ]),
                ])
                    ->skippable()
                //>submitActionLabel('Register'),
            ])
            ->statePath('data');
    }

    public function register(): ?RegistrationResponse
    {
        $data = $this->form->getState();

        // 1. Create user
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // 2. Assign role
        $user->assignRole('client');

        // 3. Create client
        Client::create([
            'user_id' => $user->id,
            'branch_id' => $data['branch_id'],
            'code' => 'CLT-' . strtoupper(uniqid()),
            'address' => $data['address'],
            'state' => $data['state'],
            'country' => $data['country'],
            'postcode' => $data['postcode'],
            'contact_person' => $data['contact_person'],
            'phone' => $data['phone'],
        ]);

        event(new Registered($user));
        Filament::auth()->login($user);
        session()->regenerate();

        return app(RegistrationResponse::class);
    }
}
