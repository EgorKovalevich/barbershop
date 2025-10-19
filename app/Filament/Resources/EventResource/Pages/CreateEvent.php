<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use Buildix\Timex\Traits\TimexTrait;
use Filament\Resources\Form;
use Filament\Resources\Pages\CreateRecord;

class CreateEvent extends CreateRecord
{
    use TimexTrait;
    protected static string $resource = EventResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return EventResource::prepareEventPayload($data);
    }
}
