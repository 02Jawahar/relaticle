<?php

declare(strict_types=1);

use App\Filament\Resources\CompanyResource\Pages\ViewCompany;
use App\Filament\Resources\DealResource\Pages\ViewDeal;
use App\Filament\Resources\PeopleResource\Pages\ViewPeople;
use App\Models\Company;
use App\Models\Deal;
use App\Models\People;
use App\Models\User;
use Filament\Facades\Filament;

mutates(ViewCompany::class, ViewPeople::class, ViewDeal::class);

beforeEach(function (): void {
    $this->user = User::factory()->withTeam()->create();
    $this->actingAs($this->user);
    $this->team = $this->user->currentTeam;
    Filament::setTenant($this->team);
});

it('no longer exposes the AI summary or ask-about-this actions on a company', function (): void {
    $company = Company::factory()->recycle([$this->user, $this->team])->create();

    livewire(ViewCompany::class, ['record' => $company->getKey()])
        ->assertActionDoesNotExist('generateSummary')
        ->assertActionDoesNotExist('askAboutThis')
        ->assertActionExists('edit');
});

it('no longer exposes the AI summary or ask-about-this actions on a person', function (): void {
    $person = People::factory()->recycle([$this->user, $this->team])->create();

    livewire(ViewPeople::class, ['record' => $person->getKey()])
        ->assertActionDoesNotExist('generateSummary')
        ->assertActionDoesNotExist('askAboutThis')
        ->assertActionExists('edit');
});

it('no longer exposes the AI summary or ask-about-this actions on an deal', function (): void {
    $deal = Deal::factory()->recycle([$this->user, $this->team])->create();

    livewire(ViewDeal::class, ['record' => $deal->getKey()])
        ->assertActionDoesNotExist('generateSummary')
        ->assertActionDoesNotExist('askAboutThis')
        ->assertActionExists('edit');
});
