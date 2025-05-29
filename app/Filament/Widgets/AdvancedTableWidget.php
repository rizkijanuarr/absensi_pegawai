<?php

namespace App\Filament\Widgets;

use EightyNine\FilamentAdvancedWidget\AdvancedWidget;
use EightyNine\FilamentAdvancedWidget\Concerns;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

class AdvancedTableWidget extends AdvancedWidget implements Actions\Contracts\HasActions, Forms\Contracts\HasForms, Infolists\Contracts\HasInfolists, Tables\Contracts\HasTable
{
    use Concerns\CanBeCustomised,
        Concerns\HasSectionContent;
    use Actions\Concerns\InteractsWithActions;
    use Forms\Concerns\InteractsWithForms;
    use Infolists\Concerns\InteractsWithInfolists;
    use Tables\Concerns\InteractsWithTable {
        makeTable as makeBaseTable;
    }

    /**
     * @var view-string
     */
    protected static string $view = 'advanced-widgets::advanced-table-widget';

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected static ?string $heading = null;

    protected function paginateTableQuery(Builder $query): Paginator | CursorPaginator
    {
        return $query->simplePaginate(($this->getTableRecordsPerPage() === 'all') ? $query->count() : $this->getTableRecordsPerPage());
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected function getTableHeading(): string | Htmlable | null
    {
        return static::$heading;
    }

    protected function table(Table $table): Table
    {
        return $table
            ->query(User::query()->latest()->limit(3))
            ->heading('🟢 Pegawai Aktif Terakhir')
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Foto')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email'),
            ])
            ->paginated(false);
    }
} 