<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Http\Requests\VoidTransactionRequest;
use App\Models\Branch;
use App\Models\BranchFinancialTransaction;
use App\Models\BranchBalance;
use App\Models\Transaction;
use App\Models\VoidedBranchFinancialTransaction;
use App\Models\VoidedTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class TransactionController extends Controller
{
    private function assertAdminOrSuperAdmin(Request $request): void
    {
        if (!$request->user() || !$request->user()->isAdminOrSuperAdmin()) {
            abort(403);
        }
    }

    /**
     * Display a listing of transactions.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $query = Transaction::with(['branch', 'user', 'itemType', 'itemTypeSubtype', 'tags', 'voided.voidedBy']);
        $showAll = false;

        // Staff users only see transactions for today
        if ($user->isStaff()) {
            $query->where('branch_id', $user->branches()->first()->id);
            $query->whereDate('created_at', today());
        } else {
            // Admin and Superadmin can filter by date range
            $showAll = $request->boolean('show_all', false);
            if ($request->has('today_only') && $request->boolean('today_only')) {
                // Today Only: clear date filters and set to today
                $query->whereDate('created_at', today());
            } elseif ($request->filled('start_date') || $request->filled('end_date')) {
                // Date range filter
                if ($request->filled('start_date')) {
                    $query->whereDate('created_at', '>=', $request->start_date);
                }
                if ($request->filled('end_date')) {
                    $query->whereDate('created_at', '<=', $request->end_date);
                }
            }
        }

        // Search by item description, names, transaction number, or pawn ticket number
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('item_description', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('transaction_number', 'like', "%{$search}%")
                  ->orWhere('pawn_ticket_number', 'like', "%{$search}%");
            });
        }

        // Filter by branch (for staff, only their branches)
        if ($user->isStaff()) {
            $userBranchIds = $user->branches()->pluck('branches.id')->toArray();
            if (!empty($userBranchIds)) {
                $query->whereIn('branch_id', $userBranchIds);
            }
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by transaction type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Hide voided (default true for admin/superadmin; staff UI hides via JS only)
        $hideVoided = $user->isAdminOrSuperAdmin()
            ? $request->boolean('hide_voided', true)
            : false;
        if ($hideVoided) {
            $query->whereDoesntHave('voided');
        }

        // Filter by user (admin and superadmin only)
        if ($user->isAdminOrSuperAdmin() && $request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Get all transactions - we'll group by pawn ticket in the view
        if ($showAll && $user->isAdminOrSuperAdmin()) {
            $transactions = $query->orderBy('created_at', 'desc')->get();
        } else {
            $transactions = $query->orderBy('created_at', 'desc')->paginate(20);
        }

        // Get all unique pawn ticket numbers from the current page
        $pawnTicketNumbers = $transactions->pluck('pawn_ticket_number')->filter()->unique()->values();

        // Fetch all renewals, tubos, and partials for these pawn tickets (even if they're on different pages)
        $renewalsForPawnTickets = collect();
        $tubosForPawnTickets = collect();
        $partialsForPawnTickets = collect();
        if ($pawnTicketNumbers->isNotEmpty()) {
            $renewalQuery = Transaction::with(['branch', 'user', 'itemType', 'itemTypeSubtype', 'tags', 'voided.voidedBy'])
                ->where('type', 'renew')
                ->whereIn('pawn_ticket_number', $pawnTicketNumbers->toArray());
            
            $tubosQuery = Transaction::with(['branch', 'user', 'itemType', 'itemTypeSubtype', 'tags', 'voided.voidedBy'])
                ->where('type', 'tubos')
                ->whereIn('pawn_ticket_number', $pawnTicketNumbers->toArray());
            
            $partialQuery = Transaction::with(['branch', 'user', 'itemType', 'itemTypeSubtype', 'tags', 'voided.voidedBy'])
                ->where('type', 'partial')
                ->whereIn('pawn_ticket_number', $pawnTicketNumbers->toArray());

            // Apply same filters as main query
            if ($user->isStaff()) {
                $renewalQuery->where('branch_id', $user->branches()->first()->id);
                $renewalQuery->whereDate('created_at', today());
            } else {
                if ($request->has('today_only') && $request->boolean('today_only')) {
                    // Today Only: clear date filters and set to today
                    $renewalQuery->whereDate('created_at', today());
                } elseif ($request->filled('start_date') || $request->filled('end_date')) {
                    // Date range filter
                    if ($request->filled('start_date')) {
                        $renewalQuery->whereDate('created_at', '>=', $request->start_date);
                    }
                    if ($request->filled('end_date')) {
                        $renewalQuery->whereDate('created_at', '<=', $request->end_date);
                    }
                }
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $renewalQuery->where(function ($q) use ($search) {
                    $q->where('item_description', 'like', "%{$search}%")
                      ->orWhere('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('transaction_number', 'like', "%{$search}%")
                      ->orWhere('pawn_ticket_number', 'like', "%{$search}%");
                });
            }

            if ($user->isStaff()) {
                $userBranchIds = $user->branches()->pluck('branches.id')->toArray();
                if (!empty($userBranchIds)) {
                    $renewalQuery->whereIn('branch_id', $userBranchIds);
                }
            } elseif ($request->filled('branch_id')) {
                $renewalQuery->where('branch_id', $request->branch_id);
            }

            if ($request->filled('type')) {
                $renewalQuery->where('type', $request->type);
            }

            // Filter by user (admin and superadmin only)
            if ($user->isAdminOrSuperAdmin() && $request->filled('user_id')) {
                $renewalQuery->where('user_id', $request->user_id);
            }

            $renewalsForPawnTickets = $renewalQuery->get();
            
            // Apply same filters for tubos
            if ($user->isStaff()) {
                $tubosQuery->where('branch_id', $user->branches()->first()->id);
                $tubosQuery->whereDate('created_at', today());
            } else {
                if ($request->has('today_only') && $request->boolean('today_only')) {
                    // Today Only: clear date filters and set to today
                    $tubosQuery->whereDate('created_at', today());
                } elseif ($request->filled('start_date') || $request->filled('end_date')) {
                    // Date range filter
                    if ($request->filled('start_date')) {
                        $tubosQuery->whereDate('created_at', '>=', $request->start_date);
                    }
                    if ($request->filled('end_date')) {
                        $tubosQuery->whereDate('created_at', '<=', $request->end_date);
                    }
                }
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $tubosQuery->where(function ($q) use ($search) {
                    $q->where('item_description', 'like', "%{$search}%")
                      ->orWhere('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('transaction_number', 'like', "%{$search}%")
                      ->orWhere('pawn_ticket_number', 'like', "%{$search}%");
                });
            }

            if ($user->isStaff()) {
                $userBranchIds = $user->branches()->pluck('branches.id')->toArray();
                if (!empty($userBranchIds)) {
                    $tubosQuery->whereIn('branch_id', $userBranchIds);
                }
            } elseif ($request->filled('branch_id')) {
                $tubosQuery->where('branch_id', $request->branch_id);
            }

            if ($request->filled('type')) {
                $tubosQuery->where('type', $request->type);
            }

            // Filter by user (admin and superadmin only)
            if ($user->isAdminOrSuperAdmin() && $request->filled('user_id')) {
                $tubosQuery->where('user_id', $request->user_id);
            }

            $tubosForPawnTickets = $tubosQuery->get();
            
            // Apply same filters for partial
            if ($user->isStaff()) {
                $partialQuery->where('branch_id', $user->branches()->first()->id);
                $partialQuery->whereDate('created_at', today());
            } else {
                if ($request->has('today_only') && $request->boolean('today_only')) {
                    // Today Only: clear date filters and set to today
                    $partialQuery->whereDate('created_at', today());
                } elseif ($request->filled('start_date') || $request->filled('end_date')) {
                    // Date range filter
                    if ($request->filled('start_date')) {
                        $partialQuery->whereDate('created_at', '>=', $request->start_date);
                    }
                    if ($request->filled('end_date')) {
                        $partialQuery->whereDate('created_at', '<=', $request->end_date);
                    }
                }
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $partialQuery->where(function ($q) use ($search) {
                    $q->where('item_description', 'like', "%{$search}%")
                      ->orWhere('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('transaction_number', 'like', "%{$search}%")
                      ->orWhere('pawn_ticket_number', 'like', "%{$search}%");
                });
            }

            if ($user->isStaff()) {
                $userBranchIds = $user->branches()->pluck('branches.id')->toArray();
                if (!empty($userBranchIds)) {
                    $partialQuery->whereIn('branch_id', $userBranchIds);
                }
            } elseif ($request->filled('branch_id')) {
                $partialQuery->where('branch_id', $request->branch_id);
            }

            if ($request->filled('type')) {
                $partialQuery->where('type', $request->type);
            }

            // Filter by user (admin and superadmin only)
            if ($user->isAdminOrSuperAdmin() && $request->filled('user_id')) {
                $partialQuery->where('user_id', $request->user_id);
            }

            $partialsForPawnTickets = $partialQuery->get();
        }

        // Get branches for filter (admin/superadmin only)
        $branches = null;
        if ($user->isAdminOrSuperAdmin()) {
            $branches = Branch::orderBy('name', 'asc')->get();
        }

        // Get users for filter (admin/superadmin only)
        $users = null;
        if ($user->isAdminOrSuperAdmin()) {
            $users = \App\Models\User::orderBy('first_name', 'asc')
                ->orderBy('last_name', 'asc')
                ->get();
        }

        return view('transactions.index', [
            'transactions' => $transactions,
            'renewalsForPawnTickets' => $renewalsForPawnTickets,
            'tubosForPawnTickets' => $tubosForPawnTickets,
            'partialsForPawnTickets' => $partialsForPawnTickets,
            'branches' => $branches,
            'users' => $users,
            'showAll' => $showAll,
            'filters' => [
                'start_date' => $request->start_date ?? null,
                'end_date' => $request->end_date ?? null,
                'today_only' => $request->boolean('today_only', false),
                'search' => $request->search ?? null,
                'branch_id' => $request->branch_id ?? null,
                'type' => $request->type ?? null,
                'user_id' => $request->user_id ?? null,
                'hide_voided' => $hideVoided,
                'show_all' => $showAll,
            ],
        ]);
    }

    /**
     * Export the currently filtered transactions to CSV (Excel-readable).
     * Admin/Superadmin only.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->assertAdminOrSuperAdmin($request);

        // Reuse the same filters as index, but always export the full filtered dataset.
        $query = Transaction::with(['branch', 'user', 'itemType', 'itemTypeSubtype', 'tags', 'voided.voidedBy']);

        if ($request->has('today_only') && $request->boolean('today_only')) {
            $query->whereDate('created_at', today());
        } elseif ($request->filled('start_date') || $request->filled('end_date')) {
            if ($request->filled('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('item_description', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('transaction_number', 'like', "%{$search}%")
                    ->orWhere('pawn_ticket_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $hideVoided = $request->boolean('hide_voided', true);
        if ($hideVoided) {
            $query->whereDoesntHave('voided');
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        // Precompute "principal increase" flags for partials (matches table logic).
        $partialIds = $transactions->where('type', 'partial')->pluck('id')->values();
        $principalIncreasePartialIds = collect();
        if ($partialIds->isNotEmpty()) {
            $principalIncreasePartialIds = \App\Models\BranchFinancialTransaction::whereIn('transaction_id', $partialIds)
                ->where('type', 'expense')
                ->where('description', 'like', '%Principal increase%')
                ->whereDoesntHave('voided')
                ->pluck('transaction_id')
                ->unique();
        }

        $filename = 'transactions_export_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($transactions, $principalIncreasePartialIds) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Row Type',
                'Pawn Ticket #',
                'Transaction #',
                'Date',
                'Time',
                'Description / Item',
                'Type',
                'Amount Label',
                'Amount (₱)',
                'Branch',
                'Processed By',
                'Pawner Name',
                'Original Principal (₱)',
                'Current Principal (₱)',
                'Net Proceeds (₱)',
                'Maturity',
                'Expiry',
                'Auction',
                'Voided?',
                'Voided By',
                'Voided At',
                'Void Reason',
            ]);

            // Group like the UI: by pawn ticket number (or standalone id)
            $groups = $transactions->groupBy(fn (Transaction $t) => $t->pawn_ticket_number ?: ('no-pawn-ticket-' . $t->id));

            foreach ($groups as $groupKey => $groupTransactions) {
                $pawnTicketNumber = $groupTransactions->first()->pawn_ticket_number;

                if ($pawnTicketNumber) {
                    $sanglas = $groupTransactions->where('type', 'sangla')->sortBy('created_at');
                    $oldestSangla = $sanglas->first();

                    $originalPrincipal = $oldestSangla ? (float) $oldestSangla->loan_amount : 0.0;
                    $netProceeds = $oldestSangla ? (float) $oldestSangla->net_proceeds : 0.0;

                    $latestPartial = $transactions->where('pawn_ticket_number', $pawnTicketNumber)
                        ->where('type', 'partial')
                        ->sortByDesc('created_at')
                        ->first();
                    $currentPrincipal = $latestPartial ? (float) $latestPartial->loan_amount : $originalPrincipal;

                    $latestTxForDates = $transactions->where('pawn_ticket_number', $pawnTicketNumber)
                        ->whereIn('type', ['sangla', 'renew', 'partial'])
                        ->sortByDesc('created_at')
                        ->first();

                    fputcsv($out, [
                        'PAWN_TICKET',
                        $pawnTicketNumber,
                        '',
                        '',
                        '',
                        '',
                        '',
                        'Principal / Net Proceeds',
                        '',
                        $oldestSangla?->branch?->name ?? '',
                        '',
                        $oldestSangla ? ($oldestSangla->first_name . ' ' . $oldestSangla->last_name) : '',
                        number_format($originalPrincipal, 2, '.', ''),
                        number_format($currentPrincipal, 2, '.', ''),
                        number_format($netProceeds, 2, '.', ''),
                        $latestTxForDates && $latestTxForDates->maturity_date ? $latestTxForDates->maturity_date->format('Y-m-d') : '',
                        $latestTxForDates && $latestTxForDates->expiry_date ? $latestTxForDates->expiry_date->format('Y-m-d') : '',
                        $latestTxForDates && $latestTxForDates->auction_sale_date ? $latestTxForDates->auction_sale_date->format('Y-m-d') : '',
                        '',
                        '',
                        '',
                        '',
                    ]);
                }

                foreach ($groupTransactions->sortBy('created_at') as $tx) {
                    $isVoided = $tx->isVoided();
                    $voidedInfo = $tx->voided;

                    $typeLabel = match ($tx->type) {
                        'sangla' => 'Sangla',
                        'renew' => 'Renewal',
                        'tubos' => 'Tubos',
                        'partial' => 'Partial',
                        default => $tx->type,
                    };

                    $amountLabel = '';
                    $amount = '';

                    if ($tx->type === 'renew') {
                        $amountLabel = 'Interest Paid';
                        $amount = number_format((float) $tx->net_proceeds, 2, '.', '');
                    } elseif ($tx->type === 'tubos') {
                        $amountLabel = 'Amount Paid';
                        $amount = number_format((float) $tx->net_proceeds, 2, '.', '');
                    } elseif ($tx->type === 'partial') {
                        $isPrincipalIncrease = $principalIncreasePartialIds->contains($tx->id);
                        $amountLabel = $isPrincipalIncrease ? 'Principal Increase' : 'Partial Amount Paid';
                        $signed = $isPrincipalIncrease ? -(float) $tx->net_proceeds : (float) $tx->net_proceeds;
                        $amount = number_format($signed, 2, '.', '');
                    } else {
                        // Sangla amount is shown in the PAWN_TICKET header row on the UI; keep blank here
                        $amountLabel = '';
                        $amount = '';
                    }

                    $desc = $tx->type === 'sangla'
                        ? trim(($tx->itemType?->name ?? '') . ($tx->itemTypeSubtype ? ' - ' . $tx->itemTypeSubtype->name : '') . ($tx->custom_item_type ? ' - ' . $tx->custom_item_type : '')) . ': ' . (string) $tx->item_description
                        : (string) $tx->item_description;

                    fputcsv($out, [
                        'TRANSACTION',
                        $tx->pawn_ticket_number ?? '',
                        $tx->transaction_number,
                        $tx->created_at ? $tx->created_at->format('Y-m-d') : '',
                        $tx->created_at ? $tx->created_at->format('H:i:s') : '',
                        $desc,
                        $typeLabel,
                        $amountLabel,
                        $amount,
                        $tx->branch?->name ?? '',
                        $tx->user?->name ?? '',
                        $tx->first_name . ' ' . $tx->last_name,
                        number_format((float) $tx->loan_amount, 2, '.', ''),
                        '',
                        $tx->type === 'sangla' ? number_format((float) $tx->net_proceeds, 2, '.', '') : '',
                        $tx->maturity_date ? $tx->maturity_date->format('Y-m-d') : '',
                        $tx->expiry_date ? $tx->expiry_date->format('Y-m-d') : '',
                        $tx->auction_sale_date ? $tx->auction_sale_date->format('Y-m-d') : '',
                        $isVoided ? 'YES' : 'NO',
                        $voidedInfo && $voidedInfo->voidedBy ? $voidedInfo->voidedBy->name : '',
                        $voidedInfo && $voidedInfo->voided_at ? $voidedInfo->voided_at->format('Y-m-d H:i:s') : '',
                        $voidedInfo ? $voidedInfo->reason : '',
                    ]);
                }
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Printable document for a transaction row.
     * IMPORTANT: Uses the clicked transaction's own data (not the parent Sangla).
     */
    public function printPawnTicket(Request $request, Transaction $transaction): View
    {
        // Any authenticated user who can see the transaction list can print.
        // (If you want admin-only later, we can gate it here.)

        $pawnTicketNumber = $transaction->pawn_ticket_number;
        $items = collect(); // rely on $base->item_description (partial already stores combined descriptions)

        $base = $transaction->loadMissing(['itemType', 'itemTypeSubtype', 'tags', 'branch', 'user']);

        $displayPawnTicketNumber = $transaction->type === 'sangla'
            ? ($transaction->pawn_ticket_number ?: null)
            : ($transaction->transaction_pawn_ticket ?: ($transaction->pawn_ticket_number ?: null));

        $dateLoanGranted = Carbon::parse($transaction->created_at)->format('M d, Y');

        $principal = (float) ($transaction->loan_amount ?? 0);
        $rate = (float) ($transaction->interest_rate ?? 0);
        $interest = $principal * ($rate / 100);
        $serviceCharge = (float) ($transaction->service_charge ?? 0);
        $netProceeds = (float) ($transaction->net_proceeds ?? 0);

        $isPartialReceipt = $transaction->type === 'partial';
        $principalBefore = null;
        $principalAfter = null;
        $principalChange = null;
        $principalPaid = null;
        $cashAmount = null;
        $cashLabel = null;
        $lateDaysCharge = (float) ($transaction->late_days_charge ?? 0);
        $interestAndOtherCharges = null;

        if ($isPartialReceipt && $pawnTicketNumber) {
            $principalAfter = (float) ($transaction->loan_amount ?? 0);

            $previousPartial = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
                ->where('type', 'partial')
                ->whereDoesntHave('voided')
                ->where('created_at', '<', $transaction->created_at)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($previousPartial) {
                $principalBefore = (float) $previousPartial->loan_amount;
            } else {
                $firstSangla = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
                    ->where('type', 'sangla')
                    ->whereDoesntHave('voided')
                    ->orderBy('created_at', 'asc')
                    ->first();
                $principalBefore = (float) ($firstSangla?->loan_amount ?? $principalAfter);
            }

            // Positive means principal reduced; negative means principal increased.
            $principalChange = $principalBefore - $principalAfter;
            $principalPaid = max(0.0, $principalChange);

            $bft = BranchFinancialTransaction::where('transaction_id', $transaction->id)
                ->whereDoesntHave('voided')
                ->orderBy('id', 'desc')
                ->first();

            $cashAmount = (float) ($bft?->amount ?? $transaction->net_proceeds ?? 0);
            $cashLabel = ($bft && $bft->isExpense()) ? 'Cash Released' : 'Amount Paid';

            // We only know the grand total cash movement for the partial.
            // Break down deterministically using stored charges and inferred remainder.
            $chargesTotal = max(0.0, $cashAmount - $principalPaid);
            $interestAndOtherCharges = max(0.0, $chargesTotal - $serviceCharge - $lateDaysCharge);
        }

        $printedAt = now();
        $printedById = (int) ($request->user()?->id ?? 0);
        $processedById = (int) ($transaction->user_id ?? 0);
        $transactionNumber = (string) ($transaction->transaction_number ?: $transaction->id);

        $printTrackingCode = sprintf(
            '%s-%s-%s-%s',
            $transactionNumber,
            str_pad((string) $printedById, 2, '0', STR_PAD_LEFT),
            str_pad((string) $processedById, 2, '0', STR_PAD_LEFT),
            $printedAt->format('YmdHis')
        );

        return view('transactions.print.pawn-ticket', [
            'transaction' => $transaction,
            'base' => $base,
            'pawnTicketNumber' => $pawnTicketNumber,
            'displayPawnTicketNumber' => $displayPawnTicketNumber,
            'items' => $items,
            'dateLoanGranted' => $dateLoanGranted,
            'principal' => $principal,
            'interest' => $interest,
            'serviceCharge' => $serviceCharge,
            'netProceeds' => $netProceeds,
            'lateDaysCharge' => $lateDaysCharge,
            'isPartialReceipt' => $isPartialReceipt,
            'principalBefore' => $principalBefore,
            'principalAfter' => $principalAfter,
            'principalChange' => $principalChange,
            'principalPaid' => $principalPaid,
            'cashAmount' => $cashAmount,
            'cashLabel' => $cashLabel,
            'interestAndOtherCharges' => $interestAndOtherCharges,
            'printedAt' => $printedAt,
            'printTrackingCode' => $printTrackingCode,
        ]);
    }

    /**
     * Void a transaction.
     */
    public function void(VoidTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        // Check if transaction is already voided
        if ($transaction->isVoided()) {
            return redirect()->back()
                ->with('error', 'This transaction is already voided.');
        }

        // Check if transaction is older than 6 hours
        $hoursSinceCreation = $transaction->created_at->diffInHours(now());
        if ($hoursSinceCreation > 6) {
            return redirect()->back()
                ->with('error', 'Cannot void this transaction. Transactions can only be voided within 6 hours of creation.');
        }

        // Check if this is a child transaction and if it's the latest one
        if ($transaction->pawn_ticket_number) {
            // Get the first Sangla transaction (parent) for this pawn ticket
            $firstSangla = Transaction::where('pawn_ticket_number', $transaction->pawn_ticket_number)
                ->where('type', 'sangla')
                ->orderBy('created_at', 'asc')
                ->first();

            // If this is not the first Sangla transaction, it's a child transaction
            if ($firstSangla && $transaction->id !== $firstSangla->id) {
                // Get all non-voided child transactions (excluding this one) ordered by creation date
                $otherChildTransactions = Transaction::where('pawn_ticket_number', $transaction->pawn_ticket_number)
                    ->where('id', '!=', $transaction->id)
                    ->where('id', '!=', $firstSangla->id)
                    ->whereDoesntHave('voided')
                    ->orderBy('created_at', 'desc')
                    ->get();

                // If there are other child transactions, check if this is the latest
                if ($otherChildTransactions->isNotEmpty()) {
                    $latestChild = $otherChildTransactions->first();
                    // Compare by created_at to determine which is latest
                    if ($transaction->created_at->lt($latestChild->created_at)) {
                        return redirect()->back()
                            ->with('error', 'Cannot void this transaction. Only the latest child transaction can be voided. Please void the most recent transaction first.');
                    }
                }
            }

            // For the first Sangla transaction, check if there are any non-voided child transactions
            if ($transaction->type === 'sangla' && $transaction->id === $firstSangla->id) {
                $hasChildTransactions = Transaction::where('pawn_ticket_number', $transaction->pawn_ticket_number)
                    ->where('id', '!=', $transaction->id)
                    ->whereDoesntHave('voided')
                    ->exists();
                
                if ($hasChildTransactions) {
                    return redirect()->back()
                        ->with('error', 'Cannot void this transaction. There are active child transactions (additional items or renewals) associated with this pawn ticket number. Please void the child transactions first.');
                }
            }
        }

        // Create void record and void associated financial transaction within a transaction
        DB::transaction(function () use ($transaction, $request) {
            VoidedTransaction::create([
                'transaction_id' => $transaction->id,
                'voided_by' => $request->user()->id,
                'reason' => $request->reason,
                'voided_at' => now(),
            ]);

            // Handle partial transaction reversals
            if ($transaction->type === 'partial') {
                $pawnTicketNumber = $transaction->pawn_ticket_number;
                
                // 1. Revert item statuses: Find all sangla transactions for this pawn ticket that are marked as redeemed
                // Only revert if there's no tubos transaction (items were redeemed via partial flow, not tubos)
                $hasTubosTransaction = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
                    ->where('type', 'tubos')
                    ->whereDoesntHave('voided')
                    ->exists();
                
                if (!$hasTubosTransaction) {
                    // No tubos transaction exists, so any redeemed items were redeemed via partial flow
                    // Revert them back to 'active'
                    Transaction::where('pawn_ticket_number', $pawnTicketNumber)
                        ->where('type', 'sangla')
                        ->where('status', 'redeemed')
                        ->whereDoesntHave('voided')
                        ->update(['status' => 'active']);
                }
                
                // 2. Revert principal amount: Find the previous partial transaction (or original sangla)
                // and restore the principal to that value
                $previousTransaction = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
                    ->whereIn('type', ['partial', 'sangla'])
                    ->where('id', '!=', $transaction->id)
                    ->whereDoesntHave('voided')
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if ($previousTransaction) {
                    // The principal should be restored to the previous transaction's loan_amount
                    // But actually, the principal is stored in the partial transaction itself
                    // The next partial/renewal will use the previous one's loan_amount
                    // So we don't need to update anything here - the voiding of this transaction
                    // means the next transaction will use the previous one's principal
                }
            }

            // Find and void the associated financial transaction
            // For partial transactions, it could be type 'transaction' (positive partial) or 'expense' (negative partial)
            $financialTransaction = BranchFinancialTransaction::where('transaction_id', $transaction->id)
                ->whereIn('type', ['transaction', 'expense'])
                ->whereDoesntHave('voided')
                ->first();

            // If not found by transaction_id, try to find by matching description, branch, amount, and date
            // This handles records created before the transaction_id column was added
            if (!$financialTransaction) {
                if ($transaction->type === 'sangla') {
                    // For Sangla transactions, match by "Sangla transaction" description
                    // Amount is stored as positive in BranchFinancialTransaction
                    $financialTransaction = BranchFinancialTransaction::where('type', 'transaction')
                        ->where(function($q) {
                            $q->where('description', 'Sangla transaction')
                              ->orWhere('description', 'Sangla transaction (additional item)');
                        })
                        ->where('branch_id', $transaction->branch_id)
                        ->where('amount', $transaction->net_proceeds) // Amount is positive
                        ->whereDate('transaction_date', $transaction->created_at->toDateString())
                        ->whereDoesntHave('voided')
                        ->whereNull('transaction_id') // Only match records without transaction_id
                        ->first();
                } elseif ($transaction->type === 'renew') {
                    // For Renewal transactions, match by "Renewal interest payment" description
                    $pawnTicketNumber = $transaction->pawn_ticket_number;
                    if ($pawnTicketNumber) {
                        $financialTransaction = BranchFinancialTransaction::where('type', 'transaction')
                            ->where('description', "Renewal interest payment - Pawn Ticket #{$pawnTicketNumber}")
                            ->where('branch_id', $transaction->branch_id)
                            ->where('amount', $transaction->net_proceeds) // Renewal amounts are positive
                            ->whereDate('transaction_date', $transaction->created_at->toDateString())
                            ->whereDoesntHave('voided')
                            ->whereNull('transaction_id') // Only match records without transaction_id
                            ->first();
                    }
                } elseif ($transaction->type === 'partial') {
                    // For Partial transactions, match by description
                    $pawnTicketNumber = $transaction->pawn_ticket_number;
                    if ($pawnTicketNumber) {
                        $financialTransaction = BranchFinancialTransaction::whereIn('type', ['transaction', 'expense'])
                            ->where(function($q) use ($pawnTicketNumber) {
                                $q->where('description', "Partial payment - Pawn Ticket #{$pawnTicketNumber}")
                                  ->orWhere('description', "Principal increase - Pawn Ticket #{$pawnTicketNumber}");
                            })
                            ->where('branch_id', $transaction->branch_id)
                            ->where('amount', abs($transaction->net_proceeds)) // Amount is stored as absolute value
                            ->whereDate('transaction_date', $transaction->created_at->toDateString())
                            ->whereDoesntHave('voided')
                            ->whereNull('transaction_id') // Only match records without transaction_id
                            ->first();
                    }
                }
            }

            if ($financialTransaction) {
                // Update transaction_id if it was NULL (for old records)
                if (!$financialTransaction->transaction_id) {
                    $financialTransaction->update(['transaction_id' => $transaction->id]);
                }

                // Create void record for financial transaction
                VoidedBranchFinancialTransaction::create([
                    'branch_financial_transaction_id' => $financialTransaction->id,
                    'voided_by' => $request->user()->id,
                    'reason' => "Associated transaction #{$transaction->transaction_number} was voided: {$request->reason}",
                    'voided_at' => now(),
                ]);

                // Reverse the transaction amount in the balance
                if ($transaction->type === 'sangla') {
                    // Sangla: amount was negative (money out), so we reverse by adding it back
                    BranchBalance::updateBalance($financialTransaction->branch_id, (float) $financialTransaction->amount);
                } elseif ($transaction->type === 'renew') {
                    // Renewal: amount was positive (money in), so we reverse by subtracting it
                    BranchBalance::updateBalance($financialTransaction->branch_id, -(float) $financialTransaction->amount);
                } elseif ($transaction->type === 'partial') {
                    // Partial: reverse the original amount
                    // If it was a payment (type: transaction), reverse by subtracting
                    // If it was an expense (type: expense), reverse by adding
                    if ($financialTransaction->type === 'transaction') {
                        // Original was money in (positive), reverse by subtracting
                        BranchBalance::updateBalance($financialTransaction->branch_id, -(float) $financialTransaction->amount);
                    } elseif ($financialTransaction->type === 'expense') {
                        // Original was money out (negative), reverse by adding
                        BranchBalance::updateBalance($financialTransaction->branch_id, (float) $financialTransaction->amount);
                    }
                }
            } else {
                // No existing financial transaction found - create one for voiding log
                // Determine the amount and description based on transaction type
                $amount = 0;
                $description = '';
                
                if ($transaction->type === 'sangla') {
                    $amount = $transaction->net_proceeds;
                    $description = $transaction->pawn_ticket_number && 
                        Transaction::where('pawn_ticket_number', $transaction->pawn_ticket_number)
                            ->where('id', '!=', $transaction->id)
                            ->where('type', 'sangla')
                            ->exists() 
                        ? 'Sangla transaction (additional item)' 
                        : 'Sangla transaction';
                } elseif ($transaction->type === 'renew') {
                    $amount = $transaction->net_proceeds;
                    $pawnTicketNumber = $transaction->pawn_ticket_number;
                    $description = $pawnTicketNumber 
                        ? "Renewal interest payment - Pawn Ticket #{$pawnTicketNumber}" 
                        : 'Renewal interest payment';
                } elseif ($transaction->type === 'partial') {
                    $amount = abs($transaction->net_proceeds);
                    $pawnTicketNumber = $transaction->pawn_ticket_number;
                    // Determine if it was a payment or expense based on the loan_amount change
                    // If loan_amount decreased, it was a payment; if increased, it was an expense
                    $previousTransaction = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
                        ->whereIn('type', ['partial', 'sangla'])
                        ->where('id', '!=', $transaction->id)
                        ->whereDoesntHave('voided')
                        ->orderBy('created_at', 'desc')
                        ->first();
                    
                    if ($previousTransaction) {
                        $previousPrincipal = (float) $previousTransaction->loan_amount;
                        $currentPrincipal = (float) $transaction->loan_amount;
                        if ($currentPrincipal < $previousPrincipal) {
                            // Principal decreased = payment (money in)
                            $description = "Partial payment - Pawn Ticket #{$pawnTicketNumber}";
                        } else {
                            // Principal increased = expense (money out)
                            $description = "Principal increase - Pawn Ticket #{$pawnTicketNumber}";
                        }
                    } else {
                        $description = "Partial payment - Pawn Ticket #{$pawnTicketNumber}";
                    }
                }

                if ($amount > 0) {
                    // Create financial transaction entry for voiding
                    $voidFinancialTransaction = BranchFinancialTransaction::create([
                        'branch_id' => $transaction->branch_id,
                        'user_id' => $request->user()->id,
                        'transaction_id' => $transaction->id,
                        'type' => 'transaction',
                        'description' => "VOIDED: {$description}",
                        'amount' => $amount,
                        'transaction_date' => now(),
                    ]);

                    // Create void record for the financial transaction
                    VoidedBranchFinancialTransaction::create([
                        'branch_financial_transaction_id' => $voidFinancialTransaction->id,
                        'voided_by' => $request->user()->id,
                        'reason' => "Transaction #{$transaction->transaction_number} was voided: {$request->reason}",
                        'voided_at' => now(),
                    ]);

                    // Update branch balance - reverse the original transaction effect
                    if ($transaction->type === 'sangla') {
                        // Sangla: original was money out (negative), voiding adds it back (positive)
                        BranchBalance::updateBalance($transaction->branch_id, (float) $amount);
                    } elseif ($transaction->type === 'renew') {
                        // Renewal: original was money in (positive), voiding subtracts it (negative)
                        BranchBalance::updateBalance($transaction->branch_id, -(float) $amount);
                    } elseif ($transaction->type === 'partial') {
                        // Partial: determine if it was payment or expense
                        $previousTransaction = Transaction::where('pawn_ticket_number', $transaction->pawn_ticket_number)
                            ->whereIn('type', ['partial', 'sangla'])
                            ->where('id', '!=', $transaction->id)
                            ->whereDoesntHave('voided')
                            ->orderBy('created_at', 'desc')
                            ->first();
                        
                        if ($previousTransaction) {
                            $previousPrincipal = (float) $previousTransaction->loan_amount;
                            $currentPrincipal = (float) $transaction->loan_amount;
                            if ($currentPrincipal < $previousPrincipal) {
                                // Was a payment (money in), reverse by subtracting
                                BranchBalance::updateBalance($transaction->branch_id, -(float) $amount);
                            } else {
                                // Was an expense (money out), reverse by adding
                                BranchBalance::updateBalance($transaction->branch_id, (float) $amount);
                            }
                        } else {
                            // Default: assume it was a payment
                            BranchBalance::updateBalance($transaction->branch_id, -(float) $amount);
                        }
                    }
                }
            }
        });

        return redirect()->back()
            ->with('success', "Transaction #{$transaction->transaction_number} has been voided.");
    }

    /**
     * Get all related transactions (Sangla) for a given pawn ticket number.
     */
    public function getRelatedTransactions(string $pawnTicketNumber): JsonResponse
    {
        $transactions = Transaction::with(['itemType', 'itemTypeSubtype', 'tags'])
            ->where('pawn_ticket_number', $pawnTicketNumber)
            ->where('type', 'sangla')
            ->whereDoesntHave('voided')
            ->orderBy('created_at', 'asc')
            ->get();

        $items = $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'transaction_number' => $transaction->transaction_number,
                'item_image_path' => $transaction->item_image_path ? route('images.show', ['path' => $transaction->item_image_path]) : null,
                'item_type' => $transaction->itemType->name,
                'item_subtype' => $transaction->itemTypeSubtype ? $transaction->itemTypeSubtype->name : null,
                'custom_item_type' => $transaction->custom_item_type,
                'item_description' => $transaction->item_description,
                'tags' => $transaction->tags->map(function ($tag) {
                    return $tag->name;
                })->toArray(),
            ];
        });

        return response()->json([
            'items' => $items,
        ]);
    }

    /**
     * Void all Sangla transactions for a given pawn ticket number.
     */
    public function voidPawnTicket(VoidTransactionRequest $request, string $pawnTicketNumber): RedirectResponse
    {
        // Find all Sangla transactions with this pawn ticket number
        $transactions = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->where('type', 'sangla')
            ->whereDoesntHave('voided')
            ->get();

        if ($transactions->isEmpty()) {
            return redirect()->back()
                ->with('error', 'No active Sangla transactions found for this pawn ticket number.');
        }

        // Check if any transaction is older than 6 hours
        $oldestTransaction = $transactions->sortBy('created_at')->first();
        $hoursSinceCreation = $oldestTransaction->created_at->diffInHours(now());
        if ($hoursSinceCreation > 6) {
            return redirect()->back()
                ->with('error', 'Cannot void this pawn ticket. Transactions can only be voided within 6 hours of creation.');
        }

        // Check if there are any non-voided child transactions (renewals)
        $hasChildTransactions = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->where('type', 'renew')
            ->whereDoesntHave('voided')
            ->exists();

        if ($hasChildTransactions) {
            return redirect()->back()
                ->with('error', 'Cannot void this pawn ticket. There are active renewal transactions associated with this pawn ticket number. Please void the renewal transactions first.');
        }

        // Void all transactions within a database transaction
        DB::transaction(function () use ($transactions, $request, $pawnTicketNumber) {
            foreach ($transactions as $transaction) {
                // Check if already voided (shouldn't happen due to query, but safety check)
                if ($transaction->isVoided()) {
                    continue;
                }

                // Create void record
                VoidedTransaction::create([
                    'transaction_id' => $transaction->id,
                    'voided_by' => $request->user()->id,
                    'reason' => "Pawn ticket #{$pawnTicketNumber} voided: {$request->reason}",
                    'voided_at' => now(),
                ]);

                // Find and void the associated financial transaction
                $financialTransaction = BranchFinancialTransaction::where('transaction_id', $transaction->id)
                    ->where('type', 'transaction')
                    ->whereDoesntHave('voided')
                    ->first();

                // If not found by transaction_id, try to find by matching description
                if (!$financialTransaction) {
                    $financialTransaction = BranchFinancialTransaction::where('type', 'transaction')
                        ->where(function($q) {
                            $q->where('description', 'Sangla transaction')
                              ->orWhere('description', 'Sangla transaction (additional item)');
                        })
                        ->where('branch_id', $transaction->branch_id)
                        ->where('amount', $transaction->net_proceeds)
                        ->whereDate('transaction_date', $transaction->created_at->toDateString())
                        ->whereDoesntHave('voided')
                        ->whereNull('transaction_id')
                        ->first();
                }

                if ($financialTransaction) {
                    // Update transaction_id if it was NULL
                    if (!$financialTransaction->transaction_id) {
                        $financialTransaction->update(['transaction_id' => $transaction->id]);
                    }

                    // Create void record for financial transaction
                    VoidedBranchFinancialTransaction::create([
                        'branch_financial_transaction_id' => $financialTransaction->id,
                        'voided_by' => $request->user()->id,
                        'reason' => "Associated transaction #{$transaction->transaction_number} was voided: {$request->reason}",
                        'voided_at' => now(),
                    ]);

                    // Reverse the transaction amount in the balance (Sangla: amount was negative, so we add it back)
                    BranchBalance::updateBalance($financialTransaction->branch_id, (float) $financialTransaction->amount);
                } else {
                    // No existing financial transaction found - create one for voiding log
                    $amount = $transaction->net_proceeds;
                    $description = $transaction->pawn_ticket_number && 
                        Transaction::where('pawn_ticket_number', $transaction->pawn_ticket_number)
                            ->where('id', '!=', $transaction->id)
                            ->where('type', 'sangla')
                            ->exists() 
                        ? 'Sangla transaction (additional item)' 
                        : 'Sangla transaction';

                    if ($amount > 0) {
                        // Create financial transaction entry for voiding
                        $voidFinancialTransaction = BranchFinancialTransaction::create([
                            'branch_id' => $transaction->branch_id,
                            'user_id' => $request->user()->id,
                            'transaction_id' => $transaction->id,
                            'type' => 'transaction',
                            'description' => "VOIDED: {$description}",
                            'amount' => $amount,
                            'transaction_date' => now(),
                        ]);

                        // Create void record for the financial transaction
                        VoidedBranchFinancialTransaction::create([
                            'branch_financial_transaction_id' => $voidFinancialTransaction->id,
                            'voided_by' => $request->user()->id,
                            'reason' => "Pawn ticket #{$pawnTicketNumber} voided: {$request->reason}",
                            'voided_at' => now(),
                        ]);

                        // Update branch balance - reverse the original transaction effect (Sangla: add back)
                        BranchBalance::updateBalance($transaction->branch_id, (float) $amount);
                    }
                }
            }
        });

        $transactionCount = $transactions->count();
        return redirect()->back()
            ->with('success', "Pawn ticket #{$pawnTicketNumber} has been voided. {$transactionCount} transaction(s) voided.");
    }
}
