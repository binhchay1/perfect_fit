<?php

namespace App\Repositories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;

class BrandRepository extends BaseRepository
{
    public function model()
    {
        return Brand::class;
    }

    /**
     * Get all active brands with products count
     */
    public function index()
    {
        return $this->model
            ->active()
            ->withCount(['products', 'activeProducts'])
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Get all brands (including inactive) for admin
     */
    public function getAll()
    {
        return $this->model
            ->withCount(['products', 'activeProducts'])
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Create a new brand
     */
    public function store(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * Get brand by ID with products
     */
    public function show($id)
    {
        return $this->model
            ->withCount(['products', 'activeProducts'])
            ->find($id);
    }

    /**
     * Get brand by slug
     */
    public function showBySlug($slug)
    {
        return $this->model
            ->where('slug', $slug)
            ->withCount(['products', 'activeProducts'])
            ->first();
    }

    /**
     * Update brand by ID
     */
    public function update(array $data, int $id)
    {
        return $this->model->where('id', $id)->update($data);
    }

    /**
     * Delete brand by ID
     */
    public function deleteBrand($id)
    {
        return $this->model->where('id', $id)->delete();
    }

    /**
     * Search brands by keyword
     */
    public function search(string $keyword): Collection
    {
        return $this->model
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%")
                    ->orWhere('country', 'like', "%{$keyword}%");
            })
            ->active()
            ->withCount(['products', 'activeProducts'])
            ->orderBy('name', 'asc')
            ->get();
    }


    public function getWithProducts($limit = null)
    {
        $query = $this->model
            ->active()
            ->with(['activeProducts' => function ($q) {
                $q->select('id', 'brand_id', 'name', 'slug', 'price', 'images')
                    ->orderBy('created_at', 'desc')
                    ->limit(5);
            }])
            ->withCount(['products', 'activeProducts'])
            ->orderBy('name', 'asc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function toggleStatus($id)
    {
        $brand = $this->model->find($id);

        if ($brand) {
            $brand->update(['is_active' => !$brand->is_active]);
            return $brand;
        }

        return null;
    }
}
