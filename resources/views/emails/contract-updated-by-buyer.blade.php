<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contract Update Notification</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 0;
        }
        .header {
            background-color: #ffffff;
            padding: 32px 40px 24px;
            border-bottom: 1px solid #e5e7eb;
        }
        .logo {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }
        .header-title {
            font-size: 24px;
            font-weight: 600;
            color: #111827;
            margin: 0;
            line-height: 1.3;
        }
        .content {
            padding: 32px 40px;
        }
        .greeting {
            font-size: 16px;
            color: #374151;
            margin-bottom: 24px;
        }
        .notification-type {
            background-color: #f3f4f6;
            border-left: 3px solid #3b82f6;
            padding: 16px 20px;
            margin: 24px 0;
            font-size: 14px;
            color: #374151;
        }
        .contract-details {
            background-color: #fafafa;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 24px;
            margin: 24px 0;
        }
        .detail-row {
            display: flex;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f3f4f6;
        }
        .detail-row:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        .detail-label {
            font-weight: 500;
            color: #6b7280;
            min-width: 140px;
            font-size: 14px;
        }
        .detail-value {
            color: #111827;
            font-size: 14px;
            font-weight: 500;
        }
        .transaction-box {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 6px;
            padding: 20px;
            margin: 24px 0;
            text-align: center;
        }
        .transaction-label {
            font-size: 13px;
            color: #92400e;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .transaction-id {
            font-size: 16px;
            font-weight: 600;
            color: #92400e;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
            letter-spacing: 0.5px;
        }
        .action-button {
            display: inline-block;
            background-color: #3b82f6;
            color: #ffffff;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            font-size: 14px;
            margin: 24px 0;
        }
        .note {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 16px;
            margin: 24px 0;
            font-size: 14px;
            color: #475569;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 24px 40px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 13px;
            color: #6b7280;
        }
        .footer-company {
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
        }
        @media (max-width: 640px) {
            .container {
                margin: 0;
            }
            .header, .content, .footer {
                padding-left: 20px;
                padding-right: 20px;
            }
            .detail-row {
                flex-direction: column;
            }
            .detail-label {
                min-width: auto;
                margin-bottom: 4px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">GenieB2B</div>
            <h1 class="header-title">Contract Update Notification</h1>
        </div>

        <div class="content">
            <div class="greeting">
                Dear Admin Team,
            </div>

            <p>A contract has been updated by the buyer and requires your review. The details are outlined below:</p>

            <div class="notification-type">
                <strong>Update Type:</strong> {{ $updateType ?: 'Contract Details Updated' }}
            </div>

            <div class="contract-details">
                <div class="detail-row">
                    <div class="detail-label">Contract Number</div>
                    <div class="detail-value">{{ $contract->contract_number ?: 'N/A' }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Buyer</div>
                    <div class="detail-value">{{ $contract->buyer->name ?: ($contract->buyer->first_name . ' ' . $contract->buyer->last_name) ?: 'Unknown' }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Buyer Email</div>
                    <div class="detail-value">{{ $contract->buyer->email ?: 'N/A' }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Seller</div>
                    <div class="detail-value">{{ $contract->seller->name ?: ($contract->seller->first_name . ' ' . $contract->seller->last_name) ?: 'Unknown' }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Current Status</div>
                    <div class="detail-value">{{ ucwords(str_replace('_', ' ', $contract->status ?: 'unknown')) }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Total Amount</div>
                    <div class="detail-value">{{ $contract->currency ?: 'USD' }} {{ number_format($contract->total_amount ?: 0, 2) }}</div>
                </div>
            </div>

            @if(isset($contract->buyer_transaction_id) && $contract->buyer_transaction_id)
            <div class="transaction-box">
                <div class="transaction-label">Buyer Transaction ID</div>
                <div class="transaction-id">{{ $contract->buyer_transaction_id }}</div>
            </div>
            @endif

            <div style="text-align: center;">
                <a href="{{ url('/admin/contracts/' . $contract->id) }}" class="action-button">
                    View Contract Details
                </a>
            </div>

            <div class="note">
                <strong>Note:</strong> You can reply directly to this email to contact the buyer at {{ $contract->buyer->email ?: 'their registered email address' }}.
            </div>

            <p>Please review this contract update and take the appropriate action.</p>

            <p>Best regards,<br>
            GenieB2B Platform</p>
        </div>

        <div class="footer">
            <div class="footer-company">GenieB2B Management Platform</div>
            <div>This email was sent from {{ $contract->buyer->name ?: ($contract->buyer->first_name . ' ' . $contract->buyer->last_name) ?: 'a buyer' }} via our platform.</div>
            <div style="margin-top: 8px;">© {{ date('Y') }} GenieB2B. All rights reserved.</div>
        </div>
    </div>
</body>
</html>
