<?php

namespace App\Console\Commands;

use App\Models\FishingTrip;
use App\Models\FishingTripPhoto;
use App\Models\User;
use App\Support\FishingTripSampleFactory;
use Illuminate\Console\Command;
use Illuminate\Http\File;
use Throwable;

class FmSeedFishingTripsCommand extends Command
{
    protected $signature = 'fm:seed:fishing-trips
        {user_id? : Owner user id. Defaults to the first FileMaker user}
        {--user-email= : Resolve the owner by email instead of id}
        {--count=6 : Number of trips to create}
        {--photos=1 : Number of placeholder photos per trip}
        {--without-photos : Create trips only}';

    protected $description = 'Seed sample fishing trips into FileMaker';

    public function handle(FishingTripSampleFactory $factory): int
    {
        $count = max(1, (int) $this->option('count'));
        $photosPerTrip = max(0, (int) $this->option('photos'));
        $withPhotos = ! (bool) $this->option('without-photos') && $photosPerTrip > 0;

        try {
            $user = $this->resolveUser();
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        if (! $user) {
            $this->components->error('No FileMaker user was found. Create or sync a user first.');

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'Seeding %d fishing trip(s) for %s (%s).',
            $count,
            $user->email ?: $user->name ?: $user->id,
            $user->id,
        ));

        $createdTrips = 0;
        $createdPhotos = 0;

        for ($tripIndex = 0; $tripIndex < $count; $tripIndex++) {
            $trip = new FishingTrip();

            foreach ($factory->makeTripAttributes((string) $user->id, $tripIndex) as $key => $value) {
                $trip->{$key} = $value;
            }

            try {
                $trip->save();
            } catch (Throwable $e) {
                $this->components->error(sprintf(
                    'Failed to create sample trip %d: %s',
                    $tripIndex + 1,
                    $e->getMessage(),
                ));

                return self::FAILURE;
            }

            $createdTrips++;

            $this->line(sprintf(
                '  - %s / %s',
                $trip->river_name,
                $trip->trip_date_label ?? $trip->trip_date?->format('Y-m-d'),
            ));

            if (! $withPhotos) {
                continue;
            }

            for ($photoIndex = 0; $photoIndex < $photosPerTrip; $photoIndex++) {
                $temporaryImagePath = null;

                try {
                    $temporaryImagePath = $factory->createPlaceholderImage($tripIndex, $photoIndex, (string) $trip->river_name);

                    $photo = new FishingTripPhoto();

                    foreach ($factory->makePhotoAttributes((string) $trip->id, $tripIndex, $photoIndex) as $key => $value) {
                        $photo->{$key} = $value;
                    }

                    $photo->image = new File($temporaryImagePath);
                    $photo->save();

                    $createdPhotos++;
                } catch (Throwable $e) {
                    $this->components->error(sprintf(
                        'Failed to create sample photo %d for trip %d: %s',
                        $photoIndex + 1,
                        $tripIndex + 1,
                        $e->getMessage(),
                    ));

                    return self::FAILURE;
                } finally {
                    if ($temporaryImagePath && file_exists($temporaryImagePath)) {
                        @unlink($temporaryImagePath);
                    }
                }
            }
        }

        $this->newLine();
        $this->components->info(sprintf(
            'Created %d fishing trip(s)%s.',
            $createdTrips,
            $withPhotos ? sprintf(' and %d photo(s)', $createdPhotos) : '',
        ));

        return self::SUCCESS;
    }

    private function resolveUser(): ?User
    {
        $userId = $this->argument('user_id');
        $userEmail = $this->option('user-email');

        if (is_string($userEmail) && $userEmail !== '') {
            return User::where('email', '==', $userEmail)->first();
        }

        if (is_string($userId) && $userId !== '') {
            return User::where('id', '==', $userId)->first();
        }

        return User::first();
    }
}
