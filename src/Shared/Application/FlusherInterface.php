<?php

declare(strict_types=1);

namespace App\Shared\Application;

interface FlusherInterface
{
    /**
     * Flush pending changes to the database.
     *
     * @return bool True if any changes were persisted, false if nothing changed
     */
    public function flush(): bool;
}
