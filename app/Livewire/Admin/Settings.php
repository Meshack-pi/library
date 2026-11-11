<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\Flashes;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Library rules')]
class Settings extends Component
{
    use Flashes;

    /**
     * Each rule's label, help text and smallest allowed value.
     *
     * @var array<string, array{label: string, help: string, min: int}>
     */
    public const array FIELDS = [
        'loan_days' => ['label' => 'Loan period (days)', 'help' => 'How long a member keeps a book before it is due.', 'min' => 1],
        'fine_per_day' => ['label' => 'Fine per day late (KES)', 'help' => 'Charged for each day a book is returned after its due date.', 'min' => 0],
        'max_books' => ['label' => 'Maximum books per member', 'help' => 'A member at this limit must return a book before borrowing another.', 'min' => 1],
        'max_renewals' => ['label' => 'Maximum renewals per loan', 'help' => 'Set to 0 to switch renewals off.', 'min' => 0],
        'hold_days' => ['label' => 'Reservation hold (days)', 'help' => 'How long a returned copy is kept for the member who reserved it.', 'min' => 1],
    ];

    /** @var array<string, int|string> */
    public array $values = [];

    public function mount(): void
    {
        Gate::authorize('settings.manage');

        $this->values = Setting::allValues();
    }

    public function save(): void
    {
        Gate::authorize('settings.manage');

        $rules = [];
        $attributes = [];

        foreach (self::FIELDS as $key => $field) {
            $rules["values.{$key}"] = ['required', 'integer', 'min:'.$field['min'], 'max:100000'];
            $attributes["values.{$key}"] = strtolower($field['label']);
        }

        $validated = $this->validate($rules, attributes: $attributes);

        foreach ($validated['values'] as $key => $value) {
            Setting::put($key, (int) $value);
        }

        $this->flashSuccess('Library rules saved. They apply to loans and returns from now on.');
    }

    public function render(): View
    {
        return view('livewire.admin.settings', ['fields' => self::FIELDS]);
    }
}
