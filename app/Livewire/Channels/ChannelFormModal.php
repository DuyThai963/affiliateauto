<?php

namespace App\Livewire\Channels;

use App\Models\Channel;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

class ChannelFormModal extends Component
{
    public bool $isOpen = false;
    public ?int $channelId = null;

    public string $platform = 'tiktok';
    public string $channel_name = '';
    public string $type = 'growth';
    public string $browser_profile_path = '';
    public int $daily_post_limit = 5;
    public int $min_interval_minutes = 200;
    public string $status = 'active';

    /**
     * Validation rules.
     *
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'platform' => ['required', 'in:tiktok,shopee'],
            'channel_name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:growth,affiliate'],
            'browser_profile_path' => ['required', 'string', 'max:255'],
            'daily_post_limit' => ['required', 'integer', 'min:1', 'max:50'],
            'min_interval_minutes' => ['required', 'integer', 'min:30', 'max:1440'],
            'status' => ['required', 'in:active,paused,needs_relogin'],
        ];
    }

    #[On('open-channel-modal')]
    public function openModal(?int $id = null): void
    {
        $this->resetValidation();
        $this->channelId = $id;

        if ($this->channelId !== null) {
            $channel = Channel::query()->findOrFail($this->channelId);
            $this->platform = $channel->platform;
            $this->channel_name = $channel->channel_name;
            $this->type = $channel->type;
            $this->browser_profile_path = $channel->browser_profile_path;
            $this->daily_post_limit = $channel->daily_post_limit;
            $this->min_interval_minutes = $channel->min_interval_minutes;
            $this->status = $channel->status;
        } else {
            $this->platform = 'tiktok';
            $this->channel_name = '';
            $this->type = 'growth';
            $this->browser_profile_path = 'storage/browser_profiles/tiktok_channel_' . Str::random(6);
            $this->daily_post_limit = 5;
            $this->min_interval_minutes = 200;
            $this->status = 'active';
        }

        $this->isOpen = true;
    }

    public function updatedPlatform(string $value): void
    {
        if ($this->channelId === null) {
            $this->browser_profile_path = 'storage/browser_profiles/' . $value . '_channel_' . Str::random(6);
        }
    }

    public function closeModal(): void
    {
        $this->isOpen = false;
    }

    public function save(): void
    {
        $validatedData = $this->validate();

        if ($this->channelId !== null) {
            $channel = Channel::query()->findOrFail($this->channelId);
            $channel->update($validatedData);
            $actionMessage = "Đã cập nhật kênh [{$channel->channel_name}] thành công!";
        } else {
            $channel = Channel::query()->create($validatedData);
            $actionMessage = "Đã thêm kênh mới [{$channel->channel_name}] thành công!";
        }

        $this->isOpen = false;
        $this->dispatch('channel-saved', message: $actionMessage);
    }

    public function render(): View
    {
        return view('livewire.channels.channel-form-modal');
    }
}

