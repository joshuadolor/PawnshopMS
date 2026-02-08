<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pawn Ticket - {{ $displayPawnTicketNumber ?: 'N/A' }}</title>
    <style>
        /* Letter portrait: 8.5in × 11in */
        @page {
            size: 8.5in 11in;
            margin: 0.35in;
        }

        :root {
            /* Requested: ~50% smaller overall typography to leave room for future header/footer */
            --font-scale: 0.75;
        }

        html, body {
            padding: 0;
            margin: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
        }

        .sheet {
            width: calc(8.5in - 0.7in);
            height: calc(11in - 0.7in);
            box-sizing: border-box;
            position: relative;
            padding-bottom: 0.22in; /* reserve space for Printed: footer */
        }

        .row {
            display: flex;
            gap: 12px;
        }

        .col {
            flex: 1;
        }

        .stack {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .label {
            font-size: calc(8.5pt * var(--font-scale));
            color: #4b5563;
        }

        .value {
            font-size: calc(10pt * var(--font-scale));
            font-weight: 700;
            color: #111827;
            line-height: 1.15;
        }

        .box {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 8px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .muted {
            color: #6b7280;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: calc(9pt * var(--font-scale));
        }
        .table td {
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
        }
        .table td:first-child {
            width: 45%;
            background: #f9fafb;
            font-weight: 700;
        }
        .money {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .items {
            height: 1.55in; /* reduced to make room for Terms & Conditions */
            overflow: hidden;
        }

        .items ul {
            padding-left: 16px;
            margin: 6px 0 0 0;
            font-size: calc(9pt * var(--font-scale));
            line-height: 1.15;
        }

        .items .line {
            font-size: calc(9pt * var(--font-scale));
            line-height: 1.15;
            margin-top: 6px;
        }

        .signature-row {
            display: flex;
            gap: 16px;
            margin-top: 8px;
        }
        .sig {
            flex: 1;
            border-top: 1px solid #9ca3af;
            padding-top: 4px;
            font-size: calc(8pt * var(--font-scale));
            color: #4b5563;
            text-align: center;
        }

        .printed-at {
            position: absolute;
            right: 0;
            bottom: 0;
            font-size: calc(8pt * var(--font-scale));
            color: #6b7280;
        }

        .rate-options {
            display: flex;
            gap: 10px;
            font-size: calc(9pt * var(--font-scale));
            margin-top: 4px;
        }

        .terms {
            margin-top: 8px;
        }
        .terms-content {
            font-size: calc(7.5pt * var(--font-scale));
            line-height: 1.15;
            margin-top: 6px;
            column-count: 2;
            column-gap: 12px;
        }
        .terms-content ol {
            margin: 0;
            padding-left: 16px;
        }
        .terms-content li {
            break-inside: avoid;
            margin: 0 0 4px 0;
        }

        /* Screen-only header */
        .screen-actions {
            display: none;
            margin-bottom: 12px;
        }
        @media screen {
            body { background: #f3f4f6; padding: 16px; }
            .screen-actions { display: flex; gap: 8px; }
            .sheet { background: #fff; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); padding: 16px; }
        }

        @media print {
            .screen-actions { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="screen-actions">
        <button onclick="window.print()" style="padding:10px 14px;border-radius:8px;border:1px solid #d1d5db;background:#111827;color:#fff;font-weight:700;cursor:pointer;">Print</button>
        <button onclick="window.close()" style="padding:10px 14px;border-radius:8px;border:1px solid #d1d5db;background:#fff;color:#111827;font-weight:700;cursor:pointer;">Close</button>
    </div>

    <div class="sheet" id="sheet">
        <!-- Content only (ignore heading/footer as requested) -->
        <div class="row">
            <div class="col box">
                <div class="label">Pawn Ticket #</div>
                <div class="value">{{ $displayPawnTicketNumber ?: '-' }}</div>
            </div>
            <div class="col box">
                <div class="label">{{ !empty($isPartialReceipt) ? 'Partial Transaction Date' : 'Date Loan Granted' }}</div>
                <div class="value">{{ $dateLoanGranted }}</div>
            </div>
            <div class="col box">
                <div class="label">Maturity Date</div>
                <div class="value">{{ $base->maturity_date ? $base->maturity_date->format('M d, Y') : '-' }}</div>
            </div>
            <div class="col box">
                <div class="label">Expiry Date of Redemption</div>
                <div class="value">{{ $base->expiry_date ? $base->expiry_date->format('M d, Y') : '-' }}</div>
            </div>
        </div>

        <div style="height:6px"></div>

        <div class="row">
            <div class="col box" style="flex: 1.6">
                <div class="label">Mr./Ms.</div>
                <div class="value" data-fit style="max-height: 0.38in; overflow:hidden;">
                    {{ trim(($base->first_name ?? '') . ' ' . ($base->last_name ?? '')) ?: '-' }}
                </div>
            </div>
            <div class="col box" style="flex: 2.4">
                <div class="label">Address</div>
                <div class="value" data-fit style="max-height: 0.38in; overflow:hidden;">
                    {{ $base->address ?: '-' }}
                </div>
            </div>
        </div>

        <div style="height:6px"></div>

        <div class="row">
            <div class="col stack">
                <div class="box items">
                    <div class="label">Description of the Pawn</div>
                    @if($items && $items->count() > 0)
                        <ul data-fit style="max-height: 1.25in; overflow:hidden; margin-bottom:0;">
                            @foreach($items->take(6) as $line)
                                <li>{{ $line }}</li>
                            @endforeach
                        </ul>
                        @if($items->count() > 6)
                            <div class="line muted">(and {{ $items->count() - 6 }} more item(s)…)</div>
                        @endif
                    @else
                        <div class="line" data-fit style="max-height: 1.25in; overflow:hidden;">
                            {{ $base->item_description ?: '-' }}
                        </div>
                    @endif
                </div>

                <div class="box">
                    <div class="grid-2" style="gap: 12px;">
                        <div>
                            <div class="label">Contact Number</div>
                            <div class="value" data-fit style="max-height: 0.34in; overflow:hidden;">{{ $base->phone_number ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="label">ID Presented</div>
                            <div class="value" data-fit style="max-height: 0.34in; overflow:hidden;">{{ $base->id_presented ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col box" style="max-width: 2.85in;">
                @if(!empty($isPartialReceipt))
                    <div class="label">Principal Summary</div>
                    <table class="table" style="margin-top:6px;">
                        <tr>
                            <td>Pawn Ticket #</td>
                            <td class="money">{{ $displayPawnTicketNumber ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td>Principal Before</td>
                            <td class="money">₱{{ number_format((float) ($principalBefore ?? 0), 2) }}</td>
                        </tr>
                        <tr>
                            <td>{{ ((float) ($principalChange ?? 0)) >= 0 ? 'Principal Paid' : 'Principal Increase' }}</td>
                            <td class="money">₱{{ number_format(abs((float) ($principalChange ?? 0)), 2) }}</td>
                        </tr>
                    </table>

                    <div style="height:10px"></div>

                    <div class="label">Payment Breakdown</div>
                    <table class="table" style="margin-top:6px;">
                        <tr>
                            <td>Principal After</td>
                            <td class="money">₱{{ number_format((float) ($principalAfter ?? 0), 2) }}</td>
                        </tr>
                        <tr>
                            <td>Service Charge</td>
                            <td class="money">₱{{ number_format((float) ($serviceCharge ?? 0), 2) }}</td>
                        </tr>
                        <tr>
                            <td>Late Days Charge</td>
                            <td class="money">₱{{ number_format((float) ($lateDaysCharge ?? 0), 2) }}</td>
                        </tr>
                        <tr>
                            <td>Interest / Other Charges</td>
                            <td class="money">₱{{ number_format((float) ($interestAndOtherCharges ?? 0), 2) }}</td>
                        </tr>
                        <tr>
                            <td>{{ $cashLabel ?: 'Amount Paid' }}</td>
                            <td class="money" style="font-weight:700; font-size: calc(10pt * var(--font-scale));">₱{{ number_format((float) ($cashAmount ?? 0), 2) }}</td>
                        </tr>
                    </table>
                @else
                    <table class="table">
                        <tr>
                            <td>Principal</td>
                            <td class="money">₱{{ number_format($principal, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Interest</td>
                            <td class="money">₱{{ number_format($interest, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Service Charge</td>
                            <td class="money">₱{{ number_format($serviceCharge, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Net Proceeds</td>
                            <td class="money">₱{{ number_format($netProceeds, 2) }}</td>
                        </tr>
                    </table>

                    <div style="height:8px"></div>
                    <div class="label">Effective Interest Rate</div>
                    <div class="rate-options">
                        @php $p = (string) ($base->interest_rate_period ?? ''); @endphp
                        <span>[{{ $p === 'per_annum' ? 'x' : ' ' }}] Per Annum</span>
                        <span>[{{ $p === 'per_month' ? 'x' : ' ' }}] Per Month</span>
                        <span>[{{ $p === 'others' ? 'x' : ' ' }}] Others</span>
                    </div>
                @endif
            </div>
        </div>

        <div style="height:6px"></div>

        @php
            // Placeholder terms; replace with your official wording later.
            $terms = [
                'This pawn ticket must be presented for renewal or redemption.',
                'Interest, service charges, and other applicable charges may apply pursuant to company policy and applicable laws/regulations.',
                'Items not redeemed within the allowed period may be subject to sale/auction in accordance with applicable laws.',
                'The pawner warrants lawful ownership and authenticity of the pawned item(s).',
                'Any alteration or tampering may invalidate this ticket.',
                'Keep this ticket in a safe place. Lost tickets may require additional verification.',
            ];
        @endphp
        <div class="box terms">
            <div class="label">Terms &amp; Conditions</div>
            <div class="terms-content" data-fit style="height: 0.78in; overflow:hidden;">
                <ol>
                    @foreach($terms as $t)
                        <li>{{ $t }}</li>
                    @endforeach
                </ol>
            </div>
        </div>

        <div class="signature-row">
            <div class="sig">Signature / Thumbmark of Pawner</div>
            <div class="sig">Authorized Representative</div>
        </div>

        <div class="printed-at">
            {{ $printTrackingCode ?? '' }}
        </div>
    </div>

    <script>
        // Best-effort auto-fit: shrink font size inside [data-fit] blocks if they overflow their container.
        (function () {
            function shrinkToFit(el) {
                const style = window.getComputedStyle(el);
                let size = parseFloat(style.fontSize);
                const min = 4; // smaller min now that base typography is ~50% smaller
                let safety = 0;
                while (el.scrollHeight > el.clientHeight + 1 && size > min && safety < 25) {
                    size -= 0.5;
                    el.style.fontSize = size + 'px';
                    safety++;
                }
            }

            function run() {
                document.querySelectorAll('[data-fit]').forEach(shrinkToFit);
            }

            window.addEventListener('load', function () {
                run();
                // auto-open print dialog
                setTimeout(() => window.print(), 50);
            });
        })();
    </script>
</body>
</html>

