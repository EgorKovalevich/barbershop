<?php

namespace App\Filament\Resources\EventResource\Pages;

use Filament\Pages\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Buildix\Timex\Traits\TimexTrait;
use App\Filament\Resources\EventResource;

class ListEvents extends ListRecords
{
    use TimexTrait;
    protected static string $resource = EventResource::class;

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $user = \Auth::user();
        $query = parent::getTableQuery();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        return match ($user->role) {
            'admin' => $query,
            'barber' => $query->where('barber_id', $user->id),
            default => $query->where('organizer_id', $user->id),
        };
    }
}
