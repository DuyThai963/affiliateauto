<?php

namespace App\Livewire\Products;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ProductFormModal extends Component
{
    public bool $isOpen = false;
    public ?int $productId = null;

    public string $platform = 'tiktok';
    public string $product_name = '';
    public ?string $external_product_id = null;
    public ?string $affiliate_url = null;
    public ?string $notes = null;
    public bool $is_active = true;

    /**
     * Validation rules.
     *
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'platform' => ['required', 'in:tiktok,shopee'],
            'product_name' => ['required', 'string', 'max:255'],
            'external_product_id' => ['nullable', 'string', 'max:100'],
            'affiliate_url' => ['nullable', 'url', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    #[On('open-product-modal')]
    public function openModal(?int $id = null): void
    {
        $this->resetValidation();
        $this->productId = $id;

        if ($this->productId !== null) {
            $product = Product::query()->findOrFail($this->productId);
            $this->platform = $product->platform;
            $this->product_name = $product->product_name;
            $this->external_product_id = $product->external_product_id;
            $this->affiliate_url = $product->affiliate_url;
            $this->notes = $product->notes;
            $this->is_active = (bool) $product->is_active;
        } else {
            $this->platform = 'tiktok';
            $this->product_name = '';
            $this->external_product_id = null;
            $this->affiliate_url = null;
            $this->notes = null;
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

        if ($this->productId !== null) {
            $product = Product::query()->findOrFail($this->productId);
            $product->update($validatedData);
            $actionMessage = "Đã cập nhật sản phẩm [{$product->product_name}] thành công!";
        } else {
            $product = Product::query()->create($validatedData);
            $actionMessage = "Đã thêm sản phẩm [{$product->product_name}] vào giỏ hàng!";
        }

        $this->isOpen = false;
        $this->dispatch('product-saved', message: $actionMessage);
    }

    public function render(): View
    {
        return view('livewire.products.product-form-modal');
    }
}

