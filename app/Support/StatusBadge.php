<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Produces status badge markup for views/components.
 *
 * Replaces the previous pattern of building badge HTML strings inside
 * controllers (e.g. ReportCategoryController::show) and returning them as JSON.
 */
class StatusBadge
{
    /**
     * Active / inactive badge used by the reference-CRUD screens.
     */
    public static function active(bool $isActive): HtmlString
    {
        $class = $isActive ? 'bg-success' : 'bg-danger';
        $label = $isActive ? 'Aktif' : 'Tidak Aktif';

        return new HtmlString('<span class="badge '.$class.'">'.$label.'</span>');
    }

    /**
     * The label text (no markup), useful in tests and non-HTML contexts.
     */
    public static function activeLabel(bool $isActive): string
    {
        return $isActive ? 'Aktif' : 'Tidak Aktif';
    }
}
