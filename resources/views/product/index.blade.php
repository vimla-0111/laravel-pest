@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h1>Products</h1>
                    <a href="{{ route('products.create') }}" class="btn btn-primary float-right">Create New Product</a>
                </div>
                <div class="card-body">
                    @if($products->isEmpty())
                        <p>No products found.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($products as $product)
                                        <tr>
                                            <td>{{ $product->id }}</td>
                                            <td>
                                                <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-info">View</a>
                                                <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-warning">Edit</a>
                                                <form method="POST" action="{{ route('products.destroy', $product) }}" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $products->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
