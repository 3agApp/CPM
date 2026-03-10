<?php

namespace App\Filament\Resources\Brands\Pages;

use App\Filament\Resources\Brands\BrandResource;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateBrand extends CreateRecord
{
    protected static string $resource = BrandResource::class;

    protected static bool $canCreateAnother = false;

    protected function getFormActions(): array
    {
        return [
            $this->getCancelFormAction(),
            $this->getCreateFormAction(),
        ];
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Create Brand');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Cancel');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['organization_id'] = Filament::getTenant()?->getKey();

        return $data;
    }
}
