<?php

namespace App\Livewire\ApiKeys;

use App\Models\ApiKey;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class ApiKeyIndex extends Component
{
    public string $providerFilter = '';
    public ?string $feedbackMessage = null;

    #[On('api-key-saved')]
    public function handleApiKeySaved(string $message): void
    {
        $this->feedbackMessage = $message;
    }

    public function toggleActive(int $keyId): void
    {
        $apiKey = ApiKey::query()->find($keyId);

        if ($apiKey !== null) {
            $apiKey->update([
                'is_active' => !$apiKey->is_active,
            ]);
            $statusText = $apiKey->is_active ? 'Kích hoạt' : 'Vô hiệu hóa';
            $this->feedbackMessage = "Đã {$statusText} Key [{$apiKey->key_name}].";
        }
    }

    public function deleteKey(int $keyId): void
    {
        $apiKey = ApiKey::query()->find($keyId);

        if ($apiKey !== null) {
            $keyName = $apiKey->key_name;
            $apiKey->delete();
            $this->feedbackMessage = "Đã xóa API Key [{$keyName}] thành công.";
        }
    }

    public function resetUsage(int $keyId): void
    {
        $apiKey = ApiKey::query()->find($keyId);

        if ($apiKey !== null) {
            $apiKey->update([
                'used_today' => 0,
            ]);
            $this->feedbackMessage = "Đã reset hạn mức sử dụng hôm nay cho [{$apiKey->key_name}].";
        }
    }

    public function render(): View
    {
        $keysQuery = ApiKey::query();

        if ($this->providerFilter !== '') {
            $keysQuery->where('provider', $this->providerFilter);
        }

        $apiKeys = $keysQuery->latest()->get();

        return view('livewire.api-keys.api-key-index', [
            'apiKeys' => $apiKeys,
        ]);
    }
}

