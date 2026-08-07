<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Deal\ConvertDealToOrder;
use App\Actions\Lead\ConvertLeadToDeal;
use App\Enums\Pipeline\DealStage;
use App\Enums\Pipeline\LeadStage;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Throwable;

/**
 * Converts records that reached Won before conversion became automatic.
 *
 * A command rather than a migration on purpose: it creates records, and doing
 * that implicitly on every deploy would be a surprising side effect.
 */
#[Description('Create the missing deals and orders for records already sitting in a Won stage')]
#[Signature('pipelines:backfill-conversions {--dry-run : List what would be created without writing}')]
final class BackfillPipelineConversionsCommand extends Command
{
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $leads = Lead::query()->withoutGlobalScopes()
            ->where('stage', LeadStage::WON->value)
            ->whereDoesntHave('deal')
            ->with(['creator', 'team.owner'])
            ->get();

        $deals = Deal::query()->withoutGlobalScopes()
            ->where('stage', DealStage::WON->value)
            ->whereNotExists(fn (Builder $query): Builder => $query->selectRaw('1')->from('orders')
                ->whereColumn('orders.deal_id', 'deals.id')
                ->whereNull('orders.deleted_at'))
            ->with(['creator', 'team.owner'])
            ->get();

        $this->info("Won leads without a deal: {$leads->count()}");
        $this->info("Won deals without an order: {$deals->count()}");

        $created = 0;

        foreach ($leads as $lead) {
            $user = $this->actorFor($lead);

            if (! $user instanceof User) {
                $this->warn("  skipped lead [{$lead->name}]: no user to attribute the conversion to");

                continue;
            }

            $this->line("  lead [{$lead->name}] -> deal");

            if (! $dryRun) {
                try {
                    resolve(ConvertLeadToDeal::class)->execute($user, $lead);
                    $created++;
                } catch (Throwable $e) {
                    $this->error("    failed: {$e->getMessage()}");
                }
            }
        }

        foreach ($deals as $deal) {
            $user = $this->actorFor($deal);

            if (! $user instanceof User) {
                $this->warn("  skipped deal [{$deal->name}]: no user to attribute the conversion to");

                continue;
            }

            $this->line("  deal [{$deal->name}] -> order");

            if (! $dryRun) {
                try {
                    resolve(ConvertDealToOrder::class)->execute($user, $deal);
                    $created++;
                } catch (Throwable $e) {
                    $this->error("    failed: {$e->getMessage()}");
                }
            }
        }

        $this->info($dryRun ? 'Dry run complete; nothing written.' : "Created {$created} record(s).");

        return self::SUCCESS;
    }

    private function actorFor(Lead|Deal $record): ?User
    {
        $creator = $record->getRelationValue('creator');

        if ($creator instanceof User) {
            return $creator;
        }

        $owner = $record->team->getRelationValue('owner');

        return $owner instanceof User ? $owner : null;
    }
}
