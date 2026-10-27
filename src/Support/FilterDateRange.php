<?php

namespace PowerComponents\Turbine\Support;

use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\{Carbon, Str};

final class FilterDateRange
{
    /**
     * @param  string  $type  'date' or 'datetime'
     * @param  string|null  $timezone  timezone the picked values are in; defaults to app.timezone
     * @return array{start: string, end: string, formatted: string}
     */
    public static function compute(string $type, string $formatted, ?string $timezone = null): array
    {
        [$startRaw, $endRaw] = Str::contains($formatted, ' to ')
            ? explode(' to ', $formatted, 2)
            : [$formatted, $formatted];

        /** @var string $appTimezone */
        $appTimezone = config('app.timezone');
        $inputTimezone = filled($timezone) ? $timezone : $appTimezone;
        $isDatetime = $type === 'datetime';
        $hasTime = $isDatetime;

        $makeDate = function (string $value) use ($hasTime, $inputTimezone) {
            try {
                $date = Carbon::parse(trim($value), $inputTimezone);
            } catch (InvalidFormatException) {
                return now($inputTimezone);
            }

            if (! $hasTime) {
                $date->setTime(0, 0, 0);
            }

            return $date;
        };

        $startDate = $makeDate($startRaw);
        $endDate = $makeDate($endRaw);

        if ($isDatetime && $endDate->isStartOfDay()) {
            $endDate->endOfDay();
        } elseif (! $isDatetime) {
            $endDate->endOfDay();
        }

        return [
            'start' => $startDate->setTimezone($appTimezone)->toString(),
            'end' => $endDate->setTimezone($appTimezone)->toString(),
            'formatted' => $formatted,
        ];
    }
}
