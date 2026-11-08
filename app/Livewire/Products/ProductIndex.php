<?php

namespace App\Livewire\Products;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class ProductIndex extends Component
{
    public string $search = '';
    public string $platformFilter = '';
    public ?string $feedbackMessage = null;

    #[On('product-saved')]
    public function handleProductSaved(string $message): void
    {
        $this->feedbackMessage = $message;
    }

    public function toggleActive(int $productId): void
    {
        $product = Product::query()->find($productId);

        if ($product !== null) {
            $product->update([
                'is_active' => !$product->is_active,
            ]);
            $statusText = $product->is_active ? 'Kích hoạt' : 'Tạm dừng';
            $this->feedbackMessage = "Đã {$statusText} sản phẩm [{$product->product_name}].";
        }
    }

    public function deleteProduct(int $productId): void
    {
        $product = Product::query()->find($productId);

        if ($product !== null) {
            $productName = $product->product_name;
            $product->delete();
            $this->feedbackMessage = "Đã xóa sản phẩm [{$productName}] thành công.";
        }
    }

    public function render(): View
    {
        $productsQuery = Product::query();

        if ($this->search !== '') {
            $searchTerm = '%' . $this->search . '%';
            $productsQuery->where(function ($query) use ($searchTerm) {
                $query->where('product_name', 'like', $searchTerm)
                    ->orWhere('external_product_id', 'like', $searchTerm)
                    ->orWhere('notes', 'like', $searchTerm);
            });
        }

        if ($this->platformFilter !== '') {
            $productsQuery->where('platform', $this->platformFilter);
        }

        $products = $productsQuery->latest()->get();

        return view('livewire.products.product-index', [
            'products' => $products,
        ]);
    }
}

