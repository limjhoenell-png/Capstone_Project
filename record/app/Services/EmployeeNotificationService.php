<?php

namespace App\Services;

use App\Models\Payslip;
use App\Models\User;
use App\Models\UserNotice;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class EmployeeNotificationService
{
    public const PER_PAGE = 10;

    /**
     * @return array{
     *     notifications: array<int, array{id: int, title: string, body: string, created_at: ?string, read: bool, view_url: ?string, print_url: ?string}>,
     *     unread_count: int,
     *     total: int,
     *     current_page: int,
     *     last_page: int,
     *     per_page: int
     * }
     */
    public function payload(User $user, int $page = 1): array
    {
        $paginator = $this->paginate($user, $page);
        $notices = $paginator->getCollection();

        return [
            'notifications' => $notices->map(function (UserNotice $notice): array {
                $payslip = $this->payslipForNotice($notice);

                return [
                    'id' => $notice->id,
                    'title' => $notice->title,
                    'body' => $notice->body,
                    'created_at' => $notice->created_at?->format('M j, Y g:i A'),
                    'read' => $notice->read_at !== null,
                    'view_url' => $payslip ? route('employee.payslips.show', $payslip) : null,
                    'print_url' => $payslip ? route('employee.payslips.pdf', $payslip) : null,
                ];
            })->values()->all(),
            'unread_count' => $user->notices()->whereNull('read_at')->count(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => self::PER_PAGE,
        ];
    }

    /**
     * @return LengthAwarePaginator<int, UserNotice>
     */
    private function paginate(User $user, int $page): LengthAwarePaginator
    {
        $total = $user->notices()->count();
        $lastPage = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, $page), $lastPage);

        return $user->notices()
            ->with('payslip')
            ->latest()
            ->paginate(self::PER_PAGE, ['*'], 'page', $page);
    }

    private function payslipForNotice(UserNotice $notice): ?Payslip
    {
        if ($notice->payslip !== null) {
            return $notice->payslip->user_id === $notice->user_id
                && $notice->payslip->isVisibleToEmployee()
                ? $notice->payslip
                : null;
        }

        if ($notice->title !== 'Payslip published'
            || ! preg_match('/payslip for ([A-Za-z]+ \d{1,2}, \d{4}) to ([A-Za-z]+ \d{1,2}, \d{4}) is ready/', $notice->body, $matches)) {
            return null;
        }

        try {
            $periodStart = Carbon::parse($matches[1])->toDateString();
            $periodEnd = Carbon::parse($matches[2])->toDateString();
        } catch (InvalidFormatException) {
            return null;
        }

        $payslip = Payslip::query()
            ->where('user_id', $notice->user_id)
            ->whereDate('period_start', $periodStart)
            ->whereDate('period_end', $periodEnd)
            ->where('kind', 'regular')
            ->where(function ($query): void {
                $query->whereNull('status')->orWhereIn('status', ['approved', 'paid']);
            })
            ->first();

        return $payslip !== null && $payslip->isVisibleToEmployee() ? $payslip : null;
    }
}
