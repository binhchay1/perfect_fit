<?php

namespace App\Enums;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

final class Utility
{

    public function saveImageUser($input)
    {
        if ($input) {
            $status = Storage::disk('public-image-user')->put($input['profile_photo_path']->getClientOriginalName(), $input['profile_photo_path']->get());
            return $status;
        }
    }
    
    public function saveLogoBrand($input)
    {
        if ($input) {
            $status = Storage::disk('public-logo-brand')->put($input['logo']->getClientOriginalName(), $input['logo']->get());
            return $status;
        }
    }

    public function paginate($items, $perPage = 15, $path = null, $pageName = 'page', $page = null, $options = [])
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        $options = ['path' => $path];

        $paginatedItems = $items->forPage($page, $perPage)->values()->toArray();

        return new LengthAwarePaginator($paginatedItems, $items->count(), $perPage, $page, $options);
    }

  
}
