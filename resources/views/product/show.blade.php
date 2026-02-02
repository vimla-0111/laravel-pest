@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h1>Product Details</h1>
                    <div class="float-right">
                        <a href="{{ route('products.edit', $product) }}" class="btn btn-warning">Edit</a>
                        <a href="{{ route('products.index') }}" class="btn btn-secondary">Back to List</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>ID:</strong> {{ $product->id }}
                        </div>
                        <div class="col-md-6">
                            <strong>Name:</strong> {{ $product->name }}
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <strong>Created:</strong> {{ $product->created_at->format('M d, Y H:i') }}
                        </div>
                        <div class="col-md-6">
                            <strong>Updated:</strong> {{ $product->updated_at->format('M d, Y H:i') }}
                        </div>
                    </div>

                    <div class="mt-4">
                        <form method="POST" action="{{ route('products.destroy', $product) }}" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this product?')">Delete Product</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
