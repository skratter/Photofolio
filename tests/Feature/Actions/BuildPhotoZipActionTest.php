<?php

use App\Actions\BuildPhotoZipAction;
use App\Models\Album;
use App\Models\Photo;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

beforeEach(function () {
    Storage::fake('photos');
});

test('it zips each photo\'s original file under its download filename', function () {
    $album = Album::factory()->create();
    $photo1 = Photo::factory()->for($album)->create(['title' => 'Strand']);
    $photo2 = Photo::factory()->for($album)->create(['title' => 'Berge']);

    File::ensureDirectoryExists($photo1->directoryPath());
    File::put($photo1->originalPath(), 'bytes-strand');
    File::ensureDirectoryExists($photo2->directoryPath());
    File::put($photo2->originalPath(), 'bytes-berge');

    $zipPath = (new BuildPhotoZipAction)->execute(collect([$photo1, $photo2]));

    $zip = new ZipArchive;
    $zip->open($zipPath);

    expect($zip->numFiles)->toBe(2)
        ->and($zip->getFromName('strand.jpg'))->toBe('bytes-strand')
        ->and($zip->getFromName('berge.jpg'))->toBe('bytes-berge');

    $zip->close();
    File::delete($zipPath);
});

test('it disambiguates photos that would otherwise share the same filename', function () {
    $album = Album::factory()->create();
    $photo1 = Photo::factory()->for($album)->create(['title' => 'Urlaub']);
    $photo2 = Photo::factory()->for($album)->create(['title' => 'Urlaub']);

    foreach ([$photo1, $photo2] as $photo) {
        File::ensureDirectoryExists($photo->directoryPath());
        File::put($photo->originalPath(), "bytes-{$photo->id}");
    }

    $zipPath = (new BuildPhotoZipAction)->execute(collect([$photo1, $photo2]));

    $zip = new ZipArchive;
    $zip->open($zipPath);

    expect($zip->numFiles)->toBe(2)
        ->and($zip->getFromName('urlaub.jpg'))->toBe("bytes-{$photo1->id}")
        ->and($zip->getFromName("urlaub-{$photo2->id}.jpg"))->toBe("bytes-{$photo2->id}");

    $zip->close();
    File::delete($zipPath);
});

test('it aborts with 404 if none of the photos have a file on disk', function () {
    $album = Album::factory()->create();
    $photo = Photo::factory()->for($album)->create(['title' => 'Fehlt']);

    expect(fn () => (new BuildPhotoZipAction)->execute(collect([$photo])))
        ->toThrow(NotFoundHttpException::class);
});

test('it skips photos whose original file is missing but keeps the rest', function () {
    $album = Album::factory()->create();
    $missing = Photo::factory()->for($album)->create(['title' => 'Fehlt']);
    $present = Photo::factory()->for($album)->create(['title' => 'Vorhanden']);

    File::ensureDirectoryExists($present->directoryPath());
    File::put($present->originalPath(), 'bytes-present');

    $zipPath = (new BuildPhotoZipAction)->execute(collect([$missing, $present]));

    $zip = new ZipArchive;
    $zip->open($zipPath);

    expect($zip->numFiles)->toBe(1)
        ->and($zip->getFromName('vorhanden.jpg'))->toBe('bytes-present');

    $zip->close();
    File::delete($zipPath);
});
