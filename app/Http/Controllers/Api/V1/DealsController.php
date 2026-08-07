<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Deal\CreateDeal;
use App\Actions\Deal\DeleteDeal;
use App\Actions\Deal\ListDeals;
use App\Actions\Deal\UpdateDeal;
use App\Enums\CreationSource;
use App\Http\Requests\Api\V1\IndexRequest;
use App\Http\Requests\Api\V1\StoreDealRequest;
use App\Http\Requests\Api\V1\UpdateDealRequest;
use App\Http\Resources\V1\DealResource;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

/**
 * @group Deals
 *
 * Manage sales deals in your CRM workspace.
 */
final readonly class DealsController
{
    #[ResponseFromApiResource(DealResource::class, Deal::class, collection: true, paginate: 15)]
    public function index(IndexRequest $request, ListDeals $action, #[CurrentUser] User $user): AnonymousResourceCollection
    {
        return DealResource::collection($action->execute(
            user: $user,
            perPage: $request->safe()->integer('per_page', 15),
            useCursor: $request->safe()->has('cursor'),
            request: $request,
        ));
    }

    #[ResponseFromApiResource(DealResource::class, Deal::class, status: 201)]
    #[BodyParam('name', 'string', required: true, example: 'Enterprise Deal')]
    #[BodyParam('company_id', 'string', required: false, example: null)]
    #[BodyParam('contact_id', 'string', required: false, example: null)]
    public function store(StoreDealRequest $request, CreateDeal $action, #[CurrentUser] User $user): JsonResponse
    {
        $deal = $action->execute($user, $request->validated(), CreationSource::API);

        return new DealResource($deal)
            ->response()
            ->setStatusCode(201);
    }

    #[ResponseFromApiResource(DealResource::class, Deal::class)]
    public function show(Deal $deal): DealResource
    {
        Gate::authorize('view', $deal);

        $deal->loadMissing('customFieldValues.customField.options');

        return new DealResource($deal);
    }

    #[ResponseFromApiResource(DealResource::class, Deal::class)]
    #[BodyParam('name', 'string', required: false, example: 'Enterprise Deal')]
    #[BodyParam('company_id', 'string', required: false, example: null)]
    #[BodyParam('contact_id', 'string', required: false, example: null)]
    public function update(UpdateDealRequest $request, Deal $deal, UpdateDeal $action, #[CurrentUser] User $user): DealResource
    {
        $deal = $action->execute($user, $deal, $request->validated());

        return new DealResource($deal);
    }

    #[Response(status: 204)]
    public function destroy(Deal $deal, DeleteDeal $action, #[CurrentUser] User $user): HttpResponse
    {
        $action->execute($user, $deal);

        return response()->noContent();
    }
}
