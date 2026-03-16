<?php

namespace App\Filament\Resources\ZplLabelTemplateResource\Pages;

use App\Filament\Resources\ZplLabelTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateZplLabelTemplate extends CreateRecord
{
    protected static string $resource = ZplLabelTemplateResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
