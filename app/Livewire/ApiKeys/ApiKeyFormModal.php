<?php

namespace App\Livewire\ApiKeys;

use App\Models\ApiKey;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ApiKeyFormModal extends Component
{
    public bool $isOpen = false;
    public ?int $keyId = null;

    public string $provider = 'gemini';
    public string $key_name = '';
    public string $api_key = '';
    public int $daily_limit = 1500;
    public int $cooldown_seconds = 4;
    public bool $is_active = true;

    /**
     * Validation rules.
     *
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'provider' => ['required', 'in:gemini,claude,openai'],
            'key_name' => ['required', 'string', 'max:100'],
            'api_key' => ['required', 'string'],
            'daily_limit' => ['required', 'integer', 'min:1', 'max:100000'],
            'cooldown_seconds' => ['required', 'integer', 'min:0', 'max:60'],
            'is_active' => ['boolean'],
        ];
    }

    #[On('open-api-key-modal')]
    public function openModal(?int $id = null): void
    {
        $this->resetValidation();
        $this->keyId = $id;

        if ($this->keyId !== null) {
            $key = ApiKey::query()->findOrFail($this->keyId);
            $this->provider = $key->provider;
            $this->key_name = $key->key_name;
            $this->api_key = $key->api_key;
            $this->daily_limit = $key->daily_limit;
            $this->cooldown_seconds = $key->cooldown_seconds;
            $this->is_active = (bool) $key->is_active;
        } else {
            $this->provider = 'gemini';
            $this->key_name = '';
            $this->api_key = '';
            $this->daily_limit = 1500;
            $this->cooldown_seconds = 4;
            $this->is_active = true;
        }

        $this->isOpen = true;
    }

    public function closeModal(): void
    {
        $this->isOpen = false;
    }

    public function save(): void
    {
        $validatedData = $this->validate();

        if ($this->keyId !== null) {
            $key = ApiKey::query()->findOrFail($this->keyId);
            $key->update($validatedData);
            $actionMessage = "Đã cập nhật API Key [{$key->key_name}]!";
        } else {
            $key = ApiKey::query()->create($validatedData);
            $actionMessage = "Đã thêm mới API Key [{$key->key_name}] thành công!";
        }

        $this->isOpen = false;
        $this->dispatch('api-key-saved', message: $actionMessage);
    }

    public function render(): View
    {
        return view('livewire.api-keys.api-key-form-modal');
    }
}

