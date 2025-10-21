<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource\RelationManagers;
use Spatie\Permission\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Spatie\Permission\Models\Permission;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    
    protected static ?string $navigationLabel = 'Roles';
    
    protected static ?string $navigationGroup = 'User Management';
    
    protected static ?int $navigationSort = 30;

    // Use permission-based access
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view_any_role') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('view_role') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create_role') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('update_role') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete_role') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete_any_role') ?? false;
    }

    public static function form(Form $form): Form
    {
        // Group permissions by module/resource
        $permissions = Permission::all()->groupBy(function ($permission) {
            $name = $permission->name;
            
            // Extract resource name from permission
            if (str_contains($name, '_user')) {
                return 'User Management';
            } elseif (str_contains($name, '_role')) {
                return 'Role Management';
            } elseif (str_contains($name, '_authentication')) {
                return 'Authentication Logs';
            } elseif (str_contains($name, '_activity')) {
                return 'Activity Logs';
            } elseif (str_contains($name, 'page_')) {
                return 'Pages';
            } elseif (str_contains($name, 'widget_')) {
                return 'Widgets';
            } else {
                return 'General';
            }
        });

        $sections = [
            Section::make('Role Information')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->label('Role Name')
                        ->placeholder('Enter role name (e.g., Admin, Editor, Viewer)')
                        ->maxLength(255),
                ])
                ->icon('heroicon-o-identification')
                ->columnSpanFull(),
                
            Grid::make(2)
                ->schema([])
                ->columnSpanFull(),
        ];
        
        $gridSections = [];
        
        foreach ($permissions as $module => $modulePermissions) {
            $moduleIcon = match($module) {
                'User Management' => 'heroicon-o-users',
                'Role Management' => 'heroicon-o-shield-check',
                'Authentication Logs' => 'heroicon-o-key',
                'Activity Logs' => 'heroicon-o-clipboard-document-list',
                'Pages' => 'heroicon-o-document',
                'Widgets' => 'heroicon-o-squares-2x2',
                default => 'heroicon-o-cog-6-tooth'
            };
            
            $gridSections[] = Section::make($module)
                ->schema([
                    CheckboxList::make('permissions')
                        ->relationship('permissions', 'name')
                        ->hiddenLabel()
                        ->options($modulePermissions->pluck('name', 'id')->toArray())
                        ->columns([
                            'sm' => 1,
                            'md' => 2, 
                            'lg' => 2,
                            'xl' => 3,
                        ])
                        ->gridDirection('row')
                        ->descriptions($modulePermissions->mapWithKeys(function ($permission) {
                            return [$permission->id => self::formatPermissionDescription($permission->name)];
                        })->toArray())
                        ->bulkToggleable(),
                ])
                ->icon($moduleIcon)
                ->collapsible()
                ->collapsed(false)
                ->extraAttributes([
                    'class' => 'border-l-4 border-l-primary-500'
                ]);
        }

        // Add grid sections to main sections in pairs
        for ($i = 0; $i < count($gridSections); $i += 2) {
            if (isset($gridSections[$i + 1])) {
                // Add 2 sections in one row
                $sections[] = Grid::make(2)
                    ->schema([
                        $gridSections[$i],
                        $gridSections[$i + 1],
                    ])
                    ->columnSpanFull();
            } else {
                // Add single section if odd number
                $sections[] = Grid::make(1)
                    ->schema([
                        $gridSections[$i],
                    ])
                    ->columnSpanFull();
            }
        }

        return $form->schema($sections);
    }
    
    private static function formatPermissionDescription(string $permission): string
    {
        // Convert permission name to readable description
        $parts = explode('_', $permission);
        $action = $parts[0] ?? '';
        $resource = implode(' ', array_slice($parts, 1));
        
        $actionLabels = [
            'view' => 'View',
            'create' => 'Create',
            'update' => 'Update', 
            'delete' => 'Delete',
            'restore' => 'Restore',
            'replicate' => 'Replicate',
            'reorder' => 'Reorder',
            'force' => 'Force Delete',
            'page' => 'Access Page',
            'widget' => 'View Widget',
        ];
        
        $actionLabel = $actionLabels[$action] ?? ucfirst($action);
        $resourceLabel = ucwords(str_replace(['::', '_'], [' ', ' '], $resource));
        
        return $actionLabel . ' ' . $resourceLabel;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Role Name')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('Permissions Count')
                    ->sortable(),
                    
                TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Users Count')
                    ->sortable(),
                    
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
