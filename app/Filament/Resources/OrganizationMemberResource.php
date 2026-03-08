<?php

namespace App\Filament\Resources;

use App\Enums\Role;
use App\Filament\Resources\OrganizationMemberResource\Pages;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrganizationMemberResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Members';

    protected static ?string $modelLabel = 'Member';

    protected static ?string $slug = 'members';

    protected static bool $isScopedToTenant = false;

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn () => User::query()
                ->select('users.*')
                ->selectRaw('organization_user.role as membership_role')
                ->selectRaw('organization_user.created_at as membership_joined_at')
                ->join('organization_user', fn ($join) => $join
                    ->on('users.id', '=', 'organization_user.user_id')
                    ->where('organization_user.organization_id', Filament::getTenant()->id)))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('membership_role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): ?string => $state ? Role::from($state)->getLabel() : null),
                TextColumn::make('membership_joined_at')
                    ->label('Joined')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                \Filament\Actions\Action::make('changeRole')
                    ->label('Change Role')
                    ->icon('heroicon-o-shield-check')
                    ->form([
                        \Filament\Forms\Components\Select::make('role')
                            ->options(fn () => collect(Role::cases())
                                ->mapWithKeys(fn (Role $role) => [$role->value => $role->getLabel()]))
                            ->required(),
                    ])
                    ->action(function (User $record, array $data): void {
                        $tenant = Filament::getTenant();

                        if ($data['role'] !== Role::Owner->value) {
                            $ownerCount = $tenant->members()
                                ->wherePivot('role', Role::Owner->value)
                                ->count();

                            $currentRole = $record->getRoleForOrganization($tenant);

                            if ($currentRole === Role::Owner && $ownerCount <= 1) {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('Cannot change role')
                                    ->body('Organization must have at least one owner.')
                                    ->send();

                                return;
                            }
                        }

                        $tenant->members()->updateExistingPivot($record->id, [
                            'role' => $data['role'],
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Role updated')
                            ->send();
                    })
                    ->visible(fn (): bool => Filament::auth()->user()
                        ->getRoleForOrganization(Filament::getTenant())
                        ?->canManageMembers() ?? false),

                \Filament\Actions\Action::make('removeMember')
                    ->label('Remove')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $tenant = Filament::getTenant();
                        $role = $record->getRoleForOrganization($tenant);

                        if ($role === Role::Owner) {
                            $ownerCount = $tenant->members()
                                ->wherePivot('role', Role::Owner->value)
                                ->count();

                            if ($ownerCount <= 1) {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('Cannot remove member')
                                    ->body('Cannot remove the last owner of the organization.')
                                    ->send();

                                return;
                            }
                        }

                        $tenant->members()->detach($record->id);

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Member removed')
                            ->send();
                    })
                    ->hidden(fn (User $record): bool => $record->id === Filament::auth()->id())
                    ->visible(fn (): bool => Filament::auth()->user()
                        ->getRoleForOrganization(Filament::getTenant())
                        ?->canManageMembers() ?? false),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizationMembers::route('/'),
        ];
    }
}
