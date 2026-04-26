<?php

namespace App\Filament\Resources\JobVacancies\Pages;

use App\Filament\Resources\JobVacancies\JobVacancyResource;
use Filament\Resources\Pages\ViewRecord;

class ViewJobVacancy extends ViewRecord
{
    protected static string $resource = JobVacancyResource::class;
    protected static ?string $title = 'تفاصيل الوظيفة';
    protected static ?string $breadcrumb = 'تفاصيل الوظيفة';
}
