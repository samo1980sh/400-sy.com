<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Models\Permission;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::components());
    }

    public static function components(): array
    {
        return [
            Grid::make()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('اسم الدور بالعربية')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->label('المعرف')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('description')
                        ->label('الوصف')
                        ->columnSpanFull()
                        ->maxLength(1000),
                    Select::make('is_active')
                        ->label('الحالة')
                        ->options([
                            1 => 'فعال',
                            0 => 'غير فعال',
                        ])
                        ->default(1),
                    Select::make('permissions')
                        ->label('الصلاحيات')
                        ->options(fn (): array => Permission::query()
                            ->orderBy('group')
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn (Permission $permission): array => [
                                $permission->id => trim(($permission->group ? $permission->group . ' - ' : '') . $permission->name),
                            ])
                            ->all())
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->columnSpanFull(),
                ]),
        ];
    }
}
