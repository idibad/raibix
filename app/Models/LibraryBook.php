<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LibraryBook extends Model
{
    protected $table = 'library_books';

    protected $guarded = [];

    public function bookIssues(): HasMany
    {
        return $this->hasMany(BookIssue::class, 'book_id');
    }
}
