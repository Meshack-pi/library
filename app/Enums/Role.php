<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Librarian = 'librarian';
    case Member = 'member';

    /**
     * The route name each role lands on after signing in.
     */
    public function homeRoute(): string
    {
        return match ($this) {
            self::Admin => 'admin.reports',
            self::Librarian => 'librarian.dashboard',
            self::Member => 'member.dashboard',
        };
    }

    /**
     * The permissions granted to each role.
     *
     * @return list<string>
     */
    public function permissions(): array
    {
        $member = [
            'catalogue.view',
            'reservations.create',
            'loans.renew-own',
            'fines.view-own',
        ];

        $librarian = [
            ...$member,
            'books.manage',
            'loans.issue',
            'loans.return',
            'reservations.manage',
            'members.manage',
            'fines.record-payment',
        ];

        return match ($this) {
            self::Member => $member,
            self::Librarian => $librarian,
            self::Admin => [
                ...$librarian,
                'librarians.manage',
                'settings.manage',
                'reports.view',
            ],
        };
    }
}
