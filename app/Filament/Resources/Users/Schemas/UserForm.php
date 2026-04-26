<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Role;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class UserForm
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
                        ->label('الاسم')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label('البريد الإلكتروني')
                        ->email()
                        ->required()
                        ->maxLength(255),
                    TextInput::make('phone')
                        ->label('الهاتف')
                        ->tel()
                        ->nullable(),
                    TextInput::make('password')
                        ->label('كلمة المرور')
                        ->password()
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(fn ($state): bool => filled($state))
                        ->minLength(8),
                    FileUpload::make('avatar')
                        ->label('الصورة')
                        ->disk('public')
                        ->directory('users/avatars')
                        ->visibility('public')
                        ->deleteUploadedFileUsing(fn (string $file): bool => Storage::disk('public')->delete($file))
                        ->image()
                        ->imageEditor(),
                    Select::make('status')
                        ->label('الحالة')
                        ->options([
                            'active' => 'فعال',
                            'inactive' => 'غير فعال',
                        ])
                        ->default('active')
                        ->required(),
                    Select::make('user_type')
                        ->label('نوع المستخدم')
                        ->options([
                            'staff' => 'موظف',
                            'customer' => 'عميل',
                        ])
                        ->default('staff')
                        ->required(),
                    Select::make('roles')
                        ->label('الأدوار')
                        ->options(fn (): array => Role::query()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->columnSpanFull(),
                ]),
        ];
    }
}
