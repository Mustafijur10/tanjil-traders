<?php

namespace App\Models\Concerns;

use DateTimeInterface;

/**
 * The admin sends times as ISO strings in UTC ("…T16:00:00Z" for 10 PM in Dhaka).
 * Laravel would save the UTC clock time ("16:00") and later read it as Dhaka time, six hours early.
 * This turns every date into the shop's timezone before it is saved.
 */
trait StoresShopTime
{
    public function fromDateTime($value)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $date = $this->asDateTime($value);

        return $date->setTimezone(config('app.timezone'))->format($this->getDateFormat());
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d\TH:i:s.uP'); // with offset, so browsers read it as the right moment
    }
}
