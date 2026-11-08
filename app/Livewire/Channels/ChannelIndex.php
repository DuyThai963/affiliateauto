<?php

namespace App\Livewire\Channels;

use App\Models\Channel;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class ChannelIndex extends Component
{
    public string $search = '';
    public string $platformFilter = '';
    public string $typeFilter = '';

    public ?string $feedbackMessage = null;

    #[On('channel-saved')]
    public function handleChannelSaved(string $message): void
    {
        $this->feedbackMessage = $message;
    }

    public function deleteChannel(int $channelId): void
    {
        $channel = Channel::query()->find($channelId);

        if ($channel !== null) {
            $channelName = $channel->channel_name;
            $channel->delete();
            $this->feedbackMessage = "Đã xóa kênh {$channelName} thành công.";
        }
    }

    public function testBrowserProfile(int $channelId): void
    {
        $channel = Channel::query()->find($channelId);

        if ($channel !== null) {
            $this->feedbackMessage = "Kiểm tra Profile [{$channel->browser_profile_path}]: Thư mục profile đã sẵn sàng cho Playwright.";
        }
    }

    public function render(): View
    {
        $channelsQuery = Channel::query();

        if ($this->search !== '') {
            $searchTerm = '%' . $this->search . '%';
            $channelsQuery->where(function ($query) use ($searchTerm) {
                $query->where('channel_name', 'like', $searchTerm)
                    ->orWhere('browser_profile_path', 'like', $searchTerm);
            });
        }

        if ($this->platformFilter !== '') {
            $channelsQuery->where('platform', $this->platformFilter);
        }

        if ($this->typeFilter !== '') {
            $channelsQuery->where('type', $this->typeFilter);
        }

        $channels = $channelsQuery->latest()->get();

        return view('livewire.channels.channel-index', [
            'channels' => $channels,
        ]);
    }
}

