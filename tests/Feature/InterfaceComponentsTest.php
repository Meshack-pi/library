<?php

namespace Tests\Feature;

use Tests\TestCase;

class InterfaceComponentsTest extends TestCase
{
    public function test_the_icon_component_renders_a_decorative_svg(): void
    {
        $this->blade('<x-icon name="book-open" class="size-8" />')
            ->assertSee('<svg', false)
            ->assertSee('aria-hidden="true"', false)
            ->assertSee('class="shrink-0 size-8"', false)
            ->assertDontSee('size-5', false)
            ->assertSee('<path', false);
    }

    public function test_an_unknown_icon_renders_an_empty_svg_instead_of_failing(): void
    {
        $this->blade('<x-icon name="does-not-exist" />')
            ->assertSee('<svg', false)
            ->assertDontSee('<path', false);
    }

    public function test_the_stat_component_shows_its_label_value_and_colour(): void
    {
        $this->blade('<x-stat label="Books on loan" icon="book-open" color="rose"><x-slot:value class="text-rose-700">42</x-slot:value></x-stat>')
            ->assertSeeText('Books on loan')
            ->assertSeeText('42')
            ->assertSee('bg-rose-100 text-rose-700', false)
            ->assertSee('stat-value text-rose-700', false);
    }

    public function test_the_stat_component_falls_back_to_the_ink_tile_for_an_unknown_colour(): void
    {
        $this->blade('<x-stat label="Titles" icon="books" color="neon"><x-slot:value>7</x-slot:value></x-stat>')
            ->assertSee('bg-ink-900 text-copper-400', false);
    }

    public function test_the_icon_component_uses_the_default_size_when_none_is_given(): void
    {
        $this->blade('<x-icon name="home" />')
            ->assertSee('class="shrink-0 size-5"', false);
    }

    public function test_a_success_flash_renders_as_a_dismissible_toast(): void
    {
        $this->blade('<x-flash notice="Book issued." :version="3" />')
            ->assertSee('toast toast-success', false)
            ->assertSee('role="status"', false)
            ->assertSee('wire:key="toast-3"', false)
            ->assertSee('setTimeout(() => show = false, 5000)', false)
            ->assertSeeText('Book issued.')
            ->assertSeeText('Dismiss notification');
    }

    public function test_an_error_flash_renders_as_an_alert_toast(): void
    {
        $this->blade('<x-flash failure="Member has unpaid fines." />')
            ->assertSee('toast toast-error', false)
            ->assertSee('role="alert"', false)
            ->assertSee('aria-live="assertive"', false)
            ->assertSeeText('Member has unpaid fines.');
    }

    public function test_the_flash_component_renders_nothing_without_messages(): void
    {
        $this->blade('<x-flash />')
            ->assertDontSee('role="status"', false)
            ->assertDontSee('role="alert"', false);
    }
}
