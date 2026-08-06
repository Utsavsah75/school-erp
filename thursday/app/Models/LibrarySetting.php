<?php

namespace App\Models;

use App\Core\Model;

class LibrarySetting extends Model
{
    protected string $table = 'library_settings';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'fine_per_day', 'loan_period_days', 'max_books_student',
        'max_books_teacher', 'max_renewals', 'updated_by',
    ];

    public function current(): array
    {
        $row = $this->find(1);
        return $row ?: [
            'fine_per_day' => 5.00,
            'loan_period_days' => 14,
            'max_books_student' => 3,
            'max_books_teacher' => 5,
            'max_renewals' => 1,
        ];
    }
}
