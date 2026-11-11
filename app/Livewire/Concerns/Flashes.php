<?php

namespace App\Livewire\Concerns;

/**
 * One success or error message shown at the top of a page after an action.
 */
trait Flashes
{
    public ?string $notice = null;

    public ?string $failure = null;

    protected function flashSuccess(string $message): void
    {
        $this->notice = $message;
        $this->failure = null;
    }

    protected function flashError(string $message): void
    {
        $this->failure = $message;
        $this->notice = null;
    }
}
