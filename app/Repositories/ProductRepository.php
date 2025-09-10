<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;

class ProductRepository extends BaseRepository
{
    public function model()
    {
        return Product::class;
    }

    /**
     * Get all active products with relationships
     */
    public function index()
    {
        return $this->model
            ->where('is_active', true)
            ->with(['brand', 'tags', 'colors.sizes'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all products (including inactive) for admin
     */
    public function getAll()
    {
        return $this->model
            ->with(['brand', 'tags', 'colors.sizes'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Create a new product
     */
    public function store(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * Get product by ID with relationships
     */
    public function show($id)
    {
        return $this->model
            ->where('id', $id)
            ->with(['brand', 'tags', 'colors.sizes'])
            ->first();
    }

    /**
     * Get product by slug
     */
    public function showBySlug($slug)
    {
        return $this->model
            ->with(['brand', 'tags', 'colors.sizes'])
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Update product by ID
     */
    public function update(array $data, int $id)
    {
        return $this->model->where('id', $id)->update($data);
    }

    /**
     * Delete product by ID
     */
    public function deleteProduct($id)
    {
        return $this->model->where('id', $id)->delete();
    }

    /**
     * Search products by keyword
     */
    public function search(string $keyword): Collection
    {
        return $this->model
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            })
            ->where('is_active', true)
            ->with(['brand', 'tags', 'colors.sizes'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get products by brand
     */
    public function getByBrand($brandId): Collection
    {
        return $this->model
            ->where('brand_id', $brandId)
            ->where('is_active', true)
            ->with(['brand', 'tags', 'colors.sizes'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get products by gender
     */
    public function getByGender($gender): Collection
    {
        return $this->model
            ->where('gender', $gender)
            ->where('is_active', true)
            ->with(['brand', 'tags', 'colors.sizes'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get products with filters
     */
    public function getWithFilters(array $filters = []): Collection
    {
        $query = $this->model->where('is_active', true);

        if (isset($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        if (isset($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        if (isset($filters['product_type'])) {
            $query->where('product_type', $filters['product_type']);
        }

        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        return $query->with(['brand', 'tags', 'colors.sizes'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get featured products
     */
    public function getFeatured($limit = 10)
    {
        return $this->model
            ->where('is_active', true)
            ->with(['brand', 'tags', 'colors.sizes'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Toggle product active status
     */
    public function toggleStatus($id)
    {
        $product = $this->model->find($id);

        if ($product) {
            $product->update(['is_active' => !$product->is_active]);
            return $product;
        }

        return null;
    }
}
