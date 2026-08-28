<?php

namespace App\Actions\Categories;

use App\Models\ReportCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Create or update a report category, including optional image handling.
 *
 * Extracted from ReportCategoryController::store/update so the logic is shared
 * and testable, independent of the HTTP/Livewire layer.
 */
class SaveCategory
{
    public function handle(ReportCategory $category, string $desc, ?UploadedFile $image = null): ReportCategory
    {
        $category->desc = $desc;

        if ($image) {
            $path = $image->store('public/images/categories');
            $category->image_path = (string) Str::of($path)->replace('public', 'storage');
        }

        $category->save();

        return $category;
    }
}
