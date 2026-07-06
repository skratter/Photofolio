<?php

namespace App\Actions;

use App\Models\Photo;
use Carbon\Carbon;

class ExtractExifDataAction
{
    /**
     * Extract EXIF data from the original file and persist it to the Photo model.
     * Runs before variant generation since dimensions/orientation may inform resizing.
     */
    public function execute(Photo $photo): void
    {
        $raw = @exif_read_data($photo->originalPath(), 'ANY_TAG', true);

        if ($raw === false) {
            // No EXIF data present (e.g. screenshot, edited/stripped file) - not an error condition.
            $photo->update(['exif_raw' => null]);

            return;
        }

        $ifd0 = $raw['IFD0'] ?? [];
        $exif = $raw['EXIF'] ?? [];
        $gps = $raw['GPS'] ?? [];

        $photo->update([
            'camera_make' => $ifd0['Make'] ?? null,
            'camera_model' => $ifd0['Model'] ?? null,
            'lens' => $exif['UndefinedTag:0xA434'] ?? null, // LensModel
            // BUG FIX: ApertureFNumber lives under the top-level COMPUTED section,
            // not nested inside EXIF. Previously read as $exif['COMPUTED'][...], which
            // is always null since EXIF has no COMPUTED key.
            'aperture' => $this->formatAperture($raw['COMPUTED']['ApertureFNumber'] ?? null),
            'shutter_speed' => $this->formatShutterSpeed($exif['ExposureTime'] ?? null),
            'iso' => isset($exif['ISOSpeedRatings']) ? (int) $exif['ISOSpeedRatings'] : null,
            'focal_length' => $this->formatFocalLength($exif['FocalLength'] ?? null),
            'taken_at' => $this->parseExifDate($exif['DateTimeOriginal'] ?? null),
            'latitude' => $this->parseGpsCoordinate($gps['GPSLatitude'] ?? null, $gps['GPSLatitudeRef'] ?? null),
            'longitude' => $this->parseGpsCoordinate($gps['GPSLongitude'] ?? null, $gps['GPSLongitudeRef'] ?? null),
            'exif_raw' => $raw,
        ]);
    }

    private function formatAperture(?string $value): ?string
    {
        // COMPUTED.ApertureFNumber already arrives pre-formatted as "f/2.0" -
        // just normalize trailing zeros (f/2.0 -> f/2, f/2.8 stays f/2.8).
        if ($value === null) {
            return null;
        }

        if (! str_starts_with($value, 'f/')) {
            return $value;
        }

        $number = substr($value, 2);

        return 'f/'.rtrim(rtrim(number_format((float) $number, 1), '0'), '.');
    }

    private function formatShutterSpeed(?string $fraction): ?string
    {
        if ($fraction === null) {
            return null;
        }

        [$numerator, $denominator] = array_pad(explode('/', $fraction), 2, 1);
        $seconds = (float) $numerator / max((float) $denominator, 1);

        if ($seconds >= 1) {
            return rtrim(rtrim(number_format($seconds, 1), '0'), '.').'s';
        }

        return '1/'.round(1 / $seconds);
    }

    private function formatFocalLength(?string $fraction): ?string
    {
        if ($fraction === null) {
            return null;
        }

        [$numerator, $denominator] = array_pad(explode('/', $fraction), 2, 1);
        $mm = (float) $numerator / max((float) $denominator, 1);

        return round($mm).'mm';
    }

    private function parseExifDate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // EXIF format: "2026:07:04 14:30:00"
        $normalized = preg_replace('/^(\d{4}):(\d{2}):(\d{2})/', '$1-$2-$3', $value);

        return Carbon::parse($normalized)->toDateTimeString();
    }

    /**
     * @param  array<int, string>|null  $coordinate
     */
    private function parseGpsCoordinate(?array $coordinate, ?string $ref): ?float
    {
        if ($coordinate === null || count($coordinate) !== 3) {
            return null;
        }

        $degrees = $this->fractionToFloat($coordinate[0]);
        $minutes = $this->fractionToFloat($coordinate[1]);
        $seconds = $this->fractionToFloat($coordinate[2]);

        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

        if (in_array($ref, ['S', 'W'], true)) {
            $decimal *= -1;
        }

        return round($decimal, 7);
    }

    private function fractionToFloat(string $fraction): float
    {
        [$numerator, $denominator] = array_pad(explode('/', $fraction), 2, 1);

        return (float) $numerator / max((float) $denominator, 1);
    }
}
