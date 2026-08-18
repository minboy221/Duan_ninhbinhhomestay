<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Contract;

class ProratedBillingService
{
    /**
     * Ngưỡng số ngày tối thiểu để tính chia lẻ (Mặc định 7 ngày)
     */
    public const DEFAULT_GRACE_DAYS = 7;

    /**
     * Tính toán thông tin chia lẻ tiền phòng tháng đầu tiên cho khách mới / ở ghép
     *
     * @param Contract $contract
     * @param string|null $targetBillingMonth (Định dạng Y-m, ví dụ '2026-08')
     * @param int $graceDays (Mặc định 7 ngày)
     * @return array
     */
    public function calculateProratedRent(Contract $contract, ?string $targetBillingMonth = null, int $graceDays = self::DEFAULT_GRACE_DAYS): array
    {
        if (!$contract->start_date) {
            return [
                'should_prorate' => false,
                'is_grace_period' => false,
                'days_occupied' => 0,
                'suggested_rent' => (float) ($contract->monthly_rent ?: ($contract->room ? $contract->room->price : 0)),
                'reason' => 'Không có ngày bắt đầu hợp đồng'
            ];
        }

        $startDate = Carbon::parse($contract->start_date);
        $startDay = $startDate->day;

        // Nếu bắt đầu từ ngày 1 của tháng, không cần chia lẻ
        if ($startDay === 1) {
            return [
                'should_prorate' => false,
                'is_grace_period' => false,
                'days_occupied' => $startDate->daysInMonth,
                'suggested_rent' => (float) ($contract->monthly_rent ?: ($contract->room ? $contract->room->price : 0)),
                'reason' => 'Hợp đồng bắt đầu từ đầu tháng'
            ];
        }

        $totalDaysInMonth = $startDate->daysInMonth;
        $occupiedDays = $totalDaysInMonth - $startDay + 1;
        $monthlyRent = (float) ($contract->monthly_rent ?: ($contract->room ? $contract->room->price : 0));

        // Nếu số ngày ở < ngưỡng graceDays (ví dụ < 7 ngày) -> Du di (Bỏ qua chia lẻ / Hoãn gộp)
        if ($occupiedDays < $graceDays) {
            return [
                'should_prorate' => false,
                'is_grace_period' => true,
                'days_occupied' => $occupiedDays,
                'grace_days' => $graceDays,
                'suggested_rent' => $monthlyRent, // Dùng giá chuẩn hoặc du di
                'monthly_rent' => $monthlyRent,
                'reason' => "Số ngày ở trong kỳ ({$occupiedDays} ngày) nhỏ hơn ngưỡng du di ({$graceDays} ngày). Coi như không chia lẻ hoặc dồn sang tháng sau."
            ];
        }

        // Nếu số ngày ở >= graceDays (ví dụ >= 7 ngày) -> Kích hoạt chia lẻ (Pro-rate)
        $proratedAmount = round(($monthlyRent / $totalDaysInMonth) * $occupiedDays);

        return [
            'should_prorate' => true,
            'is_grace_period' => false,
            'days_occupied' => $occupiedDays,
            'total_days_in_month' => $totalDaysInMonth,
            'monthly_rent' => $monthlyRent,
            'suggested_rent' => $proratedAmount,
            'reason' => "Ở thực tế {$occupiedDays}/{$totalDaysInMonth} ngày (>= {$graceDays} ngày). Tiền phòng lẻ đề xuất: " . number_format($proratedAmount) . 'đ.'
        ];
    }
}
