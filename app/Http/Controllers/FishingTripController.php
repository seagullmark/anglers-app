<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyFishingTripRequest;
use App\Http\Requests\StoreFishingTripRequest;
use App\Http\Requests\UpdateFishingTripRequest;
use App\Models\FishingTrip;
use App\Models\FishingTripPhoto;
use GearboxSolutions\EloquentFileMaker\Exceptions\FileMakerDataApiException;
use Illuminate\Http\File;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Intervention\Image\ImageManager;

class FishingTripController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', FishingTrip::class);

        return inertia('FishingTrips/Index', [
            'trips' => Inertia::scroll(fn () => FishingTrip::query()
                ->where('user_id', '==', (string) $request->user()->id)
                ->orderBy('trip_date', 'desc')
                ->orderBy('start_at', 'desc')
                ->paginate(9)
                ->through(fn (FishingTrip $trip) => $this->tripCardData($trip))),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', FishingTrip::class);

        return inertia('FishingTrips/Form', [
            'mode' => 'create',
            'trip' => [
                'id' => null,
                'mod_id' => null,
                'trip_date' => null,
                'start_at' => null,
                'end_at' => null,
                'river_name' => '',
                'point_name' => '',
                'tackle_name' => '',
                'memo' => '',
                'photos' => [],
            ],
        ]);
    }

    public function store(StoreFishingTripRequest $request): RedirectResponse
    {
        $this->authorize('create', FishingTrip::class);

        $trip = new FishingTrip();
        $trip->id = (string) Str::uuid();
        $trip->user_id = (string) $request->user()->id;

        $this->fillTripFromRequest($trip, $request);

        $trip->save();

        $this->storeUploadedPhotos($trip, $request->file('photos', []));

        return to_route('fishing-trips.edit', $trip->id)
            ->with('success', __('Fishing trip saved.'));
    }

    public function edit(string $fishing_trip): Response
    {
        $trip = $this->findFishingTripOrFail($fishing_trip);

        $this->authorize('view', $trip);

        return inertia('FishingTrips/Form', [
            'mode' => 'edit',
            'trip' => $this->tripFormData($trip),
        ]);
    }

    public function update(UpdateFishingTripRequest $request, string $fishing_trip): RedirectResponse
    {
        $trip = $this->findFishingTripOrFail($fishing_trip);

        $this->authorize('update', $trip);

        $this->fillTripFromRequest($trip, $request);

        try {
            $trip->withModId((string) $request->string('mod_id'))->save();
        } catch (FileMakerDataApiException $e) {
            if ((int) $e->getCode() === 306) {
                throw ValidationException::withMessages([
                    'mod_id' => __('Another update was saved first. Reload the page and try again.'),
                ]);
            }

            throw $e;
        }

        $this->deleteRemovedPhotos($trip, $request->input('remove_photo_ids', []));
        $this->storeUploadedPhotos($trip, $request->file('photos', []));

        return to_route('fishing-trips.edit', $trip->id)
            ->with('success', __('Fishing trip updated.'));
    }

    public function destroy(DestroyFishingTripRequest $request, string $fishing_trip): RedirectResponse
    {
        $trip = $this->findFishingTripOrFail($fishing_trip);

        $this->authorize('delete', $trip);

        if ((string) $trip->getModId() !== (string) $request->string('mod_id')) {
            throw ValidationException::withMessages([
                'mod_id' => __('Another update was saved first. Reload the page and try again.'),
            ]);
        }

        foreach ($trip->photos()->get() as $photo) {
            $photo->delete();
        }

        $trip->delete();

        return to_route('fishing-trips.index')
            ->with('success', __('Fishing trip deleted.'));
    }

    private function fillTripFromRequest(FishingTrip $trip, StoreFishingTripRequest|UpdateFishingTripRequest $request): void
    {
        $trip->trip_date = $request->date('trip_date');
        $trip->start_at = $request->date('start_at');
        $trip->end_at = $request->date('end_at');
        $trip->river_name = (string) $request->string('river_name');
        $trip->point_name = (string) $request->string('point_name');
        $trip->tackle_name = (string) $request->string('tackle_name');
        $trip->memo = $request->filled('memo') ? (string) $request->string('memo') : null;
    }

    private function findFishingTripOrFail(string $id): FishingTrip
    {
        return FishingTrip::query()
            ->where('id', '==', $id)
            ->firstOrFail();
    }

    private function tripCardData(FishingTrip $trip): array
    {
        $photos = $trip->photos()->get();
        $coverPhoto = $photos->first();

        return [
            'id' => (string) $trip->id,
            'trip_date' => $trip->trip_date_label,
            'start_time' => $trip->start_time,
            'end_time' => $trip->end_time,
            'river_name' => $trip->river_name,
            'point_name' => $trip->point_name,
            'tackle_name' => $trip->tackle_name,
            'memo' => $trip->memo,
            'cover_image_url' => $coverPhoto?->image_url,
            'photo_count' => $photos->count(),
        ];
    }

    private function tripFormData(FishingTrip $trip): array
    {
        return [
            'id' => (string) $trip->id,
            'mod_id' => (string) $trip->getModId(),
            'trip_date' => $trip->trip_date_label,
            'start_at' => $trip->start_at_input,
            'end_at' => $trip->end_at_input,
            'river_name' => $trip->river_name,
            'point_name' => $trip->point_name,
            'tackle_name' => $trip->tackle_name,
            'memo' => $trip->memo,
            'photos' => $trip->photos()->get()
                ->map(fn (FishingTripPhoto $photo) => [
                    'id' => (string) $photo->id,
                    'caption' => $photo->caption,
                    'sort_order' => $photo->sort_order,
                    'image_url' => $photo->image_url,
                ])
                ->values()
                ->all(),
        ];
    }

    private function deleteRemovedPhotos(FishingTrip $trip, array $removePhotoIds): void
    {
        if ($removePhotoIds === []) {
            return;
        }

        $removePhotoIds = array_map('strval', $removePhotoIds);

        foreach ($trip->photos()->get() as $photo) {
            if (in_array((string) $photo->id, $removePhotoIds, true)) {
                $photo->delete();
            }
        }
    }

    private function storeUploadedPhotos(FishingTrip $trip, array $uploadedPhotos): void
    {
        if ($uploadedPhotos === []) {
            return;
        }

        Storage::makeDirectory('tmp');

        $sortOrder = (int) ($trip->photos()->get()->max('sort_order') ?? 0);

        foreach ($uploadedPhotos as $uploadedPhoto) {
            $path = $uploadedPhoto->store('tmp');
            $fullPath = Storage::path($path);

            try {
                ImageManager::gd()
                    ->read($uploadedPhoto)
                    ->scaleDown(1600, 1600)
                    ->save($fullPath);

                $photo = new FishingTripPhoto();
                $photo->id = (string) Str::uuid();
                $photo->fishing_trip_id = (string) $trip->id;
                $photo->sort_order = ++$sortOrder;
                $photo->caption = null;
                $photo->image = new File($fullPath);
                $photo->save();
            } finally {
                Storage::delete($path);
            }
        }
    }
}
