<?php

declare(strict_types=1);

use App\Enums\Pipeline\DealStage;
use App\Enums\Pipeline\DealSubStage;
use App\Filament\Forms\PipelineStageFields;
use App\Filament\Resources\DealResource\Pages\ListDeals;
use App\Models\Deal;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;

mutates(PipelineStageFields::class);

beforeEach(function (): void {
    $this->user = User::factory()->withTeam()->create();
    $this->actingAs($this->user);
    $this->team = $this->user->currentTeam;
    Filament::setTenant($this->team);
});

it('accepts every sub-stage of :dataset and rejects one from another stage', function (DealStage $stage): void {
    $foreign = collect(DealStage::cases())
        ->reject(fn (DealStage $other): bool => $other === $stage)
        ->flatMap(fn (DealStage $other): array => $other->subStages())
        ->reject(fn (DealSubStage $sub): bool => in_array($sub, $stage->subStages(), true))
        ->first();

    foreach ($stage->subStages() as $own) {
        $deal = Deal::factory()->recycle([$this->user, $this->team])->create();
        $deal->stage = $stage;
        $deal->sub_stage = $own;
        $deal->save();

        expect($deal->fresh()->sub_stage)
            ->toBe($own, "{$own->value} should be valid for {$stage->value}");
    }

    $deal = Deal::factory()->recycle([$this->user, $this->team])->create();
    $deal->stage = $stage;
    $deal->sub_stage = $foreign;
    $deal->save();

    // Asserts the foreign value specifically, not null: reaching a won stage
    // converts the record downstream, and that conversion stamps its own
    // sub-stage, so null is not guaranteed there.
    expect($deal->fresh()->sub_stage)
        ->not->toBe($foreign, "{$foreign->value} must not persist on {$stage->value}");
})->with(fn (): array => array_map(
    fn (DealStage $stage): array => [$stage],
    DealStage::cases(),
));

it('clears the sub-stage when the stage changes in the form', function (): void {
    $deal = Deal::factory()->recycle([$this->user, $this->team])->create([
        'stage' => DealStage::PURCHASE_ORDER,
        'sub_stage' => DealSubStage::PO_RECEIVED,
    ]);

    livewire(ListDeals::class)
        ->mountAction(TestAction::make('edit')->table($deal))
        ->assertSchemaStateSet([
            'stage' => DealStage::PURCHASE_ORDER->value,
            'sub_stage' => DealSubStage::PO_RECEIVED->value,
        ])
        ->fillForm(['stage' => DealStage::INVOICE->value])
        ->assertSchemaStateSet(['sub_stage' => null]);
});

it('persists a valid stage and sub-stage pair', function (): void {
    $deal = Deal::factory()->recycle([$this->user, $this->team])
        ->create(['stage' => DealStage::OPPORTUNITY]);

    livewire(ListDeals::class)
        ->callAction(TestAction::make('edit')->table($deal), [
            'name' => $deal->name,
            'stage' => DealStage::READY_FOR_PRODUCTION->value,
            'sub_stage' => DealSubStage::BOM_LOCKED->value,
        ])
        ->assertHasNoActionErrors();

    $deal->refresh();

    expect($deal->stage)->toBe(DealStage::READY_FOR_PRODUCTION)
        ->and($deal->sub_stage)->toBe(DealSubStage::BOM_LOCKED);
});

it('refuses to persist a sub-stage from a different stage', function (): void {
    $deal = Deal::factory()->recycle([$this->user, $this->team])
        ->create(['stage' => DealStage::OPPORTUNITY]);

    // QC belongs to the Order pipeline's Production stage, so it is not a valid
    // pairing for any deal stage and must not survive the write.
    $deal->stage = DealStage::INVOICE;
    $deal->sub_stage = DealSubStage::BOM_LOCKED;
    $deal->save();

    expect($deal->fresh()->sub_stage)->toBeNull();
});
