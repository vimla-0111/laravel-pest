<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use Tests\TestCase;

final class ProductTest extends TestCase
{
    public function test_can_create_product(): void
    {
        $data = Product::factory()->make()->toArray();

        $response = $this->post(route('products.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('products', $data);
    }

    public function test_can_view_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->get(route('products.show', $product));

        $response->assertOk();
        $response->assertViewHas('product', $product);
    }

    public function test_can_update_product(): void
    {
        $product = Product::factory()->create();
        $data = Product::factory()->make()->toArray();

        $response = $this->put(route('products.update', $product), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('products', array_merge(['id' => $product->id], $data));
    }

    public function test_can_delete_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->delete(route('products.destroy', $product));

        $response->assertRedirect();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
