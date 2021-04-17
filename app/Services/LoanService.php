<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\ScheduledRepayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LoanService
{
    /**
     * Create a Loan
     *
     * @param User $user
     * @param int $amount
     * @param string $currencyCode
     * @param int $terms
     * @param string $processedAt
     *
     * @return Loan
     */
    public function createLoan(User $user, int $amount, string $currencyCode, int $terms, string $processedAt): Loan
    {
        $termsOfLoan = $terms == 3 ? 3 : 6;
        //Add to Loan table
        $loan = Loan::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'terms' => $termsOfLoan,
            'outstanding_amount' => $amount,
            'currency_code' => $currencyCode,
            'processed_at' => $processedAt,
            'status' => Loan::STATUS_DUE,
        ]);
        $stepAmount = (int)floor($amount / $termsOfLoan);
        $outStandingAmount = $amount;
        $processedTimestamp = strtotime($processedAt);

        for ($i = 1; $i <= $termsOfLoan; $i++) {
            $outStandingAmount -= $stepAmount;
            if ($outStandingAmount < $stepAmount) {
                $stepAmount = $outStandingAmount + $stepAmount;
            }
            ScheduledRepayment::create([
                'loan_id' => $loan->id,
                'amount' => $stepAmount,
                'outstanding_amount' => $stepAmount,
                'currency_code' => $currencyCode,
                'due_date' => date("Y-m-d", strtotime("+{$i} month", $processedTimestamp)),
                'status' => ScheduledRepayment::STATUS_DUE,
            ]);
        }
        return $loan;
    }

    /**
     * Repay Scheduled Repayments for a Loan
     *
     * @param Loan $loan
     * @param int $amount
     * @param string $currencyCode
     * @param string $receivedAt
     *
     * @return ReceivedRepayment
     */
    public function repayLoan(Loan $loan, int $amount, string $currencyCode, string $receivedAt): ReceivedRepayment
    {
        //Update the outstanding amount
        $paidAmount = DB::table('scheduled_repayments')
            ->where('loan_id', '=', $loan->id)
            ->where('status', '=', ScheduledRepayment::STATUS_REPAID)
            ->sum('amount');

        $newLoan = Loan::find($loan->id);
        $newLoan->outstanding_amount = $newLoan->amount - $amount - $paidAmount;
        if ($newLoan->outstanding_amount == 0) {
            $newLoan->status = Loan::STATUS_REPAID;
        }
        $newLoan->save();

        $newAmount = $amount;
        do {
            //Find oldest due schedule repayment
            $scheduleRepaymentResult = ScheduledRepayment::select(['id','amount'])
                ->where('loan_id', '=', $loan->id)
                ->where('status', '=', ScheduledRepayment::STATUS_DUE)
                ->orderby('id')
                ->first();
            if (isset($scheduleRepaymentResult->id)) {
                $scheduleRepayment = ScheduledRepayment::find($scheduleRepaymentResult->id);
                if ($newAmount >= $scheduleRepaymentResult->amount) {
                    $scheduleRepayment->outstanding_amount = 0;
                    $scheduleRepayment->status = ScheduledRepayment::STATUS_REPAID;
                } else {
                    $scheduleRepayment->outstanding_amount = $scheduleRepayment->amount - $newAmount;
                    $scheduleRepayment->status = ScheduledRepayment::STATUS_PARTIAL;
                }
                $scheduleRepayment->save();
                unset($scheduleRepayment);
                $newAmount -= $scheduleRepaymentResult->amount;
            }
        } while($newAmount > 0);

        return ReceivedRepayment::create([
            'loan_id' => $loan->id,
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'received_at' => $receivedAt,
        ]);
    }
}
