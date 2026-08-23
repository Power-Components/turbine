<?php

namespace PowerComponents\Turbine\Support;

use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\{Carbon, Str};

final class FilterDateRange
{
    /**
     * @param  string  $type  'date' or 'datetime'
     * @return array{start: string, end: string, formatted: string}
     */
    public static function compute(string $type, string $formatted): array
    {
        [$startRaw, $endRaw] = Str::contains($formatted, ' to ')
            ? explode(' to ', $formatted, 2)
            : [$formatted, $formatted];

        /** @var string $appTimezone */
        $appTimezone = config('app.timezone');
        $isDatetime = $type === 'datetime';
        $hasTime = $isDatetime;

        $makeDate = function (string $value) use ($hasTime, $appTimezone) {
            try {
                $date = Carbon::parse(trim($value), $appTimezone);
            } catch (InvalidFormatException) {
                return now($appTimezone);
            }

            if (! $hasTime) {
                $date->setTime(0, 0, 0);
            }

            return $date->setTimezone($appTimezone);
        };

        $startDate = $makeDate($startRaw);
        $endDate = $makeDate($endRaw);

        if ($isDatetime && $endDate->isStartOfDay()) {
            $endDate->endOfDay();
        } elseif (! $isDatetime) {
            $endDate->endOfDay();
        }

        return [
            'start' => $startDate->toString(),
            'end' => $endDate->toString(),
            'formatted' => $formatted,
        ];
    }
}
