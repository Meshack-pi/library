<?php

namespace App\Livewire\Concerns;

/**
 * One success or error message shown as a toast after an action.
 */
trait Flashes
{
    public ?string $notice = null;

    public ?string $failure = null;

    /**
     * Bumped on every flash so a repeated message pops up as a fresh toast.
     */
    public int $flashVersion = 0;

    protected function flashSuccess(string $message): void
    {
        $this->notice = $message;
        $this->failure = null;
        $this->flashVersion++;
    }

    protected function flashError(string $message): void
    {
        $this->failure = $message;
        $this->notice = null;
        $this->flashVersion++;
    }
}
