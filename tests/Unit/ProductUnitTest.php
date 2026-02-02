<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Product;
use Tests\TestCase;

final class ProductUnitTest extends TestCase
{
    public function test_product_has_fillable_attributes(): void
    {
        $product = new Product();
        
        $expected = ['name'];
        
        $this->assertEquals($expected, $product->getFillable());
    }

    public function test_product_can_be_created(): void
    {
        $attributes = Product::factory()->make()->toArray();
        $product = Product::factory()->create($attributes);

        $this->assertInstanceOf(Product::class, $product);
        
        foreach ($attributes as $key => $value) {
            if ($product->isFillable($key)) {
                $this->assertEquals($value, $product->getAttribute($key));
            }
        }
    }

    public function test_product_has_timestamps(): void
    {
        $product = Product::factory()->create();

        $this->assertNotNull($product->created_at);
        $this->assertNotNull($product->updated_at);
    }
}
