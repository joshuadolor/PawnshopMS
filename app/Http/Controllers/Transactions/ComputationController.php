<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Carbon\Carbon;

class ComputationController extends Controller
{
    /**
     * Compute the late days charge for a transaction.
     * 
     * Formula: (interest / 30) * late_days
     * 
     * @param Transaction $transaction The transaction to compute late days charge for
     * @param Carbon|null $referenceDate Optional reference date (defaults to today)
     * @param float|null $principalAmount Optional principal amount to use instead of transaction's loan_amount
     * @param float|null $interestRate Optional interest rate to use instead of transaction's interest_rate
     * @param Carbon|null $maturityDate Optional maturity date to use instead of transaction's maturity_date
     * @return float The late days charge amount
     */
    public function computeLateDaysCharge(Transaction $transaction, ?Carbon $referenceDate = null, ?float $principalAmount = null, ?float $interestRate = null, ?Carbon $maturityDate = null): float
    {
        // Use today as reference date if not provided
        if ($referenceDate === null) {
            $referenceDate = Carbon::today();
        }

        // Use provided maturity date or get from transaction
        if ($maturityDate === null) {
            $maturityDate = $transaction->maturity_date;
        }
        
        if (!$maturityDate) {
            // No maturity date, no late days charge
            return 0.0;
        }

        // Parse maturity date to Carbon if it's not already
        if (!$maturityDate instanceof Carbon) {
            $maturityDate = Carbon::parse($maturityDate);
        }

        // Calculate late days (only if reference date is after maturity date)
        $lateDays = 0;
        if ($referenceDate->gt($maturityDate)) {
            $lateDays = $maturityDate->diffInDays($referenceDate);
        }

        // If no late days, return 0
        if ($lateDays <= 0) {
            return 0.0;
        }

        // Use provided values or transaction's values
        $loanAmount = $principalAmount !== null ? $principalAmount : (float) $transaction->loan_amount;
        $rate = $interestRate !== null ? $interestRate : (float) $transaction->interest_rate;
        $interest = $loanAmount * ($rate / 100);

        // Apply formula: (interest / 30) * late_days
        $lateDaysCharge = ($interest / 30) * $lateDays;

        // Round to 2 decimal places
        return round($lateDaysCharge, 2);
    }

    /**
     * Get detailed breakdown of late days charge computation.
     * 
     * @param Transaction $transaction The transaction to compute late days charge for
     * @param Carbon|null $referenceDate Optional reference date (defaults to today)
     * @param float|null $principalAmount Optional principal amount to use instead of transaction's loan_amount
     * @param float|null $interestRate Optional interest rate to use instead of transaction's interest_rate
     * @param Carbon|null $maturityDate Optional maturity date to use instead of transaction's maturity_date
     * @return array Detailed breakdown including all calculation components
     */
    public function getLateDaysChargeBreakdown(Transaction $transaction, ?Carbon $referenceDate = null, ?float $principalAmount = null, ?float $interestRate = null, ?Carbon $maturityDate = null): array
    {
        // Use today as reference date if not provided
        if ($referenceDate === null) {
            $referenceDate = Carbon::today();
        }

        // Use provided maturity date or get from transaction
        if ($maturityDate === null) {
            $maturityDate = $transaction->maturity_date;
        }
        
        // Use provided values or transaction's values
        $loanAmount = $principalAmount !== null ? $principalAmount : (float) $transaction->loan_amount;
        $rate = $interestRate !== null ? $interestRate : (float) $transaction->interest_rate;
        $interest = $loanAmount * ($rate / 100);

        $lateDays = 0;
        $lateDaysCharge = 0.0;
        $isLate = false;

        if ($maturityDate) {
            // Parse maturity date to Carbon if it's not already
            if (!$maturityDate instanceof Carbon) {
                $maturityDate = Carbon::parse($maturityDate);
            }

            // Calculate late days (only if reference date is after maturity date)
            if ($referenceDate->gt($maturityDate)) {
                $lateDays = $maturityDate->diffInDays($referenceDate);
                $isLate = true;
                
                // Apply formula: (interest / 30) * late_days
                $lateDaysCharge = ($interest / 30) * $lateDays;
                $lateDaysCharge = round($lateDaysCharge, 2);
            }
        }

        return [
            'loan_amount' => $loanAmount,
            'interest_rate' => $rate,
            'interest' => round($interest, 2),
            'maturity_date' => $maturityDate ? $maturityDate->format('Y-m-d') : null,
            'reference_date' => $referenceDate->format('Y-m-d'),
            'late_days' => $lateDays,
            'is_late' => $isLate,
            'late_days_charge' => $lateDaysCharge,
            'formula' => '(interest / 30) * late_days',
            'calculation' => $isLate 
                ? "({$interest} / 30) * {$lateDays} = {$lateDaysCharge}"
                : 'No late days charge (transaction is not overdue)',
        ];
    }
}
